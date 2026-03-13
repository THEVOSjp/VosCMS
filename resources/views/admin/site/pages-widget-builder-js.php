<?php
/**
 * RezlyX Admin - WYSIWYG 위젯 빌더 JS (코어)
 * iframe 미리보기 + SortableJS + 설정 패널
 */
?>
<script>
var WB = (function() {
    'use strict';
    console.log('[WYSIWYG] Initializing...');

    var canvas = document.getElementById('widgetCanvas');
    var statusMsg = document.getElementById('statusMsg');
    var btnSave = document.getElementById('btnSaveLayout');
    var widgetCountEl = document.getElementById('widgetCount');

    // ===== 초기화: 배치된 위젯 로드 =====
    if (placedWidgetsData.length > 0) {
        placedWidgetsData.forEach(function(pw) {
            var block = createWidgetBlock(pw.widget_id, pw.slug, pw.name, pw.icon, pw.config, pw.config_schema);
            canvas.appendChild(block);
            loadPreview(block);
        });
    } else {
        showEmptyPlaceholder();
    }
    updateWidgetCount();

    // ===== SortableJS =====
    Sortable.create(canvas, {
        animation: 200, handle: '.drag-handle',
        ghostClass: 'widget-ghost', chosenClass: 'widget-chosen',
        filter: '#emptyPlaceholder',
        onSort: function() { console.log('[WYSIWYG] Layout reordered'); }
    });

    // ===== 카테고리 플라이아웃 시스템 =====
    var flyout = document.getElementById('widgetFlyout');
    var flyoutTitle = document.getElementById('flyoutTitle');
    var flyoutContent = document.getElementById('flyoutContent');
    var flyoutClose = document.getElementById('flyoutClose');
    var activeCategoryEl = null;
    var flyoutHideTimer = null;
    var widgetIconMap = window.widgetIconMap || {};

    // 카테고리별 색상 매핑
    var catColors = {
        layout: { bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-600 dark:text-blue-400', border: 'border-blue-200 dark:border-blue-700', hoverBg: 'hover:border-blue-300 dark:hover:border-blue-600' },
        content: { bg: 'bg-emerald-100 dark:bg-emerald-900/30', text: 'text-emerald-600 dark:text-emerald-400', border: 'border-emerald-200 dark:border-emerald-700', hoverBg: 'hover:border-emerald-300 dark:hover:border-emerald-600' },
        marketing: { bg: 'bg-amber-100 dark:bg-amber-900/30', text: 'text-amber-600 dark:text-amber-400', border: 'border-amber-200 dark:border-amber-700', hoverBg: 'hover:border-amber-300 dark:hover:border-amber-600' },
        general: { bg: 'bg-purple-100 dark:bg-purple-900/30', text: 'text-purple-600 dark:text-purple-400', border: 'border-purple-200 dark:border-purple-700', hoverBg: 'hover:border-purple-300 dark:hover:border-purple-600' }
    };

    // 위젯 데이터 수집 (hidden store에서)
    var allWidgetData = {};
    document.querySelectorAll('#widgetDataStore .widget-palette-item').forEach(function(el) {
        var d = el.dataset;
        var cat = d.widgetCategory || 'general';
        if (!allWidgetData[cat]) allWidgetData[cat] = [];
        allWidgetData[cat].push({
            id: d.widgetId, slug: d.widgetSlug, name: d.widgetName,
            icon: d.widgetIcon, description: d.widgetDescription || '',
            configSchema: d.configSchema || '{}', defaultConfig: d.defaultConfig || '{}',
            thumbnail: d.widgetThumbnail || ''
        });
    });

    function showFlyout(category) {
        if (flyoutHideTimer) { clearTimeout(flyoutHideTimer); flyoutHideTimer = null; }
        var widgets = allWidgetData[category] || [];
        var colors = catColors[category] || catColors.general;

        // 카테고리 라벨
        flyoutTitle.textContent = (window.categoryTranslations && categoryTranslations[category]) || category;

        // 위젯 카드 생성
        flyoutContent.innerHTML = '';
        widgets.forEach(function(w) {
            var wt = (window.widgetTranslations && widgetTranslations[w.slug]) || {};
            var displayName = wt.name || w.name;
            var desc = wt.desc || w.description;
            var iconPath = widgetIconMap[w.icon] || widgetIconMap['cube'] || '';

            var card = document.createElement('div');
            card.className = 'widget-palette-item group/card flex flex-col items-center p-3 rounded-xl border ' + colors.border + ' ' + colors.hoverBg + ' bg-white dark:bg-zinc-800/50 cursor-pointer transition-all';
            card.dataset.widgetId = w.id;
            card.dataset.widgetSlug = w.slug;
            card.dataset.widgetName = w.name;
            card.dataset.widgetIcon = w.icon;
            card.dataset.configSchema = w.configSchema;
            card.dataset.defaultConfig = w.defaultConfig;

            // 썸네일 또는 아이콘
            var visualHtml = w.thumbnail
                ? '<div class="w-full aspect-video rounded-lg overflow-hidden mb-2 bg-zinc-100 dark:bg-zinc-700">' +
                      '<img src="' + escapeHtml(w.thumbnail) + '" alt="" class="w-full h-full object-cover">' +
                  '</div>'
                : '<div class="w-10 h-10 ' + colors.bg + ' rounded-lg flex items-center justify-center mb-2">' +
                      '<svg class="w-5 h-5 ' + colors.text + '" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                          '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' + escapeHtml(iconPath) + '"/>' +
                      '</svg>' +
                  '</div>';

            card.innerHTML = visualHtml +
                '<p class="text-xs font-semibold text-zinc-700 dark:text-zinc-200 text-center leading-tight">' + escapeHtml(displayName) + '</p>' +
                '<p class="text-[10px] text-zinc-400 dark:text-zinc-500 text-center mt-1 line-clamp-2 leading-tight">' + escapeHtml(desc) + '</p>' +
                '<div class="mt-2 opacity-0 group-hover/card:opacity-100 transition-opacity">' +
                    '<span class="inline-flex items-center gap-1 text-[10px] font-medium ' + colors.text + '">' +
                        '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>' +
                        'Add' +
                    '</span>' +
                '</div>';

            // 클릭 → 위젯 추가
            card.addEventListener('click', function() {
                console.log('[WYSIWYG] Adding widget from flyout:', w.slug);
                removeEmptyPlaceholder();
                var block = createWidgetBlock(w.id, w.slug, w.name, w.icon, w.defaultConfig, w.configSchema);
                canvas.appendChild(block);
                loadPreview(block);
                updateWidgetCount();
                block.scrollIntoView({ behavior: 'smooth', block: 'end' });
                // 추가 피드백
                card.classList.add('ring-2', 'ring-blue-400', 'scale-95');
                setTimeout(function() { card.classList.remove('ring-2', 'ring-blue-400', 'scale-95'); }, 300);
            });

            flyoutContent.appendChild(card);
        });

        flyout.classList.remove('hidden');

        // active 표시
        document.querySelectorAll('.category-item').forEach(function(el) { el.classList.remove('active'); });
        var activeEl = document.querySelector('.category-item[data-category="' + category + '"]');
        if (activeEl) { activeEl.classList.add('active'); activeCategoryEl = activeEl; }
    }

    function hideFlyout() {
        flyoutHideTimer = setTimeout(function() {
            flyout.classList.add('hidden');
            if (activeCategoryEl) { activeCategoryEl.classList.remove('active'); activeCategoryEl = null; }
        }, 200);
    }

    // 카테고리 호버 이벤트
    document.querySelectorAll('.category-item').forEach(function(catEl) {
        catEl.addEventListener('mouseenter', function() {
            showFlyout(this.dataset.category);
        });
        catEl.addEventListener('mouseleave', function() {
            hideFlyout();
        });
        // 클릭도 지원 (모바일)
        catEl.addEventListener('click', function() {
            var cat = this.dataset.category;
            if (flyout.classList.contains('hidden') || (activeCategoryEl && activeCategoryEl.dataset.category !== cat)) {
                showFlyout(cat);
            } else {
                flyout.classList.add('hidden');
                this.classList.remove('active');
                activeCategoryEl = null;
            }
        });
    });

    // 플라이아웃 호버 유지
    flyout.addEventListener('mouseenter', function() {
        if (flyoutHideTimer) { clearTimeout(flyoutHideTimer); flyoutHideTimer = null; }
    });
    flyout.addEventListener('mouseleave', function() {
        hideFlyout();
    });

    // 닫기 버튼
    flyoutClose.addEventListener('click', function() {
        flyout.classList.add('hidden');
        if (activeCategoryEl) { activeCategoryEl.classList.remove('active'); activeCategoryEl = null; }
    });

    // ===== 팔레트 클릭 → 위젯 추가 (hidden store 데이터에서) =====
    document.querySelectorAll('#widgetDataStore .widget-palette-item').forEach(function(item) {
        item.addEventListener('click', function() {
            var d = this.dataset;
            console.log('[WYSIWYG] Adding widget:', d.widgetSlug);
            removeEmptyPlaceholder();
            var block = createWidgetBlock(d.widgetId, d.widgetSlug, d.widgetName, d.widgetIcon, d.defaultConfig || '{}', d.configSchema || '{}');
            canvas.appendChild(block);
            loadPreview(block);
            updateWidgetCount();
            block.scrollIntoView({ behavior: 'smooth', block: 'end' });
        });
    });

    // ===== 위젯 블록 생성 =====
    function createWidgetBlock(widgetId, slug, name, icon, configStr, schemaStr) {
        var wt = (window.widgetTranslations && widgetTranslations[slug]) || {};
        var displayName = wt.name || name;
        var block = document.createElement('div');
        block.className = 'widget-block relative group';
        block.dataset.widgetId = widgetId;
        block.dataset.widgetSlug = slug;
        block.dataset.config = configStr;
        block.dataset.configSchema = schemaStr;

        block.innerHTML =
            '<div class="widget-toolbar absolute top-0 left-0 right-0 z-20 opacity-0 transition-opacity pointer-events-none group-hover:pointer-events-auto">' +
                '<div class="bg-blue-600/90 backdrop-blur-sm text-white flex items-center px-3 py-1.5">' +
                    '<div class="drag-handle cursor-grab p-0.5 mr-2 hover:bg-blue-500 rounded transition" title="Drag">' +
                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg></div>' +
                    '<span class="text-xs font-medium truncate">' + escapeHtml(displayName) + '</span>' +
                    '<span class="ml-2 px-1.5 py-0.5 bg-blue-500/60 rounded text-[10px] hidden sm:inline">' + escapeHtml(slug) + '</span>' +
                    '<div class="ml-auto flex items-center gap-0.5">' +
                        '<button class="btn-up p-1 hover:bg-blue-500 rounded transition" title="Up"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg></button>' +
                        '<button class="btn-down p-1 hover:bg-blue-500 rounded transition" title="Down"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>' +
                        '<span class="w-px h-4 bg-blue-400/50 mx-1"></span>' +
                        '<button class="btn-edit p-1 hover:bg-blue-500 rounded transition" title="Inline Edit"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>' +
                        '<button class="btn-config p-1 hover:bg-blue-500 rounded transition" title="Settings"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></button>' +
                        '<button class="btn-remove p-1 hover:bg-red-500 rounded transition" title="Delete"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>' +
                    '</div></div></div>' +
            '<div class="widget-border absolute inset-0 border-2 border-transparent group-hover:border-blue-500 pointer-events-none z-10 transition-colors"></div>' +
            '<div class="widget-loading flex items-center justify-center py-20 text-zinc-400 dark:text-zinc-600">' +
                '<svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>' +
                '<span class="text-xs">' + translations.loading + '</span></div>' +
            '<iframe class="widget-preview w-full border-0 block hidden" style="pointer-events:none;min-height:40px" allow="autoplay; encrypted-media" allowfullscreen></iframe>';

        bindBlockEvents(block);
        return block;
    }

    function bindBlockEvents(block) {
        block.querySelector('.btn-edit').addEventListener('click', function(e) { e.stopPropagation(); if (WB.enterInlineMode) WB.enterInlineMode(block); else console.warn('[WYSIWYG] Inline mode not loaded'); });
        block.querySelector('.btn-config').addEventListener('click', function(e) { e.stopPropagation(); WB.openEditModal(block); });
        block.querySelector('.btn-remove').addEventListener('click', function(e) {
            e.stopPropagation();
            if (confirm(translations.remove_confirm)) {
                console.log('[WYSIWYG] Removing:', block.dataset.widgetSlug);
                block.remove(); updateWidgetCount();
                if (!canvas.querySelector('.widget-block')) showEmptyPlaceholder();
            }
        });
        block.querySelector('.btn-up').addEventListener('click', function(e) {
            e.stopPropagation();
            var prev = block.previousElementSibling;
            if (prev && prev.classList.contains('widget-block')) canvas.insertBefore(block, prev);
        });
        block.querySelector('.btn-down').addEventListener('click', function(e) {
            e.stopPropagation();
            var next = block.nextElementSibling;
            if (next && next.classList.contains('widget-block')) canvas.insertBefore(next, block);
        });
    }

    // ===== 미리보기 로드 =====
    function loadPreview(block) {
        var widgetId = parseInt(block.dataset.widgetId);
        var config = {};
        try { config = JSON.parse(block.dataset.config || '{}'); } catch(e) {}
        console.log('[WYSIWYG] Loading preview:', block.dataset.widgetSlug);

        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'preview_widget', widget_id: widgetId, config: config })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.html) {
                var iframe = block.querySelector('.widget-preview');
                var loading = block.querySelector('.widget-loading');
                iframe.srcdoc = buildPreviewDoc(data.html);
                iframe.onload = function() {
                    loading.classList.add('hidden');
                    iframe.classList.remove('hidden');
                    adjustIframeHeight(iframe);
                    setTimeout(function() { adjustIframeHeight(iframe); }, 600);
                    setTimeout(function() { adjustIframeHeight(iframe); }, 1500);
                };
            } else {
                block.querySelector('.widget-loading').innerHTML = '<span class="text-xs text-zinc-400">Empty widget</span>';
            }
        })
        .catch(function(err) {
            console.error('[WYSIWYG] Preview error:', err);
            block.querySelector('.widget-loading').innerHTML = '<span class="text-xs text-red-500">Preview failed</span>';
        });
    }

    function buildPreviewDoc(html) {
        var isDark = document.documentElement.classList.contains('dark');
        return '<!DOCTYPE html><html class="' + (isDark ? 'dark' : '') + '"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">' +
            '<script src="https://cdn.tailwindcss.com"><\/script><script>tailwind.config={darkMode:"class"}<\/script>' +
            '<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">' +
            '<style>*{margin:0;padding:0}body{overflow:hidden;font-family:"Pretendard",-apple-system,sans-serif}</style>' +
            '</head><body class="' + (isDark ? 'dark bg-zinc-900' : 'bg-white') + '">' + html + '</body></html>';
    }

    function adjustIframeHeight(iframe) {
        try { var h = iframe.contentDocument.documentElement.scrollHeight; if (h > 20) iframe.style.height = h + 'px'; } catch(e) {}
    }

    // ===== 레이아웃 저장 =====
    btnSave.addEventListener('click', function() {
        var items = [];
        canvas.querySelectorAll('.widget-block').forEach(function(el) {
            var config = {}; try { config = JSON.parse(el.dataset.config || '{}'); } catch(e) {}
            items.push({ widget_id: parseInt(el.dataset.widgetId), config: config });
        });
        console.log('[WYSIWYG] Saving layout, widgets:', items.length);
        btnSave.disabled = true;
        var origHTML = btnSave.innerHTML;
        btnSave.innerHTML = '<svg class="animate-spin w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>...';
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'save_layout', items: items })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            showStatus(data.success ? 'success' : 'error', data.message || translations.error_save);
            btnSave.disabled = false; btnSave.innerHTML = origHTML;
        })
        .catch(function(err) {
            console.error('[WYSIWYG] Save error:', err);
            showStatus('error', translations.error_save);
            btnSave.disabled = false; btnSave.innerHTML = origHTML;
        });
    });

    // ===== 유틸리티 =====
    function removeEmptyPlaceholder() { var ep = document.getElementById('emptyPlaceholder'); if (ep) ep.remove(); }
    function showEmptyPlaceholder() {
        if (document.getElementById('emptyPlaceholder')) return;
        var div = document.createElement('div'); div.id = 'emptyPlaceholder';
        div.className = 'flex flex-col items-center justify-center py-24 text-zinc-300 dark:text-zinc-600';
        div.innerHTML = '<svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>' +
            '<p class="text-sm font-medium mb-1">' + translations.empty + '</p>' +
            '<p class="text-xs text-zinc-400 dark:text-zinc-600">Click a widget from the left panel</p>';
        canvas.appendChild(div);
    }
    function updateWidgetCount() { widgetCountEl.textContent = canvas.querySelectorAll('.widget-block').length + ' ' + translations.widgets_count; }
    function showStatus(type, msg) {
        statusMsg.className = 'mb-3 p-2.5 rounded-lg text-xs ' +
            (type === 'success' ? 'bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800'
                : 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800');
        statusMsg.textContent = msg; statusMsg.classList.remove('hidden');
        setTimeout(function() { statusMsg.classList.add('hidden'); }, 3000);
    }
    function escapeHtml(str) { var d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

    console.log('[WYSIWYG] Core ready, placed widgets:', placedWidgetsData.length);

    // Public API
    return {
        canvas: canvas,
        loadPreview: loadPreview,
        showStatus: showStatus,
        escapeHtml: escapeHtml,
        openEditModal: null, // set by edit-js
        enterInlineMode: null // set by inline-js
    };
})();
</script>
<?php include __DIR__ . '/pages-widget-builder-edit-js.php'; ?>
