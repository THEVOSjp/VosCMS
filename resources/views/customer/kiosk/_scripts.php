<script>
// 전체화면 (더블클릭)
document.addEventListener('dblclick', () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
    } else {
        document.exitFullscreen();
    }
});
// 우클릭 방지
document.addEventListener('contextmenu', e => e.preventDefault());
// 유휴 타이머
let idleTimer;
function resetIdleTimer() {
    clearTimeout(idleTimer);
    idleTimer = setTimeout(() => {
        console.log('[Kiosk] Idle timeout');
        window.location.href = '<?= $adminUrl ?>/kiosk/run';
    }, <?= $kioskIdleTimeout ?> * 1000);
}
['mousemove', 'touchstart', 'keydown', 'click', 'scroll'].forEach(evt => {
    document.addEventListener(evt, resetIdleTimer, { passive: true });
});
resetIdleTimer();
</script>
