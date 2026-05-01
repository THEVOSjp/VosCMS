<?php
namespace RzxLib\Core\Notification;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * 관리자 웹푸시 알림 헬퍼.
 *
 * 사용 예:
 *   $notifier = new WebPushNotifier($pdo);
 *   $notifier->notifyAdmins('도메인 신청', 'restaurant.or.kr 등록 요청', '/admin/domain-management');
 *   $notifier->notifyUser($userId, '답변 도착', '1:1 상담 답변이 등록되었습니다', '/mypage/services');
 */
class WebPushNotifier
{
    private \PDO $pdo;
    private string $prefix;

    public function __construct(\PDO $pdo, string $prefix = null)
    {
        $this->pdo = $pdo;
        $this->prefix = $prefix ?? ($_ENV['DB_PREFIX'] ?? 'rzx_');
    }

    /**
     * 특정 사용자에게 푸시 송신.
     */
    public function notifyUser(string $userId, string $title, string $body, ?string $url = null): array
    {
        return $this->sendToUsers([$userId], $title, $body, $url);
    }

    /**
     * 관리자(admin / supervisor) 전체에게 푸시 송신.
     */
    public function notifyAdmins(string $title, string $body, ?string $url = null): array
    {
        $st = $this->pdo->query("SELECT id FROM {$this->prefix}users WHERE role IN ('admin','supervisor')");
        $adminIds = $st->fetchAll(\PDO::FETCH_COLUMN);
        if (empty($adminIds)) {
            return ['sent' => 0, 'failed' => 0, 'expired_removed' => 0, 'message_id' => null, 'skipped' => 'no admin users'];
        }
        return $this->sendToUsers($adminIds, $title, $body, $url);
    }

    /**
     * 지정한 user_id 리스트에게 푸시 송신 + DB 기록.
     *
     * @param array<int, string> $userIds
     * @return array{sent:int, failed:int, expired_removed:int, message_id:?int}
     */
    private function sendToUsers(array $userIds, string $title, string $body, ?string $url = null): array
    {
        if (empty($userIds)) {
            return ['sent' => 0, 'failed' => 0, 'expired_removed' => 0, 'message_id' => null, 'skipped' => 'no users'];
        }

        // VAPID
        $sSt = $this->pdo->prepare("SELECT `key`,`value` FROM {$this->prefix}settings WHERE `key` IN ('webpush_enabled','vapid_public_key','vapid_private_key','vapid_subject','notif_default_icon','notif_default_badge')");
        $sSt->execute();
        $cfg = [];
        while ($r = $sSt->fetch(\PDO::FETCH_ASSOC)) $cfg[$r['key']] = $r['value'];

        if (($cfg['webpush_enabled'] ?? '0') !== '1' || empty($cfg['vapid_public_key']) || empty($cfg['vapid_private_key'])) {
            return ['sent' => 0, 'failed' => 0, 'expired_removed' => 0, 'message_id' => null, 'skipped' => 'vapid not configured'];
        }

        // DB 메시지 기록
        $msgIns = $this->pdo->prepare("INSERT INTO {$this->prefix}push_messages (title, body, url, sent_count, created_at) VALUES (?, ?, ?, 0, NOW())");
        $msgIns->execute([$title, $body, $url ?? '']);
        $messageId = (int)$this->pdo->lastInsertId();

        // 구독자 조회
        $place = implode(',', array_fill(0, count($userIds), '?'));
        $sql = "SELECT id, endpoint, p256dh, auth FROM {$this->prefix}push_subscribers WHERE user_id IN ($place)";
        $st = $this->pdo->prepare($sql);
        $st->execute(array_values($userIds));
        $subs = $st->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($subs)) {
            return ['sent' => 0, 'failed' => 0, 'expired_removed' => 0, 'message_id' => $messageId, 'skipped' => 'no subscribers'];
        }

        $autoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            return ['sent' => 0, 'failed' => 0, 'expired_removed' => 0, 'message_id' => $messageId, 'skipped' => 'autoload missing'];
        }
        require_once $autoload;

        $auth = [
            'VAPID' => [
                'subject' => $cfg['vapid_subject'] ?: 'mailto:admin@voscms.com',
                'publicKey' => $cfg['vapid_public_key'],
                'privateKey' => $cfg['vapid_private_key'],
            ],
        ];

        try {
            $webPush = new WebPush($auth);
        } catch (\Throwable $e) {
            error_log('[WebPushNotifier] init: ' . $e->getMessage());
            return ['sent' => 0, 'failed' => count($subs), 'expired_removed' => 0, 'message_id' => $messageId, 'error' => $e->getMessage()];
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url ?? '/',
            'icon' => $cfg['notif_default_icon'] ?? '/favicon.ico',
            'badge' => $cfg['notif_default_badge'] ?? '/favicon.ico',
        ], JSON_UNESCAPED_UNICODE);

        $idMap = [];
        foreach ($subs as $row) {
            try {
                $sub = Subscription::create([
                    'endpoint' => $row['endpoint'],
                    'keys' => [
                        'p256dh' => $row['p256dh'],
                        'auth' => $row['auth'],
                    ],
                ]);
                $webPush->queueNotification($sub, $payload);
                $idMap[$row['endpoint']] = (int)$row['id'];
            } catch (\Throwable $e) {
                error_log('[WebPushNotifier] queue: ' . $e->getMessage());
            }
        }

        $sent = 0; $failed = 0; $expiredRemoved = 0;
        $delIds = [];
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;
            } else {
                $failed++;
                if ($report->isSubscriptionExpired()) {
                    $endpoint = $report->getRequest()->getUri()->__toString();
                    if (isset($idMap[$endpoint])) {
                        $delIds[] = $idMap[$endpoint];
                    }
                }
            }
        }
        if (!empty($delIds)) {
            $delPlace = implode(',', array_fill(0, count($delIds), '?'));
            $this->pdo->prepare("DELETE FROM {$this->prefix}push_subscribers WHERE id IN ($delPlace)")->execute($delIds);
            $expiredRemoved = count($delIds);
        }

        $this->pdo->prepare("UPDATE {$this->prefix}push_messages SET sent_count = ? WHERE id = ?")
            ->execute([$sent, $messageId]);

        return [
            'sent' => $sent,
            'failed' => $failed,
            'expired_removed' => $expiredRemoved,
            'message_id' => $messageId,
        ];
    }

    /**
     * 도메인 작업 알림 송신 — 도메인 신청/등록 요청 시.
     */
    public function notifyAdminDomainAction(int $orderId, string $action, array $info, string $adminPath = 'admin'): array
    {
        $domain = $info['domain'] ?? '?';
        $option = $info['option'] ?? '';
        $optLabel = $option === 'new' ? '신규 등록' : ($option === 'existing' ? '보유 도메인 연결' : ($option === 'free' ? '무료 서브도메인' : ''));

        if ($action === 'pay_domain') {
            $title = '🌐 도메인 신청 결제 완료';
            $body = sprintf('%s (%s)', $domain, $optLabel);
        } elseif ($action === 'service_order_with_domain') {
            $title = '🌐 서비스 신청 — 도메인 작업 필요';
            $body = sprintf('%s (%s)', $domain, $optLabel);
        } else {
            $title = '🌐 도메인 작업 요청';
            $body = $domain;
        }
        $url = '/' . trim($adminPath, '/') . '/domain-management';
        return $this->notifyAdmins($title, $body, $url);
    }
}
