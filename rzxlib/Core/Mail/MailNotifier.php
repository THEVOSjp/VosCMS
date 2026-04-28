<?php
/**
 * 메일 도메인 자동 셋업 관련 알림 발송.
 *
 * 트리거:
 *   - 결제 완료 + new_pending 발생 → 관리자에게 「도메인 구매 요청」 안내
 *   - 결제 완료 + existing_pending 발생 → 관리자에게 「NS 변경 안내」 알림
 *   - active 도달 (사장님 「등록 완료」 또는 「NS 활성 확인」 클릭 후) → 고객에게 「메일 사용 가능」
 */

namespace RzxLib\Core\Mail;

use PDO;

class MailNotifier
{
    private PDO $pdo;
    private string $tablePrefix;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->tablePrefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
    }

    /**
     * 결제 완료 + new_pending / existing_pending 발생 시 관리자에게 알림.
     */
    public function notifyAdminProvisionRequired(int $orderId, string $mode, array $provisionInfo): bool
    {
        $order = $this->loadOrder($orderId);
        if (!$order) return false;

        $adminEmail = $this->getAdminEmail();
        if (!$adminEmail) return false;

        $domain = $order['domain'] ?? '?';
        $orderNumber = $order['order_number'];
        $applicantName = $this->safeDecrypt($order['applicant_name'] ?? '');
        $applicantEmail = $this->safeDecrypt($order['applicant_email'] ?? '');
        $hostingPlan = $order['hosting_plan'] ?? '';
        $hostingCapacity = $order['hosting_capacity'] ?? '';

        if ($mode === 'new_pending') {
            $subject = "🔔 [VosCMS] 도메인 구매 요청: $domain";
            $body = $this->buildHtml(
                'New Domain Acquisition Required',
                "신규 도메인 등록 요청이 발생했습니다.<br>" .
                "NameSilo 등에서 도메인을 구매하고 NS 를 Cloudflare 로 변경한 후, " .
                "<a href=\"https://voscms.com/theadmin/service-orders/detail.php?id=$orderId\">관리자 페이지</a>에서 「도메인 취득 완료」 버튼을 클릭하세요.",
                [
                    '도메인' => $domain,
                    '주문번호' => $orderNumber,
                    '신청자' => $applicantName,
                    '연락처' => $applicantEmail,
                    '호스팅 플랜' => "$hostingPlan ($hostingCapacity)",
                ]
            );
        } elseif ($mode === 'existing_pending') {
            $nameServers = $provisionInfo['name_servers'] ?? [];
            $nsList = empty($nameServers) ? '(없음)' : implode('<br>', array_map('htmlspecialchars', $nameServers));
            $subject = "🔔 [VosCMS] NS 변경 안내 필요: $domain";
            $body = $this->buildHtml(
                'NS Change Notification Required',
                "고객의 보유 도메인이 Cloudflare 에 등록되었습니다. " .
                "고객에게 다음 NS 로 변경하도록 안내해주세요. " .
                "고객이 NS 변경 후 「NS 활성 확인」 버튼을 클릭하면 자동 셋업 완료.",
                [
                    '도메인' => $domain,
                    '주문번호' => $orderNumber,
                    '신청자' => $applicantName,
                    '연락처' => $applicantEmail,
                    '안내할 NS' => $nsList,
                ]
            );
        } else {
            return false;
        }

        require_once dirname(__DIR__) . '/Helpers/mail.php';
        $sent = rzx_send_mail($this->pdo, $adminEmail, $subject, $body);
        $this->logSent($orderId, $sent ? 'admin_notified' : 'admin_notify_failed', ['mode' => $mode, 'to' => $adminEmail]);
        return $sent;
    }

    /**
     * active 도달 시 고객에게 「메일 사용 가능」 안내.
     */
    public function notifyCustomerMailReady(int $orderId): bool
    {
        $order = $this->loadOrder($orderId);
        if (!$order) return false;

        $customerEmail = $this->safeDecrypt($order['applicant_email'] ?? '');
        if (!$customerEmail) {
            // user 테이블에서 fallback
            $u = $this->pdo->prepare("SELECT email FROM {$this->tablePrefix}users WHERE id = ?");
            $u->execute([$order['user_id']]);
            $customerEmail = $u->fetchColumn();
        }
        if (!$customerEmail) return false;

        $domain = $order['domain'] ?? '?';
        $applicantName = $this->safeDecrypt($order['applicant_name'] ?? '') ?: '고객';

        $subject = "✅ [VosCMS] $domain 메일 셋업 완료 — 사용 가능합니다";
        $body = $this->buildHtml(
            '메일 셋업 완료',
            htmlspecialchars($applicantName) . " 님,<br><br>" .
            "신청하신 도메인 <strong>" . htmlspecialchars($domain) . "</strong> 의 메일 셋업이 완료되었습니다. " .
            "지금부터 메일 계정을 추가하고 사용하실 수 있습니다.<br><br>" .
            "<a href=\"https://voscms.com/mypage/services/" . htmlspecialchars($order['order_number']) . "\">마이페이지에서 메일 관리</a>",
            [
                '도메인' => htmlspecialchars($domain),
                '메일 서버' => 'mail.voscms.com',
                '웹메일' => '<a href="https://mail.voscms.com/">https://mail.voscms.com/</a>',
                'IMAP' => 'mail.voscms.com:993 (SSL)',
                'SMTP' => 'mail.voscms.com:587 (STARTTLS)',
            ]
        );

        require_once dirname(__DIR__) . '/Helpers/mail.php';
        $sent = rzx_send_mail($this->pdo, $customerEmail, $subject, $body);
        $this->logSent($orderId, $sent ? 'customer_notified' : 'customer_notify_failed', ['to' => $customerEmail]);
        return $sent;
    }

    // ── 내부 helpers ──

    private function loadOrder(int $orderId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tablePrefix}orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function getAdminEmail(): ?string
    {
        // 1. settings 의 admin_email
        $s = $this->pdo->query("SELECT `value` FROM {$this->tablePrefix}settings WHERE `key` = 'admin_email' OR `key` = 'mail_from_email' ORDER BY (`key`='admin_email') DESC LIMIT 1");
        $email = $s->fetchColumn();
        if ($email) return $email;

        // 2. supervisor / admin 사용자 첫 번째
        $s = $this->pdo->query("SELECT email FROM {$this->tablePrefix}users WHERE role IN ('supervisor', 'admin') AND email IS NOT NULL AND email != '' LIMIT 1");
        return $s->fetchColumn() ?: null;
    }

    private function safeDecrypt(?string $value): string
    {
        if (!$value) return '';
        if (function_exists('decrypt') && str_starts_with($value, 'enc:')) {
            $r = decrypt($value);
            return $r ?: '';
        }
        return $value;
    }

    /**
     * 간단 HTML 이메일 본문 (table 형식).
     */
    private function buildHtml(string $title, string $intro, array $fields): string
    {
        $rows = '';
        foreach ($fields as $k => $v) {
            $rows .= "<tr><td style=\"padding:6px 12px;background:#f9fafb;font-weight:bold;width:120px;border-bottom:1px solid #e5e7eb\">"
                  . htmlspecialchars($k) . "</td><td style=\"padding:6px 12px;border-bottom:1px solid #e5e7eb\">"
                  . $v . "</td></tr>";
        }
        return <<<HTML
<!DOCTYPE html>
<html>
<body style="font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:20px;color:#1f2937">
  <h2 style="color:#2563eb;border-bottom:2px solid #2563eb;padding-bottom:8px">$title</h2>
  <p style="line-height:1.6">$intro</p>
  <table style="width:100%;border-collapse:collapse;margin-top:16px;font-size:14px">$rows</table>
  <p style="margin-top:24px;font-size:12px;color:#6b7280;border-top:1px solid #e5e7eb;padding-top:12px">
    이 이메일은 VosCMS 시스템에서 자동 발송되었습니다.
  </p>
</body>
</html>
HTML;
    }

    private function logSent(int $orderId, string $action, array $detail): void
    {
        try {
            $this->pdo->prepare("INSERT INTO {$this->tablePrefix}order_logs (order_id, action, detail, actor_type) VALUES (?, ?, ?, 'system')")
                ->execute([$orderId, $action, json_encode($detail, JSON_UNESCAPED_UNICODE)]);
        } catch (\Throwable $e) { /* silent */ }
    }
}
