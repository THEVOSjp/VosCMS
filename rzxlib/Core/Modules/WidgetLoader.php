<?php
/**
 * RezlyX Widget Loader
 *
 * 파일 기반 위젯 시스템의 핵심 로더.
 * widgets/ 폴더를 스캔하여 widget.json을 읽고,
 * DB(rzx_widgets)와 동기화하며, render.php를 실행합니다.
 *
 * 사용법:
 *   $loader = new WidgetLoader($pdo, BASE_PATH . '/widgets');
 *   $loader->syncToDatabase();                    // DB 동기화 (관리자 진입 시)
 *   $html = $loader->render('hero', $config, $widget, $renderer);  // 위젯 렌더링
 */

namespace RzxLib\Core\Modules;

class WidgetLoader
{
    private \PDO $pdo;
    private string $widgetsDir;
    private ?array $registry = null;

    public function __construct(\PDO $pdo, string $widgetsDir)
    {
        $this->pdo = $pdo;
        $this->widgetsDir = rtrim($widgetsDir, '/\\');
    }

    /**
     * widgets/ 폴더 스캔 → widget.json 파싱 → 레지스트리 반환
     * @return array<string, array> slug => widget.json 데이터
     */
    public function scan(): array
    {
        if ($this->registry !== null) return $this->registry;

        $this->registry = [];
        if (!is_dir($this->widgetsDir)) return $this->registry;

        $dirs = glob($this->widgetsDir . '/*/widget.json');
        foreach ($dirs as $jsonPath) {
            $data = json_decode(file_get_contents($jsonPath), true);
            if (!$data || empty($data['slug'])) continue;

            $slug = $data['slug'];
            $data['_dir'] = dirname($jsonPath);
            $data['_json_path'] = $jsonPath;
            $data['_has_render'] = file_exists(dirname($jsonPath) . '/render.php');
            $data['_has_thumbnail'] = file_exists(dirname($jsonPath) . '/' . ($data['thumbnail'] ?? 'thumbnail.png'));

            $this->registry[$slug] = $data;
        }

        return $this->registry;
    }

    /**
     * 특정 위젯의 widget.json 데이터 반환
     */
    public function get(string $slug): ?array
    {
        $reg = $this->scan();
        return $reg[$slug] ?? null;
    }

    /**
     * 파일 기반 위젯의 render.php 존재 여부
     */
    public function hasRender(string $slug): bool
    {
        $widget = $this->get($slug);
        return $widget && ($widget['_has_render'] ?? false);
    }

    /**
     * render.php 실행하여 HTML 반환
     *
     * render.php에서 사용 가능한 변수:
     *   $config   - 위젯 설정
     *   $widget   - 위젯 DB 행
     *   $renderer - WidgetRenderer 인스턴스
     *   $pdo      - PDO 인스턴스
     *   $baseUrl  - 사이트 기본 URL
     *   $locale   - 현재 로케일
     *   $loader   - WidgetLoader 인스턴스 (자기 자신)
     */
    public function render(string $slug, array $config, array $widget, WidgetRenderer $renderer): string
    {
        $widgetDef = $this->get($slug);
        if (!$widgetDef || !($widgetDef['_has_render'] ?? false)) {
            return '';
        }

        $renderFile = $widgetDef['_dir'] . '/render.php';

        // render.php에 전달할 변수들
        $pdo     = $this->pdo;
        $baseUrl = $renderer->getBaseUrl();
        $locale  = $renderer->getLocale();
        $loader  = $this;

        // render.php는 return 문으로 HTML을 반환해야 함
        try {
            $siteSettings = $GLOBALS['siteSettings'] ?? [];
            $result = (function() use ($renderFile, $config, $widget, $renderer, $pdo, $baseUrl, $locale, $loader, $siteSettings) {
                return include $renderFile;
            })();
            // render.php가 return으로 문자열을 반환하거나, ob에 출력한 경우 모두 처리
            if (is_string($result) && strlen($result) > 0) return $result;
            // include가 1을 반환한 경우 (return 없이 ob 출력)
            return '';
        } catch (\Throwable $e) {
            error_log("WidgetLoader render error [{$slug}]: " . $e->getMessage());
            return '<!-- Widget render error: ' . htmlspecialchars($slug) . ' -->';
        }
    }

    /**
     * 다국어 텍스트에서 현재 로케일 값 추출
     * @param string|array $value  widget.json의 name/description (문자열 또는 로케일 객체)
     * @param string $locale       현재 로케일
     */
    public static function localizedValue($value, string $locale = 'ko'): string
    {
        if (is_string($value)) return $value;
        if (is_array($value)) {
            return $value[$locale] ?? $value['en'] ?? $value['ko'] ?? reset($value) ?: '';
        }
        return '';
    }

