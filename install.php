<?php
/**
 * VosCMS 설치 마법사
 * 웹 브라우저에서 실행하여 VosCMS를 설치합니다.
 *
 * 설치 완료 후 이 파일은 자동으로 비활성화됩니다.
 */

// 이미 설치된 경우 차단
if (file_exists(__DIR__ . '/.env') && file_exists(__DIR__ . '/storage/.installed')) {
    die('<h1>VosCMS is already installed.</h1><p>Delete <code>storage/.installed</code> to reinstall.</p>');
}

define('BASE_PATH', __DIR__);
$step = $_POST['step'] ?? $_GET['step'] ?? '1';
$errors = [];
$success = '';

// Step 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case '2': // DB 연결 테스트
            $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
            $dbPort = trim($_POST['db_port'] ?? '3306');
            $dbName = trim($_POST['db_name'] ?? '');
            $dbUser = trim($_POST['db_user'] ?? '');
            $dbPass = $_POST['db_pass'] ?? '';
            $dbPrefix = trim($_POST['db_prefix'] ?? 'rzx_');

            if (!$dbName || !$dbUser) {
                $errors[] = 'DB 이름과 사용자명은 필수입니다.';
            } else {
                try {
                    $pdo = new PDO(
                        "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4",
                        $dbUser, $dbPass,
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                    // DB 존재 확인, 없으면 생성
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `{$dbName}`");

                    // 세션에 저장
                    session_start();
                    $_SESSION['install_db'] = compact('dbHost', 'dbPort', 'dbName', 'dbUser', 'dbPass', 'dbPrefix');
                    $step = '3';
                } catch (PDOException $e) {
                    $errors[] = 'DB 연결 실패: ' . $e->getMessage();
                }
            }
            break;

        case '3': // 테이블 생성
            session_start();
            $db = $_SESSION['install_db'] ?? null;
            if (!$db) { $step = '2'; break; }

            try {
                $pdo = new PDO(
                    "mysql:host={$db['dbHost']};port={$db['dbPort']};dbname={$db['dbName']};charset=utf8mb4",
                    $db['dbUser'], $db['dbPass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );

                // 코어 마이그레이션만 실행
                $migrationDir = BASE_PATH . '/database/migrations/core';
                $files = glob($migrationDir . '/*.sql');
                sort($files);
                $executed = 0;
                foreach ($files as $file) {
                    $sql = file_get_contents($file);
                    if ($sql) {
                        // prefix 치환
                        if ($db['dbPrefix'] !== 'rzx_') {
                            $sql = str_replace('rzx_', $db['dbPrefix'], $sql);
                        }
                        $pdo->exec($sql);
                        $executed++;
                    }
                }

                $_SESSION['install_tables_done'] = true;
                $_SESSION['install_tables_count'] = $executed;
                $step = '4';
            } catch (PDOException $e) {
                $errors[] = '테이블 생성 실패: ' . $e->getMessage();
            }
            break;

        case '4': // 관리자 계정 생성
            session_start();
            $db = $_SESSION['install_db'] ?? null;
            if (!$db) { $step = '2'; break; }

            $adminEmail = trim($_POST['admin_email'] ?? '');
            $adminPass = $_POST['admin_pass'] ?? '';
            $adminName = trim($_POST['admin_name'] ?? 'Administrator');
            $siteName = trim($_POST['site_name'] ?? 'VosCMS');
            $siteUrl = trim($_POST['site_url'] ?? '');
            $adminPath = trim($_POST['admin_path'] ?? 'admin');
            $locale = $_POST['locale'] ?? 'ko';
            $timezone = $_POST['timezone'] ?? 'Asia/Seoul';

            if (!$adminEmail || !$adminPass) {
                $errors[] = '관리자 이메일과 비밀번호는 필수입니다.';
            } elseif (strlen($adminPass) < 8) {
                $errors[] = '비밀번호는 8자 이상이어야 합니다.';
            } else {
                try {
                    $pdo = new PDO(
                        "mysql:host={$db['dbHost']};port={$db['dbPort']};dbname={$db['dbName']};charset=utf8mb4",
                        $db['dbUser'], $db['dbPass'],
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                    $pfx = $db['dbPrefix'];

                    // 관리자 계정 생성
                    $userId = bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(6));
                    $hashedPass = password_hash($adminPass, PASSWORD_BCRYPT);

                    $pdo->prepare("INSERT INTO {$pfx}users (id, email, password, name, nick_name, role, is_active, email_verified_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'admin', 1, NOW(), NOW(), NOW())")
                        ->execute([$userId, $adminEmail, $hashedPass, $adminName, $adminName]);

                    // 관리자 권한 등록
                    $pdo->prepare("INSERT IGNORE INTO {$pfx}admins (user_id, is_master, permissions, created_at) VALUES (?, 1, ?, NOW())")
                        ->execute([$userId, json_encode(['dashboard','reservations','services','staff','members','site','settings'])]);

                    // 기본 설정
                    $settings = [
                        'site_name' => $siteName,
                        'admin_path' => $adminPath,
                        'site_timezone' => $timezone,
                        'default_locale' => $locale,
                        'site_locale' => $locale,
                    ];
                    $settingStmt = $pdo->prepare("INSERT INTO {$pfx}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                    foreach ($settings as $k => $v) {
                        $settingStmt->execute([$k, $v]);
                    }

                    // 기본 메뉴 (사이트맵 + 메뉴 아이템)
                    $pdo->exec("INSERT IGNORE INTO {$pfx}sitemaps (id, title, sort_order) VALUES (1, 'Main Menu', 0)");
                    $menuItems = [
                        [1, null, 'Home', '/', '_self', 'page', 1, 1],
                    ];
                    $menuStmt = $pdo->prepare("INSERT IGNORE INTO {$pfx}menu_items (sitemap_id, parent_id, title, url, target, menu_type, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    foreach ($menuItems as $mi) {
                        $menuStmt->execute($mi);
                    }

                    // 기본 페이지
                    $pdo->prepare("INSERT IGNORE INTO {$pfx}page_contents (page_slug, page_type, locale, title, content, is_system, is_active) VALUES (?, 'widget', ?, ?, '', 1, 1)")
                        ->execute(['index', $locale, $siteName]);

                    // .env 파일 생성
                    $appKey = 'base64:' . base64_encode(random_bytes(32));
                    $jwtSecret = bin2hex(random_bytes(32));
                    $envContent = "# VosCMS Configuration
APP_NAME=\"{$siteName}\"
APP_ENV=production
APP_DEBUG=false
APP_URL={$siteUrl}
APP_TIMEZONE={$timezone}
APP_LOCALE={$locale}
ADMIN_PATH={$adminPath}
APP_KEY={$appKey}

DB_CONNECTION=mysql
DB_HOST={$db['dbHost']}
DB_PORT={$db['dbPort']}
DB_DATABASE={$db['dbName']}
DB_USERNAME={$db['dbUser']}
DB_PASSWORD={$db['dbPass']}
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_PREFIX={$db['dbPrefix']}

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

JWT_SECRET={$jwtSecret}
JWT_TTL=60
JWT_REFRESH_TTL=20160

CACHE_DRIVER=file
CACHE_PREFIX=vos_
";
                    file_put_contents(BASE_PATH . '/.env', $envContent);

                    // 설치 완료 플래그
                    file_put_contents(BASE_PATH . '/storage/.installed', date('Y-m-d H:i:s'));

                    $_SESSION['install_complete'] = true;
                    $_SESSION['install_admin_url'] = $siteUrl . '/' . $adminPath;
                    $step = '5';
                } catch (PDOException $e) {
                    $errors[] = '설정 저장 실패: ' . $e->getMessage();
                }
            }
            break;
    }
}
if (!isset($_SESSION)) session_start();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VosCMS Install</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }</style>
</head>
<body class="bg-zinc-100 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-lg">

    <!-- 로고 -->
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-zinc-800">Vos<span class="text-blue-600">CMS</span></h1>
        <p class="text-zinc-500 mt-1">Value Of Style CMS</p>
    </div>

    <!-- 프로그레스 -->
    <div class="flex items-center justify-center gap-2 mb-8">
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <div class="flex items-center">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                <?= $i < $step ? 'bg-blue-600 text-white' : ($i == $step ? 'bg-blue-600 text-white ring-4 ring-blue-200' : 'bg-zinc-300 text-zinc-500') ?>">
                <?= $i < $step ? '✓' : $i ?>
            </div>
            <?php if ($i < 5): ?><div class="w-8 h-0.5 <?= $i < $step ? 'bg-blue-600' : 'bg-zinc-300' ?>"></div><?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>

    <div class="bg-white rounded-2xl shadow-lg p-8">

    <?php if ($errors): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
        <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($step === '1' || $step === 1): // 환경 체크 ?>
    <h2 class="text-xl font-bold text-zinc-800 mb-6">1. 환경 체크</h2>
    <?php
    $checks = [
        ['PHP Version >= 8.1', version_compare(PHP_VERSION, '8.1.0', '>=')],
        ['PDO MySQL', extension_loaded('pdo_mysql')],
        ['mbstring', extension_loaded('mbstring')],
        ['json', extension_loaded('json')],
        ['fileinfo', extension_loaded('fileinfo')],
        ['openssl', extension_loaded('openssl')],
        ['storage/ 쓰기 권한', is_writable(BASE_PATH . '/storage')],
        ['.env 쓰기 권한', is_writable(BASE_PATH) || (file_exists(BASE_PATH . '/.env') && is_writable(BASE_PATH . '/.env'))],
    ];
    $allPassed = true;
    foreach ($checks as [$label, $ok]):
        if (!$ok) $allPassed = false;
    ?>
    <div class="flex items-center justify-between py-2 border-b border-zinc-100">
        <span class="text-sm text-zinc-700"><?= $label ?></span>
        <?php if ($ok): ?>
        <span class="text-green-600 text-sm font-bold">✓ OK</span>
        <?php else: ?>
        <span class="text-red-600 text-sm font-bold">✗ FAIL</span>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="mt-4 text-xs text-zinc-400">PHP <?= PHP_VERSION ?></div>

    <?php if ($allPassed): ?>
    <form method="GET" class="mt-6">
        <input type="hidden" name="step" value="2">
        <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition">다음 →</button>
    </form>
    <?php else: ?>
    <p class="mt-6 text-red-600 text-sm font-bold">환경 요구사항을 충족하지 않습니다. 서버 설정을 확인하세요.</p>
    <?php endif; ?>

    <?php elseif ($step === '2'): // DB 설정 ?>
    <h2 class="text-xl font-bold text-zinc-800 mb-6">2. 데이터베이스 설정</h2>
    <form method="POST">
        <input type="hidden" name="step" value="2">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1">호스트</label>
                    <input type="text" name="db_host" value="127.0.0.1" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1">포트</label>
                    <input type="text" name="db_port" value="3306" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1">데이터베이스 이름</label>
                <input type="text" name="db_name" placeholder="voscms" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-zinc-400 mt-1">존재하지 않으면 자동 생성됩니다.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1">사용자명</label>
                <input type="text" name="db_user" placeholder="root" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1">비밀번호</label>
                <input type="password" name="db_pass" class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1">테이블 접두사</label>
                <input type="text" name="db_prefix" value="rzx_" class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
        <button type="submit" class="w-full mt-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition">연결 테스트 + 다음 →</button>
    </form>

    <?php elseif ($step === '3'): // 테이블 생성 ?>
    <h2 class="text-xl font-bold text-zinc-800 mb-6">3. 데이터베이스 설정</h2>
    <div class="text-center py-6">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <p class="text-lg font-bold text-zinc-800">DB 연결 성공!</p>
        <p class="text-sm text-zinc-500 mt-2">마이그레이션 파일 <?= count(glob(BASE_PATH . '/database/migrations/*.sql')) ?>개를 실행합니다.</p>
    </div>
    <form method="POST">
        <input type="hidden" name="step" value="3">
        <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition">테이블 생성 →</button>
    </form>

    <?php elseif ($step === '4'): // 관리자 + 사이트 설정 ?>
    <h2 class="text-xl font-bold text-zinc-800 mb-6">4. 사이트 설정</h2>
    <form method="POST">
        <input type="hidden" name="step" value="4">
        <div class="space-y-4">
            <div class="pb-4 border-b border-zinc-200">
                <h3 class="text-sm font-bold text-zinc-600 mb-3">사이트 정보</h3>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1">사이트 이름</label>
                    <input type="text" name="site_name" value="VosCMS" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mt-3">
                    <label class="block text-sm font-medium text-zinc-700 mb-1">사이트 URL</label>
                    <input type="url" name="site_url" placeholder="https://example.com" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="grid grid-cols-3 gap-3 mt-3">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 mb-1">관리자 경로</label>
                        <input type="text" name="admin_path" value="admin" class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 mb-1">언어</label>
                        <select name="locale" class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="ko">한국어</option>
                            <option value="ja">日本語</option>
                            <option value="en">English</option>
                            <option value="zh_CN">中文(简体)</option>
                            <option value="zh_TW">中文(繁體)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 mb-1">시간대</label>
                        <select name="timezone" class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="Asia/Seoul">서울 (KST)</option>
                            <option value="Asia/Tokyo">도쿄 (JST)</option>
                            <option value="Asia/Shanghai">상하이 (CST)</option>
                            <option value="UTC">UTC</option>
                            <option value="America/New_York">뉴욕 (EST)</option>
                            <option value="Europe/London">런던 (GMT)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-bold text-zinc-600 mb-3">관리자 계정</h3>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1">이름</label>
                    <input type="text" name="admin_name" value="Administrator" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mt-3">
                    <label class="block text-sm font-medium text-zinc-700 mb-1">이메일</label>
                    <input type="email" name="admin_email" placeholder="admin@example.com" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mt-3">
                    <label class="block text-sm font-medium text-zinc-700 mb-1">비밀번호</label>
                    <input type="password" name="admin_pass" placeholder="8자 이상" required minlength="8" class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>
        <button type="submit" class="w-full mt-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition">설치 완료 →</button>
    </form>

    <?php elseif ($step === '5'): // 완료 ?>
    <div class="text-center py-6">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-zinc-800">설치 완료!</h2>
        <p class="text-zinc-500 mt-2">VosCMS가 성공적으로 설치되었습니다.</p>

        <div class="mt-6 p-4 bg-zinc-50 rounded-lg text-left text-sm">
            <p class="text-zinc-600"><strong>관리자 페이지:</strong></p>
            <p class="text-blue-600 font-mono"><?= htmlspecialchars($_SESSION['install_admin_url'] ?? '/admin') ?></p>
        </div>

        <a href="<?= htmlspecialchars($_SESSION['install_admin_url'] ?? '/admin') ?>"
           class="inline-block mt-6 px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition">
            관리자로 이동 →
        </a>
    </div>
    <?php endif; ?>

    </div>

    <p class="text-center text-xs text-zinc-400 mt-6">VosCMS — Value Of Style CMS</p>
</div>
</body>
</html>
