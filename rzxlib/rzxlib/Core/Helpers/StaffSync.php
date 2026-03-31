<?php
/**
 * RezlyX - 스태프-회원 등급 자동 동기화 헬퍼
 *
 * 회원 등급이 변경될 때 스태프를 자동 생성/비활성화합니다.
 * 사용법:
 *   require_once BASE_PATH . '/rzxlib/Core/Helpers/StaffSync.php';
 *   StaffSync::onGradeChanged($pdo, $prefix, $userId, $newGradeId, $oldGradeId);
 */

class StaffSync
{
    /**
     * 회원 등급 변경 시 호출
     * - 새 등급이 스태프 연동 등급이면: 스태프 자동 생성 또는 활성화
     * - 이전 등급이 스태프 연동 등급이었고 새 등급이 아니면: 스태프 비활성화
     */
    public static function onGradeChanged(PDO $pdo, string $prefix, string $userId, ?string $newGradeId, ?string $oldGradeId = null): array
    {
        $result = ['action' => 'none', 'staff_id' => null];

        // 스태프 연동 등급 설정 로드
        $stmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'staff_linked_grade'");
        $stmt->execute();
        $linkedGrade = $stmt->fetchColumn();

        if (empty($linkedGrade)) {
            return $result; // 연동 등급 미설정
        }

        $isNewGradeLinked = ($newGradeId === $linkedGrade);
        $isOldGradeLinked = ($oldGradeId === $linkedGrade);

        if ($isNewGradeLinked && !$isOldGradeLinked) {
            // 스태프 연동 등급으로 변경됨 → 스태프 생성 또는 활성화
            $result = self::activateOrCreateStaff($pdo, $prefix, $userId);
        } elseif (!$isNewGradeLinked && $isOldGradeLinked) {
            // 스태프 연동 등급에서 해제됨 → 스태프 비활성화
            $result = self::deactivateStaff($pdo, $prefix, $userId);
        }

        return $result;
    }

    /**
     * 회원을 스태프로 활성화 (기존 스태프가 있으면 활성화, 없으면 생성)
     */
    private static function activateOrCreateStaff(PDO $pdo, string $prefix, string $userId): array
    {
        // 기존 스태프 확인
        $stmt = $pdo->prepare("SELECT id, is_active FROM {$prefix}staff WHERE user_id = ?");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // 이미 존재 → 활성화
            if (!$existing['is_active']) {
                $pdo->prepare("UPDATE {$prefix}staff SET is_active = 1 WHERE id = ?")->execute([$existing['id']]);
            }
            return ['action' => 'activated', 'staff_id' => $existing['id']];
        }

        // 회원 정보 조회
        $stmt = $pdo->prepare("SELECT name, email, phone FROM {$prefix}users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['action' => 'error', 'staff_id' => null];
        }

        // 새 스태프 생성
        $maxSort = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$prefix}staff")->fetchColumn();
        $stmt = $pdo->prepare("INSERT INTO {$prefix}staff (user_id, name, email, phone, is_active, sort_order) VALUES (?, ?, ?, ?, 1, ?)");
        $stmt->execute([$userId, $user['name'], $user['email'], $user['phone'], $maxSort]);

        return ['action' => 'created', 'staff_id' => $pdo->lastInsertId()];
    }

    /**
     * 스태프 비활성화 (삭제하지 않고 비활성화만)
     */
    private static function deactivateStaff(PDO $pdo, string $prefix, string $userId): array
    {
        $stmt = $pdo->prepare("SELECT id FROM {$prefix}staff WHERE user_id = ?");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $pdo->prepare("UPDATE {$prefix}staff SET is_active = 0 WHERE id = ?")->execute([$existing['id']]);
            return ['action' => 'deactivated', 'staff_id' => $existing['id']];
        }

        return ['action' => 'none', 'staff_id' => null];
    }

    /**
     * 특정 등급의 모든 회원을 일괄 동기화
     * (설정 변경 시 기존 해당 등급 회원들을 일괄 처리)
     */
    public static function syncAllByGrade(PDO $pdo, string $prefix, string $gradeId): array
    {
        $results = ['created' => 0, 'activated' => 0, 'skipped' => 0];

        $stmt = $pdo->prepare("SELECT id FROM {$prefix}users WHERE grade_id = ? AND status = 'active'");
        $stmt->execute([$gradeId]);
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($users as $userId) {
            $r = self::activateOrCreateStaff($pdo, $prefix, $userId);
            if ($r['action'] === 'created') $results['created']++;
            elseif ($r['action'] === 'activated') $results['activated']++;
            else $results['skipped']++;
        }

        return $results;
    }
}
