<?php
/**
 * RezlyX Admin - 데이터 관리 가이드 JS
 */
?>
<script>
(function() {
    'use strict';
    var COMPLIANCE_URL = '<?= $adminUrl ?>/site/pages/compliance';
    var currentLocale = '<?= $currentLocale ?>';

    // 저장된 콘텐츠 데이터
    var savedContents = <?= json_encode($savedContents) ?>;

    console.log('[Compliance] Module initialized, locale:', currentLocale);

    // === Summernote 에디터 초기화 ===
    function initSummernote() {
        if (typeof $ === 'undefined' || typeof $.fn.summernote === 'undefined') {
            console.log('[Compliance] Waiting for Summernote...');
            setTimeout(initSummernote, 100);
            return;
        }
        var $editor = $('#editContent');
        if ($editor.length && !$editor.hasClass('summernote-initialized')) {
            $editor.summernote({
                lang: 'ko-KR',
                height: 350,
                placeholder: '<?= __('site.pages.compliance.content_placeholder') ?>',
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'table', 'hr']],
                    ['view', ['codeview', 'fullscreen', 'help']]
                ],
                callbacks: {
                    onInit: function() {
                        console.log('[Compliance] Summernote initialized');
                    }
                }
            });
            $editor.addClass('summernote-initialized');
        }
    }

    // Summernote 값 가져오기/설정하기 헬퍼
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

    // DOM 로드 후 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSummernote);
    } else {
        initSummernote();
    }

    // === 언어 탭 전환 ===
    document.querySelectorAll('.lang-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            var lang = this.dataset.lang;
            console.log('[Compliance] Switching to locale:', lang);

            // 탭 활성화
            document.querySelectorAll('.lang-tab').forEach(function(t) {
                t.classList.remove('bg-blue-600', 'text-white');
                t.classList.add('text-zinc-600', 'dark:text-zinc-400', 'hover:bg-zinc-100', 'dark:hover:bg-zinc-700');
            });
            this.classList.add('bg-blue-600', 'text-white');
            this.classList.remove('text-zinc-600', 'dark:text-zinc-400', 'hover:bg-zinc-100', 'dark:hover:bg-zinc-700');

            // 로캘 설정
            document.getElementById('editLocale').value = lang;

            // 저장된 콘텐츠가 있으면 로드
            if (savedContents[lang]) {
                document.getElementById('editTitle').value = savedContents[lang].title || '';
                setEditorContent(savedContents[lang].content || '');
                console.log('[Compliance] Loaded saved content for:', lang);
            } else {
                document.getElementById('editTitle').value = '';
                setEditorContent('');
                console.log('[Compliance] No saved content for:', lang);
            }
        });
    });

    // === 기본 콘텐츠 로드 ===
    var btnLoadDefault = document.getElementById('btnLoadDefault');
    if (btnLoadDefault) {
        btnLoadDefault.addEventListener('click', function() {
            var locale = document.getElementById('editLocale').value;
            console.log('[Compliance] Loading default content for:', locale);

            var fd = new FormData();
            fd.append('action', 'generate_default');
            fd.append('locale', locale);
            fd.append('country', '<?= $siteCountry ?>');
            fd.append('category', '<?= $siteCategory ?>');

            fetch(COMPLIANCE_URL, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(result) {
                    console.log('[Compliance] Default data loaded:', result);
                    if (result.success && result.data) {
                        var html = generateDefaultHtml(result.data, locale);
                        setEditorContent(html);
                        showAlert('<?= __('site.pages.compliance.default_loaded') ?>', 'success');
                    }
                })
                .catch(function(err) {
                    console.error('[Compliance] Load default error:', err);
                    showAlert('Error loading default content', 'error');
                });
        });
    }

    // === 기본 콘텐츠 HTML 생성 ===
    function generateDefaultHtml(data, locale) {
        var lines = [];

        // 테이블 헤더
        lines.push('<table class="w-full text-sm border-collapse">');
        lines.push('<thead><tr class="bg-zinc-100 dark:bg-zinc-700">');
        lines.push('<th class="px-4 py-2 text-left border"><?= __('site.pages.compliance.col_type') ?></th>');
        lines.push('<th class="px-4 py-2 text-left border"><?= __('site.pages.compliance.col_retention') ?></th>');
        lines.push('<th class="px-4 py-2 text-left border"><?= __('site.pages.compliance.col_basis') ?></th>');
        lines.push('<th class="px-4 py-2 text-left border"><?= __('site.pages.compliance.col_note') ?></th>');
        lines.push('</tr></thead><tbody>');

        // 데이터 행
        if (data.retention) {
            data.retention.forEach(function(item) {
                lines.push('<tr>');
                lines.push('<td class="px-4 py-2 border font-medium">' + translateKey(item.category_key) + '</td>');
                lines.push('<td class="px-4 py-2 border">' + translateKey(item.retention_key) + '</td>');
                lines.push('<td class="px-4 py-2 border text-xs">' + translateKey(item.basis_key) + '</td>');
                lines.push('<td class="px-4 py-2 border text-xs">' + (item.note_key ? translateKey(item.note_key) : '-') + '</td>');
                lines.push('</tr>');
            });
        }
        lines.push('</tbody></table>');

        // 실무 포인트
        if (data.tips && data.tips.length > 0) {
            lines.push('');
            lines.push('<div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">');
            lines.push('<h4 class="font-semibold text-sm mb-2"><?= __('site.pages.compliance.tips_title') ?></h4>');
            lines.push('<ul class="list-disc list-inside text-sm space-y-1">');
            data.tips.forEach(function(tipKey) {
                lines.push('<li>' + translateKey(tipKey) + '</li>');
            });
            lines.push('</ul></div>');
        }

        // 참고 링크
        if (data.references && data.references.length > 0) {
            lines.push('');
            lines.push('<div class="mt-4">');
            lines.push('<h4 class="font-semibold text-sm mb-2"><?= __('site.pages.compliance.references') ?></h4>');
            lines.push('<ul class="text-sm space-y-1">');
            data.references.forEach(function(ref) {
                lines.push('<li><a href="' + ref.url + '" target="_blank" class="text-blue-600 hover:underline">' + translateKey(ref.title_key) + '</a></li>');
            });
            lines.push('</ul></div>');
        }

        return lines.join('\n');
    }

    // === 번역 키 → 텍스트 매핑 (PHP에서 주입) ===
    var translations = <?= json_encode(getComplianceTranslations()) ?>;

    function translateKey(key) {
        return translations[key] || key;
    }

    // === 폼 제출 ===
    var form = document.getElementById('complianceForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('[Compliance] Form submitting, locale:', document.getElementById('editLocale').value);
            // Summernote 내용을 textarea에 동기화
            var content = getEditorContent();
            if (typeof $ !== 'undefined' && $('#editContent').hasClass('summernote-initialized')) {
                $('#editContent').val(content);
            }
            // 제출 후 savedContents 업데이트를 위해 현재 값 저장
            var locale = document.getElementById('editLocale').value;
            savedContents[locale] = {
                locale: locale,
                title: document.getElementById('editTitle').value,
                content: content
            };
        });
    }

    // === 알림 표시 ===
    function showAlert(msg, type) {
        var box = document.getElementById('alertBox');
        if (!box) return;
        box.className = 'mb-6 p-4 rounded-lg border ' + (type === 'success'
            ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800'
            : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800');
        box.textContent = msg;
        box.classList.remove('hidden');
        setTimeout(function() { box.classList.add('hidden'); }, 4000);
    }

    // === 미리보기 모달 ===
    var previewModal = document.getElementById('previewModal');
    var btnPreview = document.getElementById('btnPreview');
    var btnClosePreview = document.getElementById('btnClosePreview');
    var previewOverlay = document.getElementById('previewOverlay');

    function openPreview() {
        var title = document.getElementById('editTitle').value || '<?= __('customer.data_policy.title') ?>';
        var content = getEditorContent();
        console.log('[Compliance] Opening preview, title:', title);

        document.getElementById('previewTitle').textContent = title;
        document.getElementById('previewBody').innerHTML = content || '<p class="text-zinc-400"><?= __('site.pages.compliance.content_placeholder') ?></p>';
        previewModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closePreview() {
        previewModal.classList.add('hidden');
        document.body.style.overflow = '';
        console.log('[Compliance] Preview closed');
    }

    if (btnPreview) btnPreview.addEventListener('click', openPreview);
    if (btnClosePreview) btnClosePreview.addEventListener('click', closePreview);
    if (previewOverlay) previewOverlay.addEventListener('click', closePreview);

    // ESC 키로 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && previewModal && !previewModal.classList.contains('hidden')) {
            closePreview();
        }
    });

    console.log('[Compliance] JS ready');
})();
</script>

