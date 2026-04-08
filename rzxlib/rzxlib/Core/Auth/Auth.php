<?php

declare(strict_types=1);

namespace RzxLib\Core\Auth;

use PDO;
use PDOException;
use RzxLib\Core\Helpers\Encryption;

/**
 * Auth - 인증 헬퍼 클래스
 *
 * @package RzxLib\Core\Auth
 */
class Auth
{
    /**
     * 암호화할 필드 목록
     */
    protected const ENCRYPTED_FIELDS = ['name', 'phone', 'furigana'];

    /**
     * 현재 사용자 데이터 캐시
     */
    protected static ?array $user = null;

    /**
     * PDO 인스턴스
     */
    protected static ?PDO $pdo = null;

    /**
     * 테이블 접두사
     */
    protected static string $prefix = 'rzx_';

    /**
     * PDO 인스턴스 가져오기
     */
    protected static function getPdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO(
                'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
                $_ENV['DB_USERNAME'] ?? 'root',
                $_ENV['DB_PASSWORD'] ?? '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            self::$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
        }
        return self::$pdo;
    }

    /**
     * 로그인 시도
     *
     * @param string $email 이메일
     * @param string $password 비밀번호
     * @param bool $remember 로그인 상태 유지
     * @return array ['success' => bool, 'error' => string|null, 'user' => array|null]
     */
    public static function attempt(string $email, string $password, bool $remember = false): array
    {
        try {
            $pdo = self::getPdo();
            $table = self::$prefix . 'users';

            $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE email = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'error' => 'failed', 'user' => null];
            }

            // 비밀번호가 없는 경우 (소셜 로그인 전용 계정)
            if (empty($user['password'])) {
                return ['success' => false, 'error' => 'social_only', 'user' => null];
            }

            // 비밀번호 검증
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'error' => 'failed', 'user' => null];
            }

            // 사용자 데이터 복호화
            $decryptedUser = self::decryptUserData($user);

            // 세션에 사용자 정보 저장 (복호화된 데이터 사용)
            self::loginUser($decryptedUser, $remember);

            // 마지막 로그인 시간 업데이트
            $updateStmt = $pdo->prepare("UPDATE {$table} SET last_login_at = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);

            return ['success' => true, 'error' => null, 'user' => $decryptedUser];

        } catch (PDOException $e) {
            error_log('Auth::attempt error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'error', 'user' => null];
        }
    }

    /**
     * 사용자 로그인 처리
     */
    protected static function loginUser(array $user, bool $remember = false): void
    {
        // 세션 재생성 (세션 고정 공격 방지)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // 세션에 사용자 정보 저장
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_logged_in_at'] = time();

        // 관리자 여부 체크: rzx_admins 테이블에 존재하면 admin 세션도 설정
        try {
            $pdo = self::getPdo();
            $adminTable = self::$prefix . 'admins';
            $adminStmt = $pdo->prepare("SELECT id, role, name, email, permissions FROM {$adminTable} WHERE email = ? LIMIT 1");
            $adminStmt->execute([$user['email']]);
            $admin = $adminStmt->fetch(\PDO::FETCH_ASSOC);
            if ($admin) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_permissions'] = $admin['permissions'] ?? '[]';
            }
        } catch (\Exception $e) {
            // 관리자 테이블 없어도 로그인은 정상 진행
        }

        // 로그인 상태 유지 (Remember Me)
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + (86400 * 30); // 30일

            // 토큰을 데이터베이스에 저장
            try {
                $pdo = self::getPdo();
                $table = self::$prefix . 'user_remember_tokens';

                // 테이블이 없으면 생성
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS {$table} (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id CHAR(36) NOT NULL,
                        token VARCHAR(64) NOT NULL,
                        expires_at TIMESTAMP NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_token (token),
                        INDEX idx_user (user_id),
                        INDEX idx_expires (expires_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");

                // 기존 토큰 삭제
                $deleteStmt = $pdo->prepare("DELETE FROM {$table} WHERE user_id = ?");
                $deleteStmt->execute([$user['id']]);

                // 새 토큰 저장
                $insertStmt = $pdo->prepare("INSERT INTO {$table} (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))");
                $insertStmt->execute([$user['id'], hash('sha256', $token), $expires]);

                // 쿠키 설정
                setcookie('remember_token', $token, [
                    'expires' => $expires,
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                    'secure' => isset($_SERVER['HTTPS']),
                ]);

            } catch (PDOException $e) {
                error_log('Remember token error: ' . $e->getMessage());
            }
        }

        self::$user = $user;
    }

    /**
     * 로그인 여부 확인
     */
    public static function check(): bool
    {
        // 세션에 사용자 ID가 있는지 확인
        if (isset($_SESSION['user_id'])) {
            return true;
        }

        // Remember Me 토큰 확인
        if (isset($_COOKIE['remember_token'])) {
            return self::loginWithRememberToken($_COOKIE['remember_token']);
        }

        return false;
    }

    /**
     * Remember Me 토큰으로 로그인
     */
    protected static function loginWithRememberToken(string $token): bool
    {
        try {
            $pdo = self::getPdo();
            $tokenTable = self::$prefix . 'user_remember_tokens';
            $userTable = self::$prefix . 'users';

            $hashedToken = hash('sha256', $token);

            $stmt = $pdo->prepare("
                SELECT u.* FROM {$userTable} u
                INNER JOIN {$tokenTable} t ON u.id = t.user_id
                WHERE t.token = ? AND t.expires_at > NOW() AND u.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$hashedToken]);
            $user = $stmt->fetch();

            if ($user) {
                $decryptedUser = self::decryptUserData($user);
                self::loginUser($decryptedUser, true);
                return true;
            }

            // 유효하지 않은 토큰 삭제
            setcookie('remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
            ]);

        } catch (PDOException $e) {
            error_log('Remember token login error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * 현재 로그인한 사용자 가져오기
     *
     * @param bool $decrypt 복호화 여부 (기본 true)
     */
    public static function user(bool $decrypt = true): ?array
    {
        if (!self::check()) {
            return null;
        }

        if (self::$user !== null) {
            return $decrypt ? self::decryptUserData(self::$user) : self::$user;
        }

        try {
            $pdo = self::getPdo();
            $table = self::$prefix . 'users';

            $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            self::$user = $stmt->fetch() ?: null;

            return $decrypt && self::$user ? self::decryptUserData(self::$user) : self::$user;

        } catch (PDOException $e) {
            error_log('Auth::user error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 사용자 데이터 복호화
     */
    protected static function decryptUserData(array $user): array
    {
        return Encryption::decryptFields($user, self::ENCRYPTED_FIELDS);
    }

    /**
     * 사용자 데이터 암호화
     */
    protected static function encryptUserData(array $data): array
    {
        return Encryption::encryptFields($data, self::ENCRYPTED_FIELDS);
    }

    /**
     * 현재 사용자 ID 가져오기
     */
    public static function id(): ?string
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * 로그아웃
     */
    public static function logout(): void
    {
        // Remember Me 토큰 삭제
        if (isset($_SESSION['user_id'])) {
            try {
                $pdo = self::getPdo();
                $table = self::$prefix . 'user_remember_tokens';
                $stmt = $pdo->prepare("DELETE FROM {$table} WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
            } catch (PDOException $e) {
                error_log('Logout remember token error: ' . $e->getMessage());
            }
        }

        // 쿠키 삭제
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
            ]);
        }

        // 세션 데이터 삭제
        unset($_SESSION['user_id']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_logged_in_at']);

        // 캐시 초기화
        self::$user = null;
    }

    /**
     * 회원 탈퇴 (소프트 삭제 + 개인정보 익명화)
     *
     * 처리 방식:
     * - 개인정보(이름, 이메일, 전화번호 등) → 즉시 익명화
     * - 매출/결제/예약 기록 → user_id 연결 유지 (세법상 보관 의무: 한국 5년, 일본 7년)
     * - status → 'withdrawn', deleted_at 기록
     * - 보관 기간 경과 후 배치 처리로 완전 삭제
     */
    public static function deleteAccount(string $userId, string $password, string $reason = ''): array
    {
        try {
            $pdo = self::getPdo();
            $table = self::$prefix . 'users';

            // 사용자 조회
            $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            // 비밀번호 확인 (소셜 로그인 전용 계정은 비밀번호 없을 수 있음)
            if (!empty($user['password']) && !password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'wrong_password'];
            }

            $now = date('Y-m-d H:i:s');
            $anonymizedId = substr(hash('sha256', $user['email'] . $now), 0, 12);

            // 탈퇴 로그 (익명화 전 기록)
            error_log("Account withdrawal - User ID: {$userId}, Email: {$user['email']}, Reason: {$reason}, Date: {$now}");

            // Remember Me 토큰 삭제
            try {
                $tokenTable = self::$prefix . 'user_remember_tokens';
                $stmt = $pdo->prepare("DELETE FROM {$tokenTable} WHERE user_id = ?");
                $stmt->execute([$userId]);
            } catch (PDOException $e) {
                // 테이블 없을 수 있음
            }

            // 개인정보 익명화 + 소프트 삭제
            $stmt = $pdo->prepare("UPDATE {$table} SET
                email = ?,
                password = NULL,
                name = ?,
                furigana = NULL,
                phone = NULL,
                profile_image = NULL,
                birth_date = NULL,
                gender = NULL,
                company = NULL,
                blog = NULL,
                privacy_settings = NULL,
                withdraw_reason = ?,
                status = 'withdrawn',
                deleted_at = ?
                WHERE id = ?");
            $stmt->execute([
                "withdrawn_{$anonymizedId}@deleted.local",
                "탈퇴회원_{$anonymizedId}",
                $reason ?: null,
                $now,
                $userId,
            ]);

            // 예약 기록의 사용자 정보 익명화 (기록 자체는 유지)
            try {
                $resTable = self::$prefix . 'reservations';
                $stmt = $pdo->prepare("UPDATE {$resTable} SET
                    customer_name = ?, customer_email = NULL, customer_phone = NULL
                    WHERE user_id = ?");
                $stmt->execute(["탈퇴회원_{$anonymizedId}", $userId]);
            } catch (PDOException $e) {
                // reservations 테이블 없을 수 있음
            }

            // 로그아웃 처리
            self::logout();

            return ['success' => true];
        } catch (PDOException $e) {
            error_log('Auth::deleteAccount error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'error'];
        }
    }

    /**
     * 게스트(비로그인) 여부 확인
     */
    public static function guest(): bool
    {
        return !self::check();
    }

    /**
     * UUID v4 생성
     */
    public static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * 비밀번호 해시 생성
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    /**
     * 새 사용자 등록
     *
     * @param array $data ['name', 'email', 'password', 'phone' => optional, 'furigana' => optional]
     * @return array ['success' => bool, 'error' => string|null, 'user_id' => string|null]
     */
    public static function register(array $data): array
    {
        try {
            $pdo = self::getPdo();
            $table = self::$prefix . 'users';

            // 이메일 중복 확인
            $checkStmt = $pdo->prepare("SELECT id FROM {$table} WHERE email = ? LIMIT 1");
            $checkStmt->execute([$data['email']]);
            if ($checkStmt->fetch()) {
                return ['success' => false, 'error' => 'email_exists', 'user_id' => null];
            }

            // UUID 생성
            $userId = self::generateUuid();

            // 비밀번호 해시
            $hashedPassword = self::hashPassword($data['password']);

            // 민감한 정보 암호화
            $encryptedName = Encryption::encrypt($data['name']);
            $encryptedPhone = isset($data['phone']) ? Encryption::encrypt($data['phone']) : null;
            $encryptedFurigana = isset($data['furigana']) ? Encryption::encrypt($data['furigana']) : null;

            // 사용자 생성
            $insertStmt = $pdo->prepare("
                INSERT INTO {$table} (id, name, email, password, phone, furigana, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
            ");
            $insertStmt->execute([
                $userId,
                $encryptedName,
                $data['email'],
                $hashedPassword,
                $encryptedPhone,
                $encryptedFurigana,
            ]);

            return ['success' => true, 'error' => null, 'user_id' => $userId];

        } catch (PDOException $e) {
            error_log('Auth::register error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'error', 'user_id' => null];
        }
    }

    /**
     * ID로 사용자 조회 (복호화된 데이터 반환)
     *
     * @param string $userId 사용자 ID
     * @param bool $decrypt 복호화 여부
     * @return array|null
     */
    public static function findById(string $userId, bool $decrypt = true): ?array
    {
        try {
            $pdo = self::getPdo();
            $table = self::$prefix . 'users';

            $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                return null;
            }

            return $decrypt ? self::decryptUserData($user) : $user;

        } catch (PDOException $e) {
            error_log('Auth::findById error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 이메일로 사용자 조회 (복호화된 데이터 반환)
     *
     * @param string $email 이메일
     * @param bool $decrypt 복호화 여부
     * @return array|null
     */
    public static function findByEmail(string $email, bool $decrypt = true): ?array
    {
        try {
            $pdo = self::getPdo();
            $table = self::$prefix . 'users';

            $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                return null;
            }

            return $decrypt ? self::decryptUserData($user) : $user;

        } catch (PDOException $e) {
            error_log('Auth::findByEmail error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 비밀번호 변경
     *
     * @param string $userId 사용자 ID
     * @param string $newPassword 새 비밀번호
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function changePassword(string $userId, string $newPassword): array
    {
        try {
            $pdo = self::getPdo();
            $table = self::$prefix . 'users';

            // 비밀번호 해시
            $hashedPassword = self::hashPassword($newPassword);

            // 비밀번호 업데이트
            $stmt = $pdo->prepare("UPDATE {$table} SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            if ($stmt->rowCount() > 0) {
                // 캐시 초기화
                self::$user = null;
                return ['success' => true, 'error' => null];
            }

            return ['success' => false, 'error' => 'not_found'];

        } catch (PDOException $e) {
            error_log('Auth::changePassword error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'error'];
        }
    }

    /**
     * 관리자 메일 설정 가져오기
     *
     * @return array 메일 설정
     */
    private static function getMailSettings(): array
    {
        try {
            $pdo = self::getPdo();
            $stmt = $pdo->query("SELECT `key`, `value` FROM rzx_settings WHERE `key` LIKE 'mail_%' OR `key` LIKE 'smtp_%' OR `key` = 'site_name'");
            $rows = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

            return [
                'from_name' => $rows['mail_from_name'] ?? ($rows['site_name'] ?? 'RezlyX'),
                'from_email' => $rows['mail_from_email'] ?? '',
                'reply_to' => $rows['mail_reply_to'] ?? '',
                'driver' => $rows['mail_driver'] ?? 'mail',
                'smtp_host' => $rows['smtp_host'] ?? '',
                'smtp_port' => (int) ($rows['smtp_port'] ?? 587),
                'smtp_encryption' => $rows['smtp_encryption'] ?? 'tls',
                'smtp_username' => $rows['smtp_username'] ?? '',
                'smtp_password' => $rows['smtp_password'] ?? '',
            ];
        } catch (\PDOException $e) {
            error_log('[Auth] getMailSettings error: ' . $e->getMessage());
            return [
                'from_name' => 'RezlyX',
                'from_email' => '',
                'reply_to' => '',
                'driver' => 'mail',
                'smtp_host' => '',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'smtp_username' => '',
                'smtp_password' => '',
            ];
        }
    }

    /**
     * SMTP로 이메일 발송
     *
     * @param string $to 수신자
     * @param string $subject 제목
     * @param string $body HTML 본문
     * @param array $mailSettings 메일 설정
     * @return bool 성공 여부
     */
    private static function sendSmtpMail(string $to, string $subject, string $body, array $mailSettings): bool
    {
        $host = $mailSettings['smtp_host'];
        $port = $mailSettings['smtp_port'];
        $encryption = $mailSettings['smtp_encryption'];
        $username = $mailSettings['smtp_username'];
        $password = $mailSettings['smtp_password'];
        $fromName = $mailSettings['from_name'];
        $fromEmail = $mailSettings['from_email'] ?: $username;
        $replyTo = $mailSettings['reply_to'] ?: $fromEmail;

        if (empty($host) || empty($username)) {
            error_log('[Auth] SMTP settings incomplete');
            return false;
        }

        try {
            $prefix = ($encryption === 'ssl') ? 'ssl://' : '';
            $fp = @fsockopen($prefix . $host, $port, $errno, $errstr, 30);

            if (!$fp) {
                error_log("[Auth] SMTP connection failed: $errstr ($errno)");
                return false;
            }

            // 서버 응답 읽기
            $response = fgets($fp, 515);
            if (substr($response, 0, 3) !== '220') {
                fclose($fp);
                return false;
            }

            // EHLO
            fputs($fp, "EHLO localhost\r\n");
            while ($line = fgets($fp, 515)) {
                if (substr($line, 3, 1) === ' ') break;
            }

            // STARTTLS (TLS 암호화)
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
                error_log('[Auth] SMTP authentication failed: ' . trim($authResponse));
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

            // 메일 헤더 및 본문
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
            $headers .= "Reply-To: {$replyTo}\r\n";
            $headers .= "To: {$to}\r\n";
            $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $headers .= "X-Mailer: RezlyX/1.0\r\n";

            fputs($fp, $headers . "\r\n" . $body . "\r\n.\r\n");
            $dataResponse = fgets($fp, 515);

            // QUIT
            fputs($fp, "QUIT\r\n");
            fclose($fp);

            if (substr($dataResponse, 0, 3) === '250') {
                error_log("[Auth] SMTP mail sent successfully to: {$to}");
                return true;
            }

            error_log('[Auth] SMTP send failed: ' . trim($dataResponse));
            return false;

        } catch (\Throwable $e) {
            error_log('[Auth] SMTP error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 비밀번호 재설정 이메일 발송
     *
     * @param string $email 이메일
     * @param string $locale 언어 (ko, en, ja)
     * @return array ['success' => bool, 'error' => string|null, 'debug_link' => string|null]
     */
    public static function sendPasswordResetEmail(string $email, string $locale = 'ko'): array
    {
        // 토큰 생성
        $result = self::createPasswordResetToken($email);

        // 사용자가 없어도 보안상 성공으로 처리 (이메일 존재 여부 노출 방지)
        if (!$result['success'] || !$result['token']) {
            return ['success' => true, 'error' => null, 'debug_link' => null];
        }

        $token = $result['token'];
        $user = $result['user'];
        $userName = $user['name'] ?? 'User';

        // 재설정 링크 생성
        $baseUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
        $resetLink = $baseUrl . '/reset-password?token=' . $token;

        // 관리자 메일 설정 가져오기
        $mailSettings = self::getMailSettings();
        $siteName = $mailSettings['from_name'] ?: ($_ENV['APP_NAME'] ?? 'RezlyX');

        // 개발 환경 체크
        $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
        $isLocal = ($_ENV['APP_ENV'] ?? 'production') === 'local';
        $debugLink = ($isDebug && $isLocal) ? $resetLink : null;

        // MAIL_DRIVER=log인 경우 메일 발송 없이 링크만 반환
        $mailDriver = $mailSettings['driver'] ?? ($_ENV['MAIL_DRIVER'] ?? 'log');
        if ($mailDriver === 'log') {
            error_log("[Auth] LOG MODE - Password reset link for {$email}: {$resetLink}");
            return [
                'success' => true,
                'error' => null,
                'debug_link' => $debugLink
            ];
        }

        // 저장된 이메일 템플릿 가져오기
        $template = self::getEmailTemplate('password_reset', $locale);
        $subject = $template['subject'];
        $htmlBody = $template['body'];

        // 변수 치환
        $replacements = [
            '{site_name}' => $siteName,
            '{user_name}' => $userName,
            '{reset_link}' => $resetLink,
            '{expiry_minutes}' => '60',
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
        $htmlBody = str_replace(array_keys($replacements), array_values($replacements), $htmlBody);

        try {
            $sent = false;

            // 발송 방법에 따라 분기
            if ($mailSettings['driver'] === 'smtp' && !empty($mailSettings['smtp_host'])) {
                // SMTP 발송
                $sent = self::sendSmtpMail($email, $subject, $htmlBody, $mailSettings);
            } else {
                // PHP mail() 발송
                $fromName = $mailSettings['from_name'];
                $fromEmail = $mailSettings['from_email'] ?: ('noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                $replyTo = $mailSettings['reply_to'] ?: $fromEmail;

                $headers = [
                    'MIME-Version: 1.0',
                    'Content-type: text/html; charset=UTF-8',
                    'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromEmail . '>',
                    'Reply-To: ' . $replyTo,
                    'X-Mailer: RezlyX/1.0'
                ];

                $sent = @mail($email, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, implode("\r\n", $headers));
            }

            if (!$sent) {
                error_log("[Auth] Failed to send password reset email to: {$email}");
            } else {
                error_log("[Auth] Password reset email sent to: {$email}");
            }

            return ['success' => true, 'error' => null, 'debug_link' => $debugLink];

        } catch (\Exception $e) {
            error_log("[Auth] Email send error: " . $e->getMessage());
            return ['success' => true, 'error' => null, 'debug_link' => $debugLink];
        }
    }

    /**
     * 이메일 템플릿 가져오기
     *
     * @param string $type 템플릿 유형 (password_reset 등)
     * @param string $locale 언어 (ko, en, ja)
     * @return array ['subject' => string, 'body' => string]
     */
    private static function getEmailTemplate(string $type, string $locale): array
    {
        // 기본 템플릿
        $defaults = [
            'password_reset' => [
                'ko' => [
                    'subject' => '[{site_name}] 비밀번호 재설정',
                    'body' => self::getDefaultPasswordResetHtml('ko'),
                ],
                'en' => [
                    'subject' => '[{site_name}] Password Reset',
                    'body' => self::getDefaultPasswordResetHtml('en'),
                ],
                'ja' => [
                    'subject' => '[{site_name}] パスワードリセット',
                    'body' => self::getDefaultPasswordResetHtml('ja'),
                ],
            ],
        ];

        try {
            $pdo = self::getPdo();
            $subjectKey = "email_subject_{$type}_{$locale}";
            $bodyKey = "email_template_{$type}_{$locale}";

            $stmt = $pdo->prepare("SELECT `key`, `value` FROM rzx_settings WHERE `key` IN (?, ?)");
            $stmt->execute([$subjectKey, $bodyKey]);
            $templates = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

            $defaultSubject = $defaults[$type][$locale]['subject'] ?? $defaults[$type]['ko']['subject'] ?? '';
            $defaultBody = $defaults[$type][$locale]['body'] ?? $defaults[$type]['ko']['body'] ?? '';

            return [
                'subject' => $templates[$subjectKey] ?? $defaultSubject,
                'body' => $templates[$bodyKey] ?? $defaultBody,
            ];
        } catch (\PDOException $e) {
            error_log('[Auth] getEmailTemplate error: ' . $e->getMessage());
            return $defaults[$type][$locale] ?? $defaults[$type]['ko'] ?? ['subject' => '', 'body' => ''];
        }
    }

    /**
     * 기본 비밀번호 재설정 이메일 HTML
     */
    private static function getDefaultPasswordResetHtml(string $locale): string
    {
        $texts = [
            'ko' => ['title' => '비밀번호 재설정', 'greeting' => '안녕하세요, {user_name}님.', 'desc' => '비밀번호 재설정을 요청하셨습니다. 아래 버튼을 클릭하여 새 비밀번호를 설정해주세요.', 'button' => '비밀번호 재설정', 'expiry' => '이 링크는 {expiry_minutes}분 후에 만료됩니다.', 'ignore' => '비밀번호 재설정을 요청하지 않으셨다면 이 이메일을 무시해주세요.'],
            'en' => ['title' => 'Password Reset', 'greeting' => 'Hello, {user_name}.', 'desc' => 'You requested a password reset. Click the button below to set a new password.', 'button' => 'Reset Password', 'expiry' => 'This link will expire in {expiry_minutes} minutes.', 'ignore' => 'If you did not request a password reset, please ignore this email.'],
            'ja' => ['title' => 'パスワードリセット', 'greeting' => '{user_name}様、こんにちは。', 'desc' => 'パスワードリセットのリクエストがありました。下のボタンをクリックして新しいパスワードを設定してください。', 'button' => 'パスワードリセット', 'expiry' => 'このリンクは{expiry_minutes}分後に期限切れになります。', 'ignore' => 'パスワードリセットをリクエストしていない場合は、このメールを無視してください。'],
        ];

        $t = $texts[$locale] ?? $texts['ko'];

        return '<!DOCTYPE html><html lang="' . $locale . '"><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;font-family:sans-serif;background:#f4f4f5;"><table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:40px 0;"><table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;"><tr><td style="padding:30px 40px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:16px 16px 0 0;text-align:center;"><h1 style="margin:0;color:#fff;font-size:24px;">{site_name}</h1></td></tr><tr><td style="padding:40px;background:#fff;"><h2 style="margin:0 0 20px;color:#18181b;font-size:20px;">' . $t['title'] . '</h2><p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">' . $t['greeting'] . '</p><p style="margin:0 0 30px;color:#52525b;font-size:15px;line-height:1.6;">' . $t['desc'] . '</p><table width="100%"><tr><td align="center"><a href="{reset_link}" style="display:inline-block;padding:14px 32px;background:#3b82f6;color:#fff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">' . $t['button'] . '</a></td></tr></table><p style="margin:30px 0 0;color:#71717a;font-size:13px;">' . $t['expiry'] . '</p><p style="margin:10px 0 0;color:#71717a;font-size:13px;">' . $t['ignore'] . '</p></td></tr><tr><td style="padding:30px 40px;background:#f4f4f5;border-radius:0 0 16px 16px;text-align:center;"><p style="margin:0;color:#a1a1aa;font-size:12px;">&copy; {site_name}. All rights reserved.</p></td></tr></table></td></tr></table></body></html>';
    }

    /**
     * 비밀번호 재설정 이메일 HTML 생성 (레거시 - 하위 호환)
     */
    private static function buildPasswordResetEmailHtml(string $userName, string $resetLink, string $siteName): string
    {
        $expiryMinutes = 60;

        return <<<HTML
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f4f4f5;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" style="width: 100%; max-width: 600px; border-collapse: collapse;">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 30px 40px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 16px 16px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700;">{$siteName}</h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px; background-color: #ffffff;">
                            <h2 style="margin: 0 0 20px 0; color: #18181b; font-size: 20px; font-weight: 600;">비밀번호 재설정</h2>
                            <p style="margin: 0 0 20px 0; color: #52525b; font-size: 15px; line-height: 1.6;">안녕하세요, {$userName}님.</p>
                            <p style="margin: 0 0 30px 0; color: #52525b; font-size: 15px; line-height: 1.6;">비밀번호 재설정을 요청하셨습니다. 아래 버튼을 클릭하여 새 비밀번호를 설정해주세요.</p>

                            <!-- Button -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td align="center">
                                        <a href="{$resetLink}" style="display: inline-block; padding: 14px 32px; background-color: #3b82f6; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 600; border-radius: 8px;">비밀번호 재설정</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 30px 0 0 0; color: #71717a; font-size: 13px; line-height: 1.6;">이 링크는 {$expiryMinutes}분 후에 만료됩니다.</p>
                            <p style="margin: 10px 0 0 0; color: #71717a; font-size: 13px; line-height: 1.6;">비밀번호 재설정을 요청하지 않으셨다면 이 이메일을 무시해주세요.</p>

                            <!-- Link fallback -->
                            <div style="margin-top: 30px; padding: 20px; background-color: #f4f4f5; border-radius: 8px;">
                                <p style="margin: 0 0 10px 0; color: #71717a; font-size: 12px;">버튼이 작동하지 않으면 아래 링크를 복사하여 브라우저에 붙여넣으세요:</p>
                                <p style="margin: 0; color: #3b82f6; font-size: 12px; word-break: break-all;">{$resetLink}</p>
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 30px 40px; background-color: #f4f4f5; border-radius: 0 0 16px 16px;">
                            <p style="margin: 0; color: #a1a1aa; font-size: 12px;">&copy; {$siteName}. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * 비밀번호 재설정 토큰 생성
     *
     * @param string $email 이메일
     * @return array ['success' => bool, 'token' => string|null, 'user' => array|null]
     */
    public static function createPasswordResetToken(string $email): array
    {
        try {
            $pdo = self::getPdo();
            $userTable = self::$prefix . 'users';
            $resetTable = self::$prefix . 'password_resets';

            // 디버그: 테이블명과 이메일 로깅
            error_log("[Auth] createPasswordResetToken - Table: {$userTable}, Email: {$email}");

            // 사용자 확인 (대소문자 무시)
            $stmt = $pdo->prepare("SELECT id, email, name FROM {$userTable} WHERE LOWER(email) = LOWER(?) AND status = 'active' LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // 디버그: 사용자 검색 결과
            error_log("[Auth] createPasswordResetToken - User found: " . ($user ? 'Yes (ID: ' . $user['id'] . ')' : 'No'));

            if (!$user) {
                return ['success' => false, 'token' => null, 'user' => null];
            }

            // 토큰 생성
            $token = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $token);

            // 기존 토큰 삭제 후 새 토큰 저장
            $deleteStmt = $pdo->prepare("DELETE FROM {$resetTable} WHERE email = ?");
            $deleteStmt->execute([$email]);

            // MySQL의 NOW() 기준으로 만료 시간 계산 (시간대 문제 방지)
            $insertStmt = $pdo->prepare("INSERT INTO {$resetTable} (email, token, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())");
            $insertStmt->execute([$email, $hashedToken]);

            return ['success' => true, 'token' => $token, 'user' => self::decryptUserData($user)];

        } catch (PDOException $e) {
            error_log('Auth::createPasswordResetToken error: ' . $e->getMessage());
            return ['success' => false, 'token' => null, 'user' => null];
        }
    }

    /**
     * 비밀번호 재설정 토큰 검증
     *
     * @param string $token 토큰
     * @return array|null 유효하면 사용자 정보, 아니면 null
     */
    public static function verifyPasswordResetToken(string $token): ?array
    {
        try {
            $pdo = self::getPdo();
            $userTable = self::$prefix . 'users';
            $resetTable = self::$prefix . 'password_resets';

            $hashedToken = hash('sha256', $token);

            // 토큰으로 재설정 레코드 찾기
            $stmt = $pdo->prepare("SELECT * FROM {$resetTable} WHERE token = ? AND expires_at > NOW() LIMIT 1");
            $stmt->execute([$hashedToken]);
            $reset = $stmt->fetch();

            if (!$reset) {
                return null;
            }

            // 사용자 찾기
            $userStmt = $pdo->prepare("SELECT * FROM {$userTable} WHERE email = ? AND status = 'active' LIMIT 1");
            $userStmt->execute([$reset['email']]);
            $user = $userStmt->fetch();

            if (!$user) {
                return null;
            }

            return self::decryptUserData($user);

        } catch (PDOException $e) {
            error_log('Auth::verifyPasswordResetToken error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 비밀번호 재설정 (토큰으로)
     *
     * @param string $token 토큰
     * @param string $newPassword 새 비밀번호
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function resetPassword(string $token, string $newPassword): array
    {
        try {
            $pdo = self::getPdo();
            $userTable = self::$prefix . 'users';
            $resetTable = self::$prefix . 'password_resets';

            $hashedToken = hash('sha256', $token);

            // 토큰 검증
            $stmt = $pdo->prepare("SELECT * FROM {$resetTable} WHERE token = ? AND expires_at > NOW() LIMIT 1");
            $stmt->execute([$hashedToken]);
            $reset = $stmt->fetch();

            if (!$reset) {
                return ['success' => false, 'error' => 'invalid_token'];
            }

            // 비밀번호 해시
            $hashedPassword = self::hashPassword($newPassword);

            // 비밀번호 업데이트
            $updateStmt = $pdo->prepare("UPDATE {$userTable} SET password = ?, updated_at = NOW() WHERE email = ?");
            $updateStmt->execute([$hashedPassword, $reset['email']]);

            // 토큰 삭제
            $deleteStmt = $pdo->prepare("DELETE FROM {$resetTable} WHERE email = ?");
            $deleteStmt->execute([$reset['email']]);

            return ['success' => true, 'error' => null];

        } catch (PDOException $e) {
            error_log('Auth::resetPassword error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'error'];
        }
    }

    /**
     * 비밀번호 재설정 토큰 삭제
     *
     * @param string $email 이메일
     */
    public static function deletePasswordResetToken(string $email): void
    {
        try {
            $pdo = self::getPdo();
            $table = self::$prefix . 'password_resets';

            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE email = ?");
            $stmt->execute([$email]);

        } catch (PDOException $e) {
            error_log('Auth::deletePasswordResetToken error: ' . $e->getMessage());
        }
    }

    /**
     * 프로필 업데이트
     *
     * @param string $userId 사용자 ID
     * @param array $data ['name', 'phone', 'profile_image', 'birth_date', 'gender', 'company', 'blog' 등]
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function updateProfile(string $userId, array $data): array
    {
        try {
            $pdo = self::getPdo();
            $table = self::$prefix . 'users';

            $sets = [];
            $params = [];

            // 암호화 필드
            if (isset($data['name'])) {
                $sets[] = 'name = ?';
                $params[] = Encryption::encrypt($data['name']);
            }
            if (array_key_exists('phone', $data)) {
                $sets[] = 'phone = ?';
                $params[] = isset($data['phone']) && $data['phone'] !== ''
                    ? Encryption::encrypt($data['phone']) : null;
            }

            // 프로필 이미지
            if (array_key_exists('profile_image', $data)) {
                $sets[] = 'profile_image = ?';
                $params[] = $data['profile_image'];
            }
            if (array_key_exists('profile_photo', $data) && !array_key_exists('profile_image', $data)) {
                $sets[] = 'profile_image = ?';
                $params[] = $data['profile_photo'];
            }

            // 일반 텍스트 필드 (암호화 불필요)
            $plainFields = ['birth_date', 'gender', 'company', 'blog', 'privacy_settings'];
            foreach ($plainFields as $field) {
                if (array_key_exists($field, $data)) {
                    $sets[] = "{$field} = ?";
                    $params[] = (isset($data[$field]) && $data[$field] !== '') ? $data[$field] : null;
                }
            }

            if (empty($sets)) {
                return ['success' => true, 'error' => null];
            }

            $sets[] = 'updated_at = NOW()';
            $params[] = $userId;

            // 프로필 업데이트
            $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() >= 0) {
                // 캐시 초기화
                self::$user = null;

                // 세션 이름 업데이트
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $userId) {
                    $_SESSION['user_name'] = $data['name'];
                }

                return ['success' => true, 'error' => null];
            }

            return ['success' => false, 'error' => 'not_found'];

        } catch (PDOException $e) {
            error_log('Auth::updateProfile error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'error'];
        }
    }
}
