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
                $headerActions = '<button class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition flex items-center"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>' . __('site.pages.add') . '</button>';
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
$_sysPages = [
    ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'color' => 'blue', 'badge' => 'widget', 'slug' => '', 'title' => __('site.pages.home'), 'edit' => $adminUrl . '/site/pages/widget-builder', 'preview' => '/'],
    ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'color' => 'green', 'badge' => 'document', 'slug' => 'terms', 'title' => __('site.pages.terms'), 'edit' => $adminUrl . '/site/pages/edit?slug=terms', 'preview' => '/terms'],
    ['icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'color' => 'purple', 'badge' => 'document', 'slug' => 'privacy', 'title' => __('site.pages.privacy'), 'edit' => $adminUrl . '/site/pages/edit?slug=privacy', 'preview' => '/privacy'],
    ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'color' => 'amber', 'badge' => 'document', 'slug' => 'data-policy', 'title' => __('site.pages.data_policy'), 'edit' => $adminUrl . '/site/pages/compliance', 'preview' => '/data-policy'],
    ['icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'color' => 'red', 'badge' => 'document', 'slug' => 'refund-policy', 'title' => __('site.pages.refund_policy'), 'edit' => $adminUrl . '/site/pages/edit?slug=refund-policy', 'preview' => '/refund-policy'],
    ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'color' => 'emerald', 'badge' => 'widget', 'slug' => 'staff', 'title' => __('site.pages.staff_intro') ?? '스태프 소개', 'edit' => $adminUrl . '/site/pages/widget-builder?slug=staff', 'preview' => '/staff'],
    ['icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'emerald', 'badge' => 'widget', 'slug' => 'booking', 'title' => __('site.pages.booking') ?? '예약하기', 'edit' => $adminUrl . '/site/pages/widget-builder?slug=booking', 'preview' => '/booking'],
    ['icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', 'color' => 'emerald', 'badge' => 'widget', 'slug' => 'lookup', 'title' => __('site.pages.lookup') ?? '예약 조회', 'edit' => $adminUrl . '/site/pages/widget-builder?slug=lookup', 'preview' => '/lookup'],
];
$_badgeMap = [
    'widget' => ['bg' => 'purple', 'label' => __('site.pages.type_widget')],
    'document' => ['bg' => 'blue', 'label' => __('site.pages.type_document')],
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
                    $systemSlugs = ['terms', 'privacy', 'data-policy', 'refund-policy', 'home', 'staff', 'booking', 'lookup'];
                    $placeholders = implode(',', array_fill(0, count($systemSlugs), '?'));
                    $defaultLocale = $config['locale'] ?? 'ko';
                    $customPages = $pdo->prepare("
                        SELECT page_slug, page_type, title, is_active, created_at
                        FROM rzx_page_contents
                        WHERE page_slug NOT IN ({$placeholders}) AND locale = ?
                        ORDER BY created_at DESC
                    ");
                    $customPages->execute(array_merge($systemSlugs, [$defaultLocale]));
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
                        } else {
                            $_upIcon = 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
                            $_upColor = 'blue'; $_upTypeLabel = __('site.pages.type_document') ?? '문서';
                        }
                        $_upEditUrl = $adminUrl . '/site/pages/settings?slug=' . urlencode($_up['page_slug']);
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
</body>
</html>
