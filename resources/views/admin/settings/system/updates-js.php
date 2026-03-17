<script>
console.log('[Updates] Page loaded');

const csrfToken = '<?= htmlspecialchars($csrfToken) ?>';
const currentVersion = '<?= htmlspecialchars($currentVersion) ?>';
const currentPath = window.location.pathname;
// 독립 엔드포인트 사용 (index.php PDO 충돌 회피)
const ajaxUrl = window.location.origin + '/update-api.php';
let latestVersion = null;

// 번역 문자열
const i18n = {
    checking: '<?= __('system.updates.checking') ?>',
    checkFailed: '<?= __('system.updates.check_failed') ?>',
    checkUpdate: '<?= __('system.updates.check_update') ?>',
    latestVersion: '<?= __('system.updates.latest_version') ?>',
    download: '<?= __('system.updates.download') ?>',
    updateNow: '<?= __('system.updates.update_now') ?>',
    updateAvailableMsg: '<?= __('system.updates.update_available_msg') ?>',
    releaseNotes: '<?= __('system.updates.release_notes') ?>',
    upToDate: '<?= __('system.updates.up_to_date') ?>',
    confirmUpdate: '<?= __('system.updates.confirm_update') ?>',
    updateFailed: '<?= __('system.updates.update_failed') ?>',
    reloadPage: '<?= __('system.updates.reload_page') ?>',
    processing: '<?= __('admin.messages.processing') ?>',
    noBackups: '<?= __('system.updates.no_backups') ?>',
    restore: '<?= __('system.updates.restore') ?>',
    confirmRestore: '<?= __('system.updates.confirm_restore') ?>',
    restoreFailed: '<?= __('system.updates.restore_failed') ?>',
    deleteBackup: '<?= __('system.updates.delete_backup') ?>',
    confirmDelete: '<?= __('system.updates.confirm_delete_backup') ?>',
    // 패치 관련
    fullUpdate: '<?= __('system.updates.full_update') ?>',
    patchUpdate: '<?= __('system.updates.patch_update') ?>',
    compareFiles: '<?= __('system.updates.compare_files') ?>',
    comparing: '<?= __('system.updates.comparing') ?>',
    compareFailed: '<?= __('system.updates.compare_failed') ?>',
    noChanges: '<?= __('system.updates.no_changes') ?>',
    tooManyChanges: '<?= __('system.updates.too_many_changes') ?>',
    addedFiles: '<?= __('system.updates.added_files') ?>',
    modifiedFiles: '<?= __('system.updates.modified_files') ?>',
    removedFiles: '<?= __('system.updates.removed_files') ?>',
    patchDesc: '<?= __('system.updates.patch_desc') ?>',
    fullDesc: '<?= __('system.updates.full_desc') ?>',
    confirmPatch: '<?= __('system.updates.confirm_patch') ?>',
};

console.log('[Updates] AJAX URL:', ajaxUrl);

document.addEventListener('DOMContentLoaded', function() {
    loadBackups();
    checkForUpdates();
});

// ===== AJAX 헬퍼 =====
async function ajaxPost(action, extraParams = '') {
    const response = await fetch(ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken },
        body: '_token=' + encodeURIComponent(csrfToken) + '&action=' + action + extraParams
    });
    return response.json();
}

// ===== 업데이트 확인 =====
async function checkForUpdates() {
    const btn = document.getElementById('checkUpdateBtn');
    const statusDiv = document.getElementById('updateStatus');
    btn.disabled = true;
    btn.innerHTML = spinnerIcon() + i18n.checking;

    try {
        const data = await ajaxPost('check');
        if (!data.success) throw new Error(data.error || i18n.checkFailed);

        const result = data.data;
        if (result.error && !result.has_update) {
            statusDiv.innerHTML = warningBox(escapeHtml(result.error));
        } else if (result.has_update) {
            latestVersion = result.latest_version;
            statusDiv.innerHTML = renderUpdateAvailable(result);
            updateLatestVersionCard(result.latest_version, true);
        } else {
            statusDiv.innerHTML = successBox(i18n.upToDate);
            updateLatestVersionCard(result.latest_version || currentVersion, false);
        }
        statusDiv.classList.remove('hidden');
    } catch (error) {
        statusDiv.innerHTML = errorBox(error.message);
        statusDiv.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.innerHTML = refreshIcon() + i18n.checkUpdate;
    }
}

