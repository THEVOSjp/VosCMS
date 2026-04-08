<?php

declare(strict_types=1);

namespace RzxLib\Core\Middleware;

use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;
use Closure;

/**
 * ThrottleMiddleware - 요청 제한 미들웨어
 *
 * IP 기반 요청 속도 제한 (Rate Limiting)
 *
 * @package RzxLib\Core\Middleware
 */
class ThrottleMiddleware implements MiddlewareInterface
{
    /**
     * 최대 요청 횟수
     */
    protected int $maxAttempts;

    /**
     * 시간 윈도우 (초)
     */
    protected int $decaySeconds;

    /**
     * 캐시 디렉토리
     */
    protected string $cacheDir;

    /**
     * ThrottleMiddleware 생성자
     */
    public function __construct(int $maxAttempts = 60, int $decaySeconds = 60)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;

        // 캐시 디렉토리 설정
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $this->cacheDir = $basePath . '/storage/cache/throttle';

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * 요청 처리
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->tooManyAttempts($key)) {
            return $this->buildTooManyAttemptsResponse($key);
        }

        $this->hit($key);

        $response = $next($request);

        // 응답에 Rate Limit 헤더 추가
        return $this->addRateLimitHeaders($response, $key);
    }

    /**
     * 요청 시그니처 생성 (키)
     */
    protected function resolveRequestSignature(Request $request): string
    {
        // IP + URI + 메서드 조합
        $signature = $request->ip() . '|' . $request->uri() . '|' . $request->method();

        return sha1($signature);
    }

    /**
     * 제한 초과 여부 확인
     */
    protected function tooManyAttempts(string $key): bool
    {
        $data = $this->getAttemptData($key);

        if ($data === null) {
            return false;
        }

        // 시간 윈도우가 지났으면 리셋
        if (time() - $data['start_time'] > $this->decaySeconds) {
            $this->clearAttempts($key);
            return false;
        }

        return $data['attempts'] >= $this->maxAttempts;
    }

    /**
     * 요청 횟수 증가
     */
    protected function hit(string $key): void
    {
        $data = $this->getAttemptData($key);

        if ($data === null || (time() - $data['start_time'] > $this->decaySeconds)) {
            // 새로운 윈도우 시작
            $data = [
                'attempts' => 1,
                'start_time' => time(),
            ];
        } else {
            $data['attempts']++;
        }

        $this->saveAttemptData($key, $data);
    }

    /**
     * 남은 요청 횟수
     */
    protected function remainingAttempts(string $key): int
    {
        $data = $this->getAttemptData($key);

        if ($data === null) {
            return $this->maxAttempts;
        }

        return max(0, $this->maxAttempts - $data['attempts']);
    }

    /**
     * 리셋까지 남은 시간 (초)
     */
    protected function availableIn(string $key): int
    {
        $data = $this->getAttemptData($key);

        if ($data === null) {
            return 0;
        }

        $elapsed = time() - $data['start_time'];

        return max(0, $this->decaySeconds - $elapsed);
    }

    /**
     * 시도 데이터 가져오기
     */
    protected function getAttemptData(string $key): ?array
    {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);

        return $content ? json_decode($content, true) : null;
    }

    /**
     * 시도 데이터 저장
     */
    protected function saveAttemptData(string $key, array $data): void
    {
        $file = $this->getCacheFile($key);
        file_put_contents($file, json_encode($data));
    }

    /**
     * 시도 데이터 삭제
     */
    protected function clearAttempts(string $key): void
    {
        $file = $this->getCacheFile($key);

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * 캐시 파일 경로
     */
    protected function getCacheFile(string $key): string
    {
        return $this->cacheDir . '/' . $key . '.json';
    }

    /**
     * Too Many Attempts 응답 생성
     */
    protected function buildTooManyAttemptsResponse(string $key): Response
    {
        $retryAfter = $this->availableIn($key);

        $response = Response::json([
            'error' => true,
            'message' => '요청이 너무 많습니다. ' . $retryAfter . '초 후에 다시 시도해주세요.',
        ], 429);

        $response->header('Retry-After', (string) $retryAfter);
        $response->header('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->header('X-RateLimit-Remaining', '0');

        return $response;
    }

    /**
     * Rate Limit 헤더 추가
     */
    protected function addRateLimitHeaders(Response $response, string $key): Response
    {
        $remaining = $this->remainingAttempts($key);

        $response->header('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->header('X-RateLimit-Remaining', (string) $remaining);

        return $response;
    }

    /**
     * 만료된 캐시 정리
     */
    public static function cleanup(): void
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $cacheDir = $basePath . '/storage/cache/throttle';

        if (!is_dir($cacheDir)) {
            return;
        }

        $files = glob($cacheDir . '/*.json');
        $now = time();

        foreach ($files as $file) {
            if (filemtime($file) < $now - 3600) { // 1시간 이상 된 파일 삭제
                unlink($file);
            }
        }
    }
}
