<?php
/**
 * VosCMS Admin - Service Items 편집 JS
 * 공통 i18n 패턴(i18n-field-wrap + i18n-toggle + i18n-expanded)을 재사용
 */
?>
<script>
(function() {
    'use strict';
    var E = WBEdit;
    var esc = E.esc;
    var globeSvg = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>';
    var iCls = 'edit-field w-full px-2.5 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded-lg text-[11px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-1 focus:ring-blue-500';

    var svcIconOpts = [
        {v:'scissors',p:'M14.121 14.121a3 3 0 01-4.242 0l-1.172-1.172a3 3 0 010-4.242m5.414 5.414L19 19m-4.879-4.879L19 9.12m-9.364 9.364L5 14m4.636 4.485L5 14m4.636-4.879L5 4.757'},
        {v:'heart',p:'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'},
        {v:'star',p:'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'},
        {v:'lightning',p:'M13 10V3L4 14h7v7l9-11h-7z'},
        {v:'globe',p:'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9'},
        {v:'sparkles',p:'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'},
        {v:'shield',p:'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'},
        {v:'clock',p:'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'},
        {v:'users',p:'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'},
        {v:'camera',p:'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z'},
        {v:'music',p:'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3'},
        {v:'gift',p:'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7'}
    ];

    function getVal(obj, loc) {
        if (!obj) return '';
        if (typeof obj === 'string') return obj;
        return obj[loc] || obj[currentLocale] || obj['en'] || obj['ko'] || '';
    }

    // 공통 i18n 입력 블록 생성 (renderI18nField 패턴 재사용)
    function i18nBlock(prefix, label, value, type) {
        type = type || 'text';
        var mainVal = getVal(value, currentLocale);
        var h = '<div class="si-i18n-wrap" data-prefix="' + prefix + '">';
        h += '<div class="flex items-center justify-between mb-1">';
        h += '<label class="text-[10px] font-medium text-zinc-500 dark:text-zinc-400">' + label + '</label>';
        h += '<button type="button" class="si-i18n-toggle p-0.5 text-zinc-400 hover:text-blue-500 rounded transition">' + globeSvg + '</button></div>';
        if (type === 'textarea') {
            h += '<textarea class="si-i18n-main ' + iCls + ' resize-y" data-lang="' + currentLocale + '" rows="2" placeholder="' + label + ' (' + currentLocale + ')">' + esc(mainVal) + '</textarea>';
        } else {
            h += '<input type="text" class="si-i18n-main ' + iCls + '" data-lang="' + currentLocale + '" value="' + esc(mainVal) + '" placeholder="' + label + ' (' + currentLocale + ')">';
        }
        h += '<div class="si-i18n-expanded hidden mt-1.5 space-y-1 pl-2 border-l-2 border-blue-200 dark:border-blue-800">';
        supportedLangs.forEach(function(lang) {
            if (lang === currentLocale) return;
            var lv = getVal(value, lang);
            var ln = langNames[lang] || lang;
            if (type === 'textarea') {
                h += '<div><label class="text-[9px] text-zinc-400">' + esc(ln) + '</label><textarea class="si-i18n-sub ' + iCls + ' resize-y" data-lang="' + lang + '" rows="1" placeholder="' + lang + '">' + esc(lv) + '</textarea></div>';
            } else {
                h += '<div><label class="text-[9px] text-zinc-400">' + esc(ln) + '</label><input type="text" class="si-i18n-sub ' + iCls + '" data-lang="' + lang + '" value="' + esc(lv) + '" placeholder="' + lang + '"></div>';
            }
        });
        h += '</div></div>';
        return h;
    }

    function collectI18n(wrap) {
        var result = {};
        var main = wrap.querySelector('.si-i18n-main');
        if (main) result[main.dataset.lang] = main.value;
        wrap.querySelectorAll('.si-i18n-sub').forEach(function(el) {
            if (el.value) result[el.dataset.lang] = el.value;
        });
        return result;
    }

    // === 렌더링 ===
    E.renderServiceItemsSection = function() {
        var items = E.editTempConfig.service_items || [];
        var h = '<div class="border-t border-zinc-200 dark:border-zinc-700 mt-4 pt-4">';
        h += '<div class="flex items-center justify-between mb-3">';
        h += '<p class="text-[10px] font-medium text-zinc-400 uppercase tracking-wider">Service Cards</p>';
        h += '<button type="button" id="btnAddServiceItem" class="px-2 py-1 bg-blue-600 text-white text-[11px] rounded-lg hover:bg-blue-700 transition flex items-center">';
        h += '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add</button></div>';
        h += '<div id="serviceItemsList" class="space-y-3">';
        items.forEach(function(item, i) { h += siCard(item, i); });
        return h + '</div></div>';
    };

    function siCard(item, idx) {
        var image = item.image || '';
        var icon = item.icon || '';
        var vtype = image ? 'image' : (icon ? 'icon' : 'none');

        var h = '<div class="si-item p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg border border-zinc-200 dark:border-zinc-600" data-si-idx="' + idx + '">';

        // 헤더
        h += '<div class="flex items-center justify-between mb-2">';
        h += '<span class="si-label text-[11px] font-bold text-zinc-500 dark:text-zinc-400">Service ' + (idx+1) + '</span>';
        h += '<button type="button" class="si-remove p-1 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition">';
        h += '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>';

        // 비주얼: 탭 (Image / Icon / None)
        h += '<div class="flex gap-1 mb-2">';
        ['image','icon','none'].forEach(function(t) {
            var active = (t===vtype) ? 'bg-blue-600 text-white border-blue-600' : 'border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-600';
            h += '<button type="button" class="si-vtype-btn px-2.5 py-1 text-[10px] rounded-md border transition ' + active + '" data-vtype="' + t + '">' + t.charAt(0).toUpperCase()+t.slice(1) + '</button>';
        });
        h += '</div>';

        // 이미지 업로드
        h += '<div class="si-visual-image mb-2 ' + (vtype!=='image'?'hidden':'') + '">';
        h += '<div class="si-image-wrap flex items-center gap-2">';
        if (image) h += '<img src="' + esc(image) + '" class="w-20 h-14 object-cover rounded-lg border dark:border-zinc-600">';
        h += '<label class="flex-1 px-3 py-2.5 text-[11px] text-center border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50/50 dark:hover:bg-blue-900/20 transition text-zinc-400">';
        h += '<input type="file" accept="image/*" class="si-img-upload hidden">';
        h += (image ? 'Change' : '+ Upload') + '</label>';
        h += '<input type="hidden" class="si-img-val" value="' + esc(image) + '"></div></div>';

        // 아이콘 선택
        h += '<div class="si-visual-icon mb-2 ' + (vtype!=='icon'?'hidden':'') + '">';
        h += '<div class="grid grid-cols-6 gap-1">';
        svcIconOpts.forEach(function(ic) {
            var sel = (icon===ic.v) ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'hover:bg-zinc-100 dark:hover:bg-zinc-600';
            h += '<button type="button" class="si-icon-btn p-1.5 rounded border border-zinc-200 dark:border-zinc-600 ' + sel + '" data-icon="' + ic.v + '">';
            h += '<svg class="w-4 h-4 mx-auto text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' + ic.p + '"/></svg></button>';
        });
        h += '</div><input type="hidden" class="si-icon-val" value="' + esc(icon) + '"></div>';
        h += '<input type="hidden" class="si-vtype-val" value="' + vtype + '">';

        // 서비스명 (공통 i18n 컴포넌트)
        h += i18nBlock('name', 'Name', item.name, 'text');

        // 설명 (공통 i18n 컴포넌트)
        h += i18nBlock('desc', 'Description', item.description, 'textarea');

        // 가격 + 시간
        h += '<div class="grid grid-cols-2 gap-2 mt-2">';
        h += '<div><label class="text-[9px] text-zinc-400">Price</label><input type="number" class="si-price ' + iCls + '" value="' + (item.price||'') + '" placeholder="0"></div>';
        h += '<div><label class="text-[9px] text-zinc-400">Duration (min)</label><input type="number" class="si-dur ' + iCls + '" value="' + (item.duration||'') + '" placeholder="0"></div>';
        h += '</div></div>';
        return h;
    }

    // === 이벤트 ===
    E.bindServiceItemsEvents = function() {
        document.getElementById('btnAddServiceItem')?.addEventListener('click', function() {
            var items = E.editTempConfig.service_items || [];
            items.push({ name:{}, description:{}, price:0, duration:0, image:'', icon:'' });
            E.editTempConfig.service_items = items;
            document.getElementById('serviceItemsList')?.insertAdjacentHTML('beforeend', siCard(items[items.length-1], items.length-1));
            bindSi();
        });
        bindSi();
    };

    function bindSi() {
        // 삭제
        document.querySelectorAll('.si-remove').forEach(function(b) { b.onclick = function() { b.closest('.si-item').remove(); reindex(); }; });

        // i18n 토글 (공통 패턴)
        document.querySelectorAll('.si-i18n-toggle').forEach(function(b) {
            b.onclick = function() { b.closest('.si-i18n-wrap').querySelector('.si-i18n-expanded').classList.toggle('hidden'); };
        });

        // 비주얼 타입 전환
        document.querySelectorAll('.si-vtype-btn').forEach(function(b) {
            b.onclick = function() {
                var item = b.closest('.si-item'), vt = b.dataset.vtype;
                item.querySelector('.si-vtype-val').value = vt;
                item.querySelectorAll('.si-vtype-btn').forEach(function(x) {
                    var on = x.dataset.vtype===vt;
                    x.className = x.className.replace(/bg-blue-600 text-white border-blue-600|border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-600/g, '');
                    x.className += on ? ' bg-blue-600 text-white border-blue-600' : ' border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-600';
                });
                item.querySelector('.si-visual-image').classList.toggle('hidden', vt!=='image');
                item.querySelector('.si-visual-icon').classList.toggle('hidden', vt!=='icon');
            };
        });

        // 아이콘 선택
        document.querySelectorAll('.si-icon-btn').forEach(function(b) {
            b.onclick = function() {
                var item = b.closest('.si-item');
                item.querySelector('.si-icon-val').value = b.dataset.icon;
                item.querySelectorAll('.si-icon-btn').forEach(function(x) { x.classList.remove('ring-2','ring-blue-500','bg-blue-50','dark:bg-blue-900/30'); });
                b.classList.add('ring-2','ring-blue-500','bg-blue-50','dark:bg-blue-900/30');
            };
        });

        // 이미지 업로드
        document.querySelectorAll('.si-img-upload').forEach(function(inp) {
            inp.onchange = function() {
                if (!inp.files[0]) return;
                var fd = new FormData(); fd.append('action','upload_widget_image'); fd.append('image',inp.files[0]);
                var lbl = inp.closest('label'); lbl.textContent = '...'; lbl.appendChild(inp);
                fetch(window.location.href, {method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d) {
                    if (d.success && d.url) {
                        var wrap = inp.closest('.si-image-wrap'), img = wrap.querySelector('img');
                        if (!img) { img = document.createElement('img'); img.className='w-20 h-14 object-cover rounded-lg border dark:border-zinc-600'; wrap.insertBefore(img,wrap.firstChild); }
                        img.src = d.url;
                        wrap.closest('.si-item').querySelector('.si-img-val').value = d.url;
                        lbl.textContent = 'Change'; lbl.appendChild(inp);
                    } else { lbl.textContent = '+ Upload'; lbl.appendChild(inp); }
                }).catch(function() { lbl.textContent = '+ Upload'; lbl.appendChild(inp); });
            };
        });
    }

    function reindex() {
        document.querySelectorAll('.si-item').forEach(function(el,i) {
            el.dataset.siIdx = i;
            var lbl = el.querySelector('.si-label'); if (lbl) lbl.textContent = 'Service '+(i+1);
        });
    }

    // === 저장 ===
    E.saveServiceItemsToTemp = function() {
        var items = [];
        document.querySelectorAll('.si-item').forEach(function(el) {
            var vt = el.querySelector('.si-vtype-val').value;
            var nameWrap = el.querySelectorAll('.si-i18n-wrap')[0];
            var descWrap = el.querySelectorAll('.si-i18n-wrap')[1];
            items.push({
                name: nameWrap ? collectI18n(nameWrap) : {},
                description: descWrap ? collectI18n(descWrap) : {},
                price: parseInt(el.querySelector('.si-price').value) || 0,
                duration: parseInt(el.querySelector('.si-dur').value) || 0,
                image: vt==='image' ? (el.querySelector('.si-img-val').value||'') : '',
                icon: vt==='icon' ? (el.querySelector('.si-icon-val').value||'') : ''
            });
        });
        E.editTempConfig.service_items = items;
    };

    console.log('[WYSIWYG] Service items editor v3 ready');
})();
</script>
