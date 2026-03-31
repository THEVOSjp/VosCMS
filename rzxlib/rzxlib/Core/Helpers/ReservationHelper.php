<?php
/**
 * 예약 생성/서비스 저장 공용 헬퍼
 *
 * 모든 예약 생성 경로에서 동일한 로직을 사용한다.
 * - 사용자 예약 (booking.php, render.php)
 * - 스태프 지명 예약 (staff-detail-ajax.php)
 * - 관리자 예약 등록 (_api.php)
 * - POS 서비스 추가 (_api.php add-service)
 * - 기존 예약에 서비스 추가 (_api.php append-service)
 * - 키오스크 (kiosk/confirm.php)
 *
 * 번들 규칙: 번들 선택 시 service_bundle_items에서 전체 서비스를 조회하여
 *           reservation_services에 스냅샷 저장 (RESERVATION_SYSTEM.md 참조)
 */

namespace RzxLib\Core\Helpers;

class ReservationHelper
{
    /**
     * 번들/개별 서비스 조회
     * 번들이면 service_bundle_items에서 전체 조회, 아니면 service_ids로 조회
     *
     * @return array ['services' => [...], 'bundle_price' => float|null, 'total_amount' => float, 'total_duration' => int]
     */
    public static function resolveServices(\PDO $pdo, string $prefix, ?string $bundleId, array $serviceIds = []): array
    {
        $services = [];
        $bundlePrice = null;

        if ($bundleId) {
            // 번들: service_bundle_items에서 전체 서비스 조회 (스냅샷)
            $stmt = $pdo->prepare("SELECT s.id, s.name, s.price, s.duration
                FROM {$prefix}service_bundle_items bi
                JOIN {$prefix}services s ON bi.service_id = s.id
                WHERE bi.bundle_id = ? ORDER BY bi.sort_order");
            $stmt->execute([$bundleId]);
            $services = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // 번들 가격 조회
            $bdlStmt = $pdo->prepare("SELECT bundle_price FROM {$prefix}service_bundles WHERE id = ? AND is_active = 1");
            $bdlStmt->execute([$bundleId]);
            $dbPrice = $bdlStmt->fetchColumn();
            if ($dbPrice !== false) $bundlePrice = (float)$dbPrice;
        }

        if (empty($services) && !empty($serviceIds)) {
            // 개별 서비스 조회
            $ph = implode(',', array_fill(0, count($serviceIds), '?'));
            $stmt = $pdo->prepare("SELECT id, name, price, duration FROM {$prefix}services WHERE id IN ({$ph}) AND is_active = 1");
            $stmt->execute(array_values($serviceIds));
            $services = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $totalAmount = 0;
        $totalDuration = 0;
        foreach ($services as $s) {
            $totalAmount += (float)$s['price'];
            $totalDuration += (int)$s['duration'];
        }

        return [
            'services' => $services,
            'bundle_price' => $bundlePrice,
            'total_amount' => $totalAmount,
            'total_duration' => $totalDuration,
        ];
    }

    /**
     * 예약 생성 + 서비스 저장
     *
     * @param array $data 필수: customer_name, customer_phone, reservation_date, start_time
     *                    선택: bundle_id, service_ids, staff_id, designation_fee, user_id, source, notes, customer_email
     * @return array ['id' => string, 'reservation_number' => string, 'final_amount' => float]
     */
    public static function create(\PDO $pdo, string $prefix, array $data): array
    {
        $bundleId = $data['bundle_id'] ?? null;
        $serviceIds = $data['service_ids'] ?? [];

        // 서비스 조회
        $resolved = self::resolveServices($pdo, $prefix, $bundleId, $serviceIds);
        if (empty($resolved['services'])) {
            throw new \RuntimeException('유효한 서비스가 없습니다.');
        }

        $totalAmount = $resolved['total_amount'];
        $bundlePrice = $resolved['bundle_price'];
        $totalDuration = $resolved['total_duration'];
        $designationFee = (float)($data['designation_fee'] ?? 0);

        // final_amount 계산: 번들이면 번들 가격, 아니면 서비스 합계
        $baseAmount = ($bundlePrice !== null) ? $bundlePrice : $totalAmount;
        $finalAmount = $baseAmount + $designationFee;

        // 할인/적립금 적용 (전달된 경우)
        $discountAmount = (float)($data['discount_amount'] ?? 0);
        $pointsUsed = (float)($data['points_used'] ?? 0);
        $finalAmount -= ($discountAmount + $pointsUsed);

        // end_time 계산
        $startTime = $data['start_time'];
        if (!empty($data['end_time'])) {
            $endTime = $data['end_time'];
        } else {
            $parts = explode(':', $startTime);
            $endMin = ((int)$parts[0]) * 60 + ((int)($parts[1] ?? 0)) + $totalDuration;
            $endTime = sprintf('%02d:%02d:00', floor($endMin / 60) % 24, $endMin % 60);
        }

        // 온라인 결제 활성화 여부 확인
        $paymentStatus = 'paid';
        $paidAmount = $finalAmount;
        if (!empty($data['check_online_payment'])) {
            try {
                $payStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'payment_config'");
                $payStmt->execute();
                $payConf = json_decode($payStmt->fetchColumn() ?: '{}', true) ?: [];
                if (($payConf['enabled'] ?? '0') === '1' && !empty($payConf['public_key']) && !empty($payConf['secret_key'])) {
                    $paymentStatus = 'unpaid';
                    $paidAmount = 0;
                }
            } catch (\Throwable $e) {}
        }

        // 명시적 결제 상태 지정
        if (isset($data['payment_status'])) $paymentStatus = $data['payment_status'];
        if (isset($data['paid_amount'])) $paidAmount = (float)$data['paid_amount'];

        // ID 생성
        $id = bin2hex(random_bytes(18));
        $resNum = 'RZX' . date('YmdHis') . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        // 회원 매칭
        $userId = $data['user_id'] ?? null;

        $pdo->beginTransaction();
        try {
            // reservations INSERT
            $sql = "INSERT INTO {$prefix}reservations
                (id, reservation_number, user_id, staff_id, bundle_id, bundle_price,
                 customer_name, customer_phone, customer_email,
                 reservation_date, start_time, end_time,
                 total_amount, final_amount, designation_fee, discount_amount, points_used,
                 payment_status, paid_amount, status, source, notes, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $id, $resNum, $userId,
                $data['staff_id'] ?? null, $bundleId, $bundlePrice,
                $data['customer_name'], $data['customer_phone'], $data['customer_email'] ?? null,
                $data['reservation_date'], $startTime, $endTime,
                $totalAmount, $finalAmount, $designationFee, $discountAmount, $pointsUsed,
                $paymentStatus, $paidAmount, $data['source'] ?? 'online', $data['notes'] ?? null,
            ]);

            // reservation_services INSERT (스냅샷 저장)
            self::saveServices($pdo, $prefix, $id, $resolved['services'], $bundleId);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return [
            'id' => $id,
            'reservation_number' => $resNum,
            'total_amount' => $totalAmount,
            'final_amount' => $finalAmount,
            'bundle_price' => $bundlePrice,
            'services' => $resolved['services'],
        ];
    }

    /**
     * 기존 예약에 서비스 추가 (append)
     *
     * @return array ['count' => int, 'new_total' => float, 'new_final' => float]
     */
    public static function appendServices(\PDO $pdo, string $prefix, string $reservationId, ?string $bundleId, array $serviceIds = []): array
    {
        $resolved = self::resolveServices($pdo, $prefix, $bundleId, $serviceIds);
        if (empty($resolved['services'])) {
            throw new \RuntimeException('유효한 서비스가 없습니다.');
        }

        // 현재 최대 sort_order
        $sortIdx = 0;
        try {
            $maxSort = $pdo->prepare("SELECT COALESCE(MAX(sort_order), -1) FROM {$prefix}reservation_services WHERE reservation_id = ?");
            $maxSort->execute([$reservationId]);
            $sortIdx = (int)$maxSort->fetchColumn() + 1;
        } catch (\Throwable $e) {}

        // 현재 예약 정보
        $resStmt = $pdo->prepare("SELECT start_time, total_amount, final_amount, bundle_id, bundle_price FROM {$prefix}reservations WHERE id = ?");
        $resStmt->execute([$reservationId]);
        $resRow = $resStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$resRow) throw new \RuntimeException('예약을 찾을 수 없습니다.');

        $pdo->beginTransaction();
        try {
            self::saveServices($pdo, $prefix, $reservationId, $resolved['services'], $bundleId, $sortIdx);

            // 번들 정보 업데이트
            if ($bundleId && $resolved['bundle_price'] !== null) {
                $pdo->prepare("UPDATE {$prefix}reservations SET bundle_id = ?, bundle_price = ? WHERE id = ?")
                    ->execute([$bundleId, $resolved['bundle_price'], $reservationId]);
            }

            // 금액/시간 재계산
            $recalc = $pdo->prepare("SELECT SUM(price) as total, SUM(duration) as dur FROM {$prefix}reservation_services WHERE reservation_id = ?");
            $recalc->execute([$reservationId]);
            $sums = $recalc->fetch(\PDO::FETCH_ASSOC);
            $newTotal = (float)($sums['total'] ?? 0);
            $newDur = (int)($sums['dur'] ?? 0);

            // final_amount: 번들이면 번들 가격 기준
            $effectiveBundlePrice = $resolved['bundle_price'] ?? (float)($resRow['bundle_price'] ?? 0);
            $newFinal = ($bundleId || $resRow['bundle_id']) && $effectiveBundlePrice > 0
                ? $effectiveBundlePrice
                : $newTotal;

            // end_time 재계산
            $startTime = $resRow['start_time'];
            $parts = explode(':', $startTime);
            $endMin = ((int)$parts[0]) * 60 + ((int)($parts[1] ?? 0)) + $newDur;
            $newEndTime = sprintf('%02d:%02d:00', floor($endMin / 60) % 24, $endMin % 60);

            $pdo->prepare("UPDATE {$prefix}reservations SET total_amount = ?, final_amount = ?, end_time = ?, updated_at = NOW() WHERE id = ?")
                ->execute([$newTotal, $newFinal, $newEndTime, $reservationId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return [
            'count' => count($resolved['services']),
            'new_total' => $newTotal,
            'new_final' => $newFinal,
        ];
    }

    /**
     * reservation_services에 서비스 저장 (내부 공용)
     */
    /**
     * 외부에서 서비스 저장만 호출 (staff-detail-ajax 등)
     */
    public static function saveServicesPublic(\PDO $pdo, string $prefix, string $reservationId, array $services, ?string $bundleId, int $startIdx = 0): void
    {
        self::saveServices($pdo, $prefix, $reservationId, $services, $bundleId, $startIdx);
    }

    private static function saveServices(\PDO $pdo, string $prefix, string $reservationId, array $services, ?string $bundleId, int $startIdx = 0): void
    {
        $idx = $startIdx;
        try {
            $stmt = $pdo->prepare("INSERT INTO {$prefix}reservation_services
                (reservation_id, service_id, service_name, price, duration, sort_order, bundle_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($services as $s) {
                $stmt->execute([
                    $reservationId, $s['id'], $s['name'], $s['price'], $s['duration'], $idx++, $bundleId
                ]);
            }
        } catch (\PDOException $e) {
            // bundle_id 컬럼이 없는 경우 폴백
            if (stripos($e->getMessage(), 'Unknown column') !== false) {
                $stmt = $pdo->prepare("INSERT INTO {$prefix}reservation_services
                    (reservation_id, service_id, service_name, price, duration, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?)");
                $idx = $startIdx;
                foreach ($services as $s) {
                    $stmt->execute([
                        $reservationId, $s['id'], $s['name'], $s['price'], $s['duration'], $idx++
                    ]);
                }
            } else {
                throw $e;
            }
        }
    }
}
