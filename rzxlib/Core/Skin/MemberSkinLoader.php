<?php
/**
 * RezlyX Member Skin Loader
 * 회원 스킨 로딩 및 렌더링을 담당하는 클래스
 */

namespace RzxLib\Core\Skin;

use RzxLib\Core\Modules\LanguageModule;
use RzxLib\Core\Modules\LogoModule;
use RzxLib\Core\Modules\SocialLoginModule;

class MemberSkinLoader
{
    /** @var string 스킨 기본 경로 */
    private string $skinBasePath;

    /** @var string 현재 스킨명 */
    private string $currentSkin;

    /** @var array 스킨 설정 */
    private array $config = [];

    /** @var array 현재 컬러셋 */
    private array $colorset = [];

    /** @var array 번역 데이터 */
    private array $translations = [];

    /** @var string 현재 언어 */
    private string $currentLocale = 'ko';

    /** @var array DB 사이트 설정 (모듈에서 사용) */
    private array $siteSettings = [];

    /** @var array 모듈에서 수집한 데이터 캐시 */
    private ?array $moduleData = null;

    /**
     * 생성자
     *
     * @param string $skinBasePath 스킨 기본 경로
     * @param string $skin 스킨명 (기본: 'default')
     */
    public function __construct(string $skinBasePath, string $skin = 'default')
    {
        $this->skinBasePath = rtrim($skinBasePath, '/\\');
        $this->currentSkin = $skin;
        $this->loadConfig();
        $this->detectLocale();
    }

    /**
     * 사이트 설정 주입 (모듈 데이터 자동 수집에 사용)
     *
     * @param array $siteSettings DB의 rzx_settings 데이터
     * @return self
     */
    public function setSiteSettings(array $siteSettings): self
    {
        $this->siteSettings = $siteSettings;
        $this->moduleData = null; // 캐시 무효화
        return $this;
    }

    /**
     * 모듈 데이터 수집 (언어, 로고, 소셜 로그인)
     * siteSettings가 설정된 경우에만 동작
     *
     * @return array
     */
    private function collectModuleData(): array
    {
        if ($this->moduleData !== null) {
            return $this->moduleData;
        }

        $this->moduleData = [];

        if (empty($this->siteSettings)) {
            return $this->moduleData;
        }

        // 모듈 파일 로드
        $modulePath = dirname(__DIR__) . '/Modules';
        if (file_exists($modulePath . '/LanguageModule.php')) {
            require_once $modulePath . '/LanguageModule.php';
            $langData = LanguageModule::getData($this->siteSettings, $this->currentLocale);
            $this->moduleData['languages'] = $langData['languages'];
            $this->moduleData['allLanguages'] = $langData['allLanguages'];
            $this->moduleData['supportedCodes'] = $langData['supportedCodes'];
            $this->moduleData['currentLocale'] = $langData['currentLocale'];
            $this->moduleData['defaultLocale'] = $langData['defaultLocale'];
            $this->moduleData['currentLangInfo'] = $langData['currentLangInfo'];
        }

        if (file_exists($modulePath . '/LogoModule.php')) {
            require_once $modulePath . '/LogoModule.php';
            $logoData = LogoModule::getData($this->siteSettings, 'RezlyX');
            $this->moduleData['siteName'] = $logoData['siteName'];
            $this->moduleData['logoType'] = $logoData['logoType'];
            $this->moduleData['logoImage'] = $logoData['logoImage'];
        }

        if (file_exists($modulePath . '/SocialLoginModule.php')) {
            require_once $modulePath . '/SocialLoginModule.php';
            $socialData = SocialLoginModule::getData($this->siteSettings);
            $this->moduleData['socialProviders'] = $socialData['socialProviders'];
            $this->moduleData['socialEnabled'] = $socialData['socialEnabled'];
        }

        return $this->moduleData;
    }

