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
        'members_list' => '회원 목록',
        'members_settings' => '회원 설정',
        'members_groups' => '회원 그룹',
        'points' => '적립금',
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

            // PWA 서브 탭
            'tabs' => [
                'general' => '기본 설정',
                'webpush' => '웹푸시 설정',
                'subscribers' => '구독자 관리',
            ],

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

            // 웹푸시 설정
            'webpush' => [
                'title' => '웹푸시 알림',
                'description' => '웹푸시 알림 기능을 설정합니다.',
                'success' => '웹푸시 설정이 저장되었습니다.',
                'status_enabled' => '웹푸시 알림이 활성화되어 있습니다.',
                'status_disabled' => '웹푸시 알림이 비활성화되어 있습니다.',

                'vapid' => [
                    'title' => 'VAPID 키 설정',
                    'description' => '웹푸시 인증에 필요한 VAPID 키를 관리합니다.',
                    'public_key' => '공개 키 (Public Key)',
                    'public_key_hint' => '이 키는 클라이언트 측에서 사용됩니다.',
                    'private_key' => '비밀 키 (Private Key)',
                    'private_key_warning' => '이 키는 절대 공개하지 마세요!',
                    'subject' => 'Subject (연락처)',
                    'subject_hint' => 'mailto: 또는 https:// 형식 (예: mailto:admin@example.com)',
                    'generate' => 'VAPID 키 생성',
                    'generate_confirm' => '새 VAPID 키를 생성하시겠습니까? 기존 키가 덮어씌워지며, 기존 구독자들은 재구독이 필요합니다.',
                    'vapid_generated' => 'VAPID 키가 생성되었습니다.',
                    'vapid_error' => 'VAPID 키 생성에 실패했습니다.',
                    'openssl_required' => 'VAPID 키 생성에는 OpenSSL PHP 확장이 필요합니다.',
                ],

                'defaults' => [
                    'title' => '알림 기본값',
                    'title_label' => '기본 제목',
                    'icon_label' => '기본 아이콘 경로',
                    'badge_label' => '기본 배지 아이콘',
                    'badge_hint' => '작은 아이콘 (모바일 상태바에 표시됨)',
                    'vibrate' => '진동 사용',
                    'require_interaction' => '사용자 상호작용 필요',
                ],

                'test' => [
                    'title' => '테스트 알림',
                    'description' => '현재 브라우저로 테스트 알림을 보내봅니다.',
                    'send_button' => '테스트 알림 보내기',
                    'body' => '이것은 테스트 알림입니다.',
                    'not_supported' => '이 브라우저는 알림을 지원하지 않습니다.',
                    'permission_denied' => '알림 권한이 거부되었습니다. 브라우저 설정에서 허용해주세요.',
                    'test_sent' => '테스트 알림이 전송되었습니다.',
                ],
            ],

            // 구독자 관리
            'subscribers' => [
                'tables_missing' => '데이터베이스 테이블이 없습니다',
                'tables_missing_desc' => '푸시 알림 기능을 사용하려면 필요한 테이블을 생성해야 합니다.',
                'create_tables' => '테이블 생성',
                'tables_created' => '테이블이 생성되었습니다.',

                'stats' => [
                    'total' => '전체 구독자',
                    'messages_sent' => '발송 메세지',
                    'webpush_status' => '웹푸시 상태',
                ],

                'send' => [
                    'title' => '알림 발송',
                    'title_label' => '제목',
                    'title_placeholder' => '알림 제목을 입력하세요',
                    'body_label' => '내용',
                    'body_placeholder' => '알림 내용을 입력하세요',
                    'url_label' => '클릭 시 이동 URL (선택)',
                    'target_label' => '발송 대상',
                    'target_all' => '전체',
                    'target_customers' => '고객만',
                    'target_admins' => '관리자만',
                    'save_to_inbox' => '인박스에 저장',
                    'save_to_inbox_hint' => '사용자 메시지함에도 저장됩니다',
                    'submit' => '발송하기',
                ],

                'list' => [
                    'title' => '구독자 목록',
                    'endpoint' => '엔드포인트',
                    'user_agent' => '사용자 에이전트',
                    'created' => '구독일',
                    'status' => '상태',
                    'empty' => '아직 구독자가 없습니다.',
                ],

                'messages' => [
                    'title' => '최근 발송 내역',
                    'title_col' => '제목',
                    'sent_count' => '발송 수',
                    'status' => '상태',
                    'created' => '생성일',
                ],

                'error_empty_fields' => '제목과 내용을 입력해주세요.',
                'notification_queued' => '알림이 발송 대기열에 추가되었습니다.',
                'saved_to_inbox' => '(사용자 인박스에도 저장됨)',
                'deleted' => '구독자가 삭제되었습니다.',
                'delete_confirm' => '이 구독자를 삭제하시겠습니까?',
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
                'check_failed' => '업데이트 확인에 실패했습니다.',
                'up_to_date' => '최신 버전을 사용 중입니다.',
                'new_version_available' => '새 버전이 있습니다!',
                'view_details' => '상세 보기',
                'release_notes' => '릴리스 노트',
                'no_notes' => '릴리스 노트가 없습니다.',
                'no_releases' => '릴리스를 찾을 수 없습니다.',
                'requirements' => '시스템 요구사항',
                'writable_root' => '루트 디렉토리 쓰기 권한',
                'writable_storage' => 'Storage 디렉토리 쓰기 권한',
                'not_available' => '불가',
                'requirements_warning' => '일부 요구사항이 충족되지 않아 자동 업데이트가 제한될 수 있습니다.',
                'notes_title' => '업데이트 안내',
                'note_backup' => '업데이트 전 자동으로 백업이 생성됩니다.',
                'note_maintenance' => '업데이트 중에는 사이트가 유지보수 모드로 전환됩니다.',
                'note_rollback' => '업데이트 실패 시 자동으로 이전 버전으로 복원됩니다.',
                // 백업 관련
                'backups' => '백업 목록',
                'no_backups' => '저장된 백업이 없습니다.',
                'restore' => '복원',
                'confirm_restore' => '이 백업으로 복원하시겠습니까? 현재 파일들이 덮어씌워집니다.',
                'restore_failed' => '복원에 실패했습니다.',
                // 업데이트 실행
                'update_now' => '지금 업데이트',
                'confirm_update' => '업데이트를 진행하시겠습니까? 업데이트 전 자동으로 백업이 생성됩니다.',
                'update_failed' => '업데이트에 실패했습니다.',
                'reload_page' => '잠시 후 페이지가 새로고침됩니다.',
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

    // 공통 텍스트
    'common' => [
        'yes' => '예',
        'no' => '아니오',
        'recommended' => '권장',
        'enabled' => '활성화됨',
        'disabled' => '비활성화됨',
        'active' => '활성',
        'inactive' => '비활성',
        'actions' => '작업',
        'showing' => '표시 중',
        'of' => '/',
        'prev' => '이전',
        'next' => '다음',
    ],

    // 회원 관리
    'members' => [
        'title' => '회원 관리',
        'list' => '회원 목록',
        'create' => '회원 추가',
        'edit' => '회원 수정',
        'detail' => '회원 상세',

        // 회원 설정
        'settings' => [
            'title' => '회원 설정',

            // 탭 메뉴
            'tabs' => [
                'general' => '기본 설정',
                'features' => '기능 설정',
                'terms' => '약관 설정',
                'register' => '회원가입',
                'login' => '로그인',
                'design' => '디자인',
            ],

            // 기본 설정
            'general' => [
                'title' => '기본 설정',
                'description' => '회원 시스템의 기본 동작을 설정합니다.',

                // 회원가입 허가
                'registration_mode' => '회원 가입 허가',
                'registration_mode_desc' => '회원 가입을 받을지 선택합니다. URL 키를 사용할 경우, 일치하는 문자열이 포함된 URL로 접속해야 가입할 수 있게 됩니다.',
                'registration_url_key' => 'URL 키가 일치하는 경우에만 허가',
                'url_key_label' => 'URL 키',
                'url_key_placeholder' => '예: secretkey123',
                'url_key_hint' => '회원가입 URL에 ?key=값 형식으로 접속해야 가입이 가능합니다. (예: /register?key=secretkey123)',

                // 이메일 인증
                'email_verification' => '메일 인증 사용',
                'email_verification_desc' => '입력된 메일 주소로 인증 메일을 보내 회원 가입을 확인합니다. 가입자가 인증 메일의 링크를 클릭해야 정상적으로 로그인이 가능해집니다.',

                // 인증 메일 유효기간
                'email_validity' => '인증 메일 유효기간',
                'email_validity_desc' => '가입 인증 메일, 아이디/비번 찾기 등의 유효기간을 제한할 수 있습니다.',
                'days' => '일',

                // 회원 프로필사진 보이기
                'show_profile_photo' => '회원 프로필사진 보이기',
                'show_profile_photo_desc' => '관리자 회원목록 페이지에서 프로필 이미지를 볼 수 있는 옵션입니다. 회원목록에서 프로필 사진을 보기 원치 않을 경우에는 아니요를 선택하세요.',

                // 비번 변경시 다른 기기 로그아웃
                'logout_on_password_change' => '비번 변경시 다른 기기 로그아웃',
                'logout_on_password_change_desc' => '비밀번호를 변경하면 현재 기기(브라우저)를 제외한 모든 로그인이 풀리도록 합니다.',

                // ID/PW 찾기 방법
                'password_recovery_method' => 'ID/PW 찾기 방법',
                'password_recovery_method_desc' => 'ID/PW 찾기 기능 사용시, 새 비밀번호로 변경하는 방법을 선택합니다.',
                'recovery_link' => '비밀번호 변경 화면 링크 전달',
                'recovery_random' => '랜덤 비밀번호 전달',

                // 회원정보 동기화
                'sync_title' => '회원정보 동기화',
                'sync_description' => '회원정보와 게시물/댓글 정보를 동기화 합니다.',
                'sync_warning' => '데이터가 많은 경우 시간이 오래 소요될 수 있습니다. 이용자가 많은 경우 반드시 서비스를 중단하고 진행하세요.',
                'sync_button' => '동기화 실행',
                'sync_confirm' => '회원정보 동기화를 진행하시겠습니까? 데이터가 많은 경우 시간이 오래 소요될 수 있습니다.',
                'sync_complete' => '회원정보 동기화가 완료되었습니다.',
                'sync_error' => '동기화 중 오류가 발생했습니다',

                // 기존 설정 (하위 호환)
                'auto_login' => '가입 후 자동 로그인',
                'auto_login_desc' => '회원가입 완료 후 자동으로 로그인합니다.',
                'default_group' => '기본 회원 그룹',
                'default_group_desc' => '신규 가입 회원에게 자동으로 부여되는 그룹',
                'groups' => [
                    'member' => '일반 회원',
                    'vip' => 'VIP 회원',
                    'pending' => '승인 대기',
                ],
            ],

            // 기능 설정
            'features' => [
                'title' => '기능 설정',
                'description' => '회원에게 제공할 기능을 설정합니다.',
                'view_scrap' => '스크랩 보기',
                'view_scrap_desc' => '회원이 스크랩한 콘텐츠를 확인할 수 있습니다.',
                'view_bookmark' => '저장함 보기',
                'view_bookmark_desc' => '회원이 저장한 콘텐츠를 확인할 수 있습니다.',
                'view_posts' => '작성 글 보기',
                'view_posts_desc' => '회원이 작성한 게시글을 확인할 수 있습니다.',
                'view_comments' => '작성 댓글 보기',
                'view_comments_desc' => '회원이 작성한 댓글을 확인할 수 있습니다.',
                'auto_login_manage' => '자동 로그인 관리',
                'auto_login_manage_desc' => '회원이 자동 로그인 기기를 관리할 수 있습니다.',
            ],

            // 약관 설정
            'terms' => [
                'title' => '약관 설정',
                'description' => '회원가입 시 동의받을 약관을 설정합니다. 최대 5개의 약관을 등록할 수 있습니다.',
                'term_section' => '회원 가입 약관',
                'term_title' => '약관 제목',
                'term_title_placeholder' => '예: 이용약관, 개인정보처리방침',
                'term_content' => '약관 내용',
                'consent_required' => '동의 필수 여부',
                'consent_required_option' => '필수',
                'consent_optional_option' => '선택',
                'consent_disabled_option' => '사용 안 함',
            ],

            // 회원가입 설정
            'register' => [
                'title' => '회원가입 설정',
                'description' => '회원가입 폼과 프로세스를 설정합니다.',
                'form_fields' => '회원가입 입력 항목',
                'form_fields_desc' => '회원가입 시 입력받을 항목을 선택합니다.',
                'required_note' => '* 표시된 항목은 필수 항목입니다.',
                'fields' => [
                    'name' => '이름',
                    'email' => '이메일',
                    'password' => '비밀번호',
                    'phone' => '전화번호',
                    'birth_date' => '생년월일',
                    'gender' => '성별',
                    'company' => '회사/소속',
                    'blog' => '블로그',
                    'profile_photo' => '프로필 사진',
                ],
                'use_captcha' => 'CAPTCHA 사용',
                'use_captcha_desc' => '봇 가입을 방지하기 위해 CAPTCHA를 표시합니다.',
                'email_provider' => '이메일 제공자 관리',
                'email_provider_desc' => '특정 도메인에 소속된 이메일 주소로만 가입할 수 있도록 하거나, 특정 도메인을 금지할 수 있습니다. (예: naver.com, gmail.com)',
                'email_provider_none' => '제한 없음',
                'email_provider_allow' => '허가',
                'email_provider_block' => '제한',
                'email_provider_placeholder' => '예: naver.com, gmail.com',
                'email_provider_hint' => '여러 항목은 하나씩 추가하세요. 아무 것도 입력하지 않으면 이메일 주소를 제한하지 않습니다.',
                'email_provider_invalid' => '올바른 도메인 형식이 아닙니다.',
                'email_provider_duplicate' => '이미 등록된 도메인입니다.',
                'welcome_email' => '환영 이메일 발송',
                'welcome_email_desc' => '가입 완료 후 환영 이메일을 발송합니다.',
                'redirect_url' => '회원가입 후 이동할 페이지',
                'redirect_url_desc' => '회원가입 완료 후 이동할 페이지의 URL을 입력합니다.',
                'redirect_url_placeholder' => '예: /welcome 또는 https://example.com/welcome',
                'redirect_url_hint' => '비워두면 기본 페이지(마이페이지 또는 홈)로 이동합니다.',
            ],

            // 로그인 설정
            'login' => [
                'title' => '로그인 설정',
                'description' => '로그인 방식과 보안 옵션을 설정합니다.',
                'method' => '로그인 방식',
                'method_desc' => '회원이 로그인할 때 사용할 ID 유형을 선택합니다.',
                'method_email' => '이메일',
                'method_phone' => '전화번호',
                'method_both' => '이메일 또는 전화번호',
                'remember_me' => '로그인 상태 유지 옵션',
                'remember_me_desc' => '로그인 상태 유지 체크박스를 표시합니다.',
                'attempts' => '로그인 시도 제한',
                'attempts_desc' => '계정 잠금 전 허용되는 실패 횟수',
                'unlimited' => '무제한',
                'times' => '회',
                'lockout' => '계정 잠금 시간',
                'lockout_desc' => '로그인 실패 후 계정이 잠기는 시간',
                'minutes' => '분',
                'hour' => '시간',
                'hours' => '시간',
                'seconds' => '초',
                'brute_force' => '계정 무한 대입 방지 사용',
                'brute_force_desc' => '짧은 시간 동안 하나의 아이피(IP)에서 시도할 수 있는 로그인 횟수에 제한을 둡니다.',
                'single_device' => '다른 기기 로그아웃',
                'single_device_desc' => '한 번에 하나의 기기에서만 로그인할 수 있도록 합니다.',
                'login_redirect_url' => '로그인 후 이동할 주소(URL)',
                'login_redirect_url_desc' => '로그인 후 이동할 URL을 정할 수 있습니다. 입력하지 않으면 로그인 전의 페이지로 돌아갑니다.',
                'logout_redirect_url' => '로그아웃 후 이동할 주소(URL)',
                'logout_redirect_url_desc' => '로그아웃 후 이동할 URL을 정할 수 있습니다. 입력하지 않으면 로그아웃 전의 페이지로 돌아갑니다.',
                'redirect_url_placeholder' => '예: /mypage 또는 https://example.com',
            ],

            // 디자인 설정
            'design' => [
                'title' => '디자인 설정',
                'description' => '회원 관련 페이지의 디자인을 설정합니다.',
                'mypage_layout' => '마이페이지 레이아웃',
                'layout_width' => '전체너비',
                'layout_left_width' => '좌측너비',
                'layout_content_width' => '컨텐츠너비',
                'layout_right_width' => '우측너비',
                'layout_style' => '레이아웃 스타일',
                'layout' => '레이아웃',
                'layout_desc' => '회원 페이지에 적용할 레이아웃을 선택합니다.',
                'layout_none' => '미사용',
                'layout_basic' => '기본 레이아웃',
                'layout_sidebar_type' => '사이드바 레이아웃',
                'no_layouts_found' => 'layouts 폴더에 레이아웃이 없습니다. 레이아웃 폴더를 추가해주세요.',
                'skin' => '스킨',
                'skin_desc' => '회원 페이지에 적용할 스킨을 선택합니다.',
                'skin_default' => '회원 기본 스킨 (default)',
                'skin_modern' => '모던 스킨',
                'skin_classic' => '클래식 스킨',
                'colorset' => '컬러셋',
                'colorset_desc' => '스킨에 적용할 색상 테마를 선택합니다.',
                'colorset_default' => '기본',
                'colorset_blue' => '블루',
                'colorset_green' => '그린',
                'colorset_purple' => '퍼플',
                'mobile_layout' => '모바일 레이아웃',
                'mobile_layout_desc' => '모바일에서 사용할 레이아웃을 선택합니다.',
                'mobile_responsive' => 'PC와 동일한 반응형 레이아웃 사용',
                'mobile_skin' => '모바일 스킨',
                'mobile_skin_desc' => '모바일에서 사용할 스킨을 선택합니다.',
                'mobile_skin_responsive' => 'PC와 동일한 반응형 스킨 사용',
                'mobile_skin_mobile' => '모바일 전용 스킨',
                'form_style' => '폼 스타일',
                'form_style_desc' => '로그인/회원가입 폼의 스타일을 선택합니다.',
                'style_default' => '기본',
                'style_card' => '카드형',
                'style_minimal' => '미니멀',
                'login_background' => '로그인 페이지 배경',
                'login_background_desc' => '로그인 페이지의 배경 스타일',
                'bg_none' => '없음 (기본)',
                'bg_gradient' => '그라데이션',
                'bg_image' => '이미지',
                'bg_pattern' => '패턴',
                'register_layout' => '회원가입 페이지 레이아웃',
                'register_layout_desc' => '회원가입 폼의 레이아웃 형태',
                'layout_single' => '단일 페이지',
                'layout_steps' => '단계별',
                'layout_split' => '좌우 분할',
                'social_login' => '소셜 로그인 기능',
                'social_login_desc' => '로그인/회원가입 페이지에 소셜 로그인 버튼을 표시합니다.',
                'social_google' => 'Google 로그인',
                'social_line' => 'LINE 로그인',
                'social_kakao' => '카카오톡 로그인',
                'profile_style' => '마이페이지 스타일',
                'profile_style_desc' => '회원 마이페이지의 레이아웃 스타일',
                'profile_card' => '카드형',
                'profile_sidebar' => '사이드바형',
                'profile_tabs' => '탭형',

                // 스킨 추가
                'add_skin' => '신규 스킨 추가',
                'direct_register' => '직접 등록',
                'direct_register_desc' => '스킨 파일을 직접 업로드합니다',
                'marketplace' => '마켓플레이스로부터 구입',
                'marketplace_desc' => '다양한 스킨을 찾아보세요',
            ],
        ],

        // 회원 그룹
        'groups' => [
            'title' => '회원 그룹',
            'list' => '그룹 목록',
            'create' => '그룹 추가',
            'edit' => '그룹 수정',
            'fields' => [
                'name' => '그룹명',
                'description' => '설명',
                'discount_rate' => '할인율 (%)',
                'point_rate' => '적립률 (%)',
                'is_default' => '기본 그룹',
            ],
        ],

        // 적립금
        'points' => [
            'title' => '적립금 관리',
            'history' => '적립금 내역',
            'add' => '적립금 지급',
            'deduct' => '적립금 차감',
            'fields' => [
                'member' => '회원',
                'amount' => '금액',
                'reason' => '사유',
                'balance' => '잔액',
            ],
        ],
    ],
];
