<?php
/**
 * 1:1 상담 탭 (사용자 마이페이지)
 * 기술 지원 active 부가서비스 보유 시 노출.
 *
 * $subs        — [tech_support_sub] (단일)
 * $servicesByType — 전체 서비스 그룹 (호스팅 sub 참조용)
 * $order       — 주문 row
 */
$_supportSubId = (int)($subs[0]['id'] ?? 0);
$_hostSubForSupport = $servicesByType['hosting'][0] ?? null;
$_hostSubIdForSupport = (int)($_hostSubForSupport['id'] ?? 0);
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden" id="supportTicketsSection">
    <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
        <div>
            <p class="text-sm font-bold text-zinc-900 dark:text-white">💬 <?= htmlspecialchars(__('services.detail.support_section_title')) ?></p>
            <p class="text-[10px] text-zinc-400 mt-0.5"><?= htmlspecialchars(__('services.detail.support_section_desc')) ?></p>
        </div>
        <button type="button" onclick="supportOpenNewModal()"
            class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg whitespace-nowrap">
            + <?= htmlspecialchars(__('services.detail.support_btn_new')) ?>
        </button>
    </div>
    <div id="supportTicketsList" class="divide-y divide-gray-100 dark:divide-zinc-700/50">
        <div class="p-6 text-center text-xs text-zinc-400"><?= htmlspecialchars(__('services.detail.support_loading')) ?></div>
    </div>
    <div id="supportPagination" class="px-5 py-3 border-t border-gray-100 dark:border-zinc-700 flex items-center justify-center gap-1 hidden"></div>
</div>

<!-- 새 문의 모달 -->
<div id="supportNewModal" class="hidden fixed inset-0 z-[100] items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="supportCloseNewModal()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex items-center justify-between">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.detail.support_new_title')) ?></h3>
            <button type="button" onclick="supportCloseNewModal()" class="text-zinc-400 hover:text-zinc-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-3">
            <div>
                <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.detail.support_field_title')) ?></label>
                <input type="text" id="supportNewTitle" maxlength="200" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.detail.support_title_placeholder')) ?>">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.detail.support_field_body')) ?></label>
                <textarea id="supportNewBody" rows="6" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.detail.support_body_placeholder')) ?>"></textarea>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-300 mb-1">📎 <?= htmlspecialchars(__('services.detail.support_attachments')) ?>
                    <span class="text-[10px] text-zinc-400 font-normal ml-1"><?= htmlspecialchars(__('services.detail.support_attach_hint')) ?></span>
                </label>
                <input type="file" id="supportNewFiles" multiple class="block w-full text-xs text-zinc-600 dark:text-zinc-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-300">
                <div id="supportNewFilesList" class="mt-2 space-y-1"></div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700 flex items-center justify-end gap-2">
            <button type="button" onclick="supportCloseNewModal()" class="px-4 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-700 rounded-lg"><?= htmlspecialchars(__('services.order.checkout.btn_cancel')) ?></button>
            <button type="button" id="supportNewSubmit" onclick="supportSubmitNew()" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50"><?= htmlspecialchars(__('services.detail.support_btn_submit')) ?></button>
        </div>
    </div>
</div>

