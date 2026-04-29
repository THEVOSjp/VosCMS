<?php
/**
 * RezlyX Admin Members Settings - Terms
 * Terms and conditions settings configuration (5 terms with WYSIWYG editor)
 */

require_once __DIR__ . '/_init.php';
$pageTitle = __('members.settings.tabs.terms') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentMemberSettingsPage = 'terms';

// DB 컬럼 타입 확인 및 자동 수정
if ($dbConnected) {
    try {
        $colCheck = $pdo->query("SHOW COLUMNS FROM {$prefix}settings WHERE Field = 'value'");
        $colInfo = $colCheck->fetch(PDO::FETCH_ASSOC);
        $currentType = strtoupper($colInfo['Type'] ?? '');

        // TEXT가 아니면 MEDIUMTEXT로 변경
        if (strpos($currentType, 'TEXT') === false || strpos($currentType, 'VARCHAR') !== false) {
            $pdo->exec("ALTER TABLE {$prefix}settings MODIFY COLUMN `value` MEDIUMTEXT");
            $message = "DB 컬럼 타입을 MEDIUMTEXT로 변경했습니다. 다시 저장해주세요.";
            $messageType = 'info';
        }
    } catch (PDOException $e) {
        // 무시
    }
}

/**
 * Summernote 에디터 HTML 정리 함수
 * Tailwind CSS 변수 및 불필요한 스타일 제거
 */