// 상단 최신 버전 카드 갱신
function updateLatestVersionCard(version, hasUpdate) {
    const card = document.getElementById('latestVersionCard');
    if (!card) return;
    const iconDiv = card.querySelector('.w-12');
    const textDiv = iconDiv ? iconDiv.nextElementSibling : null;
    if (!iconDiv || !textDiv) return;

    if (hasUpdate) {
        iconDiv.className = 'w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center mr-4 shrink-0';
        iconDiv.innerHTML = '<svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>';
        textDiv.innerHTML = `<p class="text-xs text-zinc-500 dark:text-zinc-400 mb-0.5">${i18n.latestVersion}</p>
            <p class="text-lg font-bold text-orange-600 dark:text-orange-400">RezlyX v${escapeHtml(version)}</p>
            <p class="text-sm text-orange-500 dark:text-orange-400"><?= __('system.updates.available_short') ?></p>`;
    } else {
        iconDiv.className = 'w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mr-4 shrink-0';
        iconDiv.innerHTML = '<svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        textDiv.innerHTML = `<p class="text-xs text-zinc-500 dark:text-zinc-400 mb-0.5">${i18n.latestVersion}</p>
            <p class="text-lg font-bold text-green-600 dark:text-green-400">RezlyX v${escapeHtml(version)}</p>
            <p class="text-sm text-green-500 dark:text-green-400">${i18n.upToDate}</p>`;
    }
    console.log('[Updates] Latest version card updated:', version, hasUpdate ? '(update available)' : '(up to date)');
}

// 업데이트 가능 UI 렌더링
function renderUpdateAvailable(result) {
    const downloadBtn = result.download_url ? `
        <a href="${result.download_url}" target="_blank"
           class="px-3 py-2 border border-blue-600 text-blue-600 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-blue-900/30 text-sm font-medium rounded-lg transition flex items-center">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            ${i18n.download}
        </a>` : '';

    const notes = result.release_notes ? `
        <div class="text-sm text-blue-700 dark:text-blue-400 mt-3">
            <p class="font-medium mb-1">${i18n.releaseNotes}:</p>
            <div class="bg-white/50 dark:bg-zinc-800/50 p-3 rounded max-h-32 overflow-y-auto whitespace-pre-wrap">${escapeHtml(result.release_notes)}</div>
        </div>` : '';

    return `
    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="text-xs text-blue-600 dark:text-blue-400 mb-0.5">${i18n.latestVersion}</p>
                <p class="text-lg font-bold text-blue-900 dark:text-blue-300">v${result.latest_version}</p>
            </div>
        </div>
        <p class="text-sm text-blue-700 dark:text-blue-400 mb-3">${i18n.updateAvailableMsg}</p>
        ${notes}
        <div class="flex items-center gap-2 flex-wrap mt-4">
            ${downloadBtn}
            <button onclick="compareFiles('${result.latest_version}')"
                    class="px-3 py-2 border border-indigo-600 text-indigo-600 hover:bg-indigo-50 dark:border-indigo-400 dark:text-indigo-400 dark:hover:bg-indigo-900/30 text-sm font-medium rounded-lg transition flex items-center" id="compareBtn">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                ${i18n.compareFiles}
            </button>
            <button onclick="showUpdateModal('${result.latest_version}', \`${escapeHtml(result.release_notes || '')}\`)"
                    class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition flex items-center">
                ${refreshIcon()}
                ${i18n.updateNow}
            </button>
        </div>
    </div>`;
}

// ===== 변경 파일 비교 =====
async function compareFiles(version) {
    const compareBtn = document.getElementById('compareBtn');
    const section = document.getElementById('compareSection');
    const content = document.getElementById('compareContent');

    if (compareBtn) {
        compareBtn.disabled = true;
        compareBtn.innerHTML = spinnerIcon() + i18n.comparing;
    }

    try {
        const data = await ajaxPost('compare', '&version=' + encodeURIComponent(version));
        if (!data.success) throw new Error(data.data?.error || i18n.compareFailed);

        const result = data.data;
        if (result.total_files === 0) {
            content.innerHTML = successBox(i18n.noChanges);
        } else {
            content.innerHTML = renderCompareResult(result, version);
        }
        section.classList.remove('hidden');
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (error) {
        content.innerHTML = errorBox(error.message);
        section.classList.remove('hidden');
    } finally {
        if (compareBtn) {
            compareBtn.disabled = false;
            compareBtn.innerHTML = `<svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>${i18n.compareFiles}`;
        }
    }
}

function renderCompareResult(result, version) {
    const suggestFull = result.suggest_full || false;
    const sections = [];

    // 요약
    sections.push(`
        <div class="flex items-center justify-between mb-4 p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
            <div class="text-sm text-indigo-800 dark:text-indigo-300">
                <span class="font-semibold">${result.total_files}</span> 파일 변경 ·
                <span class="font-semibold">${result.total_commits}</span> 커밋
            </div>
            <div class="flex gap-2">
                ${suggestFull ? '' : `
                <button onclick="showPatchModal('${version}')"
                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    ${i18n.patchUpdate}
                </button>`}
                <button onclick="showUpdateModal('${version}', '')"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition flex items-center">
                    ${refreshIcon()} ${i18n.fullUpdate}
                </button>
            </div>
        </div>
    `);

    if (suggestFull) {
        sections.push(warningBox(i18n.tooManyChanges));
    }

    // 추가 파일
    if (result.added && result.added.length > 0) {
        sections.push(renderFileList(i18n.addedFiles, result.added, 'green', '+'));
    }
    // 수정 파일
    if (result.modified && result.modified.length > 0) {
        sections.push(renderFileList(i18n.modifiedFiles, result.modified, 'yellow', '~'));
    }
    // 삭제 파일
    if (result.removed && result.removed.length > 0) {
        sections.push(renderFileList(i18n.removedFiles, result.removed, 'red', '-'));
    }

    return sections.join('');
}

function renderFileList(title, files, color, prefix) {
    const colorMap = {
        green: { bg: 'bg-green-50 dark:bg-green-900/20', text: 'text-green-700 dark:text-green-400', badge: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' },
        yellow: { bg: 'bg-yellow-50 dark:bg-yellow-900/20', text: 'text-yellow-700 dark:text-yellow-400', badge: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' },
        red: { bg: 'bg-red-50 dark:bg-red-900/20', text: 'text-red-700 dark:text-red-400', badge: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' },
    };
    const c = colorMap[color];

    const fileItems = files.map(f => {
        const name = typeof f === 'string' ? f : f.filename;
        return `<div class="flex items-center justify-between py-1 px-2 rounded hover:${c.bg}">
            <span class="text-xs font-mono ${c.text}">${prefix} ${escapeHtml(name)}</span>
        </div>`;
    }).join('');

    return `
    <div class="mb-3">
        <div class="flex items-center mb-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${c.badge}">${title}</span>
            <span class="ml-2 text-xs text-zinc-500">(${files.length})</span>
        </div>
        <div class="max-h-48 overflow-y-auto border border-zinc-200 dark:border-zinc-700 rounded-lg p-2 space-y-0.5">${fileItems}</div>
    </div>`;
}

// ===== 업데이트 모달 (전체/패치 선택) =====
function showUpdateModal(version, notes) {
    const modal = document.getElementById('updateModal');
    const content = document.getElementById('modalContent');
    const footer = document.getElementById('modalFooter');
    latestVersion = version;

    content.innerHTML = `
        <p class="text-zinc-600 dark:text-zinc-400 mb-4">
            v${currentVersion} → <span class="font-bold text-blue-600">v${version}</span>
        </p>
        ${notes ? `
        <div class="bg-zinc-50 dark:bg-zinc-900 p-3 rounded-lg max-h-48 overflow-y-auto mb-4">
            <p class="text-xs text-zinc-500 mb-2">${i18n.releaseNotes}</p>
            <div class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">${notes}</div>
        </div>` : ''}
        <div class="space-y-3 mb-4">
            <label class="flex items-start p-3 border-2 border-blue-500 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-600 rounded-lg cursor-pointer">
                <input type="radio" name="updateMode" value="full" checked class="mt-0.5 mr-3 text-blue-600">
                <div>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">${i18n.fullUpdate}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">${i18n.fullDesc}</p>
                </div>
            </label>
            <label class="flex items-start p-3 border-2 border-zinc-200 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-indigo-400 transition" id="patchLabel">
                <input type="radio" name="updateMode" value="patch" class="mt-0.5 mr-3 text-indigo-600">
                <div>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">${i18n.patchUpdate}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">${i18n.patchDesc}</p>
                </div>
            </label>
        </div>
        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
            <p class="text-sm text-yellow-800 dark:text-yellow-400">
                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                ${i18n.confirmUpdate}
            </p>
        </div>
    `;

    // 라디오 버튼 스타일 변경
    content.querySelectorAll('input[name="updateMode"]').forEach(radio => {
        radio.addEventListener('change', function() {
            content.querySelectorAll('label').forEach(l => {
                l.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20', 'dark:border-blue-600',
                                   'border-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-900/20', 'dark:border-indigo-600');
                l.classList.add('border-zinc-200', 'dark:border-zinc-600');
            });
            const label = this.closest('label');
            const isFullMode = this.value === 'full';
            const c = isFullMode ? 'blue' : 'indigo';
            label.classList.remove('border-zinc-200', 'dark:border-zinc-600');
            label.classList.add(`border-${c}-500`, `bg-${c}-50`, `dark:bg-${c}-900/20`, `dark:border-${c}-600`);
        });
    });

    footer.innerHTML = `
        <button type="button" onclick="closeModal()"
                class="px-4 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition">
            <?= __('admin.buttons.cancel') ?>
        </button>
        <button type="button" id="modalConfirmBtn" onclick="executeUpdate()"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            ${i18n.updateNow}
        </button>
    `;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    console.log('[Updates] Modal opened for version:', version);
}

// 패치만 선택 모달
function showPatchModal(version) {
    const modal = document.getElementById('updateModal');
    const content = document.getElementById('modalContent');
    const footer = document.getElementById('modalFooter');
    latestVersion = version;

    content.innerHTML = `
        <p class="text-zinc-600 dark:text-zinc-400 mb-4">
            v${currentVersion} → <span class="font-bold text-indigo-600">v${version}</span>
        </p>
        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg mb-4">
            <p class="text-sm text-indigo-800 dark:text-indigo-300">${i18n.patchDesc}</p>
        </div>
        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
            <p class="text-sm text-yellow-800 dark:text-yellow-400">
                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                ${i18n.confirmPatch}
            </p>
        </div>
    `;

    footer.innerHTML = `
        <button type="button" onclick="closeModal()"
                class="px-4 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition">
            <?= __('admin.buttons.cancel') ?>
        </button>
        <button type="button" id="modalConfirmBtn" onclick="performPatchUpdate()"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
            ${i18n.patchUpdate}
        </button>
    `;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeModal() {
    const modal = document.getElementById('updateModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// 모달에서 선택한 모드에 따라 실행
function executeUpdate() {
    const mode = document.querySelector('input[name="updateMode"]:checked')?.value || 'full';
    console.log('[Updates] Execute update, mode:', mode);
    if (mode === 'patch') {
        performPatchUpdate();
    } else {
        performFullUpdate();
    }
}

// ===== 전체 업데이트 =====
async function performFullUpdate() {
    const btn = document.getElementById('modalConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = spinnerIcon() + i18n.processing;

    try {
        const data = await ajaxPost('perform', '&version=' + encodeURIComponent(latestVersion));
        closeModal();
        if (data.success) {
            showUpdateSuccess(data.data.message);
        } else {
            throw new Error(data.error || data.data?.error || i18n.updateFailed);
        }
    } catch (error) {
        closeModal();
        document.getElementById('updateStatus').innerHTML = errorBox(error.message);
    }
}

// ===== 변경분만 업데이트 =====
async function performPatchUpdate() {
    const btn = document.getElementById('modalConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = spinnerIcon() + i18n.processing;

    try {
        const data = await ajaxPost('patch', '&version=' + encodeURIComponent(latestVersion));
        closeModal();
        if (data.success) {
            showUpdateSuccess(data.data.message || i18n.patchUpdate + ' 완료');
        } else {
            throw new Error(data.error || data.data?.error || i18n.updateFailed);
        }
    } catch (error) {
        closeModal();
        document.getElementById('updateStatus').innerHTML = errorBox(error.message);
    }
}

function showUpdateSuccess(message) {
    const statusDiv = document.getElementById('updateStatus');
    statusDiv.innerHTML = `
        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <p class="text-sm font-medium text-green-800 dark:text-green-400">${escapeHtml(message)}</p>
            </div>
            <p class="mt-2 text-sm text-green-700 dark:text-green-300">${i18n.reloadPage}</p>
        </div>`;
    document.getElementById('compareSection').classList.add('hidden');
    setTimeout(() => location.reload(), 3000);
}

// ===== 백업 =====
async function loadBackups() {
    const listDiv = document.getElementById('backupList');
    try {
        const data = await ajaxPost('backups');
        if (!data.success || !data.data || data.data.length === 0) {
            listDiv.innerHTML = `
                <div class="text-center py-6 text-zinc-500 dark:text-zinc-400">
                    <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p>${i18n.noBackups}</p>
                </div>`;
            return;
        }
        listDiv.innerHTML = data.data.map(backup => `
            <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">v${backup.version}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">${backup.created_at} · ${formatFileSize(backup.size)}</p>
                </div>
                <div class="flex items-center gap-1">
                    <button onclick="restoreBackup('${backup.path}')"
                            class="px-3 py-1 text-xs text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded transition">
                        ${i18n.restore}
                    </button>
                    <button onclick="deleteBackup('${backup.path}')"
                            class="px-2 py-1 text-xs text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition"
                            title="${i18n.deleteBackup}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>`).join('');
    } catch (error) {
        listDiv.innerHTML = errorBox(error.message);
    }
}

