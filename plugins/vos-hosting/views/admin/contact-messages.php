<?php
/**
 * 관리자 - 문의 관리
 * 접수된 문의 목록 조회, 상세 보기, 답변 발송
 */
$pageTitle = '문의 관리 - ' . ($config['app_name'] ?? 'VosCMS') . ' Admin';
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// AJAX API 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    // 읽음 처리
    if ($action === 'mark_read') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE {$prefix}contact_messages SET is_read = 1 WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // 답변 발송
    if ($action === 'reply') {
        $id = (int)($_POST['id'] ?? 0);
        $replyBody = trim($_POST['reply_body'] ?? '');
        if (!$id || !$replyBody) { echo json_encode(['success' => false, 'message' => '내용을 입력하세요']); exit; }

        $msg = $pdo->prepare("SELECT * FROM {$prefix}contact_messages WHERE id = ?");
        $msg->execute([$id]);
        $msg = $msg->fetch(PDO::FETCH_ASSOC);
        if (!$msg) { echo json_encode(['success' => false, 'message' => '문의를 찾을 수 없습니다']); exit; }

        // 메일 발송
        require_once BASE_PATH . '/rzxlib/Core/Helpers/mail.php';
        $subject = 'Re: ' . ($msg['subject'] ?: $msg['category']);
        $sent = rzx_send_mail($pdo, $msg['email'], $subject, nl2br(htmlspecialchars($replyBody)), [
            'reply_to' => $siteSettings['mail_from_email'] ?? 'webmaster@thevos.jp',
        ]);

        // 답변 시간 기록
        $pdo->prepare("UPDATE {$prefix}contact_messages SET is_read = 1, replied_at = NOW() WHERE id = ?")->execute([$id]);

        echo json_encode(['success' => $sent, 'message' => $sent ? '답변이 발송되었습니다' : '메일 발송 실패']);
        exit;
    }

    // 삭제
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM {$prefix}contact_messages WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// 목록 조회
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;
$filter = $_GET['filter'] ?? '';

$where = '1=1';
$params = [];
if ($filter === 'unread') { $where .= ' AND is_read = 0'; }
elseif ($filter === 'replied') { $where .= ' AND replied_at IS NOT NULL'; }

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}contact_messages WHERE {$where}");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT * FROM {$prefix}contact_messages WHERE {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unreadCount = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}contact_messages WHERE is_read = 0")->fetchColumn();

$pageHeaderTitle = '문의 관리';
include BASE_PATH . '/resources/views/admin/reservations/_head.php';
?>

<!-- 필터 탭 -->
<div class="flex items-center justify-between mb-6">
    <div class="flex gap-2">
        <?php
        $filters = ['' => '전체 (' . $total . ')', 'unread' => '미읽음 (' . $unreadCount . ')', 'replied' => '답변완료'];
        foreach ($filters as $fk => $fl):
            $active = $filter === $fk;
        ?>
        <a href="?filter=<?= $fk ?>" class="px-4 py-2 text-sm font-medium rounded-lg transition <?= $active ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' ?>"><?= $fl ?></a>
        <?php endforeach; ?>
    </div>
</div>

<!-- 목록 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <?php if (empty($messages)): ?>
    <div class="p-12 text-center text-zinc-400">접수된 문의가 없습니다.</div>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-zinc-50 dark:bg-zinc-700/50 border-b dark:border-zinc-600">
                <th class="px-4 py-3 text-left font-semibold text-zinc-600 dark:text-zinc-300 w-8"></th>
                <th class="px-4 py-3 text-left font-semibold text-zinc-600 dark:text-zinc-300">분류</th>
                <th class="px-4 py-3 text-left font-semibold text-zinc-600 dark:text-zinc-300">이름</th>
                <th class="px-4 py-3 text-left font-semibold text-zinc-600 dark:text-zinc-300">이메일</th>
                <th class="px-4 py-3 text-left font-semibold text-zinc-600 dark:text-zinc-300">제목</th>
                <th class="px-4 py-3 text-left font-semibold text-zinc-600 dark:text-zinc-300">날짜</th>
                <th class="px-4 py-3 text-center font-semibold text-zinc-600 dark:text-zinc-300">상태</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $m):
                $isUnread = !$m['is_read'];
                $isReplied = !empty($m['replied_at']);
            ?>
            <tr class="border-b dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/30 cursor-pointer transition <?= $isUnread ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' ?>" onclick="openDetail(<?= $m['id'] ?>)">
                <td class="px-4 py-3"><?php if ($isUnread): ?><span class="w-2 h-2 bg-blue-500 rounded-full inline-block"></span><?php endif; ?></td>
                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-[10px] font-medium bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300"><?= htmlspecialchars($m['category'] ?: '-') ?></span></td>
                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white <?= $isUnread ? 'font-bold' : '' ?>"><?= htmlspecialchars($m['name']) ?></td>
                <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($m['email']) ?></td>
                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300 truncate max-w-xs <?= $isUnread ? 'font-semibold' : '' ?>"><?= htmlspecialchars($m['subject'] ?: '(제목 없음)') ?></td>
                <td class="px-4 py-3 text-zinc-400 text-xs"><?= date('m/d H:i', strtotime($m['created_at'])) ?></td>
                <td class="px-4 py-3 text-center">
                    <?php if ($isReplied): ?>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">답변완료</span>
                    <?php elseif ($isUnread): ?>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">새 문의</span>
                    <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-zinc-100 text-zinc-500">읽음</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- 페이지네이션 -->
    <?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-center gap-1 py-4 border-t dark:border-zinc-700">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="?page=<?= $p ?>&filter=<?= $filter ?>" class="px-3 py-1.5 text-xs rounded-lg <?= $p === $page ? 'bg-indigo-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- 상세 모달 -->
