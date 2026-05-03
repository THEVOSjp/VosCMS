<?php
/**
 * 서비스 관리 API
 *
 * POST /api/service-manage.php
 * Body (JSON): { action, subscription_id, ... }
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
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

// 세션 — 코어와 동일한 save_path 사용 (BASE_PATH/storage/sessions)
if (session_status() === PHP_SESSION_NONE) {
    $_sessionDir = BASE_PATH . '/storage/sessions';
    if (is_dir($_sessionDir)) ini_set('session.save_path', $_sessionDir);
    session_start();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => '요청 데이터가 없습니다.']);
    exit;
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$action = $input['action'] ?? '';
$subId = (int)($input['subscription_id'] ?? 0);

// mx1 메일 동기화 즉시 트리거 (백그라운드 — fire & forget)
function triggerMailSyncToMx1() {
    $script = BASE_PATH . '/scripts/mail-sync-to-mx1.php';
    if (!file_exists($script)) return;
    $dbName = $_ENV['DB_DATABASE'] ?? 'voscms_prod';
    // 백그라운드 실행 (응답 지연 방지)
    $cmd = sprintf('/usr/bin/php8.3 %s %s > /dev/null 2>&1 &', escapeshellarg($script), escapeshellarg($dbName));
    @exec($cmd);
}

// 1:1 상담 첨부파일 — pending → 티켓 폴더 이동 + 메타 클리닝
// $attachments: [{uuid, name, size, mime, ext}, ...] (프론트가 upload_pending 결과 그대로 전달)
// 반환: 검증·정리된 메타 배열 (사용자 user_id prefix 제거 후 ticket 폴더에 저장됨)
function processSupportAttachments(array $attachments, int $ticketId, string $userId): array {
    if (empty($attachments)) return [];
    if (count($attachments) > 3) throw new \RuntimeException('too many attachments (max 3)');

    $base = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3));
    $pendingDir = $base . '/uploads/support/_pending';
    $ticketDir = $base . '/uploads/support/' . $ticketId;
    if (!is_dir($ticketDir)) @mkdir($ticketDir, 0775, true);

    $allowedExts = ['jpg','jpeg','png','gif','webp','svg','pdf','doc','docx','xls','xlsx','ppt','pptx','csv','txt','md','zip','rar','tar','gz','7z','log','json','xml','sql'];
    $clean = [];
    foreach ($attachments as $a) {
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
        $dst = $ticketDir . '/' . $uuid . '.' . $ext;
        if (!@rename($src, $dst)) throw new \RuntimeException('move failed');
        @chmod($dst, 0644);

        $clean[] = [
            'uuid' => $uuid,
            'name' => mb_substr($name, 0, 255),
            'size' => $size,
            'mime' => mb_substr($mime, 0, 100),
            'ext' => $ext,
        ];
    }
    return $clean;
}

// 구독 소유자 확인 헬퍼
function getOwnedSubscription($pdo, $prefix, $subId, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ? AND user_id = ?");
    $stmt->execute([$subId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

switch ($action) {

    // ===== admin: 도메인 등록 취소 처리 — PAY.JP 환불 + 데이터 정리 =====
    case 'admin_domain_cancel':
        $userStmt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $userStmt->execute([$userId]);
        if (!in_array($userStmt->fetchColumn(), ['admin','supervisor'], true)) {
            echo json_encode(['success' => false, 'message' => '관리자 권한 필요']);
            exit;
        }
        $source = (string)($input['source'] ?? '');
        $domain = strtolower(trim((string)($input['domain'] ?? '')));
        if (!$domain) { echo json_encode(['success' => false, 'message' => 'domain required']); exit; }

        require_once BASE_PATH . '/rzxlib/Modules/Payment/PaymentManager.php';
        $payMgr = null;
        try {
            $payMgr = new \RzxLib\Modules\Payment\PaymentManager($pdo, $prefix);
        } catch (\Throwable $e) {
            error_log('[admin_domain_cancel] payment init: ' . $e->getMessage());
        }
        // gateway 결정은 환불 시점에 — 결제 row 의 gateway 컬럼 기준 (PG 변경 후에도 안전)
        $gateway = null;

        try {
            $pdo->beginTransaction();
            $refundResult = null;
            $refundedAmount = 0;
            $chargeId = '';
            $orderIdLog = 0;

            if ($source === 'mypage') {
                $hostSubId = (int)($input['host_sub_id'] ?? 0);
                if (!$hostSubId) throw new Exception('host_sub_id required');
                $hSt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ? FOR UPDATE");
                $hSt->execute([$hostSubId]);
                $hSub = $hSt->fetch(PDO::FETCH_ASSOC);
                if (!$hSub) throw new Exception('host sub not found');
                $hMeta = json_decode($hSub['metadata'] ?? '{}', true) ?: [];

                $matched = null;
                $newAdded = [];
                foreach (($hMeta['added_domains'] ?? []) as $ad) {
                    if (($ad['domain'] ?? '') === $domain && $matched === null) {
                        $matched = $ad;
                        continue;
                    }
                    $newAdded[] = $ad;
                }
                if (!$matched) throw new Exception('domain not found in added_domains');
                $orderIdLog = (int)$hSub['order_id'];
                $chargeId = (string)($matched['payment_transaction_id'] ?? '');
                $refundedAmount = (int)($matched['total'] ?? 0);

                // payments 테이블 fallback — payment_row_id 가 있으면 거기서 charge_id 보완
                if (!$chargeId && !empty($matched['payment_row_id'])) {
                    $pSt = $pdo->prepare("SELECT payment_key FROM {$prefix}payments WHERE id = ?");
                    $pSt->execute([(int)$matched['payment_row_id']]);
                    $chargeId = (string)($pSt->fetchColumn() ?: '');
                }

                // 환불 의무: 유료 결제는 charge_id 필수
                if ($refundedAmount > 0) {
                    if (!$chargeId) {
                        throw new Exception('환불 불가: 결제 transaction id 없음 (payment_transaction_id 누락). PG 대시보드에서 직접 환불 후 다시 시도하거나 운영자에게 문의.');
                    }
                    // 결제 row 의 gateway 기준으로 분기 (PG 변경 후에도 원 PG 로 환불)
                    $payGw = $matched['payment_gateway'] ?? '';
                    if (!$payGw && !empty($matched['payment_row_id'])) {
                        $gwSt = $pdo->prepare("SELECT gateway FROM {$prefix}payments WHERE id = ?");
                        $gwSt->execute([(int)$matched['payment_row_id']]);
                        $payGw = (string)($gwSt->fetchColumn() ?: '');
                    }
                    try {
                        $gateway = $payMgr ? $payMgr->gateway($payGw ?: null) : null;
                    } catch (\Throwable $e) {
                        error_log('[admin_domain_cancel] gateway init: ' . $e->getMessage());
                    }
                    if (!$gateway || !method_exists($gateway, 'refund')) {
                        throw new Exception('환불 불가: 게이트웨이 초기화 실패 (gateway=' . ($payGw ?: '?') . ')');
                    }
                    try {
                        $refundResult = $gateway->refund($chargeId, $refundedAmount, 'admin domain cancel: ' . $domain);
                    } catch (\Throwable $re) {
                        throw new Exception('PAY.JP refund 호출 실패: ' . $re->getMessage());
                    }
                    if (!($refundResult->success ?? false)) {
                        $detail = $refundResult->failureReason ?? '';
                        if (!$detail && !empty($refundResult->raw)) {
                            $detail = json_encode($refundResult->raw, JSON_UNESCAPED_UNICODE);
                        }
                        // 이미 환불된 charge — PAY.JP 정상, 우리 DB 만 정리하면 됨
                        $alreadyRefunded = stripos($detail, 'already been refunded') !== false
                            || stripos($detail, 'already_refunded') !== false;
                        if ($alreadyRefunded) {
                            error_log("[admin_domain_cancel] charge {$chargeId} already refunded on PAY.JP — proceeding with DB cleanup");
                            // refundResult 를 success 로 처리 (refund_id 없음)
                            $refundResult = new \RzxLib\Modules\Payment\DTO\RefundResult([
                                'success' => true,
                                'refund_id' => null,
                                'amount' => $refundedAmount,
                                'status' => 'refunded',
                                'raw' => ['note' => 'already_refunded_on_payjp'],
                            ]);
                        } else {
                            error_log('[admin_domain_cancel] PAY.JP refund fail: ' . substr($detail, 0, 500));
                            throw new Exception('PAY.JP 환불 응답 실패: ' . ($detail ?: 'unknown'));
                        }
                    }
                    // 회계 전표 발생 — 환불 row INSERT (paid row 는 그대로 유지)
                    $hostOrderNumberSt = $pdo->prepare("SELECT order_number FROM {$prefix}orders WHERE id = ?");
                    $hostOrderNumberSt->execute([(int)$hSub['order_id']]);
                    $hostOrderNumberCancel = (string)($hostOrderNumberSt->fetchColumn() ?: '');
                    $refundRowKey = $refundResult->refundId ?: ('re_local_' . bin2hex(random_bytes(8)));
                    $refundUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                    $pdo->prepare("INSERT INTO {$prefix}payments
                        (uuid, user_id, order_id, payment_key, gateway, method, amount, status, paid_at, cancelled_at, cancel_reason, metadata, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, 'card', ?, 'refund', NULL, NOW(), ?, ?, NOW(), NOW())")
                        ->execute([
                            $refundUuid, $hSub['user_id'], $hostOrderNumberCancel,
                            $refundRowKey, ($payGw ?: ($payMgr ? $payMgr->getGatewayName() : 'unknown')), $refundedAmount,
                            'admin domain cancel: ' . $domain,
                            json_encode([
                                'source' => 'admin_domain_cancel',
                                'refund_for_charge' => $chargeId,
                                'original_payment_row_id' => $matched['payment_row_id'] ?? null,
                                'domain' => $domain,
                                'host_sub_id' => $hostSubId,
                            ], JSON_UNESCAPED_UNICODE),
                        ]);
                }

                // metadata 갱신
                $hMeta['added_domains'] = $newAdded;
                if (!empty($hMeta['domains'])) {
                    $hMeta['domains'] = array_values(array_filter($hMeta['domains'], fn($d) => $d !== $domain));
                }
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                    ->execute([json_encode($hMeta, JSON_UNESCAPED_UNICODE), $hostSubId]);

                $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'domain_admin_cancelled', ?, 'admin', ?)")
                    ->execute([
                        $orderIdLog,
                        json_encode([
                            'domain' => $domain,
                            'host_sub_id' => $hostSubId,
                            'refund_amount' => $refundedAmount,
                            'charge_id' => $chargeId,
                            'refund_id' => $refundResult ? ($refundResult->refundId ?? null) : null,
                        ], JSON_UNESCAPED_UNICODE),
                        $userId,
                    ]);
            } else {
                $domainSubId = (int)($input['domain_sub_id'] ?? 0);
                if (!$domainSubId) throw new Exception('domain_sub_id required');
                $dSt = $pdo->prepare("SELECT s.*, o.payment_id FROM {$prefix}subscriptions s LEFT JOIN {$prefix}orders o ON s.order_id = o.id WHERE s.id = ? AND s.type = 'domain' FOR UPDATE");
                $dSt->execute([$domainSubId]);
                $dSub = $dSt->fetch(PDO::FETCH_ASSOC);
                if (!$dSub) throw new Exception('domain sub not found');
                $orderIdLog = (int)$dSub['order_id'];
                $refundedAmount = (int)$dSub['billing_amount'];

                // 도메인 sub status 변경
                $dMeta = json_decode($dSub['metadata'] ?? '{}', true) ?: [];
                $dMeta['cancelled_at'] = date('c');
                $dMeta['cancelled_by'] = $userId;
                $pdo->prepare("UPDATE {$prefix}subscriptions SET status = 'cancelled', cancelled_at = NOW(), cancel_reason = 'admin: domain cancel', metadata = ? WHERE id = ?")
                    ->execute([json_encode($dMeta, JSON_UNESCAPED_UNICODE), $domainSubId]);

                // 결제 charge id 추정 — payments 테이블 또는 metadata 조회
                $chargeId = (string)($dMeta['payment_transaction_id'] ?? '');
                if (!$chargeId && !empty($dSub['payment_id'])) {
                    try {
                        $pSt = $pdo->prepare("SELECT gateway_payment_id FROM {$prefix}payments WHERE id = ?");
                        $pSt->execute([$dSub['payment_id']]);
                        $chargeId = (string)($pSt->fetchColumn() ?: '');
                    } catch (\Throwable $pe) {}
                }

                if ($chargeId && $refundedAmount > 0 && $gateway && method_exists($gateway, 'refund')) {
                    try {
                        $refundResult = $gateway->refund($chargeId, $refundedAmount, 'admin domain cancel: ' . $domain);
                        if (!($refundResult->success ?? false)) {
                            throw new Exception('refund failed: ' . ($refundResult->message ?? 'unknown'));
                        }
                    } catch (\Throwable $re) {
                        throw new Exception('PAY.JP refund error: ' . $re->getMessage());
                    }
                }

                $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'domain_admin_cancelled', ?, 'admin', ?)")
                    ->execute([
                        $orderIdLog,
                        json_encode([
                            'domain' => $domain,
                            'domain_sub_id' => $domainSubId,
                            'refund_amount' => $refundedAmount,
                            'charge_id' => $chargeId,
                            'refund_id' => $refundResult ? ($refundResult->refundId ?? null) : null,
                        ], JSON_UNESCAPED_UNICODE),
                        $userId,
                    ]);
            }

            $pdo->commit();
            echo json_encode([
                'success' => true,
                'refunded' => $refundedAmount,
                'refund_id' => $refundResult ? ($refundResult->refundId ?? null) : null,
                'charge_id' => $chargeId,
            ]);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[admin_domain_cancel] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;

    // ===== admin: 도메인 작업 완료 처리 (registrar_pending / manual_attach_pending → false) =====
    case 'admin_domain_complete':
        $userStmt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $userStmt->execute([$userId]);
        if (!in_array($userStmt->fetchColumn(), ['admin','supervisor'], true)) {
            echo json_encode(['success' => false, 'message' => '관리자 권한 필요']);
            exit;
        }
        $source = (string)($input['source'] ?? '');
        $domain = strtolower(trim((string)($input['domain'] ?? '')));
        if (!$domain) { echo json_encode(['success' => false, 'message' => 'domain required']); exit; }

        try {
            if ($source === 'mypage') {
                $hostSubId = (int)($input['host_sub_id'] ?? 0);
                if (!$hostSubId) { echo json_encode(['success' => false, 'message' => 'host_sub_id required']); exit; }
                $hSt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ?");
                $hSt->execute([$hostSubId]);
                $hSub = $hSt->fetch(PDO::FETCH_ASSOC);
                if (!$hSub) { echo json_encode(['success' => false, 'message' => 'host sub not found']); exit; }
                $hMeta = json_decode($hSub['metadata'] ?? '{}', true) ?: [];
                $changed = false;
                foreach (($hMeta['added_domains'] ?? []) as $i => $ad) {
                    if (($ad['domain'] ?? '') === $domain) {
                        $hMeta['added_domains'][$i]['registrar_pending'] = false;
                        $hMeta['added_domains'][$i]['manual_attach_pending'] = false;
                        $hMeta['added_domains'][$i]['completed_at'] = date('c');
                        $hMeta['added_domains'][$i]['completed_by'] = $userId;
                        $changed = true;
                    }
                }
                if (!$changed) { echo json_encode(['success' => false, 'message' => 'domain not found in added_domains']); exit; }
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                    ->execute([json_encode($hMeta, JSON_UNESCAPED_UNICODE), $hostSubId]);
                $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'domain_admin_completed', ?, 'admin', ?)")
                    ->execute([(int)$hSub['order_id'], json_encode(['domain' => $domain, 'host_sub_id' => $hostSubId], JSON_UNESCAPED_UNICODE), $userId]);
            } else {
                $domainSubId = (int)($input['domain_sub_id'] ?? 0);
                if (!$domainSubId) { echo json_encode(['success' => false, 'message' => 'domain_sub_id required']); exit; }
                $dSt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ? AND type = 'domain'");
                $dSt->execute([$domainSubId]);
                $dSub = $dSt->fetch(PDO::FETCH_ASSOC);
                if (!$dSub) { echo json_encode(['success' => false, 'message' => 'domain sub not found']); exit; }
                $dMeta = json_decode($dSub['metadata'] ?? '{}', true) ?: [];
                $dMeta['registrar_pending'] = false;
                $dMeta['manual_attach_pending'] = false;
                $dMeta['completed_at'] = date('c');
                $dMeta['completed_by'] = $userId;
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                    ->execute([json_encode($dMeta, JSON_UNESCAPED_UNICODE), $domainSubId]);
                $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'domain_admin_completed', ?, 'admin', ?)")
                    ->execute([(int)$dSub['order_id'], json_encode(['domain' => $domain, 'domain_sub_id' => $domainSubId], JSON_UNESCAPED_UNICODE), $userId]);
            }
            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            error_log('[admin_domain_complete] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;

    // ===== admin: 도메인 sub 를 호스팅 sub 의 added_domains 로 병합 + sub 삭제 =====
    case 'admin_merge_domain_sub':
        $userStmt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $userStmt->execute([$userId]);
        if (!in_array($userStmt->fetchColumn(), ['admin','supervisor'], true)) {
            echo json_encode(['success' => false, 'message' => '관리자 권한 필요']);
            exit;
        }
        $domainSubId = (int)($input['domain_sub_id'] ?? 0);
        $targetHostSubId = (int)($input['target_host_sub_id'] ?? 0);
        if (!$domainSubId || !$targetHostSubId) {
            echo json_encode(['success' => false, 'message' => 'domain_sub_id + target_host_sub_id 필요']);
            exit;
        }

        $dSt = $pdo->prepare("SELECT s.*, o.order_number, o.subtotal, o.tax, o.total FROM {$prefix}subscriptions s JOIN {$prefix}orders o ON s.order_id = o.id WHERE s.id = ? AND s.type = 'domain'");
        $dSt->execute([$domainSubId]);
        $domainSub = $dSt->fetch(PDO::FETCH_ASSOC);
        if (!$domainSub) {
            echo json_encode(['success' => false, 'message' => '도메인 subscription 을 찾을 수 없습니다.']);
            exit;
        }
        $hSt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ? AND type = 'hosting'");
        $hSt->execute([$targetHostSubId]);
        $hostSub = $hSt->fetch(PDO::FETCH_ASSOC);
        if (!$hostSub) {
            echo json_encode(['success' => false, 'message' => '호스팅 subscription 을 찾을 수 없습니다.']);
            exit;
        }
        if ($domainSub['user_id'] !== $hostSub['user_id']) {
            echo json_encode(['success' => false, 'message' => '두 subscription 의 사용자가 다릅니다.']);
            exit;
        }

        $dMeta = json_decode($domainSub['metadata'] ?? '{}', true) ?: [];
        $hMeta = json_decode($hostSub['metadata'] ?? '{}', true) ?: [];
        $hMeta['added_domains'] = $hMeta['added_domains'] ?? [];

        try {
            $pdo->beginTransaction();
            $movedDomains = [];
            foreach ($dMeta['domains'] ?? [] as $dm) {
                // 중복 체크
                $exists = false;
                foreach ($hMeta['added_domains'] as $ad) {
                    if (($ad['domain'] ?? '') === $dm) { $exists = true; break; }
                }
                if ($exists) continue;
                $isFree = !empty($dMeta['free_subdomain']);
                $isExisting = !empty($dMeta['existing']);
                $opt = $isFree ? 'free' : ($isExisting ? 'existing' : 'new');
                $hMeta['added_domains'][] = [
                    'domain' => $dm,
                    'option' => $opt,
                    'started_at' => $domainSub['started_at'],
                    'expires_at' => $domainSub['expires_at'],
                    'subtotal' => (int)$domainSub['subtotal'],
                    'tax' => (int)$domainSub['tax'],
                    'total' => (int)$domainSub['total'],
                    'currency' => $domainSub['currency'],
                    'order_id' => (int)$domainSub['order_id'],
                    'paid_at' => $domainSub['started_at'],
                    'auto_renew' => !empty($domainSub['auto_renew']),
                    'registrar_pending' => !empty($dMeta['registrar_pending']),
                    'manual_attach_pending' => !empty($dMeta['manual_attach_pending']),
                    'migrated_from_sub' => (int)$domainSub['id'],
                ];
                $movedDomains[] = $dm;
            }
            $hMeta['domains'] = array_values(array_unique(array_merge($hMeta['domains'] ?? [], $movedDomains)));

            $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                ->execute([json_encode($hMeta, JSON_UNESCAPED_UNICODE), $hostSub['id']]);
            $pdo->prepare("DELETE FROM {$prefix}subscriptions WHERE id = ?")->execute([$domainSub['id']]);
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'sub_migrated_to_added_domain', ?, 'admin', ?)")
                ->execute([
                    $domainSub['order_id'],
                    json_encode([
                        'from_sub' => (int)$domainSub['id'],
                        'to_host_sub' => (int)$hostSub['id'],
                        'domains' => $movedDomains,
                    ], JSON_UNESCAPED_UNICODE),
                    $userId,
                ]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[admin_merge_domain_sub] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'DB 오류: ' . $e->getMessage()]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'moved_domains' => $movedDomains,
            'host_sub_id' => (int)$hostSub['id'],
            'deleted_sub' => (int)$domainSub['id'],
        ]);
        exit;

    // ===== admin: 부가서비스 취소 — sub status='cancelled' + PAY.JP 환불 + 환불 row 전표 =====
    case 'admin_cancel_addon':
        $userStmt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $userStmt->execute([$userId]);
        if (!in_array($userStmt->fetchColumn(), ['admin','supervisor'], true)) {
            echo json_encode(['success' => false, 'message' => '관리자 권한 필요']);
            exit;
        }
        $aSubId = (int)($input['subscription_id'] ?? 0);
        if (!$aSubId) { echo json_encode(['success' => false, 'message' => 'subscription_id required']); exit; }
        $aSt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ? AND type = 'addon'");
        $aSt->execute([$aSubId]);
        $aSub = $aSt->fetch(PDO::FETCH_ASSOC);
        if (!$aSub) { echo json_encode(['success' => false, 'message' => 'addon sub not found']); exit; }
        if ($aSub['status'] === 'cancelled') {
            echo json_encode(['success' => false, 'message' => '이미 취소된 sub 입니다.']);
            exit;
        }

        $aMeta = json_decode($aSub['metadata'] ?? '{}', true) ?: [];
        $chargeId = (string)($aMeta['payment_transaction_id'] ?? '');
        $paymentRowId = (int)($aMeta['payment_row_id'] ?? 0);
        if (!$chargeId && $paymentRowId) {
            $pkSt = $pdo->prepare("SELECT payment_key, gateway FROM {$prefix}payments WHERE id = ?");
            $pkSt->execute([$paymentRowId]);
            $row = $pkSt->fetch(PDO::FETCH_ASSOC) ?: [];
            $chargeId = (string)($row['payment_key'] ?? '');
            $payGw = (string)($row['gateway'] ?? '');
        } else {
            $payGw = (string)($aSub['payment_gateway'] ?? '');
        }

        $refundAmount = (int)$aSub['billing_amount'];

        require_once BASE_PATH . '/rzxlib/Modules/Payment/PaymentManager.php';
        $payMgr = null; $gateway = null;
        try {
            $payMgr = new \RzxLib\Modules\Payment\PaymentManager($pdo, $prefix);
            $gateway = $payMgr->gateway($payGw ?: null);
        } catch (\Throwable $e) { error_log('[admin_cancel_addon] gw init: ' . $e->getMessage()); }

        try {
            $pdo->beginTransaction();
            $refundResult = null;

            if ($refundAmount > 0 && $chargeId) {
                if (!$gateway || !method_exists($gateway, 'refund')) {
                    throw new Exception('환불 불가: 게이트웨이 초기화 실패 (gateway=' . ($payGw ?: '?') . ')');
                }
                $refundResult = $gateway->refund($chargeId, $refundAmount, 'admin addon cancel: ' . $aSub['label']);
                if (!($refundResult->success ?? false)) {
                    $detail = $refundResult->failureReason ?? '';
                    if (!$detail && !empty($refundResult->raw)) {
                        $detail = json_encode($refundResult->raw, JSON_UNESCAPED_UNICODE);
                    }
                    $alreadyRefunded = stripos($detail, 'already been refunded') !== false || stripos($detail, 'already_refunded') !== false;
                    if ($alreadyRefunded) {
                        error_log("[admin_cancel_addon] charge {$chargeId} already refunded — proceed");
                        $refundResult = new \RzxLib\Modules\Payment\DTO\RefundResult([
                            'success' => true, 'refund_id' => null, 'amount' => $refundAmount,
                            'status' => 'refunded', 'raw' => ['note' => 'already_refunded_on_pg'],
                        ]);
                    } else {
                        throw new Exception('PG 환불 응답 실패: ' . ($detail ?: 'unknown'));
                    }
                }

                // 환불 row INSERT (전표 발생)
                $hostOrderNumberSt = $pdo->prepare("SELECT order_number FROM {$prefix}orders WHERE id = ?");
                $hostOrderNumberSt->execute([(int)$aSub['order_id']]);
                $hostOrderNumber = (string)($hostOrderNumberSt->fetchColumn() ?: '');
                $refundRowKey = $refundResult->refundId ?: ('re_local_' . bin2hex(random_bytes(8)));
                $refundUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $pdo->prepare("INSERT INTO {$prefix}payments
                    (uuid, user_id, order_id, payment_key, gateway, method, amount, status, paid_at, cancelled_at, cancel_reason, metadata, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, 'card', ?, 'refund', NULL, NOW(), ?, ?, NOW(), NOW())")
                    ->execute([
                        $refundUuid, $aSub['user_id'], $hostOrderNumber,
                        $refundRowKey, ($payGw ?: ($payMgr ? $payMgr->getGatewayName() : 'unknown')),
                        $refundAmount, 'admin addon cancel: ' . $aSub['label'],
                        json_encode([
                            'source' => 'admin_cancel_addon',
                            'refund_for_charge' => $chargeId,
                            'original_payment_row_id' => $paymentRowId,
                            'addon_sub_id' => $aSubId,
                            'label' => $aSub['label'],
                        ], JSON_UNESCAPED_UNICODE),
                    ]);
            }

            // sub 취소
            $aMeta['cancelled_at'] = date('c');
            $aMeta['cancelled_by'] = $userId;
            $pdo->prepare("UPDATE {$prefix}subscriptions SET status = 'cancelled', cancelled_at = NOW(), cancel_reason = 'admin: addon cancel', metadata = ? WHERE id = ?")
                ->execute([json_encode($aMeta, JSON_UNESCAPED_UNICODE), $aSubId]);

            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'addon_admin_cancelled', ?, 'admin', ?)")
                ->execute([
                    (int)$aSub['order_id'],
                    json_encode([
                        'sub_id' => $aSubId,
                        'label' => $aSub['label'],
                        'refund_amount' => $refundAmount,
                        'charge_id' => $chargeId,
                        'refund_id' => $refundResult ? ($refundResult->refundId ?? null) : null,
                    ], JSON_UNESCAPED_UNICODE),
                    $userId,
                ]);

            $pdo->commit();
            echo json_encode([
                'success' => true,
                'refunded' => $refundAmount,
                'refund_id' => $refundResult ? ($refundResult->refundId ?? null) : null,
            ]);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[admin_cancel_addon] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;

    // ===== 1:1 상담 — 티켓 목록 조회 (사용자: 본인 / 관리자: 모두 또는 특정 유저) =====
    case 'support_list_tickets':
        $isAdmin = false;
        $rSt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $rSt->execute([$userId]);
        $isAdmin = in_array($rSt->fetchColumn(), ['admin','supervisor'], true);

        $page = max(1, (int)($input['page'] ?? 1));
        $perPage = max(1, min(50, (int)($input['per_page'] ?? 5)));
        $offset = ($page - 1) * $perPage;
        $hostSubId = (int)($input['host_subscription_id'] ?? 0);
        $statusFilter = (string)($input['status'] ?? '');

        $where = [];
        $params = [];
        if (!$isAdmin) {
            $where[] = 't.user_id = ?';
            $params[] = $userId;
        }
        if ($hostSubId) {
            $where[] = 't.host_subscription_id = ?';
            $params[] = $hostSubId;
        }
        if (in_array($statusFilter, ['open','answered','closed'], true)) {
            $where[] = 't.status = ?';
            $params[] = $statusFilter;
        }
        $whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $cntSt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}support_tickets t $whereSQL");
        $cntSt->execute($params);
        $total = (int)$cntSt->fetchColumn();

        $listSt = $pdo->prepare("SELECT t.*, u.email AS user_email, u.name AS user_name
            FROM {$prefix}support_tickets t
            LEFT JOIN {$prefix}users u ON t.user_id = u.id
            $whereSQL
            ORDER BY t.last_message_at DESC, t.id DESC LIMIT $perPage OFFSET $offset");
        $listSt->execute($params);
        $tickets = $listSt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'tickets' => $tickets,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'is_admin' => $isAdmin,
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // ===== 1:1 상담 — 티켓 + 메시지 thread 조회 =====
    case 'support_get_ticket':
        $ticketId = (int)($input['ticket_id'] ?? 0);
        if (!$ticketId) { echo json_encode(['success' => false, 'message' => 'ticket_id required']); exit; }

        $rSt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $rSt->execute([$userId]);
        $isAdmin = in_array($rSt->fetchColumn(), ['admin','supervisor'], true);

        $tSt = $pdo->prepare("SELECT t.*, u.email AS user_email, u.name AS user_name FROM {$prefix}support_tickets t LEFT JOIN {$prefix}users u ON t.user_id = u.id WHERE t.id = ?");
        $tSt->execute([$ticketId]);
        $ticket = $tSt->fetch(PDO::FETCH_ASSOC);
        if (!$ticket) { echo json_encode(['success' => false, 'message' => 'ticket not found']); exit; }
        if (!$isAdmin && $ticket['user_id'] !== $userId) {
            echo json_encode(['success' => false, 'message' => 'permission denied']);
            exit;
        }

        $mSt = $pdo->prepare("SELECT m.*, u.email AS sender_email, u.name AS sender_name FROM {$prefix}support_messages m LEFT JOIN {$prefix}users u ON m.sender_user_id = u.id WHERE m.ticket_id = ? ORDER BY m.created_at ASC, m.id ASC");
        $mSt->execute([$ticketId]);
        $messages = $mSt->fetchAll(PDO::FETCH_ASSOC);
        // attachments JSON 디코딩
        foreach ($messages as &$_m) {
            $_m['attachments'] = json_decode($_m['attachments'] ?? '[]', true) ?: [];
        }
        unset($_m);

        // 본인이 읽으면 unread 플래그 끔
        if ($isAdmin && $ticket['unread_by_admin']) {
            $pdo->prepare("UPDATE {$prefix}support_tickets SET unread_by_admin = 0 WHERE id = ?")->execute([$ticketId]);
        } elseif (!$isAdmin && $ticket['unread_by_user']) {
            $pdo->prepare("UPDATE {$prefix}support_tickets SET unread_by_user = 0 WHERE id = ?")->execute([$ticketId]);
        }

        echo json_encode([
            'success' => true,
            'ticket' => $ticket,
            'messages' => $messages,
            'is_admin' => $isAdmin,
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // ===== 1:1 상담 — 새 티켓 생성 (사용자) =====
    case 'support_create_ticket':
        $hostSubId = (int)($input['host_subscription_id'] ?? 0);
        $addonSubId = (int)($input['addon_subscription_id'] ?? 0);
        $title = trim((string)($input['title'] ?? ''));
        $body = trim((string)($input['body'] ?? ''));
        if ($title === '' || $body === '') {
            echo json_encode(['success' => false, 'message' => 'title + body required']);
            exit;
        }
        if (mb_strlen($title) > 200) $title = mb_substr($title, 0, 200);

        // 호스팅 sub 권한 검증 (있을 때)
        if ($hostSubId) {
            $hSub = getOwnedSubscription($pdo, $prefix, $hostSubId, $userId);
            if (!$hSub) { echo json_encode(['success' => false, 'message' => 'hosting sub access denied']); exit; }
        }
        // 부가서비스 sub 권한 (있을 때)
        if ($addonSubId) {
            $aSub = getOwnedSubscription($pdo, $prefix, $addonSubId, $userId);
            if (!$aSub) { echo json_encode(['success' => false, 'message' => 'addon sub access denied']); exit; }
        }

        $rawAttachments = is_array($input['attachments'] ?? null) ? $input['attachments'] : [];

        try {
            $pdo->beginTransaction();
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $now = date('Y-m-d H:i:s');
            $tIns = $pdo->prepare("INSERT INTO {$prefix}support_tickets
                (uuid, user_id, host_subscription_id, addon_subscription_id, title, status, last_message_at, last_message_by, unread_by_admin)
                VALUES (?, ?, ?, ?, ?, 'open', ?, 'user', 1)");
            $tIns->execute([
                $uuid, $userId,
                $hostSubId ?: null,
                $addonSubId ?: null,
                $title,
                $now,
            ]);
            $ticketId = (int)$pdo->lastInsertId();

            // 첨부파일 처리 (pending → 티켓 폴더 이동)
            $cleanAtts = processSupportAttachments($rawAttachments, $ticketId, $userId);
            $attsJson = empty($cleanAtts) ? null : json_encode($cleanAtts, JSON_UNESCAPED_UNICODE);

            $pdo->prepare("INSERT INTO {$prefix}support_messages (ticket_id, sender_user_id, is_admin, body, attachments) VALUES (?, ?, 0, ?, ?)")
                ->execute([$ticketId, $userId, $body, $attsJson]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[support_create_ticket] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
            exit;
        }

        // 관리자 푸시 알림
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
                '💬 1:1 상담 신규 문의',
                $title,
                '/' . trim($adminPath, '/') . '/support-tickets?ticket=' . $ticketId
            );
        } catch (\Throwable $ne) {
            error_log('[support_create_ticket] notify: ' . $ne->getMessage());
        }

        echo json_encode(['success' => true, 'ticket_id' => $ticketId, 'uuid' => $uuid]);
        exit;

    // ===== 1:1 상담 — 메시지 추가 (사용자 또는 관리자 답변) =====
    case 'support_post_message':
        $ticketId = (int)($input['ticket_id'] ?? 0);
        $body = trim((string)($input['body'] ?? ''));
        if (!$ticketId || $body === '') {
            echo json_encode(['success' => false, 'message' => 'ticket_id + body required']);
            exit;
        }

        $rSt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $rSt->execute([$userId]);
        $isAdmin = in_array($rSt->fetchColumn(), ['admin','supervisor'], true);

        $tSt = $pdo->prepare("SELECT * FROM {$prefix}support_tickets WHERE id = ?");
        $tSt->execute([$ticketId]);
        $ticket = $tSt->fetch(PDO::FETCH_ASSOC);
        if (!$ticket) { echo json_encode(['success' => false, 'message' => 'ticket not found']); exit; }
        if (!$isAdmin && $ticket['user_id'] !== $userId) {
            echo json_encode(['success' => false, 'message' => 'permission denied']);
            exit;
        }
        if ($ticket['status'] === 'closed') {
            echo json_encode(['success' => false, 'message' => '이미 종료된 상담입니다.']);
            exit;
        }

        $rawAttachments = is_array($input['attachments'] ?? null) ? $input['attachments'] : [];

        try {
            $pdo->beginTransaction();
            $cleanAtts = processSupportAttachments($rawAttachments, $ticketId, $userId);
            $attsJson = empty($cleanAtts) ? null : json_encode($cleanAtts, JSON_UNESCAPED_UNICODE);

            $pdo->prepare("INSERT INTO {$prefix}support_messages (ticket_id, sender_user_id, is_admin, body, attachments) VALUES (?, ?, ?, ?, ?)")
                ->execute([$ticketId, $userId, $isAdmin ? 1 : 0, $body, $attsJson]);
            // ticket 상태 갱신
            if ($isAdmin) {
                $pdo->prepare("UPDATE {$prefix}support_tickets SET status = 'answered', last_message_at = NOW(), last_message_by = 'admin', unread_by_user = 1, unread_by_admin = 0 WHERE id = ?")
                    ->execute([$ticketId]);
            } else {
                $pdo->prepare("UPDATE {$prefix}support_tickets SET status = 'open', last_message_at = NOW(), last_message_by = 'user', unread_by_admin = 1, unread_by_user = 0 WHERE id = ?")
                    ->execute([$ticketId]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[support_post_message] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
            exit;
        }

        // 푸시 알림
        try {
            require_once BASE_PATH . '/vendor/autoload.php';
            $notifier = new \RzxLib\Core\Notification\WebPushNotifier($pdo, $prefix);
            $adminPath = 'admin';
            try {
                $cfgSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'admin_path' LIMIT 1");
                $cfgSt->execute();
                $adminPath = trim((string)($cfgSt->fetchColumn() ?: 'admin'));
            } catch (\Throwable $e) {}

            if ($isAdmin) {
                $notifier->notifyUser(
                    (string)$ticket['user_id'],
                    '💬 1:1 상담 답변 도착',
                    $ticket['title'],
                    '/mypage/services#support-' . $ticketId
                );
            } else {
                $notifier->notifyAdmins(
                    '💬 1:1 상담 답변 대기',
                    $ticket['title'],
                    '/' . trim($adminPath, '/') . '/support-tickets?ticket=' . $ticketId
                );
            }
        } catch (\Throwable $ne) {
            error_log('[support_post_message] notify: ' . $ne->getMessage());
        }

        echo json_encode(['success' => true]);
        exit;

    // ===== 1:1 상담 — 종료 (사용자 또는 관리자) =====
    case 'support_close_ticket':
        $ticketId = (int)($input['ticket_id'] ?? 0);
        if (!$ticketId) { echo json_encode(['success' => false, 'message' => 'ticket_id required']); exit; }

        $rSt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $rSt->execute([$userId]);
        $isAdmin = in_array($rSt->fetchColumn(), ['admin','supervisor'], true);

        $tSt = $pdo->prepare("SELECT user_id FROM {$prefix}support_tickets WHERE id = ?");
        $tSt->execute([$ticketId]);
        $ownerId = $tSt->fetchColumn();
        if (!$ownerId) { echo json_encode(['success' => false, 'message' => 'ticket not found']); exit; }
        if (!$isAdmin && $ownerId !== $userId) {
            echo json_encode(['success' => false, 'message' => 'permission denied']);
            exit;
        }

        $pdo->prepare("UPDATE {$prefix}support_tickets SET status = 'closed', closed_at = NOW(), closed_by = ? WHERE id = ?")
            ->execute([$userId, $ticketId]);
        echo json_encode(['success' => true]);
        exit;

    // ===== 1:1 상담 — unread 플래그 수동 갱신 (목록 진입 시 자동 호출) =====
    case 'support_mark_read':
        $ticketId = (int)($input['ticket_id'] ?? 0);
        if (!$ticketId) { echo json_encode(['success' => false, 'message' => 'ticket_id required']); exit; }
        $rSt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $rSt->execute([$userId]);
        $isAdmin = in_array($rSt->fetchColumn(), ['admin','supervisor'], true);
        if ($isAdmin) {
            $pdo->prepare("UPDATE {$prefix}support_tickets SET unread_by_admin = 0 WHERE id = ?")->execute([$ticketId]);
        } else {
            $pdo->prepare("UPDATE {$prefix}support_tickets SET unread_by_user = 0 WHERE id = ? AND user_id = ?")->execute([$ticketId, $userId]);
        }
        echo json_encode(['success' => true]);
        exit;

    // ===== 부가서비스 결제 (recurring 유료 — 기술 지원 등) =====
    case 'pay_addon_recurring':
        $hostSubId = (int)($input['host_subscription_id'] ?? 0);
        $addonId = trim((string)($input['addon_id'] ?? ''));
        $label = trim((string)($input['label'] ?? ''));
        $unitPrice = (int)($input['unit_price'] ?? 0);
        if (!$hostSubId || $addonId === '' || $unitPrice <= 0) {
            echo json_encode(['success' => false, 'message' => 'invalid input']);
            exit;
        }
        $hostSub = getOwnedSubscription($pdo, $prefix, $hostSubId, $userId);
        if (!$hostSub || $hostSub['type'] !== 'hosting' || $hostSub['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => '활성 호스팅 구독이 필요합니다.']);
            exit;
        }
        // settings 에서 가격 검증
        $aSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'service_addons' LIMIT 1");
        $aSt->execute();
        $addons = json_decode($aSt->fetchColumn() ?: '[]', true) ?: [];
        $addonDef = null;
        foreach ($addons as $a) {
            if (($a['_id'] ?? '') === $addonId || trim((string)($a['label'] ?? '')) === $label) { $addonDef = $a; break; }
        }
        if (!$addonDef || (int)($addonDef['price'] ?? 0) !== $unitPrice) {
            echo json_encode(['success' => false, 'message' => '부가서비스 가격 검증 실패']);
            exit;
        }

        // 중복 활성 sub 체크
        $dupSt = $pdo->prepare("SELECT id FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'addon' AND label = ? AND status IN ('pending','paid','active') LIMIT 1");
        $dupSt->execute([$hostSub['order_id'], $label]);
        if ($dupSt->fetchColumn()) {
            echo json_encode(['success' => false, 'message' => '이미 신청된 부가서비스입니다.']);
            exit;
        }

        // 부가세 계산
        $taxRate = 10;
        $taxAmount = (int)round($unitPrice * $taxRate / 100);
        $grandTotal = $unitPrice + $taxAmount;
        $currency = $hostSub['currency'] ?? 'JPY';

        // PAY.JP 결제
        $customerId = $hostSub['payment_customer_id'] ?? '';
        $cardToken = trim($input['card_token'] ?? '');
        if (!$customerId && !$cardToken) {
            echo json_encode(['success' => false, 'message' => '카드 정보가 필요합니다.']);
            exit;
        }

        require_once BASE_PATH . '/rzxlib/Modules/Payment/PaymentManager.php';
        $paymentId = null; $gwName = null; $gateway = null;
        try {
            $payMgr = new \RzxLib\Modules\Payment\PaymentManager($pdo, $prefix);
            $gateway = $payMgr->gateway();
            $gwName = $payMgr->getGatewayName();
            if (!method_exists($gateway, 'chargeCustomer')) {
                echo json_encode(['success' => false, 'message' => '저장 카드 결제 미지원']);
                exit;
            }
            $newCustomer = false;
            if ($cardToken) {
                if (!method_exists($gateway, 'createCustomer')) {
                    echo json_encode(['success' => false, 'message' => '카드 등록 미지원']);
                    exit;
                }
                $emSt = $pdo->prepare("SELECT email FROM {$prefix}users WHERE id = ?");
                $emSt->execute([$userId]);
                $userEmail = $emSt->fetchColumn() ?: '';
                $cardHolder = trim($input['card_holder'] ?? '');
                $custMeta = ['add_addon' => $addonId];
                if ($cardHolder !== '') $custMeta['card_holder'] = $cardHolder;
                $cr = $gateway->createCustomer($cardToken, $userEmail, $custMeta);
                if (!($cr['success'] ?? false)) {
                    echo json_encode(['success' => false, 'message' => '카드 등록 실패: ' . ($cr['message'] ?? ''), 'card_error' => true]);
                    exit;
                }
                $customerId = $cr['customer_id'];
                $newCustomer = true;
            }
            $desc = "VosCMS Addon {$addonId} ({$label}) order#{$hostSub['order_id']}";
            $payResult = $gateway->chargeCustomer($customerId, $grandTotal, strtolower($currency), $desc);
        } catch (\Throwable $e) {
            error_log("[pay_addon_recurring] gateway: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => '결제 처리 중 오류가 발생했습니다.']);
            exit;
        }
        if (!$payResult->isSuccessful()) {
            $code = (string)($payResult->failureCode ?? '');
            $cardErrCodes = ['card_declined','expired_card','incorrect_card_data','invalid_card_data','invalid_expiry_month','invalid_expiry_year','invalid_cvc','invalid_number','processing_error','unacceptable_brand','invalid_card','card_flagged'];
            if (!empty($newCustomer) && $customerId) { try { $gateway->deleteCustomer($customerId); } catch (\Throwable $de) {} }
            echo json_encode(['success' => false, 'message' => $payResult->failureMessage ?? '결제 실패', 'card_error' => in_array($code, $cardErrCodes, true)]);
            exit;
        }
        $paymentId = $payResult->transactionId;

        // host order_number
        $oNumSt = $pdo->prepare("SELECT order_number FROM {$prefix}orders WHERE id = ?");
        $oNumSt->execute([$hostSub['order_id']]);
        $hostOrderNumber = (string)($oNumSt->fetchColumn() ?: '');

        // 트랜잭션 — payments + addon sub
        try {
            $pdo->beginTransaction();
            $now = date('Y-m-d H:i:s');
            $expires = date('Y-m-d H:i:s', strtotime('+1 year'));

            // payments INSERT (전표)
            $paymentRowId = null;
            $payUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            try {
                $pIns = $pdo->prepare("INSERT INTO {$prefix}payments
                    (uuid, user_id, order_id, payment_key, gateway, method, amount, status, paid_at, metadata, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, 'card', ?, 'paid', NOW(), ?, NOW(), NOW())");
                $pIns->execute([
                    $payUuid, $userId, $hostOrderNumber, $paymentId, $gwName, $grandTotal,
                    json_encode([
                        'source' => 'mypage_pay_addon_recurring',
                        'addon_id' => $addonId,
                        'label' => $label,
                        'subtotal' => $unitPrice,
                        'tax' => $taxAmount,
                        'currency' => $currency,
                        'host_sub_id' => $hostSubId,
                    ], JSON_UNESCAPED_UNICODE),
                ]);
                $paymentRowId = (int)$pdo->lastInsertId();
            } catch (\PDOException $pe) {
                if (($pe->errorInfo[1] ?? 0) === 1062) {
                    $existSt = $pdo->prepare("SELECT id FROM {$prefix}payments WHERE payment_key = ? LIMIT 1");
                    $existSt->execute([$paymentId]);
                    $paymentRowId = (int)$existSt->fetchColumn();
                } else { throw $pe; }
            }

            // addon sub INSERT (recurring, 1년)
            $sIns = $pdo->prepare("INSERT INTO {$prefix}subscriptions
                (order_id, user_id, type, service_class, label, unit_price, quantity, billing_amount, billing_cycle, billing_months,
                 currency, started_at, billing_start, expires_at, next_billing_at, auto_renew, payment_customer_id, payment_gateway, status, metadata)
                VALUES (?, ?, 'addon', 'recurring', ?, ?, 1, ?, 'yearly', 12, ?, ?, ?, ?, ?, 1, ?, ?, 'active', ?)");
            $sIns->execute([
                $hostSub['order_id'], $userId, $label,
                $unitPrice, $grandTotal, $currency,
                $now, $now, $expires, $expires,
                $customerId, $gwName,
                json_encode([
                    'addon_id' => $addonId,
                    'subtotal' => $unitPrice,
                    'tax' => $taxAmount,
                    'total' => $grandTotal,
                    'payment_transaction_id' => $paymentId,
                    'payment_row_id' => $paymentRowId,
                ], JSON_UNESCAPED_UNICODE),
            ]);
            $newSubId = (int)$pdo->lastInsertId();

            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'addon_paid_added', ?, 'user', ?)")
                ->execute([
                    (int)$hostSub['order_id'],
                    json_encode([
                        'addon_id' => $addonId,
                        'label' => $label,
                        'sub_id' => $newSubId,
                        'subtotal' => $unitPrice,
                        'tax' => $taxAmount,
                        'total' => $grandTotal,
                        'payment_transaction_id' => $paymentId,
                    ], JSON_UNESCAPED_UNICODE),
                    $userId,
                ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("[pay_addon_recurring] db error: " . $e->getMessage());
            // 안전망: 자동 환불
            if ($paymentId && $grandTotal > 0 && $gateway && method_exists($gateway, 'refund')) {
                try { $gateway->refund($paymentId, $grandTotal, 'auto-rollback: db error'); } catch (\Throwable $re) {}
            }
            echo json_encode(['success' => false, 'message' => 'DB 처리 오류: ' . $e->getMessage()]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'sub_id' => $newSubId,
            'addon_id' => $addonId,
            'subtotal' => $unitPrice,
            'tax' => $taxAmount,
            'total' => $grandTotal,
        ]);
        exit;

    // ===== 부가서비스 신청 요청 (관리자 후속 처리) =====
    case 'request_addon':
        $hostSubId = (int)($input['host_subscription_id'] ?? 0);
        $addonId = trim((string)($input['addon_id'] ?? ''));
        $label = trim((string)($input['label'] ?? ''));
        if (!$hostSubId || $addonId === '') {
            echo json_encode(['success' => false, 'message' => 'invalid input']);
            exit;
        }
        $hostSub = getOwnedSubscription($pdo, $prefix, $hostSubId, $userId);
        if (!$hostSub || $hostSub['type'] !== 'hosting') {
            echo json_encode(['success' => false, 'message' => '호스팅 구독을 찾을 수 없습니다.']);
            exit;
        }
        try {
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'addon_request_pending', ?, 'user', ?)")
                ->execute([
                    (int)$hostSub['order_id'],
                    json_encode([
                        'addon_id' => $addonId,
                        'label' => $label,
                        'host_sub_id' => $hostSubId,
                        'requested_at' => date('c'),
                    ], JSON_UNESCAPED_UNICODE),
                    $userId,
                ]);
            // 관리자 푸시 알림
            try {
                $autoload = BASE_PATH . '/vendor/autoload.php';
                if (file_exists($autoload)) require_once $autoload;
                $notifier = new \RzxLib\Core\Notification\WebPushNotifier($pdo, $prefix);
                $adminPath = 'admin';
                try {
                    $cfgSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'admin_path' LIMIT 1");
                    $cfgSt->execute();
                    $adminPath = trim((string)($cfgSt->fetchColumn() ?: 'admin'));
                } catch (\Throwable $e) {}
                $notifier->notifyAdmins(
                    '🛠️ 부가서비스 신청',
                    $label . ' (host_sub#' . $hostSubId . ')',
                    '/' . trim($adminPath, '/') . '/service-orders/' . $hostSub['order_id']
                );
            } catch (\Throwable $ne) {
                error_log('[request_addon] notify: ' . $ne->getMessage());
            }
            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            error_log('[request_addon] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;

    // ===== 도메인 추가 (구매/연결) — 결제 + 새 order/subscription 생성 =====
    case 'pay_domain':
        $hostSubId = (int)($input['hosting_subscription_id'] ?? 0);
        $opt = (string)($input['domain_option'] ?? '');
        $domain = strtolower(trim((string)($input['domain'] ?? '')));
        $unitPrice = (int)($input['unit_price'] ?? 0);
        if (!$hostSubId || !in_array($opt, ['free', 'new', 'existing'], true) || $domain === '') {
            echo json_encode(['success' => false, 'message' => 'invalid input']);
            exit;
        }
        if (!preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?\.[a-z]{2,}$/i', $domain)) {
            echo json_encode(['success' => false, 'message' => 'invalid domain format']);
            exit;
        }
        $hostSub = getOwnedSubscription($pdo, $prefix, $hostSubId, $userId);
        if (!$hostSub || $hostSub['type'] !== 'hosting' || $hostSub['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => '활성 호스팅 구독이 필요합니다.']);
            exit;
        }

        // 중복 도메인 체크 (도메인 sub + 호스팅 sub의 added_domains)
        $dupSt = $pdo->prepare("SELECT id FROM {$prefix}subscriptions WHERE status IN ('pending','paid','active') AND user_id=? AND (JSON_CONTAINS(metadata, JSON_QUOTE(?), '$.domains') OR JSON_SEARCH(metadata, 'one', ?, NULL, '$.added_domains[*].domain') IS NOT NULL) LIMIT 1");
        $dupSt->execute([$userId, $domain, $domain]);
        if ($dupSt->fetchColumn()) {
            echo json_encode(['success' => false, 'message' => '이미 등록된 도메인입니다.']);
            exit;
        }

        // settings 조회 (서버측 가격/zone 검증)
        $sSt = $pdo->prepare("SELECT `key`,`value` FROM {$prefix}settings WHERE `key` IN ('service_domain_pricing','service_free_domains','service_blocked_subdomains','service_currency')");
        $sSt->execute();
        $sx = [];
        while ($r = $sSt->fetch(PDO::FETCH_ASSOC)) $sx[$r['key']] = $r['value'];
        $pricing = json_decode($sx['service_domain_pricing'] ?? '[]', true) ?: [];
        $freeZones = json_decode($sx['service_free_domains'] ?? '[]', true) ?: [];
        $blocked = json_decode($sx['service_blocked_subdomains'] ?? '[]', true) ?: [];
        $currency = $sx['service_currency'] ?? 'JPY';

        $serverPrice = 0;
        $domainMeta = ['domains' => [$domain]];
        if ($opt === 'free') {
            // 마지막 두 라벨 이상이 무료 zone 과 일치해야 함
            $matched = false;
            foreach ($freeZones as $z) {
                if (str_ends_with($domain, '.' . $z) && substr_count($domain, '.') === substr_count($z, '.') + 1) {
                    $matched = true;
                    $sub = substr($domain, 0, -strlen('.' . $z));
                    foreach ($blocked as $b) {
                        if (strtolower(str_replace('*', '', $b)) === $sub) {
                            echo json_encode(['success' => false, 'message' => '사용할 수 없는 서브도메인입니다.']);
                            exit;
                        }
                    }
                    break;
                }
            }
            if (!$matched) {
                echo json_encode(['success' => false, 'message' => '무료 도메인 zone이 아닙니다.']);
                exit;
            }
            $serverPrice = 0;
            $domainMeta['free_subdomain'] = true;
        } elseif ($opt === 'new') {
            $tld = '.' . implode('.', array_slice(explode('.', $domain), 1));
            $found = null;
            foreach ($pricing as $p) {
                if (($p['tld'] ?? '') === $tld && !empty($p['active'])) { $found = $p; break; }
            }
            if (!$found) {
                echo json_encode(['success' => false, 'message' => '지원하지 않는 TLD 입니다.']);
                exit;
            }
            $serverPrice = (int)(!empty($found['vip_price']) ? $found['vip_price'] : ($found['price'] ?? 0));
            if ($serverPrice <= 0 || $serverPrice !== $unitPrice) {
                echo json_encode(['success' => false, 'message' => '가격 검증 실패']);
                exit;
            }
            $domainMeta['registrar_pending'] = true;
        } else {
            // existing — 결제 없음, 관리자 수동 attach 대기
            $serverPrice = 0;
            $domainMeta['existing'] = true;
            $domainMeta['manual_attach_pending'] = true;
        }
        $domainMeta['primary_domain'] = $domain;

        // 부가세 (10%) — 결제 금액 = 단가 + 부가세
        $taxRate = 10;
        $taxAmount = (int)round($serverPrice * $taxRate / 100);
        $grandTotal = $serverPrice + $taxAmount;

        // 결제 (paid 시)
        $paymentId = null; $gwName = null;
        if ($grandTotal > 0) {
            $customerId = $hostSub['payment_customer_id'] ?? '';
            $cardToken = trim($input['card_token'] ?? '');
            if (!$customerId && !$cardToken) {
                echo json_encode(['success' => false, 'message' => '카드 정보가 필요합니다.']);
                exit;
            }
            require_once BASE_PATH . '/rzxlib/Modules/Payment/PaymentManager.php';
            try {
                $payMgr = new \RzxLib\Modules\Payment\PaymentManager($pdo, $prefix);
                $gateway = $payMgr->gateway();
                $gwName = $payMgr->getGatewayName();
                if (!method_exists($gateway, 'chargeCustomer')) {
                    echo json_encode(['success' => false, 'message' => '저장 카드 결제 미지원']);
                    exit;
                }
                $newCustomer = false;
                if ($cardToken) {
                    if (!method_exists($gateway, 'createCustomer')) {
                        echo json_encode(['success' => false, 'message' => '카드 등록 미지원']);
                        exit;
                    }
                    $emSt = $pdo->prepare("SELECT email FROM {$prefix}users WHERE id = ?");
                    $emSt->execute([$userId]);
                    $userEmail = $emSt->fetchColumn() ?: '';
                    $cardHolder = trim($input['card_holder'] ?? '');
                    $custMeta = ['add_domain' => $domain];
                    if ($cardHolder !== '') $custMeta['card_holder'] = $cardHolder;
                    $cr = $gateway->createCustomer($cardToken, $userEmail, $custMeta);
                    if (!($cr['success'] ?? false)) {
                        echo json_encode(['success' => false, 'message' => '카드 등록 실패: ' . ($cr['message'] ?? ''), 'card_error' => true]);
                        exit;
                    }
                    $customerId = $cr['customer_id'];
                    $newCustomer = true;
                }
                $desc = "VosCMS Domain {$domain} (1y, incl. VAT)";
                $payResult = $gateway->chargeCustomer($customerId, $grandTotal, strtolower($currency), $desc);
            } catch (\Throwable $e) {
                error_log("[pay_domain] gateway error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => '결제 처리 중 오류가 발생했습니다.']);
                exit;
            }
            if (!$payResult->isSuccessful()) {
                $code = (string)($payResult->failureCode ?? '');
                $cardErrCodes = ['card_declined','expired_card','incorrect_card_data','invalid_card_data','invalid_expiry_month','invalid_expiry_year','invalid_cvc','invalid_number','processing_error','unacceptable_brand','invalid_card','card_flagged'];
                $isCardError = in_array($code, $cardErrCodes, true);
                if ($newCustomer && $customerId) { try { $gateway->deleteCustomer($customerId); } catch (\Throwable $de) {} }
                echo json_encode(['success' => false, 'message' => $payResult->failureMessage ?? '결제 실패', 'card_error' => $isCardError]);
                exit;
            }
            $paymentId = $payResult->transactionId;
        }

        // host order_number 조회 (payments.order_id 컬럼에 사용)
        $hostOrderNumberSt = $pdo->prepare("SELECT order_number FROM {$prefix}orders WHERE id = ?");
        $hostOrderNumberSt->execute([$hostSub['order_id']]);
        $hostOrderNumber = (string)($hostOrderNumberSt->fetchColumn() ?: '');

        // 호스팅 sub metadata.added_domains 에 append + payments 테이블에 회계 전표 INSERT
        try {
            $pdo->beginTransaction();
            $now = date('Y-m-d H:i:s');
            $expires = date('Y-m-d H:i:s', strtotime('+1 year'));

            // 회계 전표 — rzx_payments INSERT (payment_key UNIQUE — 중복 시 기존 row 재사용)
            $paymentRowId = null;
            if ($grandTotal > 0 && $paymentId) {
                $payUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                try {
                    $pIns = $pdo->prepare("INSERT INTO {$prefix}payments
                        (uuid, user_id, order_id, payment_key, gateway, method, amount, status, paid_at, metadata, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, 'card', ?, 'paid', NOW(), ?, NOW(), NOW())");
                    $pIns->execute([
                        $payUuid, $userId, $hostOrderNumber, $paymentId, $gwName, $grandTotal,
                        json_encode([
                            'source' => 'mypage_add_domain',
                            'domain' => $domain,
                            'option' => $opt,
                            'subtotal' => $serverPrice,
                            'tax' => $taxAmount,
                            'currency' => $currency,
                            'host_sub_id' => (int)$hostSub['id'],
                        ], JSON_UNESCAPED_UNICODE),
                    ]);
                    $paymentRowId = (int)$pdo->lastInsertId();
                } catch (\PDOException $pe) {
                    // 중복 charge_id (idempotent 재시도 또는 같은 PAY.JP charge 두 번 처리)
                    if ($pe->errorInfo[1] ?? 0 === 1062) {
                        $existSt = $pdo->prepare("SELECT id FROM {$prefix}payments WHERE payment_key = ? LIMIT 1");
                        $existSt->execute([$paymentId]);
                        $paymentRowId = (int)($existSt->fetchColumn() ?: 0);
                        error_log("[pay_domain] duplicate payment_key {$paymentId} — reusing row#{$paymentRowId}");
                    } else {
                        throw $pe;
                    }
                }
            }

            $hostMeta = json_decode($hostSub['metadata'] ?? '{}', true) ?: [];
            $hostMeta['added_domains'] = $hostMeta['added_domains'] ?? [];
            $hostMeta['added_domains'][] = [
                'domain' => $domain,
                'option' => $opt,
                'started_at' => $now,
                'expires_at' => $expires,
                'subtotal' => $serverPrice,
                'tax' => $taxAmount,
                'total' => $grandTotal,
                'currency' => $currency,
                'order_id' => (int)$hostSub['order_id'],
                'paid_at' => $grandTotal > 0 ? $now : null,
                'payment_method' => $grandTotal > 0 ? 'card' : 'free',
                'payment_gateway' => $gwName,
                'payment_transaction_id' => $paymentId,    // PAY.JP charge id (ch_xxx)
                'payment_row_id' => $paymentRowId,          // rzx_payments.id FK
                'auto_renew' => ($opt === 'free' ? false : true),
                'registrar_pending' => $opt === 'new',
                'manual_attach_pending' => $opt === 'existing',
            ];
            $hostMeta['domains'] = array_values(array_unique(array_merge($hostMeta['domains'] ?? [], [$domain])));

            $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                ->execute([json_encode($hostMeta, JSON_UNESCAPED_UNICODE), $hostSub['id']]);

            // host order_logs 에 결제 기록 통합
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'domain_added', ?, 'user', ?)")
                ->execute([
                    (int)$hostSub['order_id'],
                    json_encode([
                        'domain' => $domain,
                        'option' => $opt,
                        'subtotal' => $serverPrice,
                        'tax' => $taxAmount,
                        'total' => $grandTotal,
                        'currency' => $currency,
                        'host_sub_id' => (int)$hostSub['id'],
                        'payment_transaction_id' => $paymentId,
                        'payment_gateway' => $gwName,
                        'payment_row_id' => $paymentRowId,
                    ], JSON_UNESCAPED_UNICODE),
                    $userId,
                ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("[pay_domain] db error: " . $e->getMessage());

            // 안전망 — 이미 발생한 PAY.JP charge 자동 환불 (사용자 손해 방지)
            $autoRefunded = false;
            if ($paymentId && $grandTotal > 0 && isset($gateway) && method_exists($gateway, 'refund')) {
                try {
                    $gateway->refund($paymentId, $grandTotal, 'auto-rollback: db error');
                    $autoRefunded = true;
                } catch (\Throwable $re) {
                    error_log('[pay_domain] auto-refund failed: ' . $re->getMessage());
                }
            }
            echo json_encode([
                'success' => false,
                'message' => 'DB 처리 오류: ' . $e->getMessage() . ($autoRefunded ? ' (결제는 자동 환불되었습니다)' : ''),
                'auto_refunded' => $autoRefunded,
            ]);
            exit;
        }

        // 관리자 알림 — 신규 등록 / 보유 도메인 연결 시 (free 는 자동이라 알림 불필요)
        if ($opt !== 'free') {
            try {
                $autoload = BASE_PATH . '/vendor/autoload.php';
                if (file_exists($autoload)) require_once $autoload;
                $notifier = new \RzxLib\Core\Notification\WebPushNotifier($pdo, $prefix);
                $adminPath = 'admin';
                try {
                    $cfgSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'admin_path' LIMIT 1");
                    $cfgSt->execute();
                    $adminPath = trim((string)($cfgSt->fetchColumn() ?: 'admin'));
                } catch (\Throwable $e) {}
                $notifier->notifyAdminDomainAction((int)$hostSub['order_id'], 'pay_domain', [
                    'domain' => $domain,
                    'option' => $opt,
                    'host_sub_id' => (int)$hostSub['id'],
                ], $adminPath);
            } catch (\Throwable $ne) {
                error_log('[pay_domain] notify: ' . $ne->getMessage());
            }
        }

        echo json_encode([
            'success' => true,
            'host_sub_id' => (int)$hostSub['id'],
            'host_order_id' => (int)$hostSub['order_id'],
            'domain' => $domain,
            'option' => $opt,
            'subtotal' => $serverPrice,
            'tax' => $taxAmount,
            'total' => $grandTotal,
            'pending_admin' => ($opt !== 'free'),
        ]);
        exit;

    // ===== 도메인 DNS 검사 — 우리 호스팅으로 향하는지 확인 =====
    case 'check_domain_dns':
        $domain = strtolower(trim((string)($input['domain'] ?? '')));
        if (!preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?\.[a-z]{2,}$/i', $domain)) {
            echo json_encode(['success' => false, 'message' => 'invalid domain']);
            exit;
        }
        $ourIp = '27.81.39.11';
        $ourZones = ['21ces.com', '21ces.net', '21ces.kr', 'voscms.com'];
        // Cloudflare IPv4 대역 (https://www.cloudflare.com/ips-v4)
        $cfRanges = [
            '173.245.48.0/20','103.21.244.0/22','103.22.200.0/22','103.31.4.0/22',
            '141.101.64.0/18','108.162.192.0/18','190.93.240.0/20','188.114.96.0/20',
            '197.234.240.0/22','198.41.128.0/17','162.158.0.0/15','104.16.0.0/13',
            '104.24.0.0/14','172.64.0.0/13','131.0.72.0/22',
        ];
        $ipInCf = function($ip) use ($cfRanges) {
            $ipL = ip2long($ip);
            if ($ipL === false) return false;
            foreach ($cfRanges as $range) {
                [$net, $bits] = explode('/', $range);
                $netL = ip2long($net);
                $mask = -1 << (32 - (int)$bits);
                if (($ipL & $mask) === ($netL & $mask)) return true;
            }
            return false;
        };

        $records = [];
        $matched = '';
        $pointsToUs = false;

        // 1. 우리 zone 의 서브도메인이면 자동 매칭 (wildcard CNAME → Cloudflare 터널)
        foreach ($ourZones as $z) {
            if ($domain === $z || str_ends_with($domain, '.' . $z)) {
                $pointsToUs = true;
                $matched = $z;
                $records[] = 'ZONE ' . $z;
                break;
            }
        }

        $aRecs = @dns_get_record($domain, DNS_A) ?: [];
        $cnameRecs = @dns_get_record($domain, DNS_CNAME) ?: [];

        foreach ($aRecs as $r) {
            $ip = $r['ip'] ?? '';
            if (!$ip) continue;
            $records[] = 'A ' . $ip;
            if ($pointsToUs) continue;
            if ($ip === $ourIp) { $pointsToUs = true; $matched = $ip; continue; }
            if ($ipInCf($ip)) { $pointsToUs = true; $matched = $ip . ' (Cloudflare)'; }
        }
        foreach ($cnameRecs as $r) {
            $target = rtrim((string)($r['target'] ?? ''), '.');
            if ($target === '') continue;
            $records[] = 'CNAME ' . $target;
            if ($pointsToUs) continue;
            foreach ($ourZones as $z) {
                if ($target === $z || str_ends_with($target, '.' . $z)) {
                    $pointsToUs = true;
                    $matched = $target;
                    break;
                }
            }
        }
        echo json_encode([
            'success' => true,
            'domain' => $domain,
            'points_to_us' => $pointsToUs,
            'matched' => $matched,
            'records' => $records,
        ]);
        exit;

    // ===== 디스크 사용량 조회 (5분 캐시 + quota 명령) =====
    case 'get_disk_usage':
        $sub = getOwnedSubscription($pdo, $prefix, $subId, $userId);
        if (!$sub || $sub['type'] !== 'hosting') {
            echo json_encode(['success' => false, 'message' => 'subscription not found']);
            exit;
        }
        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $cache = $meta['server']['usage'] ?? [];
        $cachedAt = isset($cache['measured_at']) ? strtotime($cache['measured_at']) : 0;
        if ($cachedAt && (time() - $cachedAt) < 300 && isset($cache['hdd_used_kb'])) {
            echo json_encode([
                'success' => true,
                'used_kb' => (int)$cache['hdd_used_kb'],
                'used_label' => $cache['hdd_used'] ?? '',
                'soft_kb' => (int)($cache['hdd_soft_kb'] ?? 0),
                'hard_kb' => (int)($cache['hdd_hard_kb'] ?? 0),
                'measured_at' => $cache['measured_at'],
                'cached' => true,
            ]);
            exit;
        }
        $oSt = $pdo->prepare("SELECT order_number FROM {$prefix}orders WHERE id = ?");
        $oSt->execute([$sub['order_id']]);
        $orderNumber = $oSt->fetchColumn();
        if (!$orderNumber) {
            echo json_encode(['success' => false, 'message' => 'order not found']);
            exit;
        }
        $username = 'vos_' . preg_replace('/[^A-Za-z0-9_-]/', '', $orderNumber);
        $cmd = '/usr/bin/sudo /usr/bin/quota -u ' . escapeshellarg($username) . ' 2>/dev/null';
        $output = @shell_exec($cmd);
        $usedKb = 0; $softKb = 0; $hardKb = 0;
        if ($output && preg_match('/\/dev\/\S+\s+(\d+)\*?\s+(\d+)\s+(\d+)/', $output, $m)) {
            $usedKb = (int)$m[1];
            $softKb = (int)$m[2];
            $hardKb = (int)$m[3];
        }
        $formatBytes = function($kb) {
            if ($kb >= 1024 * 1024) return number_format($kb / 1024 / 1024, 2) . 'GB';
            if ($kb >= 1024) return number_format($kb / 1024, 2) . 'MB';
            return $kb . 'KB';
        };
        $usedLabel = $formatBytes($usedKb);
        $meta['server'] = $meta['server'] ?? [];
        $meta['server']['usage'] = [
            'hdd_used_kb' => $usedKb,
            'hdd_used' => $usedLabel,
            'hdd_soft_kb' => $softKb,
            'hdd_hard_kb' => $hardKb,
            'measured_at' => date('c'),
        ];
        $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
            ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $sub['id']]);
        echo json_encode([
            'success' => true,
            'used_kb' => $usedKb,
            'used_label' => $usedLabel,
            'soft_kb' => $softKb,
            'hard_kb' => $hardKb,
            'measured_at' => date('c'),
            'cached' => false,
        ]);
        exit;

    // ===== 사이트 백업 (web 파일 + .env + DB 덤프 zip) =====
    case 'request_backup':
        $sub = getOwnedSubscription($pdo, $prefix, $subId, $userId);
        if (!$sub || $sub['type'] !== 'hosting') {
            echo json_encode(['success' => false, 'message' => '호스팅 구독을 찾을 수 없습니다.']);
            exit;
        }
        $oSt = $pdo->prepare("SELECT order_number FROM {$prefix}orders WHERE id = ?");
        $oSt->execute([$sub['order_id']]);
        $orderNumber = $oSt->fetchColumn();
        if (!$orderNumber) {
            echo json_encode(['success' => false, 'message' => '주문을 찾을 수 없습니다.']);
            exit;
        }
        $docroot = '/var/www/customers/' . $orderNumber . '/public_html';
        if (!is_dir($docroot)) {
            echo json_encode(['success' => false, 'message' => '호스팅 디렉토리가 없습니다.']);
            exit;
        }

        // metadata.server.db 에서 DB 정보 (또는 docroot 의 .env)
        $hMeta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $dbInfo = $hMeta['server']['db'] ?? [];
        if (empty($dbInfo['db_pass']) && file_exists($docroot . '/.env')) {
            $envVars = [];
            foreach (file($docroot . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $envVars[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
            }
            $dbInfo = [
                'db_host' => $envVars['DB_HOST'] ?? '127.0.0.1',
                'db_name' => $envVars['DB_DATABASE'] ?? '',
                'db_user' => $envVars['DB_USERNAME'] ?? '',
                'db_pass' => $envVars['DB_PASSWORD'] ?? '',
            ];
        }

        // 백업 디렉토리 (호스팅 디렉토리 외부 — root:www-data 만 접근 가능)
        $backupDir = '/var/www/customers/' . $orderNumber . '/backups';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0750, true);
        }
        // 7일 넘은 기존 백업 정리
        foreach (glob($backupDir . '/backup-*.zip') ?: [] as $oldFile) {
            if (filemtime($oldFile) < time() - 7 * 86400) {
                @unlink($oldFile);
            }
        }

        // DB 덤프
        $ts = date('Ymd-His');
        $sqlFile = $backupDir . "/db-{$ts}.sql";
        if (!empty($dbInfo['db_pass'])) {
            $cmd = sprintf(
                '/usr/bin/mysqldump --single-transaction --quick --no-tablespaces --routines --triggers -h%s -u%s -p%s %s > %s 2>&1',
                escapeshellarg($dbInfo['db_host'] ?? 'localhost'),
                escapeshellarg($dbInfo['db_user']),
                escapeshellarg($dbInfo['db_pass']),
                escapeshellarg($dbInfo['db_name']),
                escapeshellarg($sqlFile)
            );
            exec($cmd, $output, $exit);
            if ($exit !== 0) {
                echo json_encode(['success' => false, 'message' => 'DB 덤프 실패: ' . implode("\n", $output)]);
                exit;
            }
        }

        // zip 패키징 (public_html 전체 + sql)
        $zipFile = $backupDir . "/backup-{$orderNumber}-{$ts}.zip";
        $cmd = sprintf(
            'cd %s && /usr/bin/zip -rq %s public_html backups/db-%s.sql 2>&1',
            escapeshellarg('/var/www/customers/' . $orderNumber),
            escapeshellarg($zipFile),
            escapeshellarg($ts)
        );
        exec($cmd, $zout, $zexit);
        @unlink($sqlFile);

        if (!file_exists($zipFile) || filesize($zipFile) === 0) {
            echo json_encode(['success' => false, 'message' => '백업 파일 생성 실패: ' . implode("\n", $zout)]);
            exit;
        }

        // 서명된 다운로드 URL (HMAC, 10분 만료)
        $secret = $_ENV['APP_KEY'] ?? 'voscms-default-secret';
        $expires = time() + 600;
        $filename = basename($zipFile);
        $sig = hash_hmac('sha256', "{$orderNumber}|{$filename}|{$expires}|{$userId}", $secret);
        $downloadUrl = sprintf(
            '/plugins/vos-hosting/api/backup-download.php?o=%s&f=%s&e=%d&u=%d&s=%s',
            urlencode($orderNumber), urlencode($filename), $expires, (int)$userId, $sig
        );

        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'site_backup_created', ?, 'customer', ?)")
            ->execute([$sub['order_id'], json_encode(['filename' => $filename, 'size_bytes' => filesize($zipFile)], JSON_UNESCAPED_UNICODE), (string)$userId]);

        echo json_encode([
            'success' => true,
            'download_url' => $downloadUrl,
            'filename' => $filename,
            'size_bytes' => filesize($zipFile),
        ]);
        exit;

    // ===== 자동연장 토글 (recurring만) =====
    case 'toggle_auto_renew':
        $sub = getOwnedSubscription($pdo, $prefix, $subId, $userId);
        if (!$sub) { echo json_encode(['success' => false, 'message' => '구독을 찾을 수 없습니다.']); exit; }

        $sc = $sub['service_class'] ?? 'recurring';
        if ($sc !== 'recurring') {
            echo json_encode(['success' => false, 'message' => '유료 정기 서비스만 자동연장 설정이 가능합니다.']);
            exit;
        }

        $autoRenew = !empty($input['auto_renew']) ? 1 : 0;
        $pdo->prepare("UPDATE {$prefix}subscriptions SET auto_renew = ? WHERE id = ? AND user_id = ?")
            ->execute([$autoRenew, $subId, $userId]);

        echo json_encode(['success' => true, 'auto_renew' => $autoRenew]);
        break;

    // ===== 무료 서비스 연장 신청 =====
    case 'request_renewal':
        $sub = getOwnedSubscription($pdo, $prefix, $subId, $userId);
        if (!$sub) { echo json_encode(['success' => false, 'message' => '구독을 찾을 수 없습니다.']); exit; }

        $sc = $sub['service_class'] ?? 'recurring';
        if ($sc !== 'free') {
            echo json_encode(['success' => false, 'message' => '무료 서비스만 연장 신청이 가능합니다.']);
            exit;
        }

        // order_logs에 연장 신청 기록
        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'renewal_request', ?, 'user', ?)")
            ->execute([
                $sub['order_id'],
                json_encode(['subscription_id' => $subId, 'label' => $sub['label'], 'type' => $sub['type']]),
                $userId
            ]);

        echo json_encode(['success' => true, 'message' => '연장 신청이 접수되었습니다. 관리자 확인 후 처리됩니다.']);
        break;

    // ===== 메인 도메인 설정 =====
    case 'set_primary_domain':
        $domain = $input['domain'] ?? '';
        if (!$domain) { echo json_encode(['success' => false, 'message' => '도메인을 지정해주세요.']); exit; }

        $sub = getOwnedSubscription($pdo, $prefix, $subId, $userId);
        if (!$sub || $sub['type'] !== 'domain') {
            echo json_encode(['success' => false, 'message' => '도메인 구독을 찾을 수 없습니다.']);
            exit;
        }

        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        if (!in_array($domain, $meta['domains'] ?? [])) {
            echo json_encode(['success' => false, 'message' => '해당 도메인이 구독에 포함되어 있지 않습니다.']);
            exit;
        }

        // 같은 주문의 모든 도메인 구독에서 primary_domain 초기화
        $resetStmt = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'domain'");
        $resetStmt->execute([$sub['order_id']]);
        while ($row = $resetStmt->fetch(PDO::FETCH_ASSOC)) {
            $rm = json_decode($row['metadata'] ?? '{}', true) ?: [];
            unset($rm['primary_domain']);
            $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                ->execute([json_encode($rm, JSON_UNESCAPED_UNICODE), $row['id']]);
        }

        // 해당 구독에 primary_domain 설정
        $meta['primary_domain'] = $domain;
        $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
            ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $subId]);

        // 주문의 domain 필드도 업데이트
        $pdo->prepare("UPDATE {$prefix}orders SET domain = ? WHERE id = ?")
            ->execute([$domain, $sub['order_id']]);

        echo json_encode(['success' => true, 'message' => '메인 도메인이 설정되었습니다.']);
        break;

    // ===== 1회성 서비스 완료 처리 (관리자용) =====
    case 'mark_complete':
        // 관리자 권한 체크
        $userStmt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $userStmt->execute([$userId]);
        $userRole = $userStmt->fetchColumn();
        if ($userRole !== 'admin') {
            echo json_encode(['success' => false, 'message' => '관리자만 완료 처리할 수 있습니다.']);
            exit;
        }

        $targetSubId = (int)($input['subscription_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ?");
        $stmt->execute([$targetSubId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sub || ($sub['service_class'] ?? '') !== 'one_time') {
            echo json_encode(['success' => false, 'message' => '1회성 서비스를 찾을 수 없습니다.']);
            exit;
        }

        $pdo->prepare("UPDATE {$prefix}subscriptions SET completed_at = NOW(), status = 'active' WHERE id = ?")
            ->execute([$targetSubId]);

        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'service_completed', ?, 'admin', ?)")
            ->execute([$sub['order_id'], json_encode(['subscription_id' => $targetSubId, 'label' => $sub['label']]), $userId]);

        echo json_encode(['success' => true, 'message' => '서비스가 완료 처리되었습니다.']);
        break;

    // ===== 메일 비밀번호 변경 =====
    case 'change_mail_password':
        $address = $input['address'] ?? '';
        $newPassword = $input['password'] ?? '';
        if (!$address || strlen($newPassword) < 8) {
            echo json_encode(['success' => false, 'message' => '메일 주소와 8자 이상 비밀번호를 입력해주세요.']);
            exit;
        }

        $sub = getOwnedSubscription($pdo, $prefix, $subId, $userId);
        if (!$sub) { echo json_encode(['success' => false, 'message' => '구독을 찾을 수 없습니다.']); exit; }

        require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
        require_once BASE_PATH . '/rzxlib/Core/Helpers/functions.php';

        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $found = false;
        // ??  연산자 + foreach reference 는 원본 array 에 안 먹음 — 직접 인덱스 갱신
        if (!empty($meta['mail_accounts']) && is_array($meta['mail_accounts'])) {
            foreach ($meta['mail_accounts'] as $_i => $ma) {
                if (($ma['address'] ?? '') === $address) {
                    $meta['mail_accounts'][$_i]['password'] = mail_password_hash($newPassword);
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            echo json_encode(['success' => false, 'message' => '해당 메일 계정을 찾을 수 없습니다.']);
            exit;
        }

        $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
            ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $subId]);

        // 같은 주문의 mail + hosting 모든 metadata.mail_accounts 동기화
        $allSubs = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND id != ? AND (type = 'mail' OR type = 'hosting')");
        $allSubs->execute([$sub['order_id'], $subId]);
        while ($hs = $allSubs->fetch(PDO::FETCH_ASSOC)) {
            $hm = json_decode($hs['metadata'] ?? '{}', true) ?: [];
            $updated = false;
            if (!empty($hm['mail_accounts']) && is_array($hm['mail_accounts'])) {
                foreach ($hm['mail_accounts'] as $_i => $hma) {
                    if (($hma['address'] ?? '') === $address) {
                        $hm['mail_accounts'][$_i]['password'] = mail_password_hash($newPassword);
                        $updated = true;
                        break;
                    }
                }
            }
            if ($updated) {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                    ->execute([json_encode($hm, JSON_UNESCAPED_UNICODE), $hs['id']]);
            }
        }

        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_password_changed', ?, 'user', ?)")
            ->execute([$sub['order_id'], json_encode(['address' => $address]), $userId]);

        triggerMailSyncToMx1();
        echo json_encode(['success' => true, 'applied' => true, 'message' => '비밀번호가 변경되었습니다.']);
        break;

    // ===== 메일 계정 추가 (기본 메일 — 호스팅 무료 한도 내) =====
    case 'add_mail_account':
        $orderId = (int)($input['order_id'] ?? 0);
        $address = strtolower(trim($input['address'] ?? ''));
        $newPassword = $input['password'] ?? '';

        if (!$orderId || !$address || strlen($newPassword) < 8) {
            echo json_encode(['success' => false, 'message' => '주문, 메일주소, 8자 이상 비밀번호가 필요합니다.']);
            exit;
        }
        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => '메일 주소 형식이 올바르지 않습니다.']);
            exit;
        }

        // 주문 소유자 확인
        $orderStmt = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE id = ? AND user_id = ?");
        $orderStmt->execute([$orderId, $userId]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) { echo json_encode(['success' => false, 'message' => '주문을 찾을 수 없습니다.']); exit; }

        // 호스팅 구독 확인 (활성 상태여야 무료 메일 사용 가능)
        $hostStmt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'hosting' AND status = 'active' LIMIT 1");
        $hostStmt->execute([$orderId]);
        $hostSub = $hostStmt->fetch(PDO::FETCH_ASSOC);
        if (!$hostSub) { echo json_encode(['success' => false, 'message' => '활성 호스팅 구독이 필요합니다.']); exit; }

        // 도메인 일치 확인
        $orderDomain = strtolower($order['domain'] ?? '');
        $addrDomain = substr(strrchr($address, '@'), 1);
        if (!$orderDomain || $addrDomain !== $orderDomain) {
            echo json_encode(['success' => false, 'message' => '주문 도메인과 메일 도메인이 일치하지 않습니다.']);
            exit;
        }

        // 무료 메일 한도 (호스팅 플랜 free_mail_count, 기본 5)
        $freeLimit = 5;
        $planStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'service_hosting_plans' LIMIT 1");
        $planStmt->execute();
        $plansJson = $planStmt->fetchColumn();
        $plans = json_decode($plansJson ?: '[]', true) ?: [];
        $hostCapacity = $order['hosting_capacity'] ?? '';
        foreach ($plans as $p) {
            if (($p['capacity'] ?? '') === $hostCapacity) {
                if (isset($p['free_mail_count']) && is_numeric($p['free_mail_count'])) {
                    $freeLimit = (int)$p['free_mail_count'];
                }
                break;
            }
        }

        // 기존 mail 구독 + 호스팅 구독의 mail_accounts 모두 집계 (중복 / 한도 체크)
        $allAddrs = [];
        $hostMeta = json_decode($hostSub['metadata'] ?? '{}', true) ?: [];
        foreach ($hostMeta['mail_accounts'] ?? [] as $ma) {
            if (!empty($ma['address'])) $allAddrs[] = strtolower($ma['address']);
        }
        $mailSubStmt = $pdo->prepare("SELECT id, label, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'mail' ORDER BY id");
        $mailSubStmt->execute([$orderId]);
        $mailSubs = $mailSubStmt->fetchAll(PDO::FETCH_ASSOC);

        $basicMailSub = null;
        foreach ($mailSubs as $ms) {
            $isBiz = stripos($ms['label'], '비즈니스') !== false || stripos($ms['label'], 'ビジネス') !== false || stripos($ms['label'], 'Business') !== false;
            if (!$isBiz && $basicMailSub === null) $basicMailSub = $ms;
            $msMeta = json_decode($ms['metadata'] ?? '{}', true) ?: [];
            foreach ($msMeta['mail_accounts'] ?? [] as $ma) {
                if (!empty($ma['address'])) $allAddrs[] = strtolower($ma['address']);
            }
        }

        if (in_array($address, $allAddrs, true)) {
            echo json_encode(['success' => false, 'message' => '이미 사용 중인 메일 주소입니다.']);
            exit;
        }
        if (count($allAddrs) >= $freeLimit) {
            echo json_encode(['success' => false, 'message' => "무료 메일 한도({$freeLimit}개)에 도달했습니다."]);
            exit;
        }

        require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
        require_once BASE_PATH . '/rzxlib/Core/Helpers/functions.php';

        $newAccount = ['address' => $address, 'password' => mail_password_hash($newPassword)];

        if ($basicMailSub) {
            // 기존 기본 메일 구독에 추가
            $msMeta = json_decode($basicMailSub['metadata'] ?? '{}', true) ?: [];
            $msMeta['mail_accounts'] = $msMeta['mail_accounts'] ?? [];
            $msMeta['mail_accounts'][] = $newAccount;
            $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                ->execute([json_encode($msMeta, JSON_UNESCAPED_UNICODE), $basicMailSub['id']]);
            $targetSubId = $basicMailSub['id'];
        } else {
            // 기본 메일 구독 자동 생성 (호스팅과 동일 만료/자동연장)
            $newMeta = ['mail_accounts' => [$newAccount]];
            $insertStmt = $pdo->prepare("INSERT INTO {$prefix}subscriptions
                (order_id, user_id, type, service_class, label, started_at, expires_at, auto_renew, status, metadata, currency, billing_amount, unit_price)
                VALUES (?, ?, 'mail', 'free', '기본 메일', ?, ?, ?, 'active', ?, ?, 0, 0)");
            $insertStmt->execute([
                $orderId, $userId,
                $hostSub['started_at'], $hostSub['expires_at'],
                (int)($hostSub['auto_renew'] ?? 1),
                json_encode($newMeta, JSON_UNESCAPED_UNICODE),
                $hostSub['currency'] ?? 'JPY'
            ]);
            $targetSubId = (int)$pdo->lastInsertId();
        }

        // 로그
        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_account_added', ?, 'user', ?)")
            ->execute([$orderId, json_encode(['address' => $address, 'subscription_id' => $targetSubId]), $userId]);

        triggerMailSyncToMx1();
        echo json_encode(['success' => true, 'message' => '메일 계정이 추가되었습니다.', 'subscription_id' => $targetSubId]);
        break;

    // ===== 메일 주소 변경 (오타 수정용) =====
    case 'change_mail_address':
        $oldAddress = strtolower(trim($input['old_address'] ?? ''));
        $newAddress = strtolower(trim($input['new_address'] ?? ''));
        if (!$subId || !$oldAddress || !$newAddress) {
            echo json_encode(['success' => false, 'message' => '구독 + 기존 주소 + 새 주소 필요']); exit;
        }
        if (!filter_var($newAddress, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => '올바른 메일 주소 형식이 아닙니다.']); exit;
        }
        if ($oldAddress === $newAddress) {
            echo json_encode(['success' => false, 'message' => '동일한 주소입니다.']); exit;
        }
        // 도메인 일치 확인
        $oldDomain = substr(strrchr($oldAddress, '@'), 1);
        $newDomain = substr(strrchr($newAddress, '@'), 1);
        if ($oldDomain !== $newDomain) {
            echo json_encode(['success' => false, 'message' => '도메인은 변경할 수 없습니다.']); exit;
        }

        $sub = getOwnedSubscription($pdo, $prefix, $subId, $userId);
        if (!$sub) { echo json_encode(['success' => false, 'message' => '구독을 찾을 수 없습니다.']); exit; }

        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $found = false;
        if (!empty($meta['mail_accounts']) && is_array($meta['mail_accounts'])) {
            foreach ($meta['mail_accounts'] as $_i => $ma) {
                if (strtolower($ma['address'] ?? '') === $oldAddress) {
                    $meta['mail_accounts'][$_i]['address'] = $newAddress;
                    $found = true;
                    break;
                }
            }
        }
        if (!$found) {
            echo json_encode(['success' => false, 'message' => '해당 메일 계정을 찾을 수 없습니다.']); exit;
        }

        // 같은 주문의 다른 메일/호스팅 구독 metadata 에 동일 주소 있는지 — 중복 검사
        $allCheck = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND (type = 'mail' OR type = 'hosting')");
        $allCheck->execute([$sub['order_id']]);
        while ($row = $allCheck->fetch(PDO::FETCH_ASSOC)) {
            if ((int)$row['id'] === $subId) continue;
            $rm = json_decode($row['metadata'] ?? '{}', true) ?: [];
            foreach ($rm['mail_accounts'] ?? [] as $rma) {
                if (strtolower($rma['address'] ?? '') === $newAddress) {
                    echo json_encode(['success' => false, 'message' => '이미 사용 중인 메일 주소입니다.']); exit;
                }
            }
        }

        $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
            ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $subId]);

        // 같은 주문의 mail + hosting 모든 metadata 동기화
        $hSt = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND id != ? AND (type = 'mail' OR type = 'hosting')");
        $hSt->execute([$sub['order_id'], $subId]);
        while ($hs = $hSt->fetch(PDO::FETCH_ASSOC)) {
            $hm = json_decode($hs['metadata'] ?? '{}', true) ?: [];
            $changed = false;
            if (!empty($hm['mail_accounts']) && is_array($hm['mail_accounts'])) {
                foreach ($hm['mail_accounts'] as $_i => $hma) {
                    if (strtolower($hma['address'] ?? '') === $oldAddress) {
                        $hm['mail_accounts'][$_i]['address'] = $newAddress;
                        $changed = true;
                    }
                }
            }
            if ($changed) {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                    ->execute([json_encode($hm, JSON_UNESCAPED_UNICODE), $hs['id']]);
            }
        }

        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_address_changed', ?, 'user', ?)")
            ->execute([$sub['order_id'], json_encode(['old' => $oldAddress, 'new' => $newAddress], JSON_UNESCAPED_UNICODE), $userId]);

        triggerMailSyncToMx1();
        echo json_encode(['success' => true, 'message' => '메일 주소가 변경되었습니다.']);
        break;

    // ===== 메일 계정 삭제 =====
    case 'delete_mail_account':
        $address = strtolower(trim($input['address'] ?? ''));
        if (!$subId || !$address) {
            echo json_encode(['success' => false, 'message' => '구독과 메일주소가 필요합니다.']);
            exit;
        }

        $sub = getOwnedSubscription($pdo, $prefix, $subId, $userId);
        if (!$sub) { echo json_encode(['success' => false, 'message' => '구독을 찾을 수 없습니다.']); exit; }

        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $accounts = $meta['mail_accounts'] ?? [];
        $found = false;
        $remaining = [];
        foreach ($accounts as $ma) {
            if (strtolower($ma['address'] ?? '') === $address) { $found = true; continue; }
            $remaining[] = $ma;
        }
        if (!$found) { echo json_encode(['success' => false, 'message' => '해당 메일 계정을 찾을 수 없습니다.']); exit; }

        $meta['mail_accounts'] = $remaining;
        $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
            ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $subId]);

        // 호스팅 구독 metadata에도 동일 주소가 있으면 동기화 제거
        $hostingSubs = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'hosting'");
        $hostingSubs->execute([$sub['order_id']]);
        while ($hs = $hostingSubs->fetch(PDO::FETCH_ASSOC)) {
            $hm = json_decode($hs['metadata'] ?? '{}', true) ?: [];
            $hAccounts = $hm['mail_accounts'] ?? [];
            $changed = false;
            $newH = [];
            foreach ($hAccounts as $hma) {
                if (strtolower($hma['address'] ?? '') === $address) { $changed = true; continue; }
                $newH[] = $hma;
            }
            if ($changed) {
                $hm['mail_accounts'] = $newH;
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                    ->execute([json_encode($hm, JSON_UNESCAPED_UNICODE), $hs['id']]);
            }
        }

        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_account_deleted', ?, 'user', ?)")
            ->execute([$sub['order_id'], json_encode(['address' => $address, 'subscription_id' => $subId]), $userId]);

        triggerMailSyncToMx1();
        echo json_encode(['success' => true, 'message' => '메일 계정이 삭제되었습니다.']);
        break;

    // ===== 비즈니스 메일 업그레이드 신청 =====
    case 'request_bizmail_upgrade':
        $orderId = (int)($input['order_id'] ?? 0);
        $reqAddress = trim($input['address'] ?? '');   // 선택: 특정 주소 업그레이드
        if (!$orderId) { echo json_encode(['success' => false, 'message' => '주문이 필요합니다.']); exit; }

        $orderStmt = $pdo->prepare("SELECT id FROM {$prefix}orders WHERE id = ? AND user_id = ?");
        $orderStmt->execute([$orderId, $userId]);
        if (!$orderStmt->fetchColumn()) {
            echo json_encode(['success' => false, 'message' => '주문을 찾을 수 없습니다.']);
            exit;
        }

        $logDetail = ['requested_at' => date('c')];
        if ($reqAddress) $logDetail['address'] = $reqAddress;

        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'bizmail_upgrade_request', ?, 'user', ?)")
            ->execute([$orderId, json_encode($logDetail, JSON_UNESCAPED_UNICODE), $userId]);

        echo json_encode(['success' => true, 'message' => '비즈니스 메일 업그레이드 신청이 접수되었습니다.']);
        break;

    // ===== admin: 신규 도메인 마이그레이션 (임시 → 정식) =====
    case 'admin_migrate_new_domain':
        // 관리자 권한 체크
        $userStmt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $userStmt->execute([$userId]);
        if (!in_array($userStmt->fetchColumn(), ['admin','supervisor'], true)) {
            echo json_encode(['success' => false, 'message' => '관리자 권한 필요']);
            exit;
        }

        $orderId = (int)($input['order_id'] ?? 0);
        if (!$orderId) { echo json_encode(['success' => false, 'message' => 'order_id 필요']); exit; }

        try {
            $provisioner = new \RzxLib\Core\Mail\MailDomainProvisioner($pdo);
            $result = $provisioner->completeNewDomainAcquisition($orderId);
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_domain_migrated', ?, 'admin', ?)")
                ->execute([$orderId, json_encode($result, JSON_UNESCAPED_UNICODE), $userId]);

            // 고객에게 「메일 사용 가능」 알림
            try {
                (new \RzxLib\Core\Mail\MailNotifier($pdo))->notifyCustomerMailReady($orderId);
            } catch (\Throwable $ne) { error_log("[notifier] order $orderId: " . $ne->getMessage()); }

            echo json_encode(['success' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ===== admin: 보유 도메인 활성화 (NS 변경 후 자동 셋업) =====
    case 'admin_activate_existing_domain':
        $userStmt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $userStmt->execute([$userId]);
        if (!in_array($userStmt->fetchColumn(), ['admin','supervisor'], true)) {
            echo json_encode(['success' => false, 'message' => '관리자 권한 필요']);
            exit;
        }

        $orderId = (int)($input['order_id'] ?? 0);
        if (!$orderId) { echo json_encode(['success' => false, 'message' => 'order_id 필요']); exit; }

        try {
            $provisioner = new \RzxLib\Core\Mail\MailDomainProvisioner($pdo);
            $result = $provisioner->activateExistingDomain($orderId);
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'mail_domain_activated', ?, 'admin', ?)")
                ->execute([$orderId, json_encode($result, JSON_UNESCAPED_UNICODE), $userId]);

            // 고객에게 「메일 사용 가능」 알림
            try {
                (new \RzxLib\Core\Mail\MailNotifier($pdo))->notifyCustomerMailReady($orderId);
            } catch (\Throwable $ne) { error_log("[notifier] order $orderId: " . $ne->getMessage()); }

            echo json_encode(['success' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ===== 부가서비스 — 웹 용량 추가 (즉시 결제 + 활성화) =====
    case 'pay_storage_addon':
        $hostSubId = (int)($input['subscription_id'] ?? 0);
        $capacity = trim($input['capacity'] ?? '');
        $unitPrice = (int)($input['unit_price'] ?? 0);
        if (!$hostSubId || $capacity === '' || $unitPrice <= 0) {
            echo json_encode(['success' => false, 'message' => '구독 + 용량 + 단가 필요']);
            exit;
        }

        $sub = getOwnedSubscription($pdo, $prefix, $hostSubId, $userId);
        if (!$sub || $sub['type'] !== 'hosting' || $sub['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => '활성 호스팅 구독을 찾을 수 없습니다.']);
            exit;
        }

        // 단가 검증 (rzx_settings 의 service_hosting_storage)
        $stOptStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'service_hosting_storage' LIMIT 1");
        $stOptStmt->execute();
        $stOpts = json_decode($stOptStmt->fetchColumn() ?: '[]', true) ?: [];
        $validUnit = 0;
        foreach ($stOpts as $opt) {
            if (($opt['capacity'] ?? '') === $capacity) { $validUnit = (int)($opt['price'] ?? 0); break; }
        }
        if ($validUnit <= 0 || $validUnit !== $unitPrice) {
            echo json_encode(['success' => false, 'message' => '잘못된 용량/단가입니다.']);
            exit;
        }

        // Calendar 일할 계산: 첫 달 일할 + 정상 N개월
        $nowTs = time();
        $daysInMonth = (int)date('t', $nowTs);
        $dayOfMonth = (int)date('j', $nowTs);
        $firstMonthDays = max(1, $daysInMonth - $dayOfMonth + 1);
        $firstMonthAmount = (int)round($validUnit * $firstMonthDays / 30);

        // 다음달 1일 ~ 호스팅 만료일까지 calendar 월수
        $billingStartTs = strtotime('first day of next month', $nowTs);
        $expiresTs = strtotime($sub['expires_at']);
        $normalMonths = max(0,
            ((int)date('Y', $expiresTs) - (int)date('Y', $billingStartTs)) * 12
            + ((int)date('n', $expiresTs) - (int)date('n', $billingStartTs))
            + 1
        );
        $normalAmount = $validUnit * $normalMonths;
        $totalAmount = $firstMonthAmount + $normalAmount;
        // total_months 는 표시용 (첫 달 일할 + 정상 N개월)
        $months = $normalMonths + ($firstMonthDays > 0 ? 1 : 0);
        $currency = $sub['currency'] ?? 'JPY';

        $customerId = $sub['payment_customer_id'] ?? '';
        $cardToken = trim($input['card_token'] ?? '');

        // 카드 미등록 + 토큰 미전송 → 결제 불가
        if (!$customerId && !$cardToken) {
            echo json_encode(['success' => false, 'message' => '카드 정보가 필요합니다.']);
            exit;
        }

        require_once BASE_PATH . '/rzxlib/Modules/Payment/PaymentManager.php';
        try {
            $payMgr = new \RzxLib\Modules\Payment\PaymentManager($pdo, $prefix);
            $gateway = $payMgr->gateway();
            $gwName = $payMgr->getGatewayName();
            if (!method_exists($gateway, 'chargeCustomer')) {
                echo json_encode(['success' => false, 'message' => '현재 게이트웨이는 저장 카드 결제를 지원하지 않습니다.']);
                exit;
            }

            // card_token 이 있으면 → 신규 Customer 생성 (기존 카드 만료/거절 시 교체 포함)
            $newCustomerCreated = false;
            $oldCustomerId = $customerId;
            if ($cardToken) {
                if (!method_exists($gateway, 'createCustomer')) {
                    echo json_encode(['success' => false, 'message' => '현재 게이트웨이는 카드 등록을 지원하지 않습니다.']);
                    exit;
                }
                $emailStmt = $pdo->prepare("SELECT email FROM {$prefix}users WHERE id = ?");
                $emailStmt->execute([$userId]);
                $userEmail = $emailStmt->fetchColumn() ?: '';
                $cardHolder = trim($input['card_holder'] ?? '');
                $custMeta = [
                    'order_id' => $sub['order_id'],
                    'addon' => 'storage',
                    'replaced_from' => $oldCustomerId ?: 'none',
                ];
                if ($cardHolder !== '') $custMeta['card_holder'] = $cardHolder;
                $custResult = $gateway->createCustomer($cardToken, $userEmail, $custMeta);
                if (!($custResult['success'] ?? false)) {
                    echo json_encode(['success' => false, 'message' => '카드 등록 실패: ' . ($custResult['message'] ?? ''), 'card_error' => true]);
                    exit;
                }
                $customerId = $custResult['customer_id'];
                $newCustomerCreated = true;
            }

            $description = "VosCMS Storage Addon +{$capacity} ({$months}m) order#{$sub['order_id']}";
            $payResult = $gateway->chargeCustomer($customerId, $totalAmount, strtolower($currency), $description);
        } catch (\Throwable $e) {
            error_log("[pay_storage_addon] gateway error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => '결제 처리 중 오류가 발생했습니다.']);
            exit;
        }

        if (!$payResult->isSuccessful()) {
            $msg = $payResult->failureMessage ?? '결제에 실패했습니다.';
            // 카드 자체 문제 → 클라이언트에서 새 카드 입력 유도
            $cardErrorCodes = [
                'card_declined', 'expired_card', 'incorrect_card_data', 'invalid_card_data',
                'invalid_expiry_month', 'invalid_expiry_year', 'invalid_cvc', 'invalid_number',
                'processing_error', 'unacceptable_brand', 'invalid_card', 'card_flagged',
            ];
            $code = (string)($payResult->failureCode ?? '');
            $isCardError = in_array($code, $cardErrorCodes, true);
            // 신규 customer 생성됐는데 charge 실패 시 — 그 customer 삭제 (오프냔 방지)
            if ($newCustomerCreated && $customerId) {
                try { $gateway->deleteCustomer($customerId); } catch (\Throwable $de) {}
            }
            echo json_encode([
                'success' => false,
                'message' => $msg,
                'card_error' => $isCardError,
                'failure_code' => $code,
            ]);
            exit;
        }

        // 트랜잭션 — payment + addon subscription + hosting metadata
        $pdo->beginTransaction();
        try {
            // 1) 결제 기록
            $payUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            $payStmt = $pdo->prepare("INSERT INTO {$prefix}payments
                (uuid, user_id, order_id, payment_key, gateway, method, method_detail, amount, status, paid_at, raw_response)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'paid', NOW(), ?)");
            $payStmt->execute([
                $payUuid, $userId, $sub['order_id'],
                $payResult->paymentKey, $gwName, $payResult->method,
                json_encode($payResult->methodDetail ?? []),
                $totalAmount, json_encode($payResult->raw ?? []),
            ]);

            // 2) addon subscription INSERT (status='active', expires_at = hosting.expires_at)
            $now = date('Y-m-d H:i:s');
            $label = "추가 용량 +" . $capacity;
            $billingStart = date('Y-m-01 00:00:00', $billingStartTs);
            $addonMeta = [
                'addon_type' => 'storage',
                'capacity' => $capacity,
                'unit_price' => $validUnit,
                'first_month_days' => $firstMonthDays,
                'first_month_amount' => $firstMonthAmount,
                'normal_months' => $normalMonths,
                'normal_amount' => $normalAmount,
                'parent_hosting_sub_id' => $hostSubId,
                'payment_key' => $payResult->paymentKey,
                'paid_at' => $now,
            ];
            $insertStmt = $pdo->prepare("INSERT INTO {$prefix}subscriptions
                (order_id, user_id, type, service_class, label, unit_price, quantity, billing_amount,
                 billing_cycle, billing_months, currency, started_at, billing_start, expires_at,
                 payment_customer_id, payment_gateway, auto_renew, status, metadata)
                VALUES (?, ?, 'addon', 'recurring', ?, ?, 1, ?, 'monthly', 1, ?, ?, ?, ?, ?, ?, 1, 'active', ?)");
            $insertStmt->execute([
                $sub['order_id'], $userId, $label, $validUnit, $validUnit,
                $currency, $now, $billingStart, $sub['expires_at'],
                $customerId, $gwName,
                json_encode($addonMeta, JSON_UNESCAPED_UNICODE),
            ]);
            $newSubId = (int)$pdo->lastInsertId();

            // 3) hosting metadata.extra_storage 누적 + (신규 카드인 경우) customer_id 저장
            $hMeta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
            $hMeta['extra_storage'] = $hMeta['extra_storage'] ?? [];
            $hMeta['extra_storage'][] = [
                'capacity' => $capacity,
                'addon_sub_id' => $newSubId,
                'added_at' => $now,
            ];
            if ($newCustomerCreated) {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ?, payment_customer_id = ?, payment_gateway = ? WHERE id = ?")
                    ->execute([json_encode($hMeta, JSON_UNESCAPED_UNICODE), $customerId, $gwName, $hostSubId]);
            } else {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                    ->execute([json_encode($hMeta, JSON_UNESCAPED_UNICODE), $hostSubId]);
            }

            // 4) 로그
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'storage_addon_paid', ?, 'user', ?)")
                ->execute([$sub['order_id'], json_encode([
                    'capacity' => $capacity, 'unit_price' => $validUnit, 'months' => $months,
                    'total' => $totalAmount, 'addon_sub_id' => $newSubId,
                    'payment_key' => $payResult->paymentKey,
                ], JSON_UNESCAPED_UNICODE), $userId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log("[pay_storage_addon] DB error: " . $e->getMessage());
            // 결제는 이미 성공 — 환불 시도
            try { $gateway->refund($payResult->paymentKey, $totalAmount, 'DB rollback'); } catch (\Throwable $re) {}
            echo json_encode(['success' => false, 'message' => '서비스 등록 중 오류가 발생하여 결제를 환불 처리했습니다.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'subscription_id' => $newSubId,
            'amount_charged' => $totalAmount,
            'months' => $months,
            'message' => "+{$capacity} 용량이 추가되었습니다.",
        ]);
        break;

    // ===== 미구현 스텁 =====
    case 'add_domain':
    case 'upgrade_plan':
    case 'add_service':
        echo json_encode(['success' => false, 'message' => '준비 중인 기능입니다.']);
        break;

    // ============================================================
    // admin_search_user — 관리자: 이메일로 사용자 검색
    // ============================================================
    case 'admin_search_user': {
        $rSt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $rSt->execute([$userId]);
        if (!in_array($rSt->fetchColumn(), ['admin','supervisor'], true)) {
            echo json_encode(['success' => false, 'message' => '관리자 권한 필요']); exit;
        }
        $email = trim((string)($input['email'] ?? ''));
        if ($email === '') { echo json_encode(['success' => false, 'message' => 'email required']); exit; }
        $st = $pdo->prepare("SELECT id, email, name FROM {$prefix}users WHERE email LIKE ? ORDER BY id DESC LIMIT 10");
        $st->execute(['%' . $email . '%']);
        $users = $st->fetchAll(PDO::FETCH_ASSOC);
        // name 복호화
        require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
        foreach ($users as &$u) {
            if ($u['name']) $u['name'] = decrypt($u['name']) ?: $u['name'];
        }
        unset($u);
        echo json_encode(['success' => true, 'users' => $users], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ============================================================
    // admin_create_order — 관리자: 호스팅 신청서 대리 등록
    //   결제 방식: cash / card / free
    // ============================================================
    case 'admin_create_order': {
        $rSt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $rSt->execute([$userId]);
        if (!in_array($rSt->fetchColumn(), ['admin','supervisor'], true)) {
            echo json_encode(['success' => false, 'message' => '관리자 권한 필요']); exit;
        }

        $cust = is_array($input['customer'] ?? null) ? $input['customer'] : [];
        $planId = trim((string)($input['hosting_plan'] ?? ''));
        $months = max(1, min(36, (int)($input['contract_months'] ?? 12)));
        $domainOption = (string)($input['domain_option'] ?? 'free');
        $domainName = trim((string)($input['domain_name'] ?? ''));
        $addonsInput = is_array($input['addons'] ?? null) ? $input['addons'] : [];
        $amountOverride = isset($input['amount_override']) ? (int)$input['amount_override'] : null;
        $pay = is_array($input['payment'] ?? null) ? $input['payment'] : [];

        if ($planId === '') { echo json_encode(['success' => false, 'message' => '호스팅 플랜을 선택해주세요.']); exit; }
        if (!in_array($domainOption, ['free','new','existing'], true)) $domainOption = 'free';
        if (($domainOption === 'new' || $domainOption === 'existing') && $domainName === '') {
            echo json_encode(['success' => false, 'message' => '도메인명을 입력해주세요.']); exit;
        }
        $payMethod = (string)($pay['method'] ?? '');
        if (!in_array($payMethod, ['cash','card','free'], true)) {
            echo json_encode(['success' => false, 'message' => '결제 방식을 선택해주세요.']); exit;
        }

        require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';

        // 1. 사용자 처리 — 기존 또는 신규
        $custUserId = null;
        if (($cust['mode'] ?? '') === 'existing') {
            $custUserId = (string)($cust['user_id'] ?? '');
            if ($custUserId === '') { echo json_encode(['success' => false, 'message' => '고객을 선택해주세요.']); exit; }
            $vSt = $pdo->prepare("SELECT id FROM {$prefix}users WHERE id = ?");
            $vSt->execute([$custUserId]);
            if (!$vSt->fetchColumn()) { echo json_encode(['success' => false, 'message' => '고객을 찾을 수 없습니다.']); exit; }
        } else {
            $newEmail = trim((string)($cust['email'] ?? ''));
            $newName = trim((string)($cust['name'] ?? ''));
            if ($newEmail === '' || $newName === '') {
                echo json_encode(['success' => false, 'message' => '이메일과 이름은 필수입니다.']); exit;
            }
            // 이메일 중복 체크
            $eSt = $pdo->prepare("SELECT id FROM {$prefix}users WHERE email = ?");
            $eSt->execute([$newEmail]);
            $existId = $eSt->fetchColumn();
            if ($existId) {
                $custUserId = (string)$existId;
            } else {
                // 신규 사용자 생성
                $custUserId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $tempPwd = bin2hex(random_bytes(8));
                $pwdHash = password_hash($tempPwd, PASSWORD_BCRYPT);
                $encName = function_exists('encrypt') ? encrypt($newName) : $newName;
                $encPhone = function_exists('encrypt') && !empty($cust['phone']) ? encrypt($cust['phone']) : (string)($cust['phone'] ?? '');
                $pdo->prepare("INSERT INTO {$prefix}users (id, email, name, phone, password, role, created_at) VALUES (?, ?, ?, ?, ?, 'user', NOW())")
                    ->execute([$custUserId, $newEmail, $encName, $encPhone, $pwdHash]);
            }
        }

        // 2. 가격 계산 + 통화
        $sSt = $pdo->prepare("SELECT `key`,`value` FROM {$prefix}settings WHERE `key` IN ('service_hosting_plans','service_currency')");
        $sSt->execute();
        $svcMap = [];
        while ($r = $sSt->fetch(PDO::FETCH_ASSOC)) $svcMap[$r['key']] = $r['value'];
        $plans = json_decode($svcMap['service_hosting_plans'] ?? '[]', true) ?: [];
        $currency = strtoupper(trim((string)($svcMap['service_currency'] ?? 'JPY'))) ?: 'JPY';
        $plan = null;
        foreach ($plans as $p) { if (($p['_id'] ?? '') === $planId) { $plan = $p; break; } }
        if (!$plan) { echo json_encode(['success' => false, 'message' => '플랜을 찾을 수 없습니다.']); exit; }
        $planPrice = (int)($plan['price'] ?? 0);

        $subtotal = $planPrice * $months;
        foreach ($addonsInput as $a) {
            $ap = (int)($a['price'] ?? 0);
            $subtotal += !empty($a['one_time']) ? $ap : ($ap * $months);
        }
        if ($amountOverride !== null && $amountOverride >= 0) $subtotal = $amountOverride;
        $taxAmt = (int)round($subtotal * 0.10);
        $totalAmt = $subtotal + $taxAmt;

        // 무료 결제는 ¥0 강제
        if ($payMethod === 'free') {
            $subtotal = 0; $taxAmt = 0; $totalAmt = 0;
        }

        // 3. 결제 처리 (카드만 외부 호출)
        $chargeId = null; $gwName = null; $payCustomerId = null;
        if ($payMethod === 'card' && $totalAmt > 0) {
            $cardToken = trim((string)($pay['card_token'] ?? ''));
            if ($cardToken === '') { echo json_encode(['success' => false, 'message' => 'card token required']); exit; }
            require_once BASE_PATH . '/rzxlib/Modules/Payment/PaymentManager.php';
            try {
                $payMgr = new \RzxLib\Modules\Payment\PaymentManager($pdo, $prefix);
                $gateway = $payMgr->gateway();
                $gwName = $payMgr->getGatewayName();
                $emSt = $pdo->prepare("SELECT email FROM {$prefix}users WHERE id = ?");
                $emSt->execute([$custUserId]);
                $userEmail = $emSt->fetchColumn() ?: '';
                $cr = $gateway->createCustomer($cardToken, $userEmail, ['admin_create_order' => 1]);
                if (!($cr['success'] ?? false)) {
                    echo json_encode(['success' => false, 'message' => '카드 등록 실패: ' . ($cr['message'] ?? '')]); exit;
                }
                $payCustomerId = $cr['customer_id'];
                $payResult = $gateway->chargeCustomer($payCustomerId, $totalAmt, strtolower($currency), "VosCMS Hosting (admin) — plan {$planId}");
                if (!$payResult->isSuccessful()) {
                    try { $gateway->deleteCustomer($payCustomerId); } catch (\Throwable $e) {}
                    echo json_encode(['success' => false, 'message' => '결제 실패: ' . ($payResult->failureCode ?? '')]); exit;
                }
                $chargeId = $payResult->transactionId;
            } catch (\Throwable $e) {
                error_log('[admin_create_order] gateway: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => '결제 처리 오류: ' . $e->getMessage()]); exit;
            }
        }

        // 4. DB INSERT — orders + subscriptions + payments + order_logs
        try {
            $pdo->beginTransaction();

            // order_number 생성 — 고객 폼(service-order.php)과 동일 형식 (SVC + YYMMDD + 6자 hex)
            $orderNumber = 'SVC' . date('ymd') . strtoupper(substr(md5(uniqid()), 0, 6));

            $startedAt = date('Y-m-d H:i:s');
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$months} months"));

            $methodLabel = $payMethod === 'card' ? 'card' : ($payMethod === 'cash' ? 'cash' : 'free');
            // 관리자 등록은 즉시 paid 처리 — 무료도 status='paid', payment_method='free' 로 구분
            $orderStatus = 'paid';

            $orderUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $orderItems = [
                'hosting' => [
                    'plan_id' => $planId,
                    'label'   => $plan['label'] ?? '',
                    'capacity'=> $plan['capacity'] ?? '',
                    'price'   => $planPrice,
                    'months'  => $months,
                ],
                'addons' => array_map(function($a){ return [
                    'id' => $a['id'] ?? '',
                    'label' => $a['label'] ?? '',
                    'price' => (int)($a['price'] ?? 0),
                    'one_time' => !empty($a['one_time']),
                ]; }, $addonsInput),
                'subtotal' => $subtotal,
                'tax' => $taxAmt,
                'total' => $totalAmt,
            ];
            $pdo->prepare("INSERT INTO {$prefix}orders
                (uuid, order_number, user_id, status, hosting_capacity, domain, domain_option, contract_months, items, total, currency, payment_method, started_at, expires_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())")
                ->execute([
                    $orderUuid, $orderNumber, $custUserId, $orderStatus,
                    $plan['capacity'] ?? '',
                    $domainName ?: ($domainOption === 'free' ? '' : ''),
                    $domainOption, $months,
                    json_encode($orderItems, JSON_UNESCAPED_UNICODE),
                    $totalAmt, $currency,
                    $methodLabel, $startedAt, $expiresAt,
                ]);
            $orderId = (int)$pdo->lastInsertId();

            // 호스팅 subscription
            $subMeta = ['capacity' => $plan['capacity'] ?? '', 'admin_created' => 1];
            $pdo->prepare("INSERT INTO {$prefix}subscriptions
                (order_id, user_id, type, service_class, label, unit_price, quantity, billing_amount, billing_cycle, billing_months,
                 currency, started_at, billing_start, expires_at, status, payment_customer_id, payment_gateway, metadata)
                VALUES (?, ?, 'hosting', 'recurring', ?, ?, 1, ?, 'monthly', ?, ?, ?, ?, ?, 'active', ?, ?, ?)")
                ->execute([
                    $orderId, $custUserId,
                    ($plan['label'] ?? '') . ' ' . ($plan['capacity'] ?? ''),
                    $planPrice, $planPrice * $months, $months,
                    $currency,
                    $startedAt, $startedAt, $expiresAt,
                    $payCustomerId, $gwName,
                    json_encode($subMeta, JSON_UNESCAPED_UNICODE),
                ]);

            // 도메인 subscription — 고객 폼과 동일하게 free/new/existing 모두 생성
            // (도메인 탭 표시 + 갱신 트래킹용)
            if ($domainName !== '') {
                $_dMeta = ['domains' => [$domainName], 'admin_created' => 1];
                if ($domainOption === 'free')      $_dMeta['free_subdomain'] = true;
                if ($domainOption === 'existing')  $_dMeta['existing'] = true;
                if ($domainOption === 'new')       $_dMeta['registrar_pending'] = true;

                $_dLabel       = ($domainOption === 'free') ? '도메인 (무료)'
                              : (($domainOption === 'existing') ? '도메인 (보유)' : '도메인');
                $_dClass       = ($domainOption === 'new') ? 'recurring' : 'free';
                $_dCycle       = ($domainOption === 'new') ? 'yearly' : (($domainOption === 'free') ? 'monthly' : 'custom');
                $_dCycleMonths = ($domainOption === 'new') ? 12 : (($domainOption === 'free') ? 1 : $months);
                $_dExpires     = ($domainOption === 'new') ? date('Y-m-d H:i:s', strtotime('+1 year'))
                              : (($domainOption === 'free') ? date('Y-m-d H:i:s', strtotime('+1 month')) : $expiresAt);

                $pdo->prepare("INSERT INTO {$prefix}subscriptions
                    (order_id, user_id, type, service_class, label, unit_price, quantity, billing_amount, billing_cycle, billing_months,
                     currency, started_at, expires_at, status, metadata)
                    VALUES (?, ?, 'domain', ?, ?, 0, 1, 0, ?, ?, ?, ?, ?, 'active', ?)")
                    ->execute([
                        $orderId, $custUserId,
                        $_dClass, $_dLabel,
                        $_dCycle, $_dCycleMonths,
                        $currency, $startedAt, $_dExpires,
                        json_encode($_dMeta, JSON_UNESCAPED_UNICODE),
                    ]);
            }

            // 부가서비스 subscription
            // install addon 자동 채움용: 고객 이메일 + 임시 비밀번호 생성
            $custEmailForInstall = '';
            $custNameForInstall = '';
            if ($custUserId) {
                $_uSt = $pdo->prepare("SELECT email, name FROM {$prefix}users WHERE id = ?");
                $_uSt->execute([$custUserId]);
                if ($_uRow = $_uSt->fetch(PDO::FETCH_ASSOC)) {
                    $custEmailForInstall = $_uRow['email'] ?? '';
                    $custNameForInstall = function_exists('decrypt') ? @decrypt($_uRow['name']) : ($_uRow['name'] ?? '');
                }
            }

            foreach ($addonsInput as $a) {
                $aPrice = (int)($a['price'] ?? 0);
                $aOneTime = !empty($a['one_time']);
                $billing = $aOneTime ? $aPrice : ($aPrice * $months);
                $aMetaArr = ['admin_created' => 1, 'addon_id' => $a['id'] ?? ''];

                // install addon 의 install_info — 관리자가 폼에서 입력한 값 우선,
                // 누락 필드는 안전한 기본값으로 채움 (고객 이메일 / 임시 비밀번호 / 도메인)
                if (($a['id'] ?? '') === 'install') {
                    $userInstall = is_array($input['install_info'] ?? null) ? $input['install_info'] : [];
                    $aMetaArr['install_info'] = [
                        'admin_id'    => trim((string)($userInstall['admin_id']    ?? '')) ?: ($custEmailForInstall ?: 'admin'),
                        'admin_email' => trim((string)($userInstall['admin_email'] ?? '')) ?: $custEmailForInstall,
                        'admin_pw'    => (string)($userInstall['admin_pw']    ?? '') !== '' ? (string)$userInstall['admin_pw'] : bin2hex(random_bytes(6)),
                        'site_title'  => trim((string)($userInstall['site_title']  ?? '')) ?: ($custNameForInstall ?: $domainName),
                    ];
                }

                $pdo->prepare("INSERT INTO {$prefix}subscriptions
                    (order_id, user_id, type, service_class, label, unit_price, quantity, billing_amount, billing_cycle, billing_months,
                     currency, started_at, expires_at, status, metadata)
                    VALUES (?, ?, 'addon', ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, 'active', ?)")
                    ->execute([
                        $orderId, $custUserId,
                        $aOneTime ? 'one_time' : 'recurring',
                        (string)($a['label'] ?? ''),
                        $aPrice, $billing,
                        $aOneTime ? 'one_time' : 'monthly', $aOneTime ? null : $months,
                        $currency,
                        $startedAt, $expiresAt,
                        json_encode($aMetaArr, JSON_UNESCAPED_UNICODE),
                    ]);
            }

            // payment 전표 (free 가 아닐 때만)
            if ($payMethod !== 'free' && $totalAmt > 0) {
                $payUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $payKey = $chargeId ?: ('manual_' . $payUuid);
                $pdo->prepare("INSERT INTO {$prefix}payments
                    (uuid, user_id, order_id, payment_key, gateway, method, amount, status, paid_at, metadata, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'paid', NOW(), ?, NOW(), NOW())")
                    ->execute([
                        $payUuid, $custUserId, $orderNumber, $payKey,
                        $gwName ?: 'manual', $methodLabel, $totalAmt,
                        json_encode([
                            'source' => 'admin_create_order',
                            'plan' => $planId,
                            'months' => $months,
                            'cash_received' => $payMethod === 'cash' ? ($pay['received'] ?? null) : null,
                            'admin_id' => $userId,
                        ], JSON_UNESCAPED_UNICODE),
                    ]);
            }

            // order_logs
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'admin_created', ?, 'admin', ?)")
                ->execute([
                    $orderId,
                    json_encode([
                        'method' => $payMethod,
                        'amount' => $totalAmt,
                        'reason' => $pay['reason'] ?? null,
                        'cash_received' => $pay['received'] ?? null,
                    ], JSON_UNESCAPED_UNICODE),
                    $userId,
                ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[admin_create_order] DB: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]); exit;
        }

        // 응답 먼저 보내고, 백그라운드에서 호스팅/메일 프로비저닝 트리거
        // (free 도메인은 즉시 셋업, new/existing 은 NS 변경 후 별도)
        echo json_encode(['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber]);
        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
        @ignore_user_abort(true);

        $_runScript = BASE_PATH . '/scripts/run-order-provision.php';
        if (is_file($_runScript)) {
            $_logFile = '/tmp/voscms-provision-' . preg_replace('/[^A-Za-z0-9_-]/', '', $orderNumber) . '.log';
            $_cmd = sprintf(
                '/usr/bin/php8.3 %s --order=%s > %s 2>&1 &',
                escapeshellarg($_runScript),
                escapeshellarg($orderNumber),
                escapeshellarg($_logFile)
            );
            @exec($_cmd);
        }
        exit;
    }

    // ============================================================
    // admin_hosting_status — SSL/disk/db 사용량 + nginx 상태 일괄 조회
    // input: order_number
    // output: { success, ssl: {...}, disk: {...}, db: {...}, nginx: {...} }
    // ============================================================
    case 'admin_hosting_status': {
        $rSt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
        $rSt->execute([$userId]);
        if (!in_array($rSt->fetchColumn(), ['admin','supervisor'], true)) {
            echo json_encode(['success' => false, 'message' => '권한 필요']); exit;
        }
        $orderNumber = strtoupper(trim((string)($input['order_number'] ?? '')));
        if (!preg_match('/^[A-Z0-9-]{6,40}$/', $orderNumber)) {
            echo json_encode(['success' => false, 'message' => 'invalid order_number']); exit;
        }
        $oSt = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE order_number = ? LIMIT 1");
        $oSt->execute([$orderNumber]);
        $order = $oSt->fetch(PDO::FETCH_ASSOC);
        if (!$order) { echo json_encode(['success' => false, 'message' => 'order not found']); exit; }

        $domain = (string)$order['domain'];
        $username = 'vos_' . preg_replace('/[^A-Za-z0-9]/', '', $orderNumber);
        $home = '/var/www/customers/' . $orderNumber;
        $vhost = '/etc/nginx/sites-available/' . $domain . '.conf';
        $vhostEnabled = '/etc/nginx/sites-enabled/' . $domain . '.conf';

        $result = ['success' => true, 'order_number' => $orderNumber, 'domain' => $domain];

        // ── SSL 인증서 (certbot)
        $sslOut = @shell_exec('sudo /usr/bin/certbot certificates --cert-name ' . escapeshellarg($domain) . ' 2>&1');
        $ssl = ['present' => false];
        if ($sslOut && preg_match('/Expiry Date:\s*([\d\-:\sUTC+]+)\s*\(VALID:\s*(\d+)\s*days\)/', $sslOut, $m)) {
            $ssl = ['present' => true, 'expiry' => trim($m[1]), 'days_left' => (int)$m[2]];
        } elseif ($sslOut && preg_match('/INVALID|EXPIRED/i', $sslOut)) {
            $ssl = ['present' => true, 'expired' => true, 'raw' => substr(trim($sslOut), 0, 200)];
        }
        $result['ssl'] = $ssl;

        // ── 디스크 사용량 (du -sb)
        $diskBytes = null;
        $du = @shell_exec('sudo /usr/bin/du -sb ' . escapeshellarg($home) . ' 2>/dev/null');
        if ($du && preg_match('/^(\d+)/', $du, $m)) $diskBytes = (int)$m[1];
        $result['disk'] = [
            'home' => $home,
            'bytes' => $diskBytes,
            'human' => $diskBytes !== null ? _vh_human_bytes($diskBytes) : null,
        ];

        // ── DB 사용량 (information_schema)
        $dbName = $username; // = vos_<order>
        $dbUsage = ['name' => $dbName, 'bytes' => null];
        try {
            $sst = $pdo->prepare("SELECT IFNULL(SUM(data_length + index_length), 0) FROM information_schema.tables WHERE table_schema = ?");
            $sst->execute([$dbName]);
            $bytes = (int)$sst->fetchColumn();
            $dbUsage['bytes'] = $bytes;
            $dbUsage['human'] = _vh_human_bytes($bytes);
            $tcSt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ?");
            $tcSt->execute([$dbName]);
            $dbUsage['table_count'] = (int)$tcSt->fetchColumn();
        } catch (\Throwable $e) {
            $dbUsage['error'] = $e->getMessage();
        }
        $result['db'] = $dbUsage;

        // ── nginx 상태
        $result['nginx'] = [
            'vhost_path' => $vhost,
            'vhost_exists' => file_exists($vhost),
            'enabled' => file_exists($vhostEnabled),
        ];

        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    default:
        echo json_encode(['success' => false, 'message' => '알 수 없는 액션입니다.']);
        break;
}

if (!function_exists('_vh_human_bytes')) {
    function _vh_human_bytes(int $b): string {
        $units = ['B','KB','MB','GB','TB'];
        $i = 0;
        while ($b >= 1024 && $i < count($units) - 1) { $b /= 1024; $i++; }
        return number_format($b, $i > 0 ? 2 : 0) . ' ' . $units[$i];
    }
}
