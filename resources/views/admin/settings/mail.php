<?php
/**
 * RezlyX Admin Settings - Mail Settings
 * 메일 설정 및 이메일 템플릿 관리
 */

// Initialize database and settings
require_once __DIR__ . '/_init.php';

$pageTitle = __('admin.settings.mail.page_title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'mail';

// 지원 언어 목록 가져오기
$supportedLanguages = json_decode($settings['supported_languages'] ?? '["ko","en","ja"]', true) ?: ['ko', 'en', 'ja'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_mail_settings') {
        // 메일 설정 처리
        $mailFromName = trim($_POST['mail_from_name'] ?? '');
        $mailFromEmail = trim($_POST['mail_from_email'] ?? '');
        $mailApplyAll = isset($_POST['mail_apply_all']) ? '1' : '0';
        $mailReplyTo = trim($_POST['mail_reply_to'] ?? '');
        $mailDriver = $_POST['mail_driver'] ?? 'mail';
        $smtpHost = trim($_POST['smtp_host'] ?? '');
        $smtpPort = trim($_POST['smtp_port'] ?? '587');
        $smtpEncryption = $_POST['smtp_encryption'] ?? 'tls';
        $smtpUsername = trim($_POST['smtp_username'] ?? '');
        $smtpPassword = $_POST['smtp_password'] ?? '';

        try {
            $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute(['mail_from_name', $mailFromName]);
            $stmt->execute(['mail_from_email', $mailFromEmail]);
            $stmt->execute(['mail_apply_all', $mailApplyAll]);
            $stmt->execute(['mail_reply_to', $mailReplyTo]);
            $stmt->execute(['mail_driver', $mailDriver]);
            $stmt->execute(['smtp_host', $smtpHost]);
            $stmt->execute(['smtp_port', $smtpPort]);
            $stmt->execute(['smtp_encryption', $smtpEncryption]);
            $stmt->execute(['smtp_username', $smtpUsername]);

            // 비밀번호는 비어있지 않은 경우에만 업데이트
            if (!empty($smtpPassword)) {
                $stmt->execute(['smtp_password', $smtpPassword]);
                $settings['smtp_password'] = $smtpPassword;
            }

            // 설정 배열 업데이트
            $settings['mail_from_name'] = $mailFromName;
            $settings['mail_from_email'] = $mailFromEmail;
            $settings['mail_apply_all'] = $mailApplyAll;
            $settings['mail_reply_to'] = $mailReplyTo;
            $settings['mail_driver'] = $mailDriver;
            $settings['smtp_host'] = $smtpHost;
            $settings['smtp_port'] = $smtpPort;
            $settings['smtp_encryption'] = $smtpEncryption;
            $settings['smtp_username'] = $smtpUsername;

            $message = __('admin.settings.success');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('admin.settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'update_email_templates') {
        // 이메일 템플릿 저장
        $templateType = $_POST['template_type'] ?? 'password_reset';
        $templateLang = $_POST['template_lang'] ?? 'ko';

        $subject = trim($_POST['email_subject'] ?? '');
        $body = trim($_POST['email_body'] ?? '');

        $templateKey = "email_template_{$templateType}_{$templateLang}";
        $subjectKey = "email_subject_{$templateType}_{$templateLang}";

        try {
            $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute([$subjectKey, $subject]);
            $stmt->execute([$templateKey, $body]);

            $message = __('admin.settings.mail.templates.saved');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('admin.settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'send_test_email') {
        // 테스트 이메일 발송
        header('Content-Type: application/json');

        $testEmail = trim($_POST['test_email'] ?? '');
        $templateType = $_POST['template_type'] ?? 'password_reset';
        $templateLang = $_POST['template_lang'] ?? 'ko';

        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => __('admin.settings.mail.test.invalid_email')]);
            exit;
        }

        try {
            // 템플릿 가져오기
            $subjectKey = "email_subject_{$templateType}_{$templateLang}";
            $templateKey = "email_template_{$templateType}_{$templateLang}";

            $stmt = $pdo->prepare("SELECT `key`, `value` FROM rzx_settings WHERE `key` IN (?, ?)");
            $stmt->execute([$subjectKey, $templateKey]);
            $templates = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

            $subject = $templates[$subjectKey] ?? '[{site_name}] 비밀번호 재설정';
            $body = $templates[$templateKey] ?? '';

            // 변수 치환
            $siteName = $settings['site_name'] ?? 'RezlyX';
            $testLink = ($settings['site_url'] ?? 'http://localhost') . '/reset-password?token=test_token_12345';

            $subject = str_replace('{site_name}', $siteName, $subject);
            $body = str_replace(['{site_name}', '{user_name}', '{reset_link}', '{expiry_minutes}'],
                               [$siteName, '테스트 사용자', $testLink, '60'], $body);

            // 메일 발송
            $fromName = $settings['mail_from_name'] ?? $siteName;
            $fromEmail = $settings['mail_from_email'] ?? 'noreply@localhost';

            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromEmail . '>',
                'X-Mailer: RezlyX/1.0'
            ];

            $sent = @mail($testEmail, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));

            if ($sent) {
                echo json_encode(['success' => true, 'message' => str_replace('{email}', $testEmail, __('admin.settings.mail.test.sent_success'))]);
            } else {
                echo json_encode(['success' => false, 'error' => __('admin.settings.mail.test.sent_failed')]);
            }
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    } elseif ($action === 'test_smtp') {
        // SMTP 연결 + 인증 테스트
        header('Content-Type: application/json');

        $smtpHost = trim($_POST['smtp_host'] ?? '');
        $smtpPort = (int) ($_POST['smtp_port'] ?? 587);
        $smtpEncryption = $_POST['smtp_encryption'] ?? 'tls';
        $smtpUsername = trim($_POST['smtp_username'] ?? '');
        $smtpPassword = $_POST['smtp_password'] ?? '';

        if (empty($smtpHost)) {
            echo json_encode(['success' => false, 'error' => __('admin.settings.mail.smtp.host_required')]);
            exit;
        }

        try {
            $timeout = 10;
            $errno = 0;
            $errstr = '';

            // 암호화에 따른 연결 프리픽스
            $prefix = '';
            if ($smtpEncryption === 'ssl') {
                $prefix = 'ssl://';
            } elseif ($smtpEncryption === 'tls') {
                $prefix = 'tcp://';
            }

            $fp = @fsockopen($prefix . $smtpHost, $smtpPort, $errno, $errstr, $timeout);

            if (!$fp) {
                echo json_encode(['success' => false, 'error' => __('admin.settings.mail.smtp.connection_failed') . ": $errstr ($errno)"]);
                exit;
            }

            // 서버 응답 확인
            $response = fgets($fp, 515);
            if (substr($response, 0, 3) !== '220') {
                fclose($fp);
                echo json_encode(['success' => false, 'error' => __('admin.settings.mail.smtp.server_error') . ': ' . trim($response)]);
                exit;
            }

            // EHLO 명령
            fputs($fp, "EHLO localhost\r\n");
            while ($line = fgets($fp, 515)) {
                if (substr($line, 3, 1) === ' ') break;
            }

            // STARTTLS (TLS 암호화)
            if ($smtpEncryption === 'tls') {
                fputs($fp, "STARTTLS\r\n");
                $starttlsResp = fgets($fp, 515);
                if (substr($starttlsResp, 0, 3) !== '220') {
                    fclose($fp);
                    echo json_encode(['success' => false, 'error' => 'STARTTLS failed: ' . trim($starttlsResp)]);
                    exit;
                }

                stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

                // STARTTLS 후 재 EHLO
                fputs($fp, "EHLO localhost\r\n");
                while ($line = fgets($fp, 515)) {
                    if (substr($line, 3, 1) === ' ') break;
                }
            }

            // AUTH LOGIN (사용자명/비밀번호가 있는 경우)
            if (!empty($smtpUsername) && !empty($smtpPassword)) {
                fputs($fp, "AUTH LOGIN\r\n");
                $authResp = fgets($fp, 515);
                if (substr($authResp, 0, 3) !== '334') {
                    fclose($fp);
                    echo json_encode(['success' => false, 'error' => 'AUTH LOGIN not supported: ' . trim($authResp)]);
                    exit;
                }

                fputs($fp, base64_encode($smtpUsername) . "\r\n");
                fgets($fp, 515);

                fputs($fp, base64_encode($smtpPassword) . "\r\n");
                $loginResp = fgets($fp, 515);

                if (substr($loginResp, 0, 3) !== '235') {
                    fclose($fp);
                    echo json_encode(['success' => false, 'error' => __('admin.settings.mail.smtp.auth_failed') . ': ' . trim($loginResp)]);
                    exit;
                }
            }

            // QUIT
            fputs($fp, "QUIT\r\n");
            fclose($fp);

            $msg = !empty($smtpUsername)
                ? __('admin.settings.mail.smtp.auth_success')
                : __('admin.settings.mail.smtp.connected');
            echo json_encode(['success' => true, 'message' => $msg]);
            exit;

        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

// 기본 템플릿 (언어별)
$defaultTemplates = [
    'password_reset' => [
        'ko' => [
            'subject' => '[{site_name}] 비밀번호 재설정',
            'body' => '<!DOCTYPE html>
<html lang="ko">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">비밀번호 재설정</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">안녕하세요, {user_name}님.</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">비밀번호 재설정을 요청하셨습니다. 아래 버튼을 클릭하여 새 비밀번호를 설정해주세요.</p>
<table width="100%"><tr><td align="center">
<a href="{reset_link}" style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">비밀번호 재설정</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">이 링크는 {expiry_minutes}분 후에 만료됩니다.</p>
<p style="margin:10px 0 0;color:#71717a;font-size:13px;">비밀번호 재설정을 요청하지 않으셨다면 이 이메일을 무시해주세요.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'en' => [
            'subject' => '[{site_name}] Password Reset',
            'body' => '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Password Reset</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Hello, {user_name}.</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">You requested a password reset. Click the button below to set a new password.</p>
<table width="100%"><tr><td align="center">
<a href="{reset_link}" style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Reset Password</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">This link will expire in {expiry_minutes} minutes.</p>
<p style="margin:10px 0 0;color:#71717a;font-size:13px;">If you did not request a password reset, please ignore this email.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'ja' => [
            'subject' => '[{site_name}] パスワードリセット',
            'body' => '<!DOCTYPE html>
<html lang="ja">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">パスワードリセット</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">{user_name}様、こんにちは。</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">パスワードリセットのリクエストがありました。下のボタンをクリックして新しいパスワードを設定してください。</p>
<table width="100%"><tr><td align="center">
<a href="{reset_link}" style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">パスワードリセット</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">このリンクは{expiry_minutes}分後に期限切れになります。</p>
<p style="margin:10px 0 0;color:#71717a;font-size:13px;">パスワードリセットをリクエストしていない場合は、このメールを無視してください。</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'zh_CN' => [
            'subject' => '[{site_name}] 密码重置',
            'body' => '<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">密码重置</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">您好，{user_name}。</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">您已请求重置密码。请点击下面的按钮设置新密码。</p>
<table width="100%"><tr><td align="center">
<a href="{reset_link}" style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">重置密码</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">此链接将在{expiry_minutes}分钟后过期。</p>
<p style="margin:10px 0 0;color:#71717a;font-size:13px;">如果您没有请求重置密码，请忽略此邮件。</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'zh_TW' => [
            'subject' => '[{site_name}] 密碼重設',
            'body' => '<!DOCTYPE html>
<html lang="zh-TW">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">密碼重設</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">您好，{user_name}。</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">您已請求重設密碼。請點擊下方按鈕設定新密碼。</p>
<table width="100%"><tr><td align="center">
<a href="{reset_link}" style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">重設密碼</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">此連結將在{expiry_minutes}分鐘後失效。</p>
<p style="margin:10px 0 0;color:#71717a;font-size:13px;">如果您沒有請求重設密碼，請忽略此郵件。</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'de' => [
            'subject' => '[{site_name}] Passwort zurücksetzen',
            'body' => '<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Passwort zurücksetzen</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Hallo {user_name},</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Sie haben das Zurücksetzen Ihres Passworts angefordert. Klicken Sie auf die Schaltfläche unten, um ein neues Passwort festzulegen.</p>
<table width="100%"><tr><td align="center">
<a href="{reset_link}" style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Passwort zurücksetzen</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Dieser Link läuft in {expiry_minutes} Minuten ab.</p>
<p style="margin:10px 0 0;color:#71717a;font-size:13px;">Falls Sie kein Zurücksetzen des Passworts angefordert haben, ignorieren Sie bitte diese E-Mail.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'es' => [
            'subject' => '[{site_name}] Restablecer contraseña',
            'body' => '<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Restablecer contraseña</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Hola {user_name},</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Ha solicitado restablecer su contraseña. Haga clic en el botón de abajo para establecer una nueva contraseña.</p>
<table width="100%"><tr><td align="center">
<a href="{reset_link}" style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Restablecer contraseña</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Este enlace caducará en {expiry_minutes} minutos.</p>
<p style="margin:10px 0 0;color:#71717a;font-size:13px;">Si no solicitó restablecer su contraseña, ignore este correo electrónico.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'fr' => [
            'subject' => '[{site_name}] Réinitialisation du mot de passe',
            'body' => '<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Réinitialisation du mot de passe</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Bonjour {user_name},</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour définir un nouveau mot de passe.</p>
<table width="100%"><tr><td align="center">
<a href="{reset_link}" style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Réinitialiser le mot de passe</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Ce lien expirera dans {expiry_minutes} minutes.</p>
<p style="margin:10px 0 0;color:#71717a;font-size:13px;">Si vous n\'avez pas demandé la réinitialisation de votre mot de passe, veuillez ignorer cet e-mail.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'id' => [
            'subject' => '[{site_name}] Atur Ulang Kata Sandi',
            'body' => '<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Atur Ulang Kata Sandi</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Halo {user_name},</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Anda telah meminta pengaturan ulang kata sandi. Klik tombol di bawah untuk mengatur kata sandi baru.</p>
<table width="100%"><tr><td align="center">
<a href="{reset_link}" style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Atur Ulang Kata Sandi</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Tautan ini akan kedaluwarsa dalam {expiry_minutes} menit.</p>
<p style="margin:10px 0 0;color:#71717a;font-size:13px;">Jika Anda tidak meminta pengaturan ulang kata sandi, abaikan email ini.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'mn' => [
            'subject' => '[{site_name}] Нууц үг шинэчлэх',
            'body' => '<!DOCTYPE html>
<html lang="mn">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Нууц үг шинэчлэх</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Сайн байна уу {user_name},</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Та нууц үгээ шинэчлэх хүсэлт илгээсэн байна. Шинэ нууц үг тохируулахын тулд доорх товчийг дарна уу.</p>
<table width="100%"><tr><td align="center">
<a href="{reset_link}" style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Нууц үг шинэчлэх</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Энэ холбоос {expiry_minutes} минутын дараа хүчингүй болно.</p>
<p style="margin:10px 0 0;color:#71717a;font-size:13px;">Хэрэв та нууц үгээ шинэчлэх хүсэлт илгээгээгүй бол энэ имэйлийг үл тоомсорлоно уу.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'ru' => [
            'subject' => '[{site_name}] Сброс пароля',
            'body' => '<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Сброс пароля</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Здравствуйте, {user_name}.</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Вы запросили сброс пароля. Нажмите кнопку ниже, чтобы установить новый пароль.</p>
<table width="100%"><tr><td align="center">
<a href="{reset_link}" style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Сбросить пароль</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Эта ссылка истечёт через {expiry_minutes} минут.</p>
<p style="margin:10px 0 0;color:#71717a;font-size:13px;">Если вы не запрашивали сброс пароля, проигнорируйте это письмо.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'tr' => [
            'subject' => '[{site_name}] Şifre Sıfırlama',
            'body' => '<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Şifre Sıfırlama</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Merhaba {user_name},</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Şifre sıfırlama talebinde bulundunuz. Yeni şifrenizi belirlemek için aşağıdaki düğmeye tıklayın.</p>
<table width="100%"><tr><td align="center">
<a href="{reset_link}" style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Şifreyi Sıfırla</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Bu bağlantı {expiry_minutes} dakika sonra geçersiz olacaktır.</p>
<p style="margin:10px 0 0;color:#71717a;font-size:13px;">Şifre sıfırlama talebinde bulunmadıysanız, bu e-postayı dikkate almayın.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'vi' => [
            'subject' => '[{site_name}] Đặt lại mật khẩu',
            'body' => '<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Đặt lại mật khẩu</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Xin chào {user_name},</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Bạn đã yêu cầu đặt lại mật khẩu. Nhấp vào nút bên dưới để đặt mật khẩu mới.</p>
<table width="100%"><tr><td align="center">
<a href="{reset_link}" style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Đặt lại mật khẩu</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Liên kết này sẽ hết hạn sau {expiry_minutes} phút.</p>
<p style="margin:10px 0 0;color:#71717a;font-size:13px;">Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ]
    ],
    'welcome' => [
        'ko' => [
            'subject' => '[{site_name}] 가입을 환영합니다!',
            'body' => '<!DOCTYPE html>
<html lang="ko">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#10b981,#059669);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">환영합니다!</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">안녕하세요, {user_name}님!</p>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">{site_name}에 가입해 주셔서 감사합니다. 이제 모든 서비스를 이용하실 수 있습니다.</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">아래 버튼을 클릭하여 사이트를 둘러보세요.</p>
<table width="100%"><tr><td align="center">
<a href="{login_link}" style="display:inline-block;padding:14px 32px;background:#10b981;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">시작하기</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">문의사항이 있으시면 언제든지 연락해 주세요.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'en' => [
            'subject' => '[{site_name}] Welcome!',
            'body' => '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#10b981,#059669);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Welcome!</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Hello {user_name}!</p>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Thank you for joining {site_name}. You now have access to all our services.</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Click the button below to explore our site.</p>
<table width="100%"><tr><td align="center">
<a href="{login_link}" style="display:inline-block;padding:14px 32px;background:#10b981;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Get Started</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">If you have any questions, feel free to contact us.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'ja' => [
            'subject' => '[{site_name}] ご登録ありがとうございます！',
            'body' => '<!DOCTYPE html>
<html lang="ja">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#10b981,#059669);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">ようこそ！</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">{user_name}様、こんにちは！</p>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">{site_name}にご登録いただきありがとうございます。すべてのサービスをご利用いただけます。</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">下のボタンをクリックしてサイトをご覧ください。</p>
<table width="100%"><tr><td align="center">
<a href="{login_link}" style="display:inline-block;padding:14px 32px;background:#10b981;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">始める</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">ご質問がございましたら、お気軽にお問い合わせください。</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'zh_CN' => [
            'subject' => '[{site_name}] 欢迎加入！',
            'body' => '<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#10b981,#059669);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">欢迎！</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">您好，{user_name}！</p>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">感谢您加入{site_name}。您现在可以使用所有服务了。</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">点击下面的按钮开始浏览。</p>
<table width="100%"><tr><td align="center">
<a href="{login_link}" style="display:inline-block;padding:14px 32px;background:#10b981;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">开始使用</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">如有任何问题，请随时联系我们。</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'zh_TW' => [
            'subject' => '[{site_name}] 歡迎加入！',
            'body' => '<!DOCTYPE html>
<html lang="zh-TW">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#10b981,#059669);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">歡迎！</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">您好，{user_name}！</p>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">感謝您加入{site_name}。您現在可以使用所有服務了。</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">點擊下面的按鈕開始瀏覽。</p>
<table width="100%"><tr><td align="center">
<a href="{login_link}" style="display:inline-block;padding:14px 32px;background:#10b981;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">開始使用</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">如有任何問題，請隨時聯繫我們。</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'de' => [
            'subject' => '[{site_name}] Willkommen!',
            'body' => '<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#10b981,#059669);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Willkommen!</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Hallo {user_name}!</p>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Vielen Dank für Ihre Registrierung bei {site_name}. Sie haben nun Zugang zu allen unseren Diensten.</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Klicken Sie auf die Schaltfläche unten, um unsere Seite zu erkunden.</p>
<table width="100%"><tr><td align="center">
<a href="{login_link}" style="display:inline-block;padding:14px 32px;background:#10b981;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Jetzt starten</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Bei Fragen können Sie uns jederzeit kontaktieren.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'es' => [
            'subject' => '[{site_name}] ¡Bienvenido!',
            'body' => '<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#10b981,#059669);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">¡Bienvenido!</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">¡Hola {user_name}!</p>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Gracias por unirte a {site_name}. Ahora tienes acceso a todos nuestros servicios.</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Haz clic en el botón de abajo para explorar nuestro sitio.</p>
<table width="100%"><tr><td align="center">
<a href="{login_link}" style="display:inline-block;padding:14px 32px;background:#10b981;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Comenzar</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Si tienes alguna pregunta, no dudes en contactarnos.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'fr' => [
            'subject' => '[{site_name}] Bienvenue !',
            'body' => '<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#10b981,#059669);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Bienvenue !</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Bonjour {user_name} !</p>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Merci de rejoindre {site_name}. Vous avez maintenant accès à tous nos services.</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Cliquez sur le bouton ci-dessous pour explorer notre site.</p>
<table width="100%"><tr><td align="center">
<a href="{login_link}" style="display:inline-block;padding:14px 32px;background:#10b981;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Commencer</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Si vous avez des questions, n\'hésitez pas à nous contacter.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'id' => [
            'subject' => '[{site_name}] Selamat Datang!',
            'body' => '<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#10b981,#059669);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Selamat Datang!</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Halo {user_name}!</p>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Terima kasih telah bergabung dengan {site_name}. Anda sekarang memiliki akses ke semua layanan kami.</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Klik tombol di bawah untuk menjelajahi situs kami.</p>
<table width="100%"><tr><td align="center">
<a href="{login_link}" style="display:inline-block;padding:14px 32px;background:#10b981;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Mulai</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Jika ada pertanyaan, jangan ragu untuk menghubungi kami.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'mn' => [
            'subject' => '[{site_name}] Тавтай морилно уу!',
            'body' => '<!DOCTYPE html>
<html lang="mn">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#10b981,#059669);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Тавтай морилно уу!</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Сайн байна уу {user_name}!</p>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">{site_name}-д нэгдсэнд баярлалаа. Та одоо бүх үйлчилгээг ашиглах боломжтой.</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Доорх товчийг дарж сайтыг үзнэ үү.</p>
<table width="100%"><tr><td align="center">
<a href="{login_link}" style="display:inline-block;padding:14px 32px;background:#10b981;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Эхлэх</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Асуулт байвал бидэнтэй холбогдоно уу.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'ru' => [
            'subject' => '[{site_name}] Добро пожаловать!',
            'body' => '<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#10b981,#059669);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Добро пожаловать!</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Здравствуйте, {user_name}!</p>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Спасибо за регистрацию на {site_name}. Теперь вам доступны все наши услуги.</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Нажмите кнопку ниже, чтобы изучить наш сайт.</p>
<table width="100%"><tr><td align="center">
<a href="{login_link}" style="display:inline-block;padding:14px 32px;background:#10b981;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Начать</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Если у вас есть вопросы, свяжитесь с нами.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'tr' => [
            'subject' => '[{site_name}] Hoş Geldiniz!',
            'body' => '<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#10b981,#059669);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Hoş Geldiniz!</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Merhaba {user_name}!</p>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">{site_name}\'a katıldığınız için teşekkürler. Artık tüm hizmetlerimize erişebilirsiniz.</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Sitemizi keşfetmek için aşağıdaki düğmeye tıklayın.</p>
<table width="100%"><tr><td align="center">
<a href="{login_link}" style="display:inline-block;padding:14px 32px;background:#10b981;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Başla</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Sorularınız varsa bizimle iletişime geçmekten çekinmeyin.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ],
        'vi' => [
            'subject' => '[{site_name}] Chào mừng bạn!',
            'body' => '<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 0;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td style="padding:30px 40px;background:linear-gradient(135deg,#10b981,#059669);border-radius:16px 16px 0 0;text-align:center;">
<h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1>
</td></tr>
<tr><td style="padding:40px;background:#fff;">
<h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">Chào mừng!</h2>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Xin chào {user_name}!</p>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Cảm ơn bạn đã tham gia {site_name}. Bạn hiện có quyền truy cập vào tất cả dịch vụ của chúng tôi.</p>
<p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">Nhấp vào nút bên dưới để khám phá trang web.</p>
<table width="100%"><tr><td align="center">
<a href="{login_link}" style="display:inline-block;padding:14px 32px;background:#10b981;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">Bắt đầu</a>
</td></tr></table>
<p style="margin:30px 0 0;color:#71717a;font-size:13px;">Nếu có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi.</p>
</td></tr>
<tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;">
<p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>'
        ]
    ]
];

// 저장된 템플릿 가져오기
$emailTemplateStmt = $pdo->query("SELECT `key`, `value` FROM rzx_settings WHERE `key` LIKE 'email_template_%' OR `key` LIKE 'email_subject_%'");
$savedTemplates = $emailTemplateStmt->fetchAll(\PDO::FETCH_KEY_PAIR);

// Start output buffering for page content
ob_start();
?>

<!-- Sub Navigation Tabs -->
<?php include __DIR__ . '/_settings_nav.php'; ?>

<!-- Mail Settings -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z';
    $headerTitle = __('admin.settings.mail.title');
    $headerDescription = __('admin.settings.mail.description');
    $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../components/settings-header.php';
    ?>

    <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="update_mail_settings">

        <!-- 발신자 정보 -->
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <?= __('admin.settings.mail.sender.title') ?>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="mail_from_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.mail.sender.name') ?></label>
                    <input type="text" name="mail_from_name" id="mail_from_name"
                           value="<?= htmlspecialchars($settings['mail_from_name'] ?? ($settings['site_name'] ?? 'RezlyX')) ?>"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="<?= __('admin.settings.mail.sender.name_placeholder') ?>">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.mail.sender.name_hint') ?></p>
                </div>
                <div>
                    <label for="mail_from_email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.mail.sender.email') ?></label>
                    <input type="email" name="mail_from_email" id="mail_from_email"
                           value="<?= htmlspecialchars($settings['mail_from_email'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="<?= __('admin.settings.mail.sender.email_placeholder') ?>">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.mail.sender.email_hint') ?></p>
                </div>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="mail_apply_all" id="mail_apply_all" value="1"
                       <?= ($settings['mail_apply_all'] ?? '0') === '1' ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                <label for="mail_apply_all" class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <?= __('admin.settings.mail.sender.apply_all') ?>
                </label>
            </div>

            <div>
                <label for="mail_reply_to" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.mail.sender.reply_to') ?></label>
                <input type="email" name="mail_reply_to" id="mail_reply_to"
                       value="<?= htmlspecialchars($settings['mail_reply_to'] ?? '') ?>"
                       class="w-full md:w-1/2 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="<?= __('admin.settings.mail.sender.reply_to_placeholder') ?>">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.mail.sender.reply_to_hint') ?></p>
            </div>
        </div>

        <!-- 발송 방법 -->
        <div class="border-t dark:border-zinc-700 pt-6 space-y-4">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                <?= __('admin.settings.mail.method.title') ?>
            </h3>

            <div>
                <label for="mail_driver" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.mail.method.driver_label') ?></label>
                <?php $currentDriver = $settings['mail_driver'] ?? 'mail'; ?>
                <select name="mail_driver" id="mail_driver"
                        class="w-full md:w-1/2 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        onchange="toggleSmtpSettings()">
                    <option value="mail" <?= $currentDriver === 'mail' ? 'selected' : '' ?>><?= __('admin.settings.mail.method.driver_mail') ?></option>
                    <option value="smtp" <?= $currentDriver === 'smtp' ? 'selected' : '' ?>><?= __('admin.settings.mail.method.driver_smtp') ?></option>
                </select>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.mail.method.driver_hint') ?></p>
            </div>
        </div>

        <!-- SMTP Settings Panel -->
        <div id="smtpSettingsPanel" class="border dark:border-zinc-700 rounded-lg p-4 bg-zinc-50 dark:bg-zinc-900 space-y-4 <?= $currentDriver !== 'smtp' ? 'hidden' : '' ?>">
            <h4 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
                <?= __('admin.settings.mail.smtp.title') ?>
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="smtp_host" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.mail.smtp.host') ?></label>
                    <input type="text" name="smtp_host" id="smtp_host"
                           value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="<?= __('admin.settings.mail.smtp.host_placeholder') ?>">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="smtp_port" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.mail.smtp.port') ?></label>
                        <input type="number" name="smtp_port" id="smtp_port"
                               value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>"
                               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="587">
                    </div>
                    <div>
                        <label for="smtp_encryption" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.mail.smtp.encryption') ?></label>
                        <?php $currentEncryption = $settings['smtp_encryption'] ?? 'tls'; ?>
                        <select name="smtp_encryption" id="smtp_encryption"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="tls" <?= $currentEncryption === 'tls' ? 'selected' : '' ?>><?= __('admin.settings.mail.smtp.encryption_tls') ?></option>
                            <option value="ssl" <?= $currentEncryption === 'ssl' ? 'selected' : '' ?>><?= __('admin.settings.mail.smtp.encryption_ssl') ?></option>
                            <option value="none" <?= $currentEncryption === 'none' ? 'selected' : '' ?>><?= __('admin.settings.mail.smtp.encryption_none') ?></option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="smtp_username" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.mail.smtp.username') ?></label>
                    <input type="text" name="smtp_username" id="smtp_username"
                           value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="<?= __('admin.settings.mail.smtp.username_placeholder') ?>">
                </div>
                <div>
                    <label for="smtp_password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.mail.smtp.password') ?></label>
                    <div class="relative">
                        <input type="password" name="smtp_password" id="smtp_password"
                               value=""
                               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10"
                               placeholder="<?= !empty($settings['smtp_password']) ? '••••••••' : __('admin.settings.mail.smtp.password_placeholder') ?>">
                        <button type="button" onclick="togglePasswordVisibility('smtp_password')"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= !empty($settings['smtp_password']) ? __('admin.settings.mail.smtp.password_change_hint') : __('admin.settings.mail.smtp.password_gmail_hint') ?></p>
                </div>
            </div>

            <!-- SMTP 테스트 버튼 -->
            <div class="pt-2 border-t dark:border-zinc-700">
                <button type="button" onclick="testSmtpConnection()"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 dark:text-blue-400 rounded-lg transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <?= __('admin.settings.mail.smtp.test_connection') ?>
                </button>
                <span id="smtpTestResult" class="ml-2 text-sm"></span>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('admin.buttons.save') ?>
            </button>
        </div>
    </form>
</div>

<!-- Email Templates -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <div class="flex items-center mb-4">
        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('admin.settings.mail.templates.title') ?></h2>
    </div>
    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-6"><?= __('admin.settings.mail.templates.description') ?></p>

    <!-- 템플릿 선택 -->
    <div class="flex flex-wrap gap-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.mail.templates.type_label') ?></label>
            <select id="templateType" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" onchange="loadEmailTemplate()">
                <option value="welcome"><?= __('admin.settings.mail.templates.type_welcome') ?></option>
                <option value="password_reset"><?= __('admin.settings.mail.templates.type_password_reset') ?></option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.mail.templates.language') ?></label>
            <select id="templateLang" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" onchange="loadEmailTemplate()">
                <?php foreach ($supportedLanguages as $langCode):
                    $native = __("admin.languages.{$langCode}.native");
                    $label = __("admin.languages.{$langCode}.label");
                    // fallback: 번역이 없으면 키가 그대로 반환되므로 확인
                    if (strpos($native, 'admin.languages') !== false) $native = $langCode;
                    if (strpos($label, 'admin.languages') !== false) $label = $langCode;
                    $displayName = $native . ' (' . $label . ')';
                ?>
                <option value="<?= htmlspecialchars($langCode) ?>"><?= htmlspecialchars($displayName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <form id="emailTemplateForm" method="POST" class="space-y-4">
        <input type="hidden" name="action" value="update_email_templates">
        <input type="hidden" name="template_type" id="formTemplateType" value="password_reset">
        <input type="hidden" name="template_lang" id="formTemplateLang" value="ko">

        <!-- 사용 가능한 변수 안내 -->
        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <p class="text-xs font-semibold text-blue-800 dark:text-blue-300 mb-1"><?= __('admin.settings.mail.templates.variables_title') ?></p>
            <p class="text-xs text-blue-700 dark:text-blue-400">
                <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">{site_name}</code> <?= __('admin.settings.mail.templates.var_site_name') ?>,
                <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">{user_name}</code> <?= __('admin.settings.mail.templates.var_user_name') ?>,
                <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">{reset_link}</code> <?= __('admin.settings.mail.templates.var_reset_link') ?>,
                <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">{expiry_minutes}</code> <?= __('admin.settings.mail.templates.var_expiry_minutes') ?>
            </p>
        </div>

        <div>
            <label for="email_subject" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.mail.templates.subject') ?></label>
            <input type="text" name="email_subject" id="email_subject"
                   class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500"
                   placeholder="<?= __('admin.settings.mail.templates.subject_placeholder') ?>">
        </div>

        <div>
            <label for="email_body" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.mail.templates.body') ?></label>
            <textarea name="email_body" id="email_body" rows="12"
                      class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                      placeholder="<?= __('admin.settings.mail.templates.body_placeholder') ?>"></textarea>
        </div>

        <!-- 미리보기 및 테스트 -->
        <div class="flex flex-wrap gap-3 pt-4 border-t dark:border-zinc-700">
            <button type="button" onclick="previewEmailTemplate()"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-zinc-700 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600 rounded-lg transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <?= __('admin.settings.mail.templates.preview') ?>
            </button>
            <button type="button" onclick="resetToDefault()"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-orange-600 bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400 dark:hover:bg-orange-900/50 rounded-lg transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <?= __('admin.settings.mail.templates.reset_default') ?>
            </button>
            <div class="flex-1"></div>
            <div class="flex items-center gap-2">
                <input type="email" id="testEmailAddress" placeholder="<?= __('admin.settings.mail.templates.test_email_placeholder') ?>"
                       class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg text-sm w-48">
                <button type="button" onclick="sendTestEmail()"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-green-600 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50 rounded-lg transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <?= __('admin.settings.mail.templates.send_test') ?>
                </button>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('admin.settings.mail.templates.save') ?>
            </button>
        </div>
    </form>
</div>

<!-- 이메일 미리보기 모달 -->
<div id="emailPreviewModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeEmailPreview()"></div>
    <div class="absolute inset-4 md:inset-10 bg-white dark:bg-zinc-800 rounded-xl shadow-xl flex flex-col">
        <div class="flex items-center justify-between p-4 border-b dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('admin.settings.mail.templates.preview_modal_title') ?></h3>
            <button onclick="closeEmailPreview()" class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="flex-1 overflow-auto p-4 bg-zinc-100 dark:bg-zinc-900">
            <iframe id="emailPreviewFrame" class="w-full h-full bg-white rounded-lg shadow" style="min-height:500px;"></iframe>
        </div>
    </div>
</div>

<script>
    // 기본 템플릿 데이터
    const defaultTemplates = <?= json_encode($defaultTemplates, JSON_UNESCAPED_UNICODE) ?>;
    const savedTemplates = <?= json_encode($savedTemplates, JSON_UNESCAPED_UNICODE) ?>;

    // 페이지 로드 시 템플릿 로드
    document.addEventListener('DOMContentLoaded', () => {
        loadEmailTemplate();
    });

    function loadEmailTemplate() {
        const type = document.getElementById('templateType').value;
        const lang = document.getElementById('templateLang').value;

        document.getElementById('formTemplateType').value = type;
        document.getElementById('formTemplateLang').value = lang;

        const subjectKey = `email_subject_${type}_${lang}`;
        const bodyKey = `email_template_${type}_${lang}`;

        // 저장된 템플릿이 있으면 사용, 없으면 기본값
        const subject = savedTemplates[subjectKey] || defaultTemplates[type]?.[lang]?.subject || '';
        const body = savedTemplates[bodyKey] || defaultTemplates[type]?.[lang]?.body || '';

        document.getElementById('email_subject').value = subject;
        document.getElementById('email_body').value = body;

        console.log(`Loaded template: ${type} / ${lang}`);
    }

    function resetToDefault() {
        if (!confirm('<?= __('admin.settings.mail.templates.reset_confirm') ?>')) return;

        const type = document.getElementById('templateType').value;
        const lang = document.getElementById('templateLang').value;

        const subject = defaultTemplates[type]?.[lang]?.subject || '';
        const body = defaultTemplates[type]?.[lang]?.body || '';

        document.getElementById('email_subject').value = subject;
        document.getElementById('email_body').value = body;
    }

    function previewEmailTemplate() {
        const body = document.getElementById('email_body').value;
        const siteName = '<?= htmlspecialchars($settings['site_name'] ?? 'RezlyX') ?>';

        // 변수 치환
        let previewHtml = body
            .replace(/\{site_name\}/g, siteName)
            .replace(/\{user_name\}/g, '홍길동')
            .replace(/\{reset_link\}/g, '#')
            .replace(/\{expiry_minutes\}/g, '60');

        const iframe = document.getElementById('emailPreviewFrame');
        iframe.srcdoc = previewHtml;

        document.getElementById('emailPreviewModal').classList.remove('hidden');
    }

    function closeEmailPreview() {
        document.getElementById('emailPreviewModal').classList.add('hidden');
    }

    function sendTestEmail() {
        const testEmail = document.getElementById('testEmailAddress').value;
        if (!testEmail) {
            alert('<?= __('admin.settings.mail.test.enter_email') ?>');
            return;
        }

        const formData = new FormData(document.getElementById('emailTemplateForm'));
        formData.set('action', 'send_test_email');
        formData.append('test_email', testEmail);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
            } else {
                alert('<?= __('admin.settings.mail.test.sending_failed') ?>: ' + (data.error || ''));
            }
        })
        .catch(error => {
            alert('<?= __('admin.settings.mail.test.request_failed') ?>: ' + error.message);
        });
    }

    // SMTP settings toggle
    function toggleSmtpSettings() {
        const driver = document.getElementById('mail_driver').value;
        const smtpPanel = document.getElementById('smtpSettingsPanel');
        if (driver === 'smtp') {
            smtpPanel.classList.remove('hidden');
        } else {
            smtpPanel.classList.add('hidden');
        }
        console.log('Mail driver changed to:', driver);
    }

    // Password visibility toggle
    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }

    // SMTP connection test
    function testSmtpConnection() {
        const resultEl = document.getElementById('smtpTestResult');
        resultEl.innerHTML = '<span class="text-blue-600 dark:text-blue-400"><?= __('admin.settings.mail.smtp.testing') ?></span>';

        const formData = new FormData();
        formData.append('action', 'test_smtp');
        formData.append('smtp_host', document.getElementById('smtp_host').value);
        formData.append('smtp_port', document.getElementById('smtp_port').value);
        formData.append('smtp_encryption', document.getElementById('smtp_encryption').value);
        formData.append('smtp_username', document.getElementById('smtp_username').value);
        formData.append('smtp_password', document.getElementById('smtp_password').value);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultEl.innerHTML = '<span class="text-green-600 dark:text-green-400"><?= __('admin.settings.mail.smtp.connection_success') ?></span>';
            } else {
                resultEl.innerHTML = '<span class="text-red-600 dark:text-red-400"><?= __('admin.settings.mail.smtp.connection_failed') ?>: ' + (data.error || '') + '</span>';
            }
        })
        .catch(error => {
            resultEl.innerHTML = '<span class="text-red-600 dark:text-red-400"><?= __('admin.settings.mail.test.request_failed') ?>: ' + error.message + '</span>';
        });
    }
</script>

<?php
$pageContent = ob_get_clean();

// Render layout with content
include __DIR__ . '/_layout.php';
