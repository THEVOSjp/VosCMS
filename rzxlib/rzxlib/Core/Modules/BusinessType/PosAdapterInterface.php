<?php

namespace RzxLib\Core\Modules\BusinessType;

/**
 * POS 어댑터 인터페이스
 * 업종별 POS 동작을 정의하는 공통 계약
 */
interface PosAdapterInterface
{
    /**
     * POS 모드 식별자 반환
     * @return string 'customer' | 'space'
     */
    public function getMode(): string;

    /**
     * 오늘 예약 데이터를 업종 특성에 맞게 그룹핑
     *
     * @param array $reservations 당일 전체 예약 배열
     * @param string $nowTime 현재 시각 (H:i:s)
     * @return array [
     *   'cards'       => 그룹핑된 카드 배열,
     *   'counts'      => ['in_service'=>int, 'waiting'=>int, 'total'=>int, ...],
     *   'tab_data'    => ['reservations'=>[], 'waiting'=>[]],
     *   'completed'   => int,
     * ]
     */
    public function groupReservations(array $reservations, string $nowTime): array;

    /**
     * 카드 1장의 HTML 렌더링에 필요한 뷰 데이터 반환
     *
     * @param array $card groupReservations()가 생성한 카드 1개
     * @param string $nowTime 현재 시각
     * @return array 뷰에서 사용할 변수 배열
     */
    public function prepareCardData(array $card, string $nowTime): array;

    /**
     * 카드 뷰 파일 경로 반환
     * @return string 뷰 파일의 절대 경로
     */
    public function getCardViewPath(): string;

    /**
     * 업종별 상태 흐름 정의
     * @return array ['statuses' => [...], 'transitions' => [...]]
     */
    public function getStatusFlow(): array;

    /**
     * 업종별 접수 모드 반환
     * @return string 'service_select' | 'table_assign' | 'room_assign'
     */
    public function getCheckinMode(): string;

    /**
     * 업종에 맞는 JS 파일 경로 반환 (추가 로드용)
     * @return string|null JS 파일 경로 또는 null
     */
    public function getExtraJsPath(): ?string;
}
