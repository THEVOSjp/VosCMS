<?php
/**
 * RezlyX 관리자 인증 및 권한 시스템
 *
 * 3중 연동 구조: rzx_users ↔ rzx_staff ↔ rzx_admins
 * 슈퍼바이저(master)는 삭제/해제/비활성화 완전 차단
 */

namespace RzxLib\Core\Auth;

class AdminAuth
{
    private static ?\PDO $pdo = null;

    public static function init(\PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    // ===== 로그인/로그아웃 =====

    /**
     * 관리자 로그인 시도
     * @return array|string 성공 시 admin 배열, 실패 시 에러 메시지
     */
    public static function attempt(string $email, string $password): array|string
    {
        $stmt = self::$pdo->prepare("
            SELECT a.*, s.is_active as staff_active, u.status as user_status
            FROM rzx_admins a
            LEFT JOIN rzx_staff s ON a.staff_id = s.id
            LEFT JOIN rzx_users u ON a.user_id = u.id
            WHERE a.email = ?
        ");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$admin) return 'invalid_credentials';
        if (!password_verify($password, $admin['password'])) return 'invalid_credentials';
        if ($admin['status'] !== 'active') return 'account_inactive';

        // 3중 연동 검증 (master는 연동 없어도 로그인 가능 - 초기 설치 호환)
        if ($admin['role'] !== 'master') {
            if ($admin['user_id'] && $admin['user_status'] !== 'active') return 'user_inactive';
            if ($admin['staff_id'] && !$admin['staff_active']) return 'staff_inactive';
        }

        // 세션 설정
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_permissions'] = $admin['permissions'];
        $_SESSION['admin_logged_in_at'] = date('c');

        // last_login_at 업데이트
        self::$pdo->prepare("UPDATE rzx_admins SET last_login_at = NOW() WHERE id = ?")
            ->execute([$admin['id']]);

        return $admin;
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
    }

    // ===== 상태 확인 =====

    /**
     * 관리자 로그인 여부
     */
    public static function check(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    /**
     * 현재 로그인된 관리자 정보
     */
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
     * 현재 관리자가 master인지 확인
     */
    public static function isMaster(): bool
    {
        return ($_SESSION['admin_role'] ?? '') === 'master';
    }

    // ===== 권한 확인 =====

    /**
     * 현재 관리자가 특정 권한을 가지고 있는지 확인
     * master는 항상 true
     * 상위 권한이 있으면 하위도 접근 가능 (staff → staff.schedule)
     */
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

    /**
     * 해당 user_id가 슈퍼바이저에 연결되어 있는지 확인
     */
    public static function isSupervisorUser(string $userId): bool
    {
        $stmt = self::$pdo->prepare("SELECT id FROM rzx_admins WHERE user_id = ? AND role = 'master'");
        $stmt->execute([$userId]);
        return (bool)$stmt->fetch();
    }

    /**
     * 해당 staff_id가 슈퍼바이저에 연결되어 있는지 확인
     */
    public static function isSupervisorStaff(int $staffId): bool
    {
        $stmt = self::$pdo->prepare("SELECT id FROM rzx_admins WHERE staff_id = ? AND role = 'master'");
        $stmt->execute([$staffId]);
        return (bool)$stmt->fetch();
    }

    /**
     * 해당 admin_id가 슈퍼바이저인지 확인
     */
    public static function isSupervisorAdmin(string $adminId): bool
    {
        $stmt = self::$pdo->prepare("SELECT id FROM rzx_admins WHERE id = ? AND role = 'master'");
        $stmt->execute([$adminId]);
        return (bool)$stmt->fetch();
    }

    /**
     * master 계정 수 반환 (최소 1명 유지용)
     */
    public static function masterCount(): int
    {
        return (int)self::$pdo->query("SELECT COUNT(*) FROM rzx_admins WHERE role = 'master'")->fetchColumn();
    }

    // ===== 라우트 → 권한 매핑 =====

    /**
     * 관리자 경로에 필요한 권한 반환
     * null이면 로그인만 필요 (권한 체크 불필요)
     */
    public static function getRequiredPermission(string $adminRoute): ?string
    {
        // 대시보드는 로그인만 필요
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

        // 가장 구체적인 경로부터 매칭
        foreach ($map as $prefix => $perm) {
            if ($adminRoute === $prefix || str_starts_with($adminRoute, $prefix . '/')) {
                return $perm;
            }
        }

        return null;
    }
}