    /**
     * 현재 언어 감지 (GET > 쿠키 > 기본값)
     */
    private function detectLocale(): void
    {
        // Translator 클래스가 DB 기반으로 유효 언어를 관리하므로, 여기서는 값만 읽음
        if (!empty($_GET['lang'])) {
            $this->currentLocale = $_GET['lang'];
        } elseif (!empty($_COOKIE['locale'])) {
            $this->currentLocale = $_COOKIE['locale'];
        } elseif (function_exists('current_locale')) {
            $this->currentLocale = \current_locale();
        }
    }

    /**
     * 언어 설정
     *
     * @param string $locale 언어 코드
     * @return self
     */
    public function setLocale(string $locale): self
    {
        $this->currentLocale = $locale;
        return $this;
    }

    /**
     * 로케일 새로고침 (렌더링 직전에 호출, Translator 초기화 후 정확한 로케일 감지)
     */
    private function refreshLocale(): void
    {
        // 1. 세션에서 직접 확인 (가장 우선순위 높음)
        if (isset($_SESSION['locale'])) {
            $this->currentLocale = $_SESSION['locale'];
        }
        // 2. 쿠키에서 확인
        elseif (!empty($_COOKIE['locale'])) {
            $this->currentLocale = $_COOKIE['locale'];
        }
        // 3. Translator에서 확인 (폴백)
        elseif (function_exists('current_locale')) {
            $this->currentLocale = \current_locale();
        }
    }

    /**
     * 스킨 설정 로드
     */
    private function loadConfig(): void
    {
        $configPath = $this->getSkinPath() . '/config.php';

        if (file_exists($configPath)) {
            $this->config = require $configPath;
        } else {
            $this->config = [
                'name' => $this->currentSkin,
                'version' => '1.0.0',
                'colorsets' => [
                    'default' => [
                        'primary' => '#3B82F6',
                        'secondary' => '#6B7280',
                        'accent' => '#10B981',
                        'background' => '#FFFFFF',
                        'text' => '#1F2937',
                    ]
                ],
            ];
        }

        // 기본 컬러셋 설정
        $this->colorset = $this->config['colorsets']['default'] ?? [];
    }

    /**
     * 스킨 경로 반환
     *
     * @return string
     */
    public function getSkinPath(): string
    {
        return $this->skinBasePath . '/' . $this->currentSkin;
    }

    /**
     * 스킨 변경
     *
     * @param string $skin 새 스킨명
     * @return self
     */
    public function setSkin(string $skin): self
    {
        $this->currentSkin = $skin;
        $this->loadConfig();
        return $this;
    }

    /**
     * 컬러셋 변경
     *
     * @param string $colorsetName 컬러셋명
     * @return self
     */
    public function setColorset(string $colorsetName): self
    {
        if (isset($this->config['colorsets'][$colorsetName])) {
            $this->colorset = $this->config['colorsets'][$colorsetName];
        }
        return $this;
    }

    /**
     * 번역 데이터 설정
     *
     * @param array $translations 번역 데이터
     * @return self
     */
    public function setTranslations(array $translations): self
    {
        $this->translations = $translations;
        return $this;
    }

    /**
     * 스킨 설정 반환
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 현재 컬러셋 반환
     *
     * @return array
     */
    public function getColorset(): array
    {
        return $this->colorset;
    }

    /**
     * 사용 가능한 스킨 목록 반환
     *
     * @return array
     */
    public function getAvailableSkins(): array
    {
        $skins = [];
        $dirs = glob($this->skinBasePath . '/*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $skinName = basename($dir);
            $configPath = $dir . '/config.php';

            if (file_exists($configPath)) {
                $config = require $configPath;
                $skins[$skinName] = [
                    'name' => $config['name'] ?? $skinName,
                    'version' => $config['version'] ?? '1.0.0',
                    'author' => $config['author'] ?? 'Unknown',
                    'description' => $config['description'] ?? '',
                    'preview' => file_exists($dir . '/preview.png')
                        ? $dir . '/preview.png'
                        : null,
                    'thumbnail' => file_exists($dir . '/thumbnail.png')
                        ? $dir . '/thumbnail.png'
                        : null,
                    'colorsets' => array_keys($config['colorsets'] ?? []),
                ];
            }
        }

        return $skins;
    }

