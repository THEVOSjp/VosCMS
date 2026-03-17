<?php

declare(strict_types=1);

namespace RzxLib\Core\Updater;

/**
 * 버전 기반 DB 마이그레이션 실행기
 *
 * database/patches/ 디렉토리의 패치 파일을 순차 실행하여
 * 기존 설치의 DB 스키마를 최신 버전으로 업그레이드합니다.
 *
 * 패치 파일명 규칙: {from}_to_{to}.sql (예: 1.1.0_to_1.2.0.sql)
 */
class DatabaseMigrator
{
    private \PDO $pdo;
    private string $patchDir;
    private string $migrationDir;
    private string $prefix;
    private string $logDir;

    public function __construct(\PDO $pdo, string $basePath)
    {
        $this->pdo = $pdo;
        $this->patchDir = rtrim($basePath, '/\\') . '/database/patches';
        $this->migrationDir = rtrim($basePath, '/\\') . '/database/migrations';
        $this->prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
        $this->logDir = rtrim($basePath, '/\\') . '/storage/logs';
    }

    /**
     * DB 버전 조회
     */
    public function getDbVersion(): string
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT `value` FROM {$this->prefix}settings WHERE `key` = 'version'"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $stmt->closeCursor();
            return !empty($rows) ? $rows[0] : '1.0.0';
        } catch (\PDOException $e) {
            return '1.0.0';
        }
    }

    /**
     * 코드 버전 조회 (index.php @version)
     */
    public static function getCodeVersion(string $basePath): string
    {
        $indexFile = rtrim($basePath, '/\\') . '/index.php';
        if (!file_exists($indexFile)) return '1.0.0';

        $content = file_get_contents($indexFile);
        if (preg_match('/@version\s+(\d+\.\d+\.\d+)/', $content, $m)) {
            return $m[1];
        }
        return '1.0.0';
    }

    /**
     * 마이그레이션 필요 여부 확인
     */
    public function needsMigration(string $codeVersion = null): bool
    {
        $dbVersion = $this->getDbVersion();
        $codeVersion = $codeVersion ?? self::getCodeVersion(dirname($this->patchDir, 2));

        return version_compare($dbVersion, $codeVersion, '<');
    }

    /**
     * 적용 가능한 패치 목록 (정렬됨)
     */
    public function getPendingPatches(string $codeVersion = null): array
    {
        $dbVersion = $this->getDbVersion();
        $codeVersion = $codeVersion ?? self::getCodeVersion(dirname($this->patchDir, 2));

        if (!is_dir($this->patchDir)) {
            return [];
        }

        $patches = [];
        foreach (glob($this->patchDir . '/*.sql') as $file) {
            $filename = basename($file, '.sql');
            // 파일명: 1.1.0_to_1.2.0
            if (!preg_match('/^(\d+\.\d+\.\d+)_to_(\d+\.\d+\.\d+)$/', $filename, $m)) {
                continue;
            }

            $from = $m[1];
            $to = $m[2];

            // DB 버전 이상 & 코드 버전 이하인 패치만
            if (version_compare($from, $dbVersion, '>=') && version_compare($to, $codeVersion, '<=')) {
                $patches[] = [
                    'file' => $file,
                    'filename' => $filename,
                    'from' => $from,
                    'to' => $to,
                ];
            }
        }

        // from 버전 기준 정렬
        usort($patches, function ($a, $b) {
            return version_compare($a['from'], $b['from']);
        });

        return $patches;
    }

    /**
     * 모든 펜딩 패치 실행
     */
    public function migrate(string $codeVersion = null): array
    {
        $patches = $this->getPendingPatches($codeVersion);

        if (empty($patches)) {
            return [
                'success' => true,
                'message' => \__('updater.no_patches'),
                'applied' => 0,
            ];
        }

        $applied = 0;
        $errors = [];

        foreach ($patches as $patch) {
            $result = $this->applyPatch($patch);
            if ($result['success']) {
                $applied++;
                $this->log("PATCH OK: {$patch['filename']} ({$patch['from']} → {$patch['to']})");
            } else {
                $errors[] = $patch['filename'] . ': ' . $result['error'];
                $this->log("PATCH FAIL: {$patch['filename']} - {$result['error']}");
                break; // 실패 시 중단
            }
        }

        // 마지막 성공 패치의 to 버전으로 DB 버전 업데이트
        if ($applied > 0) {
            $lastPatch = $patches[$applied - 1];
            $this->updateDbVersion($lastPatch['to']);
        }

        return [
            'success' => empty($errors),
            'applied' => $applied,
            'total' => count($patches),
            'errors' => $errors,
        ];
    }

    /**
     * 개별 패치 실행
     */
    private function applyPatch(array $patch): array
    {
        $sql = file_get_contents($patch['file']);
        if ($sql === false) {
            return ['success' => false, 'error' => \__('updater.file_read_failed')];
        }

        // 테이블 접두사 치환 (패치에서 rzx_ 사용 → 실제 접두사로)
        if ($this->prefix !== 'rzx_') {
            $sql = str_replace('rzx_', $this->prefix, $sql);
        }

        // AFTER 절 제거 (대상 컬럼이 없으면 에러 → 안전하게 제거)
        $sql = preg_replace('/\bAFTER\s+`?\w+`?/i', '', $sql);

        // SELECT 1 → DO 0 (결과셋 반환 방지)
        $sql = str_replace("'SELECT 1'", "'DO 0'", $sql);

        // SQL 문 분리 (세미콜론 기준, 문자열 내 세미콜론 무시)
        $statements = $this->splitSql($sql);

        // DDL(ALTER TABLE 등)은 MySQL에서 암시적 커밋을 유발하므로
        // 트랜잭션 대신 개별 실행 + 에러 시 중단 방식 사용
        try {
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (empty($stmt) || str_starts_with($stmt, '--') || str_starts_with($stmt, '#')) {
                    continue;
                }
                // query() 사용: EXECUTE stmt가 SELECT를 실행할 경우
                // 결과셋이 반환되므로 이를 소비해야 unbuffered query 에러 방지
                $result = $this->pdo->query($stmt);
                if ($result !== false) {
                    $result->closeCursor();
                }
            }

            // 마이그레이션 기록
            $this->recordMigration($patch['filename']);

            return ['success' => true];
        } catch (\PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * SQL 문 분리 (세미콜론 기준)
     */
    private function splitSql(string $sql): array
    {
        // 주석 제거 후 세미콜론으로 분리
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';

        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];

            // 문자열 토글
            if (!$inString && ($char === "'" || $char === '"')) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar && ($i === 0 || $sql[$i - 1] !== '\\')) {
                $inString = false;
            }

            if ($char === ';' && !$inString) {
                $stmt = trim($current);
                if (!empty($stmt)) {
                    $statements[] = $stmt;
                }
                $current = '';
            } else {
                $current .= $char;
            }
        }

        $stmt = trim($current);
        if (!empty($stmt)) {
            $statements[] = $stmt;
        }

        return $statements;
    }

    /**
     * DB 버전 업데이트
     */
    private function updateDbVersion(string $version): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->prefix}settings (`key`, `value`) VALUES ('version', ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
        );
        $stmt->execute([$version]);
        $stmt->closeCursor();
    }

    /**
     * database/migrations/ 폴더의 순차 마이그레이션 실행
     * 이미 실행된 마이그레이션은 건너뜀 (rzx_migrations 테이블 기반 추적)
     */
    public function runMigrations(): array
    {
        if (!is_dir($this->migrationDir)) {
            return ['success' => true, 'applied' => 0, 'message' => 'No migrations directory'];
        }

        // migrations 테이블 자동 생성
        $this->ensureMigrationsTable();

        // 이미 실행된 마이그레이션 목록 조회
        $applied = $this->getAppliedMigrations();

        // migrations 폴더에서 SQL 파일 스캔 (정렬)
        $files = glob($this->migrationDir . '/*.sql');
        if (empty($files)) {
            return ['success' => true, 'applied' => 0, 'message' => 'No migration files'];
        }
        sort($files);

        $count = 0;
        $errors = [];

        foreach ($files as $file) {
            $filename = basename($file, '.sql');

            // 이미 실행된 마이그레이션은 건너뜀
            if (in_array($filename, $applied)) {
                continue;
            }

            $this->log("MIGRATION RUN: {$filename}");
            $result = $this->executeMigrationFile($file);

            if ($result['success']) {
                $count++;
                $this->recordMigration($filename);
                $this->log("MIGRATION OK: {$filename}");
            } else {
                $errors[] = $filename . ': ' . $result['error'];
                $this->log("MIGRATION FAIL: {$filename} - {$result['error']}");
                // CREATE IF NOT EXISTS 류는 에러 시에도 계속 진행
                // ALTER 등 실패하면 기록만 하고 계속 (이미 적용된 경우)
                if ($this->isIgnorableError($result['error'])) {
                    $this->recordMigration($filename);
                    $this->log("MIGRATION IGNORED (already applied): {$filename}");
                    $errors = array_filter($errors, fn($e) => !str_starts_with($e, $filename));
                    $count++;
                } else {
                    break; // 심각한 에러면 중단
                }
            }
        }

        return [
            'success' => empty($errors),
            'applied' => $count,
            'errors' => $errors,
        ];
    }

    /**
     * 마이그레이션 파일 실행
     */
    private function executeMigrationFile(string $file): array
    {
        $sql = file_get_contents($file);
        if ($sql === false) {
            return ['success' => false, 'error' => 'Cannot read file'];
        }

        // 테이블 접두사 치환
        if ($this->prefix !== 'rzx_') {
            $sql = str_replace('rzx_', $this->prefix, $sql);
        }

        $statements = $this->splitSql($sql);

        try {
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (empty($stmt) || str_starts_with($stmt, '--') || str_starts_with($stmt, '#')) {
                    continue;
                }
                $result = $this->pdo->query($stmt);
                if ($result !== false) {
                    $result->closeCursor();
                }
            }
            return ['success' => true];
        } catch (\PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 무시 가능한 에러 판별 (이미 적용된 마이그레이션)
     */
    private function isIgnorableError(string $error): bool
    {
        $ignorable = [
            'already exists',       // 테이블/컬럼 이미 존재
            'Duplicate column',     // ALTER ADD 중복
            'Duplicate key name',   // 인덱스 중복
            'check that column',    // AFTER 절 컬럼 없음
        ];
        foreach ($ignorable as $pattern) {
            if (stripos($error, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * migrations 추적 테이블 자동 생성
     */
    private function ensureMigrationsTable(): void
    {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS {$this->prefix}migrations (
                    id INT NOT NULL AUTO_INCREMENT,
                    migration VARCHAR(255) NOT NULL,
                    batch INT NOT NULL DEFAULT 1,
                    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uniq_migration (migration)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\PDOException $e) {
            // 이미 존재하면 무시
        }
    }

    /**
     * 이미 실행된 마이그레이션 목록 조회
     */
    private function getAppliedMigrations(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT migration FROM {$this->prefix}migrations ORDER BY id");
            $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $stmt->closeCursor();
            return $rows;
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * 마이그레이션 기록
     */
    private function recordMigration(string $name): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->prefix}migrations (`migration`, `batch`)
                 VALUES (?, (SELECT COALESCE(MAX(b.batch), 0) + 1 FROM {$this->prefix}migrations b))"
            );
            $stmt->execute(['patch_' . $name]);
            $stmt->closeCursor();
        } catch (\PDOException $e) {
            // migrations 테이블이 없어도 패치 자체는 성공
            error_log('Migration record failed: ' . $e->getMessage());
        }
    }

    /**
     * 로그 기록
     */
    private function log(string $message): void
    {
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0755, true);
        }
        $logFile = $this->logDir . '/migrations.log';
        $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
        @file_put_contents($logFile, $line, FILE_APPEND);
    }
}
