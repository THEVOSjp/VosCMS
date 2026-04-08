<?php
/**
 * RezlyX Logger
 *
 * 애플리케이션 로그를 관리하는 헬퍼 클래스
 * 일별 로그 파일 생성 및 로그 레벨별 기록 지원
 *
 * @package RzxLib\Core\Helpers
 */

namespace RzxLib\Core\Helpers;

class Logger
{
    /**
     * 로그 레벨 상수
     */
    public const DEBUG = 'DEBUG';
    public const INFO = 'INFO';
    public const NOTICE = 'NOTICE';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';
    public const CRITICAL = 'CRITICAL';
    public const ALERT = 'ALERT';
    public const EMERGENCY = 'EMERGENCY';

    /**
     * 로그 레벨 우선순위 (낮을수록 상세)
     */
    private const LEVEL_PRIORITY = [
        self::DEBUG => 100,
        self::INFO => 200,
        self::NOTICE => 250,
        self::WARNING => 300,
        self::ERROR => 400,
        self::CRITICAL => 500,
        self::ALERT => 550,
        self::EMERGENCY => 600,
    ];

    /**
     * 싱글톤 인스턴스
     */
    private static ?Logger $instance = null;

    /**
     * 로그 저장 경로
     */
    private string $logPath;

    /**
     * 로그 파일 접두사
     */
    private string $filePrefix = 'rezlyx';

    /**
     * 현재 로그 레벨 (이 레벨 이상만 기록)
     */
    private string $minLevel;

    /**
     * 로그 보관 일수 (0이면 무제한)
     */
    private int $retentionDays;

    /**
     * 최대 파일 크기 (bytes, 0이면 무제한)
     */
    private int $maxFileSize;

    /**
     * 생성자
     */
    private function __construct()
    {
        // BASE_PATH가 정의되어 있으면 사용, 아니면 자동 감지
        if (defined('BASE_PATH')) {
            $this->logPath = BASE_PATH . '/storage/logs';
        } else {
            // rzxlib/Core/Helpers에서 4단계 위로 올라가면 프로젝트 루트
            $this->logPath = dirname(__DIR__, 3) . '/storage/logs';
        }

        // 환경 설정에서 로그 레벨 가져오기
        $this->minLevel = $_ENV['LOG_LEVEL'] ?? self::DEBUG;
        $this->retentionDays = (int)($_ENV['LOG_DAYS'] ?? 30);
        $this->maxFileSize = 10 * 1024 * 1024; // 기본 10MB

        // 로그 디렉토리 생성
        $this->ensureLogDirectory();
    }

    /**
     * 싱글톤 인스턴스 가져오기
     */
    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 로그 디렉토리 확인 및 생성
     */
    private function ensureLogDirectory(): void
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * 현재 날짜의 로그 파일 경로 가져오기
     */
    private function getLogFilePath(): string
    {
        $date = date('Y-m-d');
        return $this->logPath . '/' . $this->filePrefix . '-' . $date . '.log';
    }

    /**
     * 로그 레벨이 최소 레벨 이상인지 확인
     */
    private function shouldLog(string $level): bool
    {
        $minPriority = self::LEVEL_PRIORITY[strtoupper($this->minLevel)] ?? 0;
        $currentPriority = self::LEVEL_PRIORITY[strtoupper($level)] ?? 0;
        return $currentPriority >= $minPriority;
    }

    /**
     * 로그 메시지 포맷팅
     */
    private function formatMessage(string $level, string $message, array $context = []): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $environment = $_ENV['APP_ENV'] ?? 'production';

        $formatted = "[{$timestamp}] {$environment}.{$level}: {$message}";

        if (!empty($context)) {
            $formatted .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $formatted . PHP_EOL;
    }

