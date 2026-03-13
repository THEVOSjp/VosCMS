<?php
/**
 * RezlyX Admin - Feature Items 편집 JS
 * feature_items 배열 관리 (아이콘, 색상, 제목 i18n, 설명 i18n)
 * WBEdit 객체를 통해 코어 편집 JS와 연결
 */
?>
<script>
(function() {
    'use strict';
    var E = WBEdit;
    var esc = E.esc;

    // 아이콘 옵션 (key → label)
    var iconOpts = [
        {v:'mobile',l:'📱 Mobile'},{v:'check-circle',l:'✅ Check'},{v:'credit-card',l:'💳 Card'},
        {v:'calendar',l:'📅 Calendar'},{v:'clock',l:'⏰ Clock'},{v:'users',l:'👥 Users'},
        {v:'shield',l:'🛡️ Shield'},{v:'star',l:'⭐ Star'},{v:'globe',l:'🌐 Globe'},
        {v:'chart',l:'📊 Chart'},{v:'cog',l:'⚙️ Settings'},{v:'lightning',l:'⚡ Lightning'},
        {v:'chat',l:'💬 Chat'},{v:'mail',l:'📧 Mail'},{v:'heart',l:'❤️ Heart'},{v:'cube',l:'📦 Cube'}
    ];

    // 색상 옵션
    var colorOpts = [
        {v:'blue',l:'Blue'},{v:'green',l:'Green'},{v:'purple',l:'Purple'},{v:'red',l:'Red'},
        {v:'orange',l:'Orange'},{v:'indigo',l:'Indigo'},{v:'pink',l:'Pink'},{v:'teal',l:'Teal'}
    ];

    // 색상 프리뷰 클래스
    var colorDot = {
        blue:'bg-blue-500',green:'bg-green-500',purple:'bg-purple-500',red:'bg-red-500',
        orange:'bg-orange-500',indigo:'bg-indigo-500',pink:'bg-pink-500',teal:'bg-teal-500'
    };

    E.renderFeatureItemsSection = function() {
        var items = E.editTempConfig.feature_items || [];
        var h = '<div class="border-t border-zinc-200 dark:border-zinc-700 mt-4 pt-4">';
        h += '<div class="flex items-center justify-between mb-3">';
        h += '<p class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Feature Cards</p>';
        h += '<button type="button" id="btnAddFeatureItem" class="px-2 py-1 bg-blue-600 text-white text-[11px] rounded-lg hover:bg-blue-700 transition flex items-center">';
        h += '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add</button></div>';
        h += '<div id="featureItemsList" class="space-y-2">';
        items.forEach(function(item, i) { h += fiItem(item, i); });
        return h + '</div></div>';
    };

    function fiItem(item, idx) {
        var icon = item.icon || 'cube';
        var color = item.color || 'blue';
        var titleVal = '';
        if (item.title) titleVal = typeof item.title === 'object' ? (item.title[currentLocale] || item.title['ko'] || item.title['en'] || '') : item.title;
        var descVal = '';
        if (item.description) descVal = typeof item.description === 'object' ? (item.description[currentLocale] || item.description['ko'] || item.description['en'] || '') : item.description;

        var h = '<div class="fi-item p-2.5 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg border border-zinc-200 dark:border-zinc-600" data-fi-idx="' + idx + '">';

        // 헤더: 번호 + i18n 토글 + 삭제
        h += '<div class="flex items-center justify-between mb-2">';
        h += '<span class="text-[11px] font-medium text-zinc-500">Card ' + (idx + 1) + '</span>';
        h += '<div class="flex items-center gap-1">';
        h += '<button type="button" class="fi-i18n-toggle p-0.5 text-zinc-400 hover:text-blue-500 rounded transition" title="' + translations.multilang + '">';
        h += '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg></button>';
        h += '<button type="button" class="fi-remove p-0.5 text-red-400 hover:text-red-600 transition">';
        h += '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
        h += '</div></div>';

        // 아이콘 + 색상 (가로 배치)
        h += '<div class="flex gap-2 mb-1.5">';
        // 아이콘 선택
        h += '<div class="flex-1"><label class="text-[10px] text-zinc-400 mb-0.5 block">Icon</label>';
        h += '<select class="fi-icon w-full px-2 py-1 border border-zinc-200 dark:border-zinc-600 rounded text-[10px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">';
        iconOpts.forEach(function(o) { h += '<option value="' + o.v + '"' + (o.v === icon ? ' selected' : '') + '>' + o.l + '</option>'; });
        h += '</select></div>';
        // 색상 선택
        h += '<div class="flex-1"><label class="text-[10px] text-zinc-400 mb-0.5 block">Color</label>';
        h += '<div class="flex items-center gap-1.5">';
        h += '<span class="fi-color-dot w-4 h-4 rounded-full ' + (colorDot[color] || 'bg-blue-500') + '"></span>';
        h += '<select class="fi-color flex-1 px-2 py-1 border border-zinc-200 dark:border-zinc-600 rounded text-[10px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">';
        colorOpts.forEach(function(o) { h += '<option value="' + o.v + '"' + (o.v === color ? ' selected' : '') + '>' + o.l + '</option>'; });
        h += '</select></div></div>';
        h += '</div>';

        // 제목 (현재 로케일)
        h += '<div class="mb-1.5"><label class="text-[10px] text-zinc-400 mb-0.5 block">Title (' + (langNames[currentLocale] || currentLocale) + ')</label>';
        h += '<input type="text" class="fi-title w-full px-2 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded text-[11px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" data-lang="' + currentLocale + '" value="' + esc(titleVal) + '" placeholder="Title"></div>';

        // 설명 (현재 로케일)
        h += '<div class="mb-1"><label class="text-[10px] text-zinc-400 mb-0.5 block">Description (' + (langNames[currentLocale] || currentLocale) + ')</label>';
        h += '<textarea class="fi-desc w-full px-2 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded text-[11px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white resize-y" data-lang="' + currentLocale + '" rows="2" placeholder="Description">' + esc(descVal) + '</textarea></div>';

        // i18n 확장 (숨김 상태)
        h += '<div class="fi-i18n-expanded hidden space-y-1.5 pl-2 border-l-2 border-blue-200 dark:border-blue-800">';
        supportedLangs.forEach(function(lang) {
            if (lang === currentLocale) return;
            var lTitle = (item.title && typeof item.title === 'object') ? (item.title[lang] || '') : '';
            var lDesc = (item.description && typeof item.description === 'object') ? (item.description[lang] || '') : '';
            var ln = langNames[lang] || lang;
            h += '<div class="space-y-1">';
            h += '<label class="text-[10px] text-zinc-400 font-medium">' + esc(ln) + '</label>';
            h += '<input type="text" class="fi-title-extra w-full px-2 py-1 border border-zinc-200 dark:border-zinc-600 rounded text-[10px] bg-zinc-50 dark:bg-zinc-700/50 text-zinc-900 dark:text-white" data-lang="' + lang + '" value="' + esc(lTitle) + '" placeholder="Title (' + esc(ln) + ')">';
            h += '<textarea class="fi-desc-extra w-full px-2 py-1 border border-zinc-200 dark:border-zinc-600 rounded text-[10px] bg-zinc-50 dark:bg-zinc-700/50 text-zinc-900 dark:text-white resize-y" data-lang="' + lang + '" rows="1" placeholder="Desc (' + esc(ln) + ')">' + esc(lDesc) + '</textarea>';
            h += '</div>';
        });
        h += '</div>';

        return h + '</div>';
    }

    E.bindFeatureItemsEvents = function() {
        var addBtn = document.getElementById('btnAddFeatureItem');
        if (addBtn) addBtn.addEventListener('click', function() {
            if (!E.editTempConfig.feature_items) E.editTempConfig.feature_items = [];
            E.editTempConfig.feature_items.push({ icon: 'cube', color: 'blue', title: {}, description: {} });
            refreshFi();
            console.log('[WYSIWYG] Feature item added, total:', E.editTempConfig.feature_items.length);
        });
        bindFiRemove();
        bindFiI18n();
        bindFiColorDot();
    };

    function refreshFi() {
        E.saveFeatureItemsToTemp();
        var list = document.getElementById('featureItemsList');
        if (!list) return;
        list.innerHTML = '';
        (E.editTempConfig.feature_items || []).forEach(function(item, i) {
            list.insertAdjacentHTML('beforeend', fiItem(item, i));
        });
        bindFiRemove();
        bindFiI18n();
        bindFiColorDot();
    }

    function bindFiRemove() {
        E.editPanel.querySelectorAll('.fi-remove').forEach(function(btn) {
            btn.onclick = function() {
                var idx = parseInt(btn.closest('.fi-item').dataset.fiIdx);
                E.saveFeatureItemsToTemp();
                E.editTempConfig.feature_items.splice(idx, 1);
                refreshFi();
                console.log('[WYSIWYG] Feature item removed, idx:', idx);
            };
        });
    }

    function bindFiI18n() {
        E.editPanel.querySelectorAll('.fi-i18n-toggle').forEach(function(btn) {
            btn.onclick = function() {
                var exp = btn.closest('.fi-item').querySelector('.fi-i18n-expanded');
                var isHidden = exp.classList.contains('hidden');
                exp.classList.toggle('hidden');
                btn.classList.toggle('text-blue-500', isHidden);
                btn.classList.toggle('text-zinc-400', !isHidden);
            };
        });
    }

    function bindFiColorDot() {
        E.editPanel.querySelectorAll('.fi-color').forEach(function(sel) {
            sel.addEventListener('change', function() {
                var dot = sel.parentElement.querySelector('.fi-color-dot');
                if (dot) {
                    dot.className = 'fi-color-dot w-4 h-4 rounded-full ' + (colorDot[sel.value] || 'bg-blue-500');
                }
            });
        });
    }

    E.saveFeatureItemsToTemp = function() {
        var items = E.editPanel.querySelectorAll('.fi-item');
        if (items.length === 0) return;
        if (!E.editTempConfig.feature_items) E.editTempConfig.feature_items = [];

        items.forEach(function(item, idx) {
            if (!E.editTempConfig.feature_items[idx]) {
                E.editTempConfig.feature_items[idx] = { icon: 'cube', color: 'blue', title: {}, description: {} };
            }
            var fi = E.editTempConfig.feature_items[idx];
            fi.icon = item.querySelector('.fi-icon').value;
            fi.color = item.querySelector('.fi-color').value;

            // title i18n
            if (typeof fi.title !== 'object' || fi.title === null) fi.title = {};
            var mainTitle = item.querySelector('.fi-title');
            if (mainTitle) fi.title[currentLocale] = mainTitle.value;
            item.querySelectorAll('.fi-title-extra').forEach(function(inp) {
                fi.title[inp.dataset.lang] = inp.value;
            });

            // description i18n
            if (typeof fi.description !== 'object' || fi.description === null) fi.description = {};
            var mainDesc = item.querySelector('.fi-desc');
            if (mainDesc) fi.description[currentLocale] = mainDesc.value;
            item.querySelectorAll('.fi-desc-extra').forEach(function(ta) {
                fi.description[ta.dataset.lang] = ta.value;
            });
        });
    };

    console.log('[WYSIWYG] Edit features (feature_items) ready');
})();
</script>
