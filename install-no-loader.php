<?php
/**
 * VosCMS — ionCube Loader 미설치 안내 페이지
 *
 * install.php (ionCube 인코딩) 실행 시 Loader 가 없으면 이 파일이 호출됨.
 * 방문자에게 친화적 다국어 UI 로 설치 옵션 3가지 안내:
 *   1) ionCube Loader 직접 설치
 *   2) 호스팅사에 활성화 요청
 *   3) VosCMS 호스팅 서비스 이용 (CTA)
 */

// 브라우저 언어 감지 (Accept-Language 헤더 기반)
$_locale = 'ko';
$_supported = ['ko','en','ja','de','es','fr','id','mn','ru','tr','vi','zh_CN','zh_TW'];
if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    foreach (preg_split('/,\s*/', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $lang) {
        $code = strtolower(strtok($lang, ';'));
        // zh-CN → zh_CN
        $code = str_replace('-', '_', $code);
        $code = preg_replace('/^([a-z]+)_([a-z]+)$/', '$1_' . strtoupper('$2'), $code);
        // 정확 매치 우선
        if (in_array($code, $_supported, true)) { $_locale = $code; break; }
        // 언어 prefix 매치 (en-US → en)
        $prefix = strtok($code, '_');
        if (in_array($prefix, $_supported, true)) { $_locale = $prefix; break; }
    }
}

