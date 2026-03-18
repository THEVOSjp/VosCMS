<?php
/**
 * 다국어 입력 모달 컴포넌트
 *
 * DB에서 활성화된 모든 언어를 동적으로 표시합니다.
 *
 * JavaScript API:
 * - openMultilangModal(langKey, inputId, type) : 모달 열기
 * - closeMultilangModal() : 모달 닫기
 *
 * PHP API (통합 버튼 — rzxlib/Core/Helpers/multilang.php에서 자동 로드):
 * - rzx_multilang_btn($onclick, $title) : 통합 다국어 버튼 HTML 반환
 * - rzx_multilang_input($name, $value, $langKey, $opts) : input + 지구본 조합
 *
 * JS API (동적 생성):
 * - RZX_MULTILANG_BTN(onclick, title) : 통합 다국어 버튼 HTML 문자열 반환
 */

// API URL 설정
$multilangApiUrl = $adminUrl ?? '';

// DB에서 활성화된 언어 목록 가져오기
$_mlLocales = [];
$_mlLangNames = [];

// LanguageModule 사용 가능 시
$_mlModulePath = dirname(__DIR__, 4) . '/rzxlib/Core/Modules/LanguageModule.php';
if (file_exists($_mlModulePath)) {
    require_once $_mlModulePath;
    $_mlSettings = $siteSettings ?? $config ?? [];
    $_mlData = \RzxLib\Core\Modules\LanguageModule::getData($_mlSettings, $config['locale'] ?? 'ko');
    $_mlLocales = $_mlData['supportedCodes'] ?? ['ko', 'en', 'ja'];
    $_mlAllLangs = $_mlData['allLanguages'] ?? [];
    foreach ($_mlLocales as $_mlCode) {
        $_mlLangNames[$_mlCode] = $_mlAllLangs[$_mlCode]['native'] ?? $_mlCode;
    }
} else {
    // 폴백: 기본 3개 언어
    $_mlLocales = ['ko', 'en', 'ja'];
    $_mlLangNames = ['ko' => '한국어', 'en' => 'English', 'ja' => '日本語'];
}

$_mlDefaultLocale = $_mlLocales[0] ?? 'ko';
$_mlCurrentLocale = $config['locale'] ?? 'ko';
$_mlLocalesJson = json_encode($_mlLocales);
$_mlLangNamesJson = json_encode($_mlLangNames, JSON_UNESCAPED_UNICODE);
?>

<!-- 다국어 입력 모달 -->
<div id="multilangModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
        <div class="fixed inset-0 transition-opacity bg-zinc-900/75" onclick="closeMultilangModal()"></div>

        <div id="multilangModalContent"
             class="relative z-50 w-full max-w-2xl p-6 bg-white dark:bg-zinc-800 rounded-xl shadow-xl transform transition-all"
             style="min-width:400px; min-height:250px;">

            <!-- 헤더 (드래그 핸들) -->
            <div id="multilangDragHandle" class="flex items-center justify-between mb-4 cursor-move select-none">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('settings.multilang.modal_title') ?></h3>
                <button type="button" onclick="closeMultilangModal()" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4"><?= __('settings.multilang.modal_description') ?></p>

            <!-- 탭 네비게이션 -->
            <div id="multilangTabNav" class="flex flex-wrap border-b border-zinc-200 dark:border-zinc-700 mb-4 gap-0 overflow-x-auto">
<?php foreach ($_mlLocales as $i => $_mlCode): ?>
                <button type="button" onclick="switchMultilangTab('<?= $_mlCode ?>')" id="multilang-tab-<?= $_mlCode ?>"
                        class="px-3 py-2 text-xs font-medium whitespace-nowrap border-b-2 <?= $i === 0 ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200' ?>">
                    <?= htmlspecialchars($_mlLangNames[$_mlCode]) ?>
                </button>
<?php endforeach; ?>
            </div>

            <!-- Text 모드 -->
            <div id="multilang-text-mode">
