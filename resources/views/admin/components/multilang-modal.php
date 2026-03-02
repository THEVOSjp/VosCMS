<?php
/**
 * 다국어 입력 모달 컴포넌트
 *
 * 사용법:
 * <?php include __DIR__ . '/components/multilang-modal.php'; ?>
 *
 * JavaScript API:
 * - openMultilangModal(langKey, inputId) : 모달 열기
 * - closeMultilangModal() : 모달 닫기
 *
 * langKey 예시: 'site.name', 'site.tagline', 'service.description.1' 등
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
        <div class="relative z-50 w-full max-w-lg p-6 bg-white dark:bg-zinc-800 rounded-xl shadow-xl transform transition-all">
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

            <!-- 탭 컨텐츠 -->
            <div id="multilang-tabContent-ko" class="multilang-tab-content">
                <textarea id="multilang-input-ko" rows="3"
                          class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          placeholder="<?= __('admin.settings.multilang.placeholder') ?>"></textarea>
            </div>
            <div id="multilang-tabContent-en" class="multilang-tab-content hidden">
                <textarea id="multilang-input-en" rows="3"
                          class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          placeholder="<?= __('admin.settings.multilang.placeholder') ?>"></textarea>
            </div>
            <div id="multilang-tabContent-ja" class="multilang-tab-content hidden">
                <textarea id="multilang-input-ja" rows="3"
                          class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          placeholder="<?= __('admin.settings.multilang.placeholder') ?>"></textarea>
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

    // 모달 열기
    window.openMultilangModal = async function(langKey, inputId) {
        multilangCurrentKey = langKey;
        multilangCurrentInputId = inputId;

        // 입력 필드 초기화
        const currentValue = document.getElementById(inputId)?.value || '';
        document.getElementById('multilang-input-ko').value = currentValue;
        document.getElementById('multilang-input-en').value = '';
        document.getElementById('multilang-input-ja').value = '';

        // DB에서 기존 번역 데이터 로드
        try {
            const response = await fetch(`${MULTILANG_API_URL}?action=get&key=${encodeURIComponent(langKey)}`);
            const result = await response.json();

            if (result.success && result.data.translations) {
                const translations = result.data.translations;
                MULTILANG_LOCALES.forEach(locale => {
                    if (translations[locale]) {
                        document.getElementById(`multilang-input-${locale}`).value = translations[locale];
                    }
                });
            }
        } catch (error) {
            console.error('[Multilang] Error loading translations:', error);
        }

        // 모달 표시
        document.getElementById('multilangModal').classList.remove('hidden');
        switchMultilangTab('ko');
        console.log('[Multilang] Modal opened for:', langKey);
    };

    // 모달 닫기
    window.closeMultilangModal = function() {
        document.getElementById('multilangModal').classList.add('hidden');
        multilangCurrentKey = '';
        multilangCurrentInputId = '';
        console.log('[Multilang] Modal closed');
    };

    // 탭 전환
    window.switchMultilangTab = function(locale) {
        MULTILANG_LOCALES.forEach(tab => {
            const tabBtn = document.getElementById(`multilang-tab-${tab}`);
            const tabContent = document.getElementById(`multilang-tabContent-${tab}`);

            if (tab === locale) {
                tabBtn.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabBtn.classList.remove('border-transparent', 'text-zinc-500', 'dark:text-zinc-400');
                tabContent.classList.remove('hidden');
            } else {
                tabBtn.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                tabBtn.classList.add('border-transparent', 'text-zinc-500', 'dark:text-zinc-400');
                tabContent.classList.add('hidden');
            }
        });
    };

    // 다국어 데이터 저장
    window.saveMultilangData = async function() {
        const translations = {};
        MULTILANG_LOCALES.forEach(locale => {
            translations[locale] = document.getElementById(`multilang-input-${locale}`).value;
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
                const inputEl = document.getElementById(multilangCurrentInputId);
                if (inputEl) {
                    inputEl.value = newValue;
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

    console.log('[Multilang] Component initialized');
})();
</script>
