<?php
/**
 * 다국어 입력 모달 (market 버전 — 폼 내부 JSON hidden 필드 기반).
 *
 * voscms 의 multilang-modal.php 는 `/api/translations` DB 엔드포인트를 호출해
 * `rzx_translations` 테이블에 저장하지만, market 은 아이템 row 의 JSON 컬럼
 * (`mp_items.name` 등) 에 직접 쓰므로 모달의 원본·저장 대상을
 * **같은 폼 안의 hidden JSON 입력**(rzx_multilang_input 이 렌더) 으로 대체한다.
 *
 * JS API:
 *   openMultilangModal(jsonHiddenId, displayInputId, type='text'|'editor')
 *   closeMultilangModal()
 *
 * 필요 변수:
 *   $supportedLocales — ['en'=>'English','ko'=>'한국어',...] (키=ISO, 값=네이티브 이름)
 */

require_once __DIR__ . '/multilang-button.php';

$_mlSupported = $supportedLocales ?? [
    'en' => 'English', 'ko' => '한국어', 'ja' => '日本語',
    'zh_CN' => '简体中文', 'zh_TW' => '繁體中文',
    'de' => 'Deutsch', 'es' => 'Español', 'fr' => 'Français',
    'id' => 'Bahasa Indonesia', 'mn' => 'Монгол', 'ru' => 'Русский',
    'tr' => 'Türkçe', 'vi' => 'Tiếng Việt',
];
$_mlCodes = array_keys($_mlSupported);
$_mlCurrent = function_exists('current_locale') ? current_locale() : 'ko';
$_mlDefault = 'en';
$_mlCodesJson = json_encode($_mlCodes);
$_mlNamesJson = json_encode($_mlSupported, JSON_UNESCAPED_UNICODE);
?>

<!-- 다국어 입력 모달 -->
<div id="multilangModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
        <div class="fixed inset-0 transition-opacity bg-zinc-900/75" onclick="closeMultilangModal()"></div>

        <div id="multilangModalContent"
             class="relative z-50 w-full max-w-2xl p-6 bg-white dark:bg-zinc-800 rounded-xl shadow-xl transform transition-all"
             style="min-width:400px; min-height:250px;">

            <div id="multilangDragHandle" class="flex items-center justify-between mb-4 cursor-move select-none">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('admin.common.multilang') ?: '다국어 편집') ?></h3>
                <button type="button" onclick="closeMultilangModal()" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                <?= htmlspecialchars('모든 언어의 값을 입력·편집합니다.') ?>
            </p>

            <!-- 탭 네비게이션 -->
            <div id="multilangTabNav" class="flex flex-wrap border-b border-zinc-200 dark:border-zinc-700 mb-4 gap-0 overflow-x-auto">
<?php foreach ($_mlCodes as $i => $_mlCode): ?>
                <button type="button" onclick="switchMultilangTab('<?= $_mlCode ?>')" id="multilang-tab-<?= $_mlCode ?>"
                        class="px-3 py-2 text-xs font-medium whitespace-nowrap border-b-2 <?= $i === 0 ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200' ?>">
                    <span class="font-mono uppercase mr-1"><?= $_mlCode ?></span>
                    <?= htmlspecialchars($_mlSupported[$_mlCode]) ?>
                </button>
<?php endforeach; ?>
            </div>

            <!-- Text 모드 -->
            <div id="multilang-text-mode">
<?php foreach ($_mlCodes as $i => $_mlCode): ?>
                <div id="multilang-text-tabContent-<?= $_mlCode ?>" class="multilang-tab-content <?= $i > 0 ? 'hidden' : '' ?>">
                    <input type="text" id="multilang-text-input-<?= $_mlCode ?>"
                           class="w-full px-4 py-3 text-base border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="<?= htmlspecialchars($_mlSupported[$_mlCode]) ?>">
                </div>
<?php endforeach; ?>
            </div>

            <!-- Editor 모드 -->
            <div id="multilang-editor-mode" class="hidden">
<?php foreach ($_mlCodes as $i => $_mlCode): ?>
                <div id="multilang-editor-tabContent-<?= $_mlCode ?>" class="multilang-tab-content <?= $i > 0 ? 'hidden' : '' ?>">
                    <textarea id="multilang-editor-input-<?= $_mlCode ?>" class="multilang-summernote"></textarea>
                </div>
<?php endforeach; ?>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeMultilangModal()"
                        class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition">
                    <?= htmlspecialchars(__('admin.common.cancel') ?: '취소') ?>
                </button>
                <button type="button" onclick="saveMultilangData()"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                    <?= htmlspecialchars(__('admin.common.save') ?: '적용') ?>
                </button>
            </div>

            <div id="multilangResizeHandle"
                 class="absolute bottom-0 right-0 w-5 h-5 cursor-se-resize flex items-end justify-end pr-1 pb-1 text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-300">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 6 6"><path d="M6 6H4.5v-1.5H6V6zm0-3H4.5V1.5H6V3zM3 6H1.5V4.5H3V6z"/></svg>
            </div>
        </div>
    </div>
