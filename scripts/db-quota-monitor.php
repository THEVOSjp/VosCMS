#!/usr/bin/env php
<?php
/**
 * DB 쿼터 모니터링 — 10분마다 실행
 *
 * crontab: every 10min — /usr/bin/php /var/www/voscms-com/scripts/db-quota-monitor.php >> /var/www/voscms-com/storage/logs/db-quota.log 2>&1
 *
 * 동작:
 * 1. 활성 hosting 구독 전수 조사
 * 2. information_schema 합계 = 사용량
 * 3. 쿼터 = service_db_quota_mb (기본 100) + extra_db_storage 합산
 * 4. 비율별 임계 처리:
 *    - 90% 이상 100% 미만 → warning 메일 (24h throttle)
 *    - 100% 이상 110% 미만 → critical 메일 (24h throttle)
 *    - 110% 이상 → REVOKE INSERT/UPDATE/CREATE + blocked 메일
 *    - 80% 미만 + blocked 상태 → GRANT 복구 (히스테리시스)
 * 5. metadata 갱신: db_usage_bytes, db_usage_pct, db_quota_bytes, db_quota_blocked, last_quota_check
 */

date_default_timezone_set('Asia/Tokyo');
$start = microtime(true);
$log = function($msg) { echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL; };

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

// .env 로드
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

require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/rzxlib/Core/Helpers/mail.php';
require_once BASE_PATH . '/rzxlib/Core/Hosting/HostingProvisioner.php';

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\Throwable $e) {
    $log('DB connect 실패: ' . $e->getMessage()); exit(1);
}

// information_schema 조회용 어드민 PDO (일반 계정은 vos_* DB 안 보임)
$adminUser = $_ENV['HOSTING_DB_ADMIN_USER'] ?? '';
$adminPass = $_ENV['HOSTING_DB_ADMIN_PASS'] ?? '';
if (!$adminUser || !$adminPass) {
    $log('HOSTING_DB_ADMIN_USER/PASS 가 .env 에 없음 — 종료'); exit(1);
}
try {
    $adminPdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', $adminUser, $adminPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\Throwable $e) {
    $log('admin PDO 연결 실패: ' . $e->getMessage()); exit(1);
}

// 설정 — 기본 쿼터 (MB)
$baseMbStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'service_db_quota_mb' LIMIT 1");
$baseMbStmt->execute();
$baseMb = (int)($baseMbStmt->fetchColumn() ?: 100);
if ($baseMb <= 0) $baseMb = 100;

$THROTTLE_HOURS = 24; // 같은 임계 알림 재발송 간격
$WARN_PCT = 90;
$CRIT_PCT = 100;
$BLOCK_PCT = 110;
$RELEASE_PCT = 80; // 차단 해제 히스테리시스

$prov = new \RzxLib\Core\Hosting\HostingProvisioner($pdo);

// 활성 호스팅 구독 + 사용자 이메일
$sql = "SELECT s.id, s.order_id, s.user_id, s.metadata, s.currency, o.order_number, o.domain, u.email, u.name
        FROM {$prefix}subscriptions s
        JOIN {$prefix}orders o ON o.id = s.order_id
        JOIN {$prefix}users u ON u.id = s.user_id
        WHERE s.type = 'hosting' AND s.status = 'active'";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$log('활성 호스팅: ' . count($rows));

$totalChecked = 0; $sentWarn = 0; $sentCrit = 0; $blocked = 0; $released = 0; $errors = 0;

