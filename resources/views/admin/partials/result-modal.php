<?php
/**
 * 공통 결과 모달 include
 *
 * </body> 전에 include하면:
 * 1. result-modal.js 로드
 * 2. 번역 데이터 전달
 * 3. $message/$messageType이 있으면 페이지 로드 시 자동 모달 표시
 *
 * JS API: showResultModal(success, message), closeResultModal()
 */
$_baseUrl = $config['app_url'] ?? ($baseUrl ?? '');
?>
<div id="rzxResultModalData" class="hidden"
     data-success="<?= htmlspecialchars(__('common.msg.success') ?? 'Successfully processed.') ?>"
     data-error="<?= htmlspecialchars(__('common.msg.error') ?? 'An error occurred.') ?>"
     data-saved="<?= htmlspecialchars(__('common.msg.saved') ?? 'Saved.') ?>"
     data-confirm="<?= htmlspecialchars(__('common.buttons.confirm') ?? 'OK') ?>"></div>
<script src="<?= $_baseUrl ?>/assets/js/result-modal.js?v=<?= filemtime(BASE_PATH . '/assets/js/result-modal.js') ?>"></script>
<?php if (!empty($message) && !empty($messageType)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showResultModal(<?= $messageType === 'success' ? 'true' : 'false' ?>, <?= json_encode($message, JSON_UNESCAPED_UNICODE) ?>);
});
</script>
<?php endif; ?>
