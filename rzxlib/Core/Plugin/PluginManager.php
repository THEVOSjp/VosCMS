<?php
namespace RzxLib\Core\Plugin;

/**
 * VosCMS Plugin Manager
 *
 * 플러그인 검색, 로드, 설치, 활성화/비활성화, 삭제를 관리.
 * plugins/ 디렉토리의 plugin.json을 기반으로 동작.
 */
class PluginManager
{
    private static ?self $instance = null;
    private \PDO $pdo;
    private string $pluginsDir;
    private string $prefix;

    /** @var array 로드된 플러그인 매니페스트 */
    private array $loaded = [];

    /** @var array DB에 등록된 활성 플러그인 ID 목록 */
    private array $activeIds = [];

    /** @var array 플러그인이 등록한 라우트 */
    private array $routes = [];

    /** @var array 플러그인이 등록한 관리자 메뉴 */
    private array $adminMenus = [];

    private function __construct(\PDO $pdo, string $pluginsDir, string $prefix = 'rzx_')
    {
        $this->pdo = $pdo;
        $this->pluginsDir = rtrim($pluginsDir, '/');
        $this->prefix = $prefix;
    }

    /**
     * 싱글톤 초기화
     */
    public static function init(\PDO $pdo, string $pluginsDir, string $prefix = 'rzx_'): self
    {
        if (self::$instance === null) {
            self::$instance = new self($pdo, $pluginsDir, $prefix);
        }
        return self::$instance;
    }

    /**
     * 인스턴스 접근
     */
    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    // ─── 로드 ───

    /**
     * 모든 활성 플러그인 로드
     * - DB에서 활성 플러그인 목록 조회
     * - plugin.json 읽기
     * - 훅 파일 include
     * - 라우트/메뉴 수집
     */
    public function loadAll(): void
    {
        // DB에서 활성 플러그인 조회
        $stmt = $this->pdo->prepare("SELECT plugin_id FROM {$this->prefix}plugins WHERE is_active = 1");
        $stmt->execute();
        $this->activeIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($this->activeIds as $pluginId) {
            $this->loadPlugin($pluginId);
        }
    }

    /**
     * 개별 플러그인 로드
     */
    private function loadPlugin(string $pluginId): bool
    {
        $manifest = $this->getManifest($pluginId);
        if (!$manifest) return false;

        $this->loaded[$pluginId] = $manifest;
        $pluginDir = $this->pluginsDir . '/' . $pluginId;

        // 훅 파일 로드
        if (!empty($manifest['hooks'])) {
            foreach ($manifest['hooks'] as $event => $hookFile) {
                $hookPath = $pluginDir . '/' . $hookFile;
                if (file_exists($hookPath)) {
                    $callback = include $hookPath;
                    if (is_callable($callback)) {
                        Hook::on($event, $callback);
                    }
                }
            }
        }

        // 라우트 수집
        if (!empty($manifest['routes'])) {
            foreach (['admin', 'front', 'api'] as $type) {
                foreach ($manifest['routes'][$type] ?? [] as $route) {
                    $route['plugin_id'] = $pluginId;
                    $route['type'] = $type;
                    $route['view_path'] = $pluginDir . '/views/' . $route['view'];
                    $this->routes[] = $route;
                }
            }
        }

        // 관리자 메뉴 수집
        if (!empty($manifest['menus']['admin'])) {
            foreach ($manifest['menus']['admin'] as $menu) {
                $menu['plugin_id'] = $pluginId;
                $this->adminMenus[] = $menu;
            }
        }

        return true;
    }

    // ─── 매니페스트 ───

    /**
     * plugin.json 읽기
     */
    public function getManifest(string $pluginId): ?array
    {
        $jsonPath = $this->pluginsDir . '/' . $pluginId . '/plugin.json';
        if (!file_exists($jsonPath)) return null;

        $data = json_decode(file_get_contents($jsonPath), true);
        if (!$data || empty($data['id'])) return null;

        return $data;
    }

