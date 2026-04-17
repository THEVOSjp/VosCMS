    <!-- 위젯 페이지: WidgetRenderer로 렌더링 -->
    <?php
    // 전체폭 위젯 타입 (컨테이너 밖에서 렌더링)
    $_fullWidthTypes = ['hero', 'hero-slider', 'cta', 'cta001', 'stats', 'testimonials', 'shop-map', 'location-map', 'contact-info', 'contact-form'];
    ?>
    <?php if ($_showBreadcrumb && !$_hasTitleBg): ?>
    <div class="<?= $_contentWidth ?> mx-auto px-4 sm:px-6 pt-6">
        <nav class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
            <a href="<?= $config['app_url'] ?? '' ?>/" class="hover:text-blue-600"><?= __('common.home') ?? '홈' ?></a>
            <span class="mx-1">/</span>
            <span class="text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($pageTitle) ?></span>
        </nav>
    </div>
    <?php endif; ?>
    <?php if ($_customHeader): ?><div class="<?= $_contentWidth ?> mx-auto px-4 sm:px-6"><div class="mb-4"><?= $_customHeader ?></div></div><?php endif; ?>
    <?php if ($_isHomePage): ?>
    <?php elseif ($_showTitle && $_hasTitleBg): ?>
        <?php include __DIR__ . '/_page-title-bg.php'; ?>
    <?php elseif ($_showTitle): ?>
    <div class="<?= $_contentWidth ?> mx-auto px-4 sm:px-6 pt-6">
        <h1 class="text-2xl font-bold <?= $_titleTextClass ?> mb-4 inline-flex items-center gap-2"><?= htmlspecialchars($pageTitle) ?> <?= $_adminIcons ?></h1>
    </div>
    <?php endif; ?>
    <?php
    require_once BASE_PATH . '/rzxlib/Core/Modules/WidgetLoader.php';
    require_once BASE_PATH . '/rzxlib/Core/Modules/WidgetRenderer.php';
    require_once BASE_PATH . '/rzxlib/Core/Modules/WidgetHelpers.php';
    $renderer = new \RzxLib\Core\Modules\WidgetRenderer($pdo, $pageSlug, $currentLocale ?? 'ko', $baseUrl ?? '');
    $widgets = $pdo->prepare("SELECT pw.*, w.slug as widget_type, w.name as widget_name FROM {$prefix}page_widgets pw LEFT JOIN {$prefix}widgets w ON pw.widget_id = w.id WHERE pw.page_slug = ? AND pw.is_active = 1 ORDER BY pw.sort_order");
    $widgets->execute([$pageSlug]);
    $widgetList = $widgets->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($widgetList)) {
        $_savedConfig = $config;
        $locale = $currentLocale ?? 'ko';
        foreach ($widgetList as $w) {
            $config = json_decode($w['config'] ?? '{}', true) ?: [];
            $widgetType = $w['widget_type'] ?? ($w['widget_slug'] ?? 'text');
            $renderFile = BASE_PATH . '/widgets/' . $widgetType . '/render.php';
            $_isFullWidth = in_array($widgetType, $_fullWidthTypes);
            if (!$_isFullWidth) echo '<div class="' . $_contentWidth . ' mx-auto px-4 sm:px-6 py-2">';
            if (file_exists($renderFile)) {
                try {
                    $__wOut = include $renderFile;
                    if (is_string($__wOut)) {
                        echo $__wOut;
                    }
                } catch (\Throwable $__wErr) {
                    echo '<!-- WIDGET_ERROR[' . $widgetType . ']: ' . htmlspecialchars($__wErr->getMessage()) . ' at ' . $__wErr->getFile() . ':' . $__wErr->getLine() . ' -->';
                }
            }
            if (!$_isFullWidth) echo '</div>';
        }
        $config = $_savedConfig;
    } else {
        echo '<div class="py-16 text-center text-zinc-400">위젯이 아직 추가되지 않았습니다.</div>';
    }
    ?>
    <?php if ($_customFooter): ?><div class="<?= $_contentWidth ?> mx-auto px-4 sm:px-6"><div class="mt-4"><?= $_customFooter ?></div></div><?php endif; ?>
