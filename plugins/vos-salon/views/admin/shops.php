<?php
/**
 * 관리자 - 업소 관리 (승인/거절/목록)
 */
$pageTitle = __('admin.shops.title') ?? '업소 관리';
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

// AJAX 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';
    $shopId = (int)($_POST['shop_id'] ?? 0);

    if (!$shopId) { echo json_encode(['error' => 'Shop ID required']); exit; }

    try {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE {$prefix}shops SET status = 'active', verified_at = NOW(), is_verified = 1 WHERE id = ?")->execute([$shopId]);
            echo json_encode(['success' => true, 'message' => '승인되었습니다.']);
        } elseif ($action === 'reject') {
            $reason = trim($_POST['reason'] ?? '');
            $pdo->prepare("UPDATE {$prefix}shops SET status = 'rejected' WHERE id = ?")->execute([$shopId]);
            echo json_encode(['success' => true, 'message' => '거절되었습니다.']);
        } elseif ($action === 'suspend') {
            $pdo->prepare("UPDATE {$prefix}shops SET status = 'suspended' WHERE id = ?")->execute([$shopId]);
            echo json_encode(['success' => true, 'message' => '정지되었습니다.']);
        } elseif ($action === 'activate') {
            $pdo->prepare("UPDATE {$prefix}shops SET status = 'active' WHERE id = ?")->execute([$shopId]);
            echo json_encode(['success' => true, 'message' => '활성화되었습니다.']);
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM {$prefix}shops WHERE id = ?")->execute([$shopId]);
            echo json_encode(['success' => true, 'message' => '삭제되었습니다.']);
        } elseif ($action === 'get_consultation') {
            $msgs = $pdo->prepare("SELECT i.*, u.name as user_name FROM {$prefix}shop_inquiries i LEFT JOIN {$prefix}users u ON i.user_id = u.id WHERE i.shop_id = ? AND i.is_public = 0 ORDER BY i.created_at ASC");
            $msgs->execute([$shopId]);
            $shop = $pdo->prepare("SELECT name, contact_person, representative, user_id FROM {$prefix}shops WHERE id = ?");
            $shop->execute([$shopId]);
            echo json_encode(['success' => true, 'messages' => $msgs->fetchAll(PDO::FETCH_ASSOC), 'shop' => $shop->fetch(PDO::FETCH_ASSOC)]);
        } elseif ($action === 'send_consultation') {
            $msg = trim($_POST['message'] ?? '');
            if ($msg) {
                $adminId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? '';
                $pdo->prepare("UPDATE {$prefix}shop_inquiries SET status = 'answered' WHERE shop_id = ? AND is_public = 0 AND status = 'pending'")->execute([$shopId]);
                $pdo->prepare("INSERT INTO {$prefix}shop_inquiries (shop_id, user_id, question, is_public, status) VALUES (?, ?, ?, 0, 'answered')")->execute([$shopId, $adminId, $msg]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Message required']);
            }
        } elseif ($action === 'reply_consultation') {
            $qaId = (int)($_POST['qa_id'] ?? 0);
            $answer = trim($_POST['answer'] ?? '');
            if ($qaId && $answer) {
                $adminId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? '';
                $pdo->prepare("UPDATE {$prefix}shop_inquiries SET answer = ?, answered_by = ?, answered_at = NOW(), status = 'answered' WHERE id = ? AND shop_id = ?")->execute([$answer, $adminId, $qaId, $shopId]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Answer required']);
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
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($filter === 'pending') { $where[] = "s.status = 'pending'"; }
elseif ($filter === 'active') { $where[] = "s.status = 'active'"; }
elseif ($filter === 'rejected') { $where[] = "s.status = 'rejected'"; }
elseif ($filter === 'suspended') { $where[] = "s.status = 'suspended'"; }

if ($search) {
    $where[] = "(s.name LIKE ? OR s.email LIKE ? OR s.business_number LIKE ?)";
    $params = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%"]);
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// 카운트
$counts = [];
foreach (['all', 'pending', 'active', 'rejected', 'suspended'] as $st) {
    $cw = $st === 'all' ? '' : "WHERE s.status = '{$st}'";
    $counts[$st] = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}shops s {$cw}")->fetchColumn();
}

$total = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}shops s {$whereSQL}");
$total->execute($params);
$totalCount = (int)$total->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));

$stmt = $pdo->prepare("SELECT s.*, c.name as category_name, c.slug as category_slug, u.email as user_email, u.name as user_name FROM {$prefix}shops s LEFT JOIN {$prefix}shop_categories c ON s.category_id = c.id LEFT JOIN {$prefix}users u ON s.user_id = u.id {$whereSQL} ORDER BY FIELD(s.status, 'pending', 'active', 'suspended', 'rejected'), s.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$shops = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentLocale = $config['locale'] ?? 'ko';

// 사업장별 미답변 상담 수
$pendingConsults = [];
try {
    $pcStmt = $pdo->query("SELECT shop_id, COUNT(*) as cnt FROM {$prefix}shop_inquiries WHERE is_public = 0 AND status = 'pending' GROUP BY shop_id");
    foreach ($pcStmt->fetchAll(PDO::FETCH_ASSOC) as $pc) { $pendingConsults[(int)$pc['shop_id']] = (int)$pc['cnt']; }
} catch (\Throwable $e) {}

$statusLabels = [
    'pending' => ['label' => '대기', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'],
    'active' => ['label' => '활성', 'class' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'],
    'rejected' => ['label' => '거절', 'class' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'],
    'suspended' => ['label' => '정지', 'class' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400'],
];
?>
<?php
$pageHeaderTitle = $pageTitle;
$pageSubTitle = '';
$pageSubDesc = __('admin.shops.description') ?? '등록된 매장을 관리하고 승인합니다.';
include BASE_PATH . '/resources/views/admin/reservations/_head.php';
?>

        <div class="max-w-7xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= $pageTitle ?></h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.shops.description') ?? '등록된 매장을 관리하고 승인합니다.' ?></p>
        </div>
    </div>

    <!-- 필터 탭 -->
    <div class="flex gap-2 mb-4">
        <?php foreach (['all' => '전체', 'pending' => '⏳ 대기', 'active' => '✅ 활성', 'rejected' => '❌ 거절', 'suspended' => '⚠️ 정지'] as $fk => $fl): ?>
        <a href="?filter=<?= $fk ?>" class="px-3 py-1.5 text-sm rounded-lg transition <?= $filter === $fk ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200' ?>">
            <?= $fl ?> (<?= $counts[$fk] ?>)
        </a>
        <?php endforeach; ?>
    </div>

    <!-- 검색 -->
    <form method="GET" class="flex gap-2 mb-6">
        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="매장명, 이메일, 사업자번호 검색" class="flex-1 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-sm text-zinc-900 dark:text-white">
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">검색</button>
    </form>

    <!-- 목록 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">매장</th>
                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">업종</th>
                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">등록자</th>
                    <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">서류</th>
                    <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">상태</th>
                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">등록일</th>
                    <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">관리</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                <?php if (empty($shops)): ?>
                <tr><td colspan="7" class="px-4 py-12 text-center text-zinc-400">등록된 매장이 없습니다.</td></tr>
                <?php else: foreach ($shops as $s):
                    $st = $statusLabels[$s['status']] ?? $statusLabels['pending'];
                    $catNames = json_decode($s['category_name'] ?? '{}', true);
                    $catLabel = $catNames[$currentLocale] ?? $catNames['en'] ?? $catNames['ko'] ?? $s['category_slug'] ?? '';
                    $hasLicense = !empty($s['business_license']);
                ?>
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                    <td class="px-4 py-3">
                        <div>
                            <a href="<?= $baseUrl ?>/shop/<?= htmlspecialchars($s['slug']) ?>" target="_blank" class="font-medium text-zinc-900 dark:text-white hover:text-blue-600"><?= htmlspecialchars($s['name']) ?></a>
                            <p class="text-[10px] text-zinc-400 mt-0.5"><?= htmlspecialchars($s['address'] ?? '') ?></p>
                            <?php if ($s['representative'] || $s['business_number']): ?>
                            <p class="text-[10px] text-zinc-400"><?= htmlspecialchars($s['representative'] ?? '') ?> <?= $s['business_number'] ? '· ' . htmlspecialchars($s['business_number']) : '' ?></p>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded"><?= htmlspecialchars($catLabel) ?></span>
                    </td>
                    <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                        <?= htmlspecialchars($s['user_name'] ?? '') ?><br>
                        <span class="text-zinc-400"><?= htmlspecialchars($s['user_email'] ?? '') ?></span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <?php if ($hasLicense): ?>
                        <button onclick="viewLicense(<?= $s['id'] ?>, '<?= htmlspecialchars($baseUrl . $s['business_license']) ?>')" class="px-2 py-1 text-xs bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded hover:bg-amber-200 transition">📄 보기</button>
                        <?php else: ?>
                        <span class="text-xs text-zinc-300">미제출</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex px-2 py-0.5 text-[11px] font-medium rounded-full <?= $st['class'] ?>"><?= $st['label'] ?></span>
                    </td>
                    <td class="px-4 py-3 text-xs text-zinc-500"><?= date('Y-m-d', strtotime($s['created_at'])) ?></td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <?php if ($s['status'] === 'pending'): ?>
                            <button onclick="shopAction(<?= $s['id'] ?>, 'approve')" class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700 transition">승인</button>
                            <button onclick="shopAction(<?= $s['id'] ?>, 'reject')" class="px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 transition">거절</button>
                            <?php elseif ($s['status'] === 'active'): ?>
                            <button onclick="shopAction(<?= $s['id'] ?>, 'suspend')" class="px-2 py-1 text-xs bg-amber-500 text-white rounded hover:bg-amber-600 transition">정지</button>
                            <?php elseif ($s['status'] === 'suspended' || $s['status'] === 'rejected'): ?>
                            <button onclick="shopAction(<?= $s['id'] ?>, 'activate')" class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700 transition">활성화</button>
                            <?php endif; ?>
                            <button onclick="openConsultation(<?= $s['id'] ?>)" class="relative px-2 py-1 text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-300 rounded hover:bg-indigo-200 transition" title="1:1 상담">
                                💬<?php if (!empty($pendingConsults[$s['id']])): ?><span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[9px] rounded-full flex items-center justify-center"><?= $pendingConsults[$s['id']] ?></span><?php endif; ?>
                            </button>
                            <button onclick="if(confirm('정말 삭제하시겠습니까?')) shopAction(<?= $s['id'] ?>, 'delete')" class="px-2 py-1 text-xs bg-zinc-200 dark:bg-zinc-600 text-zinc-600 dark:text-zinc-300 rounded hover:bg-zinc-300 transition">삭제</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 페이지네이션 -->
    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center gap-1 mt-6">
        <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="px-3 py-1.5 text-sm rounded-lg <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- 사업자등록증 모달 -->
<div id="licenseModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl max-w-2xl max-h-[80vh] overflow-auto p-6" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">📄 사업자등록증</h3>
            <button onclick="document.getElementById('licenseModal').classList.add('hidden')" class="text-zinc-400 hover:text-zinc-600 text-xl">&times;</button>
        </div>
        <div id="licenseContent" class="text-center">
            <!-- JS로 이미지/PDF 표시 -->
        </div>
    </div>
</div>

<!-- 1:1 상담 모달 -->
<div id="consultModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl w-full max-w-lg mx-4 flex flex-col" style="max-height:80vh" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">💬 1:1 상담</h3>
                <p id="consultShopName" class="text-xs text-zinc-400"></p>
            </div>
            <button onclick="document.getElementById('consultModal').classList.add('hidden')" class="text-zinc-400 hover:text-zinc-600 text-xl">&times;</button>
        </div>
        <div id="consultMessages" class="flex-1 overflow-y-auto px-6 py-4 space-y-3" style="min-height:200px">
            <p class="text-sm text-zinc-400 text-center py-8">로딩 중...</p>
        </div>
        <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
            <div class="flex gap-2">
                <input type="text" id="consultInput" placeholder="메시지를 입력하세요"
                       class="flex-1 px-4 py-2.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();sendConsultation()}">
                <button onclick="sendConsultation()" class="px-4 py-2.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">보내기</button>
            </div>
        </div>
    </div>
</div>

<script>
var currentConsultShopId = 0;

function openConsultation(shopId) {
    currentConsultShopId = shopId;
    document.getElementById('consultModal').classList.remove('hidden');
    loadConsultation(shopId);
}

function loadConsultation(shopId) {
    var container = document.getElementById('consultMessages');
    container.innerHTML = '<p class="text-sm text-zinc-400 text-center py-8">로딩 중...</p>';
    var fd = new FormData();
    fd.append('action', 'get_consultation');
    fd.append('shop_id', shopId);
    fetch(window.location.href, {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: fd
    }).then(function(r){return r.json()}).then(function(d) {
        if (!d.success) { container.innerHTML = '<p class="text-sm text-red-400 text-center py-8">오류</p>'; return; }
        var shop = d.shop || {};
        document.getElementById('consultShopName').textContent = (shop.name || '') + (shop.contact_person ? ' (' + shop.contact_person + ')' : shop.representative ? ' (' + shop.representative + ')' : '');
        var msgs = d.messages || [];
        var ownerId = shop.user_id || '';
        if (msgs.length === 0) {
            container.innerHTML = '<p class="text-sm text-zinc-400 text-center py-8">상담 내역이 없습니다.</p>';
            return;
        }
        var html = '';
        msgs.forEach(function(m) {
            var isFromOwner = m.user_id === ownerId;
            html += '<div class="flex ' + (isFromOwner ? 'justify-start' : 'justify-end') + '" style="margin-bottom:8px">';
            html += '<div class="max-w-[75%] ' + (isFromOwner ? 'bg-zinc-50 dark:bg-zinc-700/50 border-zinc-200 dark:border-zinc-600' : 'bg-indigo-50 dark:bg-indigo-900/30 border-indigo-200 dark:border-indigo-800') + ' border rounded-xl px-4 py-3" style="' + (isFromOwner ? 'border-bottom-left-radius:4px' : 'border-bottom-right-radius:4px') + '">';
            if (isFromOwner) {
                html += '<p class="text-[10px] font-medium text-blue-600 dark:text-blue-400 mb-1">' + escapeHtml(m.user_name || '사업장') + '</p>';
            }
            html += '<p class="text-sm text-zinc-800 dark:text-zinc-200" style="white-space:pre-wrap;word-break:break-word">' + escapeHtml(m.question).replace(/\n/g, '<br>') + '</p>';
            if (m.answer) {
                html += '<div class="mt-2 pt-2 border-t border-zinc-200 dark:border-zinc-600">';
                html += '<p class="text-[10px] font-medium text-indigo-600 dark:text-indigo-400 mb-1">관리자 답변</p>';
                html += '<p class="text-sm text-zinc-700 dark:text-zinc-300" style="white-space:pre-wrap">' + escapeHtml(m.answer).replace(/\n/g, '<br>') + '</p>';
                html += '</div>';
            }
            html += '<p class="text-[10px] text-zinc-400 mt-1 ' + (!isFromOwner ? 'text-right' : '') + '">' + m.created_at.substring(0,16).replace('T',' ') + '</p>';
            html += '</div></div>';
        });
        container.innerHTML = html;
        container.scrollTop = container.scrollHeight;
    });
}

function sendConsultation() {
    var input = document.getElementById('consultInput');
    var msg = input.value.trim();
    if (!msg || !currentConsultShopId) return;
    var fd = new FormData();
    fd.append('action', 'send_consultation');
    fd.append('shop_id', currentConsultShopId);
    fd.append('message', msg);
    fetch(window.location.href, {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: fd
    }).then(function(r){return r.json()}).then(function(d) {
        if (d.success) {
            input.value = '';
            loadConsultation(currentConsultShopId);
        }
    });
}

function replyConsultation(qaId) {
    var input = document.getElementById('reply_' + qaId);
    var answer = input.value.trim();
    if (!answer) return;
    var fd = new FormData();
    fd.append('action', 'reply_consultation');
    fd.append('shop_id', currentConsultShopId);
    fd.append('qa_id', qaId);
    fd.append('answer', answer);
    fetch(window.location.href, {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: fd
    }).then(function(r){return r.json()}).then(function(d) {
        if (d.success) { loadConsultation(currentConsultShopId); }
    });
}

function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function shopAction(shopId, action) {
    var fd = new FormData();
    fd.append('action', action);
    fd.append('shop_id', shopId);
    fetch(window.location.href, {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: fd
    }).then(function(r){return r.json()}).then(function(d) {
        if (d.success) { location.reload(); }
        else { alert(d.error || 'Error'); }
    });
}

function viewLicense(shopId, url) {
    var ext = url.split('.').pop().toLowerCase();
    var content = document.getElementById('licenseContent');
    if (ext === 'pdf') {
        content.innerHTML = '<iframe src="' + url + '" class="w-full" style="height:60vh" frameborder="0"></iframe>';
    } else {
        content.innerHTML = '<img src="' + url + '" class="max-w-full rounded-lg">';
    }
    document.getElementById('licenseModal').classList.remove('hidden');
}
</script>

</div>
