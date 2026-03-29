<?php

/**
 * 마이페이지 번역 - 한국어
 */

return [
    // 메인
    'title' => '마이페이지',
    'welcome' => ':name님, 환영합니다!',

    // 네비게이션
    'nav' => [
        'dashboard' => '대시보드',
        'reservations' => '예약 내역',
        'profile' => '프로필',
        'password' => '비밀번호 변경',
        'logout' => '로그아웃',
    ],

    // 대시보드
    'dashboard' => [
        'upcoming' => '다가오는 예약',
        'recent' => '최근 예약',
        'no_upcoming' => '다가오는 예약이 없습니다.',
        'no_recent' => '최근 예약 내역이 없습니다.',
        'view_all' => '전체보기',
    ],

    // 예약 내역
    'reservations' => [
        'title' => '예약 내역',
        'filter' => [
            'all' => '전체',
            'pending' => '대기중',
            'confirmed' => '확정',
            'completed' => '완료',
            'cancelled' => '취소',
        ],
        'no_reservations' => '예약 내역이 없습니다.',
        'booking_code' => '예약번호',
        'service' => '서비스',
        'date' => '예약일',
        'status' => '상태',
        'actions' => '관리',
        'view' => '상세보기',
        'cancel' => '취소',
    ],

    // 프로필
    'profile' => [
        'title' => '프로필 설정',
        'info' => '기본 정보',
        'name' => '이름',
        'email' => '이메일',
        'phone' => '전화번호',
        'save' => '저장',
        'success' => '프로필이 수정되었습니다.',
    ],

    // 비밀번호
    'password' => [
        'title' => '비밀번호 변경',
        'current' => '현재 비밀번호',
        'new' => '새 비밀번호',
        'confirm' => '새 비밀번호 확인',
        'change' => '변경',
        'success' => '비밀번호가 변경되었습니다.',
        'mismatch' => '현재 비밀번호가 일치하지 않습니다.',
    ],

    // 통계
    'stats' => [
        'total_bookings' => '총 예약',
        'completed' => '완료',
        'cancelled' => '취소',
        'upcoming' => '예정',
    ],
];
