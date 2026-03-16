<?php
/**
 * 키오스크 접수 완료 화면 (confirm.php에서 include)
 * 변수: $resultNumber, $resultWaiting, $staffName
 */
?>
        <div class="flex-1 flex flex-col items-center justify-center px-8">
            <div class="max-w-md w-full text-center space-y-8">

                <!-- 체크 아이콘 (애니메이션) -->
                <div class="mx-auto w-24 h-24 rounded-full bg-green-500 flex items-center justify-center animate-bounce-once">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <!-- 접수 완료 메시지 -->
                <div>
                    <h2 class="text-3xl font-bold <?= $textColor ?>"><?= __('reservations.kiosk_checkin_done') ?></h2>
                    <p class="<?= $subTextColor ?> text-sm mt-2"><?= __('reservations.kiosk_checkin_done_desc') ?></p>
                </div>

                <!-- 대기번호 -->
                <div class="p-6 rounded-2xl backdrop-blur-sm border <?= $btnBg ?>">
                    <p class="<?= $subTextColor ?> text-sm mb-2"><?= __('reservations.pos_waiting_number') ?></p>
                    <p class="text-6xl font-black <?= $isLight ? 'text-blue-600' : 'text-blue-400' ?>"><?= $resultWaiting ?></p>
                </div>

                <!-- 접수 번호 -->
                <div class="p-4 rounded-xl backdrop-blur-sm border <?= $btnBg ?>">
                    <p class="<?= $subTextColor ?> text-xs mb-1"><?= __('reservations.kiosk_receipt_number') ?></p>
                    <p class="<?= $textColor ?> text-sm font-mono tracking-wider"><?= htmlspecialchars($resultNumber) ?></p>
                </div>

                <?php if ($staffName): ?>
                <p class="<?= $subTextColor ?> text-sm">
                    <?= __('reservations.kiosk_staff_selected') ?>: <span class="<?= $textColor ?> font-semibold"><?= htmlspecialchars($staffName) ?></span>
                </p>
                <?php endif; ?>

            </div>
        </div>

        <!-- 하단: 자동 복귀 카운트다운 -->
        <div class="py-6 text-center">
            <p class="<?= $subTextColor ?> text-sm">
                <?= __('reservations.kiosk_auto_return') ?> <span id="countdown" class="<?= $textColor ?> font-bold">10</span><?= __('reservations.kiosk_seconds') ?>
            </p>
        </div>

<style>
@keyframes bounceOnce {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.2); }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); opacity: 1; }
}
.animate-bounce-once {
    animation: bounceOnce 0.6s ease-out forwards;
}
</style>

<script>
console.log('[Kiosk] Checkin completed! Number:', '<?= $resultNumber ?>', 'Waiting:', <?= $resultWaiting ?>);

// 자동 복귀 카운트다운
let remaining = 10;
const countdownEl = document.getElementById('countdown');
const countdownInterval = setInterval(() => {
    remaining--;
    countdownEl.textContent = remaining;
    if (remaining <= 0) {
        clearInterval(countdownInterval);
        console.log('[Kiosk] Auto returning to home');
        window.location.href = '<?= $adminUrl ?>/kiosk/run';
    }
}, 1000);

// 터치 시 즉시 홈으로
document.addEventListener('click', () => {
    clearInterval(countdownInterval);
    window.location.href = '<?= $adminUrl ?>/kiosk/run';
});
</script>
