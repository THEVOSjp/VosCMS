<?php
/**
 * Compliance (Data Management Guide) - Simplified Chinese
 */
return [
    'data_type' => [
        'reservation' => '预约信息（姓名、联系方式、日期/时间）',
        'payment' => '付款收据 / 销售单',
        'cash_receipt' => '现金收据开具记录',
        'customer_card' => '客户卡（治疗记录等）',
        'medical_record' => '医疗记录',
        'treatment_history' => '治疗记录（化学品、过敏等）',
        'allergy_info' => '过敏信息',
        'guest_register' => '住客登记簿',
        'receipt' => '收据 / 凭证',
        'karte' => '客户病历卡（治疗、化学品、过敏）',
        'allergy_health' => '健康 / 过敏信息',
    ],
    'kr' => [
        'law' => [
            'privacy' => '个人信息保护法（개인정보보호법）',
            'vat' => '增值税法（부가가치세법）',
            'income_tax' => '所得税法（소득세법）',
            'electronic_commerce' => '电子商务法（전자상거래법）',
            'medical' => '医疗法（의료법）',
            'tourism' => '观光振兴法（관광진흥법）',
        ],
        'retention' => [
            'after_purpose' => '目的达成后立即删除',
            '5years' => '5年',
            '10years' => '10年',
            '3years' => '3年',
            'consent_period' => '明确同意中指定的期限',
        ],
        'note' => [
            'reservation' => '将已完成的客户信息与税务记录分开管理',
            'payment' => '必须为销售凭证目的保留',
            'customer_card' => '需要个人数据收集同意书；必须指定保留期限',
            'medical_record' => '医疗记录必须根据医疗法保留',
            'treatment_history' => '如用于常客管理，需要同意书',
            'allergy_info' => '健康相关信息需要特殊处理',
            'guest_register' => '根据住宿业法需要保留',
        ],
        'tip' => [
            'purpose_delete' => '在客户来访后保留超出必要时间的客户数据本身可能构成法律违规。',
            'tax_separate' => '税务记录（销售凭证）和客户个人数据必须分开管理。',
            'platform_booking' => '对于平台预约（如Naver、Kakao），平台管理的数据和自行管理的数据是分开的。',
            'beauty_consent' => '如果为常客管理维护客户卡，您必须获得同意并指定保留期限。',
            'medical_strict' => '医疗记录必须根据医疗法严格保留，最低保留义务为10年。',
            'food_allergy' => '客户过敏信息被归类为敏感数据，需要更严格的管理。',
        ],
        'ref' => [
            'pipc' => '个人信息保护委员会（PIPC）',
            'law' => '韩国法制研究院',
        ],
    ],
    'jp' => [
        'law' => [
            'privacy' => '个人信息保护法（個人情報保護法）',
            'corporate_tax' => '法人税法（法人税法）',
            'medical_practitioners' => '医师法（医師法）',
            'food_sanitation' => '食品卫生法（食品衛生法）',
            'inn_act' => '旅馆业法（旅館業法）',
        ],
        'retention' => [
            'after_purpose' => '目的达成后删除',
            '7years' => '7年',
            '5years' => '5年',
            '3years' => '3年',
            'consent_period' => '明确同意中指定的期限',
            'careful' => '作为敏感个人信息严格管理',
        ],
        'note' => [
            'reservation' => '预约完成后尽早删除个人信息是原则',
            'payment' => '作为销售相关账簿有保留义务',
            'customer_card' => '需要个人信息处理同意',
            'medical_record' => '医疗记录必须根据医师法保留',
            'karte' => '病历卡（治疗记录、化学品信息、过敏）具有准医疗性质，需要谨慎管理',
            'sensitive_info' => '作为敏感个人信息（健康数据）需要特别严格处理',
            'food_allergy' => '过敏信息作为健康相关数据必须谨慎管理',
            'guest_register' => '根据旅馆业法有住客登记簿保留义务',
        ],
        'tip' => [
            'purpose_delete' => '在预约后保留超出必要时间的客户数据本身可能构成法律违规。',
            'tax_separate' => '税务记录（销售凭证）和客户个人数据必须分开管理。',
            'karte_caution' => '客户病历卡（治疗记录、化学品信息、过敏）具有准医疗性质，需要更谨慎的管理。',
            'sensitive_info' => '药物过敏等健康相关信息被归类为敏感个人信息，需要更严格的处理。',
            'medical_strict' => '医疗记录必须根据医师法严格保留，最低保留义务为5年。',
        ],
        'ref' => [
            'ppc' => '个人信息保护委员会（PPC）',
            'e_gov' => 'e-Gov法令检索',
        ],
    ],
    'default' => [
        'retention' => [
            'check_local' => '请查阅当地法律要求',
        ],
        'basis' => [
            'local_privacy' => '当地隐私保护法',
            'local_tax' => '当地税法',
        ],
        'tip' => [
            'check_local_law' => '请务必查阅您所在国家的隐私保护和数据保留法律。',
            'minimize_data' => '尽量减少收集的个人数据，并立即删除不必要的信息。',
            'get_consent' => '收集客户数据时务必获得同意，并指定保留期限。',
        ],
    ],
];
