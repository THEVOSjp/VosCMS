<?php
/**
 * VosCMS Admin - Grid Section Cells 편집 JS
 * 각 셀에 콘텐츠 타입(게시판/텍스트/이미지/HTML)을 설정
 */
?>
<script>
(function() {
    'use strict';
    var E = WBEdit;
    var esc = E.esc;
    var globeSvg = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>';
    var iCls = 'edit-field w-full px-2.5 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded-lg text-[11px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-1 focus:ring-blue-500';
    var boardList = window._boardList || [];

    var cellTypes = [
        {v:'board-list',l:'Board: List'},
        {v:'board-card',l:'Board: Card Grid'},
        {v:'board-thumb',l:'Board: Thumbnail'},
        {v:'board-gallery',l:'Board: Gallery'},
        {v:'board-banner',l:'Board: Banner'},
        {v:'text',l:'Text'},
        {v:'html',l:'HTML'},
        {v:'image',l:'Image'},
        {v:'spacer',l:'Spacer'}
    ];

    function getVal(obj, loc) {
        if (!obj) return '';
        if (typeof obj === 'string') return obj;
        return obj[loc] || obj[currentLocale] || obj['en'] || obj['ko'] || '';
    }

    function i18nInput(prefix, label, value) {
        var mainVal = getVal(value, currentLocale);
        var h = '<div class="gc-i18n-wrap" data-prefix="' + prefix + '">';
        h += '<div class="flex items-center gap-1 mb-1"><label class="text-[10px] text-zinc-400">' + label + '</label>';
        h += '<button type="button" class="gc-i18n-toggle p-0.5 text-zinc-400 hover:text-blue-500 rounded transition">' + globeSvg + '</button></div>';
        h += '<input type="text" class="gc-i18n-main ' + iCls + '" data-lang="' + currentLocale + '" value="' + esc(mainVal) + '" placeholder="' + label + ' (' + currentLocale + ')">';
        h += '<div class="gc-i18n-expanded hidden mt-1 space-y-1 pl-2 border-l-2 border-blue-200 dark:border-blue-800">';
        supportedLangs.forEach(function(lang) {
            if (lang === currentLocale) return;
            var lv = getVal(value, lang);
            h += '<div><label class="text-[9px] text-zinc-400">' + (langNames[lang]||lang) + '</label><input type="text" class="gc-i18n-sub ' + iCls + '" data-lang="' + lang + '" value="' + esc(lv) + '" placeholder="' + lang + '"></div>';
        });
        return h + '</div></div>';
    }

    function collectI18n(wrap) {
        var r = {};
        var m = wrap.querySelector('.gc-i18n-main');
        if (m) r[m.dataset.lang] = m.value;
        wrap.querySelectorAll('.gc-i18n-sub').forEach(function(el) { if (el.value) r[el.dataset.lang] = el.value; });
        return r;
    }

    // 셀 카드 렌더링
    function cellCard(cell, idx) {
        var type = cell.type || 'board-list';
        var isBoard = type.startsWith('board-');
        var colors = {
            'board-list':'blue','board-card':'blue','board-thumb':'blue','board-gallery':'blue','board-banner':'blue',
            'text':'emerald','html':'amber','image':'purple','spacer':'zinc'
        };
        var c = colors[type] || 'zinc';

        var h = '<div class="gc-cell p-3 bg-' + c + '-50 dark:bg-' + c + '-900/20 rounded-lg border border-' + c + '-200 dark:border-' + c + '-800" data-idx="' + idx + '">';

        // 헤더
        h += '<div class="flex items-center justify-between mb-2">';
        h += '<span class="gc-cell-label text-[11px] font-bold text-' + c + '-600">Cell ' + (idx+1) + '</span>';
        h += '<button type="button" class="gc-cell-remove p-1 text-red-400 hover:text-red-600 rounded transition"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>';

        // 타입 선택
        h += '<div class="mb-2"><label class="text-[10px] text-zinc-400">Type</label>';
        h += '<select class="gc-type ' + iCls + '">';
        cellTypes.forEach(function(ct) { h += '<option value="' + ct.v + '"' + (ct.v===type?' selected':'') + '>' + ct.l + '</option>'; });
        h += '</select></div>';

        // 제목 (다국어) + 바 색상
        h += i18nInput('title', 'Title', cell.title);
        h += '<div class="flex items-center gap-2 mt-1 mb-1"><label class="text-[10px] text-zinc-400">Bar Color</label>';
        h += '<input type="color" class="gc-bar-color w-7 h-6 rounded cursor-pointer border border-zinc-200 dark:border-zinc-600" value="' + esc(cell.bar_color || '#3b82f6') + '">';
        h += '</div>';

        // 게시판 선택 (board- 타입)
        h += '<div class="gc-board-fields ' + (!isBoard?'hidden':'') + ' mt-2 space-y-2">';
        h += '<div><label class="text-[10px] text-zinc-400">Board</label><select class="gc-board ' + iCls + '">';
        h += '<option value="">-- Select --</option>';
        boardList.forEach(function(b) { h += '<option value="' + esc(b.slug) + '"' + (b.slug===(cell.board_slug||'')?' selected':'') + '>' + esc(b.title) + '</option>'; });
        h += '</select></div>';
        h += '<div class="grid grid-cols-2 gap-2">';
        h += '<div><label class="text-[10px] text-zinc-400">Count</label><input type="number" class="gc-count ' + iCls + '" value="' + (cell.count||5) + '"></div>';
        h += '<div><label class="text-[10px] text-zinc-400">Columns</label><select class="gc-columns ' + iCls + '"><option value="2"' + ((cell.columns||'2')==='2'?' selected':'') + '>2</option><option value="3"' + ((cell.columns||'2')==='3'?' selected':'') + '>3</option><option value="4"' + ((cell.columns||'2')==='4'?' selected':'') + '>4</option></select></div>';
        h += '</div>';
        h += '<div class="flex gap-3">';
        h += '<label class="flex items-center gap-1 text-[10px] text-zinc-500"><input type="checkbox" class="gc-show-image rounded border-zinc-300 text-blue-600" ' + ((cell.show_image||0)!=0?'checked':'') + '> Image</label>';
        h += '<label class="flex items-center gap-1 text-[10px] text-zinc-500"><input type="checkbox" class="gc-show-desc rounded border-zinc-300 text-blue-600" ' + ((cell.show_desc||0)!=0?'checked':'') + '> Desc</label>';
        h += '<label class="flex items-center gap-1 text-[10px] text-zinc-500"><input type="checkbox" class="gc-show-more rounded border-zinc-300 text-blue-600" ' + ((cell.show_more||0)!=0?'checked':'') + '> More</label>';
        h += '</div>';
        h += '</div>';

        // 이미지 (image 타입)
        h += '<div class="gc-image-fields ' + (type!=='image'?'hidden':'') + ' mt-2 space-y-2">';
        h += '<div><label class="text-[10px] text-zinc-400">Image URL</label><input type="text" class="gc-image-url ' + iCls + '" value="' + esc(cell.image||'') + '" placeholder="https://..."></div>';
        h += '<div><label class="text-[10px] text-zinc-400">Link URL</label><input type="text" class="gc-image-link ' + iCls + '" value="' + esc(cell.link||'') + '" placeholder="https://..."></div>';
        h += '</div>';

        // 텍스트/HTML (text, html 타입)
        h += '<div class="gc-text-fields ' + (type!=='text'&&type!=='html'?'hidden':'') + ' mt-2">';
        h += i18nInput('content', 'Content', cell.content);
        h += '</div>';

        // Spacer (spacer 타입)
        h += '<div class="gc-spacer-fields ' + (type!=='spacer'?'hidden':'') + ' mt-2">';
        h += '<label class="text-[10px] text-zinc-400">Height (px)</label><input type="number" class="gc-spacer-h ' + iCls + '" value="' + (cell.height||20) + '">';
        h += '</div>';

        h += '</div>';
        return h;
    }

    // 렌더링
    E.renderGridCellsSection = function() {
        var cells = E.editTempConfig.cells || [];
        if (typeof cells === 'object' && !Array.isArray(cells)) cells = Object.values(cells);
        var h = '<div class="border-t border-zinc-200 dark:border-zinc-700 mt-4 pt-4">';
        h += '<div class="flex items-center justify-between mb-3"><p class="text-[10px] font-medium text-zinc-400 uppercase tracking-wider">Grid Cells</p>';
        h += '<button type="button" id="btnAddGridCell" class="px-2 py-1 bg-blue-600 text-white text-[11px] rounded-lg hover:bg-blue-700 transition flex items-center"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add Cell</button></div>';
        h += '<div id="gridCellsList" class="space-y-3">';
        cells.forEach(function(c, i) { h += cellCard(c, i); });
        return h + '</div></div>';
    };

    // 이벤트
    function bindAll() {
        document.querySelectorAll('.gc-i18n-toggle').forEach(function(b) {
            b.onclick = function() { b.closest('.gc-i18n-wrap').querySelector('.gc-i18n-expanded').classList.toggle('hidden'); };
        });
        document.querySelectorAll('.gc-cell-remove').forEach(function(b) {
            b.onclick = function() { b.closest('.gc-cell').remove(); reindex(); };
        });
        // 타입 변경 → 필드 표시/숨김
        document.querySelectorAll('.gc-type').forEach(function(sel) {
            sel.onchange = function() {
                var cell = sel.closest('.gc-cell'), t = sel.value, isBoard = t.startsWith('board-');
                cell.querySelector('.gc-board-fields').classList.toggle('hidden', !isBoard);
                cell.querySelector('.gc-image-fields').classList.toggle('hidden', t !== 'image');
                cell.querySelector('.gc-text-fields').classList.toggle('hidden', t !== 'text' && t !== 'html');
                cell.querySelector('.gc-spacer-fields').classList.toggle('hidden', t !== 'spacer');
            };
        });
    }

    function reindex() {
        document.querySelectorAll('.gc-cell').forEach(function(el, i) {
            el.dataset.idx = i;
            var l = el.querySelector('.gc-cell-label'); if (l) l.textContent = 'Cell ' + (i+1);
        });
    }

    E.bindGridCellsEvents = function() {
        document.getElementById('btnAddGridCell')?.addEventListener('click', function() {
            var cells = E.editTempConfig.cells || [];
            if (typeof cells === 'object' && !Array.isArray(cells)) cells = Object.values(cells);
            cells.push({type:'board-list',title:{},board_slug:'',count:5,show_more:1});
            E.editTempConfig.cells = cells;
            document.getElementById('gridCellsList')?.insertAdjacentHTML('beforeend', cellCard(cells[cells.length-1], cells.length-1));
            bindAll();
        });
        bindAll();
    };

    // 저장
    E.saveGridCellsToTemp = function() {
        var cells = [];
        document.querySelectorAll('.gc-cell').forEach(function(el) {
            var type = el.querySelector('.gc-type').value;
            var titleWrap = el.querySelectorAll('.gc-i18n-wrap')[0];
            var contentWrap = el.querySelectorAll('.gc-i18n-wrap')[1]; // text/html 의 content

            var cell = {
                type: type,
                title: titleWrap ? collectI18n(titleWrap) : {},
                bar_color: el.querySelector('.gc-bar-color')?.value || '#3b82f6',
            };

            if (type.startsWith('board-')) {
                cell.board_slug = el.querySelector('.gc-board')?.value || '';
                cell.count = parseInt(el.querySelector('.gc-count')?.value) || 5;
                cell.columns = el.querySelector('.gc-columns')?.value || '2';
                cell.show_image = el.querySelector('.gc-show-image')?.checked ? 1 : 0;
                cell.show_desc = el.querySelector('.gc-show-desc')?.checked ? 1 : 0;
                cell.show_more = el.querySelector('.gc-show-more')?.checked ? 1 : 0;
            } else if (type === 'image') {
                cell.image = el.querySelector('.gc-image-url')?.value || '';
                cell.link = el.querySelector('.gc-image-link')?.value || '';
            } else if (type === 'text' || type === 'html') {
                cell.content = contentWrap ? collectI18n(contentWrap) : {};
            } else if (type === 'spacer') {
                cell.height = parseInt(el.querySelector('.gc-spacer-h')?.value) || 20;
            }

            cells.push(cell);
        });
        E.editTempConfig.cells = cells;
    };

    console.log('[WYSIWYG] Grid cells editor ready');
})();
</script>
