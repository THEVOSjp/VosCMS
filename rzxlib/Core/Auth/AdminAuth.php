<?php
/**
 * VosCMS v2.1 — 관리자 인증 및 권한 시스템 (통합)
 *
 * rzx_users 테이블에서 직접 인증 (rzx_admins 제거)
 * role: member / staff / manager / supervisor
 * - supervisor: 전체 권한 (삭제 불가)
 * - manager: 대부분 권한 (설정 제외 가능)
 * - staff: 제한적 권한 (배정된 권한만)
 * - member: 관리자 접근 불가
 */

namespace RzxLib\Core\Auth;

class AdminAuth
{
    private static ?\PDO $pdo = null;

    /** 관리자 접근 가능한 role 목록 */
    private const ADMIN_ROLES = ['staff', 'manager', 'supervisor'];

    public static function init(\PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    // ===== 로그인/로그아웃 =====

    /**
     * 관리자 로그인 시도 (rzx_users에서 직접 인증)
     * @return array|string 성공 시 user 배열, 실패 시 에러 메시지
     */
    public static function attempt(string $email, string $password): array|string
    {
        $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

        // users 테이블의 is_active/status 자동 감지
        $hasStatus = false;
        try { $cols = self::$pdo->query("SHOW COLUMNS FROM {$prefix}users LIKE 'status'")->fetchAll(); $hasStatus = count($cols) > 0; } catch (\PDOException $e) {}

        $activeCondition = $hasStatus ? "u.status = 'active'" : "u.is_active = 1";

        $stmt = self::$pdo->prepare("SELECT u.* FROM {$prefix}users u WHERE u.email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) return 'invalid_credentials';
        if (!password_verify($password, $user['password'])) return 'invalid_credentials';

        // 활성 상태 확인
        $isActive = $hasStatus ? (($user['status'] ?? 'active') === 'active') : (($user['is_active'] ?? 1) == 1);
        if (!$isActive) return 'account_inactive';

        // role 확인: member는 관리자 접근 불가
        $role = $user['role'] ?? 'member';

        // 하위 호환: 기존 'admin' role → 'supervisor', 'user' role → 'member'
        if ($role === 'admin') $role = 'supervisor';
        if ($role === 'user') $role = 'member';

        if (!in_array($role, self::ADMIN_ROLES)) return 'not_admin';

        // 세션 설정 (admin_id = user id로 통합)
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_role'] = $role;
        $_SESSION['admin_name'] = $user['name'] ?? '';
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_permissions'] = $user['permissions'] ?? '[]';
        $_SESSION['admin_logged_in_at'] = date('c');

        // 프론트엔드 세션도 동시 설정 (로그인 통합)
        $_SESSION['user_id'] = $user['id'];

        // last_login_at 업데이트
        try {
            self::$pdo->prepare("UPDATE {$prefix}users SET last_login_at = NOW() WHERE id = ?")
                ->execute([$user['id']]);
        } catch (\PDOException $e) {}

        return $user;
    }

    /**
     * 관리자 로그아웃
     */
    public static function logout(): void
    {
        unset(
            $_SESSION['admin_id'],
            $_SESSION['admin_role'],
            $_SESSION['admin_name'],
            $_SESSION['admin_email'],
            $_SESSION['admin_permissions'],
            $_SESSION['admin_logged_in_at']
        );
        // 프론트엔드 세션도 해제 (통합 로그아웃)
        unset($_SESSION['user_id']);
    }

    // ===== 상태 확인 =====

    public static function check(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    public static function current(): ?array
    {
        if (!self::check()) return null;
        return [
            'id' => $_SESSION['admin_id'],
            'role' => $_SESSION['admin_role'],
            'name' => $_SESSION['admin_name'],
            'email' => $_SESSION['admin_email'],
            'permissions' => $_SESSION['admin_permissions'],
        ];
    }

    /**
     * 현재 관리자가 supervisor인지 확인 (구 master)
     */
    public static function isMaster(): bool
    {
        $role = $_SESSION['admin_role'] ?? '';
        return $role === 'supervisor' || $role === 'master';
    }

    // ===== 권한 확인 =====

    public static function can(string $permission): bool
    {
        if (!self::check()) return false;
        if (self::isMaster()) return true;

        $permsJson = $_SESSION['admin_permissions'] ?? '[]';
        $perms = json_decode($permsJson, true) ?: [];

        foreach ($perms as $p) {
            if ($p === $permission) return true;
            if (str_starts_with($permission, $p . '.')) return true;
        }
        return false;
    }

    // ===== 슈퍼바이저 보호 =====

    public static function isSupervisorUser(string $userId): bool
    {
        $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
        $stmt = self::$pdo->prepare("SELECT id FROM {$prefix}users WHERE id = ? AND role IN ('supervisor','admin','master')");
        $stmt->execute([$userId]);
        return (bool)$stmt->fetch();
    }

    public static function isSupervisorStaff(int $staffId): bool
    {
        $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
        // staff 테이블에서 user_id를 찾고, 해당 user가 supervisor인지 확인
        try {
            $stmt = self::$pdo->prepare("SELECT u.id FROM {$prefix}staff s JOIN {$prefix}users u ON s.user_id = u.id WHERE s.id = ? AND u.role IN ('supervisor','admin','master')");
            $stmt->execute([$staffId]);
            return (bool)$stmt->fetch();
        } catch (\PDOException $e) {
            return false;
        }
    }

    public static function isSupervisorAdmin(string $adminId): bool
    {
        // v2.1: admin_id = user_id이므로 동일
        return self::isSupervisorUser($adminId);
    }

    public static function masterCount(): int
    {
        $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
        return (int)self::$pdo->query("SELECT COUNT(*) FROM {$prefix}users WHERE role IN ('supervisor','admin','master')")->fetchColumn();
    }

    // ===== 라우트 → 권한 매핑 =====

    public static function getRequiredPermission(string $adminRoute): ?string
    {
        if ($adminRoute === '' || $adminRoute === '/') return null;

        $map = [
            'kiosk'              => 'reservations',
            'reservations'       => 'reservations',
            'counter'            => 'counter',
            'services'           => 'services',
            'staff/schedule'     => 'staff.schedule',
            'staff/attendance'   => 'staff.attendance',
            'staff'              => 'staff',
            'members'            => 'members',
            'site/pages'         => 'site.pages',
            'site/widgets'       => 'site.widgets',
            'site/design'        => 'site.design',
            'site/menus'         => 'site.menus',
            'site'               => 'site',
            'settings'           => 'settings',
        ];

        foreach ($map as $prefix => $perm) {
            if ($adminRoute === $prefix || str_starts_with($adminRoute, $prefix . '/')) {
                return $perm;
            }
        }

        return null;
    }
}
