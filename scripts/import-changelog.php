<?php

declare(strict_types=1);

/**
 * Changelog CLI 임포터.
 *
 * 사용:
 *   php scripts/import-changelog.php --file=docs/CHANGELOG.md --locale=ko
 *   php scripts/import-changelog.php --file=docs/CHANGELOG.en.md --locale=en --silent
 *   php scripts/import-changelog.php --file=docs/CHANGELOG.md --locale=ko --dry-run
 *
 * 옵션:
 *   --file=PATH        입력 markdown 파일 (필수)
 *   --locale=CODE      언어 코드 (기본: ko)
 *   --dry-run          실제 적용 없이 계획만 출력
 *   --silent           에러 외 출력 억제 (배포 스크립트용)
 *
 * 종료 코드:
 *   0 = 성공
 *   1 = 인수 오류
 *   2 = 파일 오류
 *   3 = 파싱 실패
 *   4 = DB 오류
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$projectRoot = realpath(__DIR__ . '/..');
if (!$projectRoot) {
    fwrite(STDERR, "프로젝트 루트를 찾을 수 없습니다.\n");
    exit(2);
}

define('BASE_PATH', $projectRoot);
chdir($projectRoot);

require $projectRoot . '/vendor/autoload.php';

use RzxLib\Core\Changelog\ChangelogImporter;
use RzxLib\Core\Changelog\ChangelogParser;

// ── 인수 파싱 ──────────────────────────
$opts = getopt('', ['file:', 'locale::', 'dry-run', 'silent']);
$file = $opts['file'] ?? null;
$locale = $opts['locale'] ?? 'ko';
$dryRun = array_key_exists('dry-run', $opts);
$silent = array_key_exists('silent', $opts);

if (!$file) {
    fwrite(STDERR, "Usage: php scripts/import-changelog.php --file=PATH [--locale=CODE] [--dry-run] [--silent]\n");
    exit(1);
}

if ($file[0] !== '/') {
    $file = $projectRoot . '/' . $file;
}

if (!is_readable($file)) {
    fwrite(STDERR, "파일을 읽을 수 없음: $file\n");
    exit(2);
}

if (!preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale)) {
    fwrite(STDERR, "locale 포맷 오류: $locale (예: ko, en, zh_CN)\n");
    exit(1);
}

// ── 출력 헬퍼 ──────────────────────────
$say = function (string $msg) use ($silent): void {
    if (!$silent) {
        echo $msg . PHP_EOL;
    }
};

$say("📋 Changelog import — $file → locale=$locale" . ($dryRun ? ' (DRY RUN)' : ''));

// ── 파싱 ──────────────────────────────
$markdown = file_get_contents($file);
$result = ChangelogParser::parse($markdown);

if (empty($result['blocks'])) {
    fwrite(STDERR, "파싱 실패 — 유효한 버전 블록 없음\n");
    exit(3);
}

$say(sprintf('  블록 %d 개 파싱됨', count($result['blocks'])));
if (!empty($result['warnings'])) {
    $say(sprintf('  ⚠ 경고 %d 건', count($result['warnings'])));
    foreach ($result['warnings'] as $w) {
        $say('    - ' . $w);
    }
}

// ── DB 연결 ──────────────────────────────
$envFile = $projectRoot . '/.env';
$env = [];
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v);
    }
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $env['DB_HOST'] ?? '127.0.0.1',
    $env['DB_PORT'] ?? '3306',
    $env['DB_DATABASE'] ?? 'voscms_dev'
);

if (!extension_loaded('pdo_mysql')) {
    fwrite(STDERR, "pdo_mysql 확장이 없습니다. php8.3 등 pdo_mysql 지원 버전으로 실행하세요.\n");
    fwrite(STDERR, "예: php8.3 scripts/import-changelog.php ...\n");
    exit(4);
}

try {
    $pdo = new PDO($dsn, $env['DB_USERNAME'] ?? 'rezlyx', $env['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    fwrite(STDERR, "DB 연결 실패: " . $e->getMessage() . "\n");
    exit(4);
}

// ── Dry-run ──────────────────────────────
if ($dryRun) {
    $importer = new ChangelogImporter($pdo);
    $preview = $importer->preview($result['blocks'], $locale);

    $say('');
    $say('예상 결과:');
    $say(sprintf('  🆕 신규 %d 건: %s', count($preview['new_versions']), implode(', ', array_slice($preview['new_versions'], 0, 10))));
    $say(sprintf('  ✏ 변경 %d 건: %s', count($preview['updated_versions']), implode(', ', array_slice($preview['updated_versions'], 0, 10))));
    $say(sprintf('  ⏸ 스킵 %d 건 (내용 동일)', count($preview['unchanged_versions'])));
    if (!empty($preview['protected_versions'])) {
        $say(sprintf('  🛡 보호 %d 건 (수동 번역): %s', count($preview['protected_versions']), implode(', ', $preview['protected_versions'])));
    }
    $say('');
    $say('DRY RUN — 변경사항 없음.');
    exit(0);
}

// ── 실제 임포트 ──────────────────────────
$importer = new ChangelogImporter($pdo);

try {
    $pdo->beginTransaction();
    $stats = $importer->import($result['blocks'], $locale);
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    fwrite(STDERR, "임포트 실패: " . $e->getMessage() . "\n");
    exit(4);
}

$say('');
$say('결과:');
$say(sprintf('  🆕 신규     : %d 건', $stats['created']));
$say(sprintf('  ✏ 업데이트  : %d 건', $stats['updated']));
$say(sprintf('  ⏸ 스킵     : %d 건 (내용 동일)', $stats['skipped']));
if ($stats['protected'] > 0) {
    $say(sprintf('  🛡 보호     : %d 건 (수동 번역 — 원본 재업로드 무시됨)', $stats['protected']));
}
$say('');
$say('✅ 완료');
exit(0);
