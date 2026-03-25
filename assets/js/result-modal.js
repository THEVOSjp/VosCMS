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