    /**
     * 로그 기록
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $filePath = $this->getLogFilePath();
        $formattedMessage = $this->formatMessage($level, $message, $context);

        // 파일 크기 체크 및 로테이션
        if ($this->maxFileSize > 0 && file_exists($filePath) && filesize($filePath) >= $this->maxFileSize) {
            $this->rotateLogFile($filePath);
        }

        // 로그 기록
        file_put_contents($filePath, $formattedMessage, FILE_APPEND | LOCK_EX);

        // 오래된 로그 정리 (일정 확률로 실행)
        if (mt_rand(1, 100) === 1) {
            $this->cleanOldLogs();
        }
    }

    /**
     * 로그 파일 로테이션
     */
    private function rotateLogFile(string $filePath): void
    {
        $i = 1;
        while (file_exists($filePath . '.' . $i)) {
            $i++;
        }
        rename($filePath, $filePath . '.' . $i);
    }

    /**
     * 오래된 로그 파일 정리
     */
    private function cleanOldLogs(): void
    {
        if ($this->retentionDays <= 0) {
            return;
        }

        $cutoffTime = time() - ($this->retentionDays * 24 * 60 * 60);
        $files = glob($this->logPath . '/' . $this->filePrefix . '-*.log*');

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }

    /**
     * 디버그 로그
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * 정보 로그
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * 알림 로그
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * 경고 로그
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * 에러 로그
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * 치명적 에러 로그
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * 경보 로그
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * 긴급 로그
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * 예외 로그
     */
    public function exception(\Throwable $exception, array $context = []): void
    {
        $context['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        $this->error('Exception: ' . $exception->getMessage(), $context);
    }

    /**
     * 요청 로그 (HTTP 요청 정보 자동 포함)
     */
    public function request(string $message, array $context = []): void
    {
        $context['request'] = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        $this->info($message, $context);
    }

    /**
     * 성능 측정 로그
     */
    public function performance(string $action, float $startTime, array $context = []): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $context['duration_ms'] = $duration;

        $this->debug("Performance: {$action} completed in {$duration}ms", $context);
    }

    /**
     * SQL 쿼리 로그
     */
    public function query(string $query, array $bindings = [], float $time = 0): void
    {
        if (($_ENV['QUERY_LOG_ENABLED'] ?? 'false') !== 'true') {
            return;
        }

        $context = [
            'query' => $query,
            'bindings' => $bindings,
            'time_ms' => round($time * 1000, 2),
        ];

        $this->debug('Query executed', $context);
    }

    /**
     * 로그 경로 설정
     */
    public function setLogPath(string $path): self
    {
        $this->logPath = $path;
        $this->ensureLogDirectory();
        return $this;
    }

    /**
     * 파일 접두사 설정
     */
    public function setFilePrefix(string $prefix): self
    {
        $this->filePrefix = $prefix;
        return $this;
    }

    /**
     * 최소 로그 레벨 설정
     */
    public function setMinLevel(string $level): self
    {
        $this->minLevel = strtoupper($level);
        return $this;
    }

    /**
     * 로그 보관 일수 설정
     */
    public function setRetentionDays(int $days): self
    {
        $this->retentionDays = $days;
        return $this;
    }

    /**
     * 최대 파일 크기 설정
     */
    public function setMaxFileSize(int $bytes): self
    {
        $this->maxFileSize = $bytes;
        return $this;
    }

    // =========================================================================
    // 정적 헬퍼 메서드
    // =========================================================================

    /**
     * 디버그 로그 (정적)
     */
    public static function logDebug(string $message, array $context = []): void
    {
        self::getInstance()->debug($message, $context);
    }

    /**
     * 정보 로그 (정적)
     */
    public static function logInfo(string $message, array $context = []): void
    {
        self::getInstance()->info($message, $context);
    }

    /**
     * 경고 로그 (정적)
     */
    public static function logWarning(string $message, array $context = []): void
    {
        self::getInstance()->warning($message, $context);
    }

    /**
     * 에러 로그 (정적)
     */
    public static function logError(string $message, array $context = []): void
    {
        self::getInstance()->error($message, $context);
    }

    /**
     * 예외 로그 (정적)
     */
    public static function logException(\Throwable $exception, array $context = []): void
    {
        self::getInstance()->exception($exception, $context);
    }
}