foreach ($rows as $r) {
    $subId = (int)$r['id'];
    $orderNumber = $r['order_number'];
    $domain = $r['domain'];
    $email = $r['email'];
    $userName = $r['name'] ?? '';
    $hMeta = json_decode($r['metadata'] ?? '{}', true) ?: [];

    $dbName = 'vos_' . preg_replace('/[^A-Za-z0-9_-]/', '', $orderNumber);

    // 1. 사용량 조회
    try {
        $sst = $adminPdo->prepare("SELECT IFNULL(SUM(data_length + index_length), 0) FROM information_schema.tables WHERE table_schema = ?");
        $sst->execute([$dbName]);
        $usedBytes = (int)$sst->fetchColumn();
    } catch (\Throwable $e) {
        $log("[{$orderNumber}] 사용량 조회 실패: " . $e->getMessage()); $errors++; continue;
    }

    // DB 가 없는 케이스 (프로비저닝 미완) — 스킵
    if ($usedBytes === 0) {
        try {
            $exStmt = $adminPdo->prepare("SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = ?");
            $exStmt->execute([$dbName]);
            if ((int)$exStmt->fetchColumn() === 0) { continue; }
        } catch (\Throwable $e) { /* fallthrough */ }
    }

    // 2. 쿼터 계산
    $extraMb = 0;
    foreach (($hMeta['extra_db_storage'] ?? []) as $ex) {
        $cap = $ex['capacity'] ?? '';
        if (preg_match('/^(\d+(?:\.\d+)?)\s*(MB|GB|TB)?/i', $cap, $m)) {
            $n = (float)$m[1]; $u = strtoupper($m[2] ?? 'MB');
            $extraMb += $u === 'GB' ? $n * 1024 : ($u === 'TB' ? $n * 1024 * 1024 : $n);
        }
    }
    $quotaBytes = (int)(($baseMb + $extraMb) * 1024 * 1024);
    if ($quotaBytes <= 0) continue;
    $pct = round($usedBytes / $quotaBytes * 1000) / 10;

    // 3. metadata 갱신 (모든 케이스)
    $hMeta['db_usage_bytes'] = $usedBytes;
    $hMeta['db_usage_pct'] = $pct;
    $hMeta['db_quota_bytes'] = $quotaBytes;
    $hMeta['last_quota_check'] = date('c');

    $wasBlocked = !empty($hMeta['db_quota_blocked']);
    $action = null;

    // 4. 임계 처리
    $notifKey = null;
    if ($pct >= $BLOCK_PCT) {
        // 차단
        if (!$wasBlocked) {
            $r1 = $prov->setDbQuotaBlock($orderNumber);
            if ($r1['success']) {
                $hMeta['db_quota_blocked'] = true;
                $hMeta['db_quota_blocked_at'] = date('c');
                $blocked++;
                $action = 'blocked';
                $notifKey = 'blocked';
            } else {
                $log("[{$orderNumber}] REVOKE 실패: " . ($r1['error'] ?? ''));
                $errors++;
            }
        }
    } elseif ($pct >= $CRIT_PCT) {
        $notifKey = 'critical';
    } elseif ($pct >= $WARN_PCT) {
        $notifKey = 'warning';
    } elseif ($pct < $RELEASE_PCT && $wasBlocked) {
        // 차단 해제 (히스테리시스)
        $r2 = $prov->releaseDbQuotaBlock($orderNumber);
        if ($r2['success']) {
            unset($hMeta['db_quota_blocked'], $hMeta['db_quota_blocked_at']);
            $hMeta['db_quota_released_at'] = date('c');
            $released++;
            $action = 'released';
        }
    }

    // 5. 메일 발송 + 메시지함 적재 (24h throttle)
    if ($notifKey && $email) {
        $lastNotify = $hMeta['last_quota_notif'] ?? [];
        $lastTs = isset($lastNotify[$notifKey]) ? strtotime($lastNotify[$notifKey]) : 0;
        if ((time() - $lastTs) >= $THROTTLE_HOURS * 3600) {
            $sent = sendQuotaMail($pdo, $email, $userName, $domain, $usedBytes, $quotaBytes, $pct, $notifKey);
            // 메시지함(rzx_notifications)에도 적재 — 메일 발송 성공 여부와 무관
            createDbQuotaNotification($pdo, $prefix, $r['user_id'], $domain, $usedBytes, $quotaBytes, $pct, $notifKey);
            if ($sent) {
                $lastNotify[$notifKey] = date('c');
                $hMeta['last_quota_notif'] = $lastNotify;
                if ($notifKey === 'warning') $sentWarn++;
                elseif ($notifKey === 'critical') $sentCrit++;
            } else {
                // 메일 실패해도 throttle 적용 (메시지함 알림은 들어갔으니 중복 방지)
                $lastNotify[$notifKey] = date('c');
                $hMeta['last_quota_notif'] = $lastNotify;
            }
        }
    }

    // 6. metadata 저장
    try {
        $upd = $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?");
        $upd->execute([json_encode($hMeta, JSON_UNESCAPED_UNICODE), $subId]);
    } catch (\Throwable $e) {
        $log("[{$orderNumber}] metadata UPDATE 실패: " . $e->getMessage()); $errors++;
    }

    // 7. 활동 로그 (action 발생 시만)
    if ($action) {
        $logAction = $action === 'blocked' ? 'db_quota_blocked' : 'db_quota_released';
        try {
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, ?, ?, 'system', 0)")
                ->execute([$r['order_id'], $logAction, json_encode([
                    'used_bytes' => $usedBytes, 'quota_bytes' => $quotaBytes, 'pct' => $pct,
                ], JSON_UNESCAPED_UNICODE)]);
        } catch (\Throwable $e) { /* silent */ }
    }

    $totalChecked++;
}