    /**
     * 페이지 렌더링
     *
     * @param string $page 페이지명 (login, register, mypage, password_reset)
     * @param array $data 템플릿에 전달할 데이터
     * @return string 렌더링된 HTML
     */
    public function render(string $page, array $data = []): string
    {
        $templatePath = $this->getSkinPath() . '/' . $page . '.php';

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template not found: {$page}");
        }

        // 렌더링 직전에 로케일 다시 확인 (Translator가 이제 초기화되었을 수 있으므로)
        $this->refreshLocale();

        // 모듈 데이터를 기본값으로, render() 호출 시 전달된 $data가 우선
        $data = $this->mergeModuleData($data);

        // 템플릿에 전달할 변수들 설정
        $data['config'] = $this->config;
        $data['colorset'] = $this->colorset;
        $data['translations'] = array_merge($this->getDefaultTranslations(), $this->translations);

        // 출력 버퍼링으로 템플릿 렌더링
        ob_start();
        extract($data);
        include $templatePath;
        return ob_get_clean();
    }

    /**
     * 컴포넌트 렌더링
     *
     * @param string $component 컴포넌트명
     * @param array $data 컴포넌트에 전달할 데이터
     * @return string 렌더링된 HTML
     */
    public function renderComponent(string $component, array $data = []): string
    {
        $componentPath = $this->getSkinPath() . '/components/' . $component . '.php';

        if (!file_exists($componentPath)) {
            return '';
        }

        // 렌더링 직전에 로케일 다시 확인
        $this->refreshLocale();

        // 모듈 데이터를 기본값으로, 전달된 $data가 우선
        $data = $this->mergeModuleData($data);

        $data['config'] = $this->config;
        $data['colorset'] = $this->colorset;
        $data['translations'] = array_merge($this->getDefaultTranslations(), $this->translations);

        ob_start();
        extract($data);
        include $componentPath;
        return ob_get_clean();
    }

    /**
     * 스킨 존재 여부 확인
     *
     * @param string $skin 스킨명
     * @return bool
     */
    public function skinExists(string $skin): bool
    {
        return is_dir($this->skinBasePath . '/' . $skin);
    }

    /**
     * 모듈 데이터를 기본값으로 병합 (명시적으로 전달된 $data가 우선)
     *
     * @param array $data 명시적으로 전달된 데이터
     * @return array 모듈 기본값 + 명시적 데이터 병합 결과
     */
    private function mergeModuleData(array $data): array
    {
        $moduleData = $this->collectModuleData();

        // 모듈 데이터를 기본값으로 설정 (명시적 $data가 우선)
        foreach ($moduleData as $key => $value) {
            if (!isset($data[$key])) {
                $data[$key] = $value;
            }
        }

        // siteSettings를 스킨 템플릿에 전달 (공용 컴포넌트에서 사용)
        if (!isset($data['siteSettings'])) {
            $data['siteSettings'] = $this->siteSettings;
        }

        // 모듈이 없어도 최소한의 기본값 보장
        if (!isset($data['baseUrl'])) {
            $data['baseUrl'] = '';
        }
        if (!isset($data['siteName'])) {
            $data['siteName'] = 'RezlyX';
        }
        if (!isset($data['currentLocale'])) {
            $data['currentLocale'] = $this->currentLocale;
        }

        return $data;
    }

    /**
     * 페이지 템플릿 존재 여부 확인
     *
     * @param string $page 페이지명
     * @return bool
     */
    public function pageExists(string $page): bool
    {
        return file_exists($this->getSkinPath() . '/' . $page . '.php');
    }

    /**
     * 기본 번역 데이터 반환 (현재 언어에 맞게)
     *
     * @return array
     */
    private function getDefaultTranslations(): array
    {
        $translations = [
            'ko' => [
                // 로그인
                'login_title' => '로그인',
                'login_subtitle' => '계정에 로그인하세요',
                'login_button' => '로그인',
                'email' => '이메일',
                'email_placeholder' => 'example@email.com',
                'password' => '비밀번호',
                'password_placeholder' => '비밀번호를 입력하세요',
                'remember_me' => '로그인 유지',
                'forgot_password' => '비밀번호를 잊으셨나요?',
                'no_account' => '아직 회원이 아니신가요?',
                'register_link' => '회원가입',
                // 회원가입
                'register_title' => '회원가입',
                'register_subtitle' => '새 계정을 만드세요',
                'register_button' => '회원가입',
                'name' => '이름',
                'name_placeholder' => '홍길동',
                'password_confirm' => '비밀번호 확인',
                'password_confirm_placeholder' => '비밀번호를 다시 입력하세요',
                'password_hint' => '영문, 숫자를 포함하여 8자 이상',
                'phone' => '전화번호',
                'phone_placeholder' => '010-0000-0000',
                'birth_date' => '생년월일',
                'gender' => '성별',
                'gender_male' => '남성',
                'gender_female' => '여성',
                'gender_other' => '기타',
                'company' => '회사/소속',
                'company_placeholder' => '회사명을 입력하세요',
                'blog' => '블로그/웹사이트',
                'blog_placeholder' => 'https://example.com',
                'profile_photo' => '프로필 사진',
                'profile_photo_hint' => '최대 2MB, JPG/PNG/GIF',
                'select_photo' => '사진 선택',
                'change_photo' => '사진 변경',
                'edit_image' => '이미지 편집',
                'select_image' => '이미지 선택',
                'drag_drop_image' => '또는 이미지를 여기에 드래그하세요',
                'zoom_in' => '확대',
                'zoom_out' => '축소',
                'rotate_left' => '왼쪽 회전',
                'rotate_right' => '오른쪽 회전',
                'reset' => '초기화',
                'cancel' => '취소',
                'apply' => '적용',
                'file_too_large' => '파일 크기가 너무 큽니다. 최대 5MB까지 업로드 가능합니다.',
                'invalid_file_type' => '지원하지 않는 파일 형식입니다. JPG, PNG, GIF, WebP만 가능합니다.',
                'password_mismatch' => '비밀번호가 일치하지 않습니다.',
                'terms_agreement' => '약관 동의',
                'agree_all' => '전체 동의',
                'has_account' => '이미 계정이 있으신가요?',
                'login_link' => '로그인',
                // 마이페이지
                'member_since' => '가입일',
                'edit_profile' => '프로필 수정',
                'my_posts' => '내 게시글',
                'my_comments' => '내 댓글',
                'scraps' => '스크랩',
                'bookmarks' => '북마크',
                'profile_settings' => '프로필 설정',
                'change_password' => '비밀번호 변경',
                'logout' => '로그아웃',
                // 비밀번호 찾기
                'password_reset_title' => '비밀번호 찾기',
                'password_reset_email_desc' => '가입 시 사용한 이메일을 입력하세요',
                'send_code' => '인증 메일 발송',
                'new_password' => '새 비밀번호',
                'new_password_placeholder' => '8자 이상 입력하세요',
                'reset_password' => '비밀번호 변경',
                'password_changed' => '비밀번호가 변경되었습니다',
                'go_to_login' => '로그인하기',
                'back_to_login' => '로그인으로 돌아가기',
                'back_to_home' => '홈으로 돌아가기',
                // 소셜 로그인
                'or_continue_with' => '또는',
            ],
            'en' => [
                // Login
                'login_title' => 'Login',
                'login_subtitle' => 'Sign in to your account',
                'login_button' => 'Login',
                'email' => 'Email',
                'email_placeholder' => 'example@email.com',
                'password' => 'Password',
                'password_placeholder' => 'Enter your password',
                'remember_me' => 'Remember me',
                'forgot_password' => 'Forgot password?',
                'no_account' => "Don't have an account?",
                'register_link' => 'Sign up',
                // Register
                'register_title' => 'Sign Up',
                'register_subtitle' => 'Create a new account',
                'register_button' => 'Sign Up',
                'name' => 'Name',
                'name_placeholder' => 'John Doe',
                'password_confirm' => 'Confirm Password',
                'password_confirm_placeholder' => 'Re-enter your password',
                'password_hint' => 'At least 8 characters with letters and numbers',
                'phone' => 'Phone',
                'phone_placeholder' => '010-0000-0000',
                'birth_date' => 'Date of Birth',
                'gender' => 'Gender',
                'gender_male' => 'Male',
                'gender_female' => 'Female',
                'gender_other' => 'Other',
                'company' => 'Company',
                'company_placeholder' => 'Enter company name',
                'blog' => 'Blog/Website',
                'blog_placeholder' => 'https://example.com',
                'profile_photo' => 'Profile Photo',
                'profile_photo_hint' => 'Max 2MB, JPG/PNG/GIF',
                'select_photo' => 'Select Photo',
                'change_photo' => 'Change Photo',
                'edit_image' => 'Edit Image',
                'select_image' => 'Select Image',
                'drag_drop_image' => 'Or drag and drop an image here',
                'zoom_in' => 'Zoom In',
                'zoom_out' => 'Zoom Out',
                'rotate_left' => 'Rotate Left',
                'rotate_right' => 'Rotate Right',
                'reset' => 'Reset',
                'cancel' => 'Cancel',
                'apply' => 'Apply',
                'file_too_large' => 'File is too large. Maximum 5MB allowed.',
                'invalid_file_type' => 'Unsupported file type. JPG, PNG, GIF, WebP only.',
                'password_mismatch' => 'Passwords do not match.',
                'terms_agreement' => 'Terms Agreement',
                'agree_all' => 'Agree to all',
                'has_account' => 'Already have an account?',
                'login_link' => 'Login',
                // My Page
                'member_since' => 'Member since',
                'edit_profile' => 'Edit Profile',
                'my_posts' => 'My Posts',
                'my_comments' => 'My Comments',
                'scraps' => 'Scraps',
                'bookmarks' => 'Bookmarks',
                'profile_settings' => 'Profile Settings',
                'change_password' => 'Change Password',
                'logout' => 'Logout',
                // Password Reset
                'password_reset_title' => 'Reset Password',
                'password_reset_email_desc' => 'Enter your email address',
                'send_code' => 'Send Reset Email',
                'new_password' => 'New Password',
                'new_password_placeholder' => 'At least 8 characters',
                'reset_password' => 'Reset Password',
                'password_changed' => 'Password has been changed',
                'go_to_login' => 'Go to Login',
                'back_to_login' => 'Back to Login',
                'back_to_home' => 'Back to Home',
                // Social Login
                'or_continue_with' => 'Or continue with',
            ],
            'ja' => [
                // ログイン
                'login_title' => 'ログイン',
                'login_subtitle' => 'アカウントにログイン',
                'login_button' => 'ログイン',
                'email' => 'メールアドレス',
                'email_placeholder' => 'example@email.com',
                'password' => 'パスワード',
                'password_placeholder' => 'パスワードを入力',
                'remember_me' => 'ログイン状態を保持',
                'forgot_password' => 'パスワードをお忘れですか？',
                'no_account' => 'アカウントをお持ちでないですか？',
                'register_link' => '新規登録',
                // 新規登録
                'register_title' => '新規登録',
                'register_subtitle' => '新しいアカウントを作成',
                'register_button' => '登録',
                'name' => '名前',
                'name_placeholder' => '山田太郎',
                'password_confirm' => 'パスワード確認',
                'password_confirm_placeholder' => 'パスワードを再入力',
                'password_hint' => '英数字を含む8文字以上',
                'phone' => '電話番号',
                'phone_placeholder' => '090-0000-0000',
                'birth_date' => '生年月日',
                'gender' => '性別',
                'gender_male' => '男性',
                'gender_female' => '女性',
                'gender_other' => 'その他',
                'company' => '会社/所属',
                'company_placeholder' => '会社名を入力',
                'blog' => 'ブログ/ウェブサイト',
                'blog_placeholder' => 'https://example.com',
                'profile_photo' => 'プロフィール写真',
                'profile_photo_hint' => '最大2MB、JPG/PNG/GIF',
                'select_photo' => '写真を選択',
                'change_photo' => '写真を変更',
                'edit_image' => '画像を編集',
                'select_image' => '画像を選択',
                'drag_drop_image' => 'または画像をここにドラッグ＆ドロップ',
                'zoom_in' => '拡大',
                'zoom_out' => '縮小',
                'rotate_left' => '左回転',
                'rotate_right' => '右回転',
                'reset' => 'リセット',
                'cancel' => 'キャンセル',
                'apply' => '適用',
                'file_too_large' => 'ファイルサイズが大きすぎます。最大5MBまでアップロード可能です。',
                'invalid_file_type' => 'サポートされていないファイル形式です。JPG、PNG、GIF、WebPのみ対応しています。',
                'password_mismatch' => 'パスワードが一致しません。',
                'terms_agreement' => '利用規約',
                'agree_all' => 'すべてに同意',
                'has_account' => 'すでにアカウントをお持ちですか？',
                'login_link' => 'ログイン',
                // マイページ
                'member_since' => '登録日',
                'edit_profile' => 'プロフィール編集',
                'my_posts' => '投稿一覧',
                'my_comments' => 'コメント一覧',
                'scraps' => 'スクラップ',
                'bookmarks' => 'ブックマーク',
                'profile_settings' => 'プロフィール設定',
                'change_password' => 'パスワード変更',
                'logout' => 'ログアウト',
                // パスワードリセット
                'password_reset_title' => 'パスワードリセット',
                'password_reset_email_desc' => 'メールアドレスを入力してください',
                'send_code' => 'リセットメール送信',
                'new_password' => '新しいパスワード',
                'new_password_placeholder' => '8文字以上',
                'reset_password' => 'パスワード変更',
                'password_changed' => 'パスワードが変更されました',
                'go_to_login' => 'ログインへ',
                'back_to_login' => 'ログインに戻る',
                'back_to_home' => 'ホームに戻る',
                // ソーシャルログイン
                'or_continue_with' => 'または',
            ],
        ];

        // 1. 회원가입/마이페이지/비번재설정 등 — 기존 ko/en/ja 폴백 유지
        $localized = $translations[$this->currentLocale] ?? $translations['en'] ?? $translations['ko'];

        // 2. 로그인 키 — auth.login.* (13개국어 완비) 에서 직접 조회
        //    함수 미정의 환경(예: 일부 단일 페이지) 에서는 기존 폴백 그대로 사용
        if (function_exists('__')) {
            $loginKeys = [
                'login_title'         => __('auth.login.title'),
                'login_subtitle'      => __('auth.login.description'),
                'login_button'        => __('auth.login.submit'),
                'email'               => __('auth.login.email'),
                'email_placeholder'   => __('auth.login.email_placeholder'),
                'password'            => __('auth.login.password'),
                'password_placeholder'=> __('auth.login.password_placeholder'),
                'remember_me'         => __('auth.login.remember'),
                'forgot_password'     => __('auth.login.forgot'),
                'no_account'          => __('auth.login.no_account'),
                'register_link'       => __('auth.login.register_link'),
                'back_to_home'        => __('auth.login.back_home'),
            ];
            // __() 가 키를 못 찾아 원래 키 문자열을 반환한 경우 (마침표 포함) 폴백 유지
            foreach ($loginKeys as $skinKey => $value) {
                if (is_string($value) && strpos($value, 'auth.login.') !== false) {
                    continue; // 번역 미반영 → 폴백 사용
                }
                $localized[$skinKey] = $value;
            }
        }

        return $localized;
    }

    /**
     * CSS 변수 스타일 생성
     *
     * @return string
     */
    public function getCssVariables(): string
    {
        $css = ":root {\n";
        foreach ($this->colorset as $key => $value) {
            $css .= "    --skin-{$key}: {$value};\n";
        }
        $css .= "}";
        return $css;
    }
}
