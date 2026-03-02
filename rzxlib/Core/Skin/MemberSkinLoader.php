<?php
/**
 * RezlyX Member Skin Loader
 * 회원 스킨 로딩 및 렌더링을 담당하는 클래스
 */

namespace RezlyX\Core\Skin;

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
     * 기본 번역 데이터 반환
     *
     * @return array
     */
    private function getDefaultTranslations(): array
    {
        return [
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
            'password_reset_code_desc' => '이메일로 전송된 인증 코드를 입력하세요',
            'password_reset_new_desc' => '새 비밀번호를 입력하세요',
            'password_reset_complete_desc' => '비밀번호가 성공적으로 변경되었습니다',
            'send_code' => '인증 코드 발송',
            'verification_code' => '인증 코드',
            'code_sent_to' => '코드가 발송되었습니다:',
            'verify_code' => '코드 확인',
            'resend_code' => '코드 재발송',
            'new_password' => '새 비밀번호',
            'new_password_placeholder' => '8자 이상 입력하세요',
            'reset_password' => '비밀번호 변경',
            'password_changed' => '비밀번호가 변경되었습니다',
            'go_to_login' => '로그인하기',
            'back_to_login' => '로그인으로 돌아가기',

            // 소셜 로그인
            'or_continue_with' => '또는',
        ];
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
