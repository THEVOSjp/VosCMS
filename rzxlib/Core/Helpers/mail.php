<?php
/**
 * VosCMS 공용 메일 발송 헬퍼
 *
 * DB의 SMTP 설정(rzx_settings)을 사용하여 메일을 발송합니다.
 *
 * 사용법:
 *   // 기본 (DB 설정 자동 로드)
 *   rzx_send_mail($pdo, 'to@example.com', '제목', '<p>본문</p>');
 *
 *   // 옵션 지정
 *   rzx_send_mail($pdo, 'to@example.com', '제목', '본문', [
 *       'reply_to' => 'user@example.com',
 *       'reply_to_name' => '홍길동',
 *       'content_type' => 'text/plain',  // 기본: text/html
 *   ]);
 *
 *   // 설정 직접 전달 (DB 조회 없이)
 *   rzx_send_mail(null, 'to@example.com', '제목', '본문', [
 *       'smtp_host' => 'smtp.gmail.com',
 *       'smtp_port' => 587,
 *       ...
 *   ]);
 */

if (!function_exists('rzx_send_mail')) {

    /**
     * SMTP 메일 발송
     *
     * @param  \PDO|null $pdo      DB 연결 (설정 자동 로드 시 필요, 직접 전달 시 null 가능)
     * @param  string    $to       수신자 이메일
     * @param  string    $subject  제목
     * @param  string    $body     본문 (HTML 또는 텍스트)
     * @param  array     $options  추가 옵션:
     *   'reply_to'       => 회신 이메일 (기본: from_email)
     *   'reply_to_name'  => 회신 이름
     *   'content_type'   => 'text/html' | 'text/plain' (기본: text/html)
     *   'from_name'      => 발신자 이름 (기본: DB mail_from_name)
     *   'from_email'     => 발신자 이메일 (기본: DB mail_from_email)
     *   'smtp_host'      => 직접 지정 시
     *   'smtp_port'      => 직접 지정 시
     *   'smtp_username'  => 직접 지정 시
     *   'smtp_password'  => 직접 지정 시
     *   'smtp_encryption'=> 직접 지정 시 ('tls' | 'ssl')
     * @return bool 성공 여부
     */
    function rzx_send_mail(?\PDO $pdo, string $to, string $subject, string $body, array $options = []): bool
    {
        // 설정 로드: options에 smtp_host가 있으면 직접 사용, 없으면 DB 조회
        if (!empty($options['smtp_host'])) {
            $ms = $options;
        } elseif ($pdo) {
            $ms = _rzx_load_mail_settings($pdo);
        } else {
            error_log('[rzx_send_mail] No PDO and no SMTP settings provided');
            return false;
        }

        $host       = $ms['smtp_host'] ?? '';
        $port       = (int)($ms['smtp_port'] ?? 587);
        $encryption = $ms['smtp_encryption'] ?? 'tls';
        $username   = $ms['smtp_username'] ?? '';
        $password   = $ms['smtp_password'] ?? '';
        $fromName   = $options['from_name'] ?? $ms['mail_from_name'] ?? 'VosCMS';
        $fromEmail  = $options['from_email'] ?? $ms['mail_from_email'] ?? $username;
        $replyTo    = $options['reply_to'] ?? $ms['mail_reply_to'] ?? $fromEmail;
        $replyToName = $options['reply_to_name'] ?? '';
        $contentType = $options['content_type'] ?? 'text/html';

        if (!$host || !$username) {
            error_log('[rzx_send_mail] SMTP settings incomplete (host or username missing)');
            return false;
        }

        if (!$to) {
            error_log('[rzx_send_mail] No recipient specified');
            return false;
        }

        try {
            $prefix = ($encryption === 'ssl') ? 'ssl://' : '';
            $fp = @fsockopen($prefix . $host, $port, $errno, $errstr, 15);

            if (!$fp) {
                error_log("[rzx_send_mail] SMTP connection failed: $errstr ($errno)");
                return false;
            }

            // 서버 응답
            $response = fgets($fp, 515);
            if (substr($response, 0, 3) !== '220') {
                fclose($fp);
                error_log('[rzx_send_mail] SMTP server rejected connection: ' . trim($response));
                return false;
            }

            // EHLO
            fputs($fp, "EHLO localhost\r\n");
            while ($line = fgets($fp, 515)) {
                if (substr($line, 3, 1) === ' ') break;
            }

            // STARTTLS
            if ($encryption === 'tls') {
                fputs($fp, "STARTTLS\r\n");
                fgets($fp, 515);
                stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                fputs($fp, "EHLO localhost\r\n");
                while ($line = fgets($fp, 515)) {
                    if (substr($line, 3, 1) === ' ') break;
                }
            }

            // AUTH LOGIN
            fputs($fp, "AUTH LOGIN\r\n");
            fgets($fp, 515);
            fputs($fp, base64_encode($username) . "\r\n");
            fgets($fp, 515);
            fputs($fp, base64_encode($password) . "\r\n");
            $authResponse = fgets($fp, 515);

            if (substr($authResponse, 0, 3) !== '235') {
                error_log('[rzx_send_mail] SMTP auth failed: ' . trim($authResponse));
                fclose($fp);
                return false;
            }

            // MAIL FROM
            fputs($fp, "MAIL FROM:<{$fromEmail}>\r\n");
            fgets($fp, 515);

            // RCPT TO
            fputs($fp, "RCPT TO:<{$to}>\r\n");
            fgets($fp, 515);

            // DATA
            fputs($fp, "DATA\r\n");
            fgets($fp, 515);

            // 헤더
            $replyToHeader = $replyToName
                ? "=?UTF-8?B?" . base64_encode($replyToName) . "?= <{$replyTo}>"
                : $replyTo;

            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: {$contentType}; charset=UTF-8\r\n";
            $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
            $headers .= "Reply-To: {$replyToHeader}\r\n";
            $headers .= "To: {$to}\r\n";
            $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $headers .= "X-Mailer: VosCMS\r\n";

            fputs($fp, $headers . "\r\n" . $body . "\r\n.\r\n");
            $dataResponse = fgets($fp, 515);

            fputs($fp, "QUIT\r\n");
            fclose($fp);

            if (substr($dataResponse, 0, 3) === '250') {
                return true;
            }

            error_log('[rzx_send_mail] SMTP send failed: ' . trim($dataResponse));
            return false;

        } catch (\Throwable $e) {
            error_log('[rzx_send_mail] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * DB에서 메일 설정 로드
     */
    function _rzx_load_mail_settings(\PDO $pdo): array
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
        try {
            $stmt = $pdo->query("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` LIKE 'mail_%' OR `key` LIKE 'smtp_%'");
            $cache = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $cache[$row['key']] = $row['value'];
            }
        } catch (\PDOException $e) {
            error_log('[rzx_send_mail] Failed to load mail settings: ' . $e->getMessage());
            $cache = [];
        }

        return $cache;
    }
}
