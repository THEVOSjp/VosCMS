<?php
/**
 * RezlyX Admin Members Settings - Terms
 * Terms and conditions settings configuration (5 terms with WYSIWYG editor)
 */

require_once __DIR__ . '/_init.php';

$pageTitle = __('admin.members.settings.tabs.terms') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
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

            // 디버깅: POST 데이터 확인
            $debugLog = [];

            // 5개 약관 저장
            for ($i = 1; $i <= 5; $i++) {
                $title = trim($_POST["member_term_{$i}_title"] ?? '');
                $rawContent = $_POST["member_term_{$i}_content"] ?? '';
                $content = cleanEditorHtml($rawContent);
                $consent = $_POST["member_term_{$i}_consent"] ?? 'disabled';

                // 디버깅 로그
                $debugLog["term_{$i}"] = [
                    'raw_length' => strlen($rawContent),
                    'clean_length' => strlen($content),
                    'raw_preview' => substr($rawContent, 0, 200),
                ];

                $stmt->execute(["member_term_{$i}_title", $title]);
                $stmt->execute(["member_term_{$i}_content", $content]);
                $stmt->execute(["member_term_{$i}_consent", $consent]);

                $memberSettings["member_term_{$i}_title"] = $title;
                $memberSettings["member_term_{$i}_content"] = $content;
                $memberSettings["member_term_{$i}_consent"] = $consent;
            }

            // 디버깅: 저장 후 DB에서 다시 읽어서 길이 확인
            $checkStmt = $pdo->prepare("SELECT `key`, LENGTH(`value`) as len FROM {$prefix}settings WHERE `key` LIKE 'member_term_%_content'");
            $checkStmt->execute();
            $dbLengths = [];
            while ($row = $checkStmt->fetch(PDO::FETCH_ASSOC)) {
                $dbLengths[$row['key']] = $row['len'];
            }

            // 디버깅 메시지 생성
            $debugInfo = "DEBUG:\n";
            foreach ($debugLog as $key => $info) {
                $dbKey = 'member_' . $key . '_content';
                $dbLen = $dbLengths[$dbKey] ?? 'N/A';
                $debugInfo .= "{$key}: POST={$info['raw_length']}bytes, Clean={$info['clean_length']}bytes, DB={$dbLen}bytes\n";
            }

            $message = __('admin.settings.success') . "\n\n" . $debugInfo;
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('admin.settings.error_save') . ': ' . $e->getMessage();
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
        $headerTitle = __('admin.members.settings.terms.title');
        $headerDescription = __('admin.members.settings.terms.description');
        $headerIconColor = ''; $headerActions = '';
        include __DIR__ . '/../../components/settings-header.php';
        ?>

        <?php for ($i = 1; $i <= 5; $i++): ?>
        <!-- 회원 가입 약관 <?= $i ?> -->
        <div class="<?= $i > 1 ? 'mt-8 pt-8 border-t dark:border-zinc-700' : '' ?>">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-4">
                <?= __('admin.members.settings.terms.term_section') ?> <?= $i ?>
            </h3>

            <!-- 약관 제목 -->
            <div class="mb-4">
                <div class="flex items-center gap-2 mb-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        <?= __('admin.members.settings.terms.term_title') ?>
                    </label>
                    <button type="button" onclick="openMultilangModal('term.<?= $i ?>.title', 'term_<?= $i ?>_title', 'text')"
                            class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 rounded hover:bg-blue-100 dark:hover:bg-blue-900/50 transition">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                        <?= __('admin.settings.multilang.button_title') ?>
                    </button>
                </div>
                <?php $termTitleValue = getTranslatedValue("term.{$i}.title", "member_term_{$i}_title", $translations, $memberSettings); ?>
                <input type="text"
                       id="term_<?= $i ?>_title"
                       name="member_term_<?= $i ?>_title"
                       value="<?= htmlspecialchars($termTitleValue) ?>"
                       placeholder="<?= __('admin.members.settings.terms.term_title_placeholder') ?>"
                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- 약관 내용 -->
            <div class="mb-4">
                <div class="flex items-center gap-2 mb-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        <?= __('admin.members.settings.terms.term_content') ?>
                    </label>
                    <button type="button" onclick="openMultilangModal('term.<?= $i ?>.content', 'term_<?= $i ?>_content', 'editor')"
                            class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 rounded hover:bg-blue-100 dark:hover:bg-blue-900/50 transition">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                        <?= __('admin.settings.multilang.button_title') ?>
                    </button>
                </div>
                <?php $termContentValue = getTranslatedValue("term.{$i}.content", "member_term_{$i}_content", $translations, $memberSettings); ?>
                <textarea id="term_<?= $i ?>_content"
                          name="member_term_<?= $i ?>_content"
                          class="summernote-editor"><?= $termContentValue ?></textarea>
            </div>

            <!-- 동의 필수 여부 -->
            <div class="flex items-center space-x-6">
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    <?= __('admin.members.settings.terms.consent_required') ?>
                </span>
                <?php $currentConsent = $memberSettings["member_term_{$i}_consent"] ?? 'disabled'; ?>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="radio" name="member_term_<?= $i ?>_consent" value="required"
                           <?= $currentConsent === 'required' ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.members.settings.terms.consent_required_option') ?></span>
                </label>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="radio" name="member_term_<?= $i ?>_consent" value="optional"
                           <?= $currentConsent === 'optional' ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.members.settings.terms.consent_optional_option') ?></span>
                </label>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="radio" name="member_term_<?= $i ?>_consent" value="disabled"
                           <?= $currentConsent === 'disabled' ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.members.settings.terms.consent_disabled_option') ?></span>
                </label>
            </div>
        </div>
        <?php endfor; ?>

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