<div id="detailModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] flex flex-col border border-zinc-200 dark:border-zinc-700">
        <div class="flex items-center justify-between px-6 py-4 border-b dark:border-zinc-700">
            <h3 class="text-lg font-bold text-zinc-900 dark:text-white">문의 상세</h3>
            <button onclick="closeDetail()" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="detailContent" class="flex-1 overflow-y-auto px-6 py-4"></div>
        <!-- 답변 폼 -->
        <div class="px-6 py-4 border-t dark:border-zinc-700">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">답변</label>
            <textarea id="replyBody" rows="3" class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white resize-y" placeholder="답변 내용을 입력하세요. 상대방 이메일로 발송됩니다."></textarea>
            <div class="flex justify-between items-center mt-3">
                <button onclick="deleteMsg()" class="px-3 py-2 text-xs text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">삭제</button>
                <button onclick="sendReply()" id="replyBtn" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    답변 발송
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 메시지 데이터 (JS용) -->
<script>
var messages = <?= json_encode(array_map(function($m) {
    return [
        'id' => $m['id'], 'name' => $m['name'], 'email' => $m['email'],
        'category' => $m['category'], 'subject' => $m['subject'],
        'message' => $m['message'], 'locale' => $m['locale'],
        'ip' => $m['ip_address'], 'created_at' => $m['created_at'],
        'is_read' => $m['is_read'], 'replied_at' => $m['replied_at'],
    ];
}, $messages), JSON_UNESCAPED_UNICODE) ?>;

var currentMsgId = 0;
var apiUrl = window.location.pathname;

function openDetail(id) {
    var m = messages.find(function(x) { return x.id === id; });
    if (!m) return;
    currentMsgId = id;

    var h = '<div class="space-y-4">';
    h += '<div class="grid grid-cols-2 gap-4 text-sm">';
    h += '<div><span class="text-zinc-400">이름</span><p class="font-medium text-zinc-900 dark:text-white">' + esc(m.name) + '</p></div>';
    h += '<div><span class="text-zinc-400">이메일</span><p class="font-medium"><a href="mailto:' + esc(m.email) + '" class="text-indigo-600 dark:text-indigo-400">' + esc(m.email) + '</a></p></div>';
    h += '<div><span class="text-zinc-400">분류</span><p>' + esc(m.category || '-') + '</p></div>';
    h += '<div><span class="text-zinc-400">날짜</span><p>' + esc(m.created_at) + '</p></div>';
    h += '</div>';
    if (m.subject) h += '<div><span class="text-zinc-400 text-sm">제목</span><p class="font-semibold text-zinc-900 dark:text-white">' + esc(m.subject) + '</p></div>';
    h += '<div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-xl p-4 text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap leading-relaxed">' + esc(m.message) + '</div>';
    if (m.replied_at) h += '<div class="text-xs text-green-600 dark:text-green-400">✓ 답변 완료: ' + esc(m.replied_at) + '</div>';
    h += '<div class="text-[10px] text-zinc-400">IP: ' + esc(m.ip || '-') + ' · Locale: ' + esc(m.locale || '-') + '</div>';
    h += '</div>';

    document.getElementById('detailContent').innerHTML = h;
    document.getElementById('replyBody').value = '';
    document.getElementById('detailModal').classList.remove('hidden');

    // 읽음 처리
    if (!m.is_read) {
        fetch(apiUrl, { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'action=mark_read&id=' + id });
        m.is_read = 1;
    }
}

function closeDetail() {
    document.getElementById('detailModal').classList.add('hidden');
}

async function sendReply() {
    var body = document.getElementById('replyBody').value.trim();
    if (!body) { alert('답변 내용을 입력하세요'); return; }
    var btn = document.getElementById('replyBtn');
    btn.disabled = true; btn.textContent = '발송 중...';

    var fd = new FormData();
    fd.append('action', 'reply');
    fd.append('id', currentMsgId);
    fd.append('reply_body', body);

    try {
        var res = await fetch(apiUrl, { method: 'POST', body: fd });
        var data = await res.json();
        if (data.success) {
            alert(data.message);
            closeDetail();
            location.reload();
        } else {
            alert(data.message || '실패');
        }
    } catch(e) { alert('네트워크 오류'); }
    btn.disabled = false; btn.textContent = '답변 발송';
}

async function deleteMsg() {
    if (!confirm('이 문의를 삭제하시겠습니까?')) return;
    await fetch(apiUrl, { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'action=delete&id=' + currentMsgId });
    closeDetail();
    location.reload();
}

function esc(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

document.getElementById('detailModal').addEventListener('click', function(e) { if (e.target === this) closeDetail(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeDetail(); });
</script>
<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
