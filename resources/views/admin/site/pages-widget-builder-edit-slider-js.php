<?php
/**
 * VosCMS Admin - Hero Slider 편집 JS (v1.1 통합 구조)
 * items[] = 슬라이드 + 사이드 네비게이션 1:1 묶음
 * 공통 i18n 패턴 재사용
 */
?>
<script>
(function() {
    'use strict';
    var E = WBEdit;
    var esc = E.esc;
    var globeSvg = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>';
    var iCls = 'edit-field w-full px-2.5 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded-lg text-[11px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-1 focus:ring-blue-500';

    function getVal(obj, loc) {
        if (!obj) return '';
        if (typeof obj === 'string') return obj;
        return obj[loc] || obj[currentLocale] || obj['en'] || obj['ko'] || '';
    }

    function i18nField(prefix, label, value, type) {
        type = type || 'text';
        var mainVal = getVal(value, currentLocale);
        var h = '<div class="slider-i18n-wrap" data-prefix="' + prefix + '">';
        h += '<div class="flex items-center justify-between mb-1"><label class="text-[10px] font-medium text-zinc-500 dark:text-zinc-400">' + label + '</label>';
        h += '<button type="button" class="slider-i18n-toggle p-0.5 text-zinc-400 hover:text-blue-500 rounded transition">' + globeSvg + '</button></div>';
        if (type === 'textarea') h += '<textarea class="slider-i18n-main ' + iCls + ' resize-y" data-lang="' + currentLocale + '" rows="2" placeholder="' + label + ' (' + currentLocale + ')">' + esc(mainVal) + '</textarea>';
        else h += '<input type="text" class="slider-i18n-main ' + iCls + '" data-lang="' + currentLocale + '" value="' + esc(mainVal) + '" placeholder="' + label + ' (' + currentLocale + ')">';
        h += '<div class="slider-i18n-expanded hidden mt-1 space-y-1 pl-2 border-l-2 border-blue-200 dark:border-blue-800">';
        supportedLangs.forEach(function(lang) {
            if (lang === currentLocale) return;
            var lv = getVal(value, lang), ln = langNames[lang] || lang;
            if (type === 'textarea') h += '<div><label class="text-[9px] text-zinc-400">' + esc(ln) + '</label><textarea class="slider-i18n-sub ' + iCls + ' resize-y" data-lang="' + lang + '" rows="1" placeholder="' + lang + '">' + esc(lv) + '</textarea></div>';
            else h += '<div><label class="text-[9px] text-zinc-400">' + esc(ln) + '</label><input type="text" class="slider-i18n-sub ' + iCls + '" data-lang="' + lang + '" value="' + esc(lv) + '" placeholder="' + lang + '"></div>';
        });
        return h + '</div></div>';
    }

    function collectI18n(wrap) {
        var r = {};
        var m = wrap.querySelector('.slider-i18n-main');
        if (m) r[m.dataset.lang] = m.value;
        wrap.querySelectorAll('.slider-i18n-sub').forEach(function(el) { if (el.value) r[el.dataset.lang] = el.value; });
        return r;
    }

    // 하위 호환: 기존 slides[] + nav_items[] → items[] 변환
    function migrateToItems() {
        var items = E.editTempConfig.items;
        if (items && (Array.isArray(items) ? items.length : Object.keys(items).length)) return;
        var slides = E.editTempConfig.slides || [];
        var navs = E.editTempConfig.nav_items || [];
        if (typeof slides === 'object' && !Array.isArray(slides)) slides = Object.values(slides);
        if (typeof navs === 'object' && !Array.isArray(navs)) navs = Object.values(navs);
        var merged = [];
        var max = Math.max(slides.length, navs.length);
        for (var i = 0; i < max; i++) {
            var s = slides[i] || {};
            var n = navs[i] || {};
            merged.push({
                image: s.image || '', video: s.video || '',
                title: s.title || {}, subtitle: s.subtitle || {},
                btn_text: s.btn_text || {}, btn_url: s.btn_url || '',
                nav_title: n.title || {}, nav_subtitle: n.subtitle || {}, nav_url: n.url || ''
            });
        }
        E.editTempConfig.items = merged;
    }

    // ===== 렌더링 =====
    E.renderSliderItemsSection = function() {
        migrateToItems();
        var items = E.editTempConfig.items || [];
        if (typeof items === 'object' && !Array.isArray(items)) items = Object.values(items);
        var h = '<div class="border-t border-zinc-200 dark:border-zinc-700 mt-4 pt-4">';
        h += '<div class="flex items-center justify-between mb-3"><p class="text-[10px] font-medium text-zinc-400 uppercase tracking-wider">Slider Items</p>';
        h += '<button type="button" id="btnAddSliderItem" class="px-2 py-1 bg-blue-600 text-white text-[11px] rounded-lg hover:bg-blue-700 transition flex items-center"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add</button></div>';
        h += '<div id="sliderItemsList" class="space-y-3">';
        items.forEach(function(item, i) { h += itemCard(item, i); });
        return h + '</div></div>';
    };

    function itemCard(item, idx) {
        var img = item.image || '';
        var video = item.video || '';
        var mediaType = video ? 'video' : 'image';

        var h = '<div class="slider-item p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg border border-zinc-200 dark:border-zinc-600" data-idx="' + idx + '">';

        // 헤더
        h += '<div class="flex items-center justify-between mb-2"><span class="slider-item-label text-[11px] font-bold text-zinc-500 dark:text-zinc-400">#' + (idx+1) + '</span>';
        h += '<button type="button" class="slider-item-remove p-1 text-red-400 hover:text-red-600 rounded transition"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>';

        // ── 슬라이드 섹션 ──
        h += '<div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 mb-2">';
        h += '<p class="text-[9px] font-bold text-blue-500 uppercase mb-2">Slide</p>';

        // 미디어 타입 (Image / Video)
        h += '<div class="flex gap-1 mb-2">';
        h += '<button type="button" class="si-media-btn px-2.5 py-1 text-[10px] rounded-md border transition ' + (mediaType==='image' ? 'bg-blue-600 text-white border-blue-600' : 'border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-600') + '" data-mtype="image">Image</button>';
        h += '<button type="button" class="si-media-btn px-2.5 py-1 text-[10px] rounded-md border transition ' + (mediaType==='video' ? 'bg-blue-600 text-white border-blue-600' : 'border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-600') + '" data-mtype="video">Video</button>';
        h += '</div>';

        // 이미지
        h += '<div class="si-media-image ' + (mediaType!=='image'?'hidden':'') + ' mb-2">';
        h += '<div class="si-img-wrap flex items-center gap-2">';
        if (img) h += '<img src="' + esc(img) + '" class="w-24 h-14 object-cover rounded-lg border dark:border-zinc-600">';
        h += '<label class="flex-1 px-3 py-2 text-[11px] text-center border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-blue-400 transition text-zinc-400"><input type="file" accept="image/*" class="si-img-upload hidden">' + (img ? 'Change' : '+ Image') + '</label>';
        h += '</div></div>';

        // 비디오
        h += '<div class="si-media-video ' + (mediaType!=='video'?'hidden':'') + ' space-y-2 mb-2">';
        h += '<div class="flex items-center gap-2"><label class="px-3 py-2 text-[11px] text-center border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-blue-400 transition text-zinc-400"><input type="file" accept="video/mp4,video/webm" class="si-vid-upload hidden">Upload Video</label></div>';
        h += '<input type="text" class="si-vid-url ' + iCls + '" value="' + esc(video) + '" placeholder="Video URL (mp4, YouTube, Vimeo)">';
        h += '</div>';

        h += '<input type="hidden" class="si-img-val" value="' + esc(img) + '">';
        h += '<input type="hidden" class="si-vid-val" value="' + esc(video) + '">';
        h += '<input type="hidden" class="si-media-type" value="' + mediaType + '">';

        h += i18nField('title', 'Title', item.title, 'text');
        h += i18nField('subtitle', 'Subtitle', item.subtitle, 'text');
        h += '<div class="grid grid-cols-2 gap-2 mt-1">';
        h += '<div>' + i18nField('btn_text', 'Button', item.btn_text, 'text') + '</div>';
        h += '<div><label class="text-[10px] text-zinc-400">Button URL</label><input type="text" class="si-btn-url ' + iCls + '" value="' + esc(item.btn_url || '') + '" placeholder="https://..."></div>';
        h += '</div>';
        h += '</div>'; // slide 섹션 end

        // ── 사이드 네비게이션 섹션 ──
        h += '<div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800">';
        h += '<p class="text-[9px] font-bold text-emerald-500 uppercase mb-2">Side Nav</p>';
        h += i18nField('nav_title', 'Nav Title', item.nav_title, 'text');
        h += i18nField('nav_subtitle', 'Nav Subtitle', item.nav_subtitle, 'text');
        h += '<div class="mt-1"><label class="text-[10px] text-zinc-400">Nav URL</label><input type="text" class="si-nav-url ' + iCls + '" value="' + esc(item.nav_url || '') + '" placeholder="/booking"></div>';
        h += '</div>'; // nav 섹션 end

        h += '</div>';
        return h;
    }

    // ===== 이벤트 =====
    function bindAll() {
        document.querySelectorAll('.slider-i18n-toggle').forEach(function(b) {
            b.onclick = function() { b.closest('.slider-i18n-wrap').querySelector('.slider-i18n-expanded').classList.toggle('hidden'); };
        });
        document.querySelectorAll('.slider-item-remove').forEach(function(b) {
            b.onclick = function() { b.closest('.slider-item').remove(); reindex(); };
        });
        // 미디어 타입 전환
        document.querySelectorAll('.si-media-btn').forEach(function(b) {
            b.onclick = function() {
                var item = b.closest('.slider-item'), mt = b.dataset.mtype;
                item.querySelector('.si-media-type').value = mt;
                item.querySelectorAll('.si-media-btn').forEach(function(x) {
                    var on = x.dataset.mtype === mt;
                    x.className = x.className.replace(/bg-blue-600 text-white border-blue-600|border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-600/g, '');
                    x.className += on ? ' bg-blue-600 text-white border-blue-600' : ' border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-600';
                });
                item.querySelector('.si-media-image').classList.toggle('hidden', mt !== 'image');
                item.querySelector('.si-media-video').classList.toggle('hidden', mt !== 'video');
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
                        var wrap = inp.closest('.si-img-wrap'), img = wrap.querySelector('img');
                        if (!img) { img = document.createElement('img'); img.className='w-24 h-14 object-cover rounded-lg border dark:border-zinc-600'; wrap.insertBefore(img,wrap.firstChild); }
                        img.src = d.url;
                        wrap.closest('.slider-item').querySelector('.si-img-val').value = d.url;
                        lbl.textContent = 'Change'; lbl.appendChild(inp);
                    } else { lbl.textContent = '+ Image'; lbl.appendChild(inp); }
                }).catch(function(){ lbl.textContent = '+ Image'; lbl.appendChild(inp); });
            };
        });
        // 비디오 업로드
        document.querySelectorAll('.si-vid-upload').forEach(function(inp) {
            inp.onchange = function() {
                if (!inp.files[0]) return;
                var fd = new FormData(); fd.append('action','upload_widget_video'); fd.append('video',inp.files[0]);
                var lbl = inp.closest('label'); lbl.textContent = '...'; lbl.appendChild(inp);
                fetch(window.location.href, {method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d) {
                    if (d.success && d.url) {
                        var item = inp.closest('.slider-item');
                        item.querySelector('.si-vid-val').value = d.url;
                        item.querySelector('.si-vid-url').value = d.url;
                        lbl.textContent = 'Uploaded'; lbl.appendChild(inp);
                    } else { lbl.textContent = 'Upload Video'; lbl.appendChild(inp); }
                }).catch(function(){ lbl.textContent = 'Upload Video'; lbl.appendChild(inp); });
            };
        });
        // 비디오 URL 동기화
        document.querySelectorAll('.si-vid-url').forEach(function(inp) {
            inp.onchange = function() { inp.closest('.slider-item').querySelector('.si-vid-val').value = inp.value; };
        });
    }

    function reindex() {
        document.querySelectorAll('.slider-item').forEach(function(el,i) {
            el.dataset.idx = i;
            var l = el.querySelector('.slider-item-label'); if (l) l.textContent = '#' + (i+1);
        });
    }

    E.bindSliderItemsEvents = function() {
        document.getElementById('btnAddSliderItem')?.addEventListener('click', function() {
            var items = E.editTempConfig.items || [];
            if (typeof items === 'object' && !Array.isArray(items)) items = Object.values(items);
            items.push({image:'',video:'',title:{},subtitle:{},btn_text:{},btn_url:'',nav_title:{},nav_subtitle:{},nav_url:''});
            E.editTempConfig.items = items;
            document.getElementById('sliderItemsList')?.insertAdjacentHTML('beforeend', itemCard(items[items.length-1], items.length-1));
            bindAll();
        });
        bindAll();
    };

    // ===== 저장 =====
    E.saveSliderItemsToTemp = function() {
        var items = [];
        document.querySelectorAll('.slider-item').forEach(function(el) {
            var mt = el.querySelector('.si-media-type').value || 'image';
            var wraps = el.querySelectorAll('.slider-i18n-wrap');
            // wraps 순서: 0=title, 1=subtitle, 2=btn_text, 3=nav_title, 4=nav_subtitle
            items.push({
                image: mt === 'image' ? (el.querySelector('.si-img-val').value || '') : '',
                video: mt === 'video' ? (el.querySelector('.si-vid-val').value || '') : '',
                title: wraps[0] ? collectI18n(wraps[0]) : {},
                subtitle: wraps[1] ? collectI18n(wraps[1]) : {},
                btn_text: wraps[2] ? collectI18n(wraps[2]) : {},
                btn_url: el.querySelector('.si-btn-url')?.value || '',
                nav_title: wraps[3] ? collectI18n(wraps[3]) : {},
                nav_subtitle: wraps[4] ? collectI18n(wraps[4]) : {},
                nav_url: el.querySelector('.si-nav-url')?.value || ''
            });
        });
        E.editTempConfig.items = items;
    };

    console.log('[WYSIWYG] Hero slider editor v1.1 (unified) ready');
})();
</script>
