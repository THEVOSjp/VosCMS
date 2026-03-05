<?php
/**
 * 다국어 입력 모달 컴포넌트
 *
 * 사용법:
 * <?php include __DIR__ . '/components/multilang-modal.php'; ?>
 *
 * JavaScript API:
 * - openMultilangModal(langKey, inputId, type) : 모달 열기
 *   - langKey: 번역 키 (예: 'site.name', 'term.1.content')
 *   - inputId: 연결된 input/textarea ID
 *   - type: 'text' (단일 라인) 또는 'editor' (멀티라인)
 * - closeMultilangModal() : 모달 닫기
 *
 * 예시:
 * openMultilangModal('site.name', 'site_name', 'text')
 * openMultilangModal('term.1.content', 'term_1_content', 'editor')
 */

// API URL 설정 (이미 정의되지 않은 경우)
$multilangApiUrl = $adminUrl ?? '';
?>

<!-- 다국어 입력 모달 -->
<div id="multilangModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
        <!-- 배경 오버레이 -->
        <div class="fixed inset-0 transition-opacity bg-zinc-900/75" onclick="closeMultilangModal()"></div>

        <!-- 모달 컨텐츠 -->
        <div id="multilangModalContent" class="relative z-50 w-full max-w-lg p-6 bg-white dark:bg-zinc-800 rounded-xl shadow-xl transform transition-all">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('admin.settings.multilang.modal_title') ?></h3>
                <button type="button" onclick="closeMultilangModal()" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4"><?= __('admin.settings.multilang.modal_description') ?></p>

            <!-- 탭 네비게이션 -->
            <div class="flex border-b border-zinc-200 dark:border-zinc-700 mb-4">
                <button type="button" onclick="switchMultilangTab('ko')" id="multilang-tab-ko"
                        class="px-4 py-2 text-sm font-medium border-b-2 border-blue-500 text-blue-600 dark:text-blue-400">
                    <?= __('admin.settings.multilang.tab_ko') ?>
                </button>
                <button type="button" onclick="switchMultilangTab('en')" id="multilang-tab-en"
                        class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">
                    <?= __('admin.settings.multilang.tab_en') ?>
                </button>
                <button type="button" onclick="switchMultilangTab('ja')" id="multilang-tab-ja"
                        class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">
                    <?= __('admin.settings.multilang.tab_ja') ?>
                </button>
            </div>

            <!-- Text 모드 (단일 라인) -->
            <div id="multilang-text-mode">
                <div id="multilang-text-tabContent-ko" class="multilang-tab-content">
                    <input type="text" id="multilang-text-input-ko"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="<?= __('admin.settings.multilang.placeholder') ?>">
                </div>
                <div id="multilang-text-tabContent-en" class="multilang-tab-content hidden">
                    <input type="text" id="multilang-text-input-en"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="<?= __('admin.settings.multilang.placeholder') ?>">
                </div>
                <div id="multilang-text-tabContent-ja" class="multilang-tab-content hidden">
                    <input type="text" id="multilang-text-input-ja"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="<?= __('admin.settings.multilang.placeholder') ?>">
                </div>
            </div>

            <!-- Editor 모드 (Summernote WYSIWYG) -->
            <div id="multilang-editor-mode" class="hidden">
                <div id="multilang-editor-tabContent-ko" class="multilang-tab-content">
                    <textarea id="multilang-editor-input-ko" class="multilang-summernote"></textarea>
                </div>
                <div id="multilang-editor-tabContent-en" class="multilang-tab-content hidden">
                    <textarea id="multilang-editor-input-en" class="multilang-summernote"></textarea>
                </div>
                <div id="multilang-editor-tabContent-ja" class="multilang-tab-content hidden">
                    <textarea id="multilang-editor-input-ja" class="multilang-summernote"></textarea>
                </div>
            </div>

            <!-- 버튼 -->
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeMultilangModal()"
                        class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition">
                    <?= __('admin.settings.multilang.cancel') ?>
                </button>
                <button type="button" onclick="saveMultilangData()"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                    <?= __('admin.settings.multilang.save') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 알림 토스트 -->
<div id="multilang-toast" class="fixed bottom-4 right-4 z-50 hidden">
    <div class="px-4 py-3 rounded-lg shadow-lg text-white text-sm flex items-center"></div>
</div>

