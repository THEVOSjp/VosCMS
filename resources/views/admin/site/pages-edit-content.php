<?php
/**
 * RezlyX Admin - 페이지 설정
 * 개별 페이지의 기본 정보, 타입, 콘텐츠/URL 편집
 */
if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$pageSlug = $_GET['slug'] ?? '';
$message = '';
$messageType = '';

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $defaultLocale = $config['locale'] ?? 'ko';

    // AJAX API
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json; charset=utf-8');
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        try {
            if ($action === 'save') {
                $slug = trim($input['slug'] ?? '');
                $newSlug = trim($input['new_slug'] ?? $slug);
                $title = trim($input['title'] ?? '');
                $pageType = $input['page_type'] ?? 'document';
                $content = $input['content'] ?? '';
                $isActive = (int)($input['is_active'] ?? 1);
                $locale = $input['locale'] ?? $defaultLocale;

                if (!$slug || !$title) {
                    echo json_encode(['success' => false, 'message' => '제목과 slug는 필수입니다.']);
                    exit;
                }

                // slug 변경 시 중복 체크
                if ($newSlug !== $slug) {
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}page_contents WHERE page_slug = ?");
                    $chk->execute([$newSlug]);
                    if ((int)$chk->fetchColumn() > 0) {
                        echo json_encode(['success' => false, 'message' => '이미 사용 중인 slug입니다.']);
                        exit;
                    }
                    // 모든 로케일의 slug 변경
                    $pdo->prepare("UPDATE {$prefix}page_contents SET page_slug = ? WHERE page_slug = ?")->execute([$newSlug, $slug]);
                    // 위젯 테이블도
                    $pdo->prepare("UPDATE {$prefix}page_widgets SET page_slug = ? WHERE page_slug = ?")->execute([$newSlug, $slug]);
                    // 메뉴 URL도
                    $pdo->prepare("UPDATE {$prefix}menu_items SET url = ? WHERE url = ?")->execute([$newSlug, $slug]);
                }

                // 해당 로케일 데이터 업데이트 또는 생성
                $existing = $pdo->prepare("SELECT id FROM {$prefix}page_contents WHERE page_slug = ? AND locale = ?");
                $existing->execute([$newSlug, $locale]);
                if ($existing->fetchColumn()) {
                    $pdo->prepare("UPDATE {$prefix}page_contents SET title = ?, page_type = ?, content = ?, is_active = ?, updated_at = NOW() WHERE page_slug = ? AND locale = ?")
                        ->execute([$title, $pageType, $content, $isActive, $newSlug, $locale]);
                } else {
                    $pdo->prepare("INSERT INTO {$prefix}page_contents (page_slug, page_type, locale, title, content, is_active) VALUES (?, ?, ?, ?, ?, ?)")
                        ->execute([$newSlug, $pageType, $locale, $title, $content, $isActive]);
                }

                echo json_encode(['success' => true, 'message' => '저장되었습니다.', 'slug' => $newSlug]);
                exit;
            }

            if ($action === 'delete') {
                $slug = trim($input['slug'] ?? '');
                $pdo->prepare("DELETE FROM {$prefix}page_contents WHERE page_slug = ?")->execute([$slug]);
                $pdo->prepare("DELETE FROM {$prefix}page_widgets WHERE page_slug = ?")->execute([$slug]);
                $pdo->prepare("DELETE FROM {$prefix}menu_items WHERE url = ?")->execute([$slug]);
                echo json_encode(['success' => true, 'message' => '삭제되었습니다.', 'redirect' => $adminUrl . '/site/pages']);
                exit;
            }

            if ($action === 'load_locale') {
                $slug = trim($input['slug'] ?? '');
                $locale = $input['locale'] ?? $defaultLocale;
                $stmt = $pdo->prepare("SELECT title, content FROM {$prefix}page_contents WHERE page_slug = ? AND locale = ?");
                $stmt->execute([$slug, $locale]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data ?: ['title' => '', 'content' => '']]);
                exit;
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;
    }

    // 페이지 데이터 로드
    $pageData = null;
    if ($pageSlug) {
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}page_contents WHERE page_slug = ? AND locale = ?");
        $stmt->execute([$pageSlug, $defaultLocale]);
        $pageData = $stmt->fetch(PDO::FETCH_ASSOC);
        // 다른 로케일이라도
        if (!$pageData) {
            $stmt = $pdo->prepare("SELECT * FROM {$prefix}page_contents WHERE page_slug = ? LIMIT 1");
            $stmt->execute([$pageSlug]);
            $pageData = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    // 활성 언어 목록
    $supportedLangs = json_decode($siteSettings['supported_languages'] ?? '["ko","en","ja"]', true) ?: ['ko','en','ja'];

} catch (PDOException $e) {
    $message = 'DB 오류: ' . $e->getMessage();
    $messageType = 'error';
}

