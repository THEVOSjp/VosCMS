<?php
/**
 * RezlyX - 국가별/업종별 데이터 관리 가이드 (컴플라이언스)
 *
 * 지원 국가: KR(한국), JP(일본) — 상세 데이터
 * 기타 국가: 기본 템플릿 제공
 */

namespace RzxLib\Core\Data;

class ComplianceData
{
    /**
     * 국가별 데이터 가져오기
     * @param string $countryCode 국가 코드 (KR, JP 등)
     * @param string $category 업종 코드 (beauty_salon, hospital 등)
     * @return array
     */
    public static function get(string $countryCode, string $category = ''): array
    {
        $method = 'get' . $countryCode;
        if (method_exists(self::class, $method)) {
            return self::$method($category);
        }
        return self::getDefault($countryCode, $category);
    }

    /**
     * 지원되는 상세 국가 목록
     */
    public static function getSupportedCountries(): array
    {
        return ['KR', 'JP'];
    }

    /**
     * 업종 그룹 매핑 (유사 업종 통합)
     */
    private static function getCategoryGroup(string $category): string
    {
        $groups = [
            'beauty' => ['beauty_salon', 'nail_salon', 'skincare', 'massage'],
            'medical' => ['hospital', 'dental'],
            'food' => ['restaurant'],
            'accommodation' => ['accommodation'],
            'general' => ['studio', 'sports', 'education', 'consulting', 'pet', 'car', 'other'],
        ];
        foreach ($groups as $group => $cats) {
            if (in_array($category, $cats)) return $group;
        }
        return 'general';
    }

    // =========================================================================
    // 한국 (KR)
    // =========================================================================
    private static function getKR(string $category): array
    {
        $group = self::getCategoryGroup($category);
        $data = [
            'country_code' => 'KR',
            'country_flag' => '🇰🇷',
            'laws' => self::getKRLaws(),
            'retention' => self::getKRRetention($group),
            'tips' => self::getKRTips($group),
            'references' => self::getKRReferences(),
        ];
        return $data;
    }

    private static function getKRLaws(): array
    {
        return [
            ['name' => 'compliance.kr.law.privacy', 'key' => 'privacy_act'],
            ['name' => 'compliance.kr.law.vat', 'key' => 'vat_act'],
            ['name' => 'compliance.kr.law.income_tax', 'key' => 'income_tax_act'],
            ['name' => 'compliance.kr.law.electronic_commerce', 'key' => 'ecommerce_act'],
            ['name' => 'compliance.kr.law.medical', 'key' => 'medical_act'],
        ];
    }

    private static function getKRRetention(string $group): array
    {
        $common = [
            [
                'category_key' => 'compliance.data_type.reservation',
                'retention_key' => 'compliance.kr.retention.after_purpose',
                'basis_key' => 'compliance.kr.law.privacy',
                'note_key' => 'compliance.kr.note.reservation',
            ],
            [
                'category_key' => 'compliance.data_type.payment',
                'retention_key' => 'compliance.kr.retention.5years',
                'basis_key' => 'compliance.kr.law.vat',
                'note_key' => 'compliance.kr.note.payment',
            ],
            [
                'category_key' => 'compliance.data_type.cash_receipt',
                'retention_key' => 'compliance.kr.retention.5years',
                'basis_key' => 'compliance.kr.law.income_tax',
                'note_key' => '',
            ],
            [
                'category_key' => 'compliance.data_type.customer_card',
                'retention_key' => 'compliance.kr.retention.consent_period',
                'basis_key' => 'compliance.kr.law.privacy',
                'note_key' => 'compliance.kr.note.customer_card',
            ],
        ];

        if ($group === 'medical') {
            $common[] = [
                'category_key' => 'compliance.data_type.medical_record',
                'retention_key' => 'compliance.kr.retention.10years',
                'basis_key' => 'compliance.kr.law.medical',
                'note_key' => 'compliance.kr.note.medical_record',
            ];
        }

        if ($group === 'beauty') {
            $common[] = [
                'category_key' => 'compliance.data_type.treatment_history',
                'retention_key' => 'compliance.kr.retention.consent_period',
                'basis_key' => 'compliance.kr.law.privacy',
                'note_key' => 'compliance.kr.note.treatment_history',
            ];
        }

        if ($group === 'food') {
            $common[] = [
                'category_key' => 'compliance.data_type.allergy_info',
                'retention_key' => 'compliance.kr.retention.consent_period',
                'basis_key' => 'compliance.kr.law.privacy',
                'note_key' => 'compliance.kr.note.allergy_info',
            ];
        }

        if ($group === 'accommodation') {
            $common[] = [
                'category_key' => 'compliance.data_type.guest_register',
                'retention_key' => 'compliance.kr.retention.3years',
                'basis_key' => 'compliance.kr.law.tourism',
                'note_key' => 'compliance.kr.note.guest_register',
            ];
        }

        return $common;
    }

    private static function getKRTips(string $group): array
    {
        $tips = [
            'compliance.kr.tip.purpose_delete',
            'compliance.kr.tip.tax_separate',
            'compliance.kr.tip.platform_booking',
        ];

        if ($group === 'beauty') {
            $tips[] = 'compliance.kr.tip.beauty_consent';
        }
        if ($group === 'medical') {
            $tips[] = 'compliance.kr.tip.medical_strict';
        }
        if ($group === 'food') {
            $tips[] = 'compliance.kr.tip.food_allergy';
        }
        return $tips;
    }

    private static function getKRReferences(): array
    {
        return [
            ['title_key' => 'compliance.kr.ref.pipc', 'url' => 'https://www.pipc.go.kr'],
            ['title_key' => 'compliance.kr.ref.law', 'url' => 'https://www.law.go.kr'],
        ];
    }

