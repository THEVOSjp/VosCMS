<?php
/**
 * RezlyX Admin - 위젯 편집 사이드 패널 JS (코어)
 * i18n 필드, 단일 필드, 이미지 업로드, 저장/닫기
 * hero_images + buttons는 edit-items-js.php에서 처리
 */
?>
<script>
var WBEdit = (function() {
    'use strict';
    var editPanel = document.getElementById('editSidePanel');
    var editLangTabsWrap = document.getElementById('editLangTabs').parentElement;
    var editPanelFields = document.getElementById('editPanelFields');
    var editPanelTitle = document.getElementById('editPanelTitle');
    var editBlock = null;
    var editTempConfig = {};
    var esc = WB.escapeHtml;
    var currentLocale = '<?= $currentLocale ?? "ko" ?>';
    // 다국어 라벨 처리: 객체이면 현재 로케일 → en → 첫 번째 값
    function getLabel(label, fallback) {
        if (!label) return fallback || '';
        if (typeof label === 'string') return label;
        if (typeof label === 'object') return label[currentLocale] || label['en'] || Object.values(label)[0] || fallback || '';
        return String(label);
    }
    var globeSvg = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>';
    var iCls = 'edit-field i18n-extra w-full px-2.5 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded text-[11px] bg-zinc-50 dark:bg-zinc-700/50 text-zinc-900 dark:text-white';
    var fCls = 'edit-field w-full px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg text-xs bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white';

    editLangTabsWrap.classList.add('hidden');
    WB.openEditModal = openEditPanel;

    function openEditPanel(block) {
        editBlock = block;
        var schema = {}; try { schema = JSON.parse(block.dataset.configSchema || '{}'); } catch(e) {}
        var config = {}; try { config = JSON.parse(block.dataset.config || '{}'); } catch(e) {}
        editTempConfig = JSON.parse(JSON.stringify(config));
        var fields = schema.fields || [];
        if (fields.length === 0) { WB.showStatus('error', translations.no_config); return; }
        var nameEl = block.querySelector('.widget-toolbar span.text-xs:first-of-type');
        editPanelTitle.textContent = nameEl ? nameEl.textContent.trim() : block.dataset.widgetSlug;
        renderEditFields(fields);
        showEditPanel();
        highlightBlock(block);
        console.log('[WYSIWYG] Edit panel opened:', block.dataset.widgetSlug);
    }

    function showEditPanel() {
        editPanel.classList.remove('hidden');
        editPanel.classList.add('flex');
    }

    function hideEditPanel() {
        destroyRichtextEditors();
        editPanel.classList.add('hidden');
        editPanel.classList.remove('flex');
        clearHighlight();
        editBlock = null;
        editTempConfig = {};
    }

    function highlightBlock(block) {
        WB.canvas.querySelectorAll('.widget-block').forEach(function(b) {
            b.querySelector('.widget-border').classList.remove('border-blue-500');
            b.querySelector('.widget-border').classList.add('border-transparent');
        });
        block.querySelector('.widget-border').classList.remove('border-transparent');
        block.querySelector('.widget-border').classList.add('border-blue-500');
    }

    function clearHighlight() {
        if (editBlock) {
            editBlock.querySelector('.widget-border').classList.remove('border-blue-500');
            editBlock.querySelector('.widget-border').classList.add('border-transparent');
        }
    }

    // ===== 필드 렌더링 =====
    function renderEditFields(fields) {
        var html = '';
        var i18nFields = fields.filter(function(f) { return f.i18n; });
        var commonFields = fields.filter(function(f) { return !f.i18n && f.type !== 'buttons' && f.type !== 'hero_images' && f.type !== 'feature_items' && f.type !== 'service_items' && f.type !== 'hero_slides' && f.type !== 'nav_items' && f.type !== 'slider_items' && f.type !== 'grid_cells'; });
        var buttonsField = fields.find(function(f) { return f.type === 'buttons'; });
        var heroImagesField = fields.find(function(f) { return f.type === 'hero_images'; });
        var featureItemsField = fields.find(function(f) { return f.type === 'feature_items'; });
        var serviceItemsField = fields.find(function(f) { return f.type === 'service_items'; });
        var heroSlidesField = fields.find(function(f) { return f.type === 'hero_slides'; });
        var navItemsField = fields.find(function(f) { return f.type === 'nav_items'; });
        var sliderItemsField = fields.find(function(f) { return f.type === 'slider_items'; });
        var gridCellsField = fields.find(function(f) { return f.type === 'grid_cells'; });

        if (i18nFields.length > 0) {
            html += '<div class="space-y-3">';
            i18nFields.forEach(function(f) { html += renderI18nField(f); });
            html += '</div>';
        }
        if (commonFields.length > 0) {
            html += i18nFields.length > 0
                ? '<div class="border-t border-zinc-200 dark:border-zinc-700 mt-4 pt-4"><p class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 mb-3 uppercase tracking-wider">' + translations.common_fields + '</p>'
                : '<div>';
            html += '<div class="space-y-3">';
            commonFields.forEach(function(f) { html += renderSingleField(f); });
            html += '</div></div>';
        }
        // items-js에서 제공하는 렌더러 호출
        if (heroImagesField && WBEdit.renderHeroImagesSection) html += WBEdit.renderHeroImagesSection();
        if (buttonsField && WBEdit.renderButtonsSection) html += WBEdit.renderButtonsSection();
        if (featureItemsField && WBEdit.renderFeatureItemsSection) html += WBEdit.renderFeatureItemsSection();
        if (serviceItemsField && WBEdit.renderServiceItemsSection) html += WBEdit.renderServiceItemsSection();
        if (sliderItemsField && WBEdit.renderSliderItemsSection) html += WBEdit.renderSliderItemsSection();
        if (gridCellsField && WBEdit.renderGridCellsSection) html += WBEdit.renderGridCellsSection();
        else {
            if (heroSlidesField && WBEdit.renderHeroSlidesSection) html += WBEdit.renderHeroSlidesSection();
            if (navItemsField && WBEdit.renderNavItemsSection) html += WBEdit.renderNavItemsSection();
        }

        editPanelFields.innerHTML = html;
        bindImageUploads();
        if (WBEdit.bindVideoUploads) WBEdit.bindVideoUploads();
        bindRangeInputs();
        bindRichtextEditors();
        bindCodeEditors();
        bindI18nToggles();
        if (heroImagesField && WBEdit.bindHeroImagesEvents) WBEdit.bindHeroImagesEvents();
        if (buttonsField && WBEdit.bindButtonsEvents) WBEdit.bindButtonsEvents();
        if (featureItemsField && WBEdit.bindFeatureItemsEvents) WBEdit.bindFeatureItemsEvents();
        if (serviceItemsField && WBEdit.bindServiceItemsEvents) WBEdit.bindServiceItemsEvents();
        if (sliderItemsField && WBEdit.bindSliderItemsEvents) WBEdit.bindSliderItemsEvents();
        if (gridCellsField && WBEdit.bindGridCellsEvents) WBEdit.bindGridCellsEvents();
        else if ((heroSlidesField || navItemsField) && WBEdit.bindHeroSlidesEvents) WBEdit.bindHeroSlidesEvents();
    }

    // ===== i18n 필드 =====
    function renderI18nField(f) {
        var defaultVal = getI18nValue(editTempConfig, f.key, currentLocale);
        var h = '<div class="i18n-field-wrap" data-key="' + f.key + '">';
        h += '<div class="flex items-center justify-between mb-1">';
        h += '<label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-400">' + esc(getLabel(f.label, f.key)) + '</label>';
        h += '<button type="button" class="i18n-toggle p-1 text-zinc-400 hover:text-blue-500 dark:hover:text-blue-400 rounded transition" title="' + translations.multilang + '">' + globeSvg + '</button></div>';
        if (f.type === 'richtext') {
            h += '<div class="flex gap-1"><textarea class="edit-field i18n-default w-full px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg text-xs bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white resize-y richtext-i18n-src" data-key="' + f.key + '" data-lang="' + currentLocale + '" rows="3" placeholder="' + esc(getLabel(f.label, f.key)) + ' (' + (langNames[currentLocale] || currentLocale) + ')">' + esc(String(defaultVal)) + '</textarea>';
            h += '<button type="button" class="richtext-modal-btn flex-shrink-0 px-2 py-1 bg-blue-600 text-white text-[10px] rounded-lg hover:bg-blue-700 transition self-start" data-key="' + f.key + '" data-lang="' + currentLocale + '" data-label="' + esc(langNames[currentLocale] || currentLocale) + '" title="Editor"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button></div>';
        } else if (f.type === 'textarea') {
            h += '<textarea class="edit-field i18n-default w-full px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg text-xs bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 resize-y" data-key="' + f.key + '" data-lang="' + currentLocale + '" rows="3" placeholder="' + esc(getLabel(f.label, f.key)) + ' (' + (langNames[currentLocale] || currentLocale) + ')">' + esc(String(defaultVal)) + '</textarea>';
        } else {
            h += '<input type="text" class="edit-field i18n-default w-full px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg text-xs bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500" data-key="' + f.key + '" data-lang="' + currentLocale + '" value="' + esc(String(defaultVal)) + '" placeholder="' + esc(getLabel(f.label, f.key)) + ' (' + (langNames[currentLocale] || currentLocale) + ')">';
        }
        h += '<div class="i18n-expanded hidden mt-2 space-y-1.5 pl-2 border-l-2 border-blue-200 dark:border-blue-800">';
        supportedLangs.forEach(function(lang) {
            if (lang === currentLocale) return;
            var langVal = getI18nValue(editTempConfig, f.key, lang);
            var langLabel = langNames[lang] || lang;
            if (f.type === 'richtext') {
                // richtext: textarea + 편집 버튼
                h += '<div><label class="text-[10px] text-zinc-400 dark:text-zinc-500">' + esc(langLabel) + '</label>';
                h += '<div class="flex gap-1"><textarea class="' + iCls + ' resize-y flex-1 richtext-i18n-src" data-key="' + f.key + '" data-lang="' + lang + '" rows="2" placeholder="' + esc(getLabel(f.label, f.key)) + ' (' + esc(langLabel) + ')">' + esc(String(langVal)) + '</textarea>';
                h += '<button type="button" class="richtext-modal-btn flex-shrink-0 px-2 py-1 bg-blue-600 text-white text-[10px] rounded-lg hover:bg-blue-700 transition self-start" data-key="' + f.key + '" data-lang="' + lang + '" data-label="' + esc(langLabel) + '" title="Editor"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button></div></div>';
            } else if (f.type === 'textarea') {
                h += '<div><label class="text-[10px] text-zinc-400 dark:text-zinc-500">' + esc(langLabel) + '</label><textarea class="' + iCls + ' resize-y" data-key="' + f.key + '" data-lang="' + lang + '" rows="2" placeholder="' + esc(getLabel(f.label, f.key)) + ' (' + esc(langLabel) + ')">' + esc(String(langVal)) + '</textarea></div>';
            } else {
                h += '<div><label class="text-[10px] text-zinc-400 dark:text-zinc-500">' + esc(langLabel) + '</label><input type="text" class="' + iCls + '" data-key="' + f.key + '" data-lang="' + lang + '" value="' + esc(String(langVal)) + '" placeholder="' + esc(getLabel(f.label, f.key)) + ' (' + esc(langLabel) + ')"></div>';
            }
        });
        return h + '</div></div>';
    }

    function bindI18nToggles() {
        editPanelFields.querySelectorAll('.i18n-toggle').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var wrap = btn.closest('.i18n-field-wrap');
                var expanded = wrap.querySelector('.i18n-expanded');
                var isHidden = expanded.classList.contains('hidden');
                expanded.classList.toggle('hidden');
                btn.classList.toggle('text-blue-500', isHidden);
                btn.classList.toggle('text-zinc-400', !isHidden);
            });
        });
    }

    // ===== 단일 필드 =====
    function renderSingleField(f) {
        var val = editTempConfig[f.key] !== undefined ? editTempConfig[f.key] : (f.default !== undefined ? f.default : '');
        var h = '<div><label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-400 mb-1">' + esc(getLabel(f.label, f.key)) + '</label>';
        switch (f.type) {
            case 'number': h += '<input type="number" class="edit-field w-full px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg text-xs bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" data-key="' + f.key + '" value="' + esc(String(val)) + '">'; break;
            case 'color': h += '<div class="flex items-center gap-2"><input type="color" class="edit-field w-10 h-8 rounded-lg cursor-pointer border border-zinc-200 dark:border-zinc-600" data-key="' + f.key + '" value="' + esc(String(val || '#000000')) + '"><span class="color-label text-xs text-zinc-500">' + esc(String(val || '#000000')) + '</span></div>'; break;
            case 'color_transparent':
                var isTransparent = (!val || val === 'transparent');
                h += '<div class="flex items-center gap-2">';
                h += '<label class="flex items-center gap-1.5 cursor-pointer"><input type="checkbox" class="ct-transparent-chk rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500" data-key="' + f.key + '"' + (isTransparent ? ' checked' : '') + '><span class="text-[11px] text-zinc-500">Transparent</span></label>';
                h += '<input type="color" class="ct-color-pick w-8 h-7 rounded cursor-pointer border border-zinc-200 dark:border-zinc-600' + (isTransparent ? ' opacity-30 pointer-events-none' : '') + '" data-key="' + f.key + '" value="' + esc(isTransparent ? '#f9fafb' : String(val)) + '">';
                h += '<input type="hidden" class="edit-field" data-key="' + f.key + '" value="' + esc(String(val || 'transparent')) + '">';
                h += '</div>'; break;
            case 'toggle': var chk = (val === 1 || val === '1' || val === true) ? 'checked' : ''; h += '<label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" class="edit-field sr-only peer" data-key="' + f.key + '" ' + chk + '><div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div></label>'; break;
            case 'select': h += '<select class="edit-field w-full px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg text-xs bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" data-key="' + f.key + '">'; (f.options || []).forEach(function(o) { h += '<option value="' + esc(String(o.value)) + '"' + (String(o.value) === String(val) ? ' selected' : '') + '>' + esc(o.label) + '</option>'; }); h += '</select>'; break;
            case 'board_select':
                var boardList = window._boardList || [];
                h += '<select class="edit-field w-full px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg text-xs bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" data-key="' + f.key + '">';
                h += '<option value="">-- Select Board --</option>';
                boardList.forEach(function(b) { h += '<option value="' + esc(b.slug) + '"' + (b.slug === String(val) ? ' selected' : '') + '>' + esc(b.title) + ' (' + esc(b.slug) + ')</option>'; });
                h += '</select>';
                break;
            case 'image': var imgUrl = String(val || ''); h += '<div class="image-upload-wrap" data-key="' + f.key + '">'; if (imgUrl) h += '<div class="img-preview-wrap relative mb-2 inline-block"><img src="' + esc(imgUrl) + '" class="h-20 rounded-lg object-cover border border-zinc-200 dark:border-zinc-600"><button type="button" class="img-remove absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center hover:bg-red-600">&times;</button></div>'; h += '<div class="flex gap-2"><label class="px-2.5 py-1.5 bg-blue-600 text-white text-[11px] rounded-lg cursor-pointer hover:bg-blue-700 transition inline-flex items-center"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>Upload<input type="file" class="img-file-input hidden" accept="image/*"></label><input type="text" class="edit-field flex-1 px-2 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded-lg text-[11px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" data-key="' + f.key + '" value="' + esc(imgUrl) + '" placeholder="or paste URL"></div></div>'; break;
            case 'video':
                var vidUrl = String(val || '');
                var isUrl = vidUrl && (vidUrl.startsWith('http') || vidUrl.startsWith('//'));
                var isFile = vidUrl && !isUrl;
                h += '<div class="video-upload-wrap space-y-3" data-key="' + f.key + '">';
                // 프리뷰
                if (vidUrl) {
                    h += '<div class="vid-preview relative mb-1">';
                    if (isUrl && (vidUrl.indexOf('youtube.com') > -1 || vidUrl.indexOf('youtu.be') > -1)) {
                        var ytMatch = vidUrl.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
                        if (ytMatch) h += '<img src="https://img.youtube.com/vi/' + esc(ytMatch[1]) + '/mqdefault.jpg" class="h-24 rounded-lg border border-zinc-200 dark:border-zinc-600 object-cover">';
                    } else if (isUrl && vidUrl.indexOf('vimeo.com') > -1) {
                        h += '<div class="h-24 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center text-xs text-zinc-500">Vimeo</div>';
                    } else {
                        h += '<video src="' + esc(vidUrl) + '" class="h-24 rounded-lg border border-zinc-200 dark:border-zinc-600 object-cover" muted autoplay loop playsinline></video>';
                    }
                    h += '<button type="button" class="vid-remove absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center hover:bg-red-600">&times;</button></div>';
                }
                // 파일 업로드 영역
                h += '<div class="vid-upload-section"><p class="text-[10px] text-zinc-400 dark:text-zinc-500 mb-1">File Upload (mp4, webm, ogg)</p>';
                h += '<label class="flex items-center justify-center gap-2 w-full px-3 py-2.5 border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-purple-400 dark:hover:border-purple-500 hover:bg-purple-50 dark:hover:bg-purple-900/10 transition">';
                h += '<svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>';
                h += '<span class="text-[11px] text-zinc-600 dark:text-zinc-400">Upload Video</span>';
                h += '<input type="file" class="vid-file-input hidden" accept="video/mp4,video/webm,video/ogg"></label></div>';
                // URL 입력 영역
                h += '<div class="vid-url-section"><p class="text-[10px] text-zinc-400 dark:text-zinc-500 mb-1">Video URL (YouTube, Vimeo, direct link)</p>';
                h += '<div class="flex gap-2"><input type="text" class="edit-field flex-1 px-2.5 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg text-[11px] bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" data-key="' + f.key + '" value="' + esc(vidUrl) + '" placeholder="https://www.youtube.com/watch?v=..."></div></div>';
                h += '</div>';
                break;
            case 'range': var mn = f.min !== undefined ? f.min : 0, mx = f.max !== undefined ? f.max : 100; h += '<div class="flex items-center gap-3"><input type="range" class="edit-field range-input flex-1 h-2 bg-zinc-200 dark:bg-zinc-600 rounded-lg appearance-none cursor-pointer accent-blue-600" data-key="' + f.key + '" min="' + mn + '" max="' + mx + '" value="' + esc(String(val)) + '"><span class="range-val text-xs text-zinc-500 w-10 text-right">' + esc(String(val)) + '%</span></div>'; break;
            case 'richtext':
                h += '<div class="flex gap-1"><textarea class="edit-field w-full px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg text-xs bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white resize-y richtext-i18n-src" data-key="' + f.key + '" rows="3">' + esc(typeof val === 'string' ? val : '') + '</textarea>';
                h += '<button type="button" class="richtext-modal-btn flex-shrink-0 px-2 py-1 bg-blue-600 text-white text-[10px] rounded-lg hover:bg-blue-700 transition self-start" data-key="' + f.key + '" data-label="' + esc(getLabel(f.label, f.key)) + '" title="Editor"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button></div>';
                break;
            case 'code':
                var codeLang = f.lang || 'text';
                var langIcon = codeLang === 'css' ? '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>' : '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>';
                var langBadgeColor = codeLang === 'css' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
                h += '<div class="flex items-center gap-1.5 mb-1"><span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium ' + langBadgeColor + '">' + langIcon + ' ' + esc(codeLang.toUpperCase()) + '</span></div>';
                h += '<textarea class="edit-field w-full px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg text-xs bg-zinc-900 dark:bg-zinc-950 text-green-400 dark:text-green-300 resize-y" data-key="' + f.key + '" rows="6" spellcheck="false" style="font-family:\'Fira Code\',\'Cascadia Code\',\'JetBrains Mono\',Consolas,monospace;tab-size:2;line-height:1.6;">' + esc(typeof val === 'string' ? val : '') + '</textarea>';
                break;
            default: h += '<input type="text" class="edit-field w-full px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg text-xs bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" data-key="' + f.key + '" value="' + esc(String(val)) + '">'; break;
        }
        return h + '</div>';
    }

    // ===== 업로드 상태 표시 헬퍼 =====
    function showUploadStatus(wrap, type, message) {
        var existing = wrap.querySelector('.upload-status');
        if (existing) existing.remove();
        var el = document.createElement('div');
        el.className = 'upload-status flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] font-medium mt-2 ' +
            (type === 'loading' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' :
             type === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400' :
             'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400');
        if (type === 'loading') {
            el.innerHTML = '<svg class="animate-spin w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>' + esc(message);
        } else if (type === 'success') {
            el.innerHTML = '<svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' + esc(message);
            setTimeout(function() { if (el.parentNode) el.remove(); }, 3000);
        } else {
            el.innerHTML = '<svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' + esc(message);
            setTimeout(function() { if (el.parentNode) el.remove(); }, 5000);
        }
        wrap.appendChild(el);
    }

    // ===== 이미지 업로드 / 범위 =====
    function bindImageUploads() {
        editPanel.querySelectorAll('.image-upload-wrap').forEach(function(wrap) {
            var key = wrap.dataset.key, fileInput = wrap.querySelector('.img-file-input'), removeBtn = wrap.querySelector('.img-remove');
            if (fileInput) fileInput.addEventListener('change', function() {
                if (!this.files[0]) return;
                var file = this.files[0];
                showUploadStatus(wrap, 'loading', 'Uploading ' + file.name + '...');
                var fd = new FormData(); fd.append('action', 'upload_widget_image'); fd.append('image', file);
                fetch(window.location.href, { method: 'POST', body: fd }).then(function(r) { return r.json(); }).then(function(data) {
                    if (data.success && data.url) {
                        editTempConfig[key] = data.url;
                        var urlInput = wrap.querySelector('.edit-field[data-key="' + key + '"]'); if (urlInput) urlInput.value = data.url;
                        var existing = wrap.querySelector('.img-preview-wrap');
                        if (existing) { existing.querySelector('img').src = data.url; } else {
                            var p = document.createElement('div'); p.className = 'img-preview-wrap relative mb-2 inline-block';
                            p.innerHTML = '<img src="' + esc(data.url) + '" class="h-20 rounded-lg object-cover border border-zinc-200 dark:border-zinc-600"><button type="button" class="img-remove absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center hover:bg-red-600">&times;</button>';
                            wrap.insertBefore(p, wrap.firstChild); bindImageUploads();
                        }
                        showUploadStatus(wrap, 'success', 'Upload complete');
                    } else {
                        showUploadStatus(wrap, 'error', 'Upload failed: ' + (data.message || 'Unknown error'));
                    }
                }).catch(function(err) {
                    console.error('[WYSIWYG] Upload error:', err);
                    showUploadStatus(wrap, 'error', 'Upload error: ' + err.message);
                });
            });
            if (removeBtn) removeBtn.addEventListener('click', function() {
                editTempConfig[key] = ''; var urlInput = wrap.querySelector('.edit-field[data-key="' + key + '"]'); if (urlInput) urlInput.value = '';
                var imgWrap = removeBtn.closest('.img-preview-wrap') || removeBtn.closest('.relative'); if (imgWrap) imgWrap.remove();
            });
        });
    }
    function bindRangeInputs() {
        editPanel.querySelectorAll('.range-input').forEach(function(r) { r.addEventListener('input', function() { var s = r.parentElement.querySelector('.range-val'); if (s) s.textContent = r.value + '%'; }); });
        editPanel.querySelectorAll('input[type="color"].edit-field').forEach(function(c) { c.addEventListener('input', function() { var s = c.parentElement.querySelector('.color-label'); if (s) s.textContent = c.value; }); });
        // color_transparent 바인딩
        editPanel.querySelectorAll('.ct-transparent-chk').forEach(function(chk) {
            var key = chk.dataset.key;
            var pick = chk.closest('div').querySelector('.ct-color-pick[data-key="' + key + '"]');
            var hidden = chk.closest('div').querySelector('.edit-field[data-key="' + key + '"]');
            chk.addEventListener('change', function() {
                if (chk.checked) {
                    hidden.value = 'transparent';
                    pick.classList.add('opacity-30', 'pointer-events-none');
                } else {
                    hidden.value = pick.value;
                    pick.classList.remove('opacity-30', 'pointer-events-none');
                }
            });
            pick.addEventListener('input', function() {
                if (!chk.checked) hidden.value = pick.value;
            });
        });
    }

    // ===== Code Editor (Tab 지원) =====
    function bindCodeEditors() {
        editPanel.querySelectorAll('textarea[data-key][style*="font-family"]').forEach(function(ta) {
            ta.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    e.preventDefault();
                    var start = ta.selectionStart, end = ta.selectionEnd;
                    ta.value = ta.value.substring(0, start) + '  ' + ta.value.substring(end);
                    ta.selectionStart = ta.selectionEnd = start + 2;
                }
            });
            console.log('[WYSIWYG] Code editor bound:', ta.dataset.key);
        });
    }

    // ===== Richtext 모달 편집 =====
    var richtextModal = document.getElementById('richtextModal');
    var richtextModalEditor = document.getElementById('richtextModalEditor');
    var richtextModalTitle = document.getElementById('richtextModalTitle');
    var richtextTargetTextarea = null;

    function bindRichtextEditors() {
        editPanel.querySelectorAll('.richtext-modal-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var key = btn.dataset.key;
                var lang = btn.dataset.lang || '';
                var label = btn.dataset.label || key;
                // 대응하는 textarea 찾기
                var selector = '.richtext-i18n-src[data-key="' + key + '"]';
                if (lang) selector += '[data-lang="' + lang + '"]';
                richtextTargetTextarea = editPanel.querySelector(selector);
                if (!richtextTargetTextarea) { console.warn('[WYSIWYG] No textarea found for:', key, lang); return; }

                richtextModalTitle.textContent = label + (lang ? ' (' + lang + ')' : '');
                openRichtextModal(richtextTargetTextarea.value);
                console.log('[WYSIWYG] Opening richtext modal for:', key, lang);
            });
        });
    }

    var rtInner = document.getElementById('richtextModalInner');
    var rtHeader = document.getElementById('richtextModalHeader');
    var rtResize = document.getElementById('richtextModalResize');
    var rtMaxBtn = document.getElementById('richtextModalMaximize');
    var rtSavedPos = { w: 900, h: 600, x: null, y: null };

    function openRichtextModal(html) {
        if (typeof $ === 'undefined' || typeof $.fn.summernote === 'undefined') {
            console.warn('[WYSIWYG] Summernote not loaded'); return;
        }
        richtextModal.classList.remove('hidden');
        // 초기 위치 중앙 정렬 (최대화 아닌 경우)
        if (!rtInner.classList.contains('rt-maximized')) {
            rtInner.style.position = '';
            rtInner.style.transform = '';
            rtInner.style.top = '';
            rtInner.style.left = '';
            rtInner.style.width = rtSavedPos.w + 'px';
            rtInner.style.height = rtSavedPos.h + 'px';
            rtSavedPos.x = null;
            rtSavedPos.y = null;
        }
        var $editor = $(richtextModalEditor);
        if (!$editor.hasClass('summernote-ready')) {
            $editor.summernote({
                height: 400,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video', 'hr']],
                    ['view', ['fullscreen', 'codeview']]
                ]
            });
            $editor.addClass('summernote-ready');
        }
        $editor.summernote('code', html);
        console.log('[WYSIWYG] Richtext modal opened');
    }

    function closeRichtextModal(save) {
        if (save && richtextTargetTextarea && typeof $ !== 'undefined') {
            var html = $(richtextModalEditor).summernote('code');
            richtextTargetTextarea.value = html;
            console.log('[WYSIWYG] Richtext modal saved');
        }
        richtextModal.classList.add('hidden');
        richtextTargetTextarea = null;
    }

    // ===== 전체화면 토글 =====
    function toggleMaximize() {
        if (rtInner.classList.contains('rt-maximized')) {
            rtInner.classList.remove('rt-maximized');
            rtInner.style.position = 'fixed';
            rtInner.style.width = rtSavedPos.w + 'px';
            rtInner.style.height = rtSavedPos.h + 'px';
            if (rtSavedPos.x !== null) {
                rtInner.style.left = rtSavedPos.x + 'px';
                rtInner.style.top = rtSavedPos.y + 'px';
                rtInner.style.transform = 'none';
            } else {
                rtInner.style.position = '';
                rtInner.style.transform = '';
                rtInner.style.top = '';
                rtInner.style.left = '';
            }
            console.log('[WYSIWYG] Modal restored');
        } else {
            // 현재 크기 저장
            rtSavedPos.w = rtInner.offsetWidth;
            rtSavedPos.h = rtInner.offsetHeight;
            rtInner.classList.add('rt-maximized');
            console.log('[WYSIWYG] Modal maximized');
        }
    }
    rtMaxBtn.addEventListener('click', function(e) { e.stopPropagation(); toggleMaximize(); });
    rtHeader.addEventListener('dblclick', function(e) { e.preventDefault(); toggleMaximize(); });

    // ===== 드래그 이동 =====
    (function() {
        var dragging = false, startX, startY, origX, origY;
        rtHeader.addEventListener('mousedown', function(e) {
            if (e.target.closest('button') || rtInner.classList.contains('rt-maximized')) return;
            dragging = true;
            startX = e.clientX; startY = e.clientY;
            var rect = rtInner.getBoundingClientRect();
            origX = rect.left; origY = rect.top;
            rtInner.style.position = 'fixed';
            rtInner.style.transform = 'none';
            rtInner.style.margin = '0';
            e.preventDefault();
        });
        document.addEventListener('mousemove', function(e) {
            if (!dragging) return;
            var dx = e.clientX - startX, dy = e.clientY - startY;
            var nx = origX + dx, ny = origY + dy;
            // 화면 밖으로 나가지 않도록 제한
            nx = Math.max(0, Math.min(nx, window.innerWidth - 100));
            ny = Math.max(0, Math.min(ny, window.innerHeight - 50));
            rtInner.style.left = nx + 'px';
            rtInner.style.top = ny + 'px';
            rtSavedPos.x = nx; rtSavedPos.y = ny;
        });
        document.addEventListener('mouseup', function() { dragging = false; });
    })();

    // ===== 리사이즈 =====
    (function() {
        var resizing = false, startX, startY, origW, origH;
        rtResize.addEventListener('mousedown', function(e) {
            if (rtInner.classList.contains('rt-maximized')) return;
            resizing = true;
            startX = e.clientX; startY = e.clientY;
            origW = rtInner.offsetWidth; origH = rtInner.offsetHeight;
            e.preventDefault(); e.stopPropagation();
        });
        document.addEventListener('mousemove', function(e) {
            if (!resizing) return;
            var nw = Math.max(400, origW + (e.clientX - startX));
            var nh = Math.max(300, origH + (e.clientY - startY));
            rtInner.style.width = nw + 'px';
            rtInner.style.height = nh + 'px';
            rtSavedPos.w = nw; rtSavedPos.h = nh;
        });
        document.addEventListener('mouseup', function() { resizing = false; });
    })();

    document.getElementById('richtextModalSave').addEventListener('click', function() { closeRichtextModal(true); });
    document.getElementById('richtextModalCancel').addEventListener('click', function() { closeRichtextModal(false); });
    document.getElementById('richtextModalClose').addEventListener('click', function() { closeRichtextModal(false); });
    richtextModal.addEventListener('click', function(e) { if (e.target === richtextModal) closeRichtextModal(false); });

    function destroyRichtextEditors() {
        // 모달 Summernote는 유지 (재사용)
    }

    // ===== 헬퍼 =====
    function getI18nValue(config, key, lang) {
        var val = config[key]; if (val === undefined || val === null) return '';
        if (typeof val === 'object' && !Array.isArray(val)) return val[lang] || '';
        if (typeof val === 'string' && lang === currentLocale) return val;
        if (typeof val === 'string') return ''; return String(val);
    }

    function saveEditFieldsToTemp() {
        editPanelFields.querySelectorAll('.i18n-field-wrap').forEach(function(wrap) {
            var key = wrap.dataset.key;
            if (typeof editTempConfig[key] !== 'object' || Array.isArray(editTempConfig[key]) || editTempConfig[key] === null) {
                var old = editTempConfig[key] || ''; editTempConfig[key] = {};
                if (typeof old === 'string' && old) editTempConfig[key][currentLocale] = old;
            }
            wrap.querySelectorAll('.edit-field[data-lang]').forEach(function(field) { editTempConfig[key][field.dataset.lang] = field.value; });
        });
        editPanelFields.querySelectorAll('.edit-field:not([data-lang])').forEach(function(field) {
            var key = field.dataset.key; if (!key) return;
            // 비디오 필드: 업로드된 파일이 있으면 URL 입력보다 우선
            var videoWrap = field.closest('.video-upload-wrap');
            if (videoWrap && videoWrap.dataset.uploadedUrl) {
                editTempConfig[key] = videoWrap.dataset.uploadedUrl;
                return;
            }
            if (field.type === 'checkbox') editTempConfig[key] = field.checked ? 1 : 0;
            else if (field.type === 'number' || field.type === 'range') editTempConfig[key] = field.value ? Number(field.value) : 0;
            else editTempConfig[key] = field.value;
        });
        if (WBEdit.saveButtonsToTemp) WBEdit.saveButtonsToTemp();
        if (WBEdit.saveHeroImagesToTemp) WBEdit.saveHeroImagesToTemp();
        if (WBEdit.saveFeatureItemsToTemp) WBEdit.saveFeatureItemsToTemp();
        if (WBEdit.saveServiceItemsToTemp) WBEdit.saveServiceItemsToTemp();
        if (WBEdit.saveSliderItemsToTemp) WBEdit.saveSliderItemsToTemp();
        if (WBEdit.saveGridCellsToTemp) WBEdit.saveGridCellsToTemp();
        else if (WBEdit.saveHeroSlidesToTemp) WBEdit.saveHeroSlidesToTemp();
    }

    // ===== 이벤트 =====
    document.getElementById('btnEditBack').addEventListener('click', hideEditPanel);
    document.getElementById('btnEditPanelCancel').addEventListener('click', hideEditPanel);
    document.getElementById('btnEditPanelSave').addEventListener('click', function() {
        if (!editBlock) return;
        saveEditFieldsToTemp();
        editBlock.dataset.config = JSON.stringify(editTempConfig);
        console.log('[WYSIWYG] Edit saved:', editBlock.dataset.widgetSlug, editTempConfig);
        var loading = editBlock.querySelector('.widget-loading');
        var iframe = editBlock.querySelector('.widget-preview');
        loading.classList.remove('hidden'); iframe.classList.add('hidden');
        WB.loadPreview(editBlock);
        hideEditPanel();
        WB.showStatus('success', translations.config_updated);
    });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && !editPanel.classList.contains('hidden')) hideEditPanel(); });

    console.log('[WYSIWYG] Edit core ready');

    return {
        get editPanel() { return editPanel; },
        get editTempConfig() { return editTempConfig; },
        set editTempConfig(v) { editTempConfig = v; },
        esc: esc,
        showUploadStatus: showUploadStatus,
        // items-js에서 설정하는 함수 슬롯
        renderHeroImagesSection: null,
        bindHeroImagesEvents: null,
        saveHeroImagesToTemp: null,
        renderButtonsSection: null,
        bindButtonsEvents: null,
        saveButtonsToTemp: null,
        bindVideoUploads: null,
        renderFeatureItemsSection: null,
        bindFeatureItemsEvents: null,
        saveFeatureItemsToTemp: null,
        renderServiceItemsSection: null,
        bindServiceItemsEvents: null,
        saveServiceItemsToTemp: null,
        renderHeroSlidesSection: null,
        renderNavItemsSection: null,
        bindHeroSlidesEvents: null,
        saveHeroSlidesToTemp: null,
        renderSliderItemsSection: null,
        bindSliderItemsEvents: null,
        saveSliderItemsToTemp: null,
        renderGridCellsSection: null,
        bindGridCellsEvents: null,
        saveGridCellsToTemp: null
    };
})();
</script>
<?php include __DIR__ . '/pages-widget-builder-edit-items-js.php'; ?>
<?php include __DIR__ . '/pages-widget-builder-edit-features-js.php'; ?>
<?php include __DIR__ . '/pages-widget-builder-edit-services-js.php'; ?>
<?php include __DIR__ . '/pages-widget-builder-edit-slider-js.php'; ?>
<?php include __DIR__ . '/pages-widget-builder-edit-grid-js.php'; ?>
<?php include __DIR__ . '/pages-widget-builder-inline-js.php'; ?>
<?php include __DIR__ . '/pages-widget-builder-grid-js.php'; ?>
