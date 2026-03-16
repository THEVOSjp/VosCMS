<?php

/**
 * 서비스/카테고리/시간대 번역 - 한국어
 * admin.php의 services, categories, time_slots 섹션에서 분리
 */

return [
    'title' => '서비스 관리', 'list' => '서비스 목록', 'create' => '서비스 추가',
    'edit' => '서비스 수정', 'detail' => '서비스 상세',
    'fields' => [
        'name' => '서비스명', 'slug' => 'URL 슬러그', 'description' => '설명',
        'short_description' => '짧은 설명', 'duration' => '소요시간 (분)', 'price' => '가격',
        'category' => '카테고리', 'is_active' => '활성 상태', 'max_capacity' => '최대 수용 인원',
        'buffer_time' => '버퍼 시간 (분)', 'advance_booking_days' => '예약 가능 기간 (일)',
        'min_notice_hours' => '최소 사전 알림 (시간)',
        'image' => '서비스 이미지',
    ],
    'success' => [
        'created' => '서비스가 생성되었습니다.', 'updated' => '서비스가 수정되었습니다.',
        'deleted' => '서비스가 삭제되었습니다.', 'activated' => '서비스가 활성화되었습니다.',
        'deactivated' => '서비스가 비활성화되었습니다.',
    ],
    'error' => [
        'has_reservations' => '예약이 있는 서비스는 삭제할 수 없습니다.',
        'server_error' => '서버 오류가 발생했습니다.', 'generic' => '오류가 발생했습니다.',
        'delete_failed' => '삭제 실패',
    ],
    'empty' => '등록된 서비스가 없습니다.', 'filter_all' => '전체', 'filter_active' => '활성',
    'filter_inactive' => '비활성', 'status_active' => '활성', 'status_inactive' => '비활성',
    'confirm_delete' => '이 서비스를 삭제하시겠습니까?', 'actions' => '작업', 'minute' => '분',
    'placeholder_name' => '예: 커트, 염색, 네일아트...',
    'placeholder_slug' => '자동 생성 (영문, 숫자, 하이픈)',
    'placeholder_description' => '서비스에 대한 간단한 설명...',
    'select_none' => '-- 선택 안함 --', 'no_buffer' => '없음',
    'image_upload_hint' => '클릭 또는 드래그하여 이미지 업로드',
    'image_formats' => 'JPG, PNG, WebP, GIF (최대 5MB)',
    'image_size' => '이미지 크기',
    'image_too_large' => '이미지 파일이 너무 큽니다. (최대 5MB)',
    'settings' => [
        'tabs' => ['general' => '기본설정', 'holidays' => '공휴일 관리'],
        'general' => [
            'title' => '서비스 기본설정', 'description' => '서비스 예약의 기본 옵션을 설정합니다.',
            'saved' => '설정이 저장되었습니다.', 'default_duration' => '기본 소요시간',
            'default_buffer' => '기본 버퍼 시간', 'advance_booking_days' => '예약 가능 기간',
            'same_day_booking' => '당일 예약 허용',
            'same_day_booking_hint' => '고객이 당일 온라인 예약을 할 수 있도록 허용합니다.',
            'min_notice_hours' => '최소 사전 예약 마감',
            'min_notice_hours_hint' => '예약 시작 시각 기준 최소 N시간 전까지만 온라인 예약이 가능합니다.',
            'max_capacity' => '기본 최대 수용 인원',
            'currency' => '통화 단위', 'price_display' => '가격 표시',
            'price_show' => '표시', 'price_hide' => '숨김', 'price_contact' => '문의',
            'days' => '일', 'hours' => '시간',
            'member_benefits_title' => '회원 할인/적립 설정',
            'member_benefits_description' => '회원 등급별 할인율과 적립금 적용 여부를 설정합니다.',
            'discount_enabled' => '회원 등급별 할인율 적용',
            'discount_enabled_hint' => '활성화 시 회원 등급에 설정된 할인율이 서비스 금액에 자동 적용됩니다.',
            'points_enabled' => '회원 등급별 적립금 적용',
            'points_enabled_hint' => '활성화 시 결제 금액에 대해 회원 등급별 적립율로 포인트가 적립됩니다.',
            'points_name' => '적립금 명칭',
            'points_name_placeholder' => '예: 포인트, 마일리지, 적립금',
            'points_name_hint' => '고객에게 표시되는 적립금의 명칭을 설정합니다. 비워두면 기본값(적립금)이 사용됩니다.',
            'deposit_title' => '예약금 설정',
            'deposit_description' => '고객 예약 시 예약금(선결제) 관련 옵션을 설정합니다.',
            'deposit_enabled' => '예약금 사용',
            'deposit_type' => '예약금 타입',
            'deposit_type_fixed' => '고정 금액',
            'deposit_type_percent' => '서비스 가격 비율',
            'deposit_amount' => '예약금 금액',
            'deposit_percent' => '예약금 비율',
            'deposit_percent_hint' => '서비스 가격의 일정 비율을 예약금으로 설정합니다.',
            'deposit_refund_hours' => '환불 가능 기한',
            'deposit_refund_hint' => '예약 시간 기준 N시간 전까지 취소 시 전액 환불됩니다.',
        ],
        'holidays' => [
            'add_title' => '공휴일 추가', 'placeholder_title' => '예: 설날, 추석...',
            'repeat_yearly' => '매년 반복', 'yearly' => '매년',
            'name' => '공휴일명', 'date' => '날짜', 'status' => '상태',
            'empty' => '등록된 공휴일이 없습니다.', 'required' => '공휴일명과 날짜를 입력해주세요.',
            'created' => '공휴일이 등록되었습니다.', 'deleted' => '공휴일이 삭제되었습니다.',
            'confirm_delete' => '이 공휴일을 삭제하시겠습니까?',
        ],
    ],

    // 카테고리 관리
    'categories' => [
        'title' => '카테고리 관리', 'list' => '카테고리 목록', 'create' => '카테고리 추가',
        'edit' => '카테고리 수정', 'delete' => '삭제',
        'fields' => [
            'name' => '카테고리명', 'slug' => 'URL 슬러그', 'description' => '설명',
            'parent' => '상위 카테고리', 'sort_order' => '정렬 순서', 'is_active' => '활성 상태',
        ],
        'success' => [
            'created' => '카테고리가 생성되었습니다.', 'updated' => '카테고리가 수정되었습니다.',
            'deleted' => '카테고리가 삭제되었습니다.', 'reordered' => '카테고리 순서가 변경되었습니다.',
        ],
        'error' => ['has_services' => '이 카테고리에 서비스가 존재합니다. 먼저 서비스를 이동하거나 삭제하세요.'],
        'empty' => '등록된 카테고리가 없습니다.', 'confirm_delete' => '이 카테고리를 삭제하시겠습니까?',
        'parent_none' => '-- 없음 (최상위) --',
        'multilang_save_first' => '카테고리를 먼저 저장한 후 다국어 입력이 가능합니다.',
        'placeholder_name' => '예: 헤어, 네일, 마사지...',
        'placeholder_slug' => '자동 생성', 'placeholder_description' => '카테고리 설명...',
    ],

    // 시간대 관리
    'time_slots' => [
        'title' => '시간대 관리', 'default_slots' => '기본 시간대', 'blocked_dates' => '차단된 날짜',
        'fields' => [
            'day_of_week' => '요일', 'start_time' => '시작 시간', 'end_time' => '종료 시간',
            'max_bookings' => '최대 예약 수', 'service' => '서비스', 'specific_date' => '특정 날짜',
        ],
        'block_date' => '날짜 차단', 'unblock_date' => '차단 해제',
    ],
];