$pageTitle = ($pageData ? htmlspecialchars($pageData['title']) : '새 페이지') . ' - ' . __('site.pages.settings_title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$pageHeaderTitle = __('site.pages.settings_title') ?? '페이지 설정';
?>
<?php $embedMode = !empty($_GET['embed']); ?>
<?php if (!$embedMode): ?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>
<?php else: ?>
<!DOCTYPE html>
<html lang="<?= $config['locale'] ?? 'ko' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <?php include __DIR__ . '/../partials/pwa-head.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', sans-serif; }</style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<div class="p-2">
<?php endif; ?>
                <!-- 브레드크럼 -->
                <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                    <a href="<?= $adminUrl ?>/site/pages" class="hover:text-blue-600"><?= __('site.pages.title') ?></a>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-zinc-900 dark:text-white font-medium"><?= htmlspecialchars($pageData['title'] ?? '새 페이지') ?></span>
                </div>

                <div id="msgArea"></div>

                <!-- 언어 탭 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 mb-6 overflow-hidden">
                    <div class="border-b border-zinc-200 dark:border-zinc-700">
                        <nav class="flex overflow-x-auto px-2 pt-2">
                            <?php foreach ($supportedLangs as $lang): ?>
                            <button onclick="switchLocale('<?= $lang ?>')" class="locale-tab px-4 py-2 text-sm font-medium rounded-t-lg transition <?= $lang === $defaultLocale ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-b-2 border-blue-500' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700' ?>" data-locale="<?= $lang ?>">
                                <?= strtoupper($lang) ?>
                            </button>
                            <?php endforeach; ?>
                        </nav>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- 좌측: 기본 정보 -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 p-6 space-y-4">
                            <h3 class="font-semibold text-zinc-900 dark:text-white"><?= __('site.pages.basic_info') ?? '기본 정보' ?></h3>
                            <!-- 제목 -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.pages.document.page_title') ?? '페이지 제목' ?></label>
                                <input type="text" id="fmTitle" value="<?= htmlspecialchars($pageData['title'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                            </div>
                            <!-- Slug -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Slug (URL)</label>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-zinc-400"><?= $baseUrl ?>/</span>
                                    <input type="text" id="fmSlug" value="<?= htmlspecialchars($pageSlug) ?>" class="flex-1 px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm focus:ring-2 focus:ring-blue-500" <?= !empty($pageData['is_system']) ? 'disabled' : '' ?>>
                                </div>
                            </div>
                            <!-- 콘텐츠 / 외부 URL -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1" id="contentLabel">
                                    <?= ($pageData['page_type'] ?? '') === 'external' ? (__('site.pages.external_url') ?? '외부 URL / 파일 경로') : (__('site.pages.document.page_content') ?? '페이지 내용') ?>
                                </label>
                                <?php if (($pageData['page_type'] ?? '') === 'external'): ?>
                                <input type="text" id="fmContent" value="<?= htmlspecialchars($pageData['content'] ?? '') ?>" placeholder="https://... 또는 pages/custom.php" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-zinc-400 mt-1"><?= __('site.pages.external_url_desc') ?? '외부 URL(https://...)은 iframe으로, 내부 파일(.php/.html)은 include로 렌더링됩니다.' ?></p>
                                <?php else: ?>
                                <textarea id="fmContent" rows="10" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 font-mono"><?= htmlspecialchars($pageData['content'] ?? '') ?></textarea>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- 우측: 설정 -->
                    <div class="space-y-6">
                        <!-- 타입 -->
                        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 p-4 space-y-3">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.pages.page_type') ?? '페이지 타입' ?></label>
                            <select id="fmType" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                                <option value="document" <?= ($pageData['page_type'] ?? '') === 'document' ? 'selected' : '' ?>><?= __('site.pages.type_document') ?></option>
                                <option value="widget" <?= ($pageData['page_type'] ?? '') === 'widget' ? 'selected' : '' ?>><?= __('site.pages.type_widget') ?></option>
                                <option value="external" <?= ($pageData['page_type'] ?? '') === 'external' ? 'selected' : '' ?>><?= __('site.pages.type_external') ?></option>
                            </select>
                        </div>

                        <!-- 활성 -->
                        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.pages.document.is_active') ?? '활성화' ?></span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="fmActive" class="sr-only peer" <?= ($pageData['is_active'] ?? 1) ? 'checked' : '' ?>>
                                    <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>

                        <!-- 액션 -->
                        <div class="space-y-2">
                            <button onclick="savePage()" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"><?= __('common.buttons.save') ?? '저장' ?></button>
                            <?php if ($pageSlug): ?>
                            <a href="<?= $baseUrl ?>/<?= htmlspecialchars($pageSlug) ?>" target="_blank" class="block w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 text-sm text-center"><?= __('site.pages.document.preview') ?? '미리보기' ?></a>
                            <button onclick="deletePage()" class="w-full px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-sm"><?= __('common.buttons.delete') ?? '삭제' ?></button>
                            <?php endif; ?>
                        </div>

                        <!-- 편집 바로가기 -->
                        <?php if ($pageData): ?>
                        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 p-4 space-y-2">
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('site.pages.quick_edit') ?? '빠른 편집' ?></span>
                            <?php if (($pageData['page_type'] ?? '') === 'widget'): ?>
                            <a href="<?= $adminUrl ?>/site/pages/widget-builder?slug=<?= urlencode($pageSlug) ?>" class="block px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg"><?= __('site.pages.open_widget_builder') ?? '위젯 빌더 열기' ?> →</a>
                            <?php elseif (($pageData['page_type'] ?? '') !== 'external'): ?>
                            <a href="<?= $adminUrl ?>/site/pages/edit?slug=<?= urlencode($pageSlug) ?>" class="block px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg"><?= __('site.pages.open_document_editor') ?? '문서 에디터 열기' ?> →</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

<script>
var PAGE_URL = '<?= $adminUrl ?>/site/pages/edit-content';
var SLUG = '<?= htmlspecialchars($pageSlug) ?>';
var CURRENT_LOCALE = '<?= $defaultLocale ?>';
var ADMIN_URL = '<?= $adminUrl ?>';

async function apiFetch(payload) {
    const res = await fetch(PAGE_URL + '?slug=' + SLUG, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify(payload)
    });
    return res.json();
}

