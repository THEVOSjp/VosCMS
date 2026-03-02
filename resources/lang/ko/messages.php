<?php
/**
 * Korean Language File
 */

return [
    // Common
    'app_name' => 'RezlyX',
    'welcome' => '환영합니다',
    'home' => '홈',
    'back' => '뒤로',
    'next' => '다음',
    'cancel' => '취소',
    'confirm' => '확인',
    'save' => '저장',
    'delete' => '삭제',
    'edit' => '수정',
    'search' => '검색',
    'loading' => '로딩 중...',
    'no_data' => '데이터가 없습니다.',
    'error' => '오류가 발생했습니다.',
    'success' => '성공적으로 처리되었습니다.',

    // Auth
    'auth' => [
        'login' => '로그인',
        'logout' => '로그아웃',
        'register' => '회원가입',
        'email' => '이메일',
        'password' => '비밀번호',
        'password_confirm' => '비밀번호 확인',
        'remember_me' => '로그인 유지',
        'forgot_password' => '비밀번호 찾기',
        'reset_password' => '비밀번호 재설정',
        'invalid_credentials' => '이메일 또는 비밀번호가 올바르지 않습니다.',
        'account_inactive' => '비활성화된 계정입니다.',
    ],

    // Reservation
    'reservation' => [
        'title' => '예약',
        'new' => '새 예약',
        'my_reservations' => '내 예약',
        'select_service' => '서비스 선택',
        'select_date' => '날짜 선택',
        'select_time' => '시간 선택',
        'customer_info' => '예약자 정보',
        'payment' => '결제',
        'confirmation' => '예약 확인',
        'status' => [
            'pending' => '대기',
            'confirmed' => '확정',
            'completed' => '완료',
            'cancelled' => '취소됨',
            'no_show' => '노쇼',
        ],
    ],

    // Services
    'service' => [
        'title' => '서비스',
        'category' => '카테고리',
        'price' => '가격',
        'duration' => '소요시간',
        'description' => '설명',
        'options' => '옵션',
    ],

    // Member
    'member' => [
        'profile' => '내 정보',
        'points' => '적립금',
        'grade' => '회원 등급',
        'reservations' => '예약 내역',
        'payments' => '결제 내역',
        'settings' => '설정',
    ],

    // Payment
    'payment' => [
        'title' => '결제',
        'amount' => '결제 금액',
        'method' => '결제 수단',
        'card' => '신용카드',
        'bank_transfer' => '계좌이체',
        'virtual_account' => '가상계좌',
        'points' => '적립금',
        'use_points' => '적립금 사용',
        'available_points' => '사용 가능 적립금',
        'complete' => '결제 완료',
        'failed' => '결제 실패',
    ],

    // Time
    'time' => [
        'today' => '오늘',
        'tomorrow' => '내일',
        'minutes' => '분',
        'hours' => '시간',
        'days' => '일',
    ],

    // Validation
    'validation' => [
        'required' => ':attribute 항목은 필수입니다.',
        'email' => '유효한 이메일 주소를 입력해주세요.',
        'min' => ':attribute 항목은 최소 :min자 이상이어야 합니다.',
        'max' => ':attribute 항목은 최대 :max자까지 가능합니다.',
        'confirmed' => ':attribute 확인이 일치하지 않습니다.',
    ],
];