$elapsed = round((microtime(true) - $start) * 1000);
$log("완료 — checked={$totalChecked}, warn={$sentWarn}, crit={$sentCrit}, blocked={$blocked}, released={$released}, errors={$errors}, {$elapsed}ms");

// ─────────────────────────────────────────────
function createDbQuotaNotification(\PDO $pdo, string $prefix, string $userId, string $domain, int $usedBytes, int $quotaBytes, float $pct, string $level): void
{
    $fmt = function($b) {
        if ($b >= 1073741824) return number_format($b / 1073741824, 2) . 'GB';
        if ($b >= 1048576) return number_format($b / 1048576, 2) . 'MB';
        if ($b >= 1024) return number_format($b / 1024, 2) . 'KB';
        return $b . 'B';
    };
    $titles = [
        'warning'  => "DB 용량 경고 — {$domain} ({$pct}%)",
        'critical' => "DB 용량 한도 도달 — {$domain} ({$pct}%)",
        'blocked'  => "DB 쓰기 차단 — {$domain} ({$pct}%)",
    ];
    $bodies = [
        'warning'  => "{$domain} 의 DB 사용량이 " . $fmt($usedBytes) . " / " . $fmt($quotaBytes) . " 입니다. 곧 한도에 도달합니다.",
        'critical' => "{$domain} 의 DB 사용량이 " . $fmt($usedBytes) . " / " . $fmt($quotaBytes) . " 로 한도에 도달했습니다. 110% 초과 시 쓰기가 차단됩니다.",
        'blocked'  => "{$domain} 의 DB 쓰기가 차단되었습니다 (" . $fmt($usedBytes) . " / " . $fmt($quotaBytes) . "). 데이터 정리 또는 DB 용량 추가로 해제됩니다.",
    ];
    $icons = ['warning' => 'warning', 'critical' => 'warning', 'blocked' => 'error'];
    try {
        $stmt = $pdo->prepare("INSERT INTO {$prefix}notifications
            (user_id, type, category, title, body, link, icon, expires_at, meta)
            VALUES (?, 'system', 'db_quota', ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 90 DAY), ?)");
        $stmt->execute([
            $userId,
            $titles[$level] ?? '',
            $bodies[$level] ?? '',
            '/mypage/services',
            $icons[$level] ?? 'bell',
            json_encode([
                'domain' => $domain, 'used_bytes' => $usedBytes,
                'quota_bytes' => $quotaBytes, 'pct' => $pct, 'level' => $level,
            ], JSON_UNESCAPED_UNICODE),
        ]);
        $notifId = (int)$pdo->lastInsertId();

        // 푸시 발송 — DB 쿼터 critical/blocked 만 (warning 은 메일·인박스만으로 충분)
        if (in_array($level, ['critical', 'blocked'], true)) {
            try {
                require_once BASE_PATH . '/rzxlib/Core/Notification/PushSender.php';
                $sender = new \RzxLib\Core\Notification\PushSender($pdo, $prefix);
                $pr = $sender->sendToUser($userId, [
                    'title' => $titles[$level] ?? '',
                    'body'  => $bodies[$level] ?? '',
                    'link'  => '/mypage/services',
                    'tag'   => 'db-quota-' . $level,
                    'notification_id' => $notifId,
                ]);
                if (($pr['sent'] ?? 0) > 0) {
                    $pdo->prepare("UPDATE {$prefix}notifications SET is_pushed = 1, pushed_at = NOW() WHERE id = ?")
                        ->execute([$notifId]);
                }
            } catch (\Throwable $pe) { error_log('[db-quota-monitor] push: ' . $pe->getMessage()); }
        }
    } catch (\Throwable $e) {
        error_log('[db-quota-monitor] notification insert: ' . $e->getMessage());
    }
}