    // ─── 설치/삭제 ───

    /**
     * 플러그인 설치
     */
    public function install(string $pluginId): array
    {
        $manifest = $this->getManifest($pluginId);
        if (!$manifest) {
            return ['success' => false, 'message' => 'plugin.json not found'];
        }

        // 의존성 확인
        $depCheck = $this->checkDependencies($manifest);
        if (!$depCheck['ok']) {
            return ['success' => false, 'message' => $depCheck['message']];
        }

        // DB 등록
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->prefix}plugins (plugin_id, title, description, version, author, category, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE title=VALUES(title), version=VALUES(version), updated_at=NOW()"
        );
        $locale = $_SESSION['locale'] ?? 'ko';
        $title = is_array($manifest['name']) ? ($manifest['name'][$locale] ?? $manifest['name']['en'] ?? $pluginId) : ($manifest['name'] ?? $pluginId);
        $desc = is_array($manifest['description'] ?? '') ? ($manifest['description'][$locale] ?? $manifest['description']['en'] ?? '') : ($manifest['description'] ?? '');
        $author = is_array($manifest['author'] ?? '') ? ($manifest['author']['name'] ?? '') : ($manifest['author'] ?? '');

        $stmt->execute([
            $pluginId, $title, $desc,
            $manifest['version'] ?? '1.0.0',
            $author,
            $manifest['category'] ?? 'general'
        ]);

        // 마이그레이션 실행
        $this->runMigrations($pluginId, $manifest);

        // 샘플 에셋 복사 (이미지 등)
        if (!empty($manifest['sample_assets'])) {
            $pluginDir = $this->pluginsDir . '/' . $pluginId;
            foreach ($manifest['sample_assets'] as $src => $dest) {
                $srcPath = $pluginDir . '/' . $src;
                $destPath = rtrim(BASE_PATH, '/') . '/' . $dest;
                if (is_dir($srcPath)) {
                    if (!is_dir($destPath)) @mkdir($destPath, 0775, true);
                    foreach (scandir($srcPath) as $file) {
                        if ($file === '.' || $file === '..') continue;
                        if (!file_exists($destPath . '/' . $file)) {
                            @copy($srcPath . '/' . $file, $destPath . '/' . $file);
                        }
                    }
                }
            }
        }

        // 기본 설정 저장
        if (!empty($manifest['settings']['defaults'])) {
            foreach ($manifest['settings']['defaults'] as $key => $value) {
                $this->setSetting($pluginId, $key, is_array($value) ? json_encode($value) : $value);
            }
        }

