<?php

/**
 * 인증 관련 번역 - 한국어
 */

return [
    // 로그인
    'login' => [
        'title' => '로그인',
        'description' => '계정에 로그인하여 예약을 관리하세요',
        'email' => '이메일',
        'email_placeholder' => 'example@email.com',
        'password' => '비밀번호',
        'password_placeholder' => '••••••••',
        'remember' => '로그인 상태 유지',
        'forgot' => '비밀번호 찾기',
        'submit' => '로그인',
        'no_account' => '계정이 없으신가요?',
        'register_link' => '회원가입',
        'back_home' => '← 홈으로 돌아가기',
        'success' => '로그인되었습니다.',
        'failed' => '이메일 또는 비밀번호가 일치하지 않습니다.',
        'required' => '이메일과 비밀번호를 입력해주세요.',
        'error' => '로그인 처리 중 오류가 발생했습니다.',
        'social_only' => '소셜 로그인으로 가입한 계정입니다. 소셜 로그인을 이용해주세요.',
    ],

    // 회원가입
    'admin_login' => [
        'title' => '관리자 로그인',
        'subtitle' => '관리자 계정으로 로그인하세요',
        'errors' => [
            'account_inactive' => '비활성화된 계정입니다. 관리자에게 문의하세요.',
            'not_admin' => '관리자 권한이 없는 계정입니다.',
            'user_inactive' => '연동된 회원 계정이 비활성 상태입니다.',
            'staff_inactive' => '연동된 스태프 계정이 비활성 상태입니다.',
            'login_failed' => '로그인에 실패했습니다.',
        ],
    ],

    'register' => [
        'title' => '회원가입',
        'description' => 'RezlyX와 함께 예약을 시작하세요',
        'name' => '이름',
        'name_placeholder' => '홍길동',
        'email' => '이메일',
        'email_placeholder' => 'example@email.com',
        'phone' => '전화번호',
        'phone_placeholder' => '010-1234-5678',
        'phone_hint' => '국가코드를 선택하고 전화번호를 입력해주세요',
        'password' => '비밀번호',
        'password_placeholder' => '12자 이상 입력',
        'password_hint' => '12자 이상, 대문자, 소문자, 숫자, 특수문자 포함',
        'password_confirm' => '비밀번호 확인',
        'password_confirm_placeholder' => '비밀번호 재입력',
        'agree_terms' => '에 동의합니다',
        'agree_privacy' => '에 동의합니다',
        'submit' => '회원가입',
        'has_account' => '이미 계정이 있으신가요?',
        'login_link' => '로그인',
        'success' => '회원가입이 완료되었습니다.',
        'success_login' => '로그인하러 가기',
        'email_exists' => '이미 등록된 이메일입니다.',
        'error' => '회원가입 처리 중 오류가 발생했습니다.',
    ],

    // 비밀번호 찾기
    'forgot' => [
        'title' => '비밀번호 찾기',
        'description' => '가입한 이메일 주소를 입력하시면 비밀번호 재설정 링크를 보내드립니다.',
        'email' => '이메일',
        'submit' => '재설정 링크 보내기',
        'back_login' => '로그인으로 돌아가기',
        'success' => '비밀번호 재설정 링크를 이메일로 보냈습니다.',
        'not_found' => '등록되지 않은 이메일입니다.',
    ],

    // 비밀번호 재설정
    'reset' => [
        'title' => '비밀번호 재설정',
        'email' => '이메일',
        'password' => '새 비밀번호',
        'password_confirm' => '새 비밀번호 확인',
        'submit' => '비밀번호 재설정',
        'success' => '비밀번호가 재설정되었습니다.',
        'invalid_token' => '유효하지 않은 토큰입니다.',
        'expired_token' => '토큰이 만료되었습니다.',
    ],

    // 로그아웃
    'logout' => [
        'success' => '로그아웃되었습니다.',
    ],

    // 이메일 인증
    'verify' => [
        'title' => '이메일 인증',
        'description' => '가입한 이메일로 인증 메일을 보냈습니다. 이메일을 확인해주세요.',
        'resend' => '인증 메일 재발송',
        'success' => '이메일이 인증되었습니다.',
        'already_verified' => '이미 인증된 이메일입니다.',
    ],

    // 소셜 로그인
    'social' => [
        'or' => '또는',
        'google' => 'Google로 로그인',
        'kakao' => '카카오로 로그인',
        'naver' => '네이버로 로그인',
        'line' => 'LINE으로 로그인',
    ],

    // 소셜 로그인 버튼 (단축키)
    'login_with_line' => 'LINE으로 로그인',
    'login_with_google' => 'Google로 로그인',
    'login_with_kakao' => '카카오로 로그인',
    'login_with_naver' => '네이버로 로그인',
    'login_with_apple' => 'Apple로 로그인',
    'login_with_facebook' => 'Facebook으로 로그인',
    'or_continue_with' => '또는',

    // 약관 동의
    'terms' => [
        'title' => '약관 동의',
        'subtitle' => '서비스 이용을 위해 약관에 동의해 주세요',
        'agree_all' => '전체 약관에 모두 동의합니다',
        'required' => '필수',
        'optional' => '선택',
        'required_mark' => '필수',
        'required_note' => '* 표시는 필수 동의 항목입니다',
        'required_alert' => '필수 약관에 모두 동의해 주세요.',
        'notice' => '위 약관에 동의하지 않으시면 서비스 이용이 제한될 수 있습니다.',
        'view_content' => '내용 보기',
        'hide_content' => '내용 접기',
        'translation_pending' => '번역 준비 중',
    ],

    // 마이페이지
    'mypage' => [
        'title' => '마이페이지',
        'welcome' => ':name님, 안녕하세요!',
        'member_since' => ':date 가입',
        'menu' => [
            'dashboard' => '대시보드',
            'reservations' => '예약 내역',
            'profile' => '프로필',
            'services' => '서비스 관리',
            'settings' => '설정',
            'password' => '비밀번호 변경',
            'messages' => '메시지',
            'withdraw' => '회원 탈퇴',
            'logout' => '로그아웃',
        ],
        'stats' => [
            'total_reservations' => '총 예약',
            'upcoming' => '예정된 예약',
            'completed' => '완료된 예약',
            'cancelled' => '취소된 예약',
        ],
        'recent_reservations' => '최근 예약',
        'no_reservations' => '예약 내역이 없습니다.',
        'view_all' => '전체 보기',
        'quick_actions' => '빠른 메뉴',
        'make_reservation' => '새 예약하기',
        'messages' => [
            'title' => '메시지',
            'total' => '총 :count개의 메시지',
            'unread' => ':count개 읽지 않음',
            'empty' => '받은 메시지가 없습니다.',
            'not_available' => '메시지 기능을 사용할 수 없습니다.',
            'mark_read' => '읽음으로 표시',
            'mark_all_read' => '모두 읽음 표시',
            'delete' => '삭제',
            'delete_confirm' => '이 메시지를 삭제하시겠습니까?',
            'view_detail' => '자세히 보기',
        ],
    ],

    // 프로필
    'profile' => [
        'title' => '프로필',
        'description' => '내 프로필 정보입니다.',
        'edit_title' => '프로필 수정',
        'edit_description' => '개인 정보를 수정합니다.',
        'edit_button' => '수정',
        'name' => '이름',
        'email' => '이메일',
        'email_hint' => '이메일은 변경할 수 없습니다.',
        'phone' => '전화번호',
        'not_set' => '미설정',
        'submit' => '저장',
        'success' => '프로필이 수정되었습니다.',
        'error' => '프로필 수정 중 오류가 발생했습니다.',
    ],

    // 개인정보 설정
    'settings' => [
        'title' => '개인정보 설정',
        'description' => '다른 사용자에게 표시할 정보를 선택합니다.',
        'info' => '비활성화한 항목은 다른 사용자에게 표시되지 않습니다. 이름은 항상 표시됩니다.',
        'success' => '설정이 저장되었습니다.',
        'error' => '설정 저장 중 오류가 발생했습니다.',
        'no_fields' => '설정 가능한 항목이 없습니다.',
        'fields' => [
            'email' => '이메일',
            'email_desc' => '이메일 주소를 다른 사용자에게 표시합니다.',
            'profile_photo' => '프로필 사진',
            'profile_photo_desc' => '프로필 사진을 다른 사용자에게 표시합니다.',
            'phone' => '전화번호',
            'phone_desc' => '전화번호를 다른 사용자에게 표시합니다.',
            'birth_date' => '생년월일',
            'birth_date_desc' => '생년월일을 다른 사용자에게 표시합니다.',
            'gender' => '성별',
            'gender_desc' => '성별 정보를 다른 사용자에게 표시합니다.',
            'company' => '회사',
            'company_desc' => '회사 정보를 다른 사용자에게 표시합니다.',
            'blog' => '블로그',
            'blog_desc' => '블로그 주소를 다른 사용자에게 표시합니다.',
        ],
    ],

    // 비밀번호 변경
    'password_change' => [
        'title' => '비밀번호 변경',
        'description' => '보안을 위해 정기적으로 비밀번호를 변경해주세요.',
        'current' => '현재 비밀번호',
        'current_placeholder' => '현재 비밀번호 입력',
        'new' => '새 비밀번호',
        'new_placeholder' => '새 비밀번호 입력',
        'confirm' => '새 비밀번호 확인',
        'confirm_placeholder' => '새 비밀번호 재입력',
        'submit' => '비밀번호 변경',
        'success' => '비밀번호가 변경되었습니다.',
        'error' => '비밀번호 변경 중 오류가 발생했습니다.',
        'wrong_password' => '현재 비밀번호가 일치하지 않습니다.',
    ],

    // 예약 조회
    'reservations' => [
        'title' => '예약 조회',
        'description' => '나의 예약 내역을 확인하고 관리합니다.',
        'no_reservations' => '예약 내역이 없습니다.',
        'make_reservation' => '새 예약하기',
        'filter' => [
            'all' => '전체',
            'upcoming' => '예정',
            'past' => '지난 예약',
        ],
        'status' => [
            'pending' => '대기중',
            'confirmed' => '확정',
            'cancelled' => '취소됨',
            'completed' => '완료',
            'no_show' => '노쇼',
        ],
        'payment_status' => [
            'pending' => '결제 대기',
            'paid' => '결제 완료',
            'refunded' => '환불 완료',
            'partial' => '부분 결제',
        ],
        'booking_code' => '예약번호',
        'service' => '서비스',
        'date' => '예약일',
        'time' => '예약시간',
        'guests' => '인원',
        'price' => '금액',
        'actions' => '관리',
        'view_detail' => '상세보기',
        'cancel' => '예약 취소',
        'cancel_confirm' => '정말 이 예약을 취소하시겠습니까?',
        'cancel_success' => '예약이 취소되었습니다.',
        'cancel_error' => '예약 취소에 실패했습니다.',
        'cannot_cancel' => '이 예약은 취소할 수 없습니다.',
        'detail' => [
            'title' => '예약 상세',
            'customer_info' => '예약자 정보',
            'reservation_info' => '예약 정보',
            'payment_info' => '결제 정보',
            'notes' => '메모',
            'back' => '목록으로',
        ],
        'guest_unit' => '명',
    ],

    // 회원 탈퇴
    'withdraw' => [
        'title' => '회원 탈퇴',
        'description' => '탈퇴 시 개인정보는 즉시 익명화 처리되며, 탈퇴 후에는 계정을 복구할 수 없습니다.',
        'warning_title' => '탈퇴 전 반드시 확인해 주세요',
        'warnings' => [
            'account' => '이름, 이메일, 전화번호, 생년월일, 프로필 사진 등 모든 개인정보가 즉시 익명화됩니다. 더 이상 본인 식별이 불가능합니다.',
            'reservation' => '진행 중이거나 예정된 예약이 있는 경우, 반드시 탈퇴 전에 취소해 주세요. 탈퇴 후에는 예약 변경·취소가 불가능합니다.',
            'payment' => '결제 및 매출 관련 기록은 관련 세법(한국 국세기본법 5년, 일본 法人税法 7년)에 따라 익명화된 상태로 법정 보관 기간 동안 유지됩니다.',
            'recovery' => '탈퇴 처리된 계정은 복구할 수 없습니다. 동일한 이메일로 재가입은 가능하지만, 이전 예약 내역·적립금·메시지 등 기존 데이터는 일체 복원되지 않습니다.',
            'social' => '소셜 로그인(Google, 카카오, LINE 등)으로 가입한 경우, 해당 소셜 서비스에서의 연결도 해제됩니다.',
            'message' => '수신한 메시지, 알림 내역은 모두 삭제되며 확인할 수 없습니다.',
        ],
        'retention_notice' => '※ 관련 법령에 의해 보관이 필요한 거래 기록은 개인 식별이 불가능한 형태로 법정 기간 동안 보관 후 완전 삭제됩니다.',
        'reason' => '탈퇴 사유',
        'reason_placeholder' => '탈퇴 사유를 선택해 주세요',
        'reasons' => [
            'not_using' => '서비스를 더 이상 이용하지 않음',
            'other_service' => '다른 서비스로 이동',
            'dissatisfied' => '서비스에 불만족',
            'privacy' => '개인정보 보호 우려',
            'too_many_emails' => '이메일/알림이 너무 많음',
            'other' => '기타',
        ],
        'reason_other' => '기타 사유',
        'reason_other_placeholder' => '탈퇴 사유를 입력해 주세요',
        'password' => '비밀번호 확인',
        'password_placeholder' => '현재 비밀번호 입력',
        'password_hint' => '본인 확인을 위해 현재 비밀번호를 입력해 주세요.',
        'confirm_text' => '위 안내 사항을 모두 확인하였으며, 개인정보 익명화 및 회원 탈퇴에 동의합니다.',
        'submit' => '회원 탈퇴',
        'success' => '회원 탈퇴가 완료되었습니다. 그동안 이용해 주셔서 감사합니다.',
        'wrong_password' => '비밀번호가 일치하지 않습니다.',
        'error' => '탈퇴 처리 중 오류가 발생했습니다.',
        'confirm_required' => '탈퇴 동의를 체크해 주세요.',
    ],
];
