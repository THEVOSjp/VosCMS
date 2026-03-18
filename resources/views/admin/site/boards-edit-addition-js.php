<script>
console.log('[BoardAddition] 추가 설정 탭 로드됨');
const addAdminUrl = '<?= $adminUrl ?>';

// 각 섹션 폼 저장 핸들러
['addMergeForm', 'addDocForm', 'addCommentForm', 'addEditorForm', 'addFileForm', 'addFeedForm'].forEach(formId => {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('[BoardAddition] 저장:', formId);
        const fd = new FormData(this);

        // 배열 필드 → 쉼표 구분 문자열 변환
        const arrayFields = {};
        for (const [key, value] of fd.entries()) {
            if (key.endsWith('[]')) {
                const cleanKey = key.replace('[]', '');
                if (!arrayFields[cleanKey]) arrayFields[cleanKey] = [];
                arrayFields[cleanKey].push(value);
            }
        }
        // 배열 필드 제거 후 문자열로 재설정
        for (const key of Object.keys(arrayFields)) {
            fd.delete(key + '[]');
            fd.set(key, arrayFields[key].join(','));
        }

        // merge_boards 특수 처리 (JSON)
        if (formId === 'addMergeForm') {
            const mergeSelect = form.querySelector('[name="merge_boards[]"]');
            if (mergeSelect) {
                const selected = [...mergeSelect.selectedOptions].map(o => o.value).filter(v => v);
                fd.delete('merge_boards[]');
                fd.set('merge_boards', JSON.stringify(selected));
            }
        }

        // report_notify 조합
        if (fd.has('report_notify_super') || fd.has('report_notify_board')) {
            const parts = [];
            if (fd.get('report_notify_super')) parts.push('super');
            if (fd.get('report_notify_board')) parts.push('board');
            fd.delete('report_notify_super');
            fd.delete('report_notify_board');
            fd.set('report_notify', parts.join(','));
        }
        if (fd.has('comment_report_notify_super') || fd.has('comment_report_notify_board')) {
            const parts = [];
            if (fd.get('comment_report_notify_super')) parts.push('super');
            if (fd.get('comment_report_notify_board')) parts.push('board');
            fd.delete('comment_report_notify_super');
            fd.delete('comment_report_notify_board');
            fd.set('comment_report_notify', parts.join(','));
        }

        try {
            const resp = await fetch(addAdminUrl + '/site/boards/api', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams(fd)
            });
            const data = await resp.json();
            console.log('[BoardAddition] 응답:', data);
            if (data.success) {
                // 저장 버튼 옆에 임시 표시
                const btn = form.querySelector('button[type="submit"]');
                const span = document.createElement('span');
                span.className = 'text-sm text-green-600 dark:text-green-400 mr-2';
                span.textContent = '<?= __('admin.common.saved') ?? '저장됨' ?>';
                btn.parentElement.insertBefore(span, btn);
                setTimeout(() => span.remove(), 3000);
            } else {
                alert(data.message || 'Error');
            }
        } catch (err) {
            console.error('[BoardAddition] 에러:', err);
            alert('Error: ' + err.message);
        }
    });
});
</script>
