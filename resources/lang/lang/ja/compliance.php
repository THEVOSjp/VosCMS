<?php
/**
 * コンプライアンス（データ管理ガイド） - 日本語
 */
return [
    'data_type' => [
        'reservation' => '予約情報（氏名、連絡先、日時）',
        'payment' => '決済領収書・売上伝票',
        'cash_receipt' => '現金領収書発行記録',
        'customer_card' => '顧客カード（施術履歴等）',
        'medical_record' => '診療記録（カルテ）',
        'treatment_history' => '施術履歴（薬剤、アレルギー等）',
        'allergy_info' => 'アレルギー情報',
        'guest_register' => '宿泊者名簿',
        'receipt' => '領収書・証憑',
        'karte' => '顧客カルテ（施術・薬剤・アレルギー）',
        'allergy_health' => '健康・アレルギー情報',
    ],
    'kr' => [
        'law' => [
            'privacy' => '個人情報保護法（개인정보보호법）',
            'vat' => '付加価値税法（부가가치세법）',
            'income_tax' => '所得税法（소득세법）',
            'electronic_commerce' => '電子商取引法（전자상거래법）',
            'medical' => '医療法（의료법）',
            'tourism' => '観光振興法（관광진흥법）',
        ],
        'retention' => [
            'after_purpose' => '目的達成後直ちに削除',
            '5years' => '5年',
            '10years' => '10年',
            '3years' => '3年',
            'consent_period' => '明示的同意を得た期間',
        ],
        'note' => [
            'reservation' => '来店完了した顧客情報は税務目的と分離して管理',
            'payment' => '売上証憑目的で保管必須',
            'customer_card' => '個人情報収集・利用同意書が必要、保管期間の明示が必要',
            'medical_record' => '診療記録は医療法により保管義務',
            'treatment_history' => 'リピーター管理目的なら同意書必須',
            'allergy_info' => '健康関連情報として特別管理が必要',
            'guest_register' => '宿泊業法に基づく保管義務',
        ],
        'tip' => [
            'purpose_delete' => '来店完了後の顧客情報を必要以上に長期保管すること自体が法律違反になる可能性があります。',
            'tax_separate' => '税務目的（売上証憑）と顧客個人情報は分離して管理する必要があります。',
            'platform_booking' => 'プラットフォーム予約（Naver、Kakao等）は、プラットフォーム側の管理部分と直接管理部分が分離されます。',
            'beauty_consent' => '顧客カード（リピーター管理用）を運営する場合、個人情報収集・利用同意書を取得し保管期間を明示する必要があります。',
            'medical_strict' => '診療記録は医療法により厳格に保管する義務があり、最低10年間の保管義務があります。',
            'food_allergy' => '顧客のアレルギー情報は機微情報に分類され、より厳格な管理が必要です。',
        ],
        'ref' => [
            'pipc' => '個人情報保護委員会（PIPC）',
            'law' => '国家法令情報センター',
        ],
    ],
    'jp' => [
        'law' => [
            'privacy' => '個人情報保護法',
            'corporate_tax' => '法人税法',
            'medical_practitioners' => '医師法',
            'food_sanitation' => '食品衛生法',
            'inn_act' => '旅館業法',
        ],
        'retention' => [
            'after_purpose' => '目的達成後削除',
            '7years' => '7年',
            '5years' => '5年',
            '3years' => '3年',
            'consent_period' => '明示的同意を得た期間',
            'careful' => '要配慮個人情報として厳格管理',
        ],
        'note' => [
            'reservation' => '予約完了後の個人情報は早期削除が原則',
            'payment' => '売上関連帳簿として保管義務',
            'customer_card' => '個人情報取扱いに関する同意が必要',
            'medical_record' => '診療録は医師法により保管義務',
            'karte' => 'カルテ（施術履歴、薬剤情報、アレルギー等）は準医療的性格があり慎重に管理',
            'sensitive_info' => '要配慮個人情報（健康情報等）として特に厳格な取扱いが必要',
            'food_allergy' => 'アレルギー情報は健康関連情報として慎重に管理',
            'guest_register' => '旅館業法に基づく宿泊者名簿の保管義務',
        ],
        'tip' => [
            'purpose_delete' => '予約完了後の顧客情報は、必要以上に長期保管すること自体が法律違反になる可能性があります。',
            'tax_separate' => '税務目的（売上証憑）と顧客個人情報は分離して管理する必要があります。',
            'karte_caution' => '顧客カルテ（施術履歴、薬剤情報、アレルギー等）は準医療的性格があり、より慎重な管理が必要です。',
            'sensitive_info' => '薬剤アレルギー等の健康関連情報は要配慮個人情報に分類され、より厳格な取扱いが求められます。',
            'medical_strict' => '診療録は医師法により厳格に保管する義務があり、最低5年間の保管が必要です。',
        ],
        'ref' => [
            'ppc' => '個人情報保護委員会（PPC）',
            'e_gov' => 'e-Gov法令検索',
        ],
    ],
    'default' => [
        'retention' => [
            'check_local' => '該当国の法律を確認してください',
        ],
        'basis' => [
            'local_privacy' => '該当国の個人情報保護法',
            'local_tax' => '該当国の税法',
        ],
        'tip' => [
            'check_local_law' => '運営国の個人情報保護法およびデータ保管関連法律を必ず確認してください。',
            'minimize_data' => '収集する個人情報は最小限に制限し、不要な情報は直ちに削除してください。',
            'get_consent' => '顧客情報を収集する際は必ず同意を得て、保管期間を明示してください。',
        ],
    ],
];
