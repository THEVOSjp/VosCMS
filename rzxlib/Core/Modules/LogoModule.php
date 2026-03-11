<?php
/**
 * RezlyX Logo Module
 * DB에서 로고/사이트명 설정을 읽어 반환하는 모듈
 * 모든 스킨에서 동일한 로고 데이터를 사용할 수 있도록 함
 */

namespace RzxLib\Core\Modules;

class LogoModule
{
    /**
     * 로고 모듈 데이터 반환
     *
     * @param array $siteSettings DB의 rzx_settings 데이터
     * @param string $appName config에서 가져온 앱 이름 (폴백용)
     * @return array ['siteName' => '...', 'logoType' => '...', 'logoImage' => '...']
     */
    public static function getData(array $siteSettings, string $appName = 'RezlyX'): array
    {
        return [
            'siteName' => $siteSettings['site_name'] ?? $appName,
            'logoType' => $siteSettings['logo_type'] ?? 'text',
            'logoImage' => $siteSettings['logo_image'] ?? '',
        ];
    }
}
