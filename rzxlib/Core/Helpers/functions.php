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
     * DB에서 다국어 번역 가져오기 (폴백 체인 내장)
     *
     * 폴백 순서: 현재 로케일 → 영어(en) → 기본언어 → source_locale → $default
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

            $defaultLocale = $_ENV['DEFAULT_LOCALE'] ?? 'ko';

            // 폴백 체인 구성: 현재 로케일 → 영어 → 기본언어 (중복 제거)
            $chain = [$locale];
            if ($locale !== 'en') $chain[] = 'en';
            if ($locale !== $defaultLocale && $defaultLocale !== 'en') $chain[] = $defaultLocale;

            $stmt = $pdo->prepare("SELECT content, source_locale FROM rzx_translations WHERE lang_key = ? AND locale = ?");
            $content = '';
            $sourceLocale = null;

            // 폴백 체인 순서대로 조회
            foreach ($chain as $tryLocale) {
                $tryCacheKey = $langKey . ':' . $tryLocale;
                if (isset($cache[$tryCacheKey]) && !empty($cache[$tryCacheKey])) {
                    $content = $cache[$tryCacheKey];
                    break;
                }
                $stmt->execute([$langKey, $tryLocale]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $cache[$tryCacheKey] = $result['content'];
                    if (!$sourceLocale) $sourceLocale = $result['source_locale'];
                    if (!empty($result['content'])) {
                        $content = $result['content'];
                        break;
                    }
                }
            }

            // 폴백 체인에서 못 찾으면 source_locale로 최종 시도
            if (empty($content) && $sourceLocale && !in_array($sourceLocale, $chain)) {
                $stmt->execute([$langKey, $sourceLocale]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $content = $result ? $result['content'] : '';
            }

            // source_locale이 없는 레거시 데이터: 아무 번역이나 찾기
            if (empty($content)) {
                $anyStmt = $pdo->prepare("SELECT content FROM rzx_translations WHERE lang_key = ? AND content != '' LIMIT 1");
                $anyStmt->execute([$langKey]);
                $anyResult = $anyStmt->fetch(PDO::FETCH_ASSOC);
                $content = $anyResult ? $anyResult['content'] : '';
            }

            $cache[$cacheKey] = $content;

            return $content ?: $default;
        } catch (\PDOException $e) {
            error_log("db_trans error: " . $e->getMessage());
            return $default;
        }
    }
}

if (!function_exists('load_system_pages')) {
    /**
     * 시스템 페이지 목록 로드
     * config/system-pages.php + 플러그인 system_pages 병합
     */
    function load_system_pages(): array
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $pages = [];
        $configFile = $basePath . '/config/system-pages.php';
        if (file_exists($configFile)) {
            $pages = include $configFile;
        }
        // 플러그인 시스템 페이지 병합
        $_pm = null;
        if (class_exists('\RzxLib\Core\Plugin\PluginManager', false)) {
            $_pm = \RzxLib\Core\Plugin\PluginManager::getInstance();
        }
        if ($_pm) {
            foreach ($_pm->getLoaded() as $pmId => $manifest) {
                foreach ($manifest['system_pages'] ?? [] as $sp) {
                    $pages[] = $sp;
                }
            }
        }
        // title 번역 적용
        foreach ($pages as &$p) {
            if (is_string($p['title'] ?? '') && str_contains($p['title'], '.')) {
                $translated = function_exists('__') ? __($p['title']) : $p['title'];
                if ($translated !== $p['title']) $p['title'] = $translated;
            }
        }
        unset($p);
        return $pages;
    }
}

