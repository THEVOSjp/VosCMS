<?php
/**
 * Changelog 관리자 API
 *
 * POST /admin/site/changelog/api
 *
 * action:
 *   preview       — 업로드된 MD 파싱 + DB diff 미리보기 (실제 저장 없음)
 *   apply         — 업로드된 MD 파싱 + DB merge 실행
 *   toggle_active — id 의 is_active 토글
 *   toggle_internal — id 의 is_internal 토글
 *   delete        — id 삭제
 *   translate     — AI 번역 실행 (Phase 2, 현재는 비활성)
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

/** @var PDO $pdo */
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$action = $_POST['action'] ?? '';

use RzxLib\Core\Changelog\ChangelogImporter;
use RzxLib\Core\Changelog\ChangelogParser;
use RzxLib\Core\Translate\TranslatorFactory;

$respond = function (bool $ok, array $data = [], int $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $ok] + $data, JSON_UNESCAPED_UNICODE);
    exit;
};

try {
    switch ($action) {
        // ─────────────────────────────────
        case 'preview':
        case 'apply': {
            $locale = trim((string)($_POST['locale'] ?? 'ko'));
            if (!preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale)) {
                $respond(false, ['error' => 'Invalid locale']);
            }

            // 파일 업로드 or 텍스트
            $md = '';
            if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                $md = file_get_contents($_FILES['file']['tmp_name']);
                // 파일명에서 locale 자동 감지 (CHANGELOG.en.md 등)
                $uploadedName = $_FILES['file']['name'] ?? '';
                if (preg_match('/CHANGELOG\.([a-z]{2}(?:_[A-Z]{2})?)\.md$/i', $uploadedName, $m)) {
                    if (empty($_POST['locale_manual'])) {
                        $locale = $m[1];
                    }
                }
            } elseif (!empty($_POST['markdown'])) {
                $md = (string)$_POST['markdown'];
            } else {
                $respond(false, ['error' => '파일 또는 markdown 내용이 필요합니다.']);
            }

            if (strlen($md) < 10) {
                $respond(false, ['error' => '내용이 너무 짧습니다.']);
            }

            $parsed = ChangelogParser::parse($md);
            if (empty($parsed['blocks'])) {
                $respond(false, [
                    'error' => '파싱 실패 — 유효한 버전 블록 없음',
                    'warnings' => $parsed['warnings'],
                ]);
            }

            $importer = new ChangelogImporter($pdo);

            if ($action === 'preview') {
                $preview = $importer->preview($parsed['blocks'], $locale);
                $respond(true, [
                    'locale' => $locale,
                    'block_count' => count($parsed['blocks']),
                    'warnings' => $parsed['warnings'],
                    'new' => $preview['new_versions'],
                    'updated' => $preview['updated_versions'],
                    'unchanged' => $preview['unchanged_versions'],
                    'protected' => $preview['protected_versions'],
                ]);
            }

            // apply
            $pdo->beginTransaction();
            $stats = $importer->import($parsed['blocks'], $locale);
            $pdo->commit();

            $respond(true, [
                'locale' => $locale,
                'created' => $stats['created'],
                'updated' => $stats['updated'],
                'skipped' => $stats['skipped'],
                'protected' => $stats['protected'],
                'warnings' => $parsed['warnings'],
            ]);
        }

        // ─────────────────────────────────
        case 'toggle_active': {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) $respond(false, ['error' => 'id 필요']);
            $pdo->prepare("UPDATE {$prefix}changelog SET is_active = 1 - is_active WHERE id = ?")
                ->execute([$id]);
            $stmt = $pdo->prepare("SELECT is_active FROM {$prefix}changelog WHERE id = ?");
            $stmt->execute([$id]);
            $respond(true, ['is_active' => (int)$stmt->fetchColumn()]);
        }

        case 'toggle_internal': {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) $respond(false, ['error' => 'id 필요']);
            $pdo->prepare("UPDATE {$prefix}changelog SET is_internal = 1 - is_internal WHERE id = ?")
                ->execute([$id]);
            $stmt = $pdo->prepare("SELECT is_internal FROM {$prefix}changelog WHERE id = ?");
            $stmt->execute([$id]);
            $respond(true, ['is_internal' => (int)$stmt->fetchColumn()]);
        }

        case 'delete': {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) $respond(false, ['error' => 'id 필요']);
            $pdo->prepare("DELETE FROM {$prefix}changelog WHERE id = ?")->execute([$id]);
            $respond(true);
        }

        // ─────────────────────────────────
        case 'translate': {
            $translator = TranslatorFactory::make();
            if (!$translator->isAvailable()) {
                $respond(false, [
                    'error' => 'AI 번역 엔진이 아직 준비되지 않았습니다. config/translator.php 에서 driver 를 활성화하세요.',
                    'driver' => $translator->name(),
                ]);
            }

            $version = trim((string)($_POST['version'] ?? ''));
            $sourceLocale = trim((string)($_POST['source_locale'] ?? 'ko'));
            $targetLocale = trim((string)($_POST['target_locale'] ?? ''));

            if (!$version || !$targetLocale) {
                $respond(false, ['error' => 'version, target_locale 필요']);
            }

            // 원본 조회
            $src = $pdo->prepare(
                "SELECT version_label, release_date, content, content_hash
                   FROM {$prefix}changelog
                  WHERE version = ? AND locale = ?"
            );
            $src->execute([$version, $sourceLocale]);
            $original = $src->fetch(PDO::FETCH_ASSOC);
            if (!$original) $respond(false, ['error' => '원본 없음']);

            // 대상 확인 (수동 번역은 덮어쓰지 않음)
            $tgt = $pdo->prepare(
                "SELECT id, translation_source
                   FROM {$prefix}changelog
                  WHERE version = ? AND locale = ?"
            );
            $tgt->execute([$version, $targetLocale]);
            $existing = $tgt->fetch(PDO::FETCH_ASSOC);

            if ($existing && $existing['translation_source'] === 'manual') {
                $respond(false, ['error' => '수동 번역이 존재 — AI 가 덮어쓰지 않음', 'skipped' => true]);
            }

            // 번역
            $translatedContent = $translator->translate($original['content'], $sourceLocale, $targetLocale);
            $translatedLabel = $translator->translate($original['version_label'], $sourceLocale, $targetLocale);

            if ($existing) {
                $pdo->prepare(
                    "UPDATE {$prefix}changelog
                        SET version_label = ?, content = ?, content_hash = ?,
                            translation_source = 'ai', source_locale = ?, source_hash = ?,
                            updated_at = CURRENT_TIMESTAMP
                      WHERE id = ?"
                )->execute([
                    $translatedLabel,
                    $translatedContent,
                    md5($translatedContent),
                    $sourceLocale,
                    $original['content_hash'],
                    $existing['id'],
                ]);
            } else {
                $pdo->prepare(
                    "INSERT INTO {$prefix}changelog
                        (version, version_label, release_date, locale, content, content_hash,
                         translation_source, source_locale, source_hash, is_internal, is_active)
                     VALUES
                        (?, ?, ?, ?, ?, ?, 'ai', ?, ?, 0, 1)"
                )->execute([
                    $version,
                    $translatedLabel,
                    $original['release_date'],
                    $targetLocale,
                    $translatedContent,
                    md5($translatedContent),
                    $sourceLocale,
                    $original['content_hash'],
                ]);
            }

            $respond(true, ['translated' => true, 'version' => $version, 'target_locale' => $targetLocale]);
        }

        // ─────────────────────────────────
        default:
            $respond(false, ['error' => 'Unknown action: ' . $action], 400);
    }
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $respond(false, ['error' => $e->getMessage()], 500);
}
