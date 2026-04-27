<?php
/**
 * VosCMS 다운로드 히어로 위젯
 * version.json에서 실시간 버전 정보 로드, 다국어 지원
 */
$_cfg = $widgetConfig ?? [];
$_locale = function_exists('current_locale') ? current_locale() : 'ko';

// 위젯 config에서 다국어 텍스트 가져오기
$_t = function($key, $fallback = '') use ($_cfg, $_locale) {
    return $_cfg[$key . '_' . $_locale] ?? $_cfg[$key . '_en'] ?? $_cfg[$key . '_ko'] ?? $_cfg[$key] ?? $fallback;
};

// 위젯 내장 다국어 (하드코딩 텍스트용)
$_i18n = [
    'ko' => [
        'features' => '주요 기능',
        'requirements' => '시스템 요구사항',
        'required' => '필수',
        'php_ext' => 'PHP 확장',
        'ioncube' => 'ionCube Loader 필요 (코어 암호화)',
        'version_history' => '버전 히스토리',
        'latest' => '최신',
        'download' => '다운로드',
        'changelog' => '변경 이력',
        'release' => '릴리즈',
        'webserver' => '웹서버',
        'disk' => '디스크',
        'recommended' => '권장',
        'or_higher' => '이상',
        'feat_i18n' => '13개국어 다국어',
        'feat_i18n_desc' => '한국어, 일본어, 영어, 중국어 등 13개 언어 기본 지원',
        'feat_widget' => '위젯 빌더',
        'feat_widget_desc' => '드래그 앤 드롭으로 페이지 구성, 30+ 위젯',
        'feat_plugin' => '플러그인 시스템',
        'feat_plugin_desc' => '마켓플레이스에서 기능 확장, 업종별 DX 모듈',
        'feat_member' => '회원 관리',
        'feat_member_desc' => '등급 시스템, 암호화 저장, 소셜 로그인',
        'feat_payment' => '서비스 신청/결제',
        'feat_payment_desc' => 'PAY.JP/Stripe 결제, 구독 관리, 자동 갱신',
        'feat_dark' => '다크모드',
        'feat_dark_desc' => '시스템 설정 연동 + 수동 토글',
    ],
    'en' => [
        'features' => 'Key Features',
        'requirements' => 'System Requirements',
        'required' => 'Required',
        'php_ext' => 'PHP Extensions',
        'ioncube' => 'ionCube Loader required (core encryption)',
        'version_history' => 'Version History',
        'latest' => 'Latest',
        'download' => 'Download',
        'changelog' => 'Changelog',
        'release' => 'Release',
        'webserver' => 'Web Server',
        'disk' => 'Disk',
        'recommended' => 'recommended',
        'or_higher' => 'or higher',
        'feat_i18n' => '13 Languages',
        'feat_i18n_desc' => 'Korean, Japanese, English, Chinese and 13 languages built-in',
        'feat_widget' => 'Widget Builder',
        'feat_widget_desc' => 'Drag & drop page builder with 30+ widgets',
        'feat_plugin' => 'Plugin System',
        'feat_plugin_desc' => 'Extend via marketplace, industry-specific DX modules',
        'feat_member' => 'Member Management',
        'feat_member_desc' => 'Grade system, encrypted storage, social login',
        'feat_payment' => 'Service & Payment',
        'feat_payment_desc' => 'PAY.JP/Stripe payment, subscription management, auto-renewal',
        'feat_dark' => 'Dark Mode',
        'feat_dark_desc' => 'System preference sync + manual toggle',
    ],
    'ja' => [
        'features' => '主な機能',
        'requirements' => 'システム要件',
        'required' => '必須',
        'php_ext' => 'PHP拡張',
        'ioncube' => 'ionCube Loader 必要（コア暗号化）',
        'version_history' => 'バージョン履歴',
        'latest' => '最新',
        'download' => 'ダウンロード',
        'changelog' => '変更履歴',
        'release' => 'リリース',
        'webserver' => 'Webサーバー',
        'disk' => 'ディスク',
        'recommended' => '推奨',
        'or_higher' => '以上',
        'feat_i18n' => '13カ国語対応',
        'feat_i18n_desc' => '韓国語、日本語、英語、中国語など13言語を標準サポート',
        'feat_widget' => 'ウィジェットビルダー',
        'feat_widget_desc' => 'ドラッグ＆ドロップでページ構成、30以上のウィジェット',
        'feat_plugin' => 'プラグインシステム',
        'feat_plugin_desc' => 'マーケットプレイスで機能拡張、業種別DXモジュール',
        'feat_member' => '会員管理',
        'feat_member_desc' => 'グレードシステム、暗号化保存、ソーシャルログイン',
        'feat_payment' => 'サービス申請・決済',
        'feat_payment_desc' => 'PAY.JP/Stripe決済、サブスクリプション管理、自動更新',
        'feat_dark' => 'ダークモード',
        'feat_dark_desc' => 'システム設定連動 + 手動トグル',
    ],
    'zh_CN' => [
        'features' => '主要功能', 'requirements' => '系统要求', 'required' => '必需',
        'php_ext' => 'PHP扩展', 'ioncube' => '需要 ionCube Loader（核心加密）',
        'version_history' => '版本历史', 'latest' => '最新', 'download' => '下载',
        'changelog' => '更新日志', 'release' => '发布', 'webserver' => 'Web服务器',
        'disk' => '磁盘', 'recommended' => '推荐', 'or_higher' => '以上',
        'feat_i18n' => '13种语言', 'feat_i18n_desc' => '韩语、日语、英语、中文等13种语言内置支持',
        'feat_widget' => '组件构建器', 'feat_widget_desc' => '拖放式页面构建，30+组件',
        'feat_plugin' => '插件系统', 'feat_plugin_desc' => '通过市场扩展功能',
        'feat_member' => '会员管理', 'feat_member_desc' => '等级系统、加密存储、社交登录',
        'feat_payment' => '服务申请/支付', 'feat_payment_desc' => 'PAY.JP/Stripe支付、订阅管理',
        'feat_dark' => '暗色模式', 'feat_dark_desc' => '系统设置联动 + 手动切换',
    ],
];

