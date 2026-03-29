<?php
/**
 * RezlyX Admin - 범용 문서 페이지 에디터 JS
 */
?>
<script>
(function() {
    'use strict';
    var currentLocale = '<?= $currentLocale ?>';
    var savedContents = <?= json_encode($savedContents) ?>;

    console.log('[Document] Editor initialized, slug: <?= $pageSlug ?>, locale:', currentLocale);

    // === Summernote 초기화 ===
    function initSummernote() {
        if (typeof $ === 'undefined' || typeof $.fn.summernote === 'undefined') {
            setTimeout(initSummernote, 100);
            return;
        }
        var $editor = $('#editContent');
        if ($editor.length && !$editor.hasClass('summernote-initialized')) {
            $editor.summernote({
                lang: 'ko-KR',
                height: 350,
                placeholder: '<?= __($pageMeta['placeholder_key']) ?>',
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'table', 'hr']],
                    ['view', ['codeview', 'fullscreen', 'help']]
                ],
                callbacks: {
                    onInit: function() { console.log('[Document] Summernote ready'); }
                }
            });
            $editor.addClass('summernote-initialized');
        }
    }

    function getEditorContent() {
        if (typeof $ !== 'undefined' && $('#editContent').hasClass('summernote-initialized')) {
            return $('#editContent').summernote('code');
        }
        return document.getElementById('editContent').value;
    }

    function setEditorContent(html) {
        if (typeof $ !== 'undefined' && $('#editContent').hasClass('summernote-initialized')) {
            $('#editContent').summernote('code', html);
        } else {
            document.getElementById('editContent').value = html;
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSummernote);
    } else {
        initSummernote();
    }

    // === 언어 탭 전환 ===
    document.querySelectorAll('.lang-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            var lang = this.dataset.lang;
            console.log('[Document] Switching locale:', lang);

            document.querySelectorAll('.lang-tab').forEach(function(t) {
                t.classList.remove('bg-blue-600', 'text-white');
                t.classList.add('text-zinc-600', 'dark:text-zinc-400', 'hover:bg-zinc-100', 'dark:hover:bg-zinc-700');
            });
            this.classList.add('bg-blue-600', 'text-white');
            this.classList.remove('text-zinc-600', 'dark:text-zinc-400', 'hover:bg-zinc-100', 'dark:hover:bg-zinc-700');

            document.getElementById('editLocale').value = lang;

            if (savedContents[lang]) {
                document.getElementById('editTitle').value = savedContents[lang].title || '';
                setEditorContent(savedContents[lang].content || '');
            } else {
                document.getElementById('editTitle').value = '';
                setEditorContent('');
            }
        });
    });

    // === 폼 제출 ===
    var form = document.getElementById('documentForm');
    if (form) {
        form.addEventListener('submit', function() {
            var content = getEditorContent();
            if (typeof $ !== 'undefined' && $('#editContent').hasClass('summernote-initialized')) {
                $('#editContent').val(content);
            }
            var locale = document.getElementById('editLocale').value;
            savedContents[locale] = {
                locale: locale,
                title: document.getElementById('editTitle').value,
                content: content
            };
            console.log('[Document] Saving locale:', locale);
        });
    }

    // === 미리보기 모달 ===
    var previewModal = document.getElementById('previewModal');

    document.getElementById('btnPreview').addEventListener('click', function() {
        var content = getEditorContent();
        document.getElementById('previewBody').innerHTML = content || '<p style="color:#a1a1aa"><?= __($pageMeta['placeholder_key']) ?></p>';
        previewModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        console.log('[Document] Preview opened');
    });

    function closePreview() {
        previewModal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    document.getElementById('btnClosePreview').addEventListener('click', closePreview);
    document.getElementById('previewOverlay').addEventListener('click', closePreview);
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !previewModal.classList.contains('hidden')) closePreview();
    });

    // === 기본값 불러오기 ===
    var btnLoadDefault = document.getElementById('btnLoadDefault');
    if (btnLoadDefault && typeof defaultContents !== 'undefined') {
        btnLoadDefault.addEventListener('click', function() {
            var locale = document.getElementById('editLocale').value;
            var currentTitle = document.getElementById('editTitle').value;
            var currentContent = getEditorContent();

            if ((currentTitle || currentContent) && !confirm('<?= __('site.pages.document.load_default_confirm') ?>')) {
                console.log('[Document] Load default cancelled');
                return;
            }

            if (defaultContents[locale]) {
                document.getElementById('editTitle').value = defaultContents[locale].title || '';
                setEditorContent(defaultContents[locale].content || '');
                console.log('[Document] Default loaded for locale:', locale);
            } else if (defaultContents['en']) {
                document.getElementById('editTitle').value = defaultContents['en'].title || '';
                setEditorContent(defaultContents['en'].content || '');
                console.log('[Document] Default loaded (fallback en) for locale:', locale);
            }
        });
    }

    console.log('[Document] JS ready');
})();
</script>
