<script>
console.log('[BoardEdit] 게시판 설정 JS 로드됨, boardId=<?= $boardId ?>');
const adminUrl = '<?= $adminUrl ?>';
const boardId = <?= $boardId ?>;

// 모든 게시판 설정 폼에 대한 공통 submit 핸들러
['boardEditForm', 'boardPermForm', 'boardListForm', 'boardAdvForm'].forEach(formId => {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('[BoardEdit] 폼 저장:', formId);

        const fd = new FormData(this);

        // 체크박스 미체크 시 0으로 설정
        const checkboxes = ['is_active', 'allow_comment', 'use_anonymous', 'allow_secret',
            'consultation', 'use_trash', 'update_order_on_comment', 'protect_content_by_comment', 'except_notice'];
        checkboxes.forEach(name => {
            if (this.querySelector(`[name="${name}"]`) && !fd.has(name)) {
                fd.set(name, '0');
            }
        });

        try {
            const resp = await fetch(adminUrl + '/site/boards/api', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams(fd)
            });
            const data = await resp.json();
            console.log('[BoardEdit] 응답:', data);

            if (typeof showResultModal === 'function') {
                showResultModal(data.success, data.success ? '' : (data.message || 'Error'));
            } else if (data.success) {
                const status = document.getElementById('saveStatus');
                if (status) { status.textContent = data.message || '<?= __('site.boards.saved') ?>'; status.classList.remove('hidden'); setTimeout(() => status.classList.add('hidden'), 3000); }
            } else {
                alert(data.message || 'Error');
            }
        } catch (err) {
            console.error('[BoardEdit] 에러:', err);
            if (typeof showResultModal === 'function') showResultModal(false, err.message);
            else alert('Error: ' + err.message);
        }
    });
});
</script>