    // =========================================================================
    // 일본 (JP)
    // =========================================================================
    private static function getJP(string $category): array
    {
        $group = self::getCategoryGroup($category);
        return [
            'country_code' => 'JP',
            'country_flag' => '🇯🇵',
            'laws' => self::getJPLaws(),
            'retention' => self::getJPRetention($group),
            'tips' => self::getJPTips($group),
            'references' => self::getJPReferences(),
        ];
    }

    private static function getJPLaws(): array
    {
        return [
            ['name' => 'compliance.jp.law.privacy', 'key' => 'privacy_act'],
            ['name' => 'compliance.jp.law.corporate_tax', 'key' => 'corporate_tax_act'],
            ['name' => 'compliance.jp.law.medical_practitioners', 'key' => 'medical_practitioners_act'],
            ['name' => 'compliance.jp.law.food_sanitation', 'key' => 'food_sanitation_act'],
        ];
    }

    private static function getJPRetention(string $group): array
    {
        $common = [
            [
                'category_key' => 'compliance.data_type.reservation',
                'retention_key' => 'compliance.jp.retention.after_purpose',
                'basis_key' => 'compliance.jp.law.privacy',
                'note_key' => 'compliance.jp.note.reservation',
            ],
            [
                'category_key' => 'compliance.data_type.payment',
                'retention_key' => 'compliance.jp.retention.7years',
                'basis_key' => 'compliance.jp.law.corporate_tax',
                'note_key' => 'compliance.jp.note.payment',
            ],
            [
                'category_key' => 'compliance.data_type.receipt',
                'retention_key' => 'compliance.jp.retention.7years',
                'basis_key' => 'compliance.jp.law.corporate_tax',
                'note_key' => '',
            ],
            [
                'category_key' => 'compliance.data_type.customer_card',
                'retention_key' => 'compliance.jp.retention.consent_period',
                'basis_key' => 'compliance.jp.law.privacy',
                'note_key' => 'compliance.jp.note.customer_card',
            ],
        ];

        if ($group === 'medical') {
            $common[] = [
                'category_key' => 'compliance.data_type.medical_record',
                'retention_key' => 'compliance.jp.retention.5years',
                'basis_key' => 'compliance.jp.law.medical_practitioners',
                'note_key' => 'compliance.jp.note.medical_record',
            ];
        }

        if ($group === 'beauty') {
            $common[] = [
                'category_key' => 'compliance.data_type.karte',
                'retention_key' => 'compliance.jp.retention.consent_period',
                'basis_key' => 'compliance.jp.law.privacy',
                'note_key' => 'compliance.jp.note.karte',
            ];
            $common[] = [
                'category_key' => 'compliance.data_type.allergy_health',
                'retention_key' => 'compliance.jp.retention.careful',
                'basis_key' => 'compliance.jp.law.privacy',
                'note_key' => 'compliance.jp.note.sensitive_info',
            ];
        }

        if ($group === 'food') {
            $common[] = [
                'category_key' => 'compliance.data_type.allergy_info',
                'retention_key' => 'compliance.jp.retention.consent_period',
                'basis_key' => 'compliance.jp.law.food_sanitation',
                'note_key' => 'compliance.jp.note.food_allergy',
            ];
        }

        if ($group === 'accommodation') {
            $common[] = [
                'category_key' => 'compliance.data_type.guest_register',
                'retention_key' => 'compliance.jp.retention.3years',
                'basis_key' => 'compliance.jp.law.inn_act',
                'note_key' => 'compliance.jp.note.guest_register',
            ];
        }

        return $common;
    }

    private static function getJPTips(string $group): array
    {
        $tips = [
            'compliance.jp.tip.purpose_delete',
            'compliance.jp.tip.tax_separate',
        ];
        if ($group === 'beauty') {
            $tips[] = 'compliance.jp.tip.karte_caution';
            $tips[] = 'compliance.jp.tip.sensitive_info';
        }
        if ($group === 'medical') {
            $tips[] = 'compliance.jp.tip.medical_strict';
        }
        return $tips;
    }

    private static function getJPReferences(): array
    {
        return [
            ['title_key' => 'compliance.jp.ref.ppc', 'url' => 'https://www.ppc.go.jp'],
            ['title_key' => 'compliance.jp.ref.e_gov', 'url' => 'https://www.e-gov.go.jp'],
        ];
    }

    // =========================================================================
    // 기본 템플릿 (기타 국가)
    // =========================================================================
    private static function getDefault(string $countryCode, string $category): array
    {
        return [
            'country_code' => $countryCode,
            'country_flag' => '',
            'is_default' => true,
            'laws' => [],
            'retention' => [
                [
                    'category_key' => 'compliance.data_type.reservation',
                    'retention_key' => 'compliance.default.retention.check_local',
                    'basis_key' => 'compliance.default.basis.local_privacy',
                    'note_key' => '',
                ],
                [
                    'category_key' => 'compliance.data_type.payment',
                    'retention_key' => 'compliance.default.retention.check_local',
                    'basis_key' => 'compliance.default.basis.local_tax',
                    'note_key' => '',
                ],
                [
                    'category_key' => 'compliance.data_type.customer_card',
                    'retention_key' => 'compliance.default.retention.check_local',
                    'basis_key' => 'compliance.default.basis.local_privacy',
                    'note_key' => '',
                ],
            ],
            'tips' => [
                'compliance.default.tip.check_local_law',
                'compliance.default.tip.minimize_data',
                'compliance.default.tip.get_consent',
            ],
            'references' => [],
        ];
    }
}