// 현재 로케일 → 영어 → 한국어 폴백
$_l = function($key) use ($_i18n, $_locale) {
    return $_i18n[$_locale][$key] ?? $_i18n['en'][$key] ?? $_i18n['ko'][$key] ?? $key;
};

// dist zip 목록에서 버전 정보 결정 (version.json이 아닌 실제 빌드 기준)
$_distDir = '/var/www/voscms-dist/';
$_versions = [];
foreach (glob($_distDir . 'voscms-*.zip') as $f) {
    if (preg_match('/voscms-([\d.]+)\.zip$/', basename($f), $m)) {
        $_versions[] = [
            'version' => $m[1],
            'size' => round(filesize($f) / 1024 / 1024, 1),
            'date' => date('Y-m-d', filemtime($f)),
        ];
    }
}
usort($_versions, fn($a, $b) => version_compare($b['version'], $a['version']));

// 히어로는 최신 zip 기준 버전 사용 (version.json과 괴리 방지)
$_latestZip = $_versions[0] ?? null;
$_version = $_latestZip ? $_latestZip['version'] : '1.0.0';
$_fileSize = $_latestZip ? $_latestZip['size'] : null;

// version.json에서 코드네임·채널 등 보조 정보만 참조
$_vFile = BASE_PATH . '/version.json';
$_vData = file_exists($_vFile) ? json_decode(file_get_contents($_vFile), true) : [];
$_codename = $_vData['codename'] ?? '';
$_releaseDate = $_latestZip ? $_latestZip['date'] : ($_vData['release_date'] ?? '');
$_channel = $_vData['channel'] ?? 'stable';

$_downloadUrl = $_cfg['download_url'] ?? '/download/voscms-latest.zip';
$_changelogUrl = $_cfg['changelog_url'] ?? '';
$_githubUrl = $_cfg['github_url'] ?? '';
$_showReqs = ($_cfg['show_requirements'] ?? true) !== false;
$_showFeatures = ($_cfg['show_features'] ?? true) !== false;
$_showHistory = ($_cfg['show_history'] ?? true) !== false;

