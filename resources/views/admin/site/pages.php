<?php
/**
 * RezlyX Admin - 페이지 관리
 */
$pageTitle = '페이지 관리 - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('데이터베이스 연결 실패: ' . $e->getMessage());
}

$message = '';
$messageType = '';
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 페이지 삭제 AJAX 핸들러 제거됨 (v2.3.1)
// 원칙: 페이지는 메뉴를 따라간다. 메뉴 관리에서 메뉴를 삭제하면 페이지도 자동 삭제.
// 페이지 관리에서 단독 삭제 경로는 제공하지 않음 → 깨진 링크(메뉴는 있는데 페이지 없음) 발생 방지.

// Base URLs for navigation
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');


$pageHeaderTitle = __('site.pages.title');
?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>
                <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Header -->
                <div class="mb-6">
                <?php
                $headerIcon = 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
                $headerTitle = __('site.pages.title');
                $headerDescription = __('site.pages.description');
                $headerIconColor = '';
                // "새 페이지" 버튼 제거됨 (v2.3.1) — 페이지는 메뉴 관리에서 생성.
                $headerActions = '';
                include __DIR__ . '/../components/settings-header.php';
                ?>
                </div>

                <!-- Page List -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm transition-colors">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('site.pages.list') ?></h2>
                    </div>

                    <!-- Default Pages -->
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
<?php
// 시스템 페이지 데이터 (아이콘, 색상, 타입배지, slug, 제목, 설정URL, 수정URL, 미리보기URL)
// config/system-pages.php에서 로드
$_sysPagesDef = function_exists('load_system_pages') ? load_system_pages() : [];
$_sysPages = [];
$_sysSlugs = [];
foreach ($_sysPagesDef as $_spd) {
    if (!empty($_spd['hidden'])) continue;
    $_editUrl = str_replace('{admin}', $adminUrl, $_spd['edit'] ?? '');
    $_sysPages[] = [
        'icon' => $_spd['icon'] ?? '', 'color' => $_spd['color'] ?? 'blue',
        'badge' => $_spd['type'] ?? 'document', 'slug' => $_spd['slug'] ?? '',
        'title' => $_spd['title'] ?? '', 'edit' => $_editUrl, 'preview' => '/' . ($_spd['slug'] ?? ''),
    ];
    $_sysSlugs[] = $_spd['slug'] ?? '';
}
// DB에서 is_system=1인 문서 페이지도 자동 추가 (config에 없는 것만)
$_defaultLocale = $config['locale'] ?? 'ko';
$_dbSysStmt = $pdo->prepare("SELECT page_slug, page_type, title FROM {$prefix}page_contents WHERE is_system = 1 AND is_active = 1 ORDER BY page_slug");
$_dbSysStmt->execute();
$_docIcon = 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
while ($_dbSys = $_dbSysStmt->fetch(PDO::FETCH_ASSOC)) {
    if (in_array($_dbSys['page_slug'], $_sysSlugs)) continue;
    $_dbSysTitle = function_exists('db_trans') ? db_trans('page.' . $_dbSys['page_slug'] . '.title', null, $_dbSys['title']) : $_dbSys['title'];
    $_sysPages[] = [
        'icon' => $_docIcon, 'color' => 'blue',
        'badge' => $_dbSys['page_type'] ?? 'document', 'slug' => $_dbSys['page_slug'],
        'title' => $_dbSysTitle, 'edit' => $adminUrl . '/site/pages/edit?slug=' . urlencode($_dbSys['page_slug']),
        'preview' => '/' . $_dbSys['page_slug'],
    ];
}
$_badgeMap = [
    'widget' => ['bg' => 'purple', 'label' => __('site.pages.type_widget')],
    'document' => ['bg' => 'blue', 'label' => __('site.pages.type_document')],
    'system' => ['bg' => 'indigo', 'label' => __('site.pages.type_system') ?? '시스템'],
];
foreach ($_sysPages as $_sp):
    $_b = $_badgeMap[$_sp['badge']] ?? $_badgeMap['document'];
    $_settingsUrl = $adminUrl . '/site/pages/settings?slug=' . urlencode($_sp['slug'] ?: 'home');
?>
                        <div class="p-4 flex items-center justify-between hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-<?= $_sp['color'] ?>-100 dark:bg-<?= $_sp['color'] ?>-900/30 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-<?= $_sp['color'] ?>-600 dark:text-<?= $_sp['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $_sp['icon'] ?>"/></svg>
                                </div>
                                <div>
                                    <h4 class="font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($_sp['title']) ?></h4>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">/<?= htmlspecialchars($_sp['slug']) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium px-2 py-0.5 bg-<?= $_b['bg'] ?>-100 dark:bg-<?= $_b['bg'] ?>-900/30 text-<?= $_b['bg'] ?>-700 dark:text-<?= $_b['bg'] ?>-300 rounded"><?= $_b['label'] ?></span>
                                <span class="text-xs font-medium px-2 py-0.5 bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 rounded"><?= __('site.pages.system_page') ?></span>
                                <a href="<?= $_settingsUrl ?>" class="p-1.5 text-zinc-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition" title="<?= __('common.settings') ?? '설정' ?>"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></a>
                                <?php if ($_sp['edit']): ?>
                                <a href="<?= $_sp['edit'] ?>" class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/30 rounded-lg transition"><?= __('admin.buttons.edit') ?></a>
                                <?php endif; ?>
                                <a href="<?= $baseUrl . $_sp['preview'] ?>" target="_blank" class="p-1.5 text-zinc-400 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition" title="<?= __('common.preview') ?? '미리보기' ?>"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                            </div>
                        </div>
