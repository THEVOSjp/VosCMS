/**
 * RezlyX 공통 결과 모달
 *
 * 사용법:
 *   showResultModal(true);               // 성공 (기본 메시지)
 *   showResultModal(true, '커스텀 메시지');  // 성공 (커스텀 메시지)
 *   showResultModal(false, '에러 메시지');   // 실패
 */
function showResultModal(success, message) {
    var existing = document.getElementById('rzxResultModal');
    if (existing) existing.remove();

    var el = document.getElementById('rzxResultModalData');
    var successLabel = (el && el.dataset.success) || 'Success';
    var errorLabel = (el && el.dataset.error) || 'Error';
    var savedLabel = (el && el.dataset.saved) || 'Saved.';
    var confirmLabel = (el && el.dataset.confirm) || 'OK';

    // 커스텀 메시지가 있으면 우선, 없으면 기본 메시지
    var displayMsg = (message && message !== 'null' && message !== '')
        ? message
        : (success ? savedLabel : errorLabel);

    var iconSvg = success
        ? '<svg class="w-14 h-14 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        : '<svg class="w-14 h-14 text-red-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

    var btnColor = success ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700';

    var modal = document.createElement('div');
    modal.id = 'rzxResultModal';
    modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center';
    modal.innerHTML = ''
        + '<div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeResultModal()"></div>'
        + '<div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center transform scale-95 opacity-0 transition-all duration-200" id="rzxResultModalContent">'
        +   iconSvg
        +   '<p class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">' + (success ? successLabel : errorLabel) + '</p>'
        +   '<p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">' + displayMsg + '</p>'
        +   '<button onclick="closeResultModal()" class="mt-6 px-8 py-2.5 rounded-lg text-sm font-medium text-white ' + btnColor + ' transition">' + confirmLabel + '</button>'
        + '</div>';

    document.body.appendChild(modal);
    requestAnimationFrame(function() {
        var c = document.getElementById('rzxResultModalContent');
        if (c) { c.style.opacity = '1'; c.style.transform = 'scale(1)'; }
    });
}

function closeResultModal() {
    var c = document.getElementById('rzxResultModalContent');
    if (c) { c.style.opacity = '0'; c.style.transform = 'scale(0.95)'; }
    setTimeout(function() {
        var m = document.getElementById('rzxResultModal');
        if (m) m.remove();
    }, 150);
}

/**
 * 공통 확인 모달 (체크박스 필수 동의)
 *
 * 사용법:
 *   showConfirmModal({
 *       title: '이 항목을 삭제하시겠습니까?',
 *       message: '삭제하면 되돌릴 수 없습니다.',
 *       checkLabel: '연결된 페이지(게시판)도 삭제된다는 것을 알고 있습니다.',
 *       confirmText: '삭제',
 *       onConfirm: function() { ... }
 *   });
 */
function showConfirmModal(opts) {
    var existing = document.getElementById('rzxConfirmModal');
    if (existing) existing.remove();

    var title = opts.title || '확인';
    var message = opts.message || '';
    var checkLabel = opts.checkLabel || '';
    var confirmText = opts.confirmText || '확인';
    var cancelText = opts.cancelText || '취소';
    var onConfirm = opts.onConfirm || function() {};
    var danger = opts.danger !== false;

    var checkboxHtml = checkLabel
        ? '<label class="flex items-start gap-2 mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg cursor-pointer border border-red-200 dark:border-red-800">'
        +   '<input type="checkbox" id="rzxConfirmCheck" class="mt-0.5 text-red-600 rounded border-gray-300 dark:border-zinc-500">'
        +   '<span class="text-xs text-red-700 dark:text-red-300">' + checkLabel + '</span>'
        + '</label>'
        : '';

    var btnColor = danger ? 'bg-red-600 hover:bg-red-700 disabled:bg-red-300 dark:disabled:bg-red-900' : 'bg-blue-600 hover:bg-blue-700';
    var disabledAttr = checkLabel ? ' disabled' : '';

    var modal = document.createElement('div');
    modal.id = 'rzxConfirmModal';
    modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center';
    modal.innerHTML = ''
        + '<div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeConfirmModal()"></div>'
        + '<div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center transform scale-95 opacity-0 transition-all duration-200" id="rzxConfirmModalContent">'
        +   '<svg class="w-14 h-14 text-amber-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>'
        +   '<p class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">' + title + '</p>'
        +   (message ? '<p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">' + message + '</p>' : '')
        +   checkboxHtml
        +   '<div class="mt-6 flex gap-3 justify-center">'
        +     '<button onclick="closeConfirmModal()" class="px-6 py-2.5 rounded-lg text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">' + cancelText + '</button>'
        +     '<button id="rzxConfirmBtn" onclick="executeConfirm()" class="px-6 py-2.5 rounded-lg text-sm font-medium text-white ' + btnColor + ' transition disabled:cursor-not-allowed"' + disabledAttr + '>' + confirmText + '</button>'
        +   '</div>'
        + '</div>';

    document.body.appendChild(modal);

    // 체크박스 → 버튼 활성화
    if (checkLabel) {
        var cb = document.getElementById('rzxConfirmCheck');
        var btn = document.getElementById('rzxConfirmBtn');
        if (cb && btn) {
            cb.addEventListener('change', function() { btn.disabled = !cb.checked; });
        }
    }

    // 확인 콜백 저장
    window._rzxConfirmCallback = onConfirm;

    requestAnimationFrame(function() {
        var c = document.getElementById('rzxConfirmModalContent');
        if (c) { c.style.opacity = '1'; c.style.transform = 'scale(1)'; }
    });
}

function executeConfirm() {
    if (window._rzxConfirmCallback) window._rzxConfirmCallback();
    closeConfirmModal();
}

function closeConfirmModal() {
    var c = document.getElementById('rzxConfirmModalContent');
    if (c) { c.style.opacity = '0'; c.style.transform = 'scale(0.95)'; }
    setTimeout(function() {
        var m = document.getElementById('rzxConfirmModal');
        if (m) m.remove();
        window._rzxConfirmCallback = null;
    }, 150);
}
