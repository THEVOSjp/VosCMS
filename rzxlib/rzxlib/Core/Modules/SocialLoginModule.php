<?php
/**
 * RezlyX Social Login Module
 * DB에서 활성화된 소셜 로그인 제공자 목록을 반환하는 모듈
 * 모든 스킨에서 동일한 소셜 로그인 데이터를 사용할 수 있도록 함
 */

namespace RzxLib\Core\Modules;

class SocialLoginModule
{
    /** @var array 지원하는 소셜 로그인 제공자 목록 */
    private static array $allProviders = [
        'google', 'kakao', 'line', 'naver', 'apple', 'facebook'
    ];

    /**
     * 소셜 로그인 모듈 데이터 반환
     *
     * @param array $siteSettings DB의 rzx_settings 데이터
     * @return array ['socialProviders' => [...], 'socialEnabled' => bool]
     */
    public static function getData(array $siteSettings): array
    {
        $enabled = ($siteSettings['member_social_login_enabled'] ?? '0') === '1';
        $providers = [];

        if ($enabled) {
            foreach (self::$allProviders as $provider) {
                if (($siteSettings['member_social_' . $provider] ?? '0') === '1') {
                    $providers[] = $provider;
                }
            }
        }

        return [
            'socialProviders' => $providers,
            'socialEnabled' => $enabled,
        ];
    }
}
