<?php

namespace RzxLib\Core\Modules\BusinessType;

/**
 * 고객 중심 POS 어댑터
 * 대상 업종: 미용실, 네일샵, 피부관리, 마사지/스파, 병원, 치과, 스튜디오,
 *           펫서비스, 자동차정비, 교육, 컨설팅, 스포츠, 기타
 *
 * 핵심: 1고객 = 1카드, 다중 서비스 그룹핑
 */
class CustomerBasedAdapter implements PosAdapterInterface
{
    private string $viewBasePath;

    public function __construct(string $viewBasePath)
    {
        $this->viewBasePath = $viewBasePath;
    }

    public function getMode(): string
    {
        return 'customer';
    }

    public function groupReservations(array $reservations, string $nowTime): array
    {
        $customerGroups = [];
        $checkinList = [];  // 접수 명단: walk_in, pos, kiosk, admin
        $reserveList = [];  // 예약자: phone, online
        $completedCount = 0;
        $cancelledCount = 0;
        $noShowCount = 0;
        $inServiceCount = 0;
        $waitingCount = 0;
        $walkinCount = 0;

        // 상태 정렬 우선순위: 대기 → 확정 → 이용중(confirmed+시간내) → 완료 → 노쇼 → 취소
        $statusOrder = ['pending' => 0, 'confirmed' => 1, 'completed' => 3, 'no_show' => 4, 'cancelled' => 5];

        foreach ($reservations as $r) {
            $st = $r['status'] ?? 'pending';
            $src = $r['source'] ?? 'online';

            // 접수 vs 예약 분류 (모든 상태 포함)
            if (in_array($src, ['walk_in', 'pos', 'kiosk', 'admin'])) {
                $checkinList[] = $r;
                if ($st === 'pending' || $st === 'confirmed') $walkinCount++;
            } else {
                $reserveList[] = $r;
            }

            if ($st === 'completed') $completedCount++;
            if ($st === 'cancelled') $cancelledCount++;
            if ($st === 'no_show') $noShowCount++;

            // 카드 그룹핑은 활성 상태만
            if (in_array($st, ['completed', 'cancelled', 'no_show'])) continue;

            // 예약번호 기준 그룹핑 (1예약 = 1카드)
            $key = $r['id'];
            if (!isset($customerGroups[$key])) {
                $customerGroups[$key] = [
                    'customer_name'    => $r['customer_name'] ?? '',
                    'customer_phone'   => $r['customer_phone'] ?? '',
                    'customer_email'   => $r['customer_email'] ?? '',
                    'reservation_date' => $r['reservation_date'],
                    'source'           => $src,
                    'services'         => [],
                    'total_amount'     => 0,
                    'db_final_amount'  => 0,
                    'designation_fee'  => 0,
                    'paid_amount'      => 0,
                    'has_in_service'   => false,
                    'has_pending'      => false,
                    'earliest_start'   => '23:59',
                    'latest_end'       => '00:00',
                    'service_image'      => null,
                    'user_id'            => null,
                    'user_profile_image' => null,
                    'grade_name'         => null,
                    'grade_discount_rate'=> 0,
                    'grade_point_rate'   => 0,
                    'grade_color'        => null,
                    'points_balance'     => 0,
                    'staff_id'           => null,
                    'staff_name'         => null,
                ];
            }
            // 첫 번째 서비스 이미지 / 회원 정보 설정
            if (empty($customerGroups[$key]['service_image']) && !empty($r['service_image'])) {
                $customerGroups[$key]['service_image'] = $r['service_image'];
            }
            if (empty($customerGroups[$key]['user_id']) && !empty($r['user_id'])) {
                $customerGroups[$key]['user_id'] = $r['user_id'];
            }
            if (empty($customerGroups[$key]['user_profile_image']) && !empty($r['user_profile_image'])) {
                $customerGroups[$key]['user_profile_image'] = $r['user_profile_image'];
            }
            // 스태프 정보 전파
            if (empty($customerGroups[$key]['staff_id']) && !empty($r['staff_id'])) {
                $customerGroups[$key]['staff_id'] = $r['staff_id'];
                $customerGroups[$key]['staff_name'] = $r['staff_name'] ?? null;
            }
            // 회원 등급 정보 전파
            if (empty($customerGroups[$key]['grade_name']) && !empty($r['grade_name'])) {
                $customerGroups[$key]['grade_name'] = $r['grade_name'];
                $customerGroups[$key]['grade_discount_rate'] = (float)($r['grade_discount_rate'] ?? 0);
                $customerGroups[$key]['grade_point_rate'] = (float)($r['grade_point_rate'] ?? 0);
                $customerGroups[$key]['grade_color'] = $r['grade_color'] ?? null;
                $customerGroups[$key]['points_balance'] = (float)($r['user_points_balance'] ?? 0);
            }
            $g = &$customerGroups[$key];
            $g['services'][] = $r;
            $g['total_amount'] += (float)($r['total_amount'] ?? 0);
            $g['db_final_amount'] += (float)($r['final_amount'] ?? $r['total_amount'] ?? 0);
            $g['designation_fee'] += (float)($r['designation_fee'] ?? 0);
            $g['paid_amount']  += (float)($r['paid_amount'] ?? 0);

            // 이용중 판정: confirmed 상태이고, 현재 시간이 start~end 범위 안
            // end_time이 start_time보다 작으면 자정 넘김 (예: 22:00~02:00)
            $sTime = $r['start_time'] ?? '';
            $eTime = $r['end_time'] ?? '23:59:59';
            $crossesMidnight = ($eTime < $sTime);
            $isInSvc = ($st === 'confirmed' && $sTime <= $nowTime)
                && ($crossesMidnight || $eTime >= $nowTime);
            if ($isInSvc) { $g['has_in_service'] = true; $inServiceCount++; }
            if ($st === 'pending' || ($st === 'confirmed' && !$isInSvc)) {
                $g['has_pending'] = true;
                $waitingCount++;
            }

            $sTime = substr($r['start_time'] ?? '23:59', 0, 5);
            $eTime = substr($r['end_time'] ?? '00:00', 0, 5);
            if ($sTime < $g['earliest_start']) $g['earliest_start'] = $sTime;
            if ($eTime > $g['latest_end']) $g['latest_end'] = $eTime;
            unset($g);
        }

        // 그룹 상태 결정, 할인 적용, 정렬
        $allCards = [];
        foreach ($customerGroups as $g) {
            $g['group_status'] = $g['has_in_service'] ? 'in_service' : 'waiting';
            // 회원 등급 할인율 적용 (서비스 원가 기준으로 할인, DB final_amount에서 차감)
            $discountRate = $g['grade_discount_rate'] ?? 0;
            $baseForDiscount = $g['total_amount']; // 서비스 원가 합계 (지명비 미포함)
            $dbFinal = $g['db_final_amount']; // DB final_amount 합계 (번들/지명비 반영)
            if ($discountRate > 0 && $baseForDiscount > 0) {
                $g['discount_amount'] = round($baseForDiscount * $discountRate / 100);
                $g['final_amount'] = $dbFinal - $g['discount_amount'];
            } else {
                $g['discount_amount'] = 0;
                $g['final_amount'] = $dbFinal;
            }
            // 적립 예정 포인트
            $pointRate = $g['grade_point_rate'] ?? 0;
            $g['expected_points'] = ($pointRate > 0 && $g['final_amount'] > 0)
                ? round($g['final_amount'] * $pointRate / 100)
                : 0;
            $g['payment_status'] = ($g['paid_amount'] >= $g['final_amount'] && $g['final_amount'] > 0)
                ? 'paid'
                : (($g['paid_amount'] > 0) ? 'partial' : 'unpaid');
            $allCards[] = $g;
        }
        // 카드: 이용중 먼저, 대기 다음
        usort($allCards, fn($a, $b) =>
            ($a['group_status'] === 'in_service' ? 0 : 1) - ($b['group_status'] === 'in_service' ? 0 : 1)
        );

        // 탭 리스트 정렬: 대기 → 확정(이용중) → 완료 → 노쇼 → 취소, 같은 상태는 시간순
        $sortList = function(array &$list) use ($nowTime) {
            usort($list, function($a, $b) use ($nowTime) {
                $order = ['pending' => 0, 'confirmed' => 1, 'completed' => 3, 'no_show' => 4, 'cancelled' => 5];
                $stA = $a['status'] ?? 'pending';
                $stB = $b['status'] ?? 'pending';
                // confirmed 중 이용중(시간 내)은 2로 분류
                $oA = $order[$stA] ?? 9;
                $oB = $order[$stB] ?? 9;
                if ($stA === 'confirmed') {
                    $sA = $a['start_time'] ?? ''; $eA = $a['end_time'] ?? '23:59:59';
                    if ($sA <= $nowTime && ($eA >= $nowTime || $eA < $sA)) $oA = 2; // 이용중
                }
                if ($stB === 'confirmed') {
                    $sB = $b['start_time'] ?? ''; $eB = $b['end_time'] ?? '23:59:59';
                    if ($sB <= $nowTime && ($eB >= $nowTime || $eB < $sB)) $oB = 2;
                }
                if ($oA !== $oB) return $oA - $oB;
                return strcmp($a['start_time'] ?? '', $b['start_time'] ?? '');
            });
        };
        $sortList($checkinList);
        $sortList($reserveList);

        return [
            'cards'     => $allCards,
            'counts'    => [
                'in_service'   => $inServiceCount,
                'waiting'      => $waitingCount,
                'walkin'       => $walkinCount,
                'checkin'      => count($checkinList),
                'reservations' => count($reserveList),
                'total'        => count($reservations),
            ],
            'tab_data'  => [
                'checkin'      => $checkinList,
                'reservations' => $reserveList,
            ],
            'completed' => $completedCount,
            'cancelled' => $cancelledCount,
            'no_show'   => $noShowCount,
        ];
    }