<!-- 티켓 상세 (thread) 모달 -->
<div id="supportThreadModal" class="hidden fixed inset-0 z-[100] items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="supportCloseThreadModal()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex items-center justify-between sticky top-0 bg-white dark:bg-zinc-800 z-10">
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <h3 id="supportThreadTitle" class="text-base font-bold text-zinc-900 dark:text-white truncate"></h3>
                <span id="supportThreadStatus" class="text-[10px] px-2 py-0.5 rounded-full font-medium"></span>
            </div>
            <button type="button" onclick="supportCloseThreadModal()" class="text-zinc-400 hover:text-zinc-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="supportThreadMessages" class="p-6 space-y-3 max-h-[55vh] overflow-y-auto"></div>
        <div id="supportReplyArea" class="border-t border-gray-200 dark:border-zinc-700 p-4 bg-gray-50 dark:bg-zinc-700/30 sticky bottom-0">
            <textarea id="supportReplyBody" rows="3" class="w-full px-3 py-2 text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.detail.support_reply_placeholder')) ?>"></textarea>
            <div class="mt-2">
                <input type="file" id="supportReplyFiles" multiple class="block w-full text-[11px] text-zinc-600 dark:text-zinc-300 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-300">
                <div id="supportReplyFilesList" class="mt-1 space-y-1"></div>
            </div>
            <div class="flex items-center justify-between mt-2 gap-2">
                <button type="button" onclick="supportCloseTicket()" id="supportCloseBtn" class="text-[11px] text-zinc-500 hover:text-red-600 underline"><?= htmlspecialchars(__('services.detail.support_btn_close')) ?></button>
                <button type="button" onclick="supportSubmitReply()" id="supportReplySubmit" class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50"><?= htmlspecialchars(__('services.detail.support_btn_reply')) ?></button>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var spHostSubId = <?= $_hostSubIdForSupport ?>;
    var spAddonSubId = <?= $_supportSubId ?>;
    var spPage = 1;
    var spCurrentTicket = null;
    var spMaxFileSize = 30 * 1024 * 1024;  // 30 MB
    var spMaxFiles = 3;

    function fmtDate(s) { try { return new Date(s).toLocaleString(); } catch(e) { return s||''; } }
    function fmtSize(b) {
        b = parseInt(b, 10) || 0;
        if (b < 1024) return b + ' B';
        if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
        return (b / 1024 / 1024).toFixed(1) + ' MB';
    }
    function el(id) { return document.getElementById(id); }
    function esc(s) { return String(s||'').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

    // ---- 파일 선택 미리보기 ----
    function bindFilePreview(inputId, listId) {
        var inp = el(inputId);
        if (!inp) return;
        inp.addEventListener('change', function() {
            var list = el(listId);
            list.innerHTML = '';
            var files = Array.from(this.files);
            if (files.length > spMaxFiles) {
                alert('<?= htmlspecialchars(__('services.detail.support_attach_too_many', ['max' => 3])) ?>');
                this.value = '';
                return;
            }
            for (var i = 0; i < files.length; i++) {
                var f = files[i];
                if (f.size > spMaxFileSize) {
                    alert('"' + f.name + '" ' + '<?= htmlspecialchars(__('services.detail.support_attach_too_large', ['max' => '30MB'])) ?>');
                    this.value = '';
                    list.innerHTML = '';
                    return;
                }
                var div = document.createElement('div');
                div.className = 'flex items-center gap-2 text-[11px] bg-gray-50 dark:bg-zinc-700/30 px-2 py-1 rounded';
                div.innerHTML = '<svg class="w-3 h-3 text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>'
                              + '<span class="truncate flex-1">' + esc(f.name) + '</span>'
                              + '<span class="text-zinc-400">' + fmtSize(f.size) + '</span>';
                list.appendChild(div);
            }
        });
    }
    bindFilePreview('supportNewFiles', 'supportNewFilesList');
    bindFilePreview('supportReplyFiles', 'supportReplyFilesList');

    // ---- 첨부 업로드 (pending) ----
    function uploadAttachments(fileInput) {
        var files = fileInput && fileInput.files ? Array.from(fileInput.files) : [];
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

    function loadList(page) {
        spPage = page || 1;
        el('supportTicketsList').innerHTML = '<div class="p-6 text-center text-xs text-zinc-400">...</div>';
        serviceAction('support_list_tickets', { host_subscription_id: spHostSubId, page: spPage, per_page: 5 })
            .then(function(d) {
                if (!d.success) {
                    el('supportTicketsList').innerHTML = '<div class="p-6 text-center text-xs text-red-500">' + (d.message || 'error') + '</div>';
                    return;
                }
                if (!d.tickets || d.tickets.length === 0) {
                    el('supportTicketsList').innerHTML = '<div class="p-6 text-center text-xs text-zinc-400"><?= htmlspecialchars(__('services.detail.support_empty')) ?></div>';
                    el('supportPagination').classList.add('hidden');
                    return;
                }
                var html = '';
                d.tickets.forEach(function(t) {
                    var statusColor = t.status === 'open' ? 'text-amber-600' : (t.status === 'answered' ? 'text-emerald-600' : 'text-zinc-400');
                    var statusLabel = t.status === 'open' ? '<?= htmlspecialchars(__('services.detail.support_st_open')) ?>' :
                                      t.status === 'answered' ? '<?= htmlspecialchars(__('services.detail.support_st_answered')) ?>' :
                                      '<?= htmlspecialchars(__('services.detail.support_st_closed')) ?>';
                    var unread = parseInt(t.unread_by_user, 10) > 0 ? '<span class="ml-1 inline-block w-1.5 h-1.5 rounded-full bg-red-500"></span>' : '';
                    html += '<div class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-700/30 cursor-pointer" onclick="supportOpenThread(' + t.id + ')">'
                          + '<div class="flex items-center justify-between gap-3">'
                          + '<p class="text-sm font-medium text-zinc-900 dark:text-white truncate">' + esc(t.title) + unread + '</p>'
                          + '<span class="text-[10px] ' + statusColor + ' whitespace-nowrap">' + statusLabel + '</span>'
                          + '</div>'
                          + '<p class="text-[10px] text-zinc-400 mt-1">' + fmtDate(t.last_message_at) + '</p>'
                          + '</div>';
                });
                el('supportTicketsList').innerHTML = html;

                var perPage = d.per_page || 5;
                var totalPages = Math.max(1, Math.ceil(d.total / perPage));
                if (totalPages > 1) {
                    var ph = '';
                    for (var i = 1; i <= totalPages; i++) {
                        ph += '<button onclick="event.stopPropagation();supportLoadList(' + i + ')" class="px-2.5 py-1 text-xs rounded ' + (i === spPage ? 'bg-blue-600 text-white' : 'bg-white dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 border border-gray-200 dark:border-zinc-600') + '">' + i + '</button>';
                    }
                    el('supportPagination').innerHTML = ph;
                    el('supportPagination').classList.remove('hidden');
                } else {
                    el('supportPagination').classList.add('hidden');
                }
            }).catch(function(e) {
                el('supportTicketsList').innerHTML = '<div class="p-6 text-center text-xs text-red-500">' + (e && e.message || 'error') + '</div>';
            });
    }
    window.supportLoadList = loadList;

    window.supportOpenNewModal = function() {
        el('supportNewTitle').value = '';
        el('supportNewBody').value = '';
        el('supportNewFiles').value = '';
        el('supportNewFilesList').innerHTML = '';
        var m = el('supportNewModal');
        if (m.parentElement !== document.body) document.body.appendChild(m);
        m.classList.remove('hidden'); m.classList.add('flex');
        document.body.style.overflow = 'hidden';
    };
    window.supportCloseNewModal = function() {
        var m = el('supportNewModal');
        m.classList.add('hidden'); m.classList.remove('flex');
        document.body.style.overflow = '';
    };
    window.supportSubmitNew = function() {
        var title = el('supportNewTitle').value.trim();
        var body = el('supportNewBody').value.trim();
        if (!title || !body) { alert('<?= htmlspecialchars(__('services.detail.support_title_body_required')) ?>'); return; }
        var btn = el('supportNewSubmit');
        btn.disabled = true;
        uploadAttachments(el('supportNewFiles')).then(function(atts) {
            return serviceAction('support_create_ticket', {
                host_subscription_id: spHostSubId,
                addon_subscription_id: spAddonSubId,
                title: title, body: body,
                attachments: atts,
            });
        }).then(function(d) {
            btn.disabled = false;
            if (d.success) {
                supportCloseNewModal();
                loadList(1);
            } else {
                alert(d.message || 'error');
            }
        }).catch(function(e) { btn.disabled = false; alert(e && e.message || 'error'); });
    };

    window.supportOpenThread = function(ticketId) {
        var m = el('supportThreadModal');
        if (m.parentElement !== document.body) document.body.appendChild(m);
        m.classList.remove('hidden'); m.classList.add('flex');
        document.body.style.overflow = 'hidden';
        el('supportThreadMessages').innerHTML = '<div class="text-center text-xs text-zinc-400 py-6">...</div>';
        serviceAction('support_get_ticket', { ticket_id: ticketId })
            .then(function(d) {
                if (!d.success) { el('supportThreadMessages').innerHTML = '<div class="text-red-500 text-xs p-6">' + (d.message||'error') + '</div>'; return; }
                spCurrentTicket = d.ticket;
                el('supportThreadTitle').textContent = d.ticket.title;
                var statusBadge = el('supportThreadStatus');
                if (d.ticket.status === 'open') { statusBadge.textContent = '<?= htmlspecialchars(__('services.detail.support_st_open')) ?>'; statusBadge.className = 'text-[10px] px-2 py-0.5 rounded-full font-medium bg-amber-100 text-amber-700'; }
                else if (d.ticket.status === 'answered') { statusBadge.textContent = '<?= htmlspecialchars(__('services.detail.support_st_answered')) ?>'; statusBadge.className = 'text-[10px] px-2 py-0.5 rounded-full font-medium bg-emerald-100 text-emerald-700'; }
                else { statusBadge.textContent = '<?= htmlspecialchars(__('services.detail.support_st_closed')) ?>'; statusBadge.className = 'text-[10px] px-2 py-0.5 rounded-full font-medium bg-gray-100 text-gray-500'; }

                var msgHtml = '';
                (d.messages || []).forEach(function(msg) {
                    var isAdmin = parseInt(msg.is_admin, 10) === 1;
                    var bgClass = isAdmin ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800' : 'bg-gray-50 dark:bg-zinc-700/30 border-gray-200 dark:border-zinc-700';
                    var sender = isAdmin ? '<?= htmlspecialchars(__('services.detail.support_admin')) ?>' : (msg.sender_name || msg.sender_email || 'user');
                    msgHtml += '<div class="border ' + bgClass + ' rounded-lg p-3">'
                            + '<div class="flex items-center justify-between mb-2 text-[10px] text-zinc-500"><span class="font-medium">' + esc(sender) + '</span><span>' + fmtDate(msg.created_at) + '</span></div>'
                            + '<p class="text-sm text-zinc-800 dark:text-zinc-200 whitespace-pre-wrap">' + esc(msg.body) + '</p>';
                    var atts = msg.attachments || [];
                    if (atts.length > 0) {
                        msgHtml += '<div class="mt-2 pt-2 border-t border-gray-200 dark:border-zinc-700 space-y-1">';
                        atts.forEach(function(a, idx) {
                            var isImg = /^(jpg|jpeg|png|gif|webp|svg)$/i.test(a.ext || '');
                            var dlUrl = siteBaseUrl + '/plugins/vos-hosting/api/support-attachment.php?action=download&msg=' + msg.id + '&idx=' + idx;
                            var inUrl = siteBaseUrl + '/plugins/vos-hosting/api/support-attachment.php?action=inline&msg=' + msg.id + '&idx=' + idx;
                            if (isImg) {
                                msgHtml += '<a href="' + dlUrl + '" target="_blank" class="block">'
                                        + '<img src="' + inUrl + '" alt="' + esc(a.name) + '" class="max-h-48 rounded border border-gray-200 dark:border-zinc-600 hover:opacity-80 transition" />'
                                        + '<span class="text-[10px] text-zinc-400 mt-0.5 block">' + esc(a.name) + ' (' + fmtSize(a.size) + ')</span>'
                                        + '</a>';
                            } else {
                                msgHtml += '<a href="' + dlUrl + '" class="flex items-center gap-2 text-[11px] bg-white dark:bg-zinc-700 hover:bg-blue-50 dark:hover:bg-zinc-600 px-2 py-1.5 rounded border border-gray-200 dark:border-zinc-600 transition">'
                                        + '<svg class="w-3.5 h-3.5 text-zinc-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>'
                                        + '<span class="truncate flex-1 text-zinc-700 dark:text-zinc-200">' + esc(a.name) + '</span>'
                                        + '<span class="text-zinc-400">' + fmtSize(a.size) + '</span>'
                                        + '</a>';
                            }
                        });
                        msgHtml += '</div>';
                    }
                    msgHtml += '</div>';
                });
                el('supportThreadMessages').innerHTML = msgHtml || '<div class="text-zinc-400 text-xs text-center">no messages</div>';

                if (d.ticket.status === 'closed') {
                    el('supportReplyArea').classList.add('hidden');
                } else {
                    el('supportReplyArea').classList.remove('hidden');
                    el('supportReplyBody').value = '';
                    el('supportReplyFiles').value = '';
                    el('supportReplyFilesList').innerHTML = '';
                }
            }).catch(function(e) { el('supportThreadMessages').innerHTML = '<div class="text-red-500 text-xs p-6">' + (e&&e.message) + '</div>'; });
    };
    window.supportCloseThreadModal = function() {
        var m = el('supportThreadModal');
        m.classList.add('hidden'); m.classList.remove('flex');
        document.body.style.overflow = '';
        loadList(spPage);
    };
    window.supportSubmitReply = function() {
        if (!spCurrentTicket) return;
        var body = el('supportReplyBody').value.trim();
        if (!body) return;
        var btn = el('supportReplySubmit');
        btn.disabled = true;
        uploadAttachments(el('supportReplyFiles')).then(function(atts) {
            return serviceAction('support_post_message', {
                ticket_id: spCurrentTicket.id, body: body, attachments: atts,
            });
        }).then(function(d) {
            btn.disabled = false;
            if (d.success) {
                supportOpenThread(spCurrentTicket.id);
            } else {
                alert(d.message || 'error');
            }
        }).catch(function(e) { btn.disabled = false; alert(e && e.message || 'error'); });
    };
    window.supportCloseTicket = function() {
        if (!spCurrentTicket) return;
        if (!confirm('<?= htmlspecialchars(__('services.detail.support_confirm_close')) ?>')) return;
        serviceAction('support_close_ticket', { ticket_id: spCurrentTicket.id })
            .then(function(d) {
                if (d.success) { supportOpenThread(spCurrentTicket.id); }
                else alert(d.message || 'error');
            });
    };

    if (typeof serviceAction === 'function') {
        loadList(1);
    } else {
        document.addEventListener('DOMContentLoaded', function() { loadList(1); });
    }
})();
</script>
