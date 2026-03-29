<?php

/**
 * 관리자 공통 번역 - 한국어
 *
 * 페이지별 번역은 별도 파일로 분리:
 * - settings.php: 설정 (사이트, 메일, 언어, SEO, PWA)
 * - system.php: 시스템 (정보, 캐시, 모드, 로그, 업데이트)
 * - services.php: 서비스, 카테고리, 시간대
 * - reservations.php: 예약
 * - members.php: 회원
 * - staff.php: 스태프
 * - site.php: 사이트 관리 (메뉴, 디자인, 페이지, 위젯)
 * - updater.php: 업데이터 메시지
 */

return [
    // 공통
    'title' => '관리자',
    'dashboard' => [
        'title' => '대시보드',
        'migration_required' => 'DB 마이그레이션 필요',
        'migration_desc' => '적용되지 않은 데이터베이스 패치가 있습니다. 아래 버튼을 클릭하여 실행해주세요.',
        'run_migration' => '마이그레이션 실행',
        'migration_applied' => '개 패치 적용 완료',
        'migration_complete' => '모든 마이그레이션이 완료되었습니다.',
        'retry_migration' => '재시도',
    ],
    'back_to_site' => '사이트로 돌아가기',
    'dark_mode' => '다크 모드 전환',

    // 네비게이션
    'nav' => [
        'dashboard' => '대시보드',
        'reservations' => '예약 관리',
        'services' => '서비스 관리',
        'services_settings' => '서비스 설정',
        'categories' => '카테고리 관리',
        'time_slots' => '시간대 관리',
        'members' => '회원 관리',
        'members_list' => '회원 목록',
        'members_settings' => '회원 설정',
        'members_groups' => '회원 그룹',
        'points' => '적립금',
        'users' => '사용자 관리',
        'staff' => '스태프 관리',
        'staff_settings' => '스태프 설정',
        'staff_attendance' => '근태 관리',
        'settings' => '설정',
        'site_management' => '사이트 관리',
        'menu_management' => '메뉴 관리',
        'design_management' => '레이아웃 관리',
        'page_management' => '페이지 관리',
        'board_management' => '게시판 관리',
        'widget_management' => '위젯 관리',
            'staff_admins' => '관리자 권한',
    ],

    // 대시보드
    'stats' => [
        'today_reservations' => '오늘 예약',
        'pending_reservations' => '대기 예약',
        'monthly_revenue' => '이번 달 매출',
        'active_services' => '활성 서비스',
        'total_users' => '전체 사용자',
    ],

    // 사용자 관리
    'users' => [
        'title' => '사용자 관리', 'list' => '사용자 목록', 'create' => '사용자 추가',
        'edit' => '사용자 수정', 'detail' => '사용자 상세',
        'fields' => [
            'name' => '이름', 'email' => '이메일', 'phone' => '전화번호', 'role' => '권한',
            'is_active' => '활성 상태', 'created_at' => '가입일', 'last_login' => '마지막 로그인',
        ],
        'roles' => ['user' => '일반 사용자', 'admin' => '관리자', 'super_admin' => '최고 관리자'],
    ],

    // 공통 버튼
    'buttons' => [
        'save' => '저장', 'cancel' => '취소', 'delete' => '삭제', 'edit' => '수정',
        'add' => '추가', 'create' => '생성', 'update' => '업데이트', 'search' => '검색',
        'reset' => '초기화', 'confirm' => '확인', 'back' => '뒤로', 'close' => '닫기', 'apply' => '적용',
    ],

    // 공통 메시지
    'messages' => [
        'confirm_delete' => '정말 삭제하시겠습니까?',
        'no_data' => '데이터가 없습니다.',
        'loading' => '로딩 중...',
        'processing' => '처리 중...',
        'save' => '저장',
        'saved' => '저장되었습니다.',
    ],

    // 공통 텍스트
    'common' => [
        'yes' => '예', 'no' => '아니오', 'recommended' => '권장',
        'enabled' => '활성화됨', 'disabled' => '비활성화됨',
        'active' => '활성', 'inactive' => '비활성', 'actions' => '작업',
        'showing' => '표시 중', 'of' => '/', 'prev' => '이전', 'next' => '다음',
        'save' => '저장', 'saved' => '저장되었습니다.', 'add' => '추가', 'multilang' => '다국어',
    ],

    // 언어 목록 (현지어 + 관리자 언어로 번역된 이름)
    'languages' => [
        'ko' => ['native' => '한국어', 'label' => '한국어'],
        'en' => ['native' => 'English', 'label' => '영어'],
        'ja' => ['native' => '日本語', 'label' => '일본어'],
        'zh_CN' => ['native' => '简体中文', 'label' => '중국어(간체)'],
        'zh_TW' => ['native' => '繁體中文', 'label' => '중국어(번체)'],
        'de' => ['native' => 'Deutsch', 'label' => '독일어'],
        'es' => ['native' => 'Español', 'label' => '스페인어'],
        'fr' => ['native' => 'Français', 'label' => '프랑스어'],
        'id' => ['native' => 'Bahasa Indonesia', 'label' => '인도네시아어'],
        'mn' => ['native' => 'Монгол хэл', 'label' => '몽골어'],
        'ru' => ['native' => 'Русский', 'label' => '러시아어'],
        'tr' => ['native' => 'Türkçe', 'label' => '터키어'],
        'vi' => ['native' => 'Tiếng Việt', 'label' => '베트남어'],
    ],

    // PWA
    'pwa' => [
        'update_available' => '새 버전이 있습니다',
        'update' => '업데이트',
        'later' => '나중에',
    ],
];