    /**
     * 파일 위젯들을 DB(rzx_widgets)와 양방향 동기화
     * - 파일에 있고 DB에 없으면 → INSERT (자동 등록)
     * - 파일에 있고 DB에 있으면 → UPDATE (버전 변경 시)
     * - DB에 있고 파일에 없으면 → DELETE (builtin만, custom 유지)
     */
    public function syncToDatabase(): void
    {
        $fileWidgets = $this->scan();

        // DB에서 기존 위젯 목록 조회 (config_schema 포함하여 내용 변경 감지)
        $stmt = $this->pdo->query("SELECT id, slug, version, type, config_schema FROM rzx_widgets");
        $dbWidgets = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $dbWidgets[$row['slug']] = $row;
        }

        // 1) 파일 → DB: 새 위젯 등록 / 기존 위젯 업데이트
        foreach ($fileWidgets as $slug => $def) {
            $name = self::localizedValue($def['name'] ?? $slug, 'en');
            $desc = self::localizedValue($def['description'] ?? '', 'en');
            $category = $def['category'] ?? 'general';
            $icon = $def['icon'] ?? 'cube';
            $version = $def['version'] ?? '1.0.0';
            $author = $def['author'] ?? 'Unknown';
            $configSchema = json_encode($def['config_schema'] ?? new \stdClass(), JSON_UNESCAPED_UNICODE);

            if (isset($dbWidgets[$slug])) {
                // 이미 존재 → 버전 또는 config_schema가 다르면 업데이트
                $dbSchema = $dbWidgets[$slug]['config_schema'] ?? '{}';
                $needsUpdate = ($dbWidgets[$slug]['version'] !== $version)
                    || ($dbSchema !== $configSchema);
                if ($needsUpdate) {
                    $update = $this->pdo->prepare("
                        UPDATE rzx_widgets SET name=?, description=?, category=?, icon=?, version=?, author=?, config_schema=?, updated_at=NOW()
                        WHERE slug=?
                    ");
                    $update->execute([$name, $desc, $category, $icon, $version, $author, $configSchema, $slug]);
                }
            } else {
                // 새 위젯 → INSERT
                $insert = $this->pdo->prepare("
                    INSERT INTO rzx_widgets (slug, name, description, type, category, icon, version, author, config_schema, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, 'builtin', ?, ?, ?, ?, ?, 1, NOW(), NOW())
                ");
                $insert->execute([$slug, $name, $desc, $category, $icon, $version, $author, $configSchema]);
            }
        }

        // 2) DB → 파일: 폴더가 삭제된 builtin 위젯 제거
        //    custom 위젯은 파일 시스템과 무관하므로 유지
        foreach ($dbWidgets as $slug => $row) {
            if ($row['type'] !== 'builtin') continue;
            if (isset($fileWidgets[$slug])) continue;

            // 폴더가 존재하지 않는 builtin → 페이지 연결도 제거 후 삭제
            $id = (int)$row['id'];
            $this->pdo->prepare("DELETE FROM rzx_page_widgets WHERE widget_id = ?")->execute([$id]);
            $this->pdo->prepare("DELETE FROM rzx_widgets WHERE id = ?")->execute([$id]);
            error_log("WidgetLoader: removed orphan widget '{$slug}' (folder deleted)");
        }
    }

    /**
     * 위젯 폴더 경로 반환
     */
    public function getWidgetDir(string $slug): ?string
    {
        $widget = $this->get($slug);
        return $widget ? $widget['_dir'] : null;
    }

    /**
     * 썸네일 URL 반환 (웹 경로)
     */
    public function getThumbnailUrl(string $slug, string $baseUrl = ''): ?string
    {
        $widget = $this->get($slug);
        if (!$widget || !($widget['_has_thumbnail'] ?? false)) return null;

        $thumbnailFile = $widget['thumbnail'] ?? 'thumbnail.png';
        return $baseUrl . '/widgets/' . $slug . '/' . $thumbnailFile;
    }

    /**
     * 전체 위젯 목록 (팔레트용)
     * widget.json 기반 데이터를 DB 포맷과 호환되게 변환
     */
    public function getAllForPalette(string $locale = 'ko'): array
    {
        $result = [];
        foreach ($this->scan() as $slug => $def) {
            $result[] = [
                'slug' => $slug,
                'name' => self::localizedValue($def['name'] ?? $slug, $locale),
                'description' => self::localizedValue($def['description'] ?? '', $locale),
                'category' => $def['category'] ?? 'general',
                'icon' => $def['icon'] ?? 'cube',
                'version' => $def['version'] ?? '1.0.0',
                'thumbnail' => $def['thumbnail'] ?? null,
                'has_render' => $def['_has_render'] ?? false,
                // 다국어 원본 데이터 (팔레트 JS용)
                'name_i18n' => $def['name'] ?? $slug,
                'description_i18n' => $def['description'] ?? '',
            ];
        }
        return $result;
    }
}