function sendQuotaMail(\PDO $pdo, string $to, string $name, string $domain, int $usedBytes, int $quotaBytes, float $pct, string $level): bool
{
    $fmt = function($b) {
        if ($b >= 1024 * 1024 * 1024) return number_format($b / 1024 / 1024 / 1024, 2) . 'GB';
        if ($b >= 1024 * 1024) return number_format($b / 1024 / 1024, 2) . 'MB';
        if ($b >= 1024) return number_format($b / 1024, 2) . 'KB';
        return $b . 'B';
    };
    $usedLabel = $fmt($usedBytes);
    $quotaLabel = $fmt($quotaBytes);

    $titles = [
        'warning'  => "[VosCMS] DB 용량 경고 — {$domain} ({$pct}%)",
        'critical' => "[VosCMS] DB 용량 한도 도달 — {$domain} ({$pct}%)",
        'blocked'  => "[VosCMS] DB 쓰기 차단 — {$domain} ({$pct}%)",
    ];
    $bodyTexts = [
        'warning'  => "DB 사용량이 쿼터의 90%를 초과했습니다. 곧 한도에 도달할 수 있으니 데이터 정리 또는 DB 용량 추가를 검토해주세요.",
        'critical' => "DB 사용량이 쿼터의 100%에 도달했습니다. 110% 도달 시 새로운 쓰기 작업이 차단됩니다. 즉시 데이터 정리 또는 DB 용량 추가가 필요합니다.",
        'blocked'  => "DB 사용량이 쿼터의 110%를 초과하여 쓰기 작업(INSERT/UPDATE/CREATE)이 차단되었습니다. 데이터 정리 또는 DB 용량 추가 결제 시 자동으로 해제됩니다.",
    ];
    $color = $level === 'blocked' ? '#dc2626' : ($level === 'critical' ? '#ef4444' : '#f59e0b');
    $title = $titles[$level] ?? '[VosCMS] DB 용량 알림';
    $body = $bodyTexts[$level] ?? '';

    $html = <<<HTML
<div style="font-family:'Noto Sans JP',sans-serif;max-width:600px;margin:0 auto;padding:20px;">
  <div style="border-left:4px solid {$color};padding:16px 20px;background:#f9fafb;border-radius:6px;">
    <h2 style="margin:0 0 12px;color:{$color};font-size:18px;">{$title}</h2>
    <p style="margin:0 0 16px;color:#374151;line-height:1.6;">안녕하세요, {$name} 님.<br/>{$body}</p>
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
      <tr><td style="padding:6px 8px;color:#6b7280;width:140px;">도메인</td><td style="padding:6px 8px;color:#111;font-family:monospace;">{$domain}</td></tr>
      <tr><td style="padding:6px 8px;color:#6b7280;">사용량</td><td style="padding:6px 8px;color:#111;font-family:monospace;">{$usedLabel}</td></tr>
      <tr><td style="padding:6px 8px;color:#6b7280;">쿼터</td><td style="padding:6px 8px;color:#111;font-family:monospace;">{$quotaLabel}</td></tr>
      <tr><td style="padding:6px 8px;color:#6b7280;">사용률</td><td style="padding:6px 8px;color:{$color};font-weight:bold;">{$pct}%</td></tr>
    </table>
    <div style="margin-top:20px;text-align:center;">
      <a href="https://voscms.com/mypage/services" style="display:inline-block;padding:10px 24px;background:#3b82f6;color:white;text-decoration:none;border-radius:6px;font-weight:bold;">마이페이지에서 DB 용량 추가하기</a>
    </div>
    <p style="margin:20px 0 0;font-size:11px;color:#9ca3af;">※ 100MB 단위로 추가 결제 가능합니다 (¥100/월 부터).</p>
  </div>
</div>
HTML;
    try {
        return rzx_send_mail($pdo, $to, $title, $html);
    } catch (\Throwable $e) {
        error_log('[db-quota-monitor] mail error: ' . $e->getMessage());
        return false;
    }
}