$T = [
    'title' => [
        'ko'=>'ionCube Loader가 필요합니다','en'=>'ionCube Loader Required',
        'ja'=>'ionCube Loader が必要です','de'=>'ionCube Loader erforderlich',
        'es'=>'Se requiere ionCube Loader','fr'=>'ionCube Loader requis',
        'id'=>'ionCube Loader Diperlukan','mn'=>'ionCube Loader шаардлагатай',
        'ru'=>'Требуется ionCube Loader','tr'=>'ionCube Loader Gerekli',
        'vi'=>'Cần ionCube Loader','zh_CN'=>'需要 ionCube Loader','zh_TW'=>'需要 ionCube Loader',
    ],
    'lead' => [
        'ko'=>'VosCMS 코어 보안 모듈은 ionCube 로 보호되어 있어, PHP 에 ionCube Loader 확장이 설치되어 있어야 동작합니다.',
        'en'=>'VosCMS core security modules are protected by ionCube. The ionCube Loader extension must be installed in your PHP environment.',
        'ja'=>'VosCMS のコアセキュリティモジュールは ionCube で保護されており、PHP に ionCube Loader 拡張が必要です。',
    ],
    'option1_title' => [
        'ko'=>'옵션 1 · 직접 Loader 설치 (DIY)','en'=>'Option 1 · Install Loader yourself (DIY)','ja'=>'オプション1 · 自分でLoaderをインストール',
    ],
    'option1_desc' => [
        'ko'=>'서버 root 권한이 있는 경우. 약 5분 소요.',
        'en'=>'If you have root access. Takes about 5 minutes.',
        'ja'=>'サーバーのroot権限がある場合。約5分。',
    ],
    'option1_steps' => [
        'ko'=>['ionCube 공식 사이트에서 PHP %s · %s · %s 용 Loader 다운로드','php.ini 에 zend_extension 라인 추가','웹서버 (php-fpm / apache) 재시작','이 페이지 새로고침'],
        'en'=>['Download Loader for PHP %s · %s · %s from ionCube official site','Add zend_extension line to php.ini','Restart web server (php-fpm / apache)','Refresh this page'],
        'ja'=>['ionCube公式サイトから PHP %s · %s · %s 用 Loader をダウンロード','php.ini に zend_extension 行を追加','Webサーバ(php-fpm / apache)を再起動','このページを再読み込み'],
    ],
    'option2_title' => [
        'ko'=>'옵션 2 · 호스팅사에 요청','en'=>'Option 2 · Ask your hosting provider','ja'=>'オプション2 · ホスティング事業者に依頼',
    ],
    'option2_desc' => [
        'ko'=>'공유 호스팅·관리형 호스팅이라면 root 권한 없이도 호스팅사에 ionCube 활성화를 요청하세요. 대부분 무료로 즉시 처리됩니다.',
        'en'=>'On shared/managed hosting, ask your provider to enable ionCube. Most providers do this for free.',
        'ja'=>'共有・マネージドホスティングの場合、事業者にionCube有効化を依頼してください。多くは無料で即対応します。',
    ],
    'option3_title' => [
        'ko'=>'옵션 3 · VosCMS 호스팅 (권장)','en'=>'Option 3 · VosCMS Hosting (Recommended)','ja'=>'オプション3 · VosCMS ホスティング(おすすめ)',
    ],
    'option3_desc' => [
        'ko'=>'복잡한 환경 설정 없이 바로 사용. ionCube · PHP 8.3 · MariaDB · 백업 · SSL 모두 사전 구성된 VosCMS 전용 호스팅.',
        'en'=>'No setup hassle. ionCube · PHP 8.3 · MariaDB · backup · SSL all pre-configured VosCMS-optimized hosting.',
        'ja'=>'環境構築の手間なし。ionCube · PHP 8.3 · MariaDB · バックアップ · SSL を最初から構成済みのVosCMS専用ホスティング。',
    ],
    'option3_features' => [
        'ko'=>['ionCube Loader 사전 설치','PHP 8.3 + MariaDB 최신 안정 버전','일일 자동 백업','Let\'s Encrypt SSL 자동','VosCMS 업데이트 우선 적용','한국어/일본어 지원'],
        'en'=>['ionCube Loader pre-installed','Latest stable PHP 8.3 + MariaDB','Daily automatic backups','Auto Let\'s Encrypt SSL','Priority VosCMS updates','Korean/Japanese support'],
        'ja'=>['ionCube Loader 事前インストール済み','最新安定版 PHP 8.3 + MariaDB','日次自動バックアップ','Let\'s Encrypt SSL 自動','VosCMS アップデート優先適用','韓国語/日本語サポート'],
    ],
    'cta_hosting' => ['ko'=>'호스팅 살펴보기','en'=>'View Hosting Plans','ja'=>'ホスティングを見る'],
    'cta_loader'  => ['ko'=>'Loader 다운로드','en'=>'Download Loader','ja'=>'Loaderをダウンロード'],
    'cta_guide'   => ['ko'=>'설치 가이드 자세히','en'=>'Detailed Install Guide','ja'=>'詳細な設置ガイド'],
    'env_label'   => ['ko'=>'현재 환경','en'=>'Your Environment','ja'=>'現在の環境'],
];
$t = function (string $key) use ($T, $_locale) {
    $v = $T[$key] ?? null;
    if (!$v) return $key;
    return $v[$_locale] ?? $v['en'] ?? $v['ko'] ?? '';
};
$arr = function (string $key) use ($T, $_locale) {
    $v = $T[$key] ?? [];
    return $v[$_locale] ?? $v['en'] ?? $v['ko'] ?? [];
};

$phpVer  = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
$phpOs   = PHP_OS_FAMILY;          // Linux / Windows / Darwin / BSD
$phpArch = PHP_INT_SIZE === 8 ? 'x86_64' : 'x86';

// Step 안내 문구의 %s 치환
$step1 = $arr('option1_steps')[0] ?? '';
$step1 = sprintf($step1, $phpVer, $phpOs, $phpArch);
$rest  = array_slice($arr('option1_steps'), 1);

