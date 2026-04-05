<?php
/**
 * RezlyX Admin - 서비스 관리 JavaScript
 * services.php에서 include (서비스 목록 전용)
 */
?>
<script>
(function() {
    'use strict';

    // 서비스 다국어 번역 데이터
    var svcTranslations = <?= json_encode($svcTranslatedMap ?? [], JSON_UNESCAPED_UNICODE) ?>;

    // 서비스 다국어 임시 키
    var svcMultilangTempKey = null;

    function getServiceLangKey(field) {
        var svcId = document.getElementById('svcId').value;
        if (svcId) return 'service.' + svcId + '.' + field;
        if (!svcMultilangTempKey) {
            svcMultilangTempKey = 'service.tmp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
        }
        return svcMultilangTempKey + '.' + field;
    }

    function migrateServiceMultilangKeys(oldPrefix, newPrefix) {
        var fields = ['name', 'description'];
        fields.forEach(function(field) {
            var oldKey = oldPrefix + '.' + field;
            var newKey = newPrefix + '.' + field;
            console.log('[Services] Migrate multilang key:', oldKey, '→', newKey);
            fetch('<?= $adminUrl ?>/api/translations?action=rename', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ old_key: oldKey, new_key: newKey })
            })
                .then(function(r) { return r.json(); })
                .then(function(res) { console.log('[Services] Migrate result:', res); })
                .catch(function(err) { console.error('[Services] Migrate error:', err); });
        });
    }

    window.openServiceMultilang = function(field) {
        var langKey = getServiceLangKey(field);
        var inputId = field === 'name' ? 'svcName' : 'svcDescription';
        var type = field === 'description' ? 'editor' : 'text';
        if (typeof openMultilangModal === 'function') {
            openMultilangModal(langKey, inputId, type);
        }
        console.log('[Services] Multilang opened:', langKey, type);
    };

    // ─── 필터 적용 ───
    window.applyFilter = function() {
        var cat = document.getElementById('filterCategory').value;
        var status = document.getElementById('filterStatus').value;
        var params = new URLSearchParams(window.location.search);
        if (cat) params.set('category', cat); else params.delete('category');
        if (status) params.set('status', status); else params.delete('status');
        window.location.search = params.toString();
        console.log('[Services] Filter applied:', { category: cat, status: status });
    };

    // ─── 알림 표시 ───
    function showAlert(msg, type) {
        var box = document.getElementById('alertBox');
        box.className = 'mb-6 p-4 rounded-lg border ' +
            (type === 'success'
                ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800'
                : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800');
        box.textContent = msg;
        box.classList.remove('hidden');
        setTimeout(function() { box.classList.add('hidden'); }, 4000);
        console.log('[Services] Alert:', type, msg);
    }

    // ─── AJAX POST ───
    function postData(formData) {
        return fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        }).then(function(r) { return r.json(); });
    }

    // ═══ 이미지 미리보기 ═══
    window.previewServiceImage = function(input) {
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];
        if (file.size > 5 * 1024 * 1024) {
            showAlert('<?= __('services.image_too_large') ?>', 'error');
            input.value = '';
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('svcImagePreviewImg').src = e.target.result;
            document.getElementById('svcImagePreview').classList.remove('hidden');
            document.getElementById('svcImagePlaceholder').classList.add('hidden');
            document.getElementById('svcImageRemove').value = '0';
        };
        reader.readAsDataURL(file);
        console.log('[Services] Image selected:', file.name, file.size);
    };

    window.handleImageDrop = function(e) {
        var dt = e.dataTransfer;
        if (dt && dt.files && dt.files.length) {
            var input = document.getElementById('svcImageInput');
            input.files = dt.files;
            previewServiceImage(input);
        }
    };

    window.removeServiceImage = function() {
        document.getElementById('svcImageInput').value = '';
        document.getElementById('svcImagePreview').classList.add('hidden');
        document.getElementById('svcImagePlaceholder').classList.remove('hidden');
        document.getElementById('svcImageRemove').value = '1';
        document.getElementById('svcImageExisting').value = '';
        console.log('[Services] Image removed');
    };

    function resetImageUI() {
        document.getElementById('svcImageInput').value = '';
        document.getElementById('svcImagePreview').classList.add('hidden');
        document.getElementById('svcImagePlaceholder').classList.remove('hidden');
        document.getElementById('svcImageExisting').value = '';
        document.getElementById('svcImageRemove').value = '0';
        document.getElementById('svcImageWidth').value = '800';
        document.getElementById('svcImageHeight').value = '600';
    }

    function loadExistingImage(imagePath) {
        if (!imagePath) { resetImageUI(); return; }
        var baseUrl = '<?= $baseUrl ?>';
        var imgSrc = imagePath.startsWith('storage/') ? baseUrl + '/' + imagePath : baseUrl + '/storage/' + imagePath;
        document.getElementById('svcImagePreviewImg').src = imgSrc;
        document.getElementById('svcImagePreview').classList.remove('hidden');
        document.getElementById('svcImagePlaceholder').classList.add('hidden');
        document.getElementById('svcImageExisting').value = imagePath;
        document.getElementById('svcImageRemove').value = '0';
    }

    // ═══ 서비스 모달 ═══
    window.openServiceModal = function() {
        svcMultilangTempKey = null;
        document.getElementById('serviceModalTitle').textContent = '<?= __('services.create') ?>';
        document.getElementById('svcAction').value = 'create_service';
        document.getElementById('svcId').value = '';
        document.getElementById('svcName').value = '';
        document.getElementById('svcSlug').value = '';
        document.getElementById('svcCategory').value = '';
        document.getElementById('svcPrice').value = '0';
        document.getElementById('svcDuration').value = '30';
        document.getElementById('svcBuffer').value = '0';
        document.getElementById('svcDescription').value = '';
        document.getElementById('svcActive').checked = true;
        resetImageUI();
        document.getElementById('serviceModal').classList.remove('hidden');
        console.log('[Services] Service modal opened (create)');
    };

    window.editService = function(svc) {
        svcMultilangTempKey = null;
        var tr = svcTranslations[svc.id] || {};
        document.getElementById('serviceModalTitle').textContent = '<?= __('services.edit') ?>';
        document.getElementById('svcAction').value = 'update_service';
        document.getElementById('svcId').value = svc.id;
        document.getElementById('svcName').value = tr.name || svc.name || '';
        document.getElementById('svcSlug').value = svc.slug || '';
        document.getElementById('svcCategory').value = svc.category_id || '';
        document.getElementById('svcPrice').value = svc.price || 0;
        document.getElementById('svcDuration').value = svc.duration || 30;
        document.getElementById('svcBuffer').value = svc.buffer_time || 0;
        document.getElementById('svcDescription').value = tr.description || svc.description || '';
        document.getElementById('svcActive').checked = svc.is_active == 1;
        loadExistingImage(svc.image || '');
        document.getElementById('serviceModal').classList.remove('hidden');
        console.log('[Services] Service modal opened (edit):', svc.id);
    };

    window.closeServiceModal = function() {
        document.getElementById('serviceModal').classList.add('hidden');
        console.log('[Services] Service modal closed');
    };

    window.saveService = function() {
        var form = document.getElementById('serviceForm');
        var name = document.getElementById('svcName').value.trim();
        if (!name) {
            document.getElementById('svcName').focus();
            return;
        }

        var formData = new FormData(form);
        if (!document.getElementById('svcActive').checked) {
            formData.delete('is_active');
        }

        var isNew = !document.getElementById('svcId').value;
        console.log('[Services] Saving service:', formData.get('action'), name);

        postData(formData).then(function(data) {
            if (data.success) {
                // 신규 서비스 + 임시 키가 있으면 실제 키로 마이그레이션
                if (isNew && svcMultilangTempKey && data.id) {
                    migrateServiceMultilangKeys(svcMultilangTempKey, 'service.' + data.id);
                    svcMultilangTempKey = null;
                }
                showAlert(data.message, 'success');
                closeServiceModal();
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showAlert(data.message || '<?= __('services.error.generic') ?>', 'error');
            }
        }).catch(function(err) {
            console.error('[Services] Save error:', err);
            showAlert('<?= __('services.error.server_error') ?>', 'error');
        });
    };

    window.deleteService = function(id) {
        if (!confirm('<?= __('services.confirm_delete') ?>')) return;
        console.log('[Services] Deleting service:', id);

        var formData = new FormData();
        formData.append('action', 'delete_service');
        formData.append('id', id);

        postData(formData).then(function(data) {
            if (data.success) {
                showAlert(data.message, 'success');
                var row = document.getElementById('svc-' + id);
                if (row) row.remove();
            } else {
                showAlert(data.message || '<?= __('services.error.delete_failed') ?>', 'error');
            }
        }).catch(function(err) {
            console.error('[Services] Delete error:', err);
            showAlert('<?= __('services.error.server_error') ?>', 'error');
        });
    };

    window.toggleService = function(id) {
        console.log('[Services] Toggling service:', id);
        var formData = new FormData();
        formData.append('action', 'toggle_service');
        formData.append('id', id);

        postData(formData).then(function(data) {
            if (data.success) {
                var btn = document.getElementById('toggle-' + id);
                if (btn) {
                    var isActive = btn.dataset.active === '1' ? '0' : '1';
                    btn.dataset.active = isActive;
                    var dot = btn.querySelector('span');
                    if (isActive === '1') {
                        btn.classList.remove('bg-zinc-300', 'dark:bg-zinc-600');
                        btn.classList.add('bg-green-500');
                        dot.classList.remove('translate-x-1');
                        dot.classList.add('translate-x-6');
                    } else {
                        btn.classList.remove('bg-green-500');
                        btn.classList.add('bg-zinc-300', 'dark:bg-zinc-600');
                        dot.classList.remove('translate-x-6');
                        dot.classList.add('translate-x-1');
                    }
                }
                console.log('[Services] Toggle result:', isActive);
            }
        }).catch(function(err) {
            console.error('[Services] Toggle error:', err);
        });
    };

    console.log('[Services] Page initialized');
})();
</script>
