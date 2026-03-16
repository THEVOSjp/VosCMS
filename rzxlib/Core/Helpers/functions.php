<?php
/**
 * RezlyX Global Helper Functions
 *
 * @package RzxLib\Core\Helpers
 */

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}

if (!function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     */
    function config(string $key, mixed $default = null): mixed
    {
        return \RzxLib\Core\Application::getInstance()->config($key, $default);
    }
}

if (!function_exists('app')) {
    /**
     * Get the application instance or a service.
     */
    function app(?string $abstract = null): mixed
    {
        $instance = \RzxLib\Core\Application::getInstance();

        if ($abstract === null) {
            return $instance;
        }

        return $instance->make($abstract);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the path to the base of the installation.
     */
    function base_path(string $path = ''): string
    {
        return app()->basePath($path);
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     */
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     */
    function public_path(string $path = ''): string
    {
        return base_path('public' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('resource_path')) {
    /**
     * Get the path to the resources folder.
     */
    function resource_path(string $path = ''): string
    {
        return base_path('resources' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('view')) {
    /**
     * Get the evaluated view contents.
     */
    function view(string $view, array $data = []): string
    {
        return app('view')->render($view, $data);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to a given URL.
     */
    function redirect(string $url, int $status = 302): void
    {
        header("Location: {$url}", true, $status);
        exit;
    }
}

if (!function_exists('url')) {
    /**
     * Generate a url for the application.
     */
    function url(string $path = '', array $params = []): string
    {
        $baseUrl = rtrim(config('app.url', ''), '/');
        $url = $baseUrl . '/' . ltrim($path, '/');

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }
}

if (!function_exists('asset')) {
    /**
     * Generate an asset path for the application.
     */
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('route')) {
    /**
     * Generate a URL for a named route.
     */
    function route(string $name, array $params = []): string
    {
        return app('router')->route($name, $params);
    }
}

if (!function_exists('__')) {
    /**
     * Translate the given message.
     */
    function __(string $key, array $replace = [], ?string $locale = null): string
    {
        // Application이 초기화된 경우 app('translator') 사용
        try {
            if (class_exists(\RzxLib\Core\Application::class)) {
                $app = \RzxLib\Core\Application::getInstance();
                if ($app && $app->has('translator')) {
                    return $app->make('translator')->get($key, $replace, $locale);
                }
            }
        } catch (\Throwable $e) {
            // Application이 없으면 Translator 클래스 직접 사용
        }

        // Translator 클래스 직접 사용
        if (class_exists(\RzxLib\Core\I18n\Translator::class)) {
            return \RzxLib\Core\I18n\Translator::get($key, $replace, $locale);
        }

        // 번역을 찾을 수 없으면 키 반환
        return $key;
    }
}

if (!function_exists('trans')) {
    /**
     * Alias for __()
     */
    function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        return __($key, $replace, $locale);
    }
}

if (!function_exists('current_locale')) {
    /**
     * Get current locale.
     */
    function current_locale(): string
    {
        if (class_exists(\RzxLib\Core\I18n\Translator::class)) {
            return \RzxLib\Core\I18n\Translator::getLocale();
        }
        return 'ko';
    }
}

if (!function_exists('session')) {
    /**
     * Get / set the specified session value.
     */
    function session(?string $key = null, mixed $default = null): mixed
    {
        $session = app('session');

        if ($key === null) {
            return $session;
        }

        return $session->get($key, $default);
    }
}

if (!function_exists('auth')) {
    /**
     * Get the auth instance.
     */
    function auth(?string $guard = null): \RzxLib\Core\Auth\AuthManager
    {
        $auth = app('auth');

        if ($guard !== null) {
            return $auth->guard($guard);
        }

        return $auth;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token value.
     */
    function csrf_token(): string
    {
        return session()->token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a CSRF token form field.
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('old')) {
    /**
     * Retrieve an old input item.
     */
    function old(string $key, mixed $default = null): mixed
    {
        return session()->getOldInput($key, $default);
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML entities in a string.
     */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('now')) {
    /**
     * Create a new DateTime instance for the current time.
     */
    function now(?string $tz = null): \DateTimeImmutable
    {
        $tz = $tz ?? config('app.timezone', 'UTC');
        return new \DateTimeImmutable('now', new \DateTimeZone($tz));
    }
}

if (!function_exists('logger')) {
    /**
     * Log a debug message to the logs.
     */
    function logger(?string $message = null, array $context = []): ?\Psr\Log\LoggerInterface
    {
        $log = app('log');

        if ($message === null) {
            return $log;
        }

        $log->debug($message, $context);
        return null;
    }
}

if (!function_exists('abort')) {
    /**
     * Throw an HttpException with the given data.
     */
    function abort(int $code, string $message = ''): never
    {
        throw new \RzxLib\Core\Exceptions\HttpException($code, $message);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump the passed variables and end the script.
     */
    function dd(mixed ...$vars): never
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        exit(1);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump the passed variables without ending the script.
     */
    function dump(mixed ...$vars): void
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
    }
}

if (!function_exists('db')) {
    /**
     * Get the database manager instance or a connection.
     */
    function db(?string $connection = null): \RzxLib\Core\Database\DatabaseManager|\RzxLib\Core\Database\Connection
    {
        $db = app('db');

        if ($connection !== null) {
            return $db->connection($connection);
        }

        return $db;
    }
}

if (!function_exists('validate')) {
    /**
     * Validate the given data against the given rules.
     */
    function validate(array $data, array $rules, array $messages = [], array $customAttributes = []): array
    {
        return app('validator')->validate($data, $rules, $messages, $customAttributes);
    }
}

if (!function_exists('validator')) {
    /**
     * Create a new Validator instance.
     */
    function validator(array $data, array $rules, array $messages = [], array $customAttributes = []): \RzxLib\Core\Validation\Validator
    {
        return app('validator')->make($data, $rules, $messages, $customAttributes);
    }
}

if (!function_exists('bcrypt')) {
    /**
     * Hash the given value using bcrypt.
     */
    function bcrypt(string $value): string
    {
        return \RzxLib\Core\Auth\PasswordHasher::hash($value);
    }
}

if (!function_exists('password_check')) {
    /**
     * Check if the given plain value matches the given hash.
     */
    function password_check(string $value, string $hashedValue): bool
    {
        return \RzxLib\Core\Auth\PasswordHasher::verify($value, $hashedValue);
    }
}

if (!function_exists('encrypt')) {
    /**
     * Encrypt a value using AES-256-CBC.
     */
    function encrypt(?string $value): ?string
    {
        return \RzxLib\Core\Helpers\Encryption::encrypt($value);
    }
}

if (!function_exists('decrypt')) {
    /**
     * Decrypt an encrypted value.
     */
    function decrypt(?string $value): ?string
    {
        return \RzxLib\Core\Helpers\Encryption::decrypt($value);
    }
}

if (!function_exists('encrypt_fields')) {
    /**
     * Encrypt specific fields in an array.
     */
    function encrypt_fields(array $data, array $fields): array
    {
        return \RzxLib\Core\Helpers\Encryption::encryptFields($data, $fields);
    }
}

if (!function_exists('decrypt_fields')) {
    /**
     * Decrypt specific fields in an array.
     */
    function decrypt_fields(array $data, array $fields): array
    {
        return \RzxLib\Core\Helpers\Encryption::decryptFields($data, $fields);
    }
}

if (!function_exists('db_trans')) {
    /**
     * DB에서 다국어 번역 가져오기
     *
     * @param string $langKey 번역 키 (예: 'site.tagline')
     * @param string|null $locale 언어 코드 (null이면 현재 로케일 사용)
     * @param string $default 기본값
     * @return string
     */
    function db_trans(string $langKey, ?string $locale = null, string $default = ''): string
    {
        static $pdo = null;
        static $cache = [];

        $locale = $locale ?? current_locale();
        $cacheKey = $langKey . ':' . $locale;

        // 캐시 확인
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey] ?: $default;
        }

        try {
            // PDO 연결
            if ($pdo === null) {
                $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
                $dbname = $_ENV['DB_DATABASE'] ?? 'rezlyx_dev';
                $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
                $username = $_ENV['DB_USERNAME'] ?? 'root';
                $password = $_ENV['DB_PASSWORD'] ?? '';

                $pdo = new PDO(
                    "mysql:host={$host};dbname={$dbname};charset={$charset}",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
                    ]
                );
            }

            // 번역 조회 (source_locale도 함께 조회)
            $stmt = $pdo->prepare("SELECT content, source_locale FROM rzx_translations WHERE lang_key = ? AND locale = ?");
            $stmt->execute([$langKey, $locale]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $content = $result ? $result['content'] : '';
            $sourceLocale = $result ? $result['source_locale'] : null;

            // 현재 로케일에 번역이 없는 경우, source_locale(원본 언어)로 fallback
            if (empty($content) && $sourceLocale) {
                $stmt->execute([$langKey, $sourceLocale]);
                $fallbackResult = $stmt->fetch(PDO::FETCH_ASSOC);
                $content = $fallbackResult ? $fallbackResult['content'] : '';
            } elseif (empty($content)) {
                // source_locale이 없는 경우 (레거시 데이터), 원본 찾기
                $sourceStmt = $pdo->prepare("SELECT source_locale FROM rzx_translations WHERE lang_key = ? AND source_locale IS NOT NULL LIMIT 1");
                $sourceStmt->execute([$langKey]);
                $sourceResult = $sourceStmt->fetch(PDO::FETCH_ASSOC);
                if ($sourceResult && $sourceResult['source_locale'] !== $locale) {
                    $stmt->execute([$langKey, $sourceResult['source_locale']]);
                    $fallbackResult = $stmt->fetch(PDO::FETCH_ASSOC);
                    $content = $fallbackResult ? $fallbackResult['content'] : '';
                }
            }

            $cache[$cacheKey] = $content;

            return $content ?: $default;
        } catch (\PDOException $e) {
            // DB 오류 시 기본값 반환
            error_log("db_trans error: " . $e->getMessage());
            return $default;
        }
    }
}

if (!function_exists('is_translation_fallback')) {
    /**
     * 해당 번역이 fallback(원본 언어) 사용 중인지 확인
     * source_locale 컬럼을 사용하여 원본 언어 판별
     *
     * @param string $langKey 번역 키
     * @param string|null $locale 로케일 (null이면 현재 로케일)
     * @return bool 현재 로케일의 번역이 없어서 fallback을 사용하면 true
     */
    function is_translation_fallback(string $langKey, ?string $locale = null): bool
    {
        static $pdo = null;
        static $cache = [];
        static $sourceLocaleCache = [];

        $locale = $locale ?? current_locale();
        $cacheKey = 'fallback:' . $langKey . ':' . $locale;

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        try {
            if ($pdo === null) {
                $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
                $dbname = $_ENV['DB_DATABASE'] ?? 'rezlyx_dev';
                $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
                $username = $_ENV['DB_USERNAME'] ?? 'root';
                $password = $_ENV['DB_PASSWORD'] ?? '';

                $pdo = new PDO(
                    "mysql:host={$host};dbname={$dbname};charset={$charset}",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
                    ]
                );
            }

            // source_locale(원본 언어) 찾기
            if (!isset($sourceLocaleCache[$langKey])) {
                $stmt = $pdo->prepare("SELECT source_locale FROM rzx_translations WHERE lang_key = ? AND source_locale IS NOT NULL LIMIT 1");
                $stmt->execute([$langKey]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $sourceLocaleCache[$langKey] = $result ? $result['source_locale'] : null;
            }

            $sourceLocale = $sourceLocaleCache[$langKey];

            // 현재 로케일이 원본 언어면 fallback 아님
            if ($locale === $sourceLocale) {
                $cache[$cacheKey] = false;
                return false;
            }

            // 현재 로케일의 번역이 있는지 확인
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rzx_translations WHERE lang_key = ? AND locale = ? AND content != ''");
            $stmt->execute([$langKey, $locale]);
            $hasTranslation = (int)$stmt->fetchColumn() > 0;

            $isFallback = !$hasTranslation;
            $cache[$cacheKey] = $isFallback;

            return $isFallback;
        } catch (\PDOException $e) {
            error_log("is_translation_fallback error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('get_site_name')) {
    /**
     * 현재 로케일에 맞는 사이트 이름 가져오기
     *
     * 우선순위:
     * 1. rzx_translations 테이블에서 현재 로케일의 번역 (다국어 모달에서 입력한 값)
     * 2. rzx_settings 테이블의 site_name (입력창에 직접 입력한 값 - 모든 언어 공통)
     * 3. APP_NAME 환경변수
     *
     * @param string|null $locale 언어 코드 (null이면 현재 로케일 사용)
     * @return string
     */
    function get_site_name(?string $locale = null): string
    {
        // 먼저 다국어 번역 확인
        $translated = db_trans('site.name', $locale, '');
        if (!empty($translated)) {
            return $translated;
        }

        // 번역이 없으면 기본 설정값(모든 언어 공통) 반환
        $siteName = get_setting('site_name', '');
        if (!empty($siteName)) {
            return $siteName;
        }

        // 설정도 없으면 환경변수 또는 기본값
        return $_ENV['APP_NAME'] ?? 'RezlyX';
    }
}

if (!function_exists('get_site_tagline')) {
    /**
     * 현재 로케일에 맞는 사이트 타이틀(tagline) 가져오기
     *
     * 우선순위:
     * 1. rzx_translations 테이블에서 현재 로케일의 번역 (다국어 모달에서 입력한 값)
     * 2. rzx_settings 테이블의 site_tagline (입력창에 직접 입력한 값 - 모든 언어 공통)
     *
     * @param string|null $locale 언어 코드 (null이면 현재 로케일 사용)
     * @return string
     */
    function get_site_tagline(?string $locale = null): string
    {
        // 먼저 다국어 번역 확인
        $translated = db_trans('site.tagline', $locale, '');
        if (!empty($translated)) {
            return $translated;
        }

        // 번역이 없으면 기본 설정값(모든 언어 공통) 반환
        return get_setting('site_tagline', '');
    }
}

if (!function_exists('get_points_name')) {
    /**
     * 현재 로케일에 맞는 적립금 명칭 가져오기
     *
     * 우선순위:
     * 1. rzx_translations 테이블에서 현재 로케일의 번역 (다국어 모달에서 입력한 값)
     * 2. rzx_settings 테이블의 service_points_name (입력창에 직접 입력한 값)
     * 3. 번역 파일의 기본값 (__('booking.points_default_name'))
     *
     * @param string|null $locale 언어 코드 (null이면 현재 로케일 사용)
     * @return string
     */
    function get_points_name(?string $locale = null): string
    {
        // 먼저 다국어 번역 확인
        $translated = db_trans('services.settings.general.points_name', $locale, '');
        if (!empty($translated)) {
            return $translated;
        }

        // 번역이 없으면 기본 설정값(모든 언어 공통) 반환
        $settingVal = get_setting('service_points_name', '');
        if (!empty($settingVal)) {
            return $settingVal;
        }

        // 설정도 없으면 번역 파일 기본값
        return __('booking.points_default_name');
    }
}

if (!function_exists('get_setting')) {
    /**
     * rzx_settings 테이블에서 설정값 가져오기
     *
     * @param string $key 설정 키
     * @param string $default 기본값
     * @return string
     */
    function get_setting(string $key, string $default = ''): string
    {
        static $pdo = null;
        static $cache = [];

        // 캐시 확인
        if (isset($cache[$key])) {
            return $cache[$key] ?: $default;
        }

        try {
            // PDO 연결
            if ($pdo === null) {
                $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
                $dbname = $_ENV['DB_DATABASE'] ?? 'rezlyx_dev';
                $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
                $username = $_ENV['DB_USERNAME'] ?? 'root';
                $password = $_ENV['DB_PASSWORD'] ?? '';

                $pdo = new PDO(
                    "mysql:host={$host};dbname={$dbname};charset={$charset}",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
                    ]
                );
            }

            // 설정 조회
            $stmt = $pdo->prepare("SELECT value FROM rzx_settings WHERE `key` = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $value = $result ? $result['value'] : '';
            $cache[$key] = $value;

            return $value ?: $default;
        } catch (\PDOException $e) {
            // DB 오류 시 기본값 반환
            error_log("get_setting error: " . $e->getMessage());
            return $default;
        }
    }
}

// =============================================================================
// 로그 헬퍼 함수
// =============================================================================

if (!function_exists('log_debug')) {
    /**
     * 디버그 로그 기록
     *
     * @param string $message 로그 메시지
     * @param array $context 컨텍스트 데이터
     */
    function log_debug(string $message, array $context = []): void
    {
        \RzxLib\Core\Helpers\Logger::logDebug($message, $context);
    }
}

if (!function_exists('log_info')) {
    /**
     * 정보 로그 기록
     *
     * @param string $message 로그 메시지
     * @param array $context 컨텍스트 데이터
     */
    function log_info(string $message, array $context = []): void
    {
        \RzxLib\Core\Helpers\Logger::logInfo($message, $context);
    }
}

if (!function_exists('log_warning')) {
    /**
     * 경고 로그 기록
     *
     * @param string $message 로그 메시지
     * @param array $context 컨텍스트 데이터
     */
    function log_warning(string $message, array $context = []): void
    {
        \RzxLib\Core\Helpers\Logger::logWarning($message, $context);
    }
}

if (!function_exists('log_error')) {
    /**
     * 에러 로그 기록
     *
     * @param string $message 로그 메시지
     * @param array $context 컨텍스트 데이터
     */
    function log_error(string $message, array $context = []): void
    {
        \RzxLib\Core\Helpers\Logger::logError($message, $context);
    }
}

if (!function_exists('log_exception')) {
    /**
     * 예외 로그 기록
     *
     * @param \Throwable $exception 예외 객체
     * @param array $context 추가 컨텍스트 데이터
     */
    function log_exception(\Throwable $exception, array $context = []): void
    {
        \RzxLib\Core\Helpers\Logger::logException($exception, $context);
    }
}

if (!function_exists('rzx_logger')) {
    /**
     * Logger 인스턴스 가져오기
     *
     * @return \RzxLib\Core\Helpers\Logger
     */
    function rzx_logger(): \RzxLib\Core\Helpers\Logger
    {
        return \RzxLib\Core\Helpers\Logger::getInstance();
    }
}
