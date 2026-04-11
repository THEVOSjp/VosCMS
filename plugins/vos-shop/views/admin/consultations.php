<?php
/**
 * 관리자 - 1:1 상담 관리
 * 메신저 스타일 채팅 UI + 첨부파일 지원
 */
$pageTitle = __('admin.nav.shops_consultations') ?? '1:1 상담';
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$adminId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? '';

// AJAX 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';
    $shopId = (int)($_POST['shop_id'] ?? 0);

    try {
        if ($action === 'get_messages') {
            $msgs = $pdo->prepare("SELECT i.*, u.name as user_name FROM {$prefix}shop_inquiries i LEFT JOIN {$prefix}users u ON i.user_id = u.id WHERE i.shop_id = ? AND i.is_public = 0 ORDER BY i.created_at ASC");
            $msgs->execute([$shopId]);
            $shop = $pdo->prepare("SELECT name, slug, contact_person, representative, user_id FROM {$prefix}shops WHERE id = ?");
            $shop->execute([$shopId]);
            echo json_encode(['success' => true, 'messages' => $msgs->fetchAll(PDO::FETCH_ASSOC), 'shop' => $shop->fetch(PDO::FETCH_ASSOC)]);
        } elseif ($action === 'send') {
            $msg = trim($_POST['message'] ?? '');
            if (!$shopId) { echo json_encode(['error' => 'Shop required']); exit; }

            // 첨부파일 처리
            $attachments = [];
            if (!empty($_FILES['files'])) {
                $uploadDir = BASE_PATH . '/storage/uploads/consultations/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
                    if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    if (count($attachments) >= 5) break;
                    $origName = $_FILES['files']['name'][$i];
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    $size = $_FILES['files']['size'][$i];
                    $filename = 'consult_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $uploadDir . $filename)) {
                        $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                        $attachments[] = ['name' => $origName, 'path' => '/storage/uploads/consultations/' . $filename, 'type' => $isImage ? 'image' : 'file', 'size' => $size];
                    }
                }
            }

            if (!$msg && empty($attachments)) { echo json_encode(['error' => 'Message or file required']); exit; }

            // 기존 pending 메시지를 answered로 업데이트
            $pdo->prepare("UPDATE {$prefix}shop_inquiries SET status = 'answered' WHERE shop_id = ? AND is_public = 0 AND status = 'pending'")->execute([$shopId]);
            $pdo->prepare("INSERT INTO {$prefix}shop_inquiries (shop_id, user_id, question, attachments, is_public, status) VALUES (?, ?, ?, ?, 0, 'answered')")
                ->execute([$shopId, $adminId, $msg ?: '', !empty($attachments) ? json_encode($attachments) : null]);
            echo json_encode(['success' => true]);
        } elseif ($action === 'reply') {
            $qaId = (int)($_POST['qa_id'] ?? 0);
            $answer = trim($_POST['answer'] ?? '');
            if ($qaId && $answer) {
                $pdo->prepare("UPDATE {$prefix}shop_inquiries SET answer = ?, answered_by = ?, answered_at = NOW(), status = 'answered' WHERE id = ?")->execute([$answer, $adminId, $qaId]);
                echo json_encode(['success' => true]);
            }
        }
    } catch (\Throwable $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// 필터
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$where = ["EXISTS (SELECT 1 FROM {$prefix}shop_inquiries i WHERE i.shop_id = s.id AND i.is_public = 0)"];
$params = [];
if ($filter === 'pending') {
    $where[] = "EXISTS (SELECT 1 FROM {$prefix}shop_inquiries i WHERE i.shop_id = s.id AND i.is_public = 0 AND i.status = 'pending')";
}
if ($search) {
    $where[] = "(s.name LIKE ? OR s.contact_person LIKE ? OR s.representative LIKE ?)";
    $params = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%"]);
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$totalAll = 0; $totalPending = 0;
try {
    $totalAll = (int)$pdo->query("SELECT COUNT(DISTINCT shop_id) FROM {$prefix}shop_inquiries WHERE is_public = 0")->fetchColumn();
    $totalPending = (int)$pdo->query("SELECT COUNT(DISTINCT shop_id) FROM {$prefix}shop_inquiries WHERE is_public = 0 AND status = 'pending'")->fetchColumn();
} catch (\Throwable $e) {}

$stmt = $pdo->prepare("
    SELECT s.id, s.name, s.slug, s.contact_person, s.representative, s.status as shop_status,
           (SELECT COUNT(*) FROM {$prefix}shop_inquiries i WHERE i.shop_id = s.id AND i.is_public = 0) as total_msgs,
           (SELECT COUNT(*) FROM {$prefix}shop_inquiries i WHERE i.shop_id = s.id AND i.is_public = 0 AND i.status = 'pending') as pending_msgs,
           (SELECT MAX(i.created_at) FROM {$prefix}shop_inquiries i WHERE i.shop_id = s.id AND i.is_public = 0) as last_msg_at,
           (SELECT i.question FROM {$prefix}shop_inquiries i WHERE i.shop_id = s.id AND i.is_public = 0 ORDER BY i.created_at DESC LIMIT 1) as last_msg
    FROM {$prefix}shops s {$whereSQL}
    ORDER BY pending_msgs DESC, last_msg_at DESC
");
$stmt->execute($params);
$shops = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageHeaderTitle = $pageTitle;
$pageSubTitle = '';
$pageSubDesc = '사업장 운영자와의 비공개 상담';
include BASE_PATH . '/resources/views/admin/reservations/_head.php';
?>

<style>
.consult-wrap { display:flex; gap:1rem; height: calc(100vh - 180px); min-height:500px; }
.consult-list { width:320px; flex-shrink:0; display:flex; flex-direction:column; }
.consult-chat { flex:1; display:flex; flex-direction:column; }
.shop-item.active { background: var(--active-bg, #eff6ff); }
.dark .shop-item.active { background: rgba(59,130,246,0.15); }
.msg-area { flex:1; overflow-y:auto; padding:1.5rem; }
.msg-row { display:flex; margin-bottom:0.75rem; }
.msg-row.mine { justify-content:flex-end; }
.msg-row.theirs { justify-content:flex-start; }
.msg-bubble { max-width:70%; padding:0.75rem 1rem; border-radius:1rem; position:relative; }
.msg-row.mine .msg-bubble { background:#6366f1; color:#fff; border-bottom-right-radius:0.25rem; }
.msg-row.theirs .msg-bubble { background:#f4f4f5; color:#18181b; border-bottom-left-radius:0.25rem; }
.dark .msg-row.theirs .msg-bubble { background:#3f3f46; color:#e4e4e7; }
.msg-meta { font-size:10px; margin-top:4px; opacity:0.6; }
.msg-row.mine .msg-meta { text-align:right; color:rgba(255,255,255,0.7); }
.msg-sender { font-size:11px; font-weight:600; margin-bottom:2px; }
.msg-row.theirs .msg-sender { color:#3b82f6; }
.dark .msg-row.theirs .msg-sender { color:#60a5fa; }
.msg-text { font-size:14px; line-height:1.5; white-space:pre-wrap; word-break:break-word; }
.msg-attach { margin-top:0.5rem; }
.msg-attach img { max-width:240px; max-height:180px; border-radius:0.5rem; cursor:pointer; }
.msg-attach a { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; background:rgba(0,0,0,0.08); border-radius:6px; font-size:12px; text-decoration:none; color:inherit; }
.dark .msg-attach a { background:rgba(255,255,255,0.1); }
.msg-input-area { border-top:1px solid #e4e4e7; padding:0.75rem 1rem; }
.dark .msg-input-area { border-color:#3f3f46; }
.file-preview { display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.5rem; }
.file-preview-item { position:relative; }
.file-preview-item img { width:60px; height:60px; object-fit:cover; border-radius:8px; border:1px solid #e4e4e7; }
.file-preview-item .file-icon { width:60px; height:60px; border-radius:8px; border:1px solid #e4e4e7; display:flex; align-items:center; justify-content:center; background:#f9fafb; font-size:10px; text-align:center; padding:4px; word-break:break-all; }
.dark .file-preview-item .file-icon { background:#27272a; border-color:#3f3f46; }
.file-preview-item .remove-btn { position:absolute; top:-6px; right:-6px; width:18px; height:18px; background:#ef4444; color:#fff; border-radius:50%; font-size:10px; display:flex; align-items:center; justify-content:center; cursor:pointer; border:none; }
.pending-dot { width:8px; height:8px; background:#ef4444; border-radius:50%; flex-shrink:0; }
@media (max-width:1024px) { .consult-wrap { flex-direction:column; height:auto; } .consult-list { width:100%; max-height:300px; } }
</style>

<div class="max-w-7xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white"><?= $pageTitle ?></h1>
        </div>
        <div class="flex gap-2">
            <a href="?filter=all" class="px-3 py-1.5 text-xs rounded-lg transition <?= $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' ?>">전체 (<?= $totalAll ?>)</a>
            <a href="?filter=pending" class="px-3 py-1.5 text-xs rounded-lg transition <?= $filter === 'pending' ? 'bg-red-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300' ?>">미답변 (<?= $totalPending ?>)</a>
        </div>
    </div>

    <div class="consult-wrap">
        <!-- 사업장 리스트 -->
        <div class="consult-list bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
                <form method="GET" class="flex gap-1">
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="검색..."
                           class="flex-1 px-3 py-1.5 text-xs border border-zinc-200 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                </form>
            </div>
            <div class="flex-1 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-700">
                <?php if (empty($shops)): ?>
                <p class="px-4 py-8 text-xs text-zinc-400 text-center">상담 내역 없음</p>
                <?php else: foreach ($shops as $idx => $s): ?>
                <button onclick="selectShop(<?= $s['id'] ?>, this)" data-shop-id="<?= $s['id'] ?>"
                        class="shop-item w-full text-left px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                    <div class="flex items-center gap-2">
                        <?php if ($s['pending_msgs'] > 0): ?><span class="pending-dot"></span><?php endif; ?>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($s['name']) ?></p>
                            <p class="text-[11px] text-zinc-400 truncate"><?= htmlspecialchars(mb_substr($s['last_msg'] ?? '', 0, 30)) ?></p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-[10px] text-zinc-400"><?= $s['last_msg_at'] ? date('m/d', strtotime($s['last_msg_at'])) : '' ?></p>
                            <?php if ($s['pending_msgs'] > 0): ?>
                            <span class="inline-flex items-center justify-center mt-0.5 w-5 h-5 bg-red-500 text-white text-[9px] font-bold rounded-full"><?= $s['pending_msgs'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </button>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- 채팅 영역 -->
        <div class="consult-chat bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <div>
                    <p id="chatName" class="text-sm font-semibold text-zinc-900 dark:text-white">사업장을 선택하세요</p>
                    <p id="chatContact" class="text-[11px] text-zinc-400"></p>
                </div>
                <a id="chatLink" href="#" target="_blank" class="hidden text-[11px] text-blue-600 hover:underline">페이지 보기 →</a>
            </div>
            <div id="chatMessages" class="msg-area">
                <p class="text-sm text-zinc-300 dark:text-zinc-600 text-center mt-20">왼쪽에서 사업장을 선택하세요</p>
            </div>
            <div id="chatInputWrap" class="msg-input-area hidden">
                <div id="filePreview" class="file-preview"></div>
                <form id="chatForm" onsubmit="return sendMsg(event)" class="flex items-end gap-2">
                    <label class="cursor-pointer flex-shrink-0 p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition" title="파일 첨부">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <input type="file" multiple class="hidden" onchange="previewFiles(this)" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                    </label>
                    <input type="text" id="chatInput" placeholder="메시지를 입력하세요..."
                           class="flex-1 px-4 py-2 text-sm border border-zinc-200 dark:border-zinc-600 rounded-xl bg-zinc-50 dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-zinc-600">
                    <button type="submit" class="flex-shrink-0 p-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
var currentShopId = 0;
var pendingFiles = [];
var adminId = '<?= $adminId ?>';
var siteUrl = '<?= $baseUrl ?>';

function selectShop(shopId, btn) {
    currentShopId = shopId;
    document.querySelectorAll('.shop-item').forEach(function(el) { el.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById('chatInputWrap').classList.remove('hidden');
    clearFiles();
    loadMessages(shopId);
}

function loadMessages(shopId) {
    var container = document.getElementById('chatMessages');
    container.innerHTML = '<p class="text-sm text-zinc-300 text-center mt-20">...</p>';
    var fd = new FormData();
    fd.append('action', 'get_messages');
    fd.append('shop_id', shopId);
    fetch(location.pathname + location.search, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
    .then(function(r){return r.json()}).then(function(d) {
        if (!d.success) return;
        var shop = d.shop || {};
        document.getElementById('chatName').textContent = shop.name || '';
        document.getElementById('chatContact').textContent = (shop.contact_person || shop.representative || '') + (shop.contact_person ? ' (담당자)' : shop.representative ? ' (대표)' : '');
        if (shop.slug) {
            var link = document.getElementById('chatLink');
            link.href = siteUrl + '/shop/' + shop.slug;
            link.classList.remove('hidden');
        }
        var msgs = d.messages || [];
        if (!msgs.length) { container.innerHTML = '<p class="text-sm text-zinc-300 dark:text-zinc-600 text-center mt-20">아직 상담 내역이 없습니다.</p>'; return; }
        var ownerId = shop.user_id || '';
        var html = '';
        var lastDate = '';
        msgs.forEach(function(m) {
            // 날짜 구분선
            var msgDate = (m.created_at || '').substring(0,10);
            if (msgDate !== lastDate) {
                html += '<div class="text-center my-4"><span class="text-[10px] text-zinc-400 bg-zinc-100 dark:bg-zinc-700 px-3 py-1 rounded-full">' + msgDate + '</span></div>';
                lastDate = msgDate;
            }
            // 사업장 운영자가 보낸 메시지 = 왼쪽(theirs), 관리자가 보낸 메시지 = 오른쪽(mine)
            var isFromOwner = m.user_id === ownerId;
            var side = isFromOwner ? 'theirs' : 'mine';
            html += '<div class="msg-row ' + side + '">';
            html += '<div class="msg-bubble">';
            if (isFromOwner) html += '<div class="msg-sender">' + esc(m.user_name || '사업장') + '</div>';
            if (m.question) html += '<div class="msg-text">' + esc(m.question).replace(/\n/g,'<br>') + '</div>';
            // 첨부파일
            var att = [];
            try { att = JSON.parse(m.attachments) || []; } catch(e) {}
            if (att.length) {
                html += '<div class="msg-attach">';
                att.forEach(function(f) {
                    if (f.type === 'image') {
                        html += '<img src="' + siteUrl + f.path + '" onclick="window.open(this.src)" alt="' + esc(f.name) + '">';
                    } else {
                        var sizeStr = f.size > 1048576 ? (f.size/1048576).toFixed(1)+'MB' : Math.round(f.size/1024)+'KB';
                        html += '<a href="' + siteUrl + f.path + '" target="_blank">📎 ' + esc(f.name) + ' (' + sizeStr + ')</a>';
                    }
                });
                html += '</div>';
            }
            // 기존 Q&A 방식 답변 (고객 Q&A에서 넘어온 것)
            if (m.answer) {
                html += '<div style="margin-top:8px;padding-top:8px;border-top:1px solid rgba(128,128,128,0.2)">';
                html += '<div class="msg-sender" style="color:#6366f1">관리자 답변</div>';
                html += '<div class="msg-text">' + esc(m.answer).replace(/\n/g,'<br>') + '</div>';
                html += '</div>';
            }
            html += '<div class="msg-meta">' + (m.created_at || '').substring(11,16) + '</div>';
            html += '</div></div>';
        });
        container.innerHTML = html;
        container.scrollTop = container.scrollHeight;
    });
}

function sendMsg(e) {
    if (e) e.preventDefault();
    var input = document.getElementById('chatInput');
    var msg = input.value.trim();
    if (!msg && !pendingFiles.length) return false;
    if (!currentShopId) return false;
    var fd = new FormData();
    fd.append('action', 'send');
    fd.append('shop_id', currentShopId);
    fd.append('message', msg);
    pendingFiles.forEach(function(f) { fd.append('files[]', f); });
    fetch(location.pathname + location.search, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
    .then(function(r){return r.json()}).then(function(d) {
        if (d.success) { input.value = ''; clearFiles(); loadMessages(currentShopId); }
        else alert(d.error || 'Error');
    });
    return false;
}

function replyMsg(qaId) {
    var input = document.getElementById('reply_' + qaId);
    if (!input) return;
    var answer = input.value.trim();
    if (!answer) return;
    var fd = new FormData();
    fd.append('action', 'reply');
    fd.append('shop_id', currentShopId);
    fd.append('qa_id', qaId);
    fd.append('answer', answer);
    fetch(location.pathname + location.search, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
    .then(function(r){return r.json()}).then(function(d) {
        if (d.success) loadMessages(currentShopId);
    });
}

// 파일 미리보기
function previewFiles(input) {
    var files = input.files;
    for (var i = 0; i < files.length; i++) {
        if (pendingFiles.length >= 5) break;
        pendingFiles.push(files[i]);
    }
    renderFilePreviews();
    input.value = '';
}

function renderFilePreviews() {
    var container = document.getElementById('filePreview');
    container.innerHTML = '';
    pendingFiles.forEach(function(f, idx) {
        var div = document.createElement('div');
        div.className = 'file-preview-item';
        if (f.type.startsWith('image/')) {
            var img = document.createElement('img');
            var reader = new FileReader();
            reader.onload = function(e) { img.src = e.target.result; };
            reader.readAsDataURL(f);
            div.appendChild(img);
        } else {
            var icon = document.createElement('div');
            icon.className = 'file-icon';
            icon.textContent = f.name.length > 12 ? f.name.substring(0,12) + '...' : f.name;
            div.appendChild(icon);
        }
        var btn = document.createElement('button');
        btn.className = 'remove-btn';
        btn.innerHTML = '&times;';
        btn.onclick = function() { pendingFiles.splice(idx,1); renderFilePreviews(); };
        div.appendChild(btn);
        container.appendChild(div);
    });
}

function clearFiles() { pendingFiles = []; document.getElementById('filePreview').innerHTML = ''; }
function esc(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

<?php if (!empty($shops)): ?>
document.addEventListener('DOMContentLoaded', function() {
    var first = document.querySelector('.shop-item');
    if (first) selectShop(<?= $shops[0]['id'] ?>, first);
});
<?php endif; ?>
</script>

</div>