    public function prepareCardData(array $card, string $nowTime): array
    {
        $gStatus   = $card['group_status'];
        $pSt       = $card['payment_status'];
        $svcCount  = count($card['services']);
        $firstR    = $card['services'][0];

        // 진행률 계산
        $progress = 0;
        $remaining = 999;
        $isOvertime = false;

        if ($gStatus === 'in_service') {
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
        if ($gStatus === 'waiting') {
            $borderCls = 'border-amber-300 dark:border-amber-600';
        } elseif ($isOvertime) {
            $borderCls = 'border-red-400 dark:border-red-500';
        } else {
            $borderCls = 'border-emerald-300 dark:border-emerald-600';
        }

        // JS 전달 데이터
        $cardJson = htmlspecialchars(json_encode([
            'id'               => $firstR['id'],
            'customer_name'    => $card['customer_name'],
            'customer_phone'   => $card['customer_phone'],
            'customer_email'   => $card['customer_email'],
            'reservation_date' => $card['reservation_date'],
            'source'           => $card['source'],
            'user_id'          => $card['user_id'] ?? null,
            'reservation_ids'  => [$firstR['id']],
            'reservation_number' => $firstR['reservation_number'] ?? '',
            // 결제 내역용
            'total_amount'     => $card['total_amount'],
            'designation_fee'  => $card['designation_fee'],
            'discount_rate'    => $card['grade_discount_rate'] ?? 0,
            'discount_amount'  => $card['discount_amount'] ?? 0,
            'final_amount'     => $card['final_amount'],
            'paid_amount'      => $card['paid_amount'],
        ], JSON_UNESCAPED_UNICODE));

        return [
            'card'       => $card,
            'gStatus'    => $gStatus,
            'pSt'        => $pSt,
            'svcCount'   => $svcCount,
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
        return $this->viewBasePath . '/pos-card-customer.php';
    }

    public function getStatusFlow(): array
    {
        return [
            'statuses' => ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'],
            'transitions' => [
                'pending'   => ['confirm', 'cancel'],
                'confirmed' => ['complete', 'cancel', 'no-show'],
            ],
            'labels' => [
                'pending'   => 'reservations.pos_waiting',
                'confirmed' => 'reservations.pos_in_service',
                'completed' => 'reservations.actions.complete',
            ],
        ];
    }

    public function getCheckinMode(): string
    {
        return 'service_select';
    }

    public function getExtraJsPath(): ?string
    {
        return null; // 고객 중심은 기본 pos-js.php + pos-service-js.php 사용
    }
}
