<?php

/**
 * 예약 번역 - 한국어
 * admin.php의 'reservations' 섹션에서 분리
 */

return [
    'title' => '예약 관리', 'list' => '예약 목록', 'calendar' => '캘린더 보기',
    'statistics' => '통계', 'create' => '예약 추가', 'edit' => '예약 수정', 'detail' => '예약 상세',
    'filter' => [
        'all' => '전체', 'today' => '오늘', 'pending' => '대기중', 'confirmed' => '확정',
    ],
    'actions' => [
        'confirm' => '확정', 'cancel' => '취소', 'complete' => '완료',
        'no_show' => '노쇼', 'edit' => '수정', 'delete' => '삭제',
    ],
    'confirm_msg' => '예약을 확정하시겠습니까?', 'cancel_msg' => '예약을 취소하시겠습니까?',
    'complete_msg' => '예약을 완료 처리하시겠습니까?', 'noshow_msg' => '노쇼로 처리하시겠습니까?',
    'success' => [
        'created' => '예약이 생성되었습니다.', 'updated' => '예약이 수정되었습니다.',
        'confirmed' => '예약이 확정되었습니다.', 'cancelled' => '예약이 취소되었습니다.',
        'completed' => '예약이 완료 처리되었습니다.',
    ],
];
