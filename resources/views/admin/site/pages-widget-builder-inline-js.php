<?php
/**
 * RezlyX Admin - 위젯 인라인 편집 JS
 * 연필 아이콘 클릭 → iframe 숨기고 위젯 HTML 직접 렌더링 → contenteditable 텍스트 편집
 * data-widget-field 속성을 가진 요소들이 편집 대상
 */
?>
<script>
var WBInline = (function() {
    'use strict';

    var inlineBlock = null;      // 현재 인라인 편집 중인 widget-block
    var inlineContainer = null;  // 인라인 HTML을 담는 컨테이너 div
    var overlayBar = null;       // Save/Cancel 오버레이 바
    var originalConfig = null;   // 편집 전 원본 config (취소용)

    WB.enterInlineMode = enterInlineMode;

    // ===== 인라인 모드 진입 =====
    function enterInlineMode(block) {
        if (inlineBlock) exitInlineMode(false); // 이미 다른 블록 편집 중이면 종료

        inlineBlock = block;
        var config = {};
        try { config = JSON.parse(block.dataset.config || '{}'); } catch(e) {}
        originalConfig = JSON.stringify(config); // 원본 백업

        console.log('[WBInline] Entering inline mode:', block.dataset.widgetSlug);

        // iframe 숨기기
        var iframe = block.querySelector('.widget-preview');
        var loading = block.querySelector('.widget-loading');
        if (iframe) iframe.classList.add('hidden');
        if (loading) loading.classList.add('hidden');

        // 위젯 블록 하이라이트
        block.querySelector('.widget-border').classList.remove('border-transparent');
        block.querySelector('.widget-border').classList.add('border-green-500');

        // 서버에서 위젯 HTML 가져오기
        var widgetId = parseInt(block.dataset.widgetId);
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'preview_widget', widget_id: widgetId, config: config })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.html) {
                renderInlineContent(block, data.html);
            } else {
                console.warn('[WBInline] No HTML returned');
                exitInlineMode(false);
            }
        })
        .catch(function(err) {
            console.error('[WBInline] Fetch error:', err);
            exitInlineMode(false);
        });
    }

    // ===== 인라인 콘텐츠 렌더링 =====
    function renderInlineContent(block, html) {
        // 인라인 컨테이너 생성
        inlineContainer = document.createElement('div');
        inlineContainer.className = 'widget-inline-container relative';
        inlineContainer.innerHTML = html;
        block.appendChild(inlineContainer);

        // data-widget-field 요소들에 contenteditable 적용
        var editables = inlineContainer.querySelectorAll('[data-widget-field]');
        editables.forEach(function(el) {
            el.contentEditable = 'true';
            el.classList.add('widget-editable');
            el.style.outline = 'none';
            el.style.cursor = 'text';
            el.style.minHeight = '1em';

            // 포커스 시 파란 테두리
            el.addEventListener('focus', function() {
                el.style.boxShadow = '0 0 0 2px rgba(59,130,246,0.5)';
                el.style.borderRadius = '4px';
                console.log('[WBInline] Editing field:', el.dataset.widgetField);
            });
            el.addEventListener('blur', function() {
                el.style.boxShadow = '';
            });

            // 링크는 클릭 이동 방지
            if (el.tagName === 'A') {
                el.addEventListener('click', function(e) { e.preventDefault(); });
            }
        });

        console.log('[WBInline] Found', editables.length, 'editable fields');

        // 그리드 스냅: 오버레이 + 드래그 핸들 + 기존 위치 복원
        if (typeof WBGrid !== 'undefined') {
            WBGrid.showGridOverlay(inlineContainer);
            WBGrid.addDragHandles(inlineContainer);
            var cfg = {};
            try { cfg = JSON.parse(block.dataset.config || '{}'); } catch(e) {}
            WBGrid.applyExistingPositions(inlineContainer, cfg);
        }

        // 오버레이 바 추가
        createOverlayBar(block);
    }

    // ===== 오버레이 Save/Cancel 바 =====
    function createOverlayBar(block) {
        overlayBar = document.createElement('div');
        overlayBar.className = 'widget-inline-bar fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-white dark:bg-zinc-800 shadow-2xl rounded-xl px-5 py-3 border border-zinc-200 dark:border-zinc-700';
        overlayBar.innerHTML =
            '<div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400 mr-3">' +
                '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>' +
                '<span>' + (translations.inline_editing || 'Inline Editing') + '</span></div>' +
            '<button id="btnInlineSave" class="px-4 py-2 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition">' +
                (translations.save || 'Save') + '</button>' +
            '<button id="btnInlineCancel" class="px-4 py-2 text-zinc-600 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 text-xs font-medium rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">' +
                (translations.cancel || 'Cancel') + '</button>';

        document.body.appendChild(overlayBar);

        document.getElementById('btnInlineSave').addEventListener('click', function() {
            saveInlineChanges();
            exitInlineMode(true);
        });
        document.getElementById('btnInlineCancel').addEventListener('click', function() {
            exitInlineMode(false);
        });
    }

    // ===== 인라인 변경사항 저장 =====
    function saveInlineChanges() {
        if (!inlineBlock || !inlineContainer) return;

        var config = {};
        try { config = JSON.parse(inlineBlock.dataset.config || '{}'); } catch(e) {}
        var currentLocale = window.currentLocale || 'ko';

        // data-widget-field 요소에서 텍스트 수집
        var editables = inlineContainer.querySelectorAll('[data-widget-field]');
        editables.forEach(function(el) {
            var fieldKey = el.dataset.widgetField;
            var newText = el.innerText.trim();

            console.log('[WBInline] Saving field:', fieldKey, '=', newText);

            // i18n 값이면 해당 로케일만 업데이트
            if (typeof config[fieldKey] === 'object' && config[fieldKey] !== null && !Array.isArray(config[fieldKey])) {
                config[fieldKey][currentLocale] = newText;
            } else {
                // 단순 문자열이면 i18n 객체로 변환
                var oldVal = config[fieldKey];
                if (typeof oldVal === 'string' || oldVal === undefined) {
                    config[fieldKey] = {};
                    config[fieldKey][currentLocale] = newText;
                } else {
                    config[fieldKey] = newText;
                }
            }
        });

        inlineBlock.dataset.config = JSON.stringify(config);
        console.log('[WBInline] Config updated:', config);
        WB.showStatus('success', translations.config_updated || 'Updated');
    }

    // ===== 인라인 모드 종료 =====
    function exitInlineMode(saved) {
        if (!inlineBlock) return;

        console.log('[WBInline] Exiting inline mode, saved:', saved);

        // 그리드 오버레이 제거
        if (typeof WBGrid !== 'undefined') {
            WBGrid.removeGridOverlay();
        }

        // 저장하지 않았으면 원본 config 복원
        if (!saved && originalConfig) {
            inlineBlock.dataset.config = originalConfig;
        }

        // 인라인 컨테이너 제거
        if (inlineContainer && inlineContainer.parentNode) {
            inlineContainer.remove();
        }
        inlineContainer = null;

        // 오버레이 바 제거
        if (overlayBar && overlayBar.parentNode) {
            overlayBar.remove();
        }
        overlayBar = null;

        // 하이라이트 제거
        inlineBlock.querySelector('.widget-border').classList.remove('border-green-500');
        inlineBlock.querySelector('.widget-border').classList.add('border-transparent');

        // iframe 복원 + 미리보기 다시 로드
        var iframe = inlineBlock.querySelector('.widget-preview');
        var loading = inlineBlock.querySelector('.widget-loading');
        if (loading) loading.classList.remove('hidden');
        if (iframe) iframe.classList.add('hidden');
        WB.loadPreview(inlineBlock);

        inlineBlock = null;
        originalConfig = null;
    }

    // ===== ESC 키로 종료 =====
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && inlineBlock) {
            exitInlineMode(false);
        }
    });

    console.log('[WBInline] Inline editing module ready');

    return {
        enterInlineMode: enterInlineMode,
        exitInlineMode: exitInlineMode
    };
})();
</script>
