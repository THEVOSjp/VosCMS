<?php
/**
 * Developer - 아이템 제출/편집 페이지
 * 공통 폼 컴포넌트(item-form.php) 사용
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['developer_id'])) { header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/developer/login'); exit; }

include __DIR__ . '/partials/_layout_head.php';
$pageTitle = __mp('dev_submit');

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $categories = $pdo->query("SELECT * FROM {$prefix}mp_categories WHERE is_active = 1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $categories = []; }
$locale = $_mpLocale ?? 'ko';

// 편집 모드
$editId = (int)($_GET['id'] ?? 0);
$editItem = null;
$editVersions = [];
if ($editId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}mp_items WHERE id = ? AND seller_id = ?");
        $stmt->execute([$editId, $_SESSION['developer_id']]);
        $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($editItem) {
            $vStmt = $pdo->prepare("SELECT * FROM {$prefix}mp_item_versions WHERE item_id = ? ORDER BY released_at DESC");
            $vStmt->execute([$editId]);
            $editVersions = $vStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {}
}
$isEdit = !empty($editItem);
?>

<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= $isEdit ? __mp('submit_edit_title') : __mp('submit_title') ?></h1>
        <?php if ($isEdit): ?>
        <a href="<?= $baseUrl ?>/developer/my-items" class="text-sm text-zinc-500 hover:text-indigo-600 transition">&larr; <?= __mp('submit_back_list') ?></a>
        <?php endif; ?>
    </div>

    <div id="result" class="hidden mb-4 p-4 rounded-lg text-sm"></div>

    <?php
    $formAction = $baseUrl . '/api/developer/submit';
    $backUrl = $baseUrl . '/developer/my-items';
    $context = 'developer';
    include BASE_PATH . '/plugins/vos-marketplace/views/admin/_components/item-form.php';
    ?>
</div>

<?php include __DIR__ . '/partials/_layout_foot.php'; ?>