http_response_code(503);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_locale) ?>">
<head>
<meta charset="utf-8">
<title>VosCMS — <?= htmlspecialchars($t('title')) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script>
<style>body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif}</style>
</head>
<body class="bg-gradient-to-b from-zinc-50 to-zinc-100 min-h-screen">
<div class="max-w-3xl mx-auto px-4 py-12">

    <!-- 헤더 -->
    <div class="text-center mb-10">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-amber-100 mb-4">
            <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
        </div>
        <h1 class="text-3xl font-extrabold text-zinc-900 mb-3"><?= htmlspecialchars($t('title')) ?></h1>
        <p class="text-zinc-600 max-w-xl mx-auto leading-relaxed"><?= htmlspecialchars($t('lead')) ?></p>
        <p class="mt-3 text-xs text-zinc-400"><?= htmlspecialchars($t('env_label')) ?>: PHP <?= $phpVer ?> · <?= htmlspecialchars($phpOs) ?> · <?= htmlspecialchars($phpArch) ?></p>
    </div>

    <!-- 옵션 1 · DIY -->
    <div class="bg-white rounded-2xl shadow-sm border border-zinc-200 p-6 mb-4">
        <div class="flex items-center gap-2 mb-2">
            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-zinc-100 text-zinc-600">DIY</span>
            <h2 class="text-lg font-bold text-zinc-900"><?= htmlspecialchars($t('option1_title')) ?></h2>
        </div>
        <p class="text-sm text-zinc-600 mb-3"><?= htmlspecialchars($t('option1_desc')) ?></p>
        <ol class="list-decimal pl-5 space-y-1 text-sm text-zinc-700">
            <li><?= htmlspecialchars($step1) ?></li>
            <?php foreach ($rest as $s): ?>
                <li><?= htmlspecialchars($s) ?></li>
            <?php endforeach; ?>
        </ol>
        <a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noopener"
           class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline">
            <?= htmlspecialchars($t('cta_loader')) ?>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </a>
    </div>

    <!-- 옵션 2 · 호스팅사 요청 -->
    <div class="bg-white rounded-2xl shadow-sm border border-zinc-200 p-6 mb-4">
        <div class="flex items-center gap-2 mb-2">
            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-zinc-100 text-zinc-600"><?= $_locale === 'ko' ? '요청' : ($_locale === 'ja' ? '依頼' : 'Ask') ?></span>
            <h2 class="text-lg font-bold text-zinc-900"><?= htmlspecialchars($t('option2_title')) ?></h2>
        </div>
        <p class="text-sm text-zinc-600"><?= htmlspecialchars($t('option2_desc')) ?></p>
    </div>

    <!-- 옵션 3 · VosCMS 호스팅 (메인 CTA) -->
    <div class="bg-gradient-to-br from-blue-600 to-purple-700 rounded-2xl shadow-lg p-6 text-white mb-4">
        <div class="flex items-center gap-2 mb-2">
            <span class="text-xs font-bold px-2 py-0.5 rounded bg-white/20 text-white">★ <?= $_locale === 'ko' ? '권장' : ($_locale === 'ja' ? 'おすすめ' : 'Recommended') ?></span>
            <h2 class="text-xl font-bold"><?= htmlspecialchars($t('option3_title')) ?></h2>
        </div>
        <p class="text-sm text-white/90 mb-4 leading-relaxed"><?= htmlspecialchars($t('option3_desc')) ?></p>
        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm mb-5">
            <?php foreach ($arr('option3_features') as $f): ?>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    <span><?= htmlspecialchars($f) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="flex flex-wrap gap-2">
            <a href="https://voscms.com/service/order" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-bold bg-white text-blue-700 rounded-lg hover:bg-zinc-50 transition shadow">
                <?= htmlspecialchars($t('cta_hosting')) ?>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
            <a href="https://voscms.com/install-guide" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-medium border border-white/30 text-white rounded-lg hover:bg-white/10 transition">
                <?= htmlspecialchars($t('cta_guide')) ?>
            </a>
        </div>
    </div>

    <p class="text-center text-xs text-zinc-400 mt-8">VosCMS · <?= date('Y') ?></p>
</div>
</body>
</html>
<?php exit;