<?php foreach ($_mlLocales as $i => $_mlCode): ?>
                <div id="multilang-text-tabContent-<?= $_mlCode ?>" class="multilang-tab-content <?= $i > 0 ? 'hidden' : '' ?>">
                    <input type="text" id="multilang-text-input-<?= $_mlCode ?>"
                           class="w-full px-4 py-3 text-base border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="<?= htmlspecialchars($_mlLangNames[$_mlCode]) ?>">
                </div>
<?php endforeach; ?>
            </div>

            <!-- Editor 모드 -->
            <div id="multilang-editor-mode" class="hidden">
<?php foreach ($_mlLocales as $i => $_mlCode): ?>
                <div id="multilang-editor-tabContent-<?= $_mlCode ?>" class="multilang-tab-content <?= $i > 0 ? 'hidden' : '' ?>">
                    <textarea id="multilang-editor-input-<?= $_mlCode ?>" class="multilang-summernote"></textarea>
                </div>
<?php endforeach; ?>
            </div>

            <!-- 버튼 -->
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeMultilangModal()"
                        class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition">
                    <?= __('settings.multilang.cancel') ?>
                </button>
                <button type="button" onclick="saveMultilangData()"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                    <?= __('settings.multilang.save') ?>
                </button>
            </div>

            <!-- 리사이즈 핸들 (우측 하단) -->
            <div id="multilangResizeHandle"
                 class="absolute bottom-0 right-0 w-5 h-5 cursor-se-resize flex items-end justify-end pr-1 pb-1 text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 6 6"><path d="M6 6H4.5v-1.5H6V6zm0-3H4.5V1.5H6V3zM3 6H1.5V4.5H3V6z"/></svg>
            </div>
        </div>
    </div>
</div>

<!-- 알림 토스트 -->
<div id="multilang-toast" class="fixed bottom-4 right-4 z-50 hidden">
    <div class="px-4 py-3 rounded-lg shadow-lg text-white text-sm flex items-center"></div>
</div>

<!-- jQuery + Summernote for Multilang Modal -->
<?php if (empty($GLOBALS['_rzx_jquery_loaded'])): $GLOBALS['_rzx_jquery_loaded'] = true; ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<?php endif; ?>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<?php if (empty($GLOBALS['_rzx_summernote_loaded'])): $GLOBALS['_rzx_summernote_loaded'] = true; ?>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<?php endif; ?>
<style>
    #multilang-editor-mode .note-editor { border-radius: 0.5rem; overflow: hidden; }
    #multilang-editor-mode .note-editor .note-toolbar { background: #f4f4f5; border-color: #d4d4d8; }
    #multilang-editor-mode .note-editor .note-editing-area { background: #fff; }
    #multilang-editor-mode .note-editor .note-editable { min-height: 200px; }
    #multilang-editor-mode .note-editor .note-statusbar { background: #f4f4f5; border-color: #d4d4d8; }
    .dark #multilang-editor-mode .note-editor { border-color: #52525b; }
    .dark #multilang-editor-mode .note-editor .note-toolbar { background: #3f3f46; border-color: #52525b; }
    .dark #multilang-editor-mode .note-editor .note-toolbar .note-btn { color: #a1a1aa; background: transparent; border-color: #52525b; }
    .dark #multilang-editor-mode .note-editor .note-toolbar .note-btn:hover { color: #fff; background: #52525b; }
    .dark #multilang-editor-mode .note-editor .note-editing-area { background: #3f3f46; }
    .dark #multilang-editor-mode .note-editor .note-editable { color: #fff; background: #3f3f46; }
    .dark #multilang-editor-mode .note-editor .note-statusbar { background: #3f3f46; border-color: #52525b; }
    .dark #multilang-editor-mode .note-editor .note-codable { background: #27272a; color: #a1a1aa; }
    .dark #multilang-editor-mode .note-dropdown-menu { background: #3f3f46; border-color: #52525b; }
    .dark #multilang-editor-mode .note-dropdown-menu .note-dropdown-item { color: #a1a1aa; }
    .dark #multilang-editor-mode .note-dropdown-menu .note-dropdown-item:hover { background: #52525b; color: #fff; }