<?php endforeach; ?>
                    </div>

                    <!-- 사용자 생성 페이지 (DB에서 동적 로드) -->
                    <?php
                    $systemSlugs = array_column($_sysPagesDef, 'slug');
                    $placeholders = implode(',', array_fill(0, count($systemSlugs), '?'));
                    $defaultLocale = $config['locale'] ?? 'ko';
                    $customPages = $pdo->prepare("
                        SELECT page_slug, page_type, title, is_active, created_at
                        FROM {$prefix}page_contents
                        WHERE page_slug NOT IN ({$placeholders}) AND is_system = 0
                        ORDER BY created_at DESC
                    ");
                    $customPages->execute($systemSlugs);
                    $userPages = $customPages->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <?php if (!empty($userPages)): ?>
                    <!-- 사용자 페이지 섹션 구분 -->
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-700/30 border-t border-zinc-200 dark:border-zinc-700">
                        <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= __('site.pages.custom_pages') ?? '사용자 페이지' ?></span>
                    </div>

                    <?php foreach ($userPages as $_up):
                        // 타입별 아이콘/색상
                        $_upType = $_up['page_type'] ?? 'document';
                        if ($_upType === 'system') {
                            $_upIcon = 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z';
                            $_upColor = 'emerald'; $_upTypeLabel = __('site.pages.type_system') ?? '시스템';
                        } elseif ($_upType === 'widget') {
                            $_upIcon = 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z';
                            $_upColor = 'purple'; $_upTypeLabel = __('site.pages.type_widget') ?? '위젯';
                            $_upEditUrl = $adminUrl . '/site/pages/widget-builder?slug=' . urlencode($_up['page_slug']);
                        } elseif ($_upType === 'external') {
                            $_upIcon = 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14';
                            $_upColor = 'orange'; $_upTypeLabel = __('site.pages.type_external') ?? '외부';
                            $_upEditUrl = $adminUrl . '/site/pages/edit?slug=' . urlencode($_up['page_slug']);
                        } else {
                            $_upIcon = 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
                            $_upColor = 'blue'; $_upTypeLabel = __('site.pages.type_document') ?? '문서';
                            $_upEditUrl = $adminUrl . '/site/pages/edit?slug=' . urlencode($_up['page_slug']);
                        }
                    ?>
                    <div class="p-4 flex items-center justify-between hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition border-t border-zinc-100 dark:border-zinc-700/50">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-<?= $_upColor ?>-100 dark:bg-<?= $_upColor ?>-900/30 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-<?= $_upColor ?>-600 dark:text-<?= $_upColor ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $_upIcon ?>"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($_up['title'] ?: $_up['page_slug']) ?></h4>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">/<?= htmlspecialchars($_up['page_slug']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium px-2 py-0.5 bg-<?= $_upColor ?>-100 dark:bg-<?= $_upColor ?>-900/30 text-<?= $_upColor ?>-700 dark:text-<?= $_upColor ?>-300 rounded"><?= $_upTypeLabel ?></span>
                            <?php if (!$_up['is_active']): ?>
                            <span class="text-xs font-medium px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded"><?= __('common.inactive') ?? '비활성' ?></span>
                            <?php endif; ?>
                            <a href="<?= $adminUrl ?>/site/pages/settings?slug=<?= urlencode($_up['page_slug']) ?>" class="p-1.5 text-zinc-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition" title="<?= __('common.settings') ?? '설정' ?>"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></a>
                            <a href="<?= $_upEditUrl ?>" class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/30 rounded-lg transition"><?= __('admin.buttons.edit') ?></a>
                            <a href="<?= $baseUrl ?>/<?= htmlspecialchars($_up['page_slug']) ?>" target="_blank" class="p-1.5 text-zinc-400 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition" title="<?= __('common.preview') ?? '미리보기' ?>"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <!-- 사용자 생성 페이지 없음 -->
                    <div class="p-8 text-center border-t border-zinc-200 dark:border-zinc-700">
                        <svg class="w-12 h-12 mx-auto mb-4 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('site.pages.empty') ?></p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1"><?= __('site.pages.empty_hint') ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
<?php include __DIR__ . '/../partials/result-modal.php'; ?>
</body>
</html>
