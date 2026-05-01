<?php
/**
 * 제작 프로젝트 (커스터마이징 납품) API.
 *
 * POST /plugins/vos-hosting/api/custom-project.php
 * Body (JSON): { action, ... }
 *
 * Actions:
 *   project_create       — 고객: 의뢰서 작성
 *   project_list         — 목록 (admin: 전체, user: 본인)
 *   project_get          — 상세 + 견적 리스트
 *   project_update_status — 관리자: 상태 변경
 *   project_save_quote   — 관리자: 견적 작성/수정 (draft)
 *   project_send_quote   — 관리자: 견적 발행 (draft → sent + 고객 푸시)
 *   project_accept_quote — 고객: 견적 수락 → contracted
 *   project_reject_quote — 고객: 견적 거절
 *   project_admin_note   — 관리자: 내부 메모 갱신
 *   project_assign       — 관리자: 담당자 지정
 */
if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 3));

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']); exit;
}

if (session_status() === PHP_SESSION_NONE) {
    $_sessionDir = BASE_PATH . '/storage/sessions';
    if (is_dir($_sessionDir)) ini_set('session.save_path', $_sessionDir);
    session_start();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { echo json_encode(['success' => false, 'message' => '요청 데이터가 없습니다.']); exit; }

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB 연결 실패']); exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) { echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']); exit; }

$rSt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
$rSt->execute([$userId]);
$_role = (string)$rSt->fetchColumn();
$isAdmin = in_array($_role, ['admin', 'supervisor'], true);

$action = (string)($input['action'] ?? '');

// ============================================================
// 첨부파일 처리 (pending → 프로젝트 폴더 이동) — support 와 동일 패턴
// uploads/custom-projects/{project_id}/{uuid}.{ext}
// ============================================================
function processProjectAttachments(array $atts, int $projectId, string $userId): array {
    if (empty($atts)) return [];
    if (count($atts) > 5) throw new \RuntimeException('too many attachments (max 5)');
    $base = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3));
    $pendingDir = $base . '/uploads/support/_pending';  // 공용 pending 영역
    $targetDir = $base . '/uploads/custom-projects/' . $projectId;
    if (!is_dir($targetDir)) @mkdir($targetDir, 0775, true);

    $allowedExts = ['jpg','jpeg','png','gif','webp','svg','pdf','doc','docx','xls','xlsx','ppt','pptx','csv','txt','md','zip','rar','tar','gz','7z','log','json','xml','sql'];
    $clean = [];
    foreach ($atts as $a) {
        $uuid = (string)($a['uuid'] ?? '');
        $ext = strtolower((string)($a['ext'] ?? ''));
        $name = (string)($a['name'] ?? 'file');
        $size = (int)($a['size'] ?? 0);
        $mime = (string)($a['mime'] ?? 'application/octet-stream');
        if ($uuid === '' || !preg_match('/^[a-f0-9]{32}$/', $uuid)) throw new \RuntimeException('invalid uuid');
        if (!in_array($ext, $allowedExts, true)) throw new \RuntimeException('disallowed ext: ' . $ext);
        if ($size > 31457280) throw new \RuntimeException('file too large');
        $src = $pendingDir . '/' . $userId . '_' . $uuid . '.' . $ext;
        if (!is_file($src)) throw new \RuntimeException('pending file missing: ' . $uuid);
        $dst = $targetDir . '/' . $uuid . '.' . $ext;
        if (!@rename($src, $dst)) throw new \RuntimeException('move failed');
        @chmod($dst, 0644);
        $clean[] = ['uuid'=>$uuid, 'name'=>mb_substr($name,0,255), 'size'=>$size, 'mime'=>mb_substr($mime,0,100), 'ext'=>$ext];
    }
    return $clean;
}

// 프로젝트 번호 생성 — P-YYYY-NNN (연도별 순번)
function generateProjectNumber(\PDO $pdo, string $prefix): string {
    $year = date('Y');
    $st = $pdo->prepare("SELECT project_number FROM {$prefix}custom_projects WHERE project_number LIKE ? ORDER BY id DESC LIMIT 1");
    $st->execute(['P-' . $year . '-%']);
    $last = (string)$st->fetchColumn();
    $next = 1;
    if ($last && preg_match('/^P-\d{4}-(\d+)$/', $last, $m)) $next = (int)$m[1] + 1;
    return sprintf('P-%s-%03d', $year, $next);
}

