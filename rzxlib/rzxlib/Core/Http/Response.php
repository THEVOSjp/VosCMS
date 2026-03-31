<?php

declare(strict_types=1);

namespace RzxLib\Core\Http;

/**
 * Response - HTTP 응답
 *
 * HTTP 응답 생성 및 전송
 *
 * @package RzxLib\Core\Http
 */
class Response
{
    /**
     * 응답 본문
     */
    protected string $content = '';

    /**
     * HTTP 상태 코드
     */
    protected int $statusCode = 200;

    /**
     * HTTP 헤더
     */
    protected array $headers = [];

    /**
     * 쿠키
     */
    protected array $cookies = [];

    /**
     * HTTP 상태 메시지
     */
    protected static array $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    /**
     * Response 생성자
     */
    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $status;
        $this->headers = $headers;
    }

    /**
     * 응답 본문 설정
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * 응답 본문 반환
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * 상태 코드 설정
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * 상태 코드 반환
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * 헤더 설정
     */
    public function header(string $name, string $value, bool $replace = true): self
    {
        $this->headers[$name] = [
            'value' => $value,
            'replace' => $replace,
        ];

        return $this;
    }

    /**
     * 여러 헤더 설정
     */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }

        return $this;
    }

    /**
     * 헤더 반환
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * 쿠키 설정
     */
    public function cookie(
        string $name,
        string $value,
        int $minutes = 0,
        string $path = '/',
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): self {
        $this->cookies[$name] = [
            'name' => $name,
            'value' => $value,
            'expire' => $minutes > 0 ? time() + ($minutes * 60) : 0,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ];

        return $this;
    }

    /**
     * 쿠키 삭제
     */
    public function forgetCookie(string $name, string $path = '/', ?string $domain = null): self
    {
        return $this->cookie($name, '', -2628000, $path, $domain);
    }

    /**
     * 응답 전송
     */
    public function send(): void
    {
        // 헤더 전송
        if (!headers_sent()) {
            // 상태 코드
            $statusText = self::$statusTexts[$this->statusCode] ?? 'Unknown';
            header("HTTP/1.1 {$this->statusCode} {$statusText}");

            // 커스텀 헤더
            foreach ($this->headers as $name => $config) {
                header("{$name}: {$config['value']}", $config['replace']);
            }

            // 쿠키
            foreach ($this->cookies as $cookie) {
                setcookie(
                    $cookie['name'],
                    $cookie['value'],
                    [
                        'expires' => $cookie['expire'],
                        'path' => $cookie['path'],
                        'domain' => $cookie['domain'] ?? '',
                        'secure' => $cookie['secure'],
                        'httponly' => $cookie['httponly'],
                        'samesite' => $cookie['samesite'],
                    ]
                );
            }
        }

        // 본문 출력
        echo $this->content;
    }

    /**
     * JSON 응답 생성
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $response = new self($content ?: '{}', $status, $headers);
        $response->header('Content-Type', 'application/json; charset=utf-8');

        return $response;
    }

    /**
     * 리다이렉트 응답 생성
     */
    public static function redirect(string $url, int $status = 302): self
    {
        $response = new self('', $status);
        $response->header('Location', $url);

        return $response;
    }

    /**
     * 뷰 응답 생성
     */
    public static function view(string $content, int $status = 200): self
    {
        $response = new self($content, $status);
        $response->header('Content-Type', 'text/html; charset=utf-8');

        return $response;
    }

    /**
     * 다운로드 응답 생성
     */
    public static function download(
        string $filePath,
        ?string $name = null,
        array $headers = []
    ): self {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("파일을 찾을 수 없습니다: {$filePath}");
        }

        $name = $name ?? basename($filePath);
        $content = file_get_contents($filePath);

        $response = new self($content ?: '', 200, $headers);
        $response->header('Content-Type', 'application/octet-stream');
        $response->header('Content-Disposition', 'attachment; filename="' . $name . '"');
        $response->header('Content-Length', (string) strlen($content ?: ''));

        return $response;
    }

    /**
     * 파일 응답 (인라인)
     */
    public static function file(string $filePath, array $headers = []): self
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("파일을 찾을 수 없습니다: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        $response = new self($content ?: '', 200, $headers);
        $response->header('Content-Type', $mimeType);
        $response->header('Content-Length', (string) strlen($content ?: ''));

        return $response;
    }

    /**
     * 빈 응답 생성
     */
    public static function noContent(): self
    {
        return new self('', 204);
    }

    /**
     * 에러 응답 생성
     */
    public static function error(string $message, int $status = 500): self
    {
        return self::json([
            'error' => true,
            'message' => $message,
        ], $status);
    }

    /**
     * 성공 응답 생성
     */
    public static function success(mixed $data = null, string $message = '성공', int $status = 200): self
    {
        return self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * 페이지네이션 응답
     */
    public static function paginate(
        array $items,
        int $total,
        int $page,
        int $perPage,
        array $meta = []
    ): self {
        return self::json([
            'data' => $items,
            'meta' => array_merge([
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => (int) ceil($total / $perPage),
            ], $meta),
        ]);
    }

    /**
     * CORS 헤더 추가
     */
    public function withCors(
        string $origin = '*',
        array $methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        array $headers = ['Content-Type', 'Authorization']
    ): self {
        $this->header('Access-Control-Allow-Origin', $origin);
        $this->header('Access-Control-Allow-Methods', implode(', ', $methods));
        $this->header('Access-Control-Allow-Headers', implode(', ', $headers));

        return $this;
    }

    /**
     * 캐시 헤더 추가
     */
    public function withCache(int $minutes): self
    {
        $this->header('Cache-Control', "public, max-age=" . ($minutes * 60));
        $this->header('Expires', gmdate('D, d M Y H:i:s', time() + ($minutes * 60)) . ' GMT');

        return $this;
    }

    /**
     * 캐시 비활성화
     */
    public function withNoCache(): self
    {
        $this->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->header('Pragma', 'no-cache');
        $this->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $this;
    }
}
