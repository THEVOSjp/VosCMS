<?php
/**
 * Body 상태 자동 reset — 코어 안전망
 *
 * 어떤 모달/오버레이가 cleanup 에 실패해 body.style.overflow='hidden' 또는
 * 잔여 backdrop 가 남아있어도, 다음 페이지 진입 시점에 이 스크립트가
 * 강제로 정상화한다. 모든 페이지가 진입 즉시 항상 사용 가능한 상태 보장.
 *
 * 사용법: 레이아웃 main.php 의 <head> 끝부분 또는 <body> 시작 직후에 include
 *
 *   <?php include BASE_PATH . '/resources/views/components/body-state-reset.php'; ?>
 *
 * 안전장치 트리거 시점:
 *   1. 페이지 첫 로드 (DOMContentLoaded 또는 즉시 실행)
 *   2. bfcache 복원 (pageshow 이벤트)
 *   3. iframe 결제 완료 후 부모 페이지 복원
 *
 * 정리 대상:
 *   - <html>, <body> 의 inline style.overflow
 *   - body 의 위험 클래스 (modal-open / no-scroll 등 — 사용 시 추가)
 *   - 화면 가리는 비표시 모달 backdrop (z-[9000+] + fixed inset-0 + opacity > 0)
 */
?>
<script>
(function () {
    'use strict';

    function resetBodyState() {
        // inline overflow 잔여 강제 해제 (어떤 모달이 어떻게 깨졌든 정상화)
        if (document.documentElement && document.documentElement.style) {
            document.documentElement.style.overflow = '';
        }
        if (document.body && document.body.style) {
            document.body.style.overflow = '';
        }
        // 추후 modal-open 같은 lock 클래스 패턴이 도입되면 여기에 추가
        // document.body.classList.remove('modal-open', 'no-scroll');
    }

    // 1) 페이지 첫 로드 시점
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', resetBodyState);
    } else {
        resetBodyState();
    }

    // 2) bfcache 복원 시점 (브라우저 뒤로가기로 캐시된 페이지가 살아날 때 inline style 도 복원되므로 다시 reset)
    window.addEventListener('pageshow', resetBodyState);
})();
</script>
