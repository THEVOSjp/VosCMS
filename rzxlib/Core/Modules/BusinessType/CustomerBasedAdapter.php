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
        $reservationList = [];
        $waitingList = [];
        $completedCount = 0;
        $inServiceCount = 0;
        $waitingCount = 0;

        foreach ($reservations as $r) {
            $st = $r['status'] ?? 'pending';
            $src = $r['source'] ?? 'online';

            // 탭 분류 (개별 건 기준)
            if ($src !== 'walk_in' && $st !== 'cancelled' && $st !== 'no_show') {
                $reservationList[] = $r;
            }
            if ($src === 'walk_in' && ($st === 'pending' || $st === 'confirmed')) {
                $waitingList[] = $r;
            }
            if ($st === 'completed') { $completedCount++; continue; }
            if ($st === 'cancelled' || $st === 'no_show') continue;

            // 고객별 그룹핑 키: 이름|전화번호
            $key = ($r['customer_name'] ?? '') . '|' . ($r['customer_phone'] ?? '');
            if (!isset($customerGroups[$key])) {
                $customerGroups[$key] = [
                    'customer_name'    => $r['customer_name'] ?? '',
                    'customer_phone'   => $r['customer_phone'] ?? '',
                    'customer_email'   => $r['customer_email'] ?? '',
                    'reservation_date' => $r['reservation_date'],
                    'source'           => $src,
                    'services'         => [],
                    'total_amount'     => 0,
                    'paid_amount'      => 0,
                    'has_in_service'   => false,
                    'has_pending'      => false,
                    'earliest_start'   => '23:59',
                    'latest_end'       => '00:00',
                    'service_image'      => null,
                    'user_id'            => null,
                    'user_profile_image' => null,
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
            $g = &$customerGroups[$key];
            $g['services'][] = $r;
            $g['total_amount'] += (float)($r['final_amount'] ?? $r['total_amount'] ?? 0);
            $g['paid_amount']  += (float)($r['paid_amount'] ?? 0);

            $isInSvc = ($st === 'confirmed'
                && ($r['start_time'] ?? '') <= $nowTime
                && (($r['end_time'] ?? '23:59:59') >= $nowTime));
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

        // 그룹 상태 결정 및 정렬
        $allCards = [];
        foreach ($customerGroups as $g) {
            $g['group_status'] = $g['has_in_service'] ? 'in_service' : 'waiting';
            $g['payment_status'] = ($g['paid_amount'] >= $g['total_amount'] && $g['total_amount'] > 0)
                ? 'paid'
                : (($g['paid_amount'] > 0) ? 'partial' : 'unpaid');
            $allCards[] = $g;
        }
        // 이용중 먼저, 대기 다음
        usort($allCards, fn($a, $b) =>
            ($a['group_status'] === 'in_service' ? 0 : 1) - ($b['group_status'] === 'in_service' ? 0 : 1)
        );

        return [
            'cards'     => $allCards,
            'counts'    => [
                'in_service'   => $inServiceCount,
                'waiting'      => $waitingCount,
                'reservations' => count($reservationList),
                'total'        => count($reservations),
            ],
            'tab_data'  => [
                'reservations' => $reservationList,
                'waiting'      => $waitingList,
            ],
            'completed' => $completedCount,
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
            'service_ids'      => array_column($card['services'], 'id'),
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
