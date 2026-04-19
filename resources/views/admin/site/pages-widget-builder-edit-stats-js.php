<?php
/**
 * RezlyX Admin - Stat Items 편집 JS
 * stat_items 배열 관리 (숫자, label i18n, 아이콘, 색상, 접미사)
 * WBEdit 객체를 통해 코어 편집 JS와 연결
 */
?>
<script>
(function() {
    'use strict';
    var E = WBEdit;
    var esc = E.esc;

    // 아이콘 옵션 — feature_items 와 통일
    var iconOpts = [
        {v:'',l:'(없음)'},
        {v:'users',l:'👥 Users'},{v:'chart',l:'📊 Chart'},{v:'star',l:'⭐ Star'},
        {v:'heart',l:'❤️ Heart'},{v:'check-circle',l:'✅ Check'},{v:'clock',l:'⏰ Clock'},
        {v:'calendar',l:'📅 Calendar'},{v:'globe',l:'🌐 Globe'},{v:'shield',l:'🛡️ Shield'},
        {v:'lightning',l:'⚡ Lightning'},{v:'trophy',l:'🏆 Trophy'},{v:'cart',l:'🛒 Cart'},
        {v:'dollar',l:'💵 Dollar'},{v:'building',l:'🏢 Building'},{v:'chat',l:'💬 Chat'}
    ];

    var colorOpts = [
        {v:'blue',l:'Blue'},{v:'green',l:'Green'},{v:'purple',l:'Purple'},{v:'red',l:'Red'},
        {v:'orange',l:'Orange'},{v:'indigo',l:'Indigo'},{v:'pink',l:'Pink'},{v:'teal',l:'Teal'},
        {v:'amber',l:'Amber'},{v:'zinc',l:'Zinc'}
    ];

    // ===== 렌더 =====
    E.renderStatItemsSection = function() {
        var items = E.editTempConfig.items || [];
        var h = '<div class="border-t border-zinc-200 dark:border-zinc-700 mt-4 pt-4">';
        h += '<div class="flex items-center justify-between mb-3">';
        h += '<p class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Statistics Items</p>';
        h += '<button type="button" id="btnAddStatItem" class="px-2 py-1 bg-blue-600 text-white text-[11px] rounded-lg hover:bg-blue-700 transition flex items-center">';
        h += '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add</button></div>';
        h += '<div id="statItemsList" class="space-y-2">';
        items.forEach(function(item, i) { h += stiItem(item, i); });
        return h + '</div></div>';
    };

    function stiItem(item, idx) {
        var number = item.number || '';
        var suffix = item.suffix || '';
        var icon   = item.icon || '';
        var color  = item.color || 'blue';
        var labelVal = '';
        if (item.label) labelVal = typeof item.label === 'object' ? (item.label[currentLocale] || item.label['ko'] || item.label['en'] || '') : item.label;

        var h = '<div class="sti-item p-2.5 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg border border-zinc-200 dark:border-zinc-600" data-sti-idx="' + idx + '">';
        h += '<div class="flex items-center justify-between mb-1.5"><span class="text-[11px] font-medium text-zinc-500">Item ' + (idx + 1) + '</span>';
        h += '<div class="flex items-center gap-1">';
        h += '<button type="button" class="sti-i18n-toggle p-0.5 text-zinc-400 hover:text-blue-500 rounded transition" title="' + translations.multilang + '"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg></button>';
        h += '<button type="button" class="sti-remove p-0.5 text-red-400 hover:text-red-600 transition"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
        h += '</div></div>';

        // number + suffix
        h += '<div class="flex gap-1.5 mb-1.5">';
        h += '<input type="text" class="sti-number flex-1 px-2 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded text-[11px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" value="' + esc(String(number)) + '" placeholder="10000">';
        h += '<input type="text" class="sti-suffix w-16 px-2 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded text-[11px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-center" value="' + esc(String(suffix)) + '" placeholder="+, %">';
        h += '</div>';

        // label (현재 로케일)
        h += '<input type="text" class="sti-label w-full px-2 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded text-[11px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white mb-1.5" data-lang="' + currentLocale + '" value="' + esc(labelVal) + '" placeholder="Label (' + (langNames[currentLocale] || currentLocale) + ')">';

        // i18n expanded
        h += '<div class="sti-i18n-expanded hidden space-y-1 mb-1.5 pl-2 border-l-2 border-blue-200 dark:border-blue-800">';
        supportedLangs.forEach(function(lang) {
            if (lang === currentLocale) return;
            var lv = (item.label && typeof item.label === 'object') ? (item.label[lang] || '') : '';
            h += '<div><label class="text-[10px] text-zinc-400">' + esc(langNames[lang] || lang) + '</label>';
            h += '<input type="text" class="sti-label-extra w-full px-2 py-1 border border-zinc-200 dark:border-zinc-600 rounded text-[10px] bg-zinc-50 dark:bg-zinc-700/50 text-zinc-900 dark:text-white" data-lang="' + lang + '" value="' + esc(lv) + '" placeholder="(' + esc(langNames[lang] || lang) + ')"></div>';
        });
        h += '</div>';

        // icon + color
        h += '<div class="flex gap-1.5">';
        h += '<select class="sti-icon flex-1 px-2 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded text-[11px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">';
        iconOpts.forEach(function(o) { h += '<option value="' + o.v + '"' + (o.v === icon ? ' selected' : '') + '>' + o.l + '</option>'; });
        h += '</select>';
        h += '<select class="sti-color w-24 px-2 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded text-[11px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">';
        colorOpts.forEach(function(o) { h += '<option value="' + o.v + '"' + (o.v === color ? ' selected' : '') + '>' + o.l + '</option>'; });
        h += '</select>';
        h += '</div>';

        return h + '</div>';
    }

    // ===== 바인딩 =====
    E.bindStatItemsEvents = function() {
        var addBtn = document.getElementById('btnAddStatItem');
        if (addBtn) addBtn.addEventListener('click', function() {
            // 기존 DOM 입력값을 먼저 items 로 흡수한 뒤 새 항목 push
            E.saveStatItemsToTemp();
            if (!E.editTempConfig.items) E.editTempConfig.items = [];
            E.editTempConfig.items.push({ number: '0', suffix: '', label: {}, icon: '', color: 'blue' });
            refreshSti();
        });
        bindStiRemove(); bindStiI18n();
    };

    // 순수 렌더 — save 호출 안 함 (Add/Remove 전후에 호출자가 책임)
    function refreshSti() {
        var list = document.getElementById('statItemsList');
        if (!list) return;
        list.innerHTML = '';
        (E.editTempConfig.items || []).forEach(function(it, i) { list.insertAdjacentHTML('beforeend', stiItem(it, i)); });
        bindStiRemove(); bindStiI18n();
    }

    function bindStiRemove() {
        E.editPanel.querySelectorAll('.sti-remove').forEach(function(btn) {
            btn.onclick = function() {
                var idx = parseInt(btn.closest('.sti-item').dataset.stiIdx);
                E.saveStatItemsToTemp();         // 현재 DOM → items 동기화
                E.editTempConfig.items.splice(idx, 1);
                refreshSti();                     // 새 items 로 DOM 재구성
            };
        });
    }

    function bindStiI18n() {
        E.editPanel.querySelectorAll('.sti-i18n-toggle').forEach(function(btn) {
            btn.onclick = function() {
                var exp = btn.closest('.sti-item').querySelector('.sti-i18n-expanded');
                var h = exp.classList.contains('hidden'); exp.classList.toggle('hidden');
                btn.classList.toggle('text-blue-500', h); btn.classList.toggle('text-zinc-400', !h);
            };
        });
    }

    E.saveStatItemsToTemp = function() {
        try {
            var items = E.editPanel.querySelectorAll('.sti-item');
            if (!items || items.length === 0) return;
            if (!Array.isArray(E.editTempConfig.items)) E.editTempConfig.items = [];
            items.forEach(function(item, idx) {
                if (!E.editTempConfig.items[idx] || typeof E.editTempConfig.items[idx] !== 'object') {
                    E.editTempConfig.items[idx] = { number:'', suffix:'', label:{}, icon:'', color:'blue' };
                }
                var entry = E.editTempConfig.items[idx];
                var numEl = item.querySelector('.sti-number');
                var sufEl = item.querySelector('.sti-suffix');
                var icoEl = item.querySelector('.sti-icon');
                var colEl = item.querySelector('.sti-color');
                var lblEl = item.querySelector('.sti-label');
                if (numEl) entry.number = numEl.value;
                if (sufEl) entry.suffix = sufEl.value;
                if (icoEl) entry.icon   = icoEl.value;
                if (colEl) entry.color  = colEl.value;
                if (typeof entry.label !== 'object' || entry.label === null) entry.label = {};
                if (lblEl) entry.label[currentLocale] = lblEl.value;
                item.querySelectorAll('.sti-label-extra').forEach(function(inp) {
                    entry.label[inp.dataset.lang] = inp.value;
                });
            });
        } catch (err) {
            console.error('[WYSIWYG] saveStatItemsToTemp error:', err);
        }
    };

    console.log('[WYSIWYG] Edit stat_items ready');
})();
</script>
