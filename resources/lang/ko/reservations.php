<?php

/**
 * 예약 번역 - 한국어
 * admin.php의 'reservations' 섹션에서 분리
 */

return [
    'title' => '예약 관리', 'list' => '예약 목록', 'calendar' => '캘린더 보기',
    'statistics' => '통계', 'create' => '예약 추가', 'edit' => '예약 수정', 'detail' => '상세',
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
    // POS
    'pos' => 'POS',
    'pos_in_service' => '이용중',
    'pos_waiting' => '대기',
    'pos_done' => '완료',
    'pos_total_count' => '전체',
    'pos_refresh' => '새로고침',
    'pos_no_in_service' => '현재 이용중인 고객이 없습니다',
    'pos_min' => '분',
    'pos_remaining' => '남음',
    'pos_service' => '서비스',
    'pos_customer_name' => '고객명',
    'pos_phone' => '연락처',
    'pos_start_time' => '시간',
    'pos_today_list' => '예약자 리스트',
    'pos_no_reservations' => '오늘 예약이 없습니다.',
    'pos_tab_checkin' => '당일 접수',
    'pos_tab_reservations' => '예약자 리스트',
    'pos_tab_waiting' => '대기자 명단',
    'pos_checkin_name' => '고객명',
    'pos_checkin_phone' => '연락처',
    'pos_checkin_service' => '서비스 선택',
    'pos_checkin_submit' => '접수',
    'pos_checkin_placeholder_name' => '이름',
    'pos_checkin_placeholder_phone' => '전화번호',
    'pos_no_waiting' => '대기중인 고객이 없습니다.',
    'pos_waiting_number' => '대기번호',
    'pos_walk_in' => '워크인',
    // POS 카드 버튼
    'pos_btn_confirm' => '확정',
    'pos_btn_start' => '진행',
    'pos_btn_payment' => '결제',
    'pos_btn_complete' => '완료',
    'pos_overtime' => '초과',
    'pos_scheduled' => '예정',
    // POS 결제 상태
    'pos_paid' => '결제완료',
    'pos_partial_paid' => '부분결제',
    // POS 결제 모달
    'pos_pay_total' => '총 금액',
    'pos_pay_paid' => '결제 완료',
    'pos_pay_remaining' => '잔액',
    'pos_pay_amount' => '결제 금액',
    'pos_pay_method' => '결제 방법',
    'pos_pay_card' => '카드',
    'pos_pay_cash' => '현금',
    'pos_pay_transfer' => '이체',
    'pos_pay_submit' => '결제 처리',
    'pos_service_detail' => '서비스 내역',
    'pos_add_service' => '서비스 추가',
    'pos_add_service_submit' => '선택한 서비스 추가',
    'pos_no_services' => '등록된 서비스가 없습니다',
    'pos_service_count' => '건',

    // 공간 중심 POS (레스토랑, 숙박 등)
    'pos_space_available' => '비어있음',
    'pos_space_reserved' => '예약됨',
    'pos_space_occupied' => '사용중',
    'pos_space_cleaning' => '정리중',
    'pos_space_assign' => '배정',
    'pos_space_clear_confirm' => '이 공간을 비우시겠습니까? 모든 서비스가 완료 처리됩니다.',
    'pos_space_capacity_unit' => '인',
];