<!-- Summernote for Multilang Modal -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<style>
    /* Multilang Modal Summernote Styles */
    #multilang-editor-mode .note-editor { border-radius: 0.5rem; overflow: hidden; }
    #multilang-editor-mode .note-editor .note-toolbar { background: #f4f4f5; border-color: #d4d4d8; }
    #multilang-editor-mode .note-editor .note-editing-area { background: #fff; }
    #multilang-editor-mode .note-editor .note-editable { min-height: 200px; }
    #multilang-editor-mode .note-editor .note-statusbar { background: #f4f4f5; border-color: #d4d4d8; }
    /* Dark Mode */
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
    // ===========================
    // 다국어 모달 컴포넌트
    // ===========================
    const MULTILANG_API_URL = '<?php echo $multilangApiUrl; ?>/api/translations';
    const MULTILANG_LOCALES = ['ko', 'en', 'ja'];
    const MULTILANG_CURRENT_LOCALE = '<?php echo $config['locale'] ?? 'ko'; ?>';

    let multilangCurrentKey = '';
    let multilangCurrentInputId = '';
    let multilangCurrentType = 'text'; // 'text' or 'editor'
    let multilangEditorsInitialized = false;

    // Summernote 에디터 초기화
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
                    lang: 'ko-KR',
                    height: 250,
                    placeholder: '내용을 입력하세요...',
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
        console.log('[Multilang] All Summernote editors initialized');
    }

    // Summernote 값 가져오기
    function getEditorValue(locale) {
        if (typeof $ !== 'undefined' && typeof $.fn.summernote !== 'undefined') {
            const $textarea = $(`#multilang-editor-input-${locale}`);
            if ($textarea.hasClass('summernote-initialized')) {
                return $textarea.summernote('code');
            }
        }
        return document.getElementById(`multilang-editor-input-${locale}`)?.value || '';
    }

    // Summernote 값 설정하기
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

    // 모달 열기
    window.openMultilangModal = async function(langKey, inputId, type = 'text') {
        multilangCurrentKey = langKey;
        multilangCurrentInputId = inputId;
        multilangCurrentType = type;

        const modalContent = document.getElementById('multilangModalContent');
        const textMode = document.getElementById('multilang-text-mode');
        const editorMode = document.getElementById('multilang-editor-mode');

        // 모드에 따라 UI 전환
        if (type === 'editor') {
            textMode.classList.add('hidden');
            editorMode.classList.remove('hidden');
            modalContent.classList.remove('max-w-lg');
            modalContent.classList.add('max-w-3xl');

            // Summernote 초기화 (필요 시)
            if (!multilangEditorsInitialized) {
                initMultilangEditors();
            }
        } else {
            textMode.classList.remove('hidden');
            editorMode.classList.add('hidden');
            modalContent.classList.remove('max-w-3xl');
            modalContent.classList.add('max-w-lg');
        }

        // 입력 필드 초기화
        let currentValue = '';
        const sourceEl = document.getElementById(inputId);
        if (sourceEl) {
            // Summernote 에디터인 경우
            if (typeof $ !== 'undefined' && $(sourceEl).hasClass('summernote-initialized')) {
                currentValue = $(sourceEl).summernote('code');
            } else {
                currentValue = sourceEl.value || '';
            }
        }

        MULTILANG_LOCALES.forEach(locale => {
            if (type === 'editor') {
                setEditorValue(locale, locale === 'ko' ? currentValue : '');
            } else {
                const inputEl = document.getElementById(`multilang-text-input-${locale}`);
                if (inputEl) {
                    inputEl.value = locale === 'ko' ? currentValue : '';
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

        // 모달 표시
        document.getElementById('multilangModal').classList.remove('hidden');
        switchMultilangTab('ko');
        console.log('[Multilang] Modal opened for:', langKey, 'type:', type);
    };

    // 모달 닫기
    window.closeMultilangModal = function() {
        document.getElementById('multilangModal').classList.add('hidden');
        multilangCurrentKey = '';
        multilangCurrentInputId = '';
        multilangCurrentType = 'text';
        console.log('[Multilang] Modal closed');
    };

    // 탭 전환
    window.switchMultilangTab = function(locale) {
        const type = multilangCurrentType;

        MULTILANG_LOCALES.forEach(tab => {
            const tabBtn = document.getElementById(`multilang-tab-${tab}`);
            const textContent = document.getElementById(`multilang-text-tabContent-${tab}`);
            const editorContent = document.getElementById(`multilang-editor-tabContent-${tab}`);

            if (tab === locale) {
                tabBtn.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabBtn.classList.remove('border-transparent', 'text-zinc-500', 'dark:text-zinc-400');
                if (type === 'text') {
                    textContent.classList.remove('hidden');
                } else {
                    editorContent.classList.remove('hidden');
                }
            } else {
                tabBtn.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabBtn.classList.add('border-transparent', 'text-zinc-500', 'dark:text-zinc-400');
                if (type === 'text') {
                    textContent.classList.add('hidden');
                } else {
                    editorContent.classList.add('hidden');
                }
            }
        });
    };

    // 다국어 데이터 저장
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
                // 기본 입력 필드 업데이트 (현재 로케일 값으로)
                const newValue = translations[MULTILANG_CURRENT_LOCALE] || translations.ko || '';
                const targetEl = document.getElementById(multilangCurrentInputId);
                if (targetEl) {
                    // Summernote 에디터인 경우
                    if (typeof $ !== 'undefined' && $(targetEl).hasClass('summernote-initialized')) {
                        $(targetEl).summernote('code', newValue);
                    } else {
                        targetEl.value = newValue;
                    }
                }

                showMultilangToast('<?= __('admin.settings.multilang.saved') ?>', 'success');
                closeMultilangModal();
            } else {
                showMultilangToast('<?= __('admin.settings.multilang.error') ?>', 'error');
            }
        } catch (error) {
            console.error('[Multilang] Error saving translations:', error);
            showMultilangToast('<?= __('admin.settings.multilang.error') ?>', 'error');
        }
    };

    // 토스트 알림 표시
    window.showMultilangToast = function(message, type = 'success') {
        const toast = document.getElementById('multilang-toast');
        const toastContent = toast.querySelector('div');

        toastContent.textContent = message;
        toastContent.className = `px-4 py-3 rounded-lg shadow-lg text-white text-sm flex items-center ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;

        toast.classList.remove('hidden');

        setTimeout(() => {
            toast.classList.add('hidden');
        }, 3000);
    };

    // ESC 키로 모달 닫기
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !document.getElementById('multilangModal').classList.contains('hidden')) {
            closeMultilangModal();
        }
    });

    console.log('[Multilang] Component initialized (text/editor mode with Summernote supported)');
})();
</script>
