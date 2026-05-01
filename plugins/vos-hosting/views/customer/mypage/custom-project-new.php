<?php
/**
 * 마이페이지 — 제작 의뢰 폼
 */
use RzxLib\Core\Auth\Auth;

if (!$isLoggedIn) { header("Location: {$baseUrl}/login"); exit; }

$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
if (file_exists($_svcLangFile)) \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);

// Prefill — 부가서비스 견적요청 등에서 넘어온 경우
$_prefillTitle = trim((string)($_GET['title'] ?? ''));
$_prefillFrom = (string)($_GET['from'] ?? '');
$_prefillHostSub = (int)($_GET['host_sub'] ?? 0);

$user = \RzxLib\Core\Auth\Auth::user();
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 사용자의 호스팅 구독 목록 조회
$_myHostSubs = [];
try {
    $_hsSt = $pdo->prepare("SELECT s.id, s.label, s.metadata, o.domain, o.order_number
        FROM {$prefix}subscriptions s
        LEFT JOIN {$prefix}orders o ON s.order_id = o.id
        WHERE s.user_id = ? AND s.type = 'hosting' AND s.status = 'active'
        ORDER BY s.id DESC");
    $_hsSt->execute([$user['id']]);
    $_myHostSubs = $_hsSt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

// 부가서비스 진입 (?from=addon&host_sub=N): 자동 매칭
$_addonHostSub = null;
$_addonDomain = '';
if ($_prefillFrom === 'addon' && $_prefillHostSub > 0) {
    foreach ($_myHostSubs as $_hs) {
        if ((int)$_hs['id'] === $_prefillHostSub) {
            $_addonHostSub = $_hs;
            $_addonDomain = (string)($_hs['domain'] ?? '');
            // metadata 의 added_domains 도 우선 후보 (있으면 첫번째 사용)
            $_meta = json_decode($_hs['metadata'] ?? '{}', true) ?: [];
            if (empty($_addonDomain) && !empty($_meta['added_domains'][0]['domain'])) {
                $_addonDomain = $_meta['added_domains'][0]['domain'];
            }
            break;
        }
    }
}

// 진입 경로 분기:
//   A: addon  — 자동 매칭됨, 도메인 결정 영역 숨김
//   B: 호스팅 미보유 — 안내 + 도메인 옵션 + 호스팅 신청 체크박스
//   C: 호스팅 보유 — 호스팅 셀렉트 + 도메인 옵션
$_pathMode = $_addonHostSub ? 'A' : (empty($_myHostSubs) ? 'B' : 'C');
?>
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= $baseUrl ?>/mypage/custom-projects"
           onclick="if(window.history.length > 1){ history.back(); return false; }"
           class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-lg font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.custom.new_title')) ?></h1>
            <p class="text-xs text-zinc-400 mt-1"><?= htmlspecialchars(__('services.custom.new_desc')) ?></p>
        </div>
    </div>

    <?php if ($_pathMode === 'A'): /* 부가서비스 진입 — 자동 매칭 */ ?>
    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl px-4 py-3 mb-4 text-sm">
        <p class="font-bold text-emerald-800 dark:text-emerald-200 mb-1">✅ <?= htmlspecialchars(__('services.custom.from_addon_notice_title')) ?></p>
        <p class="text-emerald-700 dark:text-emerald-300 text-xs">
            <?= htmlspecialchars(__('services.custom.from_addon_notice_body', ['domain' => $_addonDomain ?: ($_addonHostSub['order_number'] ?? '')])) ?>
        </p>
    </div>
    <?php elseif ($_pathMode === 'B'): /* 호스팅 미보유 — 안내 */ ?>
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl px-4 py-3 mb-4 text-sm">
        <p class="font-bold text-blue-800 dark:text-blue-200 mb-1">💡 <?= htmlspecialchars(__('services.custom.no_host_notice_title')) ?></p>
        <p class="text-blue-700 dark:text-blue-300 text-xs whitespace-pre-line"><?= htmlspecialchars(__('services.custom.no_host_notice_body')) ?></p>
    </div>
    <?php else: /* C — 호스팅 보유, 셀렉트 안내 */ ?>
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl px-4 py-3 mb-4 text-sm">
        <p class="font-bold text-blue-800 dark:text-blue-200 mb-1">💡 <?= htmlspecialchars(__('services.custom.has_host_notice_title')) ?></p>
        <p class="text-blue-700 dark:text-blue-300 text-xs"><?= htmlspecialchars(__('services.custom.has_host_notice_body')) ?></p>
    </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-6 space-y-4">
        <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                <?= htmlspecialchars(__('services.custom.f_title')) ?> <span class="text-red-500">*</span>
            </label>
            <input type="text" id="f_title" maxlength="200" value="<?= htmlspecialchars($_prefillTitle) ?>" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.custom.f_title_ph')) ?>">
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

        <!-- 도메인/호스팅 매칭 -->
        <div class="border-t border-gray-200 dark:border-zinc-700 pt-4 space-y-3">
            <p class="text-xs font-bold text-zinc-700 dark:text-zinc-200">🌐 <?= htmlspecialchars(__('services.custom.f_domain_section')) ?></p>

            <?php if ($_pathMode === 'A'): /* 부가서비스 — 자동 매칭, 숨겨진 입력 */ ?>
            <input type="hidden" id="f_domain_option" value="addon">
            <input type="hidden" id="f_domain_name" value="<?= htmlspecialchars($_addonDomain) ?>">
            <input type="hidden" id="f_linked_host_sub" value="<?= (int)$_addonHostSub['id'] ?>">
            <input type="hidden" id="f_need_new_hosting" value="0">
            <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-lg p-3 text-xs">
                <p class="text-zinc-700 dark:text-zinc-200">
                    <?= htmlspecialchars(__('services.custom.f_domain_addon_text', ['domain' => $_addonDomain ?: '-'])) ?>
                </p>
            </div>

            <?php elseif ($_pathMode === 'C'): /* 호스팅 보유 — 셀렉트 + 도메인 옵션 */ ?>
            <div>
                <label class="block text-[11px] text-zinc-600 dark:text-zinc-400 mb-1"><?= htmlspecialchars(__('services.custom.f_existing_host')) ?></label>
                <select id="f_linked_host_sub" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg" onchange="onHostSelect()">
                    <option value=""><?= htmlspecialchars(__('services.custom.f_existing_host_choose')) ?></option>
                    <?php foreach ($_myHostSubs as $_hs):
                        $_dom = $_hs['domain'] ?? '';
                    ?>
                    <option value="<?= (int)$_hs['id'] ?>" data-domain="<?= htmlspecialchars($_dom) ?>"><?= htmlspecialchars($_hs['order_number']) ?> · <?= htmlspecialchars($_dom ?: $_hs['label']) ?></option>
                    <?php endforeach; ?>
                    <option value="0"><?= htmlspecialchars(__('services.custom.f_new_host_instead')) ?></option>
                </select>
            </div>
            <input type="hidden" id="f_need_new_hosting" value="0">

            <div id="domain_block">
                <label class="block text-[11px] text-zinc-600 dark:text-zinc-400 mb-1"><?= htmlspecialchars(__('services.custom.f_domain_option')) ?></label>
                <select id="f_domain_option" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg" onchange="onDomainOptionChange()">
                    <option value=""><?= htmlspecialchars(__('services.custom.f_domain_choose')) ?></option>
                    <option value="new"><?= htmlspecialchars(__('services.custom.dom_new')) ?></option>
                    <option value="existing"><?= htmlspecialchars(__('services.custom.dom_existing')) ?></option>
                    <option value="free"><?= htmlspecialchars(__('services.custom.dom_free')) ?></option>
                    <option value="discuss"><?= htmlspecialchars(__('services.custom.dom_discuss')) ?></option>
                </select>
                <input type="text" id="f_domain_name" maxlength="255" class="hidden w-full mt-2 px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg" placeholder="<?= htmlspecialchars(__('services.custom.f_domain_name_ph')) ?>">
            </div>

            <?php else: /* B — 호스팅 미보유 */ ?>
            <input type="hidden" id="f_linked_host_sub" value="0">
            <div>
                <label class="flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" id="f_need_new_hosting" checked class="rounded">
                    <span><?= htmlspecialchars(__('services.custom.f_need_new_hosting')) ?></span>
                </label>
            </div>
            <div>
                <label class="block text-[11px] text-zinc-600 dark:text-zinc-400 mb-1"><?= htmlspecialchars(__('services.custom.f_domain_option')) ?></label>
                <select id="f_domain_option" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg" onchange="onDomainOptionChange()">
                    <option value=""><?= htmlspecialchars(__('services.custom.f_domain_choose')) ?></option>
                    <option value="new"><?= htmlspecialchars(__('services.custom.dom_new')) ?></option>
                    <option value="existing"><?= htmlspecialchars(__('services.custom.dom_existing')) ?></option>
                    <option value="free"><?= htmlspecialchars(__('services.custom.dom_free')) ?></option>
                    <option value="discuss"><?= htmlspecialchars(__('services.custom.dom_discuss')) ?></option>
                </select>
                <input type="text" id="f_domain_name" maxlength="255" class="hidden w-full mt-2 px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg" placeholder="<?= htmlspecialchars(__('services.custom.f_domain_name_ph')) ?>">
            </div>
            <?php endif; ?>
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
            <a href="<?= $baseUrl ?>/mypage/custom-projects"
               onclick="if(window.history.length > 1){ history.back(); return false; }"
               class="px-4 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-700 rounded-lg"><?= htmlspecialchars(__('services.order.checkout.btn_cancel')) ?></a>
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

function onDomainOptionChange() {
    var sel = document.getElementById('f_domain_option');
    var nameEl = document.getElementById('f_domain_name');
    if (!sel || !nameEl) return;
    if (sel.value === 'new' || sel.value === 'existing') {
        nameEl.classList.remove('hidden');
    } else {
        nameEl.classList.add('hidden');
        nameEl.value = '';
    }
}
function onHostSelect() {
    // 호스팅 셀렉트 → "새 호스팅" 선택 시 도메인 옵션이 의미있어짐. 기존 선택 시는 호스팅 도메인 자동 사용 가정 → 도메인 옵션을 "free" 로 자동 선택
    var sel = document.getElementById('f_linked_host_sub');
    var newHostEl = document.getElementById('f_need_new_hosting');
    var domSel = document.getElementById('f_domain_option');
    var nameEl = document.getElementById('f_domain_name');
    if (!sel) return;
    var v = sel.value;
    if (v === '' || v === '0') {
        // 새 호스팅 또는 미선택
        if (newHostEl) newHostEl.value = (v === '0' ? '1' : '0');
    } else {
        // 기존 호스팅 선택 → 도메인은 그 호스팅 도메인 사용
        if (newHostEl) newHostEl.value = '0';
        var opt = sel.options[sel.selectedIndex];
        var hd = opt && opt.getAttribute('data-domain') || '';
        if (domSel) {
            // 호스팅 도메인이 있으면 'existing' (이미 보유), 없으면 'discuss' 자동 설정 가능 — 일단 사용자 결정 유지
        }
        if (nameEl && hd) {
            nameEl.value = hd;
            // 호스팅 자체에 도메인이 이미 있으니 도메인 결정 박스 숨김 (사용자 추가 결정 불필요)
        }
    }
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

    var optEl = document.getElementById('f_domain_option');
    var nameEl = document.getElementById('f_domain_name');
    var hostEl = document.getElementById('f_linked_host_sub');
    var newHostEl = document.getElementById('f_need_new_hosting');
    var domainOption = optEl ? optEl.value : '';
    var domainName = nameEl ? nameEl.value.trim() : '';
    var linkedHostSub = hostEl ? parseInt(hostEl.value, 10) || 0 : 0;
    var needNewHosting = 0;
    if (newHostEl) {
        if (newHostEl.type === 'checkbox') needNewHosting = newHostEl.checked ? 1 : 0;
        else needNewHosting = parseInt(newHostEl.value, 10) || 0;
    }

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
                domain_option: domainOption,
                domain_name: domainName,
                linked_host_subscription_id: linkedHostSub,
                need_new_hosting: needNewHosting,
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
