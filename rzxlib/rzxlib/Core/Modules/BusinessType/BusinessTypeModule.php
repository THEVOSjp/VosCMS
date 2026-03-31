<?php

namespace RzxLib\Core\Modules\BusinessType;

/**
 * 업종별 POS 어댑터 팩토리
 * site_category 값으로 적절한 POS 어댑터를 생성
 */
class BusinessTypeModule
{
    /**
     * 고객 중심 업종 목록
     */
    private const CUSTOMER_BASED = [
        'beauty_salon',
        'nail_salon',
        'skincare',
        'massage',
        'hospital',
        'dental',
        'studio',
        'pet',
        'car',
        'education',
        'consulting',
        'sports',
        'other',
        '',  // 미선택 시 기본값
    ];

    /**
     * 공간(테이블/룸) 중심 업종 목록
     */
    private const SPACE_BASED = [
        'restaurant',
        'accommodation',
    ];

    /**
     * site_category 값으로 POS 어댑터 생성
     *
     * @param string $siteCategory  rzx_settings.site_category 값
     * @param string $viewBasePath  뷰 파일 기본 경로
     * @param \PDO|null $pdo        공간 기반일 때 필요한 DB 연결
     * @param string $prefix        테이블 접두사
     * @return PosAdapterInterface
     */
    public static function createPosAdapter(
        string $siteCategory,
        string $viewBasePath,
        ?\PDO $pdo = null,
        string $prefix = 'rzx_'
    ): PosAdapterInterface {
        if (in_array($siteCategory, self::SPACE_BASED, true) && $pdo !== null) {
            return new SpaceBasedAdapter($viewBasePath, $pdo, $prefix);
        }

        return new CustomerBasedAdapter($viewBasePath);
    }

    /**
     * 업종이 고객 중심인지 확인
     */
    public static function isCustomerBased(string $siteCategory): bool
    {
        return in_array($siteCategory, self::CUSTOMER_BASED, true);
    }

    /**
     * 업종이 공간 중심인지 확인
     */
    public static function isSpaceBased(string $siteCategory): bool
    {
        return in_array($siteCategory, self::SPACE_BASED, true);
    }

    /**
     * POS 모드 문자열 반환
     */
    public static function getPosMode(string $siteCategory): string
    {
        return self::isSpaceBased($siteCategory) ? 'space' : 'customer';
    }

    /**
     * 업종별 그룹 정보
     * @return array ['customer' => [...categories], 'space' => [...categories]]
     */
    public static function getCategoryGroups(): array
    {
        return [
            'customer' => self::CUSTOMER_BASED,
            'space'    => self::SPACE_BASED,
        ];
    }
}
