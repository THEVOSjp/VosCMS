<?php
/**
 * VosCMS Marketplace - 관리자 아이템 등록/편집
 */
include __DIR__ . '/_head.php';

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $categories = $pdo->query("SELECT * FROM {$prefix}mp_categories WHERE is_active = 1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $categories = []; }

// 편집 모드
$editId = (int)($_GET['id'] ?? 0);
$editItem = null;
$editVersions = [];
if ($editId) {
    try {
        $editItem = $pdo->query("SELECT * FROM {$prefix}mp_items WHERE id = {$editId}")->fetch(PDO::FETCH_ASSOC);
        if ($editItem) {
            $editVersions = $pdo->query("SELECT * FROM {$prefix}mp_item_versions WHERE item_id = {$editId} ORDER BY released_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {}
}
$isEdit = !empty($editItem);

$pageHeaderTitle = $isEdit ? '아이템 편집' : '새 아이템 등록';
?>

<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= $pageHeaderTitle ?></h1>
        <a href="<?= $adminUrl ?>/marketplace" class="text-sm text-zinc-500 hover:text-indigo-600 transition">&larr; 마켓플레이스</a>
    </div>

    <div id="result" class="hidden mb-4 p-4 rounded-lg text-sm"></div>

    <?php
    $formAction = $adminUrl . '/marketplace/api';
    $backUrl = $adminUrl . '/marketplace';
    $context = 'admin';
    include __DIR__ . '/_components/item-form.php';
    ?>
</div>

<?php include __DIR__ . '/_foot.php'; ?>