$_bgStyle = $_cfg['bg_style'] ?? 'gradient';
$_bgClass = match($_bgStyle) {
    'dark' => 'bg-zinc-900',
    'light' => 'bg-gray-50 dark:bg-zinc-900',
    default => 'bg-gradient-to-br from-blue-600 via-blue-700 to-purple-800',
};
$_textLight = $_bgStyle !== 'light';
?>

<!-- 히어로 섹션 -->
<section class="<?= $_bgClass ?> py-20">
    <div class="max-w-5xl mx-auto px-4 text-center">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium mb-6 <?= $_textLight ? 'bg-white/10 text-white/80' : 'bg-blue-100 text-blue-700' ?>">
            <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
            v<?= htmlspecialchars($_version) ?> <?= $_codename ? '"' . htmlspecialchars($_codename) . '"' : '' ?>
            <?php if ($_channel !== 'stable'): ?>
            <span class="px-1.5 py-0.5 rounded text-[10px] <?= $_textLight ? 'bg-amber-500/20 text-amber-300' : 'bg-amber-100 text-amber-700' ?>"><?= htmlspecialchars($_channel) ?></span>
            <?php endif; ?>
        </div>

        <h1 class="text-4xl md:text-5xl font-extrabold mb-4 <?= $_textLight ? 'text-white' : 'text-zinc-900 dark:text-white' ?>">
            <?= htmlspecialchars($_t('title', 'VosCMS')) ?>
        </h1>
        <p class="text-lg md:text-xl mb-8 max-w-2xl mx-auto <?= $_textLight ? 'text-white/70' : 'text-zinc-500 dark:text-zinc-400' ?>">
            <?= htmlspecialchars($_t('subtitle', '')) ?>
        </p>

        <div class="flex flex-wrap items-center justify-center gap-3 mb-6">
            <a href="<?= htmlspecialchars($_downloadUrl) ?>" class="inline-flex items-center gap-2 px-8 py-3.5 bg-white text-blue-700 font-bold rounded-xl hover:bg-gray-50 shadow-lg hover:shadow-xl transition text-base">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <?= $_l('download') ?> v<?= htmlspecialchars($_version) ?>
            </a>
            <?php if ($_changelogUrl): ?>
            <a href="<?= htmlspecialchars($_changelogUrl) ?>" class="inline-flex items-center gap-2 px-6 py-3.5 font-medium rounded-xl transition text-base <?= $_textLight ? 'text-white/80 border border-white/20 hover:bg-white/10' : 'text-zinc-600 border border-zinc-300 hover:bg-zinc-100' ?>">
                <?= $_l('changelog') ?>
            </a>
            <?php endif; ?>
            <?php if ($_githubUrl): ?>
            <a href="<?= htmlspecialchars($_githubUrl) ?>" target="_blank" class="inline-flex items-center gap-2 px-6 py-3.5 font-medium rounded-xl transition text-base <?= $_textLight ? 'text-white/80 border border-white/20 hover:bg-white/10' : 'text-zinc-600 border border-zinc-300 hover:bg-zinc-100' ?>">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                GitHub
            </a>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-center gap-4 text-xs <?= $_textLight ? 'text-white/50' : 'text-zinc-400' ?>">
            <?php if ($_releaseDate): ?>
            <span><?= $_l('release') ?>: <?= htmlspecialchars($_releaseDate) ?></span>
            <?php endif; ?>
            <?php if ($_fileSize): ?>
            <span><?= $_fileSize ?>MB</span>
            <?php endif; ?>
            <span>PHP 8.0+</span>
            <span>MySQL 5.7+</span>
        </div>
    </div>
</section>