</style>

<script>
(function() {
    const MULTILANG_API_URL = '<?= $multilangApiUrl ?>/api/translations';
    const MULTILANG_LOCALES = <?= $_mlLocalesJson ?>;
    const MULTILANG_LANG_NAMES = <?= $_mlLangNamesJson ?>;
    const MULTILANG_DEFAULT_LOCALE = '<?= $_mlDefaultLocale ?>';
    const MULTILANG_CURRENT_LOCALE = '<?= $_mlCurrentLocale ?>';

    let multilangCurrentKey = '';
    let multilangCurrentInputId = '';
    let multilangCurrentType = 'text';
    let multilangEditorsInitialized = false;

    // ── 드래그 이동 ──
    (function initDrag() {
        const handle = document.getElementById('multilangDragHandle');
        const modal = document.getElementById('multilangModalContent');
        if (!handle || !modal) return;

        let isDragging = false, startX, startY, origLeft, origTop;

        handle.addEventListener('mousedown', (e) => {
            if (e.target.closest('button')) return; // 닫기 버튼 제외
            isDragging = true;
            const rect = modal.getBoundingClientRect();
            // 첫 드래그 시 position 고정
            if (!modal.style.position || modal.style.position === '') {
                modal.style.position = 'fixed';
                modal.style.left = rect.left + 'px';
                modal.style.top = rect.top + 'px';
                modal.style.margin = '0';
                modal.style.transform = 'none';
            }
            startX = e.clientX;
            startY = e.clientY;
            origLeft = parseInt(modal.style.left, 10);
            origTop = parseInt(modal.style.top, 10);
            e.preventDefault();
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            modal.style.left = (origLeft + e.clientX - startX) + 'px';
            modal.style.top = (origTop + e.clientY - startY) + 'px';
        });

        document.addEventListener('mouseup', () => { isDragging = false; });
    })();

    // ── 리사이즈 ──
    (function initResize() {
        const handle = document.getElementById('multilangResizeHandle');
        const modal = document.getElementById('multilangModalContent');
        if (!handle || !modal) return;

        let isResizing = false, startX, startY, origW, origH;

        handle.addEventListener('mousedown', (e) => {
            isResizing = true;
            const rect = modal.getBoundingClientRect();
            // 첫 리사이즈 시 position 고정
            if (!modal.style.position || modal.style.position === '') {
                modal.style.position = 'fixed';
                modal.style.left = rect.left + 'px';
                modal.style.top = rect.top + 'px';
                modal.style.margin = '0';
                modal.style.transform = 'none';
            }
            startX = e.clientX;
            startY = e.clientY;
            origW = rect.width;
            origH = rect.height;
            e.preventDefault();
            e.stopPropagation();
        });

        document.addEventListener('mousemove', (e) => {
            if (!isResizing) return;
            const newW = Math.max(400, origW + e.clientX - startX);
            const newH = Math.max(250, origH + e.clientY - startY);
            modal.style.width = newW + 'px';
            modal.style.height = newH + 'px';
            modal.style.maxWidth = 'none';
        });

        document.addEventListener('mouseup', () => { isResizing = false; });
    })();

    // ── 모달 위치/크기 리셋 ──
    function resetModalPosition() {
        const modal = document.getElementById('multilangModalContent');
        if (!modal) return;
        modal.style.position = '';
        modal.style.left = '';
        modal.style.top = '';
        modal.style.width = '';
        modal.style.height = '';
        modal.style.maxWidth = '';
        modal.style.margin = '';
        modal.style.transform = '';
    }

    // ── Summernote 에디터 초기화 ──
    function initMultilangEditors() {
        if (typeof $ === 'undefined' || typeof $.fn.summernote === 'undefined') {
            console.log('[Multilang] Waiting for Summernote...');
            setTimeout(initMultilangEditors, 100);
            return;
        }

        MULTILANG_LOCALES.forEach(locale => {
            const $textarea = $(`#multilang-editor-input-${locale}`);
            if ($textarea.length && !$textarea.hasClass('summernote-initialized')) {
                $textarea.summernote({
                    height: 250,
                    placeholder: MULTILANG_LANG_NAMES[locale] || locale,
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link', 'table', 'hr']],
                        ['view', ['codeview', 'fullscreen']]
                    ],
                    callbacks: {
                        onInit: function() {
                            console.log('[Multilang] Summernote initialized:', locale);
                        }
                    }
                });
                $textarea.addClass('summernote-initialized');
            }
        });

        multilangEditorsInitialized = true;
        console.log('[Multilang] All Summernote editors initialized (' + MULTILANG_LOCALES.length + ' locales)');
    }

    function getEditorValue(locale) {
        if (typeof $ !== 'undefined' && typeof $.fn.summernote !== 'undefined') {
            const $textarea = $(`#multilang-editor-input-${locale}`);
            if ($textarea.hasClass('summernote-initialized')) {
                return $textarea.summernote('code');
            }
        }
        return document.getElementById(`multilang-editor-input-${locale}`)?.value || '';
    }

    function setEditorValue(locale, value) {
        if (typeof $ !== 'undefined' && typeof $.fn.summernote !== 'undefined') {
            const $textarea = $(`#multilang-editor-input-${locale}`);
            if ($textarea.hasClass('summernote-initialized')) {
                $textarea.summernote('code', value || '');
                return;
            }
        }
        const el = document.getElementById(`multilang-editor-input-${locale}`);
        if (el) el.value = value || '';
    }

    // ── 모달 열기 ──
    window.openMultilangModal = async function(langKey, inputId, type = 'text') {
        multilangCurrentKey = langKey;
        multilangCurrentInputId = inputId;
        multilangCurrentType = type;

        // 위치/크기 리셋 (매번 중앙 정렬)
        resetModalPosition();

        const modalContent = document.getElementById('multilangModalContent');
        const textMode = document.getElementById('multilang-text-mode');
        const editorMode = document.getElementById('multilang-editor-mode');

        if (type === 'editor') {
            textMode.classList.add('hidden');
            editorMode.classList.remove('hidden');
            modalContent.classList.remove('max-w-2xl');
            modalContent.classList.add('max-w-4xl');
        } else {
            textMode.classList.remove('hidden');
            editorMode.classList.add('hidden');
            modalContent.classList.remove('max-w-4xl');
            modalContent.classList.add('max-w-2xl');
        }

        // 현재 입력 필드 값 가져오기
        let currentValue = '';
        const sourceEl = document.getElementById(inputId);
        if (sourceEl) {
            if (typeof $ !== 'undefined' && $(sourceEl).hasClass('summernote-initialized')) {
                currentValue = $(sourceEl).summernote('code');
            } else {
                currentValue = sourceEl.value || '';
            }
        }

        // 입력 필드 초기화 (기본 로케일에만 현재 값)
        MULTILANG_LOCALES.forEach(locale => {
            if (type === 'editor') {
                setEditorValue(locale, locale === MULTILANG_DEFAULT_LOCALE ? currentValue : '');
            } else {
                const inputEl = document.getElementById(`multilang-text-input-${locale}`);
                if (inputEl) {
                    inputEl.value = locale === MULTILANG_DEFAULT_LOCALE ? currentValue : '';
                }
            }
        });

        // DB에서 기존 번역 데이터 로드
        try {
            const response = await fetch(`${MULTILANG_API_URL}?action=get&key=${encodeURIComponent(langKey)}`);
            const result = await response.json();

            if (result.success && result.data.translations) {
                const translations = result.data.translations;
                MULTILANG_LOCALES.forEach(locale => {
                    if (translations[locale]) {
                        if (type === 'editor') {
                            setEditorValue(locale, translations[locale]);
                        } else {
                            const inputEl = document.getElementById(`multilang-text-input-${locale}`);
                            if (inputEl) {
                                inputEl.value = translations[locale];
                            }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('[Multilang] Error loading translations:', error);
        }

        document.getElementById('multilangModal').classList.remove('hidden');
        switchMultilangTab(MULTILANG_DEFAULT_LOCALE);

        // Summernote는 모달이 보인 후 초기화해야 정상 렌더링됨
        if (type === 'editor' && !multilangEditorsInitialized) {
            setTimeout(() => initMultilangEditors(), 50);
        }

        console.log('[Multilang] Modal opened for:', langKey, 'type:', type, 'locales:', MULTILANG_LOCALES.length);
    };

    // ── 모달 닫기 ──
    window.closeMultilangModal = function() {
        document.getElementById('multilangModal').classList.add('hidden');
        multilangCurrentKey = '';
        multilangCurrentInputId = '';
        multilangCurrentType = 'text';
        console.log('[Multilang] Modal closed');
    };

    // ── 탭 전환 ──
    window.switchMultilangTab = function(locale) {
        const type = multilangCurrentType;

        MULTILANG_LOCALES.forEach(tab => {
            const tabBtn = document.getElementById(`multilang-tab-${tab}`);
            const textContent = document.getElementById(`multilang-text-tabContent-${tab}`);
            const editorContent = document.getElementById(`multilang-editor-tabContent-${tab}`);

            if (!tabBtn) return;

            if (tab === locale) {
                tabBtn.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabBtn.classList.remove('border-transparent', 'text-zinc-500', 'dark:text-zinc-400');
                if (type === 'text' && textContent) textContent.classList.remove('hidden');
                if (type === 'editor' && editorContent) editorContent.classList.remove('hidden');
            } else {
                tabBtn.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabBtn.classList.add('border-transparent', 'text-zinc-500', 'dark:text-zinc-400');
                if (type === 'text' && textContent) textContent.classList.add('hidden');
                if (type === 'editor' && editorContent) editorContent.classList.add('hidden');
            }
        });
    };

    // ── 다국어 데이터 저장 ──
    window.saveMultilangData = async function() {
        const type = multilangCurrentType;
        const translations = {};

        MULTILANG_LOCALES.forEach(locale => {
            if (type === 'editor') {
                translations[locale] = getEditorValue(locale);
            } else {
                const inputEl = document.getElementById(`multilang-text-input-${locale}`);
                if (inputEl) {
                    translations[locale] = inputEl.value;
                }
            }
        });

        try {
            const response = await fetch(`${MULTILANG_API_URL}?action=save`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    key: multilangCurrentKey,
                    translations: translations
                })
            });

            const result = await response.json();

            if (result.success) {
                const newValue = translations[MULTILANG_CURRENT_LOCALE] || translations[MULTILANG_DEFAULT_LOCALE] || '';
                const targetEl = document.getElementById(multilangCurrentInputId);
                if (targetEl) {
                    if (typeof $ !== 'undefined' && $(targetEl).hasClass('summernote-initialized')) {
                        $(targetEl).summernote('code', newValue);
                    } else {
                        targetEl.value = newValue;
                    }
                }

                showMultilangToast('<?= __('settings.multilang.saved') ?>', 'success');
                closeMultilangModal();
            } else {
                showMultilangToast('<?= __('settings.multilang.error') ?>', 'error');
            }
        } catch (error) {
            console.error('[Multilang] Error saving translations:', error);
            showMultilangToast('<?= __('settings.multilang.error') ?>', 'error');
        }
    };

    // ── 토스트 알림 ──
    window.showMultilangToast = function(message, type = 'success') {
        const toast = document.getElementById('multilang-toast');
        const toastContent = toast.querySelector('div');
        toastContent.textContent = message;
        toastContent.className = `px-4 py-3 rounded-lg shadow-lg text-white text-sm flex items-center ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
        toast.classList.remove('hidden');
        setTimeout(() => { toast.classList.add('hidden'); }, 3000);
    };

    // ESC 키로 모달 닫기
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !document.getElementById('multilangModal').classList.contains('hidden')) {
            closeMultilangModal();
        }
    });

    console.log('[Multilang] Component initialized with', MULTILANG_LOCALES.length, 'languages:', MULTILANG_LOCALES.join(', '));
})();
</script>
<?= rzx_multilang_btn_js() ?>