if (!function_exists('load_menu')) {
    /**
     * 메뉴 로딩 통합 헬퍼
     *
     * config 파일 + 플러그인 plugin.json에서 메뉴를 로드하고 병합/정렬하여 반환.
     * 스킨은 이 함수의 결과만 받아서 렌더링하면 됨.
     *
     * @param string $type 메뉴 타입: 'admin', 'admin_dropdown', 'mypage', 'user_dropdown'
     * @param array $options 추가 옵션 ['admin_path' => 'admin']
     * @return array 정렬된 메뉴 배열
     */
    function load_menu(string $type, array $options = []): array
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);

        // config 파일 매핑
        $configMap = [
            'admin'          => 'admin-menu.php',
            'admin_dropdown' => 'admin-dropdown-menu.php',
            'mypage'         => 'mypage-menu.php',
            'user_dropdown'  => 'user-dropdown-menu.php',
        ];

        // 1. config에서 로드
        $menus = [];
        $configFile = $basePath . '/config/' . ($configMap[$type] ?? '');
        if (file_exists($configFile)) {
            $menus = include $configFile;
        }

        // 2. 활성 플러그인 메뉴만 병합
        $pluginKey = $type;

        // 활성 플러그인 메뉴만 병합
        $pluginKey = $type;
        $_pm = null;

        // PluginManager 싱글턴 확인
        if (class_exists('\RzxLib\Core\Plugin\PluginManager', false)) {
            $_pm = \RzxLib\Core\Plugin\PluginManager::getInstance();
        }

        if ($_pm) {
            // PluginManager에서 로드된(활성) 플러그인만
            foreach ($_pm->getLoaded() as $pmId => $manifest) {
                foreach ($manifest['menus'][$pluginKey] ?? [] as $mi) {
                    $mi['position'] = $mi['position'] ?? 50;
                    $menus[] = $mi;
                }
            }
        } else {
            // PluginManager 없을 때 — DB에서 활성 목록 조회
            $activePlugins = null; // null = 조회 안 됨, [] = 활성 플러그인 없음
            try {
                $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
                $dbname = $_ENV['DB_DATABASE'] ?? '';
                $username = $_ENV['DB_USERNAME'] ?? 'root';
                $password = $_ENV['DB_PASSWORD'] ?? '';
                $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';
                if ($dbname) {
                    $_pdo = new \PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password);
                    $activePlugins = $_pdo->query("SELECT plugin_id FROM {$pfx}plugins WHERE is_active = 1")->fetchAll(\PDO::FETCH_COLUMN);
                }
            } catch (\Throwable $e) {}

            // 활성 플러그인의 plugin.json만 로드 (DB 조회 실패 시 플러그인 메뉴 없음)
            if (is_array($activePlugins) && !empty($activePlugins)) {
                $pluginsDir = $basePath . '/plugins';
                if (is_dir($pluginsDir)) {
                    foreach ($activePlugins as $pluginSlug) {
                        $pjFile = $pluginsDir . '/' . $pluginSlug . '/plugin.json';
                        if (!file_exists($pjFile)) continue;
                        $pj = @json_decode(file_get_contents($pjFile), true);
                        foreach ($pj['menus'][$pluginKey] ?? [] as $mi) {
                            $mi['position'] = $mi['position'] ?? 50;
                            $menus[] = $mi;
                        }
                    }
                }
            }
        }

        // 3. 정렬
        usort($menus, fn($a, $b) => ($a['position'] ?? 50) <=> ($b['position'] ?? 50));

        // 5. label 번역 적용 + URL 치환
        $adminPath = $options['admin_path'] ?? ($GLOBALS['config']['admin_path'] ?? 'admin');
        foreach ($menus as &$mi) {
            // label 번역
            if (is_string($mi['label'] ?? '') && str_contains($mi['label'], '.')) {
                $mi['label'] = function_exists('__') ? __($mi['label']) : $mi['label'];
            } elseif (is_array($mi['label'] ?? null)) {
                $locale = function_exists('current_locale') ? current_locale() : 'ko';
                $mi['label'] = $mi['label'][$locale] ?? $mi['label']['en'] ?? $mi['label']['ko'] ?? reset($mi['label']);
            }
            // URL 치환
            if (isset($mi['url'])) {
                $mi['url'] = str_replace('{admin_path}', $adminPath, $mi['url']);
            }
        }
        unset($mi);

        return $menus;
    }
}

if (!function_exists('db_trans_batch')) {
    /**
     * 게시글 배치 다국어 번역 (db_trans와 동일한 폴백 체인)
     * 위젯 등에서 여러 게시글의 제목/내용을 한 번에 번역
     *
     * @param \PDO $pdo DB 연결
     * @param array $posts 게시글 배열 (id, title, content 포함)
     * @param string $locale 현재 로케일
     * @param string $prefix DB 테이블 접두사
     * @return array 번역이 적용된 게시글 배열
     */
    function db_trans_batch(\PDO $pdo, array $posts, string $locale, string $prefix = 'rzx_'): array
    {
        if (empty($posts)) return $posts;

        $postIds = array_column($posts, 'id');
        $titleTr = [];
        $contentTr = [];

        // 게시글별 original_locale 매핑
        $origLocaleMap = [];
        foreach ($posts as $p) {
            $origLocaleMap[(int)$p['id']] = $p['original_locale'] ?? '';
        }

        // 현재 로케일이 원본 언어인 게시글은 번역 불필요 (원본 title 유지)
        $needTranslation = array_filter($postIds, fn($id) => ($origLocaleMap[(int)$id] ?? '') !== $locale);
        if (empty($needTranslation)) return $posts; // 전부 원본 언어

        // 폴백 체인: 현재 로케일 → 영어 → 기본언어
        $defaultLocale = $_ENV['APP_LOCALE'] ?? $_ENV['DEFAULT_LOCALE'] ?? 'ko';
        $chain = [$locale];
        if ($locale !== 'en') $chain[] = 'en';
        if ($locale !== $defaultLocale && $defaultLocale !== 'en') $chain[] = $defaultLocale;

        // 번역 필요한 게시글만 키 생성
        $titleKeys = implode(',', array_map(fn($id) => "'" . addslashes("board_post.{$id}.title") . "'", $needTranslation));
        $contentKeys = implode(',', array_map(fn($id) => "'" . addslashes("board_post.{$id}.content") . "'", $needTranslation));
        $allKeys = $titleKeys . ',' . $contentKeys;

        try {
            foreach ($chain as $tryLocale) {
                $still = array_filter($needTranslation, fn($id) => !isset($titleTr[(int)$id]));
                if (empty($still)) break;

                $rows = $pdo->query("SELECT lang_key, content FROM {$prefix}translations WHERE lang_key IN ({$allKeys}) AND locale = '" . addslashes($tryLocale) . "'")->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $tr) {
                    if (preg_match('/board_post\.(\d+)\.title/', $tr['lang_key'], $m)) {
                        if (!isset($titleTr[(int)$m[1]])) $titleTr[(int)$m[1]] = $tr['content'];
                    } elseif (preg_match('/board_post\.(\d+)\.content/', $tr['lang_key'], $m)) {
                        if (!isset($contentTr[(int)$m[1]])) $contentTr[(int)$m[1]] = $tr['content'];
                    }
                }
            }
        } catch (\PDOException $e) {
            error_log("db_trans_batch error: " . $e->getMessage());
        }

        // 번역 적용 (원본 언어 게시글은 건드리지 않음)
        foreach ($posts as &$p) {
            $pid = (int)$p['id'];
            if (isset($titleTr[$pid])) $p['title'] = $titleTr[$pid];
            if (isset($contentTr[$pid])) $p['content'] = $contentTr[$pid];
        }
        unset($p);

        return $posts;
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
