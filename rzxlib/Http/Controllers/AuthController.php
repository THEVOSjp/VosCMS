<?php

declare(strict_types=1);

namespace RzxLib\Http\Controllers;

use RzxLib\Core\Http\Controller;
use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;
use RzxLib\Core\Auth\Auth;
use RzxLib\Core\Validation\Validator;
use RzxLib\Core\Validation\ValidationException;
use RzxLib\Core\Skin\MemberSkinLoader;
use RzxLib\Core\Helpers\ImageHelper;

/**
 * AuthController - 인증 (로그인, 회원가입, 비밀번호 찾기)
 *
 * @package RzxLib\Http\Controllers
 */
class AuthController extends Controller
{
    protected ?MemberSkinLoader $skinLoader = null;
    protected array $memberSettings = [];

    public function __construct()
    {
        $this->loadMemberSettings();
        $this->initSkinLoader();
    }

    /**
     * 회원 설정 로드
     */
    protected function loadMemberSettings(): void
    {
        try {
            $db = \RzxLib\Core\Database\DB::connection();
            $settings = $db->table('rzx_settings')
                ->where('key', 'LIKE', 'member_%')
                ->get();

            foreach ($settings as $row) {
                $this->memberSettings[$row['key']] = $row['value'];
            }
        } catch (\Exception $e) {
            // 설정 로드 실패 시 기본값 사용
        }

        // 기본값 설정
        $defaults = [
            'member_skin' => 'default',
            'member_social_login_enabled' => '0',
            'member_social_google' => '0',
            'member_social_line' => '0',
            'member_social_kakao' => '0',
        ];

        $this->memberSettings = array_merge($defaults, $this->memberSettings);
    }

    /**
     * 스킨 로더 초기화
     */
    protected function initSkinLoader(): void
    {
        $skinBasePath = BASE_PATH . '/skins/member';
        $skinName = $this->memberSettings['member_skin'] ?? 'default';

        if (is_dir($skinBasePath . '/' . $skinName)) {
            $this->skinLoader = new MemberSkinLoader($skinBasePath, $skinName);
        }
    }

    /**
     * 스킨 템플릿 렌더링 또는 기존 뷰 사용
     */
    protected function renderSkin(string $page, array $data = []): Response
    {
        // 소셜 로그인 정보 추가
        $data['socialProviders'] = $this->getEnabledSocialProviders();
        $data['memberSettings'] = $this->memberSettings;

        // 스킨 로더가 있고 해당 페이지가 스킨에 존재하면 스킨 사용
        if ($this->skinLoader && $this->skinLoader->pageExists($page)) {
            $html = $this->skinLoader->render($page, $data);
            return new Response($html);
        }

        // 스킨이 없으면 기존 뷰 사용
        $viewMap = [
            'login' => 'customer.login',
            'register' => 'customer.register',
            'mypage' => 'customer.mypage',
            'password_reset' => 'customer.password-reset',
        ];

        $viewName = $viewMap[$page] ?? 'customer.' . $page;
        return $this->view($viewName, $data);
    }

    /**
     * 활성화된 소셜 로그인 제공자 목록
     */
    protected function getEnabledSocialProviders(): array
    {
        $providers = [];

        if (($this->memberSettings['member_social_login_enabled'] ?? '0') === '1') {
            if (($this->memberSettings['member_social_google'] ?? '0') === '1') {
                $providers[] = 'google';
            }
            if (($this->memberSettings['member_social_line'] ?? '0') === '1') {
                $providers[] = 'line';
            }
            if (($this->memberSettings['member_social_kakao'] ?? '0') === '1') {
                $providers[] = 'kakao';
            }
        }

        return $providers;
    }

    /**
     * 로그인 폼
     */
    public function loginForm(Request $request): Response
    {
        // 이미 로그인된 경우
        if (Auth::check()) {
            return $this->redirect('/');
        }

        return $this->renderSkin('login', [
            'errors' => [],
            'oldInput' => [],
            'csrfToken' => csrf_token(),
            'registerUrl' => url('/auth/register'),
            'passwordResetUrl' => url('/auth/forgot-password'),
        ]);
    }