function cleanEditorHtml(string $html): string
{
    if (empty($html)) {
        return '';
    }

    // Tailwind CSS 변수 제거 (--tw-로 시작하는 모든 변수)
    $html = preg_replace('/--tw-[a-z-]+\s*:\s*[^;]+;\s*/i', '', $html);

    // 빈 style 속성 제거
    $html = preg_replace('/\s*style\s*=\s*["\']\s*["\']/', '', $html);

    // 불필요한 class 속성 정리 (빈 class 제거)
    $html = preg_replace('/\s*class\s*=\s*["\']\s*["\']/', '', $html);

    // 연속 공백 정리
    $html = preg_replace('/\s+/', ' ', $html);

    return trim($html);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_terms') {
        try {
            $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");

            // 시스템 페이지 동의 설정 저장 (config/system-pages.php 의 document 타입)
            $savedCount = 0;
            $sysPagesPosted = $_POST['member_page_consent'] ?? [];
            if (is_array($sysPagesPosted)) {
                foreach ($sysPagesPosted as $slug => $val) {
                    $val = in_array($val, ['required', 'optional', 'disabled'], true) ? $val : 'disabled';
                    $key = 'member_page_consent_' . preg_replace('/[^a-z0-9_-]/', '', strtolower($slug));
                    $stmt->execute([$key, $val]);
                    $memberSettings[$key] = $val;
                    $savedCount++;
                }
            }

            $message = __('settings.success');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

ob_start();
?>

<form method="POST" id="termsForm">
    <input type="hidden" name="action" value="update_terms">

    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors mb-6">
        <?php
        $headerIcon = 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
        $headerTitle = __('members.settings.terms.title');
        $headerDescription = __('members.settings.terms.description');
        $headerIconColor = ''; $headerActions = '';
        include __DIR__ . '/../../components/settings-header.php';
        ?>

        <!-- 시스템 페이지 동의 설정 (config/system-pages.php 의 document 타입 자동 추출) -->
        <?php
        $_sysPages = file_exists(BASE_PATH . '/config/system-pages.php') ? include BASE_PATH . '/config/system-pages.php' : [];
        $_documentPages = array_filter($_sysPages, fn($p) => ($p['type'] ?? '') === 'document');
        ?>
        <?php if (!empty($_documentPages)): ?>
        <div class="mt-6">
            <div class="space-y-3">
                <?php foreach ($_documentPages as $_sp):
                    $_slug = $_sp['slug'] ?? '';
                    if (!$_slug) continue;
                    $_titleKey = $_sp['title'] ?? '';
                    $_pageTitle = $_titleKey ? __($_titleKey) : $_slug;
                    $_emoji = $_sp['emoji'] ?? '📄';
                    $_consentKey = 'member_page_consent_' . preg_replace('/[^a-z0-9_-]/', '', strtolower($_slug));
                    $_currentConsent = $memberSettings[$_consentKey] ?? 'disabled';
                ?>
                <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <span class="text-2xl"><?= $_emoji ?></span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($_pageTitle) ?></p>
                            <p class="text-[11px] text-zinc-400 font-mono">/<?= htmlspecialchars($_slug) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 shrink-0">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="member_page_consent[<?= htmlspecialchars($_slug) ?>]" value="required"
                                   <?= $_currentConsent === 'required' ? 'checked' : '' ?>
                                   class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-zinc-700 dark:text-zinc-300"><?= __('members.settings.terms.consent_required_option') ?></span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="member_page_consent[<?= htmlspecialchars($_slug) ?>]" value="optional"
                                   <?= $_currentConsent === 'optional' ? 'checked' : '' ?>
                                   class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-zinc-700 dark:text-zinc-300"><?= __('members.settings.terms.consent_optional_option') ?></span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="member_page_consent[<?= htmlspecialchars($_slug) ?>]" value="disabled"
                                   <?= $_currentConsent === 'disabled' ? 'checked' : '' ?>
                                   class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-zinc-700 dark:text-zinc-300"><?= __('members.settings.terms.consent_disabled_option') ?></span>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="flex justify-end pt-6 mt-8 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('admin.buttons.save') ?>
            </button>
        </div>
    </div>
</form>

<!-- 다국어 모달 컴포넌트 -->
<?php include __DIR__ . '/../../components/multilang-modal.php'; ?>

<!-- Summernote Editor -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<style>
    .note-editor { border-radius: 0.5rem; overflow: hidden; }
    .note-editor .note-toolbar { background: #f4f4f5; border-color: #d4d4d8; }
    .note-editor .note-editing-area { background: #fff; }
    .note-editor .note-editable { min-height: 200px; }
    .note-editor .note-statusbar { background: #f4f4f5; border-color: #d4d4d8; }
    /* 에디터 내 헤딩 스타일 (Tailwind 리셋 오버라이드) */
    .note-editor .note-editable h1 { font-size: 2em !important; font-weight: bold !important; margin: 0.67em 0 !important; line-height: 1.2 !important; }
    .note-editor .note-editable h2 { font-size: 1.5em !important; font-weight: bold !important; margin: 0.83em 0 !important; line-height: 1.3 !important; }
    .note-editor .note-editable h3 { font-size: 1.17em !important; font-weight: bold !important; margin: 1em 0 !important; line-height: 1.4 !important; }
    .note-editor .note-editable h4 { font-size: 1em !important; font-weight: bold !important; margin: 1.33em 0 !important; line-height: 1.5 !important; }
    .note-editor .note-editable h5 { font-size: 0.83em !important; font-weight: bold !important; margin: 1.67em 0 !important; line-height: 1.5 !important; }
    .note-editor .note-editable h6 { font-size: 0.67em !important; font-weight: bold !important; margin: 2.33em 0 !important; line-height: 1.5 !important; }
    .note-editor .note-editable p { margin: 1em 0 !important; }
    .note-editor .note-editable ul, .note-editor .note-editable ol { margin: 1em 0 !important; padding-left: 2em !important; list-style: revert !important; }
    .note-editor .note-editable li { margin: 0.5em 0 !important; }
    /* 다크 모드 */
    .dark .note-editor { border-color: #52525b; }
    .dark .note-editor .note-toolbar { background: #3f3f46; border-color: #52525b; }
    .dark .note-editor .note-toolbar .note-btn { color: #a1a1aa; background: transparent; border-color: #52525b; }
    .dark .note-editor .note-toolbar .note-btn:hover { color: #fff; background: #52525b; }
    .dark .note-editor .note-editing-area { background: #3f3f46; }
    .dark .note-editor .note-editable { color: #fff; background: #3f3f46; }
    .dark .note-editor .note-statusbar { background: #3f3f46; border-color: #52525b; }
    .dark .note-editor .note-codable { background: #27272a; color: #a1a1aa; }
    /* 드롭다운 다크 모드 */
    .dark .note-dropdown-menu { background: #3f3f46; border-color: #52525b; }
    .dark .note-dropdown-menu .note-dropdown-item { color: #a1a1aa; }
    .dark .note-dropdown-menu .note-dropdown-item:hover { background: #52525b; color: #fff; }
</style>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-ko-KR.min.js"></script>
<script>
(function() {
    function initEditors() {
        if (typeof $ === 'undefined' || typeof $.fn.summernote === 'undefined') {
            console.log('[Summernote] Waiting for Summernote to load...');
            setTimeout(initEditors, 100);
            return;
        }

        $('.summernote-editor').each(function() {
            const $this = $(this);
            if (!$this.hasClass('summernote-initialized')) {
                $this.summernote({
                    lang: 'ko-KR',
                    height: 280,
                    placeholder: '약관 내용을 입력하세요...',
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link', 'table', 'hr']],
                        ['view', ['codeview', 'fullscreen', 'help']]
                    ],
                    callbacks: {
                        onInit: function() {
                            console.log('[Summernote] Initialized:', $this.attr('id'));
                        }
                    }
                });
                $this.addClass('summernote-initialized');
            }
        });

        console.log('[Summernote] All editors initialized');
    }

    // Form submit 전에 Summernote 내용을 textarea에 동기화
    function syncEditorsBeforeSubmit() {
        const form = document.getElementById('termsForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                console.log('[Summernote] Form submit triggered, syncing editors...');

                // 모든 Summernote 에디터 내용을 textarea에 동기화
                $('.summernote-editor').each(function() {
                    const $this = $(this);
                    if ($this.hasClass('summernote-initialized')) {
                        const content = $this.summernote('code');
                        const id = $this.attr('id');
                        console.log('[Summernote] Syncing', id, '- Length:', content.length, 'bytes');

                        // textarea 값을 직접 설정
                        $this.val(content);
                    }
                });

                console.log('[Summernote] All editors synced, submitting form...');
            });
            console.log('[Summernote] Form submit handler attached');
        }
    }

    // DOM 로드 후 에디터 초기화 및 form 핸들러 등록
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initEditors();
            syncEditorsBeforeSubmit();
        });
    } else {
        initEditors();
        syncEditorsBeforeSubmit();
    }
})();
</script>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_layout.php';
