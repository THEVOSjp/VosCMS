<?php
/**
 * 마이페이지 — 제작 의뢰 폼
 */
use RzxLib\Core\Auth\Auth;

if (!$isLoggedIn) { header("Location: {$baseUrl}/login"); exit; }

$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
if (file_exists($_svcLangFile)) \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);
?>
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= $baseUrl ?>/mypage/custom-projects" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-lg font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.custom.new_title')) ?></h1>
            <p class="text-xs text-zinc-400 mt-1"><?= htmlspecialchars(__('services.custom.new_desc')) ?></p>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-6 space-y-4">
        <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                <?= htmlspecialchars(__('services.custom.f_title')) ?> <span class="text-red-500">*</span>
            </label>
            <input type="text" id="f_title" maxlength="200" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.custom.f_title_ph')) ?>">
        </div>

        <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                <?= htmlspecialchars(__('services.custom.f_site_type')) ?>
            </label>
            <select id="f_site_type" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
                <option value="homepage"><?= htmlspecialchars(__('services.custom.site_homepage')) ?></option>
                <option value="shop"><?= htmlspecialchars(__('services.custom.site_shop')) ?></option>
                <option value="reservation"><?= htmlspecialchars(__('services.custom.site_reservation')) ?></option>
                <option value="other"><?= htmlspecialchars(__('services.custom.site_other')) ?></option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                <?= htmlspecialchars(__('services.custom.f_requirements')) ?> <span class="text-red-500">*</span>
            </label>
            <textarea id="f_requirements" rows="8" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.custom.f_requirements_ph')) ?>"></textarea>
        </div>

        <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                <?= htmlspecialchars(__('services.custom.f_reference_urls')) ?>
            </label>
            <textarea id="f_reference_urls" rows="3" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.custom.f_reference_urls_ph')) ?>"></textarea>
            <p class="text-[10px] text-zinc-400 mt-1"><?= htmlspecialchars(__('services.custom.f_reference_urls_hint')) ?></p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    <?= htmlspecialchars(__('services.custom.f_budget')) ?>
                </label>
                <select id="f_budget" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
                    <option value="">-</option>
                    <option value="lt100k"><?= htmlspecialchars(__('services.custom.budget_lt100k')) ?></option>
                    <option value="100k_300k"><?= htmlspecialchars(__('services.custom.budget_100k_300k')) ?></option>
                    <option value="300k_1m"><?= htmlspecialchars(__('services.custom.budget_300k_1m')) ?></option>
                    <option value="gt1m"><?= htmlspecialchars(__('services.custom.budget_gt1m')) ?></option>
                    <option value="discuss"><?= htmlspecialchars(__('services.custom.budget_discuss')) ?></option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    <?= htmlspecialchars(__('services.custom.f_due_date')) ?>
                </label>
                <input type="date" id="f_due_date" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                <?= htmlspecialchars(__('services.custom.f_contact_hours')) ?>
            </label>
            <input type="text" id="f_contact_hours" maxlength="100" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.custom.f_contact_hours_ph')) ?>">
        </div>

        <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                📎 <?= htmlspecialchars(__('services.custom.f_attachments')) ?>
                <span class="text-[10px] text-zinc-400 font-normal ml-1"><?= htmlspecialchars(__('services.custom.f_attach_hint')) ?></span>
            </label>
            <input type="file" id="f_files" multiple class="block w-full text-xs text-zinc-600 dark:text-zinc-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-300">
            <div id="f_files_list" class="mt-2 space-y-1"></div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-200 dark:border-zinc-700">
            <a href="<?= $baseUrl ?>/mypage/custom-projects" class="px-4 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-700 rounded-lg"><?= htmlspecialchars(__('services.order.checkout.btn_cancel')) ?></a>
            <button type="button" id="submitBtn" onclick="submitProject()" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50">
                <?= htmlspecialchars(__('services.custom.btn_submit')) ?>
            </button>
        </div>
    </div>
</div>

<script>
var siteBaseUrl = '<?= $baseUrl ?>';
var maxFiles = 5;
var maxFileSize = 31457280;

function fmtSize(b){ b=parseInt(b,10)||0; if(b<1024)return b+' B'; if(b<1048576)return(b/1024).toFixed(1)+' KB'; return(b/1048576).toFixed(1)+' MB'; }
function esc(s){ return String(s||'').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

document.getElementById('f_files').addEventListener('change', function(){
    var list = document.getElementById('f_files_list');
    list.innerHTML = '';
    var files = Array.from(this.files);
    if (files.length > maxFiles) {
        alert('<?= htmlspecialchars(__('services.custom.attach_too_many', ['max' => 5])) ?>');
        this.value = ''; return;
    }
    for (var i = 0; i < files.length; i++) {
        var f = files[i];
        if (f.size > maxFileSize) {
            alert('"' + f.name + '" ' + '<?= htmlspecialchars(__('services.detail.support_attach_too_large', ['max' => '30MB'])) ?>');
            this.value=''; list.innerHTML=''; return;
        }
        var div = document.createElement('div');
        div.className = 'flex items-center gap-2 text-[11px] bg-gray-50 dark:bg-zinc-700/30 px-2 py-1 rounded';
        div.innerHTML = '<svg class="w-3 h-3 text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>'
                      + '<span class="truncate flex-1">' + esc(f.name) + '</span>'
                      + '<span class="text-zinc-400">' + fmtSize(f.size) + '</span>';
        list.appendChild(div);
    }
});

function uploadAttachments() {
    var inp = document.getElementById('f_files');
    var files = Array.from(inp.files || []);
    if (files.length === 0) return Promise.resolve([]);
    var fd = new FormData();
    files.forEach(function(f){ fd.append('files[]', f); });
    return fetch(siteBaseUrl + '/plugins/vos-hosting/api/support-attachment.php?action=upload_pending', {
        method: 'POST', body: fd, credentials: 'same-origin'
    }).then(function(r){ return r.json(); }).then(function(d){
        if (!d.success) throw new Error(d.message || 'upload failed');
        return d.attachments || [];
    });
}

function submitProject() {
    var title = document.getElementById('f_title').value.trim();
    var requirements = document.getElementById('f_requirements').value.trim();
    if (!title || !requirements) {
        alert('<?= htmlspecialchars(__('services.custom.required_missing')) ?>');
        return;
    }
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;

    uploadAttachments().then(function(atts) {
        return fetch(siteBaseUrl + '/plugins/vos-hosting/api/custom-project.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                action: 'project_create',
                title: title,
                site_type: document.getElementById('f_site_type').value,
                requirements: requirements,
                reference_urls: document.getElementById('f_reference_urls').value.trim(),
                budget_range: document.getElementById('f_budget').value,
                desired_due_date: document.getElementById('f_due_date').value,
                contact_hours: document.getElementById('f_contact_hours').value.trim(),
                attachments: atts,
            })
        }).then(function(r){ return r.json(); });
    }).then(function(d) {
        btn.disabled = false;
        if (d.success) {
            alert('<?= htmlspecialchars(__('services.custom.submit_done')) ?>');
            location.href = siteBaseUrl + '/mypage/custom-projects/' + d.project_id;
        } else {
            alert(d.message || 'error');
        }
    }).catch(function(e) {
        btn.disabled = false;
        alert(e && e.message || 'error');
    });
}
</script>
