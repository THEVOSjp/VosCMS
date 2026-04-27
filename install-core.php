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

// localhost / 사설 IP 차단 — 실제 도메인에서만 설치 가능
$_installHost = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
$_blockedHosts = ['localhost', '127.0.0.1', '::1', '0.0.0.0'];
$_blockedSuffixes = ['.local', '.test', '.example', '.invalid', '.localhost'];
$_isBlocked = in_array($_installHost, $_blockedHosts);
foreach ($_blockedSuffixes as $_sfx) {
    if (str_ends_with($_installHost, $_sfx)) $_isBlocked = true;
}
if (!$_isBlocked && filter_var($_installHost, FILTER_VALIDATE_IP) !== false) {
    // 사설 IP 대역 차단 (192.168.x.x, 10.x.x.x, 172.16-31.x.x)
    if (filter_var($_installHost, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        $_isBlocked = true;
    }
}
if ($_isBlocked) {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>VosCMS</title>
    <style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#f4f4f5}
    .box{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.08);max-width:480px;text-align:center}
    h1{color:#dc2626;font-size:1.5rem}p{color:#71717a;line-height:1.6}</style></head>
    <body><div class="box"><h1>Installation Blocked</h1>
    <p>VosCMS can only be installed on a public domain with SSL.<br>
    <code>localhost</code>, private IPs, and test domains are not allowed.</p>
    <p style="margin-top:20px;font-size:.85rem;color:#a1a1aa">Current host: <code>' . htmlspecialchars($_installHost) . '</code></p>
    </div></body></html>');
}

define('BASE_PATH', __DIR__);

// 세션 시작 (언어 선택 저장용)
if (session_status() === PHP_SESSION_NONE) session_start();

// 번역 시스템 로드
$_langData = file_exists(BASE_PATH . '/resources/lang/install.php')
    ? include(BASE_PATH . '/resources/lang/install.php')
    : ['languages' => ['en' => 'English'], 'translations' => ['en' => []]];
$_installLangs = $_langData['languages'];
$_it = $_langData['translations'];

// .env 없이 최초 접속 = 새 설치 → 이전 세션 전체 초기화
if (!isset($_POST['step']) && !isset($_GET['step']) && !file_exists(BASE_PATH . '/.env')) {
    session_destroy();
    session_start();
}

// 세션 → URL ?lang= 폴백 → 브라우저 Accept-Language → 'en'
// 세션이 어떤 이유로 끊겨도 install.php 가 ?lang= 를 넘겨주면 복구됨
$installLocale = $_SESSION['install_locale'] ?? null;
if (!$installLocale && !empty($_GET['lang'])) {
    $_lang = $_GET['lang'];
    if (isset($_installLangs[$_lang])) {
        $installLocale = $_lang;
        $_SESSION['install_locale'] = $_lang;  // 세션 복구
    }
}
$step = $_POST['step'] ?? $_GET['step'] ?? ($installLocale ? '1' : '0');

// Step 0/1 은 install.php 가 처리. 직접 접근하면 되돌려보냄
if ((int)$step < 2 || (int)$step > 5) {
    header('Location: install.php' . ($step ? '?step=' . urlencode($step) : ''));
    exit;
}

$errors = [];
$success = '';

// 번역 헬퍼
function __t(string $key): string {
    global $_it, $installLocale;
    return $_it[$installLocale][$key] ?? $_it['en'][$key] ?? $key;
}

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
                $errors[] = __t('db_required');
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

                    // 세션 + 폼 전달용 저장
                    $_SESSION['install_db'] = compact('dbHost', 'dbPort', 'dbName', 'dbUser', 'dbPass', 'dbPrefix');
                    session_write_close();
                    session_start();
                    $step = '3';
                } catch (PDOException $e) {
                    $errors[] = __t('db_fail') . ': ' . $e->getMessage();
                }
            }
            break;

        case '3': // 테이블 생성
            $db = $_SESSION['install_db'] ?? null;
            // 세션 폴백: hidden 필드에서 복원
            if (!$db && !empty($_POST['_db'])) {
                $db = json_decode(base64_decode($_POST['_db']), true);
                if ($db) $_SESSION['install_db'] = $db;
            }
            if (!$db) { $step = '2'; break; }

            try {
                $pdo = new PDO(
                    "mysql:host={$db['dbHost']};port={$db['dbPort']};dbname={$db['dbName']};charset=utf8mb4",
                    $db['dbUser'], $db['dbPass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );

                // 코어 + 기능 마이그레이션 모두 실행 (core 먼저, 그다음 migrations)
                //  - core/*.sql : 필수 부트스트랩 (users, settings, sessions 등)
                //  - migrations/*.sql : 기능별 (reservations, orders, payments, boards 등)
                $dirs = [
                    BASE_PATH . '/database/migrations/core',
                    BASE_PATH . '/database/migrations/migrations',
                ];
                $executed = 0;
                $skipped = 0;
                foreach ($dirs as $migrationDir) {
                    if (!is_dir($migrationDir)) continue;
                    $files = glob($migrationDir . '/*.sql');
                    sort($files);
                    foreach ($files as $file) {
                        $sql = file_get_contents($file);
                        if (!$sql) continue;
                        // prefix 치환
                        if ($db['dbPrefix'] !== 'rzx_') {
                            $sql = str_replace('rzx_', $db['dbPrefix'], $sql);
                        }
                        try {
                            $pdo->exec($sql);
                            $executed++;
                        } catch (PDOException $e) {
                            // 멱등성/레거시 호환 오류는 skip (core 와 충돌하는 legacy migrations 대응)
                            $msg = $e->getMessage();
                            if (str_contains($msg, 'already exists')
                                || str_contains($msg, 'Duplicate column')
                                || str_contains($msg, 'Duplicate key')
                                || str_contains($msg, 'Duplicate entry')
                                || str_contains($msg, 'check that column/key exists')
                                || str_contains($msg, 'Cannot cast')
                                || str_contains($msg, 'Unknown column')
                                || str_contains($msg, 'Incorrect integer value')
                                || str_contains($msg, "doesn't exist")
                                // FK 제약 형식 불일치 (legacy migrations 의 UUID vs INT 등)
                                || str_contains($msg, 'Foreign key constraint is incorrectly formed')
                                || str_contains($msg, 'errno: 150')
                                // 컬럼 타입 변경 불가 등 ALTER 호환성
                                || str_contains($msg, 'Cannot change column')
                                || str_contains($msg, 'errno: 121')   // duplicate FK name
                                || str_contains($msg, 'errno: 152')   // FK 이름 충돌
                                || str_contains($msg, 'check that')) {
                                $skipped++;
                                continue;
                            }
                            throw $e;
                        }
                    }
                }
                $_SESSION['install_tables_skipped'] = $skipped;

                $_SESSION['install_tables_done'] = true;
                $_SESSION['install_tables_count'] = $executed;
                session_write_close();
                session_start();
                $step = '4';
            } catch (PDOException $e) {
                $errors[] = __t('table_fail') . ': ' . $e->getMessage();
            }
            break;

        case '4': // 관리자 계정 생성
            // 대량 인서트(메뉴/시드/번역 ~900건) + 라이선스 서버 호출 → 30초 기본 한계 초과 가능
            // PHP-FPM 워커 타임아웃 시 502 Bad Gateway. 5분으로 확장.
            @set_time_limit(300);
            $db = $_SESSION['install_db'] ?? null;
            if (!$db && !empty($_POST['_db'])) {
                $db = json_decode(base64_decode($_POST['_db']), true);
                if ($db) $_SESSION['install_db'] = $db;
            }
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
                $errors[] = __t('admin_required');
            } elseif (strlen($adminPass) < 8) {
                $errors[] = __t('pw_length');
            } else {
                try {
                    $pdo = new PDO(
                        "mysql:host={$db['dbHost']};port={$db['dbPort']};dbname={$db['dbName']};charset=utf8mb4",
                        $db['dbUser'], $db['dbPass'],
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                    $pfx = $db['dbPrefix'];

                    // 트랜잭션 시작 — 1,400+건의 INSERT를 단일 fsync로 처리 (autocommit 시 17초 → 1초 미만)
                    $pdo->beginTransaction();

                    // 관리자 계정 생성 (v2.1: rzx_users에 supervisor role로 통합)
                    $userId = bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(6));
                    $hashedPass = password_hash($adminPass, PASSWORD_BCRYPT);
                    $perms = json_encode(['dashboard','reservations','services','staff','members','site','settings']);

                    // 기존 사용자 확인
                    $chk = $pdo->prepare("SELECT id FROM {$pfx}users WHERE email = ?");
                    $chk->execute([$adminEmail]);
                    $existing = $chk->fetchColumn();
                    if ($existing) {
                        $userId = $existing;
                        $pdo->prepare("UPDATE {$pfx}users SET password = ?, name = ?, nick_name = ?, role = 'supervisor', permissions = ?, is_active = 1 WHERE id = ?")
                            ->execute([$hashedPass, $adminName, $adminName, $perms, $userId]);
                    } else {
                        $pdo->prepare("INSERT INTO {$pfx}users (id, email, password, name, nick_name, role, permissions, is_active, email_verified_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'supervisor', ?, 1, NOW(), NOW(), NOW())")
                            ->execute([$userId, $adminEmail, $hashedPass, $adminName, $adminName, $perms]);
                    }

                    // 기본 설정
                    $settings = [
                        'site_name' => $siteName,
                        'admin_path' => $adminPath,
                        'site_timezone' => $timezone,
                        'default_locale' => $locale,
                        'site_locale' => $locale,
                        'default_language' => $locale,
                        'force_locale' => '1',
                        'language_auto_detect' => '0',
                        'home_page' => 'home',
                        'site_layout' => 'modern',
                    ];

                    // 설치한 언어로 세션/쿠키 즉시 설정
                    $_SESSION['locale'] = $locale;
                    if (!headers_sent()) {
                        setcookie('locale', $locale, ['expires' => time() + 86400 * 365, 'path' => '/', 'httponly' => false, 'samesite' => 'Lax']);
                    }
                    $settingStmt = $pdo->prepare("INSERT INTO {$pfx}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                    foreach ($settings as $k => $v) {
                        $settingStmt->execute([$k, $v]);
                    }

                    // 기본 사이트맵
                    $pdo->exec("INSERT IGNORE INTO {$pfx}sitemaps (id, title, sort_order) VALUES (1, 'Main Menu', 0)");
                    $pdo->exec("INSERT IGNORE INTO {$pfx}sitemaps (id, title, sort_order) VALUES (2, 'Utility Menu', 1)");
                    $pdo->exec("INSERT IGNORE INTO {$pfx}sitemaps (id, title, sort_order) VALUES (3, 'Footer Menu', 2)");
                    $pdo->exec("INSERT IGNORE INTO {$pfx}sitemaps (id, title, sort_order) VALUES (4, 'Unlinked', 99)");

                    // Main Menu
                    $menuStmt = $pdo->prepare("INSERT IGNORE INTO {$pfx}menu_items (sitemap_id, parent_id, title, url, target, menu_type, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $menuStmt->execute([1, null, 'Home', 'home', '_self', 'page', 1, 1]);
                    $menuStmt->execute([1, null, 'Notice', 'notice', '_self', 'board', 2, 1]);
                    $menuStmt->execute([1, null, 'Free Board', 'free', '_self', 'board', 3, 1]);
                    $menuStmt->execute([1, null, 'Q&A', 'qna', '_self', 'board', 4, 1]);
                    $menuStmt->execute([1, null, 'FAQ', 'faq', '_self', 'board', 5, 1]);

                    // 메뉴 다국어 번역은 푸터/언링크드 메뉴를 모두 추가한 후 한 번에 처리 (아래 _menuTranslations 블록)

                    // Footer Menu — 법적 페이지
                    $menuStmt->execute([3, null, 'Terms of Service', 'terms', '_self', 'page', 1, 1]);
                    $menuStmt->execute([3, null, 'Privacy Policy', 'privacy', '_self', 'page', 2, 1]);
                    $menuStmt->execute([3, null, 'Refund Policy', 'refund-policy', '_self', 'page', 3, 1]);
                    $menuStmt->execute([3, null, 'Data Policy', 'data-policy', '_self', 'page', 4, 1]);
                    $menuStmt->execute([3, null, '特定商取引法に基づく表記', 'tokushoho', '_self', 'page', 5, 1]);
                    $menuStmt->execute([3, null, '資金決済法に基づく表示', 'funds-settlement', '_self', 'page', 6, 1]);

                    // Unlinked — 메뉴 미연결 페이지 관리용
                    $menuStmt->execute([4, null, $siteName, 'index', '_self', 'page', 1, 1]);

                    // 기본 페이지 — 홈 (시스템, 삭제 불가)
                    $_homeTitle = ['ko'=>'홈','en'=>'Home','ja'=>'ホーム','de'=>'Startseite','es'=>'Inicio','fr'=>'Accueil'][$locale] ?? 'Home';
                    $pdo->prepare("INSERT IGNORE INTO {$pfx}page_contents (page_slug, page_type, locale, title, content, is_system, is_active) VALUES ('home', 'widget', ?, ?, '', 1, 1)")
                        ->execute([$locale, $_homeTitle]);

                    // 기본 페이지 — index (사용자, 리뉴얼용)
                    $pdo->prepare("INSERT IGNORE INTO {$pfx}page_contents (page_slug, page_type, locale, title, content, is_system, is_active) VALUES ('index', 'widget', ?, ?, '', 0, 1)")
                        ->execute([$locale, $siteName]);

                    // 법적 페이지 13개국어 (시드 파일에서 로드)
                    $_seedFile = BASE_PATH . '/database/seeds/legal_pages.php';
                    if (file_exists($_seedFile)) {
                        $_seedRows = include $_seedFile;
                        $_seedStmt = $pdo->prepare("INSERT IGNORE INTO {$pfx}page_contents (page_slug, page_type, locale, title, content, is_system, is_active) VALUES (?, ?, ?, ?, ?, 1, 1)");
                        foreach ($_seedRows as $_sr) {
                            $_seedStmt->execute([$_sr['page_slug'], $_sr['page_type'], $_sr['locale'], $_sr['title'], $_sr['content']]);
                        }
                    }
                    /* 기존 인라인 샘플 제거됨 — 시드 파일에서 로드
                    $_legalPages_SKIP = [
                        'terms_REMOVED' => [
                            'ko' => ['이용약관', '<h2>제1조 (목적)</h2><p>이 약관은 본 사이트가 제공하는 서비스의 이용 조건 및 절차, 회사와 이용자의 권리·의무 및 책임사항을 규정함을 목적으로 합니다.</p><h2>제2조 (정의)</h2><p>①「서비스」란 본 사이트를 통해 제공되는 모든 온라인 서비스를 의미합니다.<br>②「이용자」란 본 약관에 따라 서비스를 이용하는 회원 및 비회원을 말합니다.</p><h2>제3조 (약관의 효력)</h2><p>본 약관은 서비스 화면에 게시하거나 기타의 방법으로 이용자에게 공지함으로써 효력이 발생합니다.</p>'],
                            'en' => ['Terms of Service', '<h2>Article 1 (Purpose)</h2><p>These Terms govern the conditions, procedures, and responsibilities between the Company and Users for the use of services provided through this website.</p><h2>Article 2 (Definitions)</h2><p>① "Service" refers to all online services provided through this website.<br>② "User" refers to members and non-members who use the Service under these Terms.</p><h2>Article 3 (Effectiveness)</h2><p>These Terms become effective when posted on the service screen or otherwise notified to Users.</p>'],
                            'ja' => ['利用規約', '<h2>第1条（目的）</h2><p>本規約は、当サイトが提供するサービスの利用条件・手続き、会社と利用者の権利・義務及び責任事項を規定することを目的とします。</p><h2>第2条（定義）</h2><p>①「サービス」とは、当サイトを通じて提供されるすべてのオンラインサービスを意味します。<br>②「利用者」とは、本規約に基づきサービスを利用する会員及び非会員をいいます。</p><h2>第3条（規約の効力）</h2><p>本規約は、サービス画面に掲示またはその他の方法で利用者に通知することにより効力が発生します。</p>'],
                            'de' => ['Nutzungsbedingungen', '<h2>Artikel 1 (Zweck)</h2><p>Diese Bedingungen regeln die Nutzung der über diese Website bereitgestellten Dienste.</p><h2>Artikel 2 (Definitionen)</h2><p>① „Dienst" bezieht sich auf alle über diese Website bereitgestellten Online-Dienste.<br>② „Nutzer" bezieht sich auf Mitglieder und Nicht-Mitglieder.</p>'],
                            'es' => ['Términos de servicio', '<h2>Artículo 1 (Propósito)</h2><p>Estos términos regulan el uso de los servicios proporcionados a través de este sitio web.</p><h2>Artículo 2 (Definiciones)</h2><p>① "Servicio" se refiere a todos los servicios en línea proporcionados.<br>② "Usuario" se refiere a miembros y no miembros.</p>'],
                            'fr' => ["Conditions d'utilisation", '<h2>Article 1 (Objet)</h2><p>Les présentes conditions régissent l\'utilisation des services fournis par ce site web.</p><h2>Article 2 (Définitions)</h2><p>① « Service » désigne tous les services en ligne fournis.<br>② « Utilisateur » désigne les membres et non-membres.</p>'],
                            'id' => ['Syarat Penggunaan', '<h2>Pasal 1 (Tujuan)</h2><p>Ketentuan ini mengatur penggunaan layanan yang disediakan melalui situs web ini.</p><h2>Pasal 2 (Definisi)</h2><p>① "Layanan" mengacu pada semua layanan online yang disediakan.<br>② "Pengguna" mengacu pada anggota dan non-anggota.</p>'],
                            'mn' => ['Үйлчилгээний нөхцөл', '<h2>1-р зүйл (Зорилго)</h2><p>Энэхүү нөхцөлүүд нь энэ вэбсайтаар дамжуулан үзүүлэх үйлчилгээний ашиглалтыг зохицуулна.</p>'],
                            'ru' => ['Условия использования', '<h2>Статья 1 (Цель)</h2><p>Настоящие условия регулируют использование услуг, предоставляемых через данный веб-сайт.</p><h2>Статья 2 (Определения)</h2><p>① «Сервис» — все онлайн-услуги, предоставляемые через сайт.<br>② «Пользователь» — участники и неучастники.</p>'],
                            'tr' => ['Kullanım Şartları', '<h2>Madde 1 (Amaç)</h2><p>Bu şartlar, bu web sitesi aracılığıyla sunulan hizmetlerin kullanımını düzenler.</p><h2>Madde 2 (Tanımlar)</h2><p>① "Hizmet", sunulan tüm çevrimiçi hizmetleri ifade eder.<br>② "Kullanıcı", üyeleri ve üye olmayanları ifade eder.</p>'],
                            'vi' => ['Điều khoản sử dụng', '<h2>Điều 1 (Mục đích)</h2><p>Các điều khoản này quy định việc sử dụng dịch vụ được cung cấp thông qua trang web này.</p><h2>Điều 2 (Định nghĩa)</h2><p>① "Dịch vụ" là tất cả các dịch vụ trực tuyến được cung cấp.<br>② "Người dùng" là thành viên và không phải thành viên.</p>'],
                            'zh_CN' => ['使用条款', '<h2>第一条（目的）</h2><p>本条款规定通过本网站提供的服务的使用条件。</p><h2>第二条（定义）</h2><p>①"服务"是指通过本网站提供的所有在线服务。<br>②"用户"是指会员和非会员。</p>'],
                            'zh_TW' => ['使用條款', '<h2>第一條（目的）</h2><p>本條款規定透過本網站提供的服務的使用條件。</p><h2>第二條（定義）</h2><p>①「服務」是指透過本網站提供的所有線上服務。<br>②「使用者」是指會員和非會員。</p>'],
                        ],
                        'privacy' => [
                            'ko' => ['개인정보처리방침', '<h2>1. 개인정보의 수집 및 이용 목적</h2><p>회사는 다음의 목적을 위하여 개인정보를 처리합니다.</p><ul><li>회원 가입 및 관리</li><li>서비스 제공 및 운영</li><li>고객 문의 대응</li></ul><h2>2. 수집하는 개인정보 항목</h2><p>이메일, 이름, 연락처 등 서비스 이용에 필요한 최소한의 정보를 수집합니다.</p><h2>3. 개인정보의 보유 및 이용기간</h2><p>회원 탈퇴 시 지체 없이 파기합니다. 단, 관련 법령에 따라 보존이 필요한 경우 해당 기간 동안 보관합니다.</p>'],
                            'en' => ['Privacy Policy', '<h2>1. Purpose of Collection and Use</h2><p>We collect personal information for the following purposes:</p><ul><li>Member registration and management</li><li>Service provision and operation</li><li>Customer inquiry response</li></ul><h2>2. Information Collected</h2><p>We collect the minimum information necessary for service use, such as email, name, and contact information.</p><h2>3. Retention Period</h2><p>Personal information is destroyed without delay upon membership withdrawal, unless retention is required by applicable laws.</p>'],
                            'ja' => ['プライバシーポリシー', '<h2>1. 個人情報の収集・利用目的</h2><p>当社は以下の目的で個人情報を処理します。</p><ul><li>会員登録及び管理</li><li>サービスの提供及び運営</li><li>お客様のお問い合わせ対応</li></ul><h2>2. 収集する個人情報の項目</h2><p>メールアドレス、氏名、連絡先など、サービス利用に必要な最小限の情報を収集します。</p><h2>3. 個人情報の保有及び利用期間</h2><p>会員退会時に遅滞なく破棄します。ただし、関連法令により保存が必要な場合は当該期間保管します。</p>'],
                            'de' => ['Datenschutzrichtlinie', '<h2>1. Zweck der Erhebung</h2><p>Wir erheben personenbezogene Daten für folgende Zwecke:</p><ul><li>Mitgliederverwaltung</li><li>Servicebereitstellung</li><li>Kundenanfragen</li></ul><h2>2. Erhobene Daten</h2><p>E-Mail, Name, Kontaktdaten.</p><h2>3. Aufbewahrungsfrist</h2><p>Daten werden bei Austritt unverzüglich gelöscht.</p>'],
                            'es' => ['Política de privacidad', '<h2>1. Propósito de recopilación</h2><p>Recopilamos información personal para:</p><ul><li>Gestión de miembros</li><li>Prestación de servicios</li><li>Consultas de clientes</li></ul><h2>2. Datos recopilados</h2><p>Correo electrónico, nombre, información de contacto.</p>'],
                            'fr' => ['Politique de confidentialité', '<h2>1. Objectif de la collecte</h2><p>Nous collectons des données personnelles pour :</p><ul><li>Gestion des membres</li><li>Fourniture de services</li><li>Réponse aux demandes</li></ul><h2>2. Données collectées</h2><p>E-mail, nom, coordonnées.</p>'],
                            'id' => ['Kebijakan Privasi', '<h2>1. Tujuan Pengumpulan</h2><p>Kami mengumpulkan informasi pribadi untuk:</p><ul><li>Manajemen anggota</li><li>Penyediaan layanan</li><li>Tanggapan pertanyaan</li></ul><h2>2. Data yang Dikumpulkan</h2><p>Email, nama, informasi kontak.</p>'],
                            'mn' => ['Нууцлалын бодлого', '<h2>1. Цуглуулах зорилго</h2><p>Бид хувийн мэдээллийг дараах зорилгоор цуглуулна:</p><ul><li>Гишүүнчлэлийн удирдлага</li><li>Үйлчилгээ үзүүлэх</li></ul>'],
                            'ru' => ['Политика конфиденциальности', '<h2>1. Цели сбора</h2><p>Мы собираем персональные данные для:</p><ul><li>Управление участниками</li><li>Предоставление услуг</li><li>Ответы на запросы</li></ul><h2>2. Собираемые данные</h2><p>Email, имя, контактная информация.</p>'],
                            'tr' => ['Gizlilik Politikası', '<h2>1. Toplama Amacı</h2><p>Kişisel bilgileri şu amaçlarla toplarız:</p><ul><li>Üye yönetimi</li><li>Hizmet sunumu</li><li>Müşteri soruları</li></ul><h2>2. Toplanan Veriler</h2><p>E-posta, ad, iletişim bilgileri.</p>'],
                            'vi' => ['Chính sách quyền riêng tư', '<h2>1. Mục đích thu thập</h2><p>Chúng tôi thu thập thông tin cá nhân để:</p><ul><li>Quản lý thành viên</li><li>Cung cấp dịch vụ</li><li>Phản hồi yêu cầu</li></ul><h2>2. Dữ liệu thu thập</h2><p>Email, tên, thông tin liên hệ.</p>'],
                            'zh_CN' => ['隐私政策', '<h2>1. 收集目的</h2><p>我们为以下目的收集个人信息：</p><ul><li>会员管理</li><li>服务提供</li><li>客户咨询</li></ul><h2>2. 收集的数据</h2><p>电子邮件、姓名、联系方式。</p>'],
                            'zh_TW' => ['隱私政策', '<h2>1. 收集目的</h2><p>我們為以下目的收集個人資訊：</p><ul><li>會員管理</li><li>服務提供</li><li>客戶諮詢</li></ul><h2>2. 收集的資料</h2><p>電子郵件、姓名、聯絡方式。</p>'],
                        ],
                        'refund-policy' => [
                            'ko' => ['취소 및 환불 규정', '<h2>1. 예약 취소 규정</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>취소 시점</th><th>환불율</th></tr></thead><tbody><tr><td>24시간 이전</td><td>100% 전액 환불</td></tr><tr><td>12~24시간 전</td><td>50% 환불</td></tr><tr><td>12시간 이내</td><td>환불 불가</td></tr><tr><td>노쇼(No-Show)</td><td>환불 불가</td></tr></tbody></table><h2>2. 노쇼(No-Show) 정책</h2><p>사전 연락 없이 예약 시간에 나타나지 않은 경우 노쇼로 처리됩니다.</p>'],
                            'en' => ['Cancellation & Refund Policy', '<h2>1. Reservation Cancellation Policy</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Cancellation Time</th><th>Refund Rate</th></tr></thead><tbody><tr><td>24+ hours before</td><td>100% full refund</td></tr><tr><td>12-24 hours before</td><td>50% refund</td></tr><tr><td>Less than 12 hours</td><td>No refund</td></tr><tr><td>No-Show</td><td>No refund</td></tr></tbody></table><h2>2. No-Show Policy</h2><p>Failure to appear at the reserved time without prior notice will be treated as a no-show.</p>'],
                            'ja' => ['キャンセル・返金規定', '<h2>1. 予約キャンセル規定</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>キャンセル時点</th><th>返金率</th></tr></thead><tbody><tr><td>24時間以上前</td><td>100%全額返金</td></tr><tr><td>12〜24時間前</td><td>50%返金</td></tr><tr><td>12時間以内</td><td>返金不可</td></tr><tr><td>無断キャンセル</td><td>返金不可</td></tr></tbody></table><h2>2. 無断キャンセル（No-Show）ポリシー</h2><p>事前連絡なく予約時間に来店されない場合、無断キャンセルとして処理されます。</p>'],
                            'de' => ['Stornierungs- und Rückerstattungsrichtlinie', '<h2>1. Stornierungsrichtlinie</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Stornierungszeitpunkt</th><th>Erstattung</th></tr></thead><tbody><tr><td>24+ Stunden vorher</td><td>100%</td></tr><tr><td>12-24 Stunden</td><td>50%</td></tr><tr><td>Unter 12 Stunden</td><td>Keine</td></tr><tr><td>No-Show</td><td>Keine</td></tr></tbody></table>'],
                            'es' => ['Política de cancelación y reembolso', '<h2>1. Política de cancelación</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Momento</th><th>Reembolso</th></tr></thead><tbody><tr><td>24+ horas antes</td><td>100%</td></tr><tr><td>12-24 horas</td><td>50%</td></tr><tr><td>Menos de 12h</td><td>Sin reembolso</td></tr><tr><td>No-Show</td><td>Sin reembolso</td></tr></tbody></table>'],
                            'fr' => ["Politique d'annulation et de remboursement", '<h2>1. Politique d\'annulation</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Moment</th><th>Remboursement</th></tr></thead><tbody><tr><td>24h+ avant</td><td>100%</td></tr><tr><td>12-24h</td><td>50%</td></tr><tr><td>Moins de 12h</td><td>Non remboursable</td></tr><tr><td>No-Show</td><td>Non remboursable</td></tr></tbody></table>'],
                            'id' => ['Kebijakan Pembatalan dan Pengembalian', '<h2>1. Kebijakan Pembatalan</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Waktu</th><th>Pengembalian</th></tr></thead><tbody><tr><td>24+ jam sebelum</td><td>100%</td></tr><tr><td>12-24 jam</td><td>50%</td></tr><tr><td>Kurang dari 12 jam</td><td>Tidak ada</td></tr><tr><td>No-Show</td><td>Tidak ada</td></tr></tbody></table>'],
                            'mn' => ['Цуцлалт буцаалтын бодлого', '<h2>1. Цуцлах бодлого</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Цаг</th><th>Буцаалт</th></tr></thead><tbody><tr><td>24+ цагийн өмнө</td><td>100%</td></tr><tr><td>12-24 цаг</td><td>50%</td></tr><tr><td>12 цагаас бага</td><td>Буцаалт байхгүй</td></tr></tbody></table>'],
                            'ru' => ['Политика отмены и возврата', '<h2>1. Политика отмены</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Время</th><th>Возврат</th></tr></thead><tbody><tr><td>За 24+ часа</td><td>100%</td></tr><tr><td>12-24 часа</td><td>50%</td></tr><tr><td>Менее 12 часов</td><td>Без возврата</td></tr><tr><td>Неявка</td><td>Без возврата</td></tr></tbody></table>'],
                            'tr' => ['İptal ve İade Politikası', '<h2>1. İptal Politikası</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Zaman</th><th>İade</th></tr></thead><tbody><tr><td>24+ saat önce</td><td>100%</td></tr><tr><td>12-24 saat</td><td>50%</td></tr><tr><td>12 saatten az</td><td>İade yok</td></tr><tr><td>No-Show</td><td>İade yok</td></tr></tbody></table>'],
                            'vi' => ['Chính sách hủy và hoàn tiền', '<h2>1. Chính sách hủy</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Thời điểm</th><th>Hoàn tiền</th></tr></thead><tbody><tr><td>Trước 24+ giờ</td><td>100%</td></tr><tr><td>12-24 giờ</td><td>50%</td></tr><tr><td>Dưới 12 giờ</td><td>Không hoàn</td></tr><tr><td>No-Show</td><td>Không hoàn</td></tr></tbody></table>'],
                            'zh_CN' => ['取消退款政策', '<h2>1. 取消政策</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>时间</th><th>退款</th></tr></thead><tbody><tr><td>24小时以上</td><td>100%</td></tr><tr><td>12-24小时</td><td>50%</td></tr><tr><td>12小时内</td><td>不退款</td></tr><tr><td>未到场</td><td>不退款</td></tr></tbody></table>'],
                            'zh_TW' => ['取消退款政策', '<h2>1. 取消政策</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>時間</th><th>退款</th></tr></thead><tbody><tr><td>24小時以上</td><td>100%</td></tr><tr><td>12-24小時</td><td>50%</td></tr><tr><td>12小時內</td><td>不退款</td></tr><tr><td>未到場</td><td>不退款</td></tr></tbody></table>'],
                        ],
                        'data-policy' => [
                            'ko' => ['데이터 관리 정책', '<h2>데이터 관리 정책</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>자료 종류</th><th>보관 기간</th><th>근거</th></tr></thead><tbody><tr><td>회원 정보</td><td>회원 탈퇴 시 삭제</td><td>개인정보보호법</td></tr><tr><td>예약 정보</td><td>목적 달성 후 삭제</td><td>개인정보보호법</td></tr><tr><td>결제 기록</td><td>5년</td><td>전자상거래법</td></tr></tbody></table>'],
                            'en' => ['Data Management Policy', '<h2>Data Management Policy</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Data Type</th><th>Retention Period</th><th>Legal Basis</th></tr></thead><tbody><tr><td>Member information</td><td>Deleted upon withdrawal</td><td>Privacy Act</td></tr><tr><td>Reservation info</td><td>Deleted after purpose fulfilled</td><td>Privacy Act</td></tr><tr><td>Payment records</td><td>5 years</td><td>E-Commerce Act</td></tr></tbody></table>'],
                            'ja' => ['データ管理ポリシー', '<h2>データ管理ポリシー</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>資料の種類</th><th>保管期間</th><th>根拠</th></tr></thead><tbody><tr><td>会員情報</td><td>退会時に削除</td><td>個人情報保護法</td></tr><tr><td>予約情報</td><td>目的達成後に削除</td><td>個人情報保護法</td></tr><tr><td>決済記録</td><td>5年</td><td>電子商取引法</td></tr></tbody></table>'],
                            'de' => ['Datenverwaltungsrichtlinie', '<h2>Datenverwaltungsrichtlinie</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Datentyp</th><th>Aufbewahrung</th><th>Grundlage</th></tr></thead><tbody><tr><td>Mitgliederdaten</td><td>Bei Austritt gelöscht</td><td>DSGVO</td></tr><tr><td>Reservierungsdaten</td><td>Nach Erfüllung gelöscht</td><td>DSGVO</td></tr><tr><td>Zahlungsaufzeichnungen</td><td>5 Jahre</td><td>HGB</td></tr></tbody></table>'],
                            'es' => ['Política de gestión de datos', '<h2>Política de gestión de datos</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Tipo</th><th>Retención</th><th>Base legal</th></tr></thead><tbody><tr><td>Datos de miembros</td><td>Eliminados al darse de baja</td><td>Ley de privacidad</td></tr><tr><td>Reservas</td><td>Eliminados tras cumplimiento</td><td>Ley de privacidad</td></tr><tr><td>Pagos</td><td>5 años</td><td>Ley de comercio</td></tr></tbody></table>'],
                            'fr' => ['Politique de gestion des données', '<h2>Politique de gestion des données</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Type</th><th>Conservation</th><th>Base légale</th></tr></thead><tbody><tr><td>Données membres</td><td>Supprimées à la désinscription</td><td>RGPD</td></tr><tr><td>Réservations</td><td>Supprimées après usage</td><td>RGPD</td></tr><tr><td>Paiements</td><td>5 ans</td><td>Code de commerce</td></tr></tbody></table>'],
                            'id' => ['Kebijakan Pengelolaan Data', '<h2>Kebijakan Pengelolaan Data</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Jenis Data</th><th>Penyimpanan</th><th>Dasar Hukum</th></tr></thead><tbody><tr><td>Data anggota</td><td>Dihapus saat keluar</td><td>UU Privasi</td></tr><tr><td>Data reservasi</td><td>Dihapus setelah selesai</td><td>UU Privasi</td></tr><tr><td>Catatan pembayaran</td><td>5 tahun</td><td>UU Perdagangan</td></tr></tbody></table>'],
                            'mn' => ['Дата удирдлагын бодлого', '<h2>Дата удирдлагын бодлого</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Төрөл</th><th>Хадгалалт</th><th>Үндэслэл</th></tr></thead><tbody><tr><td>Гишүүний мэдээлэл</td><td>Гарах үед устгана</td><td>Нууцлалын хууль</td></tr></tbody></table>'],
                            'ru' => ['Политика управления данными', '<h2>Политика управления данными</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Тип данных</th><th>Хранение</th><th>Основание</th></tr></thead><tbody><tr><td>Данные участников</td><td>Удаляются при выходе</td><td>Закон о персональных данных</td></tr><tr><td>Данные бронирования</td><td>Удаляются после использования</td><td>Закон о персональных данных</td></tr><tr><td>Платёжные записи</td><td>5 лет</td><td>Закон о торговле</td></tr></tbody></table>'],
                            'tr' => ['Veri Yönetim Politikası', '<h2>Veri Yönetim Politikası</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Veri Türü</th><th>Saklama</th><th>Yasal Dayanak</th></tr></thead><tbody><tr><td>Üye verileri</td><td>Ayrılınca silinir</td><td>KVKK</td></tr><tr><td>Rezervasyon</td><td>Amaç sonrası silinir</td><td>KVKK</td></tr><tr><td>Ödeme kayıtları</td><td>5 yıl</td><td>Ticaret Kanunu</td></tr></tbody></table>'],
                            'vi' => ['Chính sách quản lý dữ liệu', '<h2>Chính sách quản lý dữ liệu</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>Loại dữ liệu</th><th>Lưu trữ</th><th>Cơ sở pháp lý</th></tr></thead><tbody><tr><td>Thông tin thành viên</td><td>Xóa khi rút</td><td>Luật bảo vệ dữ liệu</td></tr><tr><td>Thông tin đặt chỗ</td><td>Xóa sau khi hoàn thành</td><td>Luật bảo vệ dữ liệu</td></tr><tr><td>Hồ sơ thanh toán</td><td>5 năm</td><td>Luật thương mại</td></tr></tbody></table>'],
                            'zh_CN' => ['数据管理政策', '<h2>数据管理政策</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>数据类型</th><th>保留期</th><th>法律依据</th></tr></thead><tbody><tr><td>会员信息</td><td>退出时删除</td><td>隐私法</td></tr><tr><td>预约信息</td><td>完成后删除</td><td>隐私法</td></tr><tr><td>支付记录</td><td>5年</td><td>电子商务法</td></tr></tbody></table>'],
                            'zh_TW' => ['資料管理政策', '<h2>資料管理政策</h2><table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><thead><tr><th>資料類型</th><th>保留期</th><th>法律依據</th></tr></thead><tbody><tr><td>會員資訊</td><td>退出時刪除</td><td>隱私法</td></tr><tr><td>預約資訊</td><td>完成後刪除</td><td>隱私法</td></tr><tr><td>付款記錄</td><td>5年</td><td>電子商務法</td></tr></tbody></table>'],
                        ],
                        'tokushoho' => [
                            'ko' => ['특정상거래법에 기반한 표기', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">판매업자</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">대표 책임자</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">소재지</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">전화번호</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">이메일</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">영업시간</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">판매가격</th><td>각 상품 페이지에 표시</td></tr><tr><th style="background:#f5f5f5">결제방법</th><td>신용카드, 은행이체</td></tr></tbody></table>'],
                            'en' => ['Notation Based on the Specified Commercial Transactions Act', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Seller</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Representative</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Address</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Phone</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Email</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Business Hours</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Prices</th><td>As displayed on each product page</td></tr><tr><th style="background:#f5f5f5">Payment Methods</th><td>Credit card, Bank transfer</td></tr></tbody></table>'],
                            'ja' => ['特定商取引法に基づく表記', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">販売業者</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">代表責任者</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">所在地</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">電話番号</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">メール</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">営業時間</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">販売価格</th><td>各商品ページに表示</td></tr><tr><th style="background:#f5f5f5">お支払い方法</th><td>クレジットカード、銀行振込</td></tr></tbody></table>'],
                            'de' => ['Angaben gemäß dem Gesetz über spezifizierte kommerzielle Transaktionen', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Verkäufer</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Vertreter</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Adresse</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Telefon</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">E-Mail</th><td>&nbsp;</td></tr></tbody></table>'],
                            'es' => ['Notación basada en la Ley de Transacciones Comerciales Específicas', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Vendedor</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Representante</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Dirección</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Teléfono</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Email</th><td>&nbsp;</td></tr></tbody></table>'],
                            'fr' => ['Mention basée sur la loi sur les transactions commerciales spécifiées', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Vendeur</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Représentant</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Adresse</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Téléphone</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Email</th><td>&nbsp;</td></tr></tbody></table>'],
                            'id' => ['Notasi Berdasarkan Undang-Undang Transaksi Komersial Tertentu', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Penjual</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Perwakilan</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Alamat</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Telepon</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Email</th><td>&nbsp;</td></tr></tbody></table>'],
                            'mn' => ['Тодорхой худалдааны гүйлгээний хуулийн дагуу тэмдэглэл', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Худалдагч</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Төлөөлөгч</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Хаяг</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Утас</th><td>&nbsp;</td></tr></tbody></table>'],
                            'ru' => ['Уведомление на основании Закона об определённых коммерческих сделках', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Продавец</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Представитель</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Адрес</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Телефон</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Email</th><td>&nbsp;</td></tr></tbody></table>'],
                            'tr' => ['Belirli Ticari İşlemler Yasasına Dayalı Bildirim', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Satıcı</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Temsilci</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Adres</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Telefon</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">E-posta</th><td>&nbsp;</td></tr></tbody></table>'],
                            'vi' => ['Ghi chú theo Luật Giao dịch Thương mại Cụ thể', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Người bán</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Đại diện</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Địa chỉ</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Điện thoại</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Email</th><td>&nbsp;</td></tr></tbody></table>'],
                            'zh_CN' => ['基于特定商交易法的标注', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">卖方</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">代表人</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">地址</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">电话</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">邮箱</th><td>&nbsp;</td></tr></tbody></table>'],
                            'zh_TW' => ['基於特定商交易法的標註', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">賣方</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">代表人</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">地址</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">電話</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">郵箱</th><td>&nbsp;</td></tr></tbody></table>'],
                        ],
                        'funds-settlement' => [
                            'ko' => ['자금결제법에 의한 표시', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">발행자</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">적립금 명칭</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">이용 가능 범위</th><td>본 사이트 내 서비스</td></tr><tr><th style="background:#f5f5f5">유효기간</th><td>발행일로부터 1년</td></tr><tr><th style="background:#f5f5f5">문의처</th><td>&nbsp;</td></tr></tbody></table>'],
                            'en' => ['Disclosure Under the Payment Services Act', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Issuer</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Point Name</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Usable Scope</th><td>Services within this site</td></tr><tr><th style="background:#f5f5f5">Validity</th><td>1 year from issuance</td></tr><tr><th style="background:#f5f5f5">Contact</th><td>&nbsp;</td></tr></tbody></table>'],
                            'ja' => ['資金決済法に基づく表示', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">発行者</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">ポイント名称</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">利用可能範囲</th><td>本サイト内のサービス</td></tr><tr><th style="background:#f5f5f5">有効期限</th><td>発行日から1年</td></tr><tr><th style="background:#f5f5f5">お問い合わせ先</th><td>&nbsp;</td></tr></tbody></table>'],
                            'de' => ['Offenlegung gemäß dem Zahlungsdienstegesetz', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Herausgeber</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Punktename</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Gültigkeitsbereich</th><td>Dienste dieser Website</td></tr><tr><th style="background:#f5f5f5">Gültigkeit</th><td>1 Jahr ab Ausstellung</td></tr></tbody></table>'],
                            'es' => ['Divulgación según la Ley de Servicios de Pago', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Emisor</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Nombre del punto</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Alcance</th><td>Servicios de este sitio</td></tr><tr><th style="background:#f5f5f5">Validez</th><td>1 año desde la emisión</td></tr></tbody></table>'],
                            'fr' => ['Divulgation en vertu de la loi sur les services de paiement', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Émetteur</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Nom du point</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Portée</th><td>Services de ce site</td></tr><tr><th style="background:#f5f5f5">Validité</th><td>1 an à compter de l\'émission</td></tr></tbody></table>'],
                            'id' => ['Pengungkapan Berdasarkan Undang-Undang Layanan Pembayaran', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Penerbit</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Nama Poin</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Cakupan</th><td>Layanan situs ini</td></tr><tr><th style="background:#f5f5f5">Berlaku</th><td>1 tahun sejak penerbitan</td></tr></tbody></table>'],
                            'mn' => ['Төлбөрийн үйлчилгээний хуулийн дагуу мэдээлэл', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Гаргагч</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Оноо нэр</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Хамрах хүрээ</th><td>Сайтын үйлчилгээ</td></tr><tr><th style="background:#f5f5f5">Хүчинтэй хугацаа</th><td>Олгосноос хойш 1 жил</td></tr></tbody></table>'],
                            'ru' => ['Раскрытие информации по Закону о платёжных услугах', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Эмитент</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Название баллов</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Область применения</th><td>Услуги данного сайта</td></tr><tr><th style="background:#f5f5f5">Срок действия</th><td>1 год с момента выпуска</td></tr></tbody></table>'],
                            'tr' => ['Ödeme Hizmetleri Kanunu Kapsamında Bilgilendirme', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Yayıncı</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Puan Adı</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Kapsam</th><td>Bu sitenin hizmetleri</td></tr><tr><th style="background:#f5f5f5">Geçerlilik</th><td>Düzenleme tarihinden itibaren 1 yıl</td></tr></tbody></table>'],
                            'vi' => ['Công bố theo Luật Dịch vụ Thanh toán', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">Nhà phát hành</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Tên điểm</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">Phạm vi</th><td>Dịch vụ của trang web này</td></tr><tr><th style="background:#f5f5f5">Hiệu lực</th><td>1 năm kể từ ngày phát hành</td></tr></tbody></table>'],
                            'zh_CN' => ['根据资金结算法的公示', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">发行方</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">积分名称</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">使用范围</th><td>本站服务</td></tr><tr><th style="background:#f5f5f5">有效期</th><td>发行日起1年</td></tr></tbody></table>'],
                            'zh_TW' => ['根據資金結算法的公示', '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%"><tbody><tr><th style="width:30%;background:#f5f5f5">發行方</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">積分名稱</th><td>&nbsp;</td></tr><tr><th style="background:#f5f5f5">使用範圍</th><td>本站服務</td></tr><tr><th style="background:#f5f5f5">有效期</th><td>發行日起1年</td></tr></tbody></table>'],
                        ],
                    ];
                    ]; 기존 인라인 샘플 제거됨 */

                    // 메뉴 항목 13개국어 번역 (menu_item.{id}.title)
                    // ID: 1=Home, 2=Notice, 3=Free Board, 4=Q&A, 5=FAQ, 6=Terms, 7=Privacy, 8=Refund, 9=Data, 10=Tokushoho, 11=Funds
                    $_menuTranslations = [
                        1 => ['ko'=>'홈','en'=>'Home','ja'=>'ホーム','de'=>'Startseite','es'=>'Inicio','fr'=>'Accueil','id'=>'Beranda','mn'=>'Нүүр','ru'=>'Главная','tr'=>'Ana Sayfa','vi'=>'Trang chủ','zh_CN'=>'首页','zh_TW'=>'首頁'],
                        2 => ['ko'=>'공지사항','en'=>'Notice','ja'=>'お知らせ','de'=>'Ankündigungen','es'=>'Avisos','fr'=>'Annonces','id'=>'Pengumuman','mn'=>'Мэдэгдэл','ru'=>'Объявления','tr'=>'Duyurular','vi'=>'Thông báo','zh_CN'=>'公告','zh_TW'=>'公告'],
                        3 => ['ko'=>'자유게시판','en'=>'Free Board','ja'=>'自由掲示板','de'=>'Freies Forum','es'=>'Foro Libre','fr'=>'Forum Libre','id'=>'Forum Bebas','mn'=>'Чөлөөт самбар','ru'=>'Свободный форум','tr'=>'Serbest Forum','vi'=>'Diễn đàn tự do','zh_CN'=>'自由论坛','zh_TW'=>'自由論壇'],
                        4 => ['ko'=>'질문과 답변','en'=>'Q&A','ja'=>'質問と回答','de'=>'Fragen & Antworten','es'=>'Preguntas','fr'=>'Questions','id'=>'Tanya Jawab','mn'=>'Асуулт хариулт','ru'=>'Вопросы','tr'=>'Soru Cevap','vi'=>'Hỏi đáp','zh_CN'=>'问答','zh_TW'=>'問答'],
                        5 => ['ko'=>'자주 묻는 질문','en'=>'FAQ','ja'=>'よくある質問','de'=>'FAQ','es'=>'FAQ','fr'=>'FAQ','id'=>'FAQ','mn'=>'Түгээмэл асуулт','ru'=>'FAQ','tr'=>'SSS','vi'=>'FAQ','zh_CN'=>'常见问题','zh_TW'=>'常見問題'],
                        6 => ['ko'=>'이용약관','en'=>'Terms of Use','ja'=>'利用規約','de'=>'Nutzungsbedingungen','es'=>'Términos','fr'=>'Conditions','id'=>'Ketentuan','mn'=>'Нөхцөл','ru'=>'Условия','tr'=>'Kullanım Şartları','vi'=>'Điều khoản','zh_CN'=>'使用条款','zh_TW'=>'使用條款'],
                        7 => ['ko'=>'개인정보처리방침','en'=>'Privacy Policy','ja'=>'個人情報処理方針','de'=>'Datenschutz','es'=>'Privacidad','fr'=>'Confidentialité','id'=>'Privasi','mn'=>'Нууцлал','ru'=>'Конфиденциальность','tr'=>'Gizlilik','vi'=>'Quyền riêng tư','zh_CN'=>'隐私政策','zh_TW'=>'隱私政策'],
                        8 => ['ko'=>'취소 환불 규정','en'=>'Cancellation Policy','ja'=>'キャンセル規定','de'=>'Stornierung','es'=>'Cancelación','fr'=>'Annulation','id'=>'Pembatalan','mn'=>'Цуцлалт','ru'=>'Отмена','tr'=>'İptal','vi'=>'Hủy hoàn tiền','zh_CN'=>'取消退款','zh_TW'=>'取消退款'],
                        9 => ['ko'=>'데이터 관리 정책','en'=>'Data Policy','ja'=>'データ管理ポリシー','de'=>'Datenrichtlinie','es'=>'Política de datos','fr'=>'Politique des données','id'=>'Kebijakan Data','mn'=>'Дата бодлого','ru'=>'Политика данных','tr'=>'Veri Politikası','vi'=>'Chính sách dữ liệu','zh_CN'=>'数据管理政策','zh_TW'=>'資料管理政策'],
                        10 => ['ko'=>'특정상거래법 표기','en'=>'Commercial Transactions Act','ja'=>'特定商取引法に基づく表記','de'=>'Handelsgesetz','es'=>'Ley de Comercio','fr'=>'Loi commerciale','id'=>'UU Perdagangan','mn'=>'Худалдааны хууль','ru'=>'Закон о торговле','tr'=>'Ticaret Kanunu','vi'=>'Luật Thương mại','zh_CN'=>'特定商业交易法','zh_TW'=>'特定商業交易法'],
                        11 => ['ko'=>'자금결제법 표시','en'=>'Funds Settlement Act','ja'=>'資金決済法に基づく表示','de'=>'Zahlungsgesetz','es'=>'Ley de Pagos','fr'=>'Loi sur les paiements','id'=>'UU Pembayaran','mn'=>'Төлбөрийн хууль','ru'=>'Закон о расчётах','tr'=>'Ödeme Kanunu','vi'=>'Luật Thanh toán','zh_CN'=>'资金结算法','zh_TW'=>'資金結算法'],
                    ];
                    $trStmt = $pdo->prepare("INSERT IGNORE INTO {$pfx}translations (lang_key, locale, content) VALUES (?, ?, ?)");
                    foreach ($_menuTranslations as $_mid => $_tr) {
                        foreach ($_tr as $_loc => $_content) {
                            $trStmt->execute(["menu_item.{$_mid}.title", $_loc, $_content]);
                        }
                    }

                    // 기본 게시판 4개 (공지, Q&A, FAQ, 자유게시판)
                    $boardStmt = $pdo->prepare("INSERT IGNORE INTO {$pfx}boards (id, slug, title, category, perm_write, perm_read, list_columns, skin, per_page, is_active) VALUES (?, ?, ?, ?, ?, 'all', ?, ?, ?, 1)");
                    $listCols = json_encode(['no', 'title', 'nick_name', 'created_at', 'view_count']);
                    $boardStmt->execute([1, 'notice', 'Notice', 'notice', 'admin', $listCols, 'default', 20]);
                    $boardStmt->execute([2, 'qna', 'Q&A', 'qna', 'member', $listCols, 'default', 20]);
                    $boardStmt->execute([3, 'faq', 'FAQ', 'faq', 'admin', $listCols, 'faq', 10]);
                    $boardStmt->execute([4, 'free', 'Free Board', 'board', 'member', $listCols, 'default', 20]);

                    // 게시판 시드 데이터 (카테고리 + 게시글 + 다국어 번역)
                    $_boardSeedFile = BASE_PATH . '/database/seeds/board_data.php';
                    if (file_exists($_boardSeedFile)) {
                        $_boardSeed = include $_boardSeedFile;

                        // 게시판 slug → id 매핑
                        $_boardMap = ['notice'=>1, 'qna'=>2, 'faq'=>3, 'free'=>4];

                        // 카테고리 생성
                        $_catMap = []; // slug → id
                        if (!empty($_boardSeed['categories'])) {
                            $_catStmt = $pdo->prepare("INSERT IGNORE INTO {$pfx}board_categories (board_id, name, slug, sort_order, is_active) VALUES (?, ?, ?, ?, 1)");
                            $_catTrStmt = $pdo->prepare("INSERT IGNORE INTO {$pfx}translations (lang_key, locale, content) VALUES (?, ?, ?)");
                            foreach ($_boardSeed['categories'] as $_cat) {
                                $_boardId = $_boardMap[$_cat['board_slug']] ?? null;
                                if (!$_boardId) continue;
                                $_catStmt->execute([$_boardId, $_cat['name'], $_cat['slug'], $_cat['sort']]);
                                $_catId = (int)$pdo->lastInsertId();
                                if ($_catId > 0) {
                                    $_catMap[$_cat['slug']] = $_catId;
                                    foreach ($_cat['translations'] ?? [] as $_tLocale => $_tContent) {
                                        $_catTrStmt->execute(["board_category.{$_catId}.name", $_tLocale, $_tContent]);
                                    }
                                }
                            }
                        }

                        // 게시글 생성
                        if (!empty($_boardSeed['posts'])) {
                            $_postStmt = $pdo->prepare("INSERT IGNORE INTO {$pfx}board_posts (board_id, title, content, nick_name, view_count, is_notice, category_id, original_locale, source_locale, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                            $_postTrStmt = $pdo->prepare("INSERT IGNORE INTO {$pfx}translations (lang_key, locale, content) VALUES (?, ?, ?)");
                            foreach ($_boardSeed['posts'] as $_post) {
                                $_boardId = $_boardMap[$_post['board_slug']] ?? null;
                                if (!$_boardId) continue;
                                $_catId = isset($_post['cat_slug']) ? ($_catMap[$_post['cat_slug']] ?? null) : null;
                                $_postStmt->execute([$_boardId, $_post['title'], $_post['content'], $_post['nick_name'], rand(5,30), $_post['is_notice'], $_catId, $_post['original_locale'], $_post['original_locale']]);
                                $_postId = (int)$pdo->lastInsertId();
                                if ($_postId > 0) {
                                    foreach ($_post['title_translations'] ?? [] as $_tLocale => $_tContent) {
                                        $_postTrStmt->execute(["board_post.{$_postId}.title", $_tLocale, $_tContent]);
                                    }
                                    foreach ($_post['content_translations'] ?? [] as $_tLocale => $_tContent) {
                                        $_postTrStmt->execute(["board_post.{$_postId}.content", $_tLocale, $_tContent]);
                                    }
                                }
                            }
                        }
                    }

                    // 기본 회원 등급
                    $gradeStmt = $pdo->prepare("INSERT IGNORE INTO {$pfx}member_grades (id, name, slug, level, discount_rate, point_rate, color, sort_order, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $gradeStmt->execute([1, 'Normal', 'normal', 0, 0, 0, '#5c8dff', 0, 1]);
                    $gradeStmt->execute([2, 'Silver', 'silver', 1, 2, 0.5, '#bdbdbd', 1, 0]);
                    $gradeStmt->execute([3, 'Gold', 'gold', 2, 3, 1, '#ffd500', 2, 0]);
                    $gradeStmt->execute([4, 'VIP', 'vip', 3, 5, 2, '#ff528e', 3, 0]);

                    // 회원 등급 다국어 번역 (13개국어)
                    $_gradeTranslations = [
                        1 => ['ko'=>'일반','en'=>'Normal','ja'=>'一般','de'=>'Standard','es'=>'Normal','fr'=>'Normal','id'=>'Normal','mn'=>'Энгийн','ru'=>'Обычный','tr'=>'Normal','vi'=>'Thường','zh_CN'=>'普通','zh_TW'=>'普通'],
                        2 => ['ko'=>'실버','en'=>'Silver','ja'=>'シルバー','de'=>'Silber','es'=>'Plata','fr'=>'Argent','id'=>'Silver','mn'=>'Мөнгө','ru'=>'Серебро','tr'=>'Gümüş','vi'=>'Bạc','zh_CN'=>'白银','zh_TW'=>'白銀'],
                        3 => ['ko'=>'골드','en'=>'Gold','ja'=>'ゴールド','de'=>'Gold','es'=>'Oro','fr'=>'Or','id'=>'Emas','mn'=>'Алт','ru'=>'Золото','tr'=>'Altın','vi'=>'Vàng','zh_CN'=>'黄金','zh_TW'=>'黃金'],
                        4 => ['ko'=>'VIP','en'=>'VIP','ja'=>'VIP','de'=>'VIP','es'=>'VIP','fr'=>'VIP','id'=>'VIP','mn'=>'VIP','ru'=>'VIP','tr'=>'VIP','vi'=>'VIP','zh_CN'=>'VIP','zh_TW'=>'VIP'],
                    ];
                    $_gradeTrStmt = $pdo->prepare("INSERT IGNORE INTO {$pfx}translations (lang_key, locale, content) VALUES (?, ?, ?)");
                    foreach ($_gradeTranslations as $_gid => $_gtr) {
                        foreach ($_gtr as $_gloc => $_gname) {
                            $_gradeTrStmt->execute(["member_grade.{$_gid}.name", $_gloc, $_gname]);
                        }
                    }

                    // ─── 홈 페이지 기본 위젯 배치 ───
                    // 위젯 ID 조회 (WidgetLoader가 자동 등록했을 수 있으므로)
                    $_widgetIds = [];
                    $_wStmt = $pdo->query("SELECT id, slug FROM {$pfx}widgets WHERE slug IN ('hero-cta2','stats','features')");
                    while ($_w = $_wStmt->fetch(PDO::FETCH_ASSOC)) $_widgetIds[$_w['slug']] = (int)$_w['id'];

                    // 위젯이 DB에 없으면 수동 등록
                    if (empty($_widgetIds['hero-cta2'])) {
                        $pdo->exec("INSERT IGNORE INTO {$pfx}widgets (slug, type, is_active) VALUES ('hero-cta2','hero-cta2',1)");
                        $_widgetIds['hero-cta2'] = (int)$pdo->lastInsertId();
                    }
                    if (empty($_widgetIds['stats'])) {
                        $pdo->exec("INSERT IGNORE INTO {$pfx}widgets (slug, type, is_active) VALUES ('stats','stats',1)");
                        $_widgetIds['stats'] = (int)$pdo->lastInsertId();
                    }
                    if (empty($_widgetIds['features'])) {
                        $pdo->exec("INSERT IGNORE INTO {$pfx}widgets (slug, type, is_active) VALUES ('features','features',1)");
                        $_widgetIds['features'] = (int)$pdo->lastInsertId();
                    }

                    if (!empty($_widgetIds['hero-cta2'])) {
                        $heroConfig = json_encode([
                            'tagline_top' => ['ko'=>'플러그인과 위젯, 스킨, 테마로 자유롭게 확장하는','en'=>'Freely extensible with plugins and themes','ja'=>'プラグインとテーマで自由に拡張できる'],
                            'highlight_word' => ['ko'=>'VosCMS','en'=>'VosCMS','ja'=>'VosCMS'],
                            'tagline_bottom' => ['ko'=>'오픈소스 CMS','en'=>'Open Source CMS','ja'=>'オープンソースCMS'],
                            'description' => ['ko'=>"VosCMS는 누구나 홈페이지를 만들고,\n플러그인과 위젯, 스킨, 테마를 사용해서\n원하는 대로 꾸밀 수 있는 오픈소스 CMS입니다.",'en'=>'VosCMS is an open-source CMS that lets anyone build a website and customize it with plugins and themes.','ja'=>'VosCMSは誰でもホームページを作成し、プラグインとテーマで自由にカスタマイズできるオープンソースCMSです。'],
                            'typing_words' => ['ko'=>"커뮤니티 사이트\n비즈니스 홈페이지\n온라인 쇼핑몰\n포트폴리오 사이트\n예약 플랫폼",'en'=>"Community Site\nBusiness Website\nOnline Store\nPortfolio\nBooking Platform",'ja'=>"コミュニティサイト\nビジネスサイト\nオンラインストア\nポートフォリオ\n予約プラットフォーム"],
                            'typing_speed' => 'normal',
                            'primary_btn_text' => ['ko'=>'관리자 화면','en'=>'Admin Panel','ja'=>'管理画面'],
                            'primary_btn_url' => '/' . $adminPath,
                            'primary_btn_sub' => '',
                            'secondary_btn_text' => '','secondary_btn_url' => '','secondary_btn_sub' => '',
                            'requirements' => 'GPLv2 | PHP 8.1+ | MySQL or MariaDB',
                            'highlight_color' => '#4f46e5','primary_btn_color' => '#4f46e5','bg_style' => 'light',
                        ], JSON_UNESCAPED_UNICODE);

                        $statsConfig = json_encode([
                            'items' => [
                                ['number'=>'22','label'=>['ko'=>'위젯','en'=>'Widgets','ja'=>'ウィジェット']],
                                ['number'=>'13','label'=>['ko'=>'지원 언어','en'=>'Languages','ja'=>'対応言語']],
                                ['number'=>'100%','label'=>['ko'=>'오픈소스','en'=>'Open Source','ja'=>'オープンソース']],
                                ['number'=>'24/7','label'=>['ko'=>'커뮤니티 지원','en'=>'Community Support','ja'=>'コミュニティサポート']],
                            ]
                        ], JSON_UNESCAPED_UNICODE);

                        $featuresConfig = json_encode([
                            'title' => ['ko'=>'왜 VosCMS 인가요?','en'=>'Why VosCMS?','ja'=>'なぜVosCMS？','de'=>'Warum VosCMS?','es'=>'¿Por qué VosCMS?','fr'=>'Pourquoi VosCMS ?','id'=>'Mengapa VosCMS?','mn'=>'Яагаад VosCMS?','ru'=>'Почему VosCMS?','tr'=>'Neden VosCMS?','vi'=>'Tại sao chọn VosCMS?','zh_CN'=>'为什么选择 VosCMS？','zh_TW'=>'為什麼選擇 VosCMS？'],
                            'subtitle' => ['ko'=>"VosCMS는 오픈소스 CMS 및 프레임워크입니다.\n누구나 홈페이지를 시작할 수 있는 안정적인 기초가 되어 주고,\n플러그인과 위젯, 스킨, 테마를 사용해서 원하는 대로 꾸밀 수 있습니다.",'en'=>"VosCMS is an open-source CMS and framework.\nIt provides a stable foundation for anyone to start a website,\nand lets you customize it with plugins, widgets, skins, and themes.",'ja'=>"VosCMSはオープンソースのCMS及びフレームワークです。\n誰でもホームページを始められる安定した基盤を提供し、\nプラグインやウィジェット、スキン、テーマで自由にカスタマイズできます。"],
                            'columns' => '3',
                            'feature_items' => [
                                ['icon'=>'mobile','color'=>'blue','title'=>['ko'=>'모바일 최적화','en'=>'Mobile Optimized','ja'=>'モバイル最適化','de'=>'Mobil optimiert','es'=>'Optimización móvil','fr'=>'Optimisé mobile','id'=>'Optimasi Mobile','mn'=>'Мобайл оновчлол','ru'=>'Мобильная оптимизация','tr'=>'Mobil Optimizasyon','vi'=>'Tối ưu di động','zh_CN'=>'移动端优化','zh_TW'=>'行動裝置優化'],'description'=>['ko'=>'언제 어디서나 편리하게 사용 할 수 있게 PWA 지원으로 안드로이드, 애플 모두 사용 가능한 앱이 제공 됩니다.','en'=>'PWA support enables app-like experience on both Android and iOS, accessible anytime, anywhere.','ja'=>'PWA対応でAndroid・iOS両方で使えるアプリが提供されます。いつでもどこでも便利に利用可能。']],
                                ['icon'=>'cube','color'=>'green','title'=>['ko'=>'일정한 성능','en'=>'Consistent Performance','ja'=>'一定のパフォーマンス','de'=>'Konstante Leistung','es'=>'Rendimiento constante','fr'=>'Performance constante','id'=>'Performa Konsisten','mn'=>'Тогтвортой гүйцэтгэл','ru'=>'Стабильная производительность','tr'=>'Tutarlı Performans','vi'=>'Hiệu suất ổn định','zh_CN'=>'稳定性能','zh_TW'=>'穩定效能'],'description'=>['ko'=>'홈페이지가 성장해도 걱정 없어요! 동시접속자 1만 명이 넘는 대형 커뮤니티들도 VOS CMS를 적극 도입하고 있어요.','en'=>'No worries as your site grows! Large communities with 10,000+ concurrent users actively use VosCMS.','ja'=>'サイトが成長しても心配なし！同時接続1万人超の大型コミュニティもVosCMSを採用。']],
                                ['icon'=>'shield','color'=>'purple','title'=>['ko'=>'완벽한 보안 솔루션','en'=>'Complete Security','ja'=>'完全なセキュリティ','de'=>'Vollständige Sicherheit','es'=>'Seguridad completa','fr'=>'Sécurité complète','id'=>'Keamanan Lengkap','mn'=>'Бүрэн аюулгүй байдал','ru'=>'Полная безопасность','tr'=>'Tam Güvenlik','vi'=>'Bảo mật toàn diện','zh_CN'=>'完善的安全方案','zh_TW'=>'完善的安全方案'],'description'=>['ko'=>'회원의 중요 정보는 모두 암호화하여 저장 됩니다. 사이트 해킹을 당해도 회원 정보는 안전하게 보호 됩니다.','en'=>'All sensitive member data is encrypted. Even if the site is hacked, member information stays protected.','ja'=>'会員の重要情報はすべて暗号化して保存。サイトがハッキングされても会員情報は安全に保護されます。']],
                                ['icon'=>'heart','color'=>'red','title'=>['ko'=>'다양한 기능과 디자인','en'=>'Rich Features & Design','ja'=>'豊富な機能とデザイン','de'=>'Vielfältige Funktionen','es'=>'Funciones variadas','fr'=>'Fonctionnalités riches','id'=>'Fitur Beragam','mn'=>'Олон функц, дизайн','ru'=>'Богатый функционал','tr'=>'Zengin Özellikler','vi'=>'Tính năng phong phú','zh_CN'=>'丰富的功能和设计','zh_TW'=>'豐富的功能和設計'],'description'=>['ko'=>'계속 개발되어 공급되는 다양한 기능과 자유로운 디자인이 가능하게 많은 위젯과 스킨이 풍부하게 제공 됩니다.','en'=>'A wide variety of widgets and skins are provided for diverse functionality and free design customization.','ja'=>'多様な機能と自由なデザインを可能にする豊富なウィジェットとスキンが提供されます。']],
                                ['icon'=>'check-circle','color'=>'orange','title'=>['ko'=>'꾸준한 업데이트','en'=>'Steady Updates','ja'=>'着実なアップデート','de'=>'Regelmäßige Updates','es'=>'Actualizaciones constantes','fr'=>'Mises à jour régulières','id'=>'Pembaruan Rutin','mn'=>'Тогтмол шинэчлэлт','ru'=>'Регулярные обновления','tr'=>'Düzenli Güncellemeler','vi'=>'Cập nhật đều đặn','zh_CN'=>'持续更新','zh_TW'=>'持續更新'],'description'=>['ko'=>'개발 후 지금까지 꾸준한 업데이트와 보안패치를 만들어 왔어요. 오래 운영할 홈페이지를 위한 최적의 선택!','en'=>'Consistent updates and security patches since launch. The best choice for long-running websites!','ja'=>'開発以来、着実なアップデートとセキュリティパッチを提供。長期運営サイトに最適！']],
                                ['icon'=>'chat','color'=>'indigo','title'=>['ko'=>'같이 배우는 커뮤니티','en'=>'Learning Community','ja'=>'共に学ぶコミュニティ','de'=>'Lernende Gemeinschaft','es'=>'Comunidad de aprendizaje','fr'=>"Communauté d'apprentissage",'id'=>'Komunitas Belajar','mn'=>'Хамтдаа суралцах','ru'=>'Обучающее сообщество','tr'=>'Öğrenen Topluluk','vi'=>'Cộng đồng học hỏi','zh_CN'=>'共同学习的社区','zh_TW'=>'共同學習的社群'],'description'=>['ko'=>'질문으로 문제를 해결하는 과정을 통해, 개발자도 사용자도 한 걸음씩 성장합니다.','en'=>'Through the process of solving problems with questions, both developers and users grow step by step.','ja'=>'質問で問題を解決する過程を通じて、開発者もユーザーも一歩ずつ成長します。']],
                            ],
                        ], JSON_UNESCAPED_UNICODE);

                        // grid-section 위젯으로 3단 레이아웃 (Notice, Q&A, FAQ)
                        if (empty($_widgetIds['grid-section'])) {
                            $pdo->exec("INSERT IGNORE INTO {$pfx}widgets (slug, type, is_active) VALUES ('grid-section','grid-section',1)");
                            $_widgetIds['grid-section'] = (int)$pdo->lastInsertId();
                        }

                        $gridConfig = json_encode([
                            'layout' => '3-equal',
                            'gap' => '6',
                            'bg_color' => 'transparent',
                            'cells' => [
                                [
                                    'type' => 'board-list',
                                    'board_slug' => 'notice',
                                    'title' => ['ko'=>'공지사항','en'=>'Notice','ja'=>'お知らせ','de'=>'Ankündigungen','es'=>'Avisos','fr'=>'Annonces','id'=>'Pengumuman','mn'=>'Мэдэгдэл','ru'=>'Объявления','tr'=>'Duyurular','vi'=>'Thông báo','zh_CN'=>'公告','zh_TW'=>'公告'],
                                    'count' => 5,
                                    'show_more' => 1,
                                ],
                                [
                                    'type' => 'board-list',
                                    'board_slug' => 'qna',
                                    'title' => ['ko'=>'Q&A','en'=>'Q&A','ja'=>'Q&A','de'=>'Q&A','es'=>'Q&A','fr'=>'Q&A','id'=>'Q&A','mn'=>'Асуулт хариулт','ru'=>'Вопросы','tr'=>'Soru Cevap','vi'=>'Hỏi đáp','zh_CN'=>'问答','zh_TW'=>'問答'],
                                    'count' => 5,
                                    'show_more' => 1,
                                ],
                                [
                                    'type' => 'board-list',
                                    'board_slug' => 'faq',
                                    'title' => ['ko'=>'FAQ','en'=>'FAQ','ja'=>'FAQ','de'=>'FAQ','es'=>'FAQ','fr'=>'FAQ','id'=>'FAQ','mn'=>'Түгээмэл асуулт','ru'=>'FAQ','tr'=>'SSS','vi'=>'FAQ','zh_CN'=>'常见问题','zh_TW'=>'常見問題'],
                                    'count' => 5,
                                    'show_more' => 1,
                                ],
                            ],
                        ], JSON_UNESCAPED_UNICODE);

                        // 혹시 기존 항목이 있으면 제거 후 새로 삽입 (중복 방지)
                        $pdo->exec("DELETE FROM {$pfx}page_widgets WHERE page_slug = 'home'");
                        $pwStmt = $pdo->prepare("INSERT INTO {$pfx}page_widgets (page_slug, widget_id, sort_order, config) VALUES ('home', ?, ?, ?)");
                        $pwStmt->execute([$_widgetIds['hero-cta2'], 0, $heroConfig]);
                        $pwStmt->execute([$_widgetIds['stats'], 1, $statsConfig]);
                        $pwStmt->execute([$_widgetIds['features'], 2, $featuresConfig]);
                        if (!empty($_widgetIds['grid-section'])) {
                            $pwStmt->execute([$_widgetIds['grid-section'], 3, $gridConfig]);
                        }
                    }

                    // 모든 INSERT 작업 완료 → 트랜잭션 커밋 (네트워크 호출 전 disk flush)
                    $pdo->commit();

                    // ─── 라이선스 서버에서 키 발급 ───
                    $licenseDomain = strtolower(preg_replace('#^https?://#', '', rtrim($siteUrl, '/')));
                    $licenseDomain = preg_replace('#^www\.#', '', $licenseDomain);
                    $licenseServer = 'https://vos.21ces.com/api'; // 향후 https://voscms.com/api
                    $licenseKey = '';
                    $licenseRegistered = false;

                    try {
                        $ch = curl_init($licenseServer . '/license/register');
                        curl_setopt_array($ch, [
                            CURLOPT_POST => true,
                            CURLOPT_POSTFIELDS => json_encode([
                                'domain' => $licenseDomain,
                                'version' => '2.1.0',
                                'php_version' => PHP_VERSION,
                                'server_ip' => $_SERVER['SERVER_ADDR'] ?? '',
                            ]),
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_TIMEOUT => 5,
                            CURLOPT_CONNECTTIMEOUT => 3,
                            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
                            CURLOPT_SSL_VERIFYPEER => true,
                        ]);
                        $licResponse = curl_exec($ch);
                        $licHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);

                        if ($licHttpCode === 200 && $licResponse) {
                            $licData = json_decode($licResponse, true);
                            if (!empty($licData['success']) && !empty($licData['key'])) {
                                $licenseKey = $licData['key'];
                                $licenseRegistered = true;
                            }
                        }
                    } catch (\Throwable $e) {
                        // 서버 연결 실패 — 키 없이 설치 진행
                    }

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
SESSION_LIFETIME=10080
SESSION_SECURE_COOKIE=true

JWT_SECRET={$jwtSecret}
JWT_TTL=60
JWT_REFRESH_TTL=20160

CACHE_DRIVER=file
CACHE_PREFIX=vos_

LICENSE_KEY={$licenseKey}
LICENSE_DOMAIN={$licenseDomain}
LICENSE_REGISTERED_AT=" . date('c') . "
LICENSE_SERVER={$licenseServer}
";
                    file_put_contents(BASE_PATH . '/.env', $envContent);

                    // 필수 스토리지 디렉토리 생성
                    $storageDirs = [
                        'storage/cache',
                        'storage/tmp',
                        'storage/app',
                        'storage/logs',
                        'storage/uploads',
                        'storage/uploads/images',
                        'storage/uploads/files',
                    ];
                    foreach ($storageDirs as $dir) {
                        $path = BASE_PATH . '/' . $dir;
                        if (!is_dir($path)) {
                            @mkdir($path, 0775, true);
                        }
                        @chmod($path, 0775);
                    }

                    // 번들 플러그인 자동 활성화 (vos-autoinstall)
                    try {
                        require_once BASE_PATH . '/rzxlib/Core/Plugin/PluginManager.php';
                        $pm = \RzxLib\Core\Plugin\PluginManager::init($pdo, BASE_PATH . '/plugins', $pfx);
                        $pm->install('vos-autoinstall');
                    } catch (\Throwable $e) {
                        // 플러그인 설치 실패해도 전체 설치는 계속
                        error_log('vos-autoinstall auto-install failed: ' . $e->getMessage());
                    }

                    // 설치 완료 플래그
                    file_put_contents(BASE_PATH . '/storage/.installed', date('Y-m-d H:i:s'));

                    $_SESSION['install_complete'] = true;
                    $_SESSION['install_admin_url'] = $siteUrl . '/' . $adminPath;
                    $_SESSION['install_license_key'] = $licenseKey;
                    $_SESSION['install_license_registered'] = $licenseRegistered;
                    $step = '5';
                } catch (PDOException $e) {
                    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
                    $errors[] = __t('save_fail') . ': ' . $e->getMessage();
                }
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($installLocale ?? 'en') ?>">
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

    <?php if ($step !== '0'): // Step 0에서는 프로그레스 바 숨김 ?>
    <!-- 프로그레스 -->
    <div class="flex items-center justify-center gap-2 mb-8">
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <div class="flex items-center">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                <?= $i < $step ? 'bg-blue-600 text-white' : ($i == $step ? 'bg-blue-600 text-white ring-4 ring-blue-200' : 'bg-zinc-300 text-zinc-500') ?>">
                <?= $i < $step ? '&#10003;' : $i ?>
            </div>
            <?php if ($i < 5): ?><div class="w-8 h-0.5 <?= $i < $step ? 'bg-blue-600' : 'bg-zinc-300' ?>"></div><?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-lg p-8">

    <?php if ($errors): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
        <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($step === '2'): // DB 설정 ?>
    <h2 class="text-xl font-bold text-zinc-800 mb-6">2. <?= __t('step2') ?></h2>
    <form method="POST">
        <input type="hidden" name="step" value="2">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1"><?= __t('host') ?></label>
                    <input type="text" name="db_host" value="127.0.0.1" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1"><?= __t('port') ?></label>
                    <input type="text" name="db_port" value="3306" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1"><?= __t('db_name') ?></label>
                <input type="text" name="db_name" placeholder="voscms" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-zinc-400 mt-1"><?= __t('db_name_hint') ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1"><?= __t('db_user') ?></label>
                <input type="text" name="db_user" placeholder="root" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1"><?= __t('db_pass') ?></label>
                <input type="password" name="db_pass" class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1"><?= __t('db_prefix') ?></label>
                <input type="text" name="db_prefix" value="rzx_" class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
        <button type="submit" class="w-full mt-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition"><?= __t('connect_next') ?> &rarr;</button>
    </form>

    <?php elseif ($step === '3'): // 테이블 생성 ?>
    <h2 class="text-xl font-bold text-zinc-800 mb-6">3. <?= __t('step3') ?></h2>
    <div class="text-center py-6">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <p class="text-lg font-bold text-zinc-800"><?= __t('db_success') ?></p>
        <p class="text-sm text-zinc-500 mt-2"><?= sprintf(__t('migration_info'), count(glob(BASE_PATH . '/database/migrations/core/*.sql')) + count(glob(BASE_PATH . '/database/migrations/migrations/*.sql'))) ?></p>
    </div>
    <form method="POST">
        <input type="hidden" name="step" value="3">
        <input type="hidden" name="_db" value="<?= base64_encode(json_encode($_SESSION['install_db'] ?? [])) ?>">
        <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition"><?= __t('create_tables') ?> &rarr;</button>
    </form>

    <?php elseif ($step === '4'): // 관리자 + 사이트 설정 ?>
    <h2 class="text-xl font-bold text-zinc-800 mb-6">4. <?= __t('step4') ?></h2>
    <form method="POST">
        <input type="hidden" name="step" value="4">
        <input type="hidden" name="_db" value="<?= base64_encode(json_encode($_SESSION['install_db'] ?? [])) ?>">
        <div class="space-y-4">
            <div class="pb-4 border-b border-zinc-200">
                <h3 class="text-sm font-bold text-zinc-600 mb-3"><?= __t('site_info') ?></h3>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1"><?= __t('site_name') ?></label>
                    <input type="text" name="site_name" value="VosCMS" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mt-3">
                    <label class="block text-sm font-medium text-zinc-700 mb-1"><?= __t('site_url') ?></label>
                    <input type="url" name="site_url" placeholder="https://example.com" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mt-3">
                    <label class="block text-sm font-medium text-zinc-700 mb-1"><?= __t('admin_path') ?></label>
                    <input type="text" id="adminPathInput" name="admin_path" value="admin"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           oninput="updateAdminPathPreview()">
                    <p class="mt-1.5 text-xs text-amber-600 dark:text-amber-400"><?= __t('admin_path_hint') ?></p>
                    <div id="adminPathPreview" class="mt-1.5 text-xs text-zinc-400 font-mono hidden">
                        → <span id="adminPathPreviewUrl"></span>
                    </div>
                    <details class="mt-2 group">
                        <summary class="cursor-pointer text-xs font-medium text-blue-600 hover:text-blue-800 select-none list-none flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            <?= __t('admin_path_why_title') ?>
                        </summary>
                        <div class="mt-2 p-3 rounded-lg bg-blue-50 border border-blue-100 text-xs text-zinc-700 leading-relaxed">
                            <?= __t('admin_path_why_body') ?>
                        </div>
                    </details>
                </div>
                <div class="grid grid-cols-2 gap-3 mt-3">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 mb-1"><?= __t('language') ?></label>
                        <select name="locale" class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <?php foreach ($_installLangs as $_lCode => $_lName): ?>
                            <option value="<?= $_lCode ?>" <?= $_lCode === $installLocale ? 'selected' : '' ?>><?= $_lName ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 mb-1"><?= __t('timezone_label') ?></label>
                        <select name="timezone" class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="Asia/Seoul">Seoul (KST)</option>
                            <option value="Asia/Tokyo">Tokyo (JST)</option>
                            <option value="Asia/Shanghai">Shanghai (CST)</option>
                            <option value="UTC">UTC</option>
                            <option value="America/New_York">New York (EST)</option>
                            <option value="Europe/London">London (GMT)</option>
                            <option value="Europe/Berlin">Berlin (CET)</option>
                            <option value="Europe/Moscow">Moscow (MSK)</option>
                            <option value="Europe/Istanbul">Istanbul (TRT)</option>
                            <option value="Asia/Jakarta">Jakarta (WIB)</option>
                            <option value="Asia/Ulaanbaatar">Ulaanbaatar (ULAT)</option>
                            <option value="Asia/Ho_Chi_Minh">Ho Chi Minh (ICT)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-bold text-zinc-600 mb-3"><?= __t('admin_account') ?></h3>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1"><?= __t('admin_name') ?></label>
                    <input type="text" name="admin_name" value="Administrator" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mt-3">
                    <label class="block text-sm font-medium text-zinc-700 mb-1"><?= __t('email') ?></label>
                    <input type="email" name="admin_email" placeholder="admin@example.com" required class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mt-3">
                    <label class="block text-sm font-medium text-zinc-700 mb-1"><?= __t('password') ?></label>
                    <input type="password" name="admin_pass" placeholder="<?= __t('pw_hint') ?>" required minlength="8" class="w-full px-3 py-2 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>
        <button type="submit" class="w-full mt-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition"><?= __t('finish_install') ?> &rarr;</button>
    </form>
    <script>
    function updateAdminPathPreview() {
        var siteUrlEl = document.querySelector('input[name="site_url"]');
        var pathEl    = document.getElementById('adminPathInput');
        var preview   = document.getElementById('adminPathPreview');
        var previewUrl = document.getElementById('adminPathPreviewUrl');
        var base = (siteUrlEl ? siteUrlEl.value.replace(/\/$/, '') : '') || 'https://your-domain.com';
        var path = pathEl.value.trim() || 'admin';
        previewUrl.textContent = base + '/' + path;
        preview.classList.remove('hidden');
    }
    document.addEventListener('DOMContentLoaded', function() {
        var siteUrlEl = document.querySelector('input[name="site_url"]');
        if (siteUrlEl) siteUrlEl.addEventListener('input', updateAdminPathPreview);
        updateAdminPathPreview();
    });
    </script>

    <?php elseif ($step === '5'): // 완료 ?>
    <div class="text-center py-6">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-zinc-800"><?= __t('complete_title') ?></h2>
        <p class="text-zinc-500 mt-2"><?= __t('complete_desc') ?></p>

        <!-- 라이선스 정보 -->
        <div class="mt-6 p-4 bg-blue-50 rounded-lg text-left text-sm border border-blue-200">
            <p class="text-zinc-700 font-semibold mb-2"><?= __t('license_info') ?></p>
            <div class="flex items-center justify-between">
                <p class="text-zinc-600"><?= __t('license_key') ?>:</p>
                <code class="text-blue-700 font-bold text-base tracking-wider"><?= htmlspecialchars($_SESSION['install_license_key'] ?? '-') ?></code>
            </div>
            <?php if (!empty($_SESSION['install_license_registered'])): ?>
            <p class="text-green-600 text-xs mt-2 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                <?= __t('license_ok') ?>
            </p>
            <?php else: ?>
            <p class="text-amber-600 text-xs mt-2 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <?= __t('license_fail_msg') ?>
            </p>
            <?php endif; ?>
            <p class="text-zinc-400 text-xs mt-2"><?= __t('license_env') ?></p>
        </div>

        <div class="mt-4 p-4 bg-zinc-50 rounded-lg text-left text-sm">
            <p class="text-zinc-600"><strong><?= __t('admin_page') ?>:</strong></p>
            <p class="text-blue-600 font-mono"><?= htmlspecialchars($_SESSION['install_admin_url'] ?? '/admin') ?></p>
        </div>

        <a href="<?= htmlspecialchars($_SESSION['install_admin_url'] ?? '/admin') ?>"
           class="inline-block mt-6 px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition">
            <?= __t('go_admin') ?> &rarr;
        </a>
    </div>
    <?php endif; ?>

    </div>

    <p class="text-center text-xs text-zinc-400 mt-6">VosCMS &mdash; Value Of Style CMS</p>
    <p class="text-center text-xs text-zinc-300 mt-1">Powered by <a href="https://thevos.com" target="_blank" class="hover:text-zinc-500 transition">THEVOS</a></p>
</div>
</body>
</html>
