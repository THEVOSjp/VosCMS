<?php
/**
 * RezlyX Member Skin Loader
 * 회원 스킨 로딩 및 렌더링을 담당하는 클래스
 */

namespace RzxLib\Core\Skin;

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
     * 현재 언어 감지 (GET > 쿠키 > 기본값)
     */
    private function detectLocale(): void
    {
        $validLocales = ['ko', 'en', 'ja'];

        if (!empty($_GET['lang']) && in_array($_GET['lang'], $validLocales)) {
            $this->currentLocale = $_GET['lang'];
        } elseif (!empty($_COOKIE['locale']) && in_array($_COOKIE['locale'], $validLocales)) {
            $this->currentLocale = $_COOKIE['locale'];
        } elseif (function_exists('current_locale')) {
            $locale = current_locale();
            if (in_array($locale, $validLocales)) {
                $this->currentLocale = $locale;
            }
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
        if (in_array($locale, ['ko', 'en', 'ja'])) {
            $this->currentLocale = $locale;
        }
        return $this;
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

        return $translations[$this->currentLocale] ?? $translations['ko'];
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