function showMsg(type, msg) {
    var area = document.getElementById('msgArea');
    var cls = type === 'success' ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200' : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200';
    area.innerHTML = '<div class="mb-4 p-3 rounded-lg border ' + cls + ' text-sm">' + msg + '</div>';
    setTimeout(function() { area.innerHTML = ''; }, 4000);
}

async function savePage() {
    var data = await apiFetch({
        action: 'save',
        slug: SLUG,
        new_slug: document.getElementById('fmSlug').value.trim(),
        title: document.getElementById('fmTitle').value.trim(),
        page_type: document.getElementById('fmType').value,
        content: document.getElementById('fmContent').value,
        is_active: document.getElementById('fmActive').checked ? 1 : 0,
        locale: CURRENT_LOCALE
    });
    showResultModal(data.success, data.success ? '' : data.message);
    if (data.success && data.slug && data.slug !== SLUG) {
        window.location.href = PAGE_URL + '?slug=' + data.slug;
    }
}

async function deletePage() {
    if (!confirm('이 페이지를 삭제하시겠습니까? 모든 언어의 콘텐츠가 삭제됩니다.')) return;
    var data = await apiFetch({ action: 'delete', slug: SLUG });
    if (data.success && data.redirect) window.location.href = data.redirect;
    else showResultModal(false, data.message);
}

async function switchLocale(locale) {
    CURRENT_LOCALE = locale;
    document.querySelectorAll('.locale-tab').forEach(function(tab) {
        var active = tab.dataset.locale === locale;
        tab.className = 'locale-tab px-4 py-2 text-sm font-medium rounded-t-lg transition ' +
            (active ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-b-2 border-blue-500' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700');
    });
    var data = await apiFetch({ action: 'load_locale', slug: SLUG, locale: locale });
    if (data.success && data.data) {
        document.getElementById('fmTitle').value = data.data.title || '';
        document.getElementById('fmContent').value = data.data.content || '';
    }
    console.log('[PageSettings] locale:', locale);
}
</script>
<?php include BASE_PATH . '/resources/views/admin/partials/result-modal.php'; ?>
<?php if (!$embedMode): ?>
</body>
</html>
<?php else: ?>
</div>
<?php endif; ?>
