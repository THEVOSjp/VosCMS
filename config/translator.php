<?php

/**
 * 번역 엔진 설정.
 *
 * driver 종류:
 *   - null   : 폴백. 원문 그대로 반환 (기본)
 *   - gemma  : Gemma 4 AI 연동 (ai.21ces.com) — GPU 준비 후 활성화 예정
 *
 * Phase 1 (현재) — GPU 없음, 수동 업로드 중심.
 * Phase 2 (예정) — driver 를 'gemma' 로 변경 + endpoint 설정 → 관리자 UI "번역" 버튼 활성화.
 */
return [
    'driver' => 'null',

    // gemma 드라이버용 설정 (Phase 2 에서 사용)
    'gemma' => [
        'endpoint' => 'https://ai.21ces.com/translate',
        'timeout'  => 30,
        'model'    => 'gemma-4',
    ],
];