</div>

<!-- jQuery + Summernote (한 번만 로드) -->
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
    #multilang-editor-mode .note-editor .note-editable { min-height: 200px; }
    .dark #multilang-editor-mode .note-editor { border-color: #52525b; }
    .dark #multilang-editor-mode .note-editor .note-toolbar { background: #3f3f46; border-color: #52525b; }
    .dark #multilang-editor-mode .note-editor .note-editable { color: #fff; background: #3f3f46; }
</style>

<script>
(function () {
    const MULTILANG_LOCALES   = <?= $_mlCodesJson ?>;
    const MULTILANG_LANG_NAMES = <?= $_mlNamesJson ?>;
    const MULTILANG_DEFAULT   = '<?= $_mlDefault ?>';
    const MULTILANG_CURRENT   = '<?= $_mlCurrent ?>';

    let currentJsonId    = '';   // hidden JSON 필드의 id
    let currentDisplayId = '';   // 화면 표시 input/textarea id
    let currentType      = 'text';
    let editorsReady     = false;

    // 표시 input 변경 시 hidden JSON 의 현재 로케일 값을 자동 동기화
    document.addEventListener('input', function (e) {
        const el = e.target;
        if (!el || !el.dataset || !el.dataset.mpMlSync) return;
        const hid = document.getElementById(el.dataset.mpMlSync);
        if (!hid) return;
        let map = {};
        try { map = el.dataset.mpMlSync && hid.value ? JSON.parse(hid.value) : {}; } catch (_) { map = {}; }
        if (!map || typeof map !== 'object') map = {};
        const lc = el.dataset.mpMlLocale || MULTILANG_CURRENT || 'en';
        const v = el.value.trim();
        if (v) map[lc] = v; else delete map[lc];
        hid.value = JSON.stringify(map);
    });

    function initEditors() {
        if (typeof $ === 'undefined' || typeof $.fn.summernote === 'undefined') {
            setTimeout(initEditors, 80); return;
        }
        MULTILANG_LOCALES.forEach(function (lc) {
            const $ta = $('#multilang-editor-input-' + lc);
            if ($ta.length && !$ta.hasClass('summernote-initialized')) {
                $ta.summernote({
                    height: 250,
                    placeholder: MULTILANG_LANG_NAMES[lc] || lc,
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link', 'table', 'hr']],
                        ['view', ['codeview', 'fullscreen']]
                    ]
                });
                $ta.addClass('summernote-initialized');
            }
        });
        editorsReady = true;
    }

    function getEditorValue(lc) {
        const $ta = (typeof $ !== 'undefined') ? $('#multilang-editor-input-' + lc) : null;
        if ($ta && $ta.hasClass && $ta.hasClass('summernote-initialized')) return $ta.summernote('code');
        const el = document.getElementById('multilang-editor-input-' + lc);
        return el ? el.value : '';
    }

    function setEditorValue(lc, v) {
        const $ta = (typeof $ !== 'undefined') ? $('#multilang-editor-input-' + lc) : null;
        if ($ta && $ta.hasClass && $ta.hasClass('summernote-initialized')) { $ta.summernote('code', v || ''); return; }
        const el = document.getElementById('multilang-editor-input-' + lc);
        if (el) el.value = v || '';
    }

    // ── 드래그 ──
    (function initDrag() {
        const h = document.getElementById('multilangDragHandle');
        const m = document.getElementById('multilangModalContent');
        if (!h || !m) return;
        let dragging = false, sx, sy, ol, ot;
        h.addEventListener('mousedown', function (e) {
            if (e.target.closest('button')) return;
            dragging = true;
            const r = m.getBoundingClientRect();
            if (!m.style.position) {
                m.style.position = 'fixed';
                m.style.left = r.left + 'px';
                m.style.top = r.top + 'px';
                m.style.margin = '0';
                m.style.transform = 'none';
            }
            sx = e.clientX; sy = e.clientY;
            ol = parseInt(m.style.left, 10); ot = parseInt(m.style.top, 10);
            e.preventDefault();
        });
        document.addEventListener('mousemove', function (e) {
            if (!dragging) return;
            m.style.left = (ol + e.clientX - sx) + 'px';
            m.style.top = (ot + e.clientY - sy) + 'px';
        });
        document.addEventListener('mouseup', function () { dragging = false; });
    })();

    // ── 리사이즈 ──
    (function initResize() {
        const h = document.getElementById('multilangResizeHandle');
        const m = document.getElementById('multilangModalContent');
        if (!h || !m) return;
        let r = false, sx, sy, ow, oh;
        h.addEventListener('mousedown', function (e) {
            r = true;
            const rect = m.getBoundingClientRect();
            if (!m.style.position) {
                m.style.position = 'fixed';
                m.style.left = rect.left + 'px'; m.style.top = rect.top + 'px';
                m.style.margin = '0'; m.style.transform = 'none';
            }
            sx = e.clientX; sy = e.clientY; ow = rect.width; oh = rect.height;
            e.preventDefault(); e.stopPropagation();
        });
        document.addEventListener('mousemove', function (e) {
            if (!r) return;
            const nw = Math.max(400, ow + e.clientX - sx);
            const nh = Math.max(250, oh + e.clientY - sy);
            m.style.width = nw + 'px'; m.style.height = nh + 'px'; m.style.maxWidth = 'none';
        });
        document.addEventListener('mouseup', function () { r = false; });
    })();

    function resetModalPosition() {
        const m = document.getElementById('multilangModalContent');
        if (!m) return;
        ['position', 'left', 'top', 'width', 'height', 'maxWidth', 'margin', 'transform']
            .forEach(function (k) { m.style[k] = ''; });
    }

    // ── 모달 열기 ──
    window.openMultilangModal = function (jsonHiddenId, displayInputId, type) {
        type = type || 'text';
        currentJsonId = jsonHiddenId;
        currentDisplayId = displayInputId;
        currentType = type;

        resetModalPosition();

        const modal = document.getElementById('multilangModalContent');
        const textMode = document.getElementById('multilang-text-mode');
        const editorMode = document.getElementById('multilang-editor-mode');

        if (type === 'editor') {
            textMode.classList.add('hidden');
            editorMode.classList.remove('hidden');
            modal.classList.remove('max-w-2xl'); modal.classList.add('max-w-4xl');
        } else {
            textMode.classList.remove('hidden');
            editorMode.classList.add('hidden');
            modal.classList.remove('max-w-4xl'); modal.classList.add('max-w-2xl');
        }

        // hidden JSON 읽기
        const hid = document.getElementById(jsonHiddenId);
        let map = {};
        if (hid && hid.value) { try { map = JSON.parse(hid.value) || {}; } catch (_) { map = {}; } }

        // 각 로케일 입력 초기화
        MULTILANG_LOCALES.forEach(function (lc) {
            const v = map[lc] || '';
            if (type === 'editor') {
                setEditorValue(lc, v);
            } else {
                const el = document.getElementById('multilang-text-input-' + lc);
                if (el) el.value = v;
            }
        });

        document.getElementById('multilangModal').classList.remove('hidden');
        switchMultilangTab(MULTILANG_DEFAULT);

        if (type === 'editor' && !editorsReady) setTimeout(initEditors, 50);
    };

    window.closeMultilangModal = function () {
        document.getElementById('multilangModal').classList.add('hidden');
        currentJsonId = ''; currentDisplayId = ''; currentType = 'text';
    };

    window.switchMultilangTab = function (lc) {
        const type = currentType;
        MULTILANG_LOCALES.forEach(function (t) {
            const btn = document.getElementById('multilang-tab-' + t);
            const tc  = document.getElementById('multilang-text-tabContent-' + t);
            const ec  = document.getElementById('multilang-editor-tabContent-' + t);
            if (!btn) return;
            if (t === lc) {
                btn.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                btn.classList.remove('border-transparent', 'text-zinc-500', 'dark:text-zinc-400');
                if (type === 'text' && tc) tc.classList.remove('hidden');
                if (type === 'editor' && ec) ec.classList.remove('hidden');
            } else {
                btn.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                btn.classList.add('border-transparent', 'text-zinc-500', 'dark:text-zinc-400');
                if (type === 'text' && tc) tc.classList.add('hidden');
                if (type === 'editor' && ec) ec.classList.add('hidden');
            }
        });
    };

    window.saveMultilangData = function () {
        if (!currentJsonId) return;
        const type = currentType;
        const map = {};
        MULTILANG_LOCALES.forEach(function (lc) {
            let v;
            if (type === 'editor') v = getEditorValue(lc);
            else {
                const el = document.getElementById('multilang-text-input-' + lc);
                v = el ? el.value : '';
            }
            v = (v || '').trim();
            if (v) map[lc] = v;
        });

        const hid = document.getElementById(currentJsonId);
        if (hid) hid.value = JSON.stringify(map);

        // 표시 요소에 현재 로케일 값 반영 (없으면 default).
        // 지원 타입:
        //   1) summernote 가 초기화된 요소 ($(el).data('summernote') 존재)
        //   2) text input / textarea (value 쓰기)
        const disp = document.getElementById(currentDisplayId);
        if (disp) {
            const v = map[MULTILANG_CURRENT] || map[MULTILANG_DEFAULT] || '';
            let handled = false;
            if (typeof $ !== 'undefined') {
                try {
                    const $d = $(disp);
                    if ($d.data('summernote') || $d.hasClass('summernote-initialized')) {
                        $d.summernote('code', v);
                        handled = true;
                    }
                } catch (_) { /* fallthrough */ }
            }
            if (!handled) {
                if ('value' in disp) disp.value = v;
                else disp.innerHTML = v;
            }
        }
        closeMultilangModal();
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !document.getElementById('multilangModal').classList.contains('hidden')) {
            closeMultilangModal();
        }
    });
})();
</script>
<?= rzx_multilang_btn_js() ?>
