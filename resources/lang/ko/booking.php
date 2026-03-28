<?php

/**
 * 예약 관련 번역 - 한국어
 */

return [
    // 페이지 제목
    'title' => '예약하기',
    'service_list' => '서비스 목록',
    'select_service' => '서비스를 선택해주세요',
    'select_date' => '날짜 선택',
    'select_time' => '시간 선택',
    'enter_info' => '예약자 정보를 입력해주세요',
    'confirm_booking' => '예약 확인',
    'confirm_info' => '예약 정보를 확인해주세요',
    'complete_booking' => '예약 완료하기',
    'select_service_datetime' => '원하시는 서비스와 일시를 선택해주세요',
    'staff_designation_guide' => '스태프 지명 예약은 스태프 페이지에서 진행해주세요',
    'go_staff_booking' => '스태프 지명 예약',
    'select_datetime' => '날짜와 시간을 선택해주세요',
    'no_services' => '현재 등록된 서비스가 없습니다.',
    'contact_admin' => '관리자에게 문의해주세요.',
    'notes' => '요청사항',
    'notes_placeholder' => '요청사항이 있으면 입력해주세요',
    'customer' => '예약자',
    'phone' => '연락처',
    'date_label' => '날짜',
    'time_label' => '시간',
    'total_price' => '결제 금액',
    'cancel_policy' => '예약 확정 후 예약 시간 24시간 전까지 취소 가능합니다. 이후 취소 시 취소 수수료가 부과될 수 있습니다.',
    'success' => '예약이 완료되었습니다!',
    'success_desc' => '예약 확인 문자가 발송됩니다. 아래 예약번호를 보관해주세요.',
    'submitting' => '처리 중...',
    'select_staff' => '스태프를 선택해주세요',
    'no_preference' => '지정 안함',
    'staff' => '담당 스태프',
    'designation_fee' => '지명비',
    'designation_fee_badge' => '+:amount',
    'loading_slots' => '가능한 시간을 확인하는 중...',
    'no_available_slots' => '선택한 날짜에 예약 가능한 시간이 없습니다.',
    'items_selected' => '개 선택',
    'total_duration' => '총 소요시간',

    // 단계
    'step' => [
        'service' => '서비스 선택',
        'datetime' => '날짜/시간',
        'info' => '정보 입력',
        'confirm' => '확인',
    ],

    // 서비스
    'service' => [
        'title' => '서비스',
        'name' => '서비스명',
        'description' => '설명',
        'duration' => '소요시간',
        'price' => '가격',
        'category' => '카테고리',
        'select' => '선택하기',
        'view_detail' => '상세보기',
        'no_services' => '이용 가능한 서비스가 없습니다.',
    ],

    // 날짜/시간
    'date' => [
        'title' => '예약 날짜',
        'select_date' => '날짜를 선택해주세요',
        'available' => '예약 가능',
        'unavailable' => '예약 불가',
        'fully_booked' => '예약 마감',
        'past_date' => '지난 날짜',
    ],

    'time' => [
        'title' => '예약 시간',
        'select_time' => '시간을 선택해주세요',
        'available_slots' => '예약 가능 시간',
        'no_slots' => '예약 가능한 시간이 없습니다.',
        'remaining' => ':count자리 남음',
    ],

    // 예약 폼
    'form' => [
        'customer_name' => '예약자명',
        'customer_email' => '이메일',
        'customer_phone' => '전화번호',
        'guests' => '인원',
        'notes' => '요청사항',
        'notes_placeholder' => '요청사항이 있으시면 입력해주세요',
    ],

    // 예약 확인
    'confirm' => [
        'title' => '예약 확인',
        'summary' => '예약 정보 확인',
        'service_info' => '서비스 정보',
        'booking_info' => '예약 정보',
        'customer_info' => '예약자 정보',
        'total_price' => '총 금액',
        'agree_terms' => '예약 약관에 동의합니다',
        'submit' => '예약하기',
    ],

    // 예약 완료
    'complete' => [
        'title' => '예약 완료',
        'success' => '예약이 완료되었습니다!',
        'booking_code' => '예약번호',
        'check_email' => '입력하신 이메일로 예약 확인서가 발송되었습니다.',
        'view_detail' => '예약 상세보기',
        'book_another' => '다른 예약하기',
    ],

    // 예약 조회
    'lookup' => [
        'title' => '예약 조회',
        'description' => '예약 정보를 입력하여 예약을 조회하세요.',
        'booking_code' => '예약번호',
        'booking_code_placeholder' => 'RZ250301XXXXXX',
        'email' => '이메일',
        'email_placeholder' => '예약 시 입력한 이메일',
        'phone' => '전화번호',
        'phone_placeholder' => '예약 시 입력한 전화번호',
        'search' => '조회하기',
        'search_method' => '조회 방법',
        'by_code' => '예약번호로 조회',
        'by_email' => '이메일로 조회',
        'by_phone' => '전화번호로 조회',
        'not_found' => '예약을 찾을 수 없습니다. 입력 정보를 확인해주세요.',
        'input_required' => '예약번호와 이메일 또는 전화번호를 입력해주세요.',
        'result_title' => '조회 결과',
        'multiple_results' => ':count개의 예약을 찾았습니다.',
        'hint' => '예약번호와 이메일 또는 전화번호를 함께 입력하시면 정확한 조회가 가능합니다.',
        'help_text' => '예약을 찾을 수 없나요?',
        'contact_support' => '고객센터에 문의하기',
    ],

    // 예약 상세
    'detail' => [
        'title' => '예약 상세',
        'status' => '예약 상태',
        'booking_date' => '예약 일시',
        'service' => '서비스',
        'services' => '포함 서비스',
        'bundle' => '번들 상품',
        'guests' => '인원',
        'total_price' => '결제 금액',
        'payment_status' => '결제 상태',
        'notes' => '요청사항',
        'created_at' => '예약 일시',
        'staff' => '담당 스태프',
        'designation_fee' => '지명비',
        'payment' => '결제 정보',
        'total' => '합계',
        'discount' => '할인',
        'points_used' => '적립금 사용',
        'final_amount' => '최종 결제금액',
        'customer_info' => '예약자 정보',
        'name' => '이름',
        'email' => '이메일',
        'phone' => '연락처',
        'source' => '예약 경로',
        'cancel_info' => '취소 정보',
        'cancelled_at' => '취소일시',
        'cancel_reason' => '취소 사유',
        'back_to_lookup' => '예약 조회',
        'duration_unit' => '분',
    ],
    // 결제 상태
    'payment' => [
        'unpaid' => '미결제',
        'paid' => '결제완료',
        'partial' => '부분결제',
        'refunded' => '환불',
    ],

    // 예약 취소
    'cancel' => [
        'title' => '예약 취소',
        'confirm' => '정말 예약을 취소하시겠습니까?',
        'reason' => '취소 사유',
        'reason_placeholder' => '취소 사유를 입력해주세요',
        'submit' => '예약 취소',
        'success' => '예약이 취소되었습니다.',
        'cannot_cancel' => '이 예약은 취소할 수 없습니다.',
    ],

    // 상태 메시지
    'status' => [
        'pending' => '예약이 접수되었습니다. 확정을 기다려주세요.',
        'confirmed' => '예약이 확정되었습니다.',
        'cancelled' => '예약이 취소되었습니다.',
        'completed' => '이용이 완료되었습니다.',
        'no_show' => '노쇼로 처리되었습니다.',
    ],

    // 에러 메시지
    'error' => [
        'service_not_found' => '서비스를 찾을 수 없습니다.',
        'slot_unavailable' => '선택한 시간은 예약이 불가능합니다.',
        'past_date' => '과거 날짜는 예약할 수 없습니다.',
        'max_capacity' => '예약 가능 인원을 초과했습니다.',
        'booking_failed' => '예약 처리 중 오류가 발생했습니다.',
        'required_fields' => '이름과 연락처를 입력해주세요.',
        'invalid_service' => '유효하지 않은 서비스입니다.',
    ],

    // 적립금/할인
    'member_discount' => '회원 할인',
    'use_points' => '적립금 사용',
    'points_balance' => '보유',
    'use_all' => '전액 사용',
    'points_default_name' => '적립금',
    'deposit_pay_now' => '예약금 (결제 금액)',
    'deposit_remaining_later' => '잔액은 서비스 이용 시 결제됩니다',
    'next' => '다음',
    'categories' => '개 카테고리',
    'service_count' => '개 서비스',
    'expected_points' => '예상 적립',
    'reservation_complete' => '예약이 완료되었습니다',
    'reservation_complete_desc' => '예약 내용을 확인해주세요',
    'reservation_number' => '예약번호',
    'check_summary' => '예약 내용 확인',
];
