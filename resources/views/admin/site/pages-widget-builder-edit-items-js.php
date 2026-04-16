<?php
/**
 * RezlyX Admin - 위젯 편집 아이템 JS
 * Hero Images 관리 + Buttons 관리
 * WBEdit 객체를 통해 코어 편집 JS와 연결
 */
?>
<script>
(function() {
    'use strict';
    var E = WBEdit;
    var esc = E.esc;

    // ===== 히어로 이미지 =====
    var posOpts = [
        {v:'top-left',l:'↖ Top Left'},{v:'top-center',l:'↑ Top Center'},{v:'top-right',l:'↗ Top Right'},
        {v:'center-left',l:'← Center Left'},{v:'center',l:'● Center'},{v:'center-right',l:'→ Center Right'},
        {v:'bottom-left',l:'↙ Bottom Left'},{v:'bottom-center',l:'↓ Bottom Center'},{v:'bottom-right',l:'↘ Bottom Right'}
    ];
    var sizeOpts = [{v:'small',l:'Small'},{v:'medium',l:'Medium'},{v:'large',l:'Large'},{v:'full',l:'Full Width'}];

    E.renderHeroImagesSection = function() {
        var images = E.editTempConfig.hero_images || [];
        var h = '<div class="border-t border-zinc-200 dark:border-zinc-700 mt-4 pt-4">';
        h += '<div class="flex items-center justify-between mb-3"><p class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Hero Images</p>';
        h += '<button type="button" id="btnAddHeroImg" class="px-2 py-1 bg-blue-600 text-white text-[11px] rounded-lg hover:bg-blue-700 transition flex items-center"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add</button></div>';
        h += '<div id="heroImagesList" class="space-y-2">';
        images.forEach(function(img, i) { h += hiItem(img, i); });
        return h + '</div></div>';
    };

    function hiItem(img, idx) {
        var url = img.url || '', pos = img.position || 'center', layer = img.layer !== undefined ? img.layer : 1, size = img.size || 'medium';
        var h = '<div class="hi-item p-2.5 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg border border-zinc-200 dark:border-zinc-600" data-hi-idx="' + idx + '">';
        h += '<div class="flex items-center justify-between mb-2"><span class="text-[11px] font-medium text-zinc-500">Image ' + (idx + 1) + '</span>';
        h += '<button type="button" class="hi-remove p-0.5 text-red-400 hover:text-red-600 transition"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></div>';
        // 업로드
        h += '<div class="hi-upload-wrap mb-2" data-hi-idx="' + idx + '">';
        if (url) h += '<div class="relative mb-1.5 inline-block"><img src="' + esc(url) + '" class="h-16 rounded border border-zinc-200 dark:border-zinc-600 object-cover"><button type="button" class="hi-img-remove absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-[10px] flex items-center justify-center hover:bg-red-600">&times;</button></div>';
        h += '<div class="flex gap-1.5"><label class="px-2 py-1 bg-blue-600 text-white text-[10px] rounded cursor-pointer hover:bg-blue-700 inline-flex items-center"><svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>Upload<input type="file" class="hi-file-input hidden" accept="image/*"></label>';
        h += '<input type="text" class="hi-url flex-1 px-2 py-1 border border-zinc-200 dark:border-zinc-600 rounded text-[10px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" value="' + esc(url) + '" placeholder="Image URL"></div></div>';
        // 위치
        h += '<div class="mb-1.5"><label class="text-[10px] text-zinc-400 mb-0.5 block">Position</label><select class="hi-position w-full px-2 py-1 border border-zinc-200 dark:border-zinc-600 rounded text-[10px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">';
        posOpts.forEach(function(o) { h += '<option value="' + o.v + '"' + (o.v === pos ? ' selected' : '') + '>' + o.l + '</option>'; });
        h += '</select></div>';
        // 사이즈 + 레이어
        h += '<div class="flex gap-2"><div class="flex-1"><label class="text-[10px] text-zinc-400 mb-0.5 block">Size</label><select class="hi-size w-full px-2 py-1 border border-zinc-200 dark:border-zinc-600 rounded text-[10px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">';
        sizeOpts.forEach(function(o) { h += '<option value="' + o.v + '"' + (o.v === size ? ' selected' : '') + '>' + o.l + '</option>'; });
        h += '</select></div><div class="w-16"><label class="text-[10px] text-zinc-400 mb-0.5 block">Layer</label>';
        h += '<input type="number" class="hi-layer w-full px-2 py-1 border border-zinc-200 dark:border-zinc-600 rounded text-[10px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-center" value="' + layer + '" min="0" max="20"></div></div>';
        return h + '</div>';
    }

    E.bindHeroImagesEvents = function() {
        var addBtn = document.getElementById('btnAddHeroImg');
        if (addBtn) addBtn.addEventListener('click', function() {
            if (!E.editTempConfig.hero_images) E.editTempConfig.hero_images = [];
            E.editTempConfig.hero_images.push({ url: '', position: 'center', layer: 1, size: 'medium' });
            refreshHi();
        });
        bindHiRemove(); bindHiUploads();
    };

    function refreshHi() {
        E.saveHeroImagesToTemp();
        var list = document.getElementById('heroImagesList'); list.innerHTML = '';
        (E.editTempConfig.hero_images || []).forEach(function(img, i) { list.insertAdjacentHTML('beforeend', hiItem(img, i)); });
        bindHiRemove(); bindHiUploads();
    }

    function bindHiRemove() {
        E.editPanel.querySelectorAll('.hi-remove').forEach(function(btn) {
            btn.onclick = function() {
                var idx = parseInt(btn.closest('.hi-item').dataset.hiIdx);
                E.saveHeroImagesToTemp(); E.editTempConfig.hero_images.splice(idx, 1); refreshHi();
            };
        });
    }

    function bindHiUploads() {
        E.editPanel.querySelectorAll('.hi-upload-wrap').forEach(function(wrap) {
            var idx = parseInt(wrap.dataset.hiIdx);
            var fi = wrap.querySelector('.hi-file-input'), rb = wrap.querySelector('.hi-img-remove');
            if (fi) fi.addEventListener('change', function() {
                if (!this.files[0]) return;
                var fd = new FormData(); fd.append('action', 'upload_widget_image'); fd.append('image', this.files[0]);
                fetch(window.location.href, { method: 'POST', body: fd }).then(function(r) { return r.json(); }).then(function(d) {
                    if (d.success && d.url) { E.saveHeroImagesToTemp(); E.editTempConfig.hero_images[idx].url = d.url; refreshHi(); }
                }).catch(function(e) { console.error('[WYSIWYG] Upload error:', e); });
            });
            if (rb) rb.addEventListener('click', function() { E.saveHeroImagesToTemp(); E.editTempConfig.hero_images[idx].url = ''; refreshHi(); });
        });
    }

    E.saveHeroImagesToTemp = function() {
        var items = E.editPanel.querySelectorAll('.hi-item');
        if (items.length === 0) return;
        if (!E.editTempConfig.hero_images) E.editTempConfig.hero_images = [];
        items.forEach(function(item, idx) {
            if (!E.editTempConfig.hero_images[idx]) E.editTempConfig.hero_images[idx] = { url: '', position: 'center', layer: 1, size: 'medium' };
            E.editTempConfig.hero_images[idx].url = item.querySelector('.hi-url').value;
            E.editTempConfig.hero_images[idx].position = item.querySelector('.hi-position').value;
            E.editTempConfig.hero_images[idx].size = item.querySelector('.hi-size').value;
            E.editTempConfig.hero_images[idx].layer = parseInt(item.querySelector('.hi-layer').value) || 1;
        });
    };

    // ===== 버튼 =====
    E.renderButtonsSection = function() {
        var buttons = E.editTempConfig.buttons || [];
        var h = '<div class="border-t border-zinc-200 dark:border-zinc-700 mt-4 pt-4">';
        h += '<div class="flex items-center justify-between mb-3"><p class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Buttons</p>';
        h += '<button type="button" id="btnAddButton" class="px-2 py-1 bg-blue-600 text-white text-[11px] rounded-lg hover:bg-blue-700 transition flex items-center"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add</button></div>';
        h += '<div id="buttonsList" class="space-y-2">';
        buttons.forEach(function(btn, i) { h += btnItem(btn, i); });
        return h + '</div></div>';
    };

    function btnItem(btn, idx) {
        var tv = '';
        if (btn.text) tv = typeof btn.text === 'object' ? (btn.text[currentLocale] || btn.text['ko'] || btn.text['en'] || '') : btn.text;
        var style = btn.style || 'primary';
        var h = '<div class="btn-item p-2.5 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg border border-zinc-200 dark:border-zinc-600" data-btn-idx="' + idx + '">';
        h += '<div class="flex items-center justify-between mb-1.5"><span class="text-[11px] font-medium text-zinc-500">Button ' + (idx + 1) + '</span><div class="flex items-center gap-1">';
        h += '<button type="button" class="btn-i18n-toggle p-0.5 text-zinc-400 hover:text-blue-500 rounded transition" title="' + translations.multilang + '"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg></button>';
        h += '<button type="button" class="btn-remove-item p-0.5 text-red-400 hover:text-red-600 transition"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></div></div>';
        h += '<input type="text" class="btn-text w-full px-2 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded text-[11px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white mb-1.5" data-lang="' + currentLocale + '" value="' + esc(tv) + '" placeholder="Text (' + (langNames[currentLocale] || currentLocale) + ')">';
        h += '<div class="btn-i18n-expanded hidden space-y-1 mb-1.5 pl-2 border-l-2 border-blue-200 dark:border-blue-800">';
        supportedLangs.forEach(function(lang) {
            if (lang === currentLocale) return;
            var lv = (btn.text && typeof btn.text === 'object') ? (btn.text[lang] || '') : '';
            h += '<div><label class="text-[10px] text-zinc-400">' + esc(langNames[lang] || lang) + '</label><input type="text" class="btn-text-extra w-full px-2 py-1 border border-zinc-200 dark:border-zinc-600 rounded text-[10px] bg-zinc-50 dark:bg-zinc-700/50 text-zinc-900 dark:text-white" data-lang="' + lang + '" value="' + esc(lv) + '" placeholder="(' + esc(langNames[lang] || lang) + ')"></div>';
        });
        h += '</div>';
        h += '<input type="text" class="btn-url w-full px-2 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded text-[11px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white mb-1.5" value="' + esc(btn.url || '') + '" placeholder="URL">';
        h += '<select class="btn-style w-full px-2 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded text-[11px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">';
        ['primary','secondary','outline'].forEach(function(s) { h += '<option value="' + s + '"' + (style === s ? ' selected' : '') + '>' + s.charAt(0).toUpperCase() + s.slice(1) + '</option>'; });
        return h + '</select></div>';
    }

    E.bindButtonsEvents = function() {
        var addBtn = document.getElementById('btnAddButton');
        if (addBtn) addBtn.addEventListener('click', function() {
            if (!E.editTempConfig.buttons) E.editTempConfig.buttons = [];
            E.editTempConfig.buttons.push({ text: {}, url: '', style: 'primary' });
            var list = document.getElementById('buttonsList');
            list.insertAdjacentHTML('beforeend', btnItem(E.editTempConfig.buttons[E.editTempConfig.buttons.length - 1], E.editTempConfig.buttons.length - 1));
            bindBtnRemove(); bindBtnI18n();
        });
        bindBtnRemove(); bindBtnI18n();
    };

    function bindBtnI18n() {
        E.editPanel.querySelectorAll('.btn-i18n-toggle').forEach(function(btn) {
            btn.onclick = function() {
                var exp = btn.closest('.btn-item').querySelector('.btn-i18n-expanded');
                var h = exp.classList.contains('hidden'); exp.classList.toggle('hidden');
                btn.classList.toggle('text-blue-500', h); btn.classList.toggle('text-zinc-400', !h);
            };
        });
    }

    function bindBtnRemove() {
        E.editPanel.querySelectorAll('.btn-remove-item').forEach(function(btn) {
            btn.onclick = function() {
                var idx = parseInt(btn.closest('.btn-item').dataset.btnIdx);
                E.saveButtonsToTemp(); E.editTempConfig.buttons.splice(idx, 1);
                var list = document.getElementById('buttonsList'); list.innerHTML = '';
                (E.editTempConfig.buttons || []).forEach(function(b, i) { list.insertAdjacentHTML('beforeend', btnItem(b, i)); });
                bindBtnRemove(); bindBtnI18n();
            };
        });
    }

    E.saveButtonsToTemp = function() {
        var items = E.editPanel.querySelectorAll('.btn-item');
        if (items.length === 0) return;
        if (!E.editTempConfig.buttons) E.editTempConfig.buttons = [];
        items.forEach(function(item, idx) {
            if (!E.editTempConfig.buttons[idx]) E.editTempConfig.buttons[idx] = { text: {}, url: '', style: 'primary' };
            var mt = item.querySelector('.btn-text');
            if (typeof E.editTempConfig.buttons[idx].text !== 'object' || E.editTempConfig.buttons[idx].text === null) E.editTempConfig.buttons[idx].text = {};
            E.editTempConfig.buttons[idx].text[currentLocale] = mt.value;
            item.querySelectorAll('.btn-text-extra').forEach(function(input) { E.editTempConfig.buttons[idx].text[input.dataset.lang] = input.value; });
            E.editTempConfig.buttons[idx].url = item.querySelector('.btn-url').value;
            E.editTempConfig.buttons[idx].style = item.querySelector('.btn-style').value;
        });
    };

    // ===== 비디오 업로드 =====
    E.bindVideoUploads = function() {
        E.editPanel.querySelectorAll('.video-upload-wrap').forEach(function(wrap) {
            var key = wrap.dataset.key;
            var fileInput = wrap.querySelector('.vid-file-input');
            var removeBtn = wrap.querySelector('.vid-remove');

            // 파일 업로드
            if (fileInput) fileInput.addEventListener('change', function() {
                if (!this.files[0]) return;
                var file = this.files[0];
                var fd = new FormData();
                fd.append('action', 'upload_widget_video');
                fd.append('video', file);
                console.log('[WYSIWYG] Uploading video for:', key);

                // 업로드 중 표시
                var uploadSection = wrap.querySelector('.vid-upload-section');
                var origLabel = uploadSection ? uploadSection.querySelector('label') : null;
                if (origLabel) origLabel.innerHTML = '<svg class="animate-spin w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span class="text-[11px] text-zinc-600 dark:text-zinc-400">Uploading...</span>';
                E.showUploadStatus(wrap, 'loading', 'Uploading ' + file.name + '...');

                fetch(window.location.href, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success && data.url) {
                        E.editTempConfig[key] = data.url;
                        wrap.dataset.uploadedUrl = data.url;
                        var urlInput = wrap.querySelector('.edit-field[data-key="' + key + '"]');
                        if (urlInput) urlInput.value = data.url;
                        updateVideoPreview(wrap, data.url);
                        E.showUploadStatus(wrap, 'success', 'Upload complete');
                        console.log('[WYSIWYG] Video uploaded (priority over URL):', data.url);
                    } else {
                        console.error('[WYSIWYG] Video upload failed:', data.message);
                        E.showUploadStatus(wrap, 'error', 'Upload failed: ' + (data.message || 'Unknown error'));
                    }
                    restoreUploadLabel(origLabel);
                })
                .catch(function(err) {
                    console.error('[WYSIWYG] Video upload error:', err);
                    E.showUploadStatus(wrap, 'error', 'Upload error: ' + err.message);
                    restoreUploadLabel(origLabel);
                });
            });

            // 삭제 버튼
            if (removeBtn) removeBtn.addEventListener('click', function() {
                E.editTempConfig[key] = '';
                delete wrap.dataset.uploadedUrl; // 업로드 우선 마킹 제거
                var urlInput = wrap.querySelector('.edit-field[data-key="' + key + '"]');
                if (urlInput) urlInput.value = '';
                var preview = wrap.querySelector('.vid-preview');
                if (preview) preview.remove();
                console.log('[WYSIWYG] Video removed for:', key);
            });
        });
    };

    function updateVideoPreview(wrap, url) {
        var existing = wrap.querySelector('.vid-preview');
        if (existing) existing.remove();
        var p = document.createElement('div');
        p.className = 'vid-preview relative mb-1';
        p.innerHTML = '<video src="' + esc(url) + '" class="h-24 rounded-lg border border-zinc-200 dark:border-zinc-600 object-cover" muted autoplay loop playsinline></video>' +
            '<button type="button" class="vid-remove absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center hover:bg-red-600">&times;</button>';
        wrap.insertBefore(p, wrap.firstChild);
        E.bindVideoUploads(); // 새 삭제 버튼 바인딩
    }

    function restoreUploadLabel(label) {
        if (!label) return;
        label.innerHTML = '<svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>' +
            '<span class="text-[11px] text-zinc-600 dark:text-zinc-400">Upload Video</span>' +
            '<input type="file" class="vid-file-input hidden" accept="video/mp4,video/webm,video/ogg">';
        E.bindVideoUploads(); // 새 file input 바인딩
    }

    console.log('[WYSIWYG] Edit items (hero images + buttons + video) ready');
})();
</script>
