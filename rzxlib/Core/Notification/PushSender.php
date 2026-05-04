<?php
namespace RzxLib\Core\Notification;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * VosCMS Web Push 발송 헬퍼 (rzx_push_subscriptions + .env VAPID 사용).
 *
 * 사용 예:
 *   $sender = new PushSender($pdo);
 *   $sender->sendToUser($userId, [
 *       'title' => '새 메시지',
 *       'body'  => '한동우 님의 메시지: ...',
 *       'link'  => '/mypage/messages?c=42',
 *       'icon'  => '/manifest/icon-192.png',
 *   ]);
 */
class PushSender
{
    private \PDO $pdo;
    private string $prefix;
    private array $vapid;

    public function __construct(\PDO $pdo, string $prefix = null)
    {
        $this->pdo = $pdo;
        $this->prefix = $prefix ?? ($_ENV['DB_PREFIX'] ?? 'rzx_');
        $this->vapid = [
            'publicKey'  => $_ENV['VAPID_PUBLIC_KEY']  ?? '',
            'privateKey' => $_ENV['VAPID_PRIVATE_KEY'] ?? '',
            'subject'    => $_ENV['VAPID_SUBJECT']     ?? 'mailto:admin@voscms.com',
        ];
    }

    /**
     * 단일 사용자에 푸시 발송.
     *
     * @param string $userId
     * @param array  $payload  ['title','body','link','icon','badge','tag'?]
     * @return array{sent:int, failed:int, expired:int}
     */
    public function sendToUser(string $userId, array $payload): array
    {
        return $this->sendToUsers([$userId], $payload);
    }

    /**
     * 다수 사용자에 푸시 발송.
     *
     * @param array<string> $userIds
     */
    public function sendToUsers(array $userIds, array $payload): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'expired' => 0];
        if (empty($userIds) || empty($this->vapid['publicKey']) || empty($this->vapid['privateKey'])) {
            return $result;
        }

        $place = implode(',', array_fill(0, count($userIds), '?'));
        $st = $this->pdo->prepare("SELECT id, user_id, endpoint, p256dh, auth FROM {$this->prefix}push_subscriptions WHERE user_id IN ($place)");
        $st->execute(array_values($userIds));
        $subs = $st->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($subs)) return $result;

        try {
            $wp = new WebPush(['VAPID' => $this->vapid]);
            $wp->setReuseVAPIDHeaders(true);
        } catch (\Throwable $e) {
            error_log('[PushSender] WebPush init failed: ' . $e->getMessage());
            return $result;
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        foreach ($subs as $s) {
            try {
                $sub = Subscription::create([
                    'endpoint'        => $s['endpoint'],
                    'keys'            => ['p256dh' => $s['p256dh'], 'auth' => $s['auth']],
                    'contentEncoding' => 'aes128gcm',
                ]);
                $wp->queueNotification($sub, $body, ['urgency' => 'normal', 'TTL' => 86400]);
            } catch (\Throwable $e) {
                error_log('[PushSender] queue: ' . $e->getMessage());
                $result['failed']++;
            }
        }

        $expiredIds = [];
        foreach ($wp->flush() as $report) {
            if ($report->isSuccess()) {
                $result['sent']++;
            } else {
                $code = $report->getResponse() ? $report->getResponse()->getStatusCode() : 0;
                // 410 Gone / 404 Not Found = 구독 만료. DB 정리
                if (in_array($code, [404, 410], true)) {
                    $endpoint = $report->getRequest()->getUri()->__toString();
                    foreach ($subs as $s) {
                        if ($s['endpoint'] === $endpoint) { $expiredIds[] = $s['id']; break; }
                    }
                    $result['expired']++;
                } else {
                    $result['failed']++;
                }
            }
        }
        if (!empty($expiredIds)) {
            $del = $this->pdo->prepare("DELETE FROM {$this->prefix}push_subscriptions WHERE id IN (" . implode(',', array_fill(0, count($expiredIds), '?')) . ")");
            $del->execute($expiredIds);
        }
        // last_used_at 갱신 (성공한 것만)
        if ($result['sent'] > 0) {
            $this->pdo->prepare("UPDATE {$this->prefix}push_subscriptions SET last_used_at = NOW() WHERE user_id IN ($place)")
                ->execute(array_values($userIds));
        }
        return $result;
    }

    /**
     * notification 테이블에 적재 + (구독 있으면) 푸시 발송 통합.
     * - rzx_notifications INSERT
     * - 푸시 발송 시도 → is_pushed=1 / pushed_at 갱신
     */
    public function notifyUser(string $userId, string $type, string $category, string $title, string $body, ?string $link = null, ?string $icon = 'bell', array $meta = []): int
    {
        // 1. notification INSERT
        $stmt = $this->pdo->prepare("INSERT INTO {$this->prefix}notifications
            (user_id, type, category, title, body, link, icon, expires_at, meta)
            VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 90 DAY), ?)");
        $stmt->execute([
            $userId, $type, $category, $title, $body, $link, $icon,
            empty($meta) ? null : json_encode($meta, JSON_UNESCAPED_UNICODE),
        ]);
        $notifId = (int)$this->pdo->lastInsertId();

        // 2. 푸시 발송 시도
        $pushResult = $this->sendToUser($userId, [
            'title' => $title,
            'body'  => $body,
            'link'  => $link,
            'icon'  => $icon === 'warning' || $icon === 'error'
                ? '/manifest/icon-192.png'
                : '/manifest/icon-192.png',
            'tag'   => $type . '-' . $category,
            'notification_id' => $notifId,
        ]);

        if (($pushResult['sent'] ?? 0) > 0) {
            $this->pdo->prepare("UPDATE {$this->prefix}notifications SET is_pushed = 1, pushed_at = NOW() WHERE id = ?")
                ->execute([$notifId]);
        }
        return $notifId;
    }
}
