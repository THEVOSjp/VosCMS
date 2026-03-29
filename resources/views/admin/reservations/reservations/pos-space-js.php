<script>
/**
 * POS 공간(테이블/룸) 중심 JS 확장
 * SpaceBasedAdapter가 로드하는 추가 JS
 */
Object.assign(POS, {

    // ─── 공간 배정 (빈 테이블/룸 클릭) ───
    assignToSpace(spaceData) {
        console.log('[POS:Space] Assign to space:', spaceData.space_name);
        // 접수 모달 열기 (space_id 포함)
        this._assignSpaceId = spaceData.space_id;
        this.openCheckinModal();
        // 모달 열린 후 space_id 숨김 필드 설정
        setTimeout(() => {
            const spaceField = document.getElementById('posCheckinSpaceId');
            if (spaceField) spaceField.value = spaceData.space_id;
        }, 100);
    },

    // ─── 공간 상세 보기 (사용중/예약됨 테이블 클릭) ───
    showSpaceDetail(spaceData) {
        console.log('[POS:Space] Show space detail:', spaceData.space_name);
        // 고객 서비스 모달 재사용 (첫 번째 고객 기준)
        if (spaceData.id) {
            this.showServices({
                customer_name: spaceData.customer_name || spaceData.space_name,
                customer_phone: spaceData.customer_phone || '',
                customer_email: spaceData.customer_email || '',
                reservation_date: spaceData.reservation_date || new Date().toISOString().slice(0, 10),
                source: spaceData.source || 'walk_in',
                reservation_ids: spaceData.reservation_ids || [],
            });
        }
    },

    // ─── 공간 비우기 (완료 후 리셋) ───
    async clearSpace(spaceData) {
        if (!confirm('<?= __('reservations.pos_space_clear_confirm') ?>')) return;
        console.log('[POS:Space] Clear space:', spaceData.space_name);
        const ids = spaceData.reservation_ids || [];
        for (const id of ids) {
            try {
                await fetch(`${this.adminUrl}/reservations/${id}/complete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: `_token=${encodeURIComponent(this.csrfToken)}`
                });
            } catch (e) { console.error('[POS:Space] Clear error:', id, e); }
        }
        location.reload();
    },
});

console.log('[POS:Space] Space mode JS loaded');
</script>
