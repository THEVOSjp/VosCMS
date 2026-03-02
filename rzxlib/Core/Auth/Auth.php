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
     * 프로필 업데이트
     *
     * @param string $userId 사용자 ID
     * @param array $data ['name' => string, 'phone' => string|null]
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function updateProfile(string $userId, array $data): array
    {
        try {
            $pdo = self::getPdo();
            $table = self::$prefix . 'users';

            // 민감한 정보 암호화
            $encryptedName = Encryption::encrypt($data['name']);
            $encryptedPhone = isset($data['phone']) && $data['phone'] !== ''
                ? Encryption::encrypt($data['phone'])
                : null;

            // 프로필 업데이트
            $stmt = $pdo->prepare("UPDATE {$table} SET name = ?, phone = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$encryptedName, $encryptedPhone, $userId]);

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