    /**
     * 로그인 처리
     */
    public function login(Request $request): Response
    {
        // 이미 로그인된 경우
        if (Auth::check()) {
            return $this->redirect('/');
        }

        $email = trim($request->input('email', ''));
        $password = $request->input('password', '');
        $remember = $request->has('remember');

        $errors = [];

        if (empty($email) || empty($password)) {
            $errors[] = __('auth.login.required');
        } else {
            $result = Auth::attempt($email, $password, $remember);

            if ($result['success']) {
                $redirect = $request->input('redirect', '/');
                return $this->redirect($redirect);
            } else {
                $errors[] = __('auth.login.' . ($result['error'] ?? 'failed'));
            }
        }

        return $this->renderSkin('login', [
            'errors' => $errors,
            'oldInput' => ['email' => $email],
            'csrfToken' => csrf_token(),
            'registerUrl' => url('/auth/register'),
            'passwordResetUrl' => url('/auth/forgot-password'),
        ]);
    }

    /**
     * 회원가입 폼
     */
    public function registerForm(Request $request): Response
    {
        // 이미 로그인된 경우
        if (Auth::check()) {
            return $this->redirect('/');
        }

        return $this->renderSkin('register', [
            'errors' => [],
            'oldInput' => [],
            'csrfToken' => csrf_token(),
            'terms' => $this->getTerms(),
            'loginUrl' => url('/auth/login'),
        ]);
    }

    /**
     * 회원가입 처리
     */
    public function register(Request $request): Response
    {
        // 이미 로그인된 경우
        if (Auth::check()) {
            return $this->redirect('/');
        }

        $errors = [];
        $formData = [
            'name' => trim($request->input('name', '')),
            'email' => trim($request->input('email', '')),
            'phone' => trim($request->input('phone', '')),
        ];

        $password = $request->input('password', '');
        $passwordConfirm = $request->input('password_confirm', '');
        $agreeTerms = $request->has('agree_terms');

        // 유효성 검사
        if (empty($formData['name'])) {
            $errors[] = __('validation.required', ['attribute' => __('auth.register.name')]);
        } elseif (empty($formData['email'])) {
            $errors[] = __('validation.required', ['attribute' => __('auth.register.email')]);
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('validation.email', ['attribute' => __('auth.register.email')]);
        } elseif (empty($password)) {
            $errors[] = __('validation.required', ['attribute' => __('auth.register.password')]);
        } elseif (strlen($password) < 8) {
            $errors[] = __('validation.min.string', ['attribute' => __('auth.register.password'), 'min' => 8]);
        } elseif ($password !== $passwordConfirm) {
            $errors[] = __('validation.confirmed', ['attribute' => __('auth.register.password')]);
        } elseif (!$agreeTerms) {
            $errors[] = __('validation.accepted', ['attribute' => __('common.terms')]);
        }

        if (empty($errors)) {
            // 회원 데이터 준비
            $userData = [
                'name' => $formData['name'],
                'email' => $formData['email'],
                'phone' => $formData['phone'] ?: null,
                'password' => $password,
            ];

            // 추가 필드 처리 (동적 필드)
            $optionalFields = ['birth_date', 'gender', 'company', 'blog'];
            foreach ($optionalFields as $field) {
                $value = trim($request->input($field, ''));
                if (!empty($value)) {
                    $userData[$field] = $value;
                }
            }

            $result = Auth::register($userData);

            if ($result['success']) {
                $userId = $result['user_id'] ?? $result['id'] ?? null;

                // 프로필 사진 처리 (크롭된 Base64 이미지)
                $croppedPhoto = $request->input('cropped_profile_photo', '');
                if (!empty($croppedPhoto) && str_starts_with($croppedPhoto, 'data:image/') && $userId) {
                    try {
                        $imageHelper = new ImageHelper();
                        $imageResult = $imageHelper->saveProfileImage($croppedPhoto, $userId);

                        if ($imageResult['success']) {
                            // 프로필 이미지 경로 저장
                            Auth::updateProfile($userId, [
                                'profile_photo' => $imageResult['relative_path'],
                            ]);
                        }
                    } catch (\Exception $e) {
                        // 이미지 저장 실패 시 로그만 남기고 계속 진행
                        error_log('[AuthController] Profile image save failed: ' . $e->getMessage());
                    }
                }

                // 회원가입 성공 - 로그인 페이지로 리다이렉트
                return $this->redirect('/auth/login')
                    ->withSuccess(__('auth.register.success'));
            } else {
                $errors[] = $result['error'] ?? __('auth.register.error');
            }
        }

        return $this->renderSkin('register', [
            'errors' => $errors,
            'oldInput' => $formData,
            'csrfToken' => csrf_token(),
            'terms' => $this->getTerms(),
            'loginUrl' => url('/auth/login'),
        ]);
    }

    /**
     * 비밀번호 찾기 폼
     */
    public function forgotPasswordForm(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect('/');
        }

