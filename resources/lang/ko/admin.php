<?php

/**
 * 관리자 번역 - 한국어
 */

return [
    // 공통
    'title' => '관리자',
    'dashboard' => '대시보드',
    'back_to_site' => '사이트로 돌아가기',
    'dark_mode' => '다크 모드 전환',

    // 네비게이션
    'nav' => [
        'dashboard' => '대시보드',
        'reservations' => '예약 관리',
        'services' => '서비스 관리',
        'categories' => '카테고리 관리',
        'time_slots' => '시간대 관리',
        'members' => '회원 관리',
        'points' => '적립금 관리',
        'users' => '사용자 관리',
        'settings' => '설정',
        'site_management' => '사이트 관리',
        'menu_management' => '메뉴 관리',
        'design_management' => '디자인 관리',
        'page_management' => '페이지 관리',
    ],

    // 대시보드
    'stats' => [
        'today_reservations' => '오늘 예약',
        'pending_reservations' => '대기 예약',
        'monthly_revenue' => '이번 달 매출',
        'active_services' => '활성 서비스',
        'total_users' => '전체 사용자',
    ],

    // 예약 관리
    'reservations' => [
        'title' => '예약 관리',
        'list' => '예약 목록',
        'calendar' => '캘린더 보기',
        'statistics' => '통계',
        'create' => '예약 추가',
        'edit' => '예약 수정',
        'detail' => '예약 상세',

        'filter' => [
            'all' => '전체',
            'today' => '오늘',
            'pending' => '대기중',
            'confirmed' => '확정',
        ],

        'actions' => [
            'confirm' => '확정',
            'cancel' => '취소',
            'complete' => '완료',
            'no_show' => '노쇼',
            'edit' => '수정',
            'delete' => '삭제',
        ],

        'confirm_msg' => '예약을 확정하시겠습니까?',
        'cancel_msg' => '예약을 취소하시겠습니까?',
        'complete_msg' => '예약을 완료 처리하시겠습니까?',
        'noshow_msg' => '노쇼로 처리하시겠습니까?',

        'success' => [
            'created' => '예약이 생성되었습니다.',
            'updated' => '예약이 수정되었습니다.',
            'confirmed' => '예약이 확정되었습니다.',
            'cancelled' => '예약이 취소되었습니다.',
            'completed' => '예약이 완료 처리되었습니다.',
        ],
    ],

    // 서비스 관리
    'services' => [
        'title' => '서비스 관리',
        'list' => '서비스 목록',
        'create' => '서비스 추가',
        'edit' => '서비스 수정',
        'detail' => '서비스 상세',

        'fields' => [
            'name' => '서비스명',
            'slug' => 'URL 슬러그',
            'description' => '설명',
            'short_description' => '짧은 설명',
            'duration' => '소요시간 (분)',
            'price' => '가격',
            'category' => '카테고리',
            'is_active' => '활성 상태',
            'max_capacity' => '최대 수용 인원',
            'buffer_time' => '버퍼 시간 (분)',
            'advance_booking_days' => '예약 가능 기간 (일)',
            'min_notice_hours' => '최소 사전 알림 (시간)',
        ],

        'success' => [
            'created' => '서비스가 생성되었습니다.',
            'updated' => '서비스가 수정되었습니다.',
            'deleted' => '서비스가 삭제되었습니다.',
            'activated' => '서비스가 활성화되었습니다.',
            'deactivated' => '서비스가 비활성화되었습니다.',
        ],

        'error' => [
            'has_reservations' => '예약이 있는 서비스는 삭제할 수 없습니다.',
        ],
    ],

    // 카테고리 관리
    'categories' => [
        'title' => '카테고리 관리',
        'list' => '카테고리 목록',
        'create' => '카테고리 추가',
        'edit' => '카테고리 수정',

        'fields' => [
            'name' => '카테고리명',
            'slug' => 'URL 슬러그',
            'description' => '설명',
            'parent' => '상위 카테고리',
            'sort_order' => '정렬 순서',
            'is_active' => '활성 상태',
        ],

        'success' => [
            'created' => '카테고리가 생성되었습니다.',
            'updated' => '카테고리가 수정되었습니다.',
            'deleted' => '카테고리가 삭제되었습니다.',
        ],
    ],

    // 시간대 관리
    'time_slots' => [
        'title' => '시간대 관리',
        'default_slots' => '기본 시간대',
        'blocked_dates' => '차단된 날짜',

        'fields' => [
            'day_of_week' => '요일',
            'start_time' => '시작 시간',
            'end_time' => '종료 시간',
            'max_bookings' => '최대 예약 수',
            'service' => '서비스',
            'specific_date' => '특정 날짜',
        ],

        'block_date' => '날짜 차단',
        'unblock_date' => '차단 해제',
    ],

    // 사용자 관리
    'users' => [
        'title' => '사용자 관리',
        'list' => '사용자 목록',
        'create' => '사용자 추가',
        'edit' => '사용자 수정',
        'detail' => '사용자 상세',

        'fields' => [
            'name' => '이름',
            'email' => '이메일',
            'phone' => '전화번호',
            'role' => '권한',
            'is_active' => '활성 상태',
            'created_at' => '가입일',
            'last_login' => '마지막 로그인',
        ],

        'roles' => [
            'user' => '일반 사용자',
            'admin' => '관리자',
            'super_admin' => '최고 관리자',
        ],
    ],

    // 설정
    'settings' => [
        'title' => '시스템 설정',
        'general' => '일반 설정',
        'booking' => '예약 설정',
        'email' => '이메일 설정',
        'payment' => '결제 설정',

        // 설정 탭
        'tabs' => [
            'general' => '일반',
            'seo' => 'SEO',
            'pwa' => 'PWA',
            'system' => '시스템',
        ],

        // 관리자 경로
        'admin_path' => [
            'title' => '관리자 접속 경로',
            'description' => '보안을 위해 관리자 페이지 접속 경로를 변경할 수 있습니다.',
            'current_url' => '현재 접속 URL',
            'label' => '관리자 경로',
            'hint' => '영문, 숫자, 하이픈(-), 언더스코어(_)만 사용 가능합니다.',
            'warning' => '경로 변경 후 새 주소로 접속해야 합니다.',
            'button' => '경로 변경',
            'changed' => '관리자 경로가 변경되었습니다. 현재 새 경로로 접속 중입니다.',
            'error_empty' => '관리자 경로를 입력해주세요.',
            'error_invalid' => '관리자 경로는 영문, 숫자, 하이픈, 언더스코어만 사용 가능합니다.',
            'error_reserved' => '예약된 경로는 사용할 수 없습니다.',
        ],

        // 사이트 기본 설정
        'site' => [
            'title' => '사이트 기본 설정',
            'category_label' => '사이트 분류 (업종)',
            'category_description' => '예약 시스템이 적용될 업종을 선택하세요. 업종에 따라 최적화된 기능이 제공됩니다.',
            'category_placeholder' => '-- 업종을 선택하세요 --',
            'categories' => [
                'beauty_salon' => '미용실 / 헤어샵',
                'nail_salon' => '네일샵',
                'skincare' => '피부관리 / 에스테틱',
                'massage' => '마사지 / 스파',
                'hospital' => '병원 / 의원',
                'dental' => '치과',
                'studio' => '스튜디오 / 사진관',
                'restaurant' => '레스토랑 / 카페',
                'accommodation' => '숙박 / 호텔 / 펜션',
                'sports' => '스포츠 / 피트니스 / 골프',
                'education' => '교육 / 학원 / 레슨',
                'consulting' => '컨설팅 / 상담',
                'pet' => '펫 서비스 / 동물병원',
                'car' => '자동차 정비 / 세차',
                'other' => '기타',
            ],
            'name' => '사이트 이름',
            'tagline' => '사이트 제목',
            'tagline_hint' => '사이트의 슬로건이나 짧은 설명을 입력하세요.',
            'url' => '사이트 URL',
        ],

        // 다국어 입력
        'multilang' => [
            'button_title' => '다국어 입력',
            'modal_title' => '다국어 입력',
            'modal_description' => '각 언어별로 내용을 입력하세요.',
            'tab_ko' => '한국어',
            'tab_en' => '영어',
            'tab_ja' => '일본어',
            'placeholder' => '내용을 입력하세요...',
            'save' => '저장',
            'cancel' => '취소',
            'saved' => '다국어 내용이 저장되었습니다.',
            'error' => '저장 중 오류가 발생했습니다.',
        ],

        // 로고 설정
        'logo' => [
            'title' => '로고 설정',
            'type_label' => '로고 표시 형식',
            'type_text' => '텍스트만',
            'type_image' => '이미지만',
            'type_image_text' => '이미지 + 텍스트',
            'image_label' => '로고 이미지',
            'current' => '현재 로고',
            'preview' => '새 로고 미리보기',
            'display_preview' => '실제 표시 형태',
            'hint' => 'JPG, PNG, GIF, SVG, WebP 형식 지원 (권장 크기: 높이 40px)',
            'delete' => '삭제',
            'delete_confirm' => '로고 이미지를 삭제하시겠습니까?',
        ],

        // SEO 설정
        'seo' => [
            'title' => 'SEO 설정',
            'description' => '검색 엔진 최적화 설정을 관리합니다.',

            // 메타 태그
            'meta' => [
                'title' => '메타 태그',
                'description_label' => '메타 설명 (Meta Description)',
                'description_hint' => '검색 결과에 표시되는 사이트 설명입니다. 150-160자 권장.',
                'keywords_label' => '메타 키워드 (Meta Keywords)',
                'keywords_hint' => '쉼표로 구분하여 입력 (예: 예약, 뷰티, 헤어샵)',
                'keywords_placeholder' => '예약, 뷰티, 헤어샵, 네일',
            ],

            // 오픈 그래프
            'og' => [
                'title' => '소셜 미디어 (Open Graph)',
                'description' => 'SNS 공유 시 표시되는 정보를 설정합니다.',
                'image_label' => '대표 이미지 (OG Image)',
                'image_hint' => '권장 크기: 1200x630 픽셀 (JPG, PNG, WebP)',
                'image_current' => '현재 이미지',
                'image_preview' => '새 이미지 미리보기',
                'image_delete' => '삭제',
                'image_delete_confirm' => '대표 이미지를 삭제하시겠습니까?',
            ],

            // 검색 엔진
            'search_engine' => [
                'title' => '검색 엔진 설정',
                'robots_label' => '검색 엔진 노출',
                'robots_index' => '검색 허용 (index, follow)',
                'robots_noindex' => '검색 비허용 (noindex, nofollow)',
                'robots_hint' => '사이트가 검색 엔진에 노출되도록 할지 설정합니다.',
            ],

            // 웹마스터 도구
            'webmaster' => [
                'title' => '웹마스터 도구 인증',
                'google_label' => 'Google Search Console',
                'google_hint' => 'Google Search Console 메타 태그의 content 값을 입력하세요.',
                'google_placeholder' => 'XXXXXXXXXXXXXXXX',
                'naver_label' => '네이버 웹마스터 도구',
                'naver_hint' => '네이버 웹마스터 도구 메타 태그의 content 값을 입력하세요.',
                'naver_placeholder' => 'XXXXXXXXXXXXXXXX',
            ],

            // 애널리틱스
            'analytics' => [
                'title' => '분석 도구 연동',
                'ga_label' => 'Google Analytics 추적 ID',
                'ga_hint' => 'G-XXXXXXXXXX 또는 UA-XXXXXXXXX-X 형식으로 입력하세요.',
                'ga_placeholder' => 'G-XXXXXXXXXX',
                'gtm_label' => 'Google Tag Manager ID',
                'gtm_hint' => 'GTM-XXXXXXX 형식으로 입력하세요.',
                'gtm_placeholder' => 'GTM-XXXXXXX',
            ],

            // 저장 메시지
            'success' => 'SEO 설정이 저장되었습니다.',
        ],

        // PWA 설정
        'pwa' => [
            'title' => 'PWA 설정',
            'description' => '프로그레시브 웹 앱(PWA) 설정을 관리합니다.',

            // 프론트엔드 PWA
            'front' => [
                'title' => '프론트엔드 PWA',
                'description' => '사용자용 웹 앱 설정입니다.',
                'name_label' => '앱 이름',
                'name_placeholder' => '앱 이름을 입력하세요',
                'short_name_label' => '짧은 이름',
                'short_name_placeholder' => '짧은 이름',
                'short_name_hint' => '최대 12자. 홈 화면에서 공간이 부족할 때 표시됩니다.',
                'description_label' => '앱 설명',
                'theme_color_label' => '테마 색상',
                'bg_color_label' => '배경 색상',
                'display_label' => '디스플레이 모드',
                'icon_label' => '앱 아이콘',
            ],

            // 관리자 PWA
            'admin' => [
                'title' => '관리자 PWA',
                'description' => '관리자용 웹 앱 설정입니다.',
                'name_label' => '앱 이름',
                'short_name_label' => '짧은 이름',
                'theme_color_label' => '테마 색상',
                'bg_color_label' => '배경 색상',
                'icon_label' => '앱 아이콘',
            ],

            // 공통
            'icon_current' => '현재 아이콘',
            'icon_hint' => 'PNG 또는 WebP 형식, 512x512 픽셀 권장',
            'icon_delete' => '아이콘 삭제',
            'icon_delete_confirm' => '아이콘을 삭제하시겠습니까?',
            'icon_deleted' => '아이콘이 삭제되었습니다.',
            'error_icon_type' => '허용되지 않는 이미지 형식입니다. PNG 또는 WebP만 가능합니다.',
            'success' => 'PWA 설정이 저장되었습니다.',
        ],

        // 시스템 정보
        'system' => [
            // 탭 메뉴
            'tabs' => [
                'info' => '정보관리',
                'cache' => '캐시관리',
                'mode' => '모드관리',
                'logs' => '로그관리',
                'updates' => '업데이트',
            ],
            'app' => [
                'title' => '애플리케이션 정보',
                'name' => '앱 이름',
                'version' => '버전',
                'environment' => '환경',
                'debug_mode' => '디버그 모드',
                'debug_warning' => '프로덕션 환경에서는 디버그 모드를 비활성화하세요.',
                'url' => 'URL',
                'locale' => '언어',
            ],
            'php' => [
                'title' => 'PHP 정보',
                'version' => 'PHP 버전',
                'sapi' => 'SAPI',
                'timezone' => '타임존',
                'memory_limit' => '메모리 제한',
                'max_execution_time' => '최대 실행 시간',
                'upload_max_filesize' => '최대 업로드 크기',
                'post_max_size' => '최대 POST 크기',
                'display_errors' => '오류 표시',
                'extensions' => '필수 확장 모듈',
            ],
            'db' => [
                'title' => '데이터베이스 정보',
                'driver' => '드라이버',
                'version' => '버전',
                'host' => '호스트',
                'database' => '데이터베이스',
                'charset' => '문자셋',
                'collation' => '콜레이션',
            ],
            'server' => [
                'title' => '서버 정보',
                'os' => '운영체제',
                'os_family' => 'OS 계열',
                'software' => '서버 소프트웨어',
                'document_root' => '문서 루트',
                'current_time' => '현재 시간',
            ],
            'status' => [
                'on' => '켜짐',
                'off' => '꺼짐',
            ],
            // 캐시관리
            'cache' => [
                'title' => '캐시 관리',
                'description' => '애플리케이션 캐시를 관리합니다. 캐시를 삭제하면 성능이 일시적으로 저하될 수 있습니다.',
                'view' => '뷰 캐시',
                'view_desc' => '컴파일된 뷰 템플릿 캐시',
                'config' => '설정 캐시',
                'config_desc' => '애플리케이션 설정 캐시',
                'route' => '라우트 캐시',
                'route_desc' => '라우팅 정보 캐시',
                'clear' => '삭제',
                'clear_all' => '모든 캐시 삭제',
                'cached' => '캐시됨',
                'not_cached' => '없음',
                'confirm_clear' => '캐시를 삭제하시겠습니까?',
                'cleared' => '캐시가 삭제되었습니다.',
            ],
            // 모드관리
            'mode' => [
                'title' => '모드 관리',
                'description' => '애플리케이션 실행 모드를 관리합니다.',
                'debug' => '디버그 모드',
                'debug_desc' => '상세 오류 메시지를 표시합니다. 프로덕션에서는 비활성화하세요.',
                'maintenance' => '점검 모드',
                'maintenance_desc' => '사이트 점검 중 사용자 접근을 차단합니다.',
                'environment' => '환경',
                'environment_desc' => '현재 애플리케이션 실행 환경',
                'env_notice' => '디버그 모드와 환경 설정은 .env 파일에서 변경할 수 있습니다.',
                'enable_maintenance' => '점검 모드 활성화',
                'disable_maintenance' => '점검 모드 해제',
                'confirm_enable_maintenance' => '점검 모드를 활성화하시겠습니까? 관리자를 제외한 모든 사용자가 사이트에 접근할 수 없게 됩니다.',
                'confirm_disable_maintenance' => '점검 모드를 해제하시겠습니까?',
                'maintenance_enabled' => '점검 모드가 활성화되었습니다.',
                'maintenance_disabled' => '점검 모드가 해제되었습니다.',
                'maintenance_message' => '현재 사이트 점검 중입니다. 잠시 후 다시 방문해 주세요.',
                // 디버그 모드 토글
                'enable_debug' => '디버그 모드 활성화',
                'disable_debug' => '디버그 모드 비활성화',
                'confirm_enable_debug' => '디버그 모드를 활성화하시겠습니까? 오류 상세 정보가 표시됩니다.',
                'confirm_disable_debug' => '디버그 모드를 비활성화하시겠습니까?',
                'debug_enabled' => '디버그 모드가 활성화되었습니다.',
                'debug_disabled' => '디버그 모드가 비활성화되었습니다.',
                'debug_error' => '디버그 모드 설정 중 오류가 발생했습니다.',
                'debug_env_locked' => '.env 파일에서 APP_DEBUG=true로 설정되어 있어 비활성화할 수 없습니다.',
            ],
            // 로그관리
            'logs' => [
                'title' => '로그 관리',
                'description' => '애플리케이션 로그 파일을 관리합니다.',
                'filename' => '파일명',
                'size' => '크기',
                'modified' => '수정일',
                'actions' => '작업',
                'view' => '보기',
                'delete' => '삭제',
                'download' => '다운로드',
                'copy' => '복사',
                'copied' => '클립보드에 복사되었습니다.',
                'clear_all' => '모든 로그 삭제',
                'no_logs' => '로그 파일이 없습니다.',
                'no_logs_desc' => '아직 기록된 로그가 없습니다.',
                'confirm_delete' => '이 로그 파일을 삭제하시겠습니까?',
                'confirm_clear_all' => '모든 로그 파일을 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.',
                'deleted' => '로그 파일이 삭제되었습니다.',
                'all_cleared' => '모든 로그 파일이 삭제되었습니다.',
                'back_to_list' => '목록으로',
                'selected' => '개 선택됨',
                'delete_selected' => '선택 삭제',
                'confirm_delete_selected' => '선택한 로그 파일들을 삭제하시겠습니까?',
                'selected_deleted' => ':count개의 로그 파일이 삭제되었습니다.',
                'total_files' => '총 :count개 파일',
                'last_lines' => '최근 :count줄 표시',
                'showing_first' => ':total개 중 :count개만 표시됩니다.',
            ],
            // 업데이트 관리
            'updates' => [
                'title' => '업데이트 관리',
                'description' => 'GitHub를 통해 시스템 업데이트를 관리합니다.',
                'current_version' => '현재 버전',
                'channel' => '채널',
                'check_update' => '업데이트 확인',
                'checking' => '확인 중...',
                'up_to_date' => '최신 버전을 사용 중입니다.',
                'new_version_available' => '새 버전이 있습니다!',
                'view_details' => '상세 보기',
                'release_notes' => '릴리스 노트',
                'no_notes' => '릴리스 노트가 없습니다.',
                'no_releases' => '릴리스를 찾을 수 없습니다.',
                'github_settings' => 'GitHub 설정',
                'github_description' => 'GitHub 저장소 정보를 입력하여 자동 업데이트를 활성화합니다.',
                'github_owner' => '저장소 소유자',
                'github_owner_hint' => 'GitHub 사용자명 또는 조직명',
                'github_repo' => '저장소 이름',
                'github_repo_hint' => '저장소 이름 (예: rezlyx)',
                'github_branch' => '브랜치',
                'github_token' => 'GitHub 토큰',
                'github_token_hint' => '비공개 저장소의 경우 Personal Access Token 필요',
                'github_not_configured' => 'GitHub 저장소가 설정되지 않았습니다.',
                'optional' => '선택사항',
                'settings_saved' => 'GitHub 설정이 저장되었습니다.',
                'settings_error' => '설정 저장 중 오류가 발생했습니다.',
                'requirements' => '시스템 요구사항',
                'writable_root' => '루트 디렉토리 쓰기 권한',
                'not_available' => '불가',
                'requirements_warning' => '일부 요구사항이 충족되지 않아 자동 업데이트가 제한될 수 있습니다.',
                'notes_title' => '업데이트 안내',
                'note_backup' => '업데이트 전 자동으로 백업이 생성됩니다.',
                'note_maintenance' => '업데이트 중에는 사이트가 유지보수 모드로 전환됩니다.',
                'note_rollback' => '업데이트 실패 시 자동으로 이전 버전으로 복원됩니다.',
                'note_private' => '비공개 저장소는 GitHub Personal Access Token이 필요합니다.',
            ],
        ],

        // 시스템 정보
        'system_info' => [
            'title' => '시스템 정보',
            'php_version' => 'PHP 버전',
            'environment' => '환경',
            'timezone' => '타임존',
            'debug_mode' => '디버그 모드',
            'enabled' => '활성화',
            'disabled' => '비활성화',
        ],

        'fields' => [
            'app_name' => '사이트명',
            'app_timezone' => '시간대',
            'app_locale' => '기본 언어',
            'admin_path' => '관리자 경로',
            'booking_auto_confirm' => '예약 자동 확정',
            'booking_email_notification' => '이메일 알림',
            'booking_advance_days' => '예약 가능 기간 (일)',
        ],

        'success' => '사이트 설정이 저장되었습니다.',
        'error_save' => '저장 실패',
        'error_image_type' => '허용되지 않는 이미지 형식입니다. (JPG, PNG, GIF, SVG, WebP만 가능)',
        'logo_deleted' => '로고 이미지가 삭제되었습니다.',
    ],

    // 사이트 관리
    'site' => [
        // 메뉴 관리
        'menus' => [
            'title' => '메뉴 관리',
            'description' => '사이트 네비게이션 메뉴를 관리합니다.',
            'list' => '메뉴 목록',
            'add' => '메뉴 추가',
            'coming_soon' => '메뉴 관리 기능 준비 중',
            'coming_soon_desc' => '곧 네비게이션 메뉴를 관리할 수 있습니다.',
        ],

        // 디자인 관리
        'design' => [
            'title' => '디자인 관리',
            'description' => '사이트 디자인 및 테마를 관리합니다.',
            'theme_title' => '테마 설정',
            'theme_desc' => '사이트 색상 테마와 스타일을 변경합니다.',
            'layout_title' => '레이아웃 설정',
            'layout_desc' => '페이지 레이아웃과 구조를 변경합니다.',
            'header_footer_title' => '헤더/푸터',
            'header_footer_desc' => '헤더와 푸터 디자인을 변경합니다.',
            'coming_soon' => '준비 중',
        ],

        // 페이지 관리
        'pages' => [
            'title' => '페이지 관리',
            'description' => '커스텀 페이지를 생성하고 관리합니다.',
            'list' => '페이지 목록',
            'add' => '새 페이지',
            'system_page' => '시스템 페이지',
            'custom_page' => '커스텀 페이지',
            'empty' => '아직 커스텀 페이지가 없습니다.',
            'empty_hint' => '새 페이지를 추가하여 사이트를 확장하세요.',
            'home' => '홈',
            'terms' => '이용약관',
            'privacy' => '개인정보처리방침',
        ],
    ],

    // 공통 버튼
    'buttons' => [
        'save' => '저장',
        'cancel' => '취소',
        'delete' => '삭제',
        'edit' => '수정',
        'add' => '추가',
        'create' => '생성',
        'update' => '업데이트',
        'search' => '검색',
        'reset' => '초기화',
        'confirm' => '확인',
        'back' => '뒤로',
        'close' => '닫기',
    ],

    // 공통 메시지
    'messages' => [
        'confirm_delete' => '정말 삭제하시겠습니까?',
        'no_data' => '데이터가 없습니다.',
        'loading' => '로딩 중...',
        'processing' => '처리 중...',
    ],
];
