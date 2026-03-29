<?php
/**
 * 예약 생성 페이지 - 공용 컴포넌트 사용
 */
include __DIR__ . '/_init.php';

$pageTitle = __('reservations.create') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$services = getServices($pdo, $prefix);

// 번들 로드 (calendar-loader와 동일 로직)
$_createBundles = [];
try {
    $_bStmt = $pdo->query("SELECT b.*, GROUP_CONCAT(bi.service_id) as svc_ids, COUNT(bi.service_id) as svc_count, SUM(sv.duration) as total_duration FROM {$prefix}service_bundles b LEFT JOIN {$prefix}service_bundle_items bi ON b.id = bi.bundle_id LEFT JOIN {$prefix}services sv ON bi.service_id = sv.id WHERE b.is_active = 1 GROUP BY b.id ORDER BY b.display_order");
    while ($_b = $_bStmt->fetch(PDO::FETCH_ASSOC)) {
        $_b['svc_id_list'] = $_b['svc_ids'] ? explode(',', $_b['svc_ids']) : [];
        $now = date('Y-m-d H:i:s');
        $_b['is_event'] = $_b['event_price'] && $_b['event_price'] > 0 && (!$_b['event_start'] || $_b['event_start'] <= $now) && (!$_b['event_end'] || $_b['event_end'] >= $now);
        $_b['display_price'] = $_b['is_event'] ? $_b['event_price'] : $_b['bundle_price'];
        if ($_b['image'] && !str_starts_with($_b['image'], 'http') && !str_starts_with($_b['image'], '/')) $_b['image'] = '/' . ltrim($_b['image'], '/');
        $_createBundles[] = $_b;
    }
} catch (PDOException $e) {}

$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['errors'], $_SESSION['old_input']);

include __DIR__ . '/_head.php';
?>

<div class="flex items-center gap-3 mb-6">
    <a href="<?= $adminUrl ?>/reservations" class="p-2 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('reservations.create') ?></h2>
</div>

<?php if (!empty($errors)): ?>
<div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
    <ul class="text-sm text-red-600 dark:text-red-400 list-disc list-inside">
        <?php foreach ($errors as $err): ?><li><?= htmlspecialchars(is_array($err) ? implode(', ', $err) : $err) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php
// 공용 예약 폼 컴포넌트
$resForm = [
    'services'         => $services,
    'bundles'          => $_createBundles,
    'adminUrl'         => $adminUrl,
    'csrfToken'        => $csrfToken,
    'currencySymbol'   => $currencySymbol,
    'currencyPosition' => $currencyPosition,
    'formId'           => 'createForm',
    'mode'             => 'page',
    'defaultDate'      => date('Y-m-d'),
    'old'              => $old,
];

include BASE_PATH . '/resources/views/admin/components/reservation-form.php';
include BASE_PATH . '/resources/views/admin/components/reservation-form-js.php';
?>

<?php include __DIR__ . '/_foot.php'; ?>