        return $this->renderSkin('password_reset', [
            'step' => 'email',
            'errors' => [],
            'email' => '',
            'csrfToken' => csrf_token(),
            'loginUrl' => url('/auth/login'),
        ]);
    }

    /**
     * 비밀번호 찾기 처리
     */
    public function forgotPassword(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect('/');
        }

        $email = trim($request->input('email', ''));
        $errors = [];

        if (empty($email)) {
            $errors[] = __('validation.required', ['attribute' => __('auth.login.email')]);
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('validation.email', ['attribute' => __('auth.login.email')]);
        } else {
            // 비밀번호 재설정 이메일 발송 로직
            $result = Auth::sendPasswordResetEmail($email);

            if ($result['success']) {
                return $this->renderSkin('password_reset', [
                    'step' => 'sent',
                    'errors' => [],
                    'email' => $email,
                    'csrfToken' => csrf_token(),
                    'loginUrl' => url('/auth/login'),
                ]);
            } else {
                $errors[] = $result['error'] ?? __('auth.password_reset.error');
            }
        }

        return $this->renderSkin('password_reset', [
            'step' => 'email',
            'errors' => $errors,
            'email' => $email,
            'csrfToken' => csrf_token(),
            'loginUrl' => url('/auth/login'),
        ]);
    }

    /**
     * 비밀번호 재설정 폼
     */
    public function resetPasswordForm(Request $request, string $token): Response
    {
        if (Auth::check()) {
            return $this->redirect('/');
        }

        // 토큰 유효성 확인
        $valid = Auth::verifyPasswordResetToken($token);

        if (!$valid) {
            return $this->redirect('/auth/forgot-password')
                ->withError(__('auth.password_reset.invalid_token'));
        }

        return $this->renderSkin('password_reset', [
            'step' => 'reset',
            'errors' => [],
            'token' => $token,
            'csrfToken' => csrf_token(),
            'loginUrl' => url('/auth/login'),
        ]);
    }

    /**
     * 비밀번호 재설정 처리
     */
    public function resetPassword(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect('/');
        }

        $token = $request->input('token', '');
        $password = $request->input('password', '');
        $passwordConfirm = $request->input('password_confirm', '');
        $errors = [];

        if (empty($password)) {
            $errors[] = __('validation.required', ['attribute' => __('auth.register.password')]);
        } elseif (strlen($password) < 8) {
            $errors[] = __('validation.min.string', ['attribute' => __('auth.register.password'), 'min' => 8]);
        } elseif ($password !== $passwordConfirm) {
            $errors[] = __('validation.confirmed', ['attribute' => __('auth.register.password')]);
        }

        if (empty($errors)) {
            $result = Auth::resetPassword($token, $password);

            if ($result['success']) {
                return $this->renderSkin('password_reset', [
                    'step' => 'complete',
                    'errors' => [],
                    'csrfToken' => csrf_token(),
                    'loginUrl' => url('/auth/login'),
                ]);
            } else {
                $errors[] = $result['error'] ?? __('auth.password_reset.error');
            }
        }

        return $this->renderSkin('password_reset', [
            'step' => 'reset',
            'errors' => $errors,
            'token' => $token,
            'csrfToken' => csrf_token(),
            'loginUrl' => url('/auth/login'),
        ]);
    }

    /**
     * 로그아웃
     */
    public function logout(Request $request): Response
    {
        Auth::logout();
        return $this->redirect('/');
    }

    /**
     * 약관 정보 가져오기 (다국어 지원)
     */
    protected function getTerms(): array
    {
        $terms = [];
        $currentLocale = current_locale();

        for ($i = 1; $i <= 5; $i++) {
            $consent = $this->memberSettings["member_term_{$i}_consent"] ?? 'disabled';

            // 비활성화된 약관은 건너뛰기
            if ($consent === 'disabled') {
                continue;
            }

            // db_trans()를 사용하여 번역 조회, 없으면 기본 설정값 사용
            $defaultTitle = $this->memberSettings["member_term_{$i}_title"] ?? '';
            $defaultContent = $this->memberSettings["member_term_{$i}_content"] ?? '';

            $title = db_trans("term.{$i}.title", $currentLocale, $defaultTitle);
            $content = db_trans("term.{$i}.content", $currentLocale, $defaultContent);

            if (!empty($title)) {
                $terms[] = [
                    'id' => $i,
                    'title' => $title,
                    'content' => $content,
                    'required' => $consent === 'required',
                ];
            }
        }

        return $terms;
    }
}