        return ['success' => true, 'message' => 'Plugin installed: ' . $pluginId];
    }

    /**
     * 플러그인 삭제
     */
    public function uninstall(string $pluginId): array
    {
        $this->pdo->prepare("DELETE FROM {$this->prefix}plugins WHERE plugin_id = ?")->execute([$pluginId]);
        $this->pdo->prepare("DELETE FROM {$this->prefix}plugin_migrations WHERE plugin_id = ?")->execute([$pluginId]);
        $this->pdo->prepare("DELETE FROM {$this->prefix}plugin_settings WHERE plugin_id = ?")->execute([$pluginId]);

        return ['success' => true, 'message' => 'Plugin uninstalled: ' . $pluginId];
    }

    // ─── 활성화/비활성화 ───

    public function activate(string $pluginId): array
    {
        $this->pdo->prepare("UPDATE {$this->prefix}plugins SET is_active = 1, updated_at = NOW() WHERE plugin_id = ?")->execute([$pluginId]);
        return ['success' => true];
    }

    public function deactivate(string $pluginId): array
    {
        $this->pdo->prepare("UPDATE {$this->prefix}plugins SET is_active = 0, updated_at = NOW() WHERE plugin_id = ?")->execute([$pluginId]);
        return ['success' => true];
    }

    // ─── 마이그레이션 ───

    private function runMigrations(string $pluginId, array $manifest): void
    {
        if (empty($manifest['migrations'])) return;

        $pluginDir = $this->pluginsDir . '/' . $pluginId;

        foreach ($manifest['migrations'] as $file) {
            // 이미 실행된 마이그레이션 스킵
            $chk = $this->pdo->prepare("SELECT id FROM {$this->prefix}plugin_migrations WHERE plugin_id = ? AND migration_file = ?");
            $chk->execute([$pluginId, $file]);
            if ($chk->fetch()) continue;

            $sqlPath = $pluginDir . '/' . $file;
            if (!file_exists($sqlPath)) continue;

            $sql = file_get_contents($sqlPath);
            if ($sql) {
                $this->pdo->exec($sql);
                $this->pdo->prepare("INSERT INTO {$this->prefix}plugin_migrations (plugin_id, migration_file) VALUES (?, ?)")
                    ->execute([$pluginId, $file]);
            }
        }
    }

    // ─── 의존성 ───

    private function checkDependencies(array $manifest): array
    {
        $requires = $manifest['requires'] ?? [];

        // PHP 버전
        if (!empty($requires['php'])) {
            $req = ltrim($requires['php'], '>=<');
            if (version_compare(PHP_VERSION, $req, '<')) {
                return ['ok' => false, 'message' => "PHP {$requires['php']} required, current: " . PHP_VERSION];
            }
        }

        // 필수 플러그인
        if (!empty($requires['plugins'])) {
            foreach ($requires['plugins'] as $depId) {
                if (!in_array($depId, $this->activeIds)) {
                    // DB에서도 확인
                    $chk = $this->pdo->prepare("SELECT is_active FROM {$this->prefix}plugins WHERE plugin_id = ?");
                    $chk->execute([$depId]);
                    $dep = $chk->fetch(\PDO::FETCH_ASSOC);
                    if (!$dep || !$dep['is_active']) {
                        return ['ok' => false, 'message' => "Required plugin not active: {$depId}"];
                    }
                }
            }
        }

        return ['ok' => true, 'message' => ''];
    }

    // ─── 설정 ───

    public function getSetting(string $pluginId, string $key, mixed $default = null): mixed
    {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM {$this->prefix}plugin_settings WHERE plugin_id = ? AND setting_key = ?");
        $stmt->execute([$pluginId, $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    public function setSetting(string $pluginId, string $key, string $value): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->prefix}plugin_settings (plugin_id, setting_key, setting_value)
             VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        $stmt->execute([$pluginId, $key, $value]);
    }

    // ─── 조회 ───

    /**
     * plugins/ 디렉토리에서 사용 가능한 전체 플러그인 목록
     */
    public function getAvailable(): array
    {
        $result = [];
        foreach (glob($this->pluginsDir . '/*/plugin.json') as $jsonPath) {
            $data = json_decode(file_get_contents($jsonPath), true);
            if ($data && !empty($data['id'])) {
                $data['_dir'] = basename(dirname($jsonPath));
                $data['_installed'] = $this->isInstalled($data['id']);
                $data['_active'] = in_array($data['id'], $this->activeIds);
                $result[] = $data;
            }
        }
        return $result;
    }

    /**
     * DB에 설치된 플러그인 목록
     */
    public function getInstalled(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->prefix}plugins ORDER BY installed_at DESC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function isInstalled(string $pluginId): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM {$this->prefix}plugins WHERE plugin_id = ?");
        $stmt->execute([$pluginId]);
        return (bool)$stmt->fetch();
    }

    public function isActive(string $pluginId): bool
    {
        return in_array($pluginId, $this->activeIds);
    }

    /**
     * 로드된 플러그인의 라우트 목록
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * 로드된 플러그인의 관리자 메뉴 목록
     */
    public function getAdminMenus(): array
    {
        return $this->adminMenus;
    }

    /**
     * 로드된 플러그인 매니페스트
     */
    public function getLoaded(): array
    {
        return $this->loaded;
    }
}