<!-- 주요 기능 -->
<?php if ($_showFeatures): ?>
<section class="py-16 bg-white dark:bg-zinc-800">
    <div class="max-w-5xl mx-auto px-4">
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white text-center mb-10"><?= $_l('features') ?></h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $features = [
                ['icon' => 'M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129', 'key' => 'i18n'],
                ['icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z', 'key' => 'widget'],
                ['icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6', 'key' => 'plugin'],
                ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'key' => 'member'],
                ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'key' => 'payment'],
                ['icon' => 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z', 'key' => 'dark'],
            ];
            foreach ($features as $f): ?>
            <div class="p-5 rounded-xl border border-gray-100 dark:border-zinc-700 hover:border-blue-200 dark:hover:border-blue-800 transition">
                <svg class="w-8 h-8 text-blue-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?= $f['icon'] ?>"/></svg>
                <h3 class="font-bold text-zinc-900 dark:text-white mb-1"><?= $_l('feat_' . $f['key']) ?></h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= $_l('feat_' . $f['key'] . '_desc') ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 시스템 요구사항 -->
<?php if ($_showReqs): ?>
<section class="py-16 bg-gray-50 dark:bg-zinc-900">
    <div class="max-w-5xl mx-auto px-4">
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white text-center mb-10"><?= $_l('requirements') ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl mx-auto">
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-6">
                <h3 class="font-bold text-zinc-900 dark:text-white mb-4"><?= $_l('required') ?></h3>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-700">
                        <tr><td class="py-2 text-zinc-400 w-24">PHP</td><td class="py-2 font-medium text-zinc-800 dark:text-zinc-200">8.0 <?= $_l('or_higher') ?> (8.3 <?= $_l('recommended') ?>)</td></tr>
                        <tr><td class="py-2 text-zinc-400">MySQL</td><td class="py-2 font-medium text-zinc-800 dark:text-zinc-200">5.7 <?= $_l('or_higher') ?> / MariaDB 10.3+</td></tr>
                        <tr><td class="py-2 text-zinc-400"><?= $_l('webserver') ?></td><td class="py-2 font-medium text-zinc-800 dark:text-zinc-200">Nginx / Apache</td></tr>
                        <tr><td class="py-2 text-zinc-400"><?= $_l('disk') ?></td><td class="py-2 font-medium text-zinc-800 dark:text-zinc-200">50MB <?= $_l('or_higher') ?></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-6">
                <h3 class="font-bold text-zinc-900 dark:text-white mb-4"><?= $_l('php_ext') ?></h3>
                <div class="flex flex-wrap gap-2">
                    <?php foreach (['mbstring','json','pdo_mysql','openssl','fileinfo','curl','gd','xml','zip','intl'] as $ext): ?>
                    <span class="px-2.5 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 text-xs font-mono rounded"><?= $ext ?></span>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-zinc-400 mt-4"><?= $_l('ioncube') ?></p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 버전 히스토리 -->
<?php if ($_showHistory && count($_versions) > 0): ?>
<section class="py-16 bg-white dark:bg-zinc-800">
    <div class="max-w-3xl mx-auto px-4">
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white text-center mb-10"><?= $_l('version_history') ?></h2>
        <div class="space-y-3">
            <?php foreach ($_versions as $i => $v): ?>
            <div class="flex items-center justify-between p-4 rounded-xl <?= $i === 0 ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' : 'bg-gray-50 dark:bg-zinc-700/30' ?>">
                <div class="flex items-center gap-3">
                    <?php if ($i === 0): ?>
                    <span class="text-[10px] px-2 py-0.5 bg-blue-600 text-white rounded-full font-medium"><?= $_l('latest') ?></span>
                    <?php endif; ?>
                    <div>
                        <p class="font-bold text-zinc-900 dark:text-white">v<?= htmlspecialchars($v['version']) ?></p>
                        <p class="text-xs text-zinc-400"><?= htmlspecialchars($v['date']) ?> · <?= $v['size'] ?>MB</p>
                    </div>
                </div>
                <a href="/download/voscms-<?= htmlspecialchars($v['version']) ?>.zip" class="text-sm font-medium text-blue-600 hover:underline flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <?= $_l('download') ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
