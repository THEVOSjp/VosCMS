<?php

namespace RzxLib\Core\Modules\BusinessType;

/**
 * 공간(테이블/룸) 중심 POS 어댑터
 * 대상 업종: 레스토랑/카페, 숙박/호텔/펜션
 *
 * 핵심: 1테이블(룸) = 1카드, 고객+주문/서비스 연결
 * - 공간이 미등록 시 고객 중심 폴백
 * - rzx_spaces 테이블 활용 (향후 생성)
 */
class SpaceBasedAdapter implements PosAdapterInterface
{
    private string $viewBasePath;
    private \PDO $pdo;
    private string $prefix;

    public function __construct(string $viewBasePath, \PDO $pdo, string $prefix = 'rzx_')
    {
        $this->viewBasePath = $viewBasePath;
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    public function getMode(): string
    {
        return 'space';
    }

    public function groupReservations(array $reservations, string $nowTime): array
    {
        // 공간(테이블/룸) 목록 로드
        $spaces = $this->loadSpaces();

        $reservationList = [];
        $waitingList = [];
        $completedCount = 0;
        $occupiedCount = 0;
        $waitingCount = 0;

        // 공간별 그룹핑
        $spaceGroups = [];
        // 공간이 있으면 초기화
        foreach ($spaces as $sp) {
            $spaceGroups[$sp['id']] = [
                'space_id'     => $sp['id'],
                'space_name'   => $sp['name'],
                'space_type'   => $sp['type'] ?? 'table', // table, room, seat
                'capacity'     => (int)($sp['capacity'] ?? 0),
                'floor'        => $sp['floor'] ?? '',
                'services'     => [],
                'customers'    => [],
                'total_amount' => 0,
                'paid_amount'  => 0,
                'is_occupied'  => false,
                'has_pending'  => false,
                'earliest_start' => '23:59',
                'latest_end'     => '00:00',
            ];
        }

        // 미배정 예약 그룹 (공간 없는 예약)
        $unassigned = [];

        foreach ($reservations as $r) {
            $st = $r['status'] ?? 'pending';
            $src = $r['source'] ?? 'online';

            // 탭 분류
            if ($src !== 'walk_in' && $st !== 'cancelled' && $st !== 'no_show') {
                $reservationList[] = $r;
            }
            if ($src === 'walk_in' && ($st === 'pending' || $st === 'confirmed')) {
                $waitingList[] = $r;
            }
            if ($st === 'completed') { $completedCount++; continue; }
            if ($st === 'cancelled' || $st === 'no_show') continue;

            $spaceId = $r['space_id'] ?? null;

            if ($spaceId && isset($spaceGroups[$spaceId])) {
                $g = &$spaceGroups[$spaceId];
                $g['services'][] = $r;
                $g['total_amount'] += (float)($r['final_amount'] ?? $r['total_amount'] ?? 0);
                $g['paid_amount']  += (float)($r['paid_amount'] ?? 0);

                // 고객 정보 수집 (중복 제거)
                $custKey = ($r['customer_name'] ?? '') . '|' . ($r['customer_phone'] ?? '');
                if (!isset($g['customers'][$custKey])) {
                    $g['customers'][$custKey] = [
                        'name'  => $r['customer_name'] ?? '',
                        'phone' => $r['customer_phone'] ?? '',
                    ];
                }

                $isInSvc = ($st === 'confirmed'
                    && ($r['start_time'] ?? '') <= $nowTime
                    && (($r['end_time'] ?? '23:59:59') >= $nowTime));
                if ($isInSvc) { $g['is_occupied'] = true; $occupiedCount++; }
                if ($st === 'pending' || ($st === 'confirmed' && !$isInSvc)) {
                    $g['has_pending'] = true;
                    $waitingCount++;
                }

                $sTime = substr($r['start_time'] ?? '23:59', 0, 5);
                $eTime = substr($r['end_time'] ?? '00:00', 0, 5);
                if ($sTime < $g['earliest_start']) $g['earliest_start'] = $sTime;
                if ($eTime > $g['latest_end']) $g['latest_end'] = $eTime;
                unset($g);
            } else {
                $unassigned[] = $r;
            }
        }

        // 카드 배열 구성
        $allCards = [];
        foreach ($spaceGroups as $g) {
            $g['customers'] = array_values($g['customers']); // 인덱스 배열로 변환
            if (!empty($g['services'])) {
                $g['group_status'] = $g['is_occupied'] ? 'occupied' : ($g['has_pending'] ? 'reserved' : 'available');
            } else {
                $g['group_status'] = 'available';
            }
            $g['payment_status'] = ($g['paid_amount'] >= $g['total_amount'] && $g['total_amount'] > 0)
                ? 'paid'
                : (($g['paid_amount'] > 0) ? 'partial' : 'unpaid');
            $allCards[] = $g;
        }

        // 사용중 > 예약됨 > 빈자리 순 정렬
        $order = ['occupied' => 0, 'reserved' => 1, 'available' => 2];
        usort($allCards, fn($a, $b) =>
            ($order[$a['group_status']] ?? 9) - ($order[$b['group_status']] ?? 9)
        );

        return [
            'cards'      => $allCards,
            'counts'     => [
                'in_service'   => $occupiedCount,
                'waiting'      => $waitingCount,
                'reservations' => count($reservationList),
                'total'        => count($reservations),
                'available'    => count($spaces) - count(array_filter($allCards, fn($c) => $c['group_status'] !== 'available')),
            ],
            'tab_data'   => [
                'reservations' => $reservationList,
                'waiting'      => $waitingList,
            ],
            'completed'  => $completedCount,
            'unassigned' => $unassigned,
        ];
    }

    public function prepareCardData(array $card, string $nowTime): array
    {
        $gStatus = $card['group_status'];
        $pSt     = $card['payment_status'];
        $custCount = count($card['customers']);

        // 진행률 (사용중일 때만)
        $progress = 0;
        $remaining = 999;
        $isOvertime = false;

        if ($gStatus === 'occupied') {
            foreach ($card['services'] as $s) {
                $sSt = $s['status'] ?? 'pending';
                $sInSvc = ($sSt === 'confirmed'
                    && ($s['start_time'] ?? '') <= $nowTime
                    && (($s['end_time'] ?? '23:59:59') >= $nowTime));
                if (!$sInSvc) continue;

                $sT  = substr($s['start_time'] ?? '', 0, 5);
                $eT  = substr($s['end_time'] ?? '', 0, 5);
                $dur = (int)($s['service_duration'] ?? 0);
                $sm  = intval(substr($sT, 0, 2)) * 60 + intval(substr($sT, 3, 2));
                $em  = $eT ? intval(substr($eT, 0, 2)) * 60 + intval(substr($eT, 3, 2)) : $sm + $dur;
                $nm  = intval(date('H')) * 60 + intval(date('i'));
                $tm  = max($em - $sm, 1);
                $el  = max(0, $nm - $sm);
                $p   = min(100, round($el / $tm * 100));
                $rm  = $em - $nm;
                if ($rm < $remaining) { $remaining = $rm; $progress = $p; }
            }
            $isOvertime = $remaining <= 0;
        }

        // 카드 색상
        if ($gStatus === 'available') {
            $borderCls = 'border-zinc-200 dark:border-zinc-700';
        } elseif ($gStatus === 'reserved') {
            $borderCls = 'border-amber-300 dark:border-amber-600';
        } elseif ($isOvertime) {
            $borderCls = 'border-red-400 dark:border-red-500';
        } else {
            $borderCls = 'border-emerald-300 dark:border-emerald-600';
        }

        // JS 전달 데이터
        $firstR = !empty($card['services']) ? $card['services'][0] : null;
        $cardJson = htmlspecialchars(json_encode([
            'space_id'         => $card['space_id'],
            'space_name'       => $card['space_name'],
            'id'               => $firstR['id'] ?? null,
            'customer_name'    => $firstR['customer_name'] ?? '',
            'customer_phone'   => $firstR['customer_phone'] ?? '',
            'customer_email'   => $firstR['customer_email'] ?? '',
            'reservation_date' => $firstR['reservation_date'] ?? date('Y-m-d'),
            'source'           => $firstR['source'] ?? 'walk_in',
            'service_ids'      => array_column($card['services'], 'id'),
        ], JSON_UNESCAPED_UNICODE));

        return [
            'card'       => $card,
            'gStatus'    => $gStatus,
            'pSt'        => $pSt,
            'custCount'  => $custCount,
            'firstR'     => $firstR,
            'progress'   => $progress,
            'remaining'  => $remaining,
            'isOvertime' => $isOvertime,
            'borderCls'  => $borderCls,
            'cardJson'   => $cardJson,
        ];
    }

    public function getCardViewPath(): string
    {
        return $this->viewBasePath . '/pos-card-space.php';
    }

    public function getStatusFlow(): array
    {
        return [
            'statuses' => ['available', 'reserved', 'occupied', 'cleaning', 'completed'],
            'transitions' => [
                'available' => ['reserve', 'seat'],
                'reserved'  => ['seat', 'cancel'],
                'occupied'  => ['complete', 'cancel'],
                'cleaning'  => ['available'],
            ],
            'labels' => [
                'available' => 'reservations.pos_space_available',
                'reserved'  => 'reservations.pos_space_reserved',
                'occupied'  => 'reservations.pos_space_occupied',
                'cleaning'  => 'reservations.pos_space_cleaning',
            ],
        ];
    }

    public function getCheckinMode(): string
    {
        return 'table_assign';
    }

    public function getExtraJsPath(): ?string
    {
        return $this->viewBasePath . '/pos-space-js.php';
    }

    /**
     * 공간(테이블/룸) 목록 로드
     */
    private function loadSpaces(): array
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT * FROM {$this->prefix}spaces WHERE is_active = 1 ORDER BY floor ASC, sort_order ASC, name ASC"
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // 테이블 미존재 시 빈 배열 반환
            return [];
        }
    }
}
