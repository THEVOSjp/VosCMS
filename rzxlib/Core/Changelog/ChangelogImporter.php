<?php

declare(strict_types=1);

namespace RzxLib\Core\Changelog;

use PDO;

/**
 * 파싱된 블록을 rzx_changelog 테이블에 병합(UPSERT).
 *
 * 동작:
 *   - DB 에 (version, locale) 없으면 INSERT (신규)
 *   - 있고 content_hash 같으면 스킵 (변경 없음)
 *   - 있고 content_hash 다르면 UPDATE (수정 반영, translation_source=original 만)
 *   - DB 에만 있고 파일에 없는 버전은 건드리지 않음 (안전)
 *
 * 수동 번역(translation_source='manual')은 원본 재업로드 시 건드리지 않고 경고만.
 */
final class ChangelogImporter
{
    public function __construct(private PDO $pdo) {}

    /**
     * @param array<int, array<string, mixed>> $blocks ChangelogParser::parse() 결과의 'blocks'
     * @param string $locale 임포트 locale (ko/en/...)
     * @return array{
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     protected: int,
     *     details: array<int, array{version: string, action: string}>
     * }
     */
    public function import(array $blocks, string $locale): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $protectedCount = 0;
        $details = [];

        $sel = $this->pdo->prepare(
            "SELECT id, content_hash, translation_source
               FROM rzx_changelog
              WHERE version = :v AND locale = :l"
        );
        $ins = $this->pdo->prepare(
            "INSERT INTO rzx_changelog
                (version, version_label, release_date, locale, content, content_hash,
                 translation_source, source_locale, is_internal, is_active)
             VALUES
                (:version, :label, :rdate, :locale, :content, :hash,
                 'original', NULL, :internal, 1)"
        );
        $upd = $this->pdo->prepare(
            "UPDATE rzx_changelog
                SET version_label = :label,
                    release_date  = :rdate,
                    content       = :content,
                    content_hash  = :hash,
                    is_internal   = :internal,
                    updated_at    = CURRENT_TIMESTAMP
              WHERE id = :id"
        );

        foreach ($blocks as $b) {
            $sel->execute([':v' => $b['version'], ':l' => $locale]);
            $existing = $sel->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                // 신규
                $ins->execute([
                    ':version'  => $b['version'],
                    ':label'    => $b['version_label'],
                    ':rdate'    => $b['release_date'],
                    ':locale'   => $locale,
                    ':content'  => $b['content'],
                    ':hash'     => $b['content_hash'],
                    ':internal' => $b['is_internal'] ? 1 : 0,
                ]);
                $created++;
                $details[] = ['version' => $b['version'], 'action' => 'created'];
                continue;
            }

            // 기존 레코드
            if ($existing['translation_source'] === 'manual') {
                // 수동 번역은 원본 업로드가 덮어쓰지 않음
                $protectedCount++;
                $details[] = ['version' => $b['version'], 'action' => 'protected_manual'];
                continue;
            }

            if ($existing['content_hash'] === $b['content_hash']) {
                $skipped++;
                continue;
            }

            // 내용 변경 — UPDATE
            $upd->execute([
                ':label'    => $b['version_label'],
                ':rdate'    => $b['release_date'],
                ':content'  => $b['content'],
                ':hash'     => $b['content_hash'],
                ':internal' => $b['is_internal'] ? 1 : 0,
                ':id'       => $existing['id'],
            ]);
            $updated++;
            $details[] = ['version' => $b['version'], 'action' => 'updated'];
        }

        return [
            'created'   => $created,
            'updated'   => $updated,
            'skipped'   => $skipped,
            'protected' => $protectedCount,
            'details'   => $details,
        ];
    }

    /**
     * Dry-run — 실제 변경 없이 예상 결과만 계산.
     *
     * @param array<int, array<string, mixed>> $blocks
     * @return array{
     *     new_versions: array<int, string>,
     *     updated_versions: array<int, string>,
     *     unchanged_versions: array<int, string>,
     *     protected_versions: array<int, string>
     * }
     */
    public function preview(array $blocks, string $locale): array
    {
        $newVersions = [];
        $updatedVersions = [];
        $unchangedVersions = [];
        $protectedVersions = [];

        $sel = $this->pdo->prepare(
            "SELECT content_hash, translation_source
               FROM rzx_changelog
              WHERE version = :v AND locale = :l"
        );

        foreach ($blocks as $b) {
            $sel->execute([':v' => $b['version'], ':l' => $locale]);
            $existing = $sel->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                $newVersions[] = $b['version'];
            } elseif ($existing['translation_source'] === 'manual') {
                $protectedVersions[] = $b['version'];
            } elseif ($existing['content_hash'] === $b['content_hash']) {
                $unchangedVersions[] = $b['version'];
            } else {
                $updatedVersions[] = $b['version'];
            }
        }

        return [
            'new_versions'       => $newVersions,
            'updated_versions'   => $updatedVersions,
            'unchanged_versions' => $unchangedVersions,
            'protected_versions' => $protectedVersions,
        ];
    }
}
