<?php

declare(strict_types=1);

namespace RzxLib\Core\Http;

use RzxLib\Core\Validation\Validator;
use RzxLib\Core\Validation\ValidationException;

/**
 * Request - HTTP 요청 래퍼
 *
 * HTTP 요청 데이터에 대한 편리한 접근 제공
 *
 * @package RzxLib\Core\Http
 */
class Request
{
    /**
     * 요청 메서드
     */
    protected string $method;

    /**
     * 요청 URI
     */
    protected string $uri;

    /**
     * 쿼리 파라미터 ($_GET)
     */
    protected array $query;

    /**
     * POST 데이터 ($_POST)
     */
    protected array $post;

    /**
     * 업로드 파일 ($_FILES)
     */
    protected array $files;

    /**
     * 서버 변수 ($_SERVER)
     */
    protected array $server;

    /**
     * 쿠키 ($_COOKIE)
     */
    protected array $cookies;

    /**
     * 요청 헤더
     */
    protected array $headers;

    /**
     * Raw 바디 내용
     */
    protected ?string $content = null;

    /**
     * 라우트 파라미터
     */
    protected array $routeParams = [];

    /**
     * Request 생성자
     */
    public function __construct(
        array $query = [],
        array $post = [],
        array $files = [],
        array $server = [],
        array $cookies = [],
        ?string $content = null
    ) {
        $this->query = $query;
        $this->post = $post;
        $this->files = $files;
        $this->server = $server;
        $this->cookies = $cookies;
        $this->content = $content;

        $this->method = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $this->parseUri($server);
        $this->headers = $this->parseHeaders($server);
    }

    /**
     * 현재 요청에서 인스턴스 생성
     */
    public static function capture(): static
    {
        return new static(
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER,
            $_COOKIE,
            file_get_contents('php://input') ?: null
        );
    }

    /**
     * URI 파싱
     */
    protected function parseUri(array $server): string
    {
        $uri = $server['REQUEST_URI'] ?? '/';
        $pos = strpos($uri, '?');

        return $pos !== false ? substr($uri, 0, $pos) : $uri;
    }

    /**
     * 헤더 파싱
     */
    protected function parseHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($name)] = $value;
            }
        }

        // 특수 헤더
        if (isset($server['CONTENT_TYPE'])) {
            $headers['content-type'] = $server['CONTENT_TYPE'];
        }

        if (isset($server['CONTENT_LENGTH'])) {
            $headers['content-length'] = $server['CONTENT_LENGTH'];
        }

        return $headers;
    }

    /**
     * HTTP 메서드 반환
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * 메서드 확인
     */
    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    /**
     * GET 요청 확인
     */
    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    /**
     * POST 요청 확인
     */
    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * AJAX 요청 확인
     */
    public function isAjax(): bool
    {
        return $this->header('x-requested-with') === 'XMLHttpRequest';
    }

    /**
     * JSON 요청 확인
     */
    public function isJson(): bool
    {
        $contentType = $this->header('content-type', '');
        return str_contains($contentType, 'application/json');
    }

    /**
     * 요청 URI 반환
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * 전체 URL 반환
     */
    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $this->server['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . ($this->server['REQUEST_URI'] ?? '/');
    }

    /**
     * HTTPS 확인
     */
    public function isSecure(): bool
    {
        return ($this->server['HTTPS'] ?? '') === 'on'
            || ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    /**
     * 입력값 가져오기 (GET + POST + JSON)
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * 모든 입력값 가져오기
     */
    public function all(): array
    {
        $data = array_merge($this->query, $this->post);

        // JSON 바디 파싱
        if ($this->isJson() && $this->content) {
            $json = json_decode($this->content, true);
            if (is_array($json)) {
                $data = array_merge($data, $json);
            }
        }

        return $data;
    }

    /**
     * 특정 키만 가져오기
     */
    public function only(array|string $keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $all = $this->all();

        return array_intersect_key($all, array_flip($keys));
    }

    /**
     * 특정 키 제외하고 가져오기
     */
    public function except(array|string $keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $all = $this->all();

        return array_diff_key($all, array_flip($keys));
    }

    /**
     * 입력값 존재 확인
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * 입력값이 비어있지 않은지 확인
     */
    public function filled(string $key): bool
    {
        $value = $this->input($key);
        return $value !== null && $value !== '' && $value !== [];
    }

    /**
     * 쿼리 파라미터 가져오기
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    /**
     * POST 데이터 가져오기
     */
    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }

        return $this->post[$key] ?? $default;
    }

    /**
     * 헤더 가져오기
     */
    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    /**
     * 모든 헤더 가져오기
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Bearer 토큰 가져오기
     */
    public function bearerToken(): ?string
    {
        $header = $this->header('authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }

    /**
     * 쿠키 가져오기
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * 서버 변수 가져오기
     */
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * 클라이언트 IP 가져오기
     */
    public function ip(): string
    {
        $keys = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($keys as $key) {
            if (isset($this->server[$key])) {
                $ip = $this->server[$key];
                // X-Forwarded-For는 콤마로 구분된 IP 목록일 수 있음
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }

        return '127.0.0.1';
    }

    /**
     * User Agent 가져오기
     */
    public function userAgent(): ?string
    {
        return $this->server['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * 파일 가져오기
     */
    public function file(string $key): ?UploadedFile
    {
        if (!isset($this->files[$key])) {
            return null;
        }

        $file = $this->files[$key];

        if (is_array($file['name'])) {
            // 다중 파일 - 첫 번째만 반환
            return new UploadedFile(
                $file['tmp_name'][0],
                $file['name'][0],
                $file['type'][0],
                $file['size'][0],
                $file['error'][0]
            );
        }

        return new UploadedFile(
            $file['tmp_name'],
            $file['name'],
            $file['type'],
            $file['size'],
            $file['error']
        );
    }

    /**
     * 파일 존재 확인
     */
    public function hasFile(string $key): bool
    {
        $file = $this->files[$key] ?? null;

        if (!$file) {
            return false;
        }

        $error = is_array($file['error']) ? $file['error'][0] : $file['error'];

        return $error !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Raw 바디 내용 가져오기
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * JSON 바디 가져오기
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        $data = json_decode($this->content ?? '', true) ?? [];

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? $default;
    }

    /**
     * 라우트 파라미터 설정
     */
    public function setRouteParams(array $params): self
    {
        $this->routeParams = $params;
        return $this;
    }

    /**
     * 라우트 파라미터 가져오기
     */
    public function route(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->routeParams;
        }

        return $this->routeParams[$key] ?? $default;
    }

    /**
     * 입력값 검증
     */
    public function validate(array $rules, array $messages = [], array $customAttributes = []): array
    {
        $validator = new Validator($this->all(), $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * 유효성 검사 (실패해도 예외 발생 안함)
     */
    public function validateSafe(array $rules, array $messages = []): array
    {
        $validator = new Validator($this->all(), $rules, $messages);

        return [
            'valid' => $validator->passes(),
            'errors' => $validator->errors(),
            'data' => $validator->safe(),
        ];
    }

    /**
     * 이전 페이지 URL
     */
    public function referer(): ?string
    {
        return $this->server['HTTP_REFERER'] ?? null;
    }

    /**
     * Accept 헤더가 JSON을 원하는지 확인
     */
    public function wantsJson(): bool
    {
        $accept = $this->header('accept', '');
        return str_contains($accept, 'application/json');
    }

    /**
     * Accept 헤더가 HTML을 원하는지 확인
     */
    public function wantsHtml(): bool
    {
        $accept = $this->header('accept', '');
        return str_contains($accept, 'text/html');
    }
}
