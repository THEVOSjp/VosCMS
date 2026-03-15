<?php
/**
 * 예약 수정 페이지
 */
include __DIR__ . '/_init.php';

$id = $reservationId ?? $_GET['id'] ?? null;
if (!$id) { header("Location: {$adminUrl}/reservations"); exit; }

$stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$r) { http_response_code(404); echo '<p>예약을 찾을 수 없습니다.</p>'; exit; }

$pageTitle = __('reservations.edit') . ' - ' . ($r['reservation_number'] ?? $id);
$services = getServices($pdo, $prefix);
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);

include __DIR__ . '/_head.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= $adminUrl ?>/reservations/<?= $id ?>" class="p-2 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('reservations.edit') ?></h2>
            <p class="text-sm text-zinc-500 font-mono"><?= htmlspecialchars($r['reservation_number'] ?? '') ?></p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
        <ul class="text-sm text-red-600 dark:text-red-400 list-disc list-inside">
            <?php foreach ($errors as $err): ?><li><?= htmlspecialchars(is_array($err) ? implode(', ', $err) : $err) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= $adminUrl ?>/reservations/<?= $id ?>" class="space-y-6">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="_method" value="PUT">

        <!-- 서비스 선택 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">서비스</h3>
            <select name="service_id" required class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                <?php foreach ($services as $svc): ?>
                <option value="<?= $svc['id'] ?>" <?= $r['service_id'] === $svc['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($svc['name']) ?> (<?= formatPrice((float)($svc['price'] ?? 0)) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- 일시 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">일시</h3>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">날짜</label>
                    <input type="date" name="reservation_date" value="<?= $r['reservation_date'] ?>" required
                           class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                </div>
                <div>
                    <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">시작</label>
                    <input type="time" name="start_time" value="<?= substr($r['start_time'], 0, 5) ?>" required
                           class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                </div>
                <div>
                    <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">종료</label>
                    <input type="time" name="end_time" value="<?= substr($r['end_time'] ?? '', 0, 5) ?>"
                           class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                </div>
            </div>
        </div>

        <!-- 고객 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">고객 정보</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">이름</label>
                    <input type="text" name="customer_name" value="<?= htmlspecialchars($r['customer_name']) ?>" required
                           class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                </div>
                <div>
                    <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">전화번호</label>
                    <input type="tel" name="customer_phone" value="<?= htmlspecialchars($r['customer_phone']) ?>" required
                           class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">이메일</label>
                <input type="email" name="customer_email" value="<?= htmlspecialchars($r['customer_email'] ?? '') ?>"
                       class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
            </div>
            <div class="mt-4">
                <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">고객 메모</label>
                <textarea name="notes" rows="3"
                    class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100 resize-none"><?= htmlspecialchars($r['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- 금액 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">금액</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">총액</label>
                    <input type="number" name="total_amount" value="<?= (int)$r['total_amount'] ?>" step="100"
                           class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                </div>
                <div>
                    <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">할인</label>
                    <input type="number" name="discount_amount" value="<?= (int)$r['discount_amount'] ?>" step="100"
                           class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                </div>
            </div>
        </div>

        <!-- 관리자 메모 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">관리자 메모</h3>
            <textarea name="admin_notes" rows="3"
                class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100 resize-none"><?= htmlspecialchars($r['admin_notes'] ?? '') ?></textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="<?= $adminUrl ?>/reservations/<?= $id ?>" class="px-6 py-2.5 text-zinc-600 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">취소</a>
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition">저장</button>
        </div>
    </form>
</div>

<script>console.log('[Reservations] Edit page loaded, id=<?= $id ?>');</script>

<?php include __DIR__ . '/_foot.php'; ?>
