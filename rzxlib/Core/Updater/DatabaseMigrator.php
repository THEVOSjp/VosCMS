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
    private string $prefix;
    private string $logDir;

    public function __construct(\PDO $pdo, string $basePath)
    {
        $this->pdo = $pdo;
        $this->patchDir = rtrim($basePath, '/\\') . '/database/patches';
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
            return $stmt->fetchColumn() ?: '1.0.0';
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

        // SQL 문 분리 (세미콜론 기준, 문자열 내 세미콜론 무시)
        $statements = $this->splitSql($sql);

        $this->pdo->beginTransaction();
        try {
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (empty($stmt) || str_starts_with($stmt, '--') || str_starts_with($stmt, '#')) {
                    continue;
                }
                $this->pdo->exec($stmt);
            }
            $this->pdo->commit();

            // 마이그레이션 기록
            $this->recordMigration($patch['filename']);

            return ['success' => true];
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
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