async function deleteBackup(backupPath) {
    if (!confirm(i18n.confirmDelete)) return;
    try {
        const data = await ajaxPost('delete_backup', '&backup_path=' + encodeURIComponent(backupPath));
        if (data.success) {
            loadBackups();
        } else {
            alert(data.message || data.error || 'Delete failed');
        }
    } catch (error) {
        alert(error.message);
    }
}

async function restoreBackup(backupPath) {
    if (!confirm(i18n.confirmRestore)) return;
    try {
        const data = await ajaxPost('rollback', '&backup_path=' + encodeURIComponent(backupPath));
        if (data.success) {
            alert(data.data.message);
            location.reload();
        } else {
            throw new Error(data.error || i18n.restoreFailed);
        }
    } catch (error) {
        alert(error.message);
    }
}

// ===== 유틸리티 =====
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function spinnerIcon() {
    return '<svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
}

function refreshIcon() {
    return '<svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';
}

function successBox(msg) {
    return `<div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg flex items-center">
        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <p class="text-sm font-medium text-green-800 dark:text-green-400">${msg}</p>
    </div>`;
}

function warningBox(msg) {
    return `<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg flex items-center">
        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-3 shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <p class="text-sm font-medium text-yellow-800 dark:text-yellow-400">${msg}</p>
    </div>`;
}

function errorBox(msg) {
    return `<div class="p-4 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded-lg">${msg}</div>`;
}
</script>
