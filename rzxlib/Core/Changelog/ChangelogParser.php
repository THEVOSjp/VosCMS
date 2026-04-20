<?php

declare(strict_types=1);

namespace RzxLib\Core\Changelog;

/**
 * Changelog 마크다운 파서.
 *
 * 포맷 (Keep a Changelog 기반 + VosCMS 확장):
 *   ## [버전] - YYYY-MM-DD — 헤드라인(선택)
 *   ### Added — 부제목(선택)
 *   - 내용
 *   - 내용
 *   ### Fixed
 *   - 내용
 *
 * 섹션 화이트리스트:
 *   공개 : Added, Changed, Deprecated, Removed, Fixed, Security
 *   비공개: Infrastructure, Internal, Refactor, Chore, Docs, Test
 *   기타 : 파싱은 되지만 warnings 에 기록 (관리자 확인 필요)
 */
final class ChangelogParser
{
    /** 공개 섹션 */
    public const PUBLIC_SECTIONS = ['Added', 'Changed', 'Deprecated', 'Removed', 'Fixed', 'Security'];

    /** 비공개 섹션 (is_internal=1 로 저장) */
    public const INTERNAL_SECTIONS = ['Infrastructure', 'Internal', 'Refactor', 'Chore', 'Docs', 'Test'];

    /**
     * 마크다운 파일 내용을 파싱해 버전 블록 배열 반환.
     *
     * @param string $markdown 원본 markdown 전체
     * @return array{
     *     blocks: array<int, array{
     *         version: string,
     *         version_label: string,
     *         release_date: string,
     *         content: string,
     *         content_hash: string,
     *         is_internal: bool,
     *         sections: array<int, array{name: string, public: bool, body: string}>
     *     }>,
     *     warnings: array<int, string>
     * }
     */
    public static function parse(string $markdown): array
    {
        // 개행 정규화
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $lines = explode("\n", $markdown);

        $blocks = [];
        $warnings = [];
        $currentBlock = null;
        $currentBlockLines = [];

        foreach ($lines as $lineIdx => $line) {
            // 버전 헤더 감지: ## [XXX 1.2.3] - 2026-04-20 — 제목
            if (preg_match('/^## \[(.+?)\]\s*-\s*(\d{4}-\d{2}-\d{2})(?:\s*[—–-]\s*(.+))?\s*$/u', $line, $m)) {
                // 이전 블록 마무리
                if ($currentBlock !== null) {
                    $currentBlock = self::finalizeBlock($currentBlock, $currentBlockLines, $warnings);
                    $blocks[] = $currentBlock;
                }

                $versionLabel = trim($m[1]);
                $releaseDate = $m[2];
                $headline = trim($m[3] ?? '');

                // 버전 라벨에서 시맨틱 버전 추출 — SemVer pre-release 포함
                //   예: "VosCMS 2.2.2"          → "2.2.2"
                //   예: "VosCMS 2.1.1-hotfix"   → "2.1.1-hotfix"
                //   예: "1.0.0-beta.3"         → "1.0.0-beta.3"
                if (!preg_match('/(\d+\.\d+(?:\.\d+)?(?:\.\d+)?(?:-[a-zA-Z0-9][a-zA-Z0-9\.\-]*)?)/', $versionLabel, $vm)) {
                    $warnings[] = sprintf('Line %d: 버전 번호를 추출할 수 없습니다 — "%s"', $lineIdx + 1, $versionLabel);
                    $currentBlock = null;
                    $currentBlockLines = [];
                    continue;
                }
                $version = $vm[1];

                $currentBlock = [
                    'version' => $version,
                    'version_label' => $versionLabel . ($headline ? ' — ' . $headline : ''),
                    'release_date' => $releaseDate,
                    'content' => '',
                    'content_hash' => '',
                    'is_internal' => false,
                    'sections' => [],
                ];
                $currentBlockLines = [];
                continue;
            }

            // 블록 내부 라인
            if ($currentBlock !== null) {
                $currentBlockLines[] = $line;
            }
        }

        // 마지막 블록 처리
        if ($currentBlock !== null) {
            $currentBlock = self::finalizeBlock($currentBlock, $currentBlockLines, $warnings);
            $blocks[] = $currentBlock;
        }

        return ['blocks' => $blocks, 'warnings' => $warnings];
    }

    /**
     * 블록 내부를 섹션 단위로 분리하고 메타 계산.
     *
     * @param array<string, mixed> $block
     * @param array<int, string> $lines
     * @param array<int, string> $warnings (by reference)
     * @return array<string, mixed>
     */
    private static function finalizeBlock(array $block, array $lines, array &$warnings): array
    {
        $sections = [];
        $currentSection = null;
        $currentSectionLines = [];
        $hasPublicSection = false;
        $allInternal = true;

        foreach ($lines as $line) {
            if (preg_match('/^### (.+?)(?:\s*[—–-]\s*(.+))?\s*$/u', $line, $m)) {
                // 새 섹션 시작 — 이전 섹션 마무리
                if ($currentSection !== null) {
                    $currentSection['body'] = trim(implode("\n", $currentSectionLines));
                    $sections[] = $currentSection;
                }

                $sectionTitle = trim($m[1]);
                // "Added — subtitle" 같은 경우 첫 단어만 키로 사용
                $sectionName = explode(' ', $sectionTitle)[0];

                $isPublic = in_array($sectionName, self::PUBLIC_SECTIONS, true);
                $isInternal = in_array($sectionName, self::INTERNAL_SECTIONS, true);

                if (!$isPublic && !$isInternal) {
                    $warnings[] = sprintf(
                        '버전 %s: 알 수 없는 섹션 "### %s" — 화이트리스트에 없음. 공개로 가정.',
                        $block['version'],
                        $sectionTitle
                    );
                    $isPublic = true;
                }

                if ($isPublic) {
                    $hasPublicSection = true;
                }
                if (!$isInternal) {
                    $allInternal = false;
                }

                $currentSection = [
                    'name' => $sectionName,
                    'title' => $sectionTitle,
                    'public' => $isPublic,
                    'body' => '',
                ];
                $currentSectionLines = [];
                continue;
            }

            if ($currentSection !== null) {
                $currentSectionLines[] = $line;
            }
        }

        if ($currentSection !== null) {
            $currentSection['body'] = trim(implode("\n", $currentSectionLines));
            $sections[] = $currentSection;
        }

        $block['sections'] = $sections;

        // 섹션이 전부 Internal 이면 블록 자체를 내부용으로 플래그
        $block['is_internal'] = $sections && $allInternal && !$hasPublicSection;

        // content = 원본 블록 markdown (섹션 구조 보존)
        $block['content'] = trim(implode("\n", $lines));
        $block['content_hash'] = md5($block['content']);

        return $block;
    }

    /**
     * 섹션 배열을 공개 가능한 렌더용 HTML 데이터로 변환.
     * 스킨 뷰에서 사용.
     *
     * @param array<int, array{name: string, title?: string, public: bool, body: string}> $sections
     * @param bool $includeInternal 내부 섹션도 포함할지 (관리자 프리뷰용)
     * @return array<int, array<string, mixed>>
     */
    public static function filterVisibleSections(array $sections, bool $includeInternal = false): array
    {
        $out = [];
        foreach ($sections as $sec) {
            if (!$includeInternal && !$sec['public']) {
                continue;
            }
            $out[] = $sec;
        }
        return $out;
    }

    /**
     * 버전 비교 — 내림차순 (최신 먼저).
     */
    public static function compareVersionsDesc(string $a, string $b): int
    {
        return version_compare($b, $a);
    }
}