<?php
/**
 * 컴플라이언스 관련 번역 키 모두 수집하여 JS에 전달
 */
function getComplianceTranslations(): array {
    $keys = [
        // 데이터 유형
        'compliance.data_type.reservation', 'compliance.data_type.payment',
        'compliance.data_type.cash_receipt', 'compliance.data_type.customer_card',
        'compliance.data_type.medical_record', 'compliance.data_type.treatment_history',
        'compliance.data_type.allergy_info', 'compliance.data_type.guest_register',
        'compliance.data_type.receipt', 'compliance.data_type.karte',
        'compliance.data_type.allergy_health',
        // KR
        'compliance.kr.law.privacy', 'compliance.kr.law.vat', 'compliance.kr.law.income_tax',
        'compliance.kr.law.electronic_commerce', 'compliance.kr.law.medical', 'compliance.kr.law.tourism',
        'compliance.kr.retention.after_purpose', 'compliance.kr.retention.5years',
        'compliance.kr.retention.10years', 'compliance.kr.retention.3years',
        'compliance.kr.retention.consent_period',
        'compliance.kr.note.reservation', 'compliance.kr.note.payment',
        'compliance.kr.note.customer_card', 'compliance.kr.note.medical_record',
        'compliance.kr.note.treatment_history', 'compliance.kr.note.allergy_info',
        'compliance.kr.note.guest_register',
        'compliance.kr.tip.purpose_delete', 'compliance.kr.tip.tax_separate',
        'compliance.kr.tip.platform_booking', 'compliance.kr.tip.beauty_consent',
        'compliance.kr.tip.medical_strict', 'compliance.kr.tip.food_allergy',
        'compliance.kr.ref.pipc', 'compliance.kr.ref.law',
        // JP
        'compliance.jp.law.privacy', 'compliance.jp.law.corporate_tax',
        'compliance.jp.law.medical_practitioners', 'compliance.jp.law.food_sanitation',
        'compliance.jp.law.inn_act',
        'compliance.jp.retention.after_purpose', 'compliance.jp.retention.7years',
        'compliance.jp.retention.5years', 'compliance.jp.retention.3years',
        'compliance.jp.retention.consent_period', 'compliance.jp.retention.careful',
        'compliance.jp.note.reservation', 'compliance.jp.note.payment',
        'compliance.jp.note.customer_card', 'compliance.jp.note.medical_record',
        'compliance.jp.note.karte', 'compliance.jp.note.sensitive_info',
        'compliance.jp.note.food_allergy', 'compliance.jp.note.guest_register',
        'compliance.jp.tip.purpose_delete', 'compliance.jp.tip.tax_separate',
        'compliance.jp.tip.karte_caution', 'compliance.jp.tip.sensitive_info',
        'compliance.jp.tip.medical_strict',
        'compliance.jp.ref.ppc', 'compliance.jp.ref.e_gov',
        // Default
        'compliance.default.retention.check_local',
        'compliance.default.basis.local_privacy', 'compliance.default.basis.local_tax',
        'compliance.default.tip.check_local_law', 'compliance.default.tip.minimize_data',
        'compliance.default.tip.get_consent',
    ];

    $result = [];
    foreach ($keys as $key) {
        $result[$key] = __($key);
    }
    return $result;
}
?>