switch ($action) {

    // ============================================================
    // project_create — 고객: 의뢰서 제출
    // ============================================================
    case 'project_create': {
        $title = trim((string)($input['title'] ?? ''));
        $siteType = (string)($input['site_type'] ?? 'homepage');
        $requirements = trim((string)($input['requirements'] ?? ''));
        $referenceUrls = trim((string)($input['reference_urls'] ?? ''));
        $budgetRange = (string)($input['budget_range'] ?? '');
        $desiredDueDate = trim((string)($input['desired_due_date'] ?? ''));
        $contactHours = trim((string)($input['contact_hours'] ?? ''));
        $rawAtts = is_array($input['attachments'] ?? null) ? $input['attachments'] : [];
        // 도메인/호스팅 매칭
        $domainOption = (string)($input['domain_option'] ?? '');
        $domainName = trim((string)($input['domain_name'] ?? ''));
        $linkedHostSubId = (int)($input['linked_host_subscription_id'] ?? 0);
        $needNewHosting = !empty($input['need_new_hosting']) ? 1 : 0;

        if ($title === '' || $requirements === '') {
            echo json_encode(['success' => false, 'message' => '제목과 요구사항은 필수입니다.']); exit;
        }
        if (mb_strlen($title) > 200) $title = mb_substr($title, 0, 200);
        if (!in_array($siteType, ['homepage','shop','reservation','other'], true)) $siteType = 'homepage';
        if (!in_array($budgetRange, ['lt100k','100k_300k','300k_1m','gt1m','discuss',''], true)) $budgetRange = '';
        $desiredDueDate = ($desiredDueDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desiredDueDate)) ? $desiredDueDate : null;
        if (!in_array($domainOption, ['addon','new','existing','free','discuss',''], true)) $domainOption = '';
        if ($domainName !== '' && mb_strlen($domainName) > 255) $domainName = mb_substr($domainName, 0, 255);

        // linked_host_subscription_id 권한 검증 (소유자인지)
        if ($linkedHostSubId > 0) {
            $vSt = $pdo->prepare("SELECT id FROM {$prefix}subscriptions WHERE id = ? AND user_id = ? AND type = 'hosting'");
            $vSt->execute([$linkedHostSubId, $userId]);
            if (!$vSt->fetchColumn()) $linkedHostSubId = 0;
        }

        try {
            $pdo->beginTransaction();
            $projectNumber = generateProjectNumber($pdo, $prefix);

            $ins = $pdo->prepare("INSERT INTO {$prefix}custom_projects
                (project_number, user_id, title, site_type, requirements, reference_urls,
                 domain_option, domain_name, linked_host_subscription_id, need_new_hosting,
                 budget_range, desired_due_date, contact_hours, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'lead')");
            $ins->execute([
                $projectNumber, $userId, $title, $siteType, $requirements,
                $referenceUrls !== '' ? $referenceUrls : null,
                $domainOption !== '' ? $domainOption : null,
                $domainName !== '' ? $domainName : null,
                $linkedHostSubId ?: null,
                $needNewHosting,
                $budgetRange !== '' ? $budgetRange : null,
                $desiredDueDate, $contactHours !== '' ? $contactHours : null,
            ]);
            $projectId = (int)$pdo->lastInsertId();

            $cleanAtts = processProjectAttachments($rawAtts, $projectId, $userId);
            if (!empty($cleanAtts)) {
                $pdo->prepare("UPDATE {$prefix}custom_projects SET attachments = ? WHERE id = ?")
                    ->execute([json_encode($cleanAtts, JSON_UNESCAPED_UNICODE), $projectId]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[project_create] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]); exit;
        }

        // 관리자 푸시
        try {
            require_once BASE_PATH . '/vendor/autoload.php';
            $notifier = new \RzxLib\Core\Notification\WebPushNotifier($pdo, $prefix);
            $adminPath = 'admin';
            try {
                $cfgSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'admin_path' LIMIT 1");
                $cfgSt->execute();
                $adminPath = trim((string)($cfgSt->fetchColumn() ?: 'admin'));
            } catch (\Throwable $e) {}
            $notifier->notifyAdmins(
                '🛠 새 제작 의뢰',
                $projectNumber . ' — ' . $title,
                '/' . trim($adminPath, '/') . '/custom-projects/' . $projectId
            );
        } catch (\Throwable $ne) {
            error_log('[project_create] notify: ' . $ne->getMessage());
        }

        echo json_encode(['success' => true, 'project_id' => $projectId, 'project_number' => $projectNumber]);
        exit;
    }

    // ============================================================
    // project_list — 목록 (admin: 전체, user: 본인)
    // ============================================================
    case 'project_list': {
        $page = max(1, (int)($input['page'] ?? 1));
        $perPage = max(1, min(50, (int)($input['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;
        $statusFilter = (string)($input['status'] ?? '');
        $search = trim((string)($input['search'] ?? ''));

        $where = []; $params = [];
        if (!$isAdmin) { $where[] = 'p.user_id = ?'; $params[] = $userId; }
        if (in_array($statusFilter, ['lead','quoted','contracted','in_progress','review','delivered','maintenance','cancelled'], true)) {
            $where[] = 'p.status = ?'; $params[] = $statusFilter;
        }
        if ($search !== '') {
            $where[] = '(p.title LIKE ? OR p.project_number LIKE ?)';
            $params[] = '%' . $search . '%'; $params[] = '%' . $search . '%';
        }
        $whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $cntSt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}custom_projects p $whereSQL");
        $cntSt->execute($params);
        $total = (int)$cntSt->fetchColumn();

        $listSt = $pdo->prepare("SELECT p.*, u.email AS user_email, u.name AS user_name
            FROM {$prefix}custom_projects p
            LEFT JOIN {$prefix}users u ON p.user_id = u.id
            $whereSQL
            ORDER BY p.created_at DESC, p.id DESC LIMIT $perPage OFFSET $offset");
        $listSt->execute($params);
        $rows = $listSt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'projects' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'is_admin' => $isAdmin], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ============================================================
    // project_get — 상세 + 견적 리스트
    // ============================================================
    case 'project_get': {
        $projectId = (int)($input['project_id'] ?? 0);
        if (!$projectId) { echo json_encode(['success' => false, 'message' => 'project_id required']); exit; }

        $pSt = $pdo->prepare("SELECT p.*, u.email AS user_email, u.name AS user_name
            FROM {$prefix}custom_projects p
            LEFT JOIN {$prefix}users u ON p.user_id = u.id WHERE p.id = ?");
        $pSt->execute([$projectId]);
        $project = $pSt->fetch(PDO::FETCH_ASSOC);
        if (!$project) { echo json_encode(['success' => false, 'message' => 'project not found']); exit; }
        if (!$isAdmin && $project['user_id'] !== $userId) {
            echo json_encode(['success' => false, 'message' => 'permission denied']); exit;
        }

        $project['attachments'] = json_decode($project['attachments'] ?? '[]', true) ?: [];

        // 견적 리스트 — 고객은 sent/accepted/rejected/superseded 만, admin 은 전체
        $qSql = "SELECT * FROM {$prefix}custom_quotes WHERE project_id = ? ";
        if (!$isAdmin) $qSql .= "AND status IN ('sent','accepted','rejected','superseded') ";
        $qSql .= "ORDER BY version DESC, id DESC";
        $qSt = $pdo->prepare($qSql);
        $qSt->execute([$projectId]);
        $quotes = $qSt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($quotes as &$_q) {
            $_q['items'] = json_decode($_q['items'] ?? '[]', true) ?: [];
            // 견적별 결제 일정
            $_pSt = $pdo->prepare("SELECT id, sequence_no, label, amount, currency, due_date, status, paid_at, note FROM {$prefix}custom_project_payments WHERE quote_id = ? ORDER BY sequence_no ASC, id ASC");
            $_pSt->execute([$_q['id']]);
            $_q['payments'] = $_pSt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($_q);

        // admin 만 노출되는 필드 마스킹
        if (!$isAdmin) {
            unset($project['admin_note']);
            unset($project['assigned_admin_id']);
        }

        echo json_encode(['success' => true, 'project' => $project, 'quotes' => $quotes, 'is_admin' => $isAdmin], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ============================================================
    // project_update_status — 관리자: 상태 변경
    // ============================================================
    case 'project_update_status': {
        if (!$isAdmin) { echo json_encode(['success' => false, 'message' => '관리자 권한 필요']); exit; }
        $projectId = (int)($input['project_id'] ?? 0);
        $newStatus = (string)($input['status'] ?? '');
        if (!$projectId || !in_array($newStatus, ['lead','quoted','contracted','in_progress','review','delivered','maintenance','cancelled'], true)) {
            echo json_encode(['success' => false, 'message' => 'invalid params']); exit;
        }
        $cancelReason = trim((string)($input['cancel_reason'] ?? ''));

        $extras = [];
        if ($newStatus === 'in_progress') $extras[] = 'started_at = COALESCE(started_at, NOW())';
        if ($newStatus === 'delivered') $extras[] = 'delivered_at = NOW()';
        if ($newStatus === 'cancelled') {
            $extras[] = 'cancelled_at = NOW()';
            if ($cancelReason !== '') $extras[] = 'cancel_reason = ' . $pdo->quote(mb_substr($cancelReason, 0, 255));
        }
        $extraSql = $extras ? (', ' . implode(', ', $extras)) : '';

        $pdo->prepare("UPDATE {$prefix}custom_projects SET status = ? $extraSql WHERE id = ?")
            ->execute([$newStatus, $projectId]);

        echo json_encode(['success' => true]);
        exit;
    }

    // ============================================================
    // project_save_quote — 관리자: 견적 작성/수정 (draft 상태에서만 수정 가능)
    //   결제 일정 (payments) 도 함께 저장 — draft 상태에서만 변경 가능
    // ============================================================
    case 'project_save_quote': {
        if (!$isAdmin) { echo json_encode(['success' => false, 'message' => '관리자 권한 필요']); exit; }
        $projectId = (int)($input['project_id'] ?? 0);
        $quoteId = (int)($input['quote_id'] ?? 0);
        $items = is_array($input['items'] ?? null) ? $input['items'] : [];
        $taxRate = (float)($input['tax_rate'] ?? 10);
        $validUntil = trim((string)($input['valid_until'] ?? ''));
        $notes = trim((string)($input['notes'] ?? ''));
        $currency = strtoupper((string)($input['currency'] ?? 'JPY'));
        $payments = is_array($input['payments'] ?? null) ? $input['payments'] : [];

        if (!$projectId) { echo json_encode(['success' => false, 'message' => 'project_id required']); exit; }
        if (empty($items)) { echo json_encode(['success' => false, 'message' => '견적 항목이 비어있습니다.']); exit; }

        // 항목 정리 + 합계 계산
        $clean = []; $subtotal = 0;
        foreach ($items as $it) {
            $label = mb_substr(trim((string)($it['label'] ?? '')), 0, 200);
            $qty = max(0, (float)($it['qty'] ?? 1));
            $unitPrice = max(0, (float)($it['unit_price'] ?? 0));
            $note = mb_substr(trim((string)($it['note'] ?? '')), 0, 500);
            if ($label === '') continue;
            $amount = round($qty * $unitPrice, 2);
            $clean[] = ['label'=>$label, 'qty'=>$qty, 'unit_price'=>$unitPrice, 'amount'=>$amount, 'note'=>$note];
            $subtotal += $amount;
        }
        if (empty($clean)) { echo json_encode(['success' => false, 'message' => '유효한 견적 항목이 없습니다.']); exit; }
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = round($subtotal + $taxAmount, 2);
        $itemsJson = json_encode($clean, JSON_UNESCAPED_UNICODE);
        $validUntil = ($validUntil !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $validUntil)) ? $validUntil : null;

        // 결제 일정 검증·정리
        $cleanPayments = [];
        if (!empty($payments)) {
            $sumAmt = 0;
            foreach ($payments as $idx => $p) {
                $pLabel = mb_substr(trim((string)($p['label'] ?? '')), 0, 100);
                $pAmt = max(0, (float)($p['amount'] ?? 0));
                $pDue = trim((string)($p['due_date'] ?? ''));
                $pDue = ($pDue !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $pDue)) ? $pDue : null;
                if ($pLabel === '' || $pAmt <= 0) continue;
                $cleanPayments[] = [
                    'sequence_no' => $idx + 1,
                    'label' => $pLabel,
                    'amount' => $pAmt,
                    'due_date' => $pDue,
                    'note' => mb_substr(trim((string)($p['note'] ?? '')), 0, 255),
                ];
                $sumAmt += $pAmt;
            }
            // 결제 합계가 총액과 일치해야 함 (반올림 1엔 오차 허용)
            if (!empty($cleanPayments) && abs($sumAmt - $total) > 1) {
                echo json_encode(['success' => false, 'message' => '결제 일정 합계(¥' . number_format($sumAmt) . ')가 견적 총액(¥' . number_format($total) . ')과 일치하지 않습니다.']); exit;
            }
        }

        try {
            $pdo->beginTransaction();
            if ($quoteId > 0) {
                // 기존 draft 수정
                $existing = $pdo->prepare("SELECT * FROM {$prefix}custom_quotes WHERE id = ? AND project_id = ?");
                $existing->execute([$quoteId, $projectId]);
                $row = $existing->fetch(PDO::FETCH_ASSOC);
                if (!$row) { $pdo->rollBack(); echo json_encode(['success' => false, 'message' => 'quote not found']); exit; }
                if ($row['status'] !== 'draft') { $pdo->rollBack(); echo json_encode(['success' => false, 'message' => '이미 발행된 견적은 수정할 수 없습니다. 새 버전을 작성하세요.']); exit; }
                $pdo->prepare("UPDATE {$prefix}custom_quotes
                    SET items = ?, subtotal = ?, tax_rate = ?, tax_amount = ?, total = ?, currency = ?, valid_until = ?, notes = ?
                    WHERE id = ?")
                    ->execute([$itemsJson, $subtotal, $taxRate, $taxAmount, $total, $currency, $validUntil, $notes !== '' ? $notes : null, $quoteId]);
                // 기존 draft 결제 일정 — pending 상태인 것만 삭제 후 재생성 (paid 보호)
                $pdo->prepare("DELETE FROM {$prefix}custom_project_payments WHERE quote_id = ? AND status = 'pending'")
                    ->execute([$quoteId]);
            } else {
                // 신규 견적 — 다음 version 번호 계산
                $vSt = $pdo->prepare("SELECT MAX(version) FROM {$prefix}custom_quotes WHERE project_id = ?");
                $vSt->execute([$projectId]);
                $version = (int)$vSt->fetchColumn() + 1;

                $pdo->prepare("INSERT INTO {$prefix}custom_quotes
                    (project_id, version, items, subtotal, tax_rate, tax_amount, total, currency, valid_until, notes, status, created_by_admin_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)")
                    ->execute([$projectId, $version, $itemsJson, $subtotal, $taxRate, $taxAmount, $total, $currency, $validUntil, $notes !== '' ? $notes : null, $userId]);
                $quoteId = (int)$pdo->lastInsertId();
            }

            // 결제 일정 INSERT
            if (!empty($cleanPayments)) {
                $pIns = $pdo->prepare("INSERT INTO {$prefix}custom_project_payments
                    (project_id, quote_id, sequence_no, label, amount, currency, due_date, note, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                foreach ($cleanPayments as $cp) {
                    $pIns->execute([
                        $projectId, $quoteId, $cp['sequence_no'], $cp['label'], $cp['amount'],
                        $currency, $cp['due_date'], $cp['note'] !== '' ? $cp['note'] : null,
                    ]);
                }
            }

            // 프로젝트 estimated_amount 갱신
            $pdo->prepare("UPDATE {$prefix}custom_projects SET estimated_amount = ?, contract_currency = ? WHERE id = ?")
                ->execute([$total, $currency, $projectId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[project_save_quote] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]); exit;
        }

        echo json_encode(['success' => true, 'quote_id' => $quoteId]);
        exit;
    }

    // ============================================================
    // project_send_quote — 관리자: 견적 발행 (draft → sent + 푸시)
    //   기존 sent/accepted 견적이 있다면 superseded 로 변경
    // ============================================================
    case 'project_send_quote': {
        if (!$isAdmin) { echo json_encode(['success' => false, 'message' => '관리자 권한 필요']); exit; }
        $projectId = (int)($input['project_id'] ?? 0);
        $quoteId = (int)($input['quote_id'] ?? 0);
        if (!$projectId || !$quoteId) { echo json_encode(['success' => false, 'message' => 'invalid params']); exit; }

        try {
            $pdo->beginTransaction();
            $qSt = $pdo->prepare("SELECT * FROM {$prefix}custom_quotes WHERE id = ? AND project_id = ?");
            $qSt->execute([$quoteId, $projectId]);
            $quote = $qSt->fetch(PDO::FETCH_ASSOC);
            if (!$quote) { $pdo->rollBack(); echo json_encode(['success' => false, 'message' => 'quote not found']); exit; }
            if ($quote['status'] !== 'draft') { $pdo->rollBack(); echo json_encode(['success' => false, 'message' => '이미 발행된 견적입니다.']); exit; }

            // 이전 sent (미수락) 견적은 superseded 로 + 결제 일정 cancelled
            $oldSt = $pdo->prepare("SELECT id FROM {$prefix}custom_quotes WHERE project_id = ? AND status = 'sent' AND id != ?");
            $oldSt->execute([$projectId, $quoteId]);
            $oldQuoteIds = $oldSt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($oldQuoteIds)) {
                $place = implode(',', array_fill(0, count($oldQuoteIds), '?'));
                $pdo->prepare("UPDATE {$prefix}custom_quotes SET status = 'superseded' WHERE id IN ($place)")
                    ->execute($oldQuoteIds);
                $pdo->prepare("UPDATE {$prefix}custom_project_payments SET status = 'cancelled' WHERE quote_id IN ($place) AND status = 'pending'")
                    ->execute($oldQuoteIds);
            }

            // 발행
            $pdo->prepare("UPDATE {$prefix}custom_quotes SET status = 'sent', sent_at = NOW() WHERE id = ?")
                ->execute([$quoteId]);

            // 프로젝트 상태가 lead 면 quoted 로
            $pdo->prepare("UPDATE {$prefix}custom_projects SET status = 'quoted' WHERE id = ? AND status = 'lead'")
                ->execute([$projectId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[project_send_quote] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'DB error']); exit;
        }

        // 고객 푸시
        try {
            $owner = $pdo->prepare("SELECT user_id, project_number, title FROM {$prefix}custom_projects WHERE id = ?");
            $owner->execute([$projectId]);
            $row = $owner->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                require_once BASE_PATH . '/vendor/autoload.php';
                $notifier = new \RzxLib\Core\Notification\WebPushNotifier($pdo, $prefix);
                $notifier->notifyUser(
                    (string)$row['user_id'],
                    '📋 견적서 도착',
                    $row['project_number'] . ' — ' . $row['title'],
                    '/mypage/custom-projects/' . $projectId
                );
            }
        } catch (\Throwable $ne) { error_log('[project_send_quote] notify: ' . $ne->getMessage()); }

        echo json_encode(['success' => true]);
        exit;
    }

    // ============================================================
    // project_accept_quote — 고객: 견적 수락 → contracted
    // ============================================================
    case 'project_accept_quote': {
        $projectId = (int)($input['project_id'] ?? 0);
        $quoteId = (int)($input['quote_id'] ?? 0);
        if (!$projectId || !$quoteId) { echo json_encode(['success' => false, 'message' => 'invalid params']); exit; }

        $pSt = $pdo->prepare("SELECT * FROM {$prefix}custom_projects WHERE id = ?");
        $pSt->execute([$projectId]);
        $project = $pSt->fetch(PDO::FETCH_ASSOC);
        if (!$project) { echo json_encode(['success' => false, 'message' => 'project not found']); exit; }
        if (!$isAdmin && $project['user_id'] !== $userId) { echo json_encode(['success' => false, 'message' => 'permission denied']); exit; }

        $qSt = $pdo->prepare("SELECT * FROM {$prefix}custom_quotes WHERE id = ? AND project_id = ?");
        $qSt->execute([$quoteId, $projectId]);
        $quote = $qSt->fetch(PDO::FETCH_ASSOC);
        if (!$quote) { echo json_encode(['success' => false, 'message' => 'quote not found']); exit; }
        if ($quote['status'] !== 'sent') { echo json_encode(['success' => false, 'message' => '발행된 견적만 수락 가능합니다.']); exit; }

        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE {$prefix}custom_quotes SET status = 'accepted', accepted_at = NOW() WHERE id = ?")
                ->execute([$quoteId]);
            $pdo->prepare("UPDATE {$prefix}custom_projects
                SET status = 'contracted', accepted_quote_id = ?, contract_amount = ?, contract_currency = ?
                WHERE id = ?")
                ->execute([$quoteId, $quote['total'], $quote['currency'], $projectId]);

            // 프로젝트 채널 자동 생성 (이미 있으면 skip)
            $existSt = $pdo->prepare("SELECT id FROM {$prefix}support_tickets WHERE custom_project_id = ? LIMIT 1");
            $existSt->execute([$projectId]);
            if (!$existSt->fetchColumn()) {
                $tUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $tNow = date('Y-m-d H:i:s');
                $tIns = $pdo->prepare("INSERT INTO {$prefix}support_tickets
                    (uuid, user_id, host_subscription_id, custom_project_id, title, status, last_message_at, last_message_by, unread_by_admin)
                    VALUES (?, ?, ?, ?, ?, 'open', ?, 'user', 1)");
                $tIns->execute([
                    $tUuid, $project['user_id'],
                    $project['linked_host_subscription_id'] ?: null,
                    $projectId,
                    '[' . $project['project_number'] . '] ' . $project['title'],
                    $tNow,
                ]);
                $newTicketId = (int)$pdo->lastInsertId();
                $pdo->prepare("INSERT INTO {$prefix}support_messages (ticket_id, sender_user_id, is_admin, body) VALUES (?, ?, 0, ?)")
                    ->execute([$newTicketId, $project['user_id'], '✅ 계약이 체결되었습니다. 이 채널에서 진행 사항을 공유드립니다.']);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[project_accept_quote] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'DB error']); exit;
        }

        // 관리자 푸시
        try {
            require_once BASE_PATH . '/vendor/autoload.php';
            $notifier = new \RzxLib\Core\Notification\WebPushNotifier($pdo, $prefix);
            $adminPath = 'admin';
            try {
                $cfgSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'admin_path' LIMIT 1");
                $cfgSt->execute();
                $adminPath = trim((string)($cfgSt->fetchColumn() ?: 'admin'));
            } catch (\Throwable $e) {}
            $notifier->notifyAdmins(
                '✅ 견적 수락 — 계약 체결',
                $project['project_number'] . ' — ' . $project['title'],
                '/' . trim($adminPath, '/') . '/custom-projects/' . $projectId
            );
        } catch (\Throwable $ne) { error_log('[project_accept_quote] notify: ' . $ne->getMessage()); }

        echo json_encode(['success' => true]);
        exit;
    }

    // ============================================================
    // project_reject_quote — 고객: 견적 거절
    // ============================================================
    case 'project_reject_quote': {
        $projectId = (int)($input['project_id'] ?? 0);
        $quoteId = (int)($input['quote_id'] ?? 0);
        $reason = mb_substr(trim((string)($input['reason'] ?? '')), 0, 255);
        if (!$projectId || !$quoteId) { echo json_encode(['success' => false, 'message' => 'invalid params']); exit; }

        $pSt = $pdo->prepare("SELECT user_id, project_number, title FROM {$prefix}custom_projects WHERE id = ?");
        $pSt->execute([$projectId]);
        $project = $pSt->fetch(PDO::FETCH_ASSOC);
        if (!$project) { echo json_encode(['success' => false, 'message' => 'project not found']); exit; }
        if (!$isAdmin && $project['user_id'] !== $userId) { echo json_encode(['success' => false, 'message' => 'permission denied']); exit; }

        $qSt = $pdo->prepare("SELECT status FROM {$prefix}custom_quotes WHERE id = ? AND project_id = ?");
        $qSt->execute([$quoteId, $projectId]);
        $st = (string)$qSt->fetchColumn();
        if ($st !== 'sent') { echo json_encode(['success' => false, 'message' => '발행된 견적만 거절 가능합니다.']); exit; }

        $pdo->prepare("UPDATE {$prefix}custom_quotes SET status = 'rejected', rejected_at = NOW(), reject_reason = ? WHERE id = ?")
            ->execute([$reason !== '' ? $reason : null, $quoteId]);

        // 관리자 푸시
        try {
            require_once BASE_PATH . '/vendor/autoload.php';
            $notifier = new \RzxLib\Core\Notification\WebPushNotifier($pdo, $prefix);
            $adminPath = 'admin';
            try {
                $cfgSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'admin_path' LIMIT 1");
                $cfgSt->execute();
                $adminPath = trim((string)($cfgSt->fetchColumn() ?: 'admin'));
            } catch (\Throwable $e) {}
            $notifier->notifyAdmins(
                '❌ 견적 거절',
                $project['project_number'] . ' — ' . ($reason ?: '(사유 없음)'),
                '/' . trim($adminPath, '/') . '/custom-projects/' . $projectId
            );
        } catch (\Throwable $ne) { error_log('[project_reject_quote] notify: ' . $ne->getMessage()); }

        echo json_encode(['success' => true]);
        exit;
    }

    // ============================================================
    // project_admin_note — 관리자: 내부 메모 갱신
    // ============================================================
    case 'project_admin_note': {
        if (!$isAdmin) { echo json_encode(['success' => false, 'message' => '관리자 권한 필요']); exit; }
        $projectId = (int)($input['project_id'] ?? 0);
        $note = (string)($input['note'] ?? '');
        if (!$projectId) { echo json_encode(['success' => false, 'message' => 'project_id required']); exit; }
        $pdo->prepare("UPDATE {$prefix}custom_projects SET admin_note = ? WHERE id = ?")
            ->execute([$note !== '' ? $note : null, $projectId]);
        echo json_encode(['success' => true]);
        exit;
    }

    // ============================================================
    // project_pay_installment — 고객: 분할금 PAY.JP 결제
    //   기존 호스팅 결제 인프라 (PaymentManager / PayjpGateway) 재사용.
    //   성공 시: rzx_payments 전표 INSERT + custom_project_payments status='paid' 갱신.
    //   계약 수락 후 (project.status >= 'contracted') 인 경우만 결제 가능.
    // ============================================================
    case 'project_pay_installment': {
        $projectId = (int)($input['project_id'] ?? 0);
        $paymentScheduleId = (int)($input['payment_id'] ?? 0);
        $cardToken = trim((string)($input['card_token'] ?? ''));
        if (!$projectId || !$paymentScheduleId) {
            echo json_encode(['success' => false, 'message' => 'invalid params']); exit;
        }

        // 프로젝트 + 결제 일정 조회
        $pSt = $pdo->prepare("SELECT p.*, u.email AS user_email FROM {$prefix}custom_projects p LEFT JOIN {$prefix}users u ON p.user_id = u.id WHERE p.id = ?");
        $pSt->execute([$projectId]);
        $project = $pSt->fetch(PDO::FETCH_ASSOC);
        if (!$project) { echo json_encode(['success' => false, 'message' => 'project not found']); exit; }
        if (!$isAdmin && $project['user_id'] !== $userId) {
            echo json_encode(['success' => false, 'message' => 'permission denied']); exit;
        }
        if (!in_array($project['status'], ['contracted','in_progress','review','delivered','maintenance'], true)) {
            echo json_encode(['success' => false, 'message' => '계약 후에만 결제 가능합니다.']); exit;
        }

        $sSt = $pdo->prepare("SELECT * FROM {$prefix}custom_project_payments WHERE id = ? AND project_id = ?");
        $sSt->execute([$paymentScheduleId, $projectId]);
        $sched = $sSt->fetch(PDO::FETCH_ASSOC);
        if (!$sched) { echo json_encode(['success' => false, 'message' => 'payment schedule not found']); exit; }
        if ($sched['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => '이미 처리된 결제입니다.']); exit;
        }
        // 수락된 견적의 일정인지 검증
        $qSt = $pdo->prepare("SELECT status FROM {$prefix}custom_quotes WHERE id = ?");
        $qSt->execute([$sched['quote_id']]);
        if ($qSt->fetchColumn() !== 'accepted') {
            echo json_encode(['success' => false, 'message' => '수락된 견적의 결제만 가능합니다.']); exit;
        }

        $amount = (int)round((float)$sched['amount']);
        $currency = $sched['currency'] ?: 'JPY';

        // PAY.JP 결제
        if ($cardToken === '') {
            echo json_encode(['success' => false, 'message' => '카드 정보가 필요합니다.']); exit;
        }
        require_once BASE_PATH . '/rzxlib/Modules/Payment/PaymentManager.php';
        $chargeId = null; $gwName = null;
        try {
            $payMgr = new \RzxLib\Modules\Payment\PaymentManager($pdo, $prefix);
            $gateway = $payMgr->gateway();
            $gwName = $payMgr->getGatewayName();
            $emSt = $pdo->prepare("SELECT email FROM {$prefix}users WHERE id = ?");
            $emSt->execute([$project['user_id']]);
            $userEmail = $emSt->fetchColumn() ?: '';
            $custMeta = ['custom_project' => $project['project_number']];
            $cr = $gateway->createCustomer($cardToken, $userEmail, $custMeta);
            if (!($cr['success'] ?? false)) {
                echo json_encode(['success' => false, 'message' => '카드 등록 실패: ' . ($cr['message'] ?? ''), 'card_error' => true]); exit;
            }
            $customerId = $cr['customer_id'];
            $desc = "VosCMS Custom Project {$project['project_number']} — {$sched['label']}";
            $payResult = $gateway->chargeCustomer($customerId, $amount, strtolower($currency), $desc);
            if (!$payResult->isSuccessful()) {
                $code = (string)($payResult->failureCode ?? '');
                try { $gateway->deleteCustomer($customerId); } catch (\Throwable $de) {}
                echo json_encode(['success' => false, 'message' => '결제 실패: ' . $code, 'card_error' => true]); exit;
            }
            $chargeId = $payResult->transactionId;
        } catch (\Throwable $e) {
            error_log('[project_pay_installment] gateway: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => '결제 처리 중 오류: ' . $e->getMessage()]); exit;
        }

        // 전표 INSERT + 일정 갱신
        try {
            $pdo->beginTransaction();
            $payUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $pIns = $pdo->prepare("INSERT INTO {$prefix}payments
                (uuid, user_id, order_id, payment_key, gateway, method, amount, status, paid_at, metadata, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'card', ?, 'paid', NOW(), ?, NOW(), NOW())");
            $pIns->execute([
                $payUuid, $project['user_id'], $project['project_number'],
                $chargeId, $gwName, $amount,
                json_encode([
                    'source' => 'custom_project_installment',
                    'project_id' => $projectId,
                    'project_number' => $project['project_number'],
                    'schedule_id' => $paymentScheduleId,
                    'schedule_label' => $sched['label'],
                    'sequence_no' => $sched['sequence_no'],
                ], JSON_UNESCAPED_UNICODE),
            ]);
            $paymentRowId = (int)$pdo->lastInsertId();
            $pdo->prepare("UPDATE {$prefix}custom_project_payments SET status = 'paid', paid_at = NOW(), payment_id = ?, payment_key = ? WHERE id = ?")
                ->execute([$paymentRowId, $chargeId, $paymentScheduleId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[project_pay_installment] DB: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'DB error after payment: ' . $e->getMessage(), 'charge_id' => $chargeId]); exit;
        }

        // 관리자 푸시
        try {
            require_once BASE_PATH . '/vendor/autoload.php';
            $notifier = new \RzxLib\Core\Notification\WebPushNotifier($pdo, $prefix);
            $adminPath = 'admin';
            try {
                $cfgSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'admin_path' LIMIT 1");
                $cfgSt->execute();
                $adminPath = trim((string)($cfgSt->fetchColumn() ?: 'admin'));
            } catch (\Throwable $e) {}
            $notifier->notifyAdmins(
                '💰 제작 프로젝트 결제 완료',
                $project['project_number'] . ' — ' . $sched['label'] . ' ¥' . number_format($amount),
                '/' . trim($adminPath, '/') . '/custom-projects/' . $projectId
            );
        } catch (\Throwable $ne) { error_log('[project_pay_installment] notify: ' . $ne->getMessage()); }

        echo json_encode(['success' => true, 'paid_amount' => $amount, 'charge_id' => $chargeId]);
        exit;
    }

    default:
        echo json_encode(['success' => false, 'message' => 'unknown action: ' . $action]); exit;
}
