<?php
/**
 * Compliance (Data Management Guide) - Traditional Chinese
 */
return [
    'data_type' => [
        'reservation' => '預約資訊（姓名、聯絡方式、日期/時間）',
        'payment' => '付款收據 / 銷售單',
        'cash_receipt' => '現金收據開立記錄',
        'customer_card' => '客戶卡（治療記錄等）',
        'medical_record' => '醫療紀錄',
        'treatment_history' => '治療記錄（化學品、過敏等）',
        'allergy_info' => '過敏資訊',
        'guest_register' => '住客登記簿',
        'receipt' => '收據 / 憑證',
        'karte' => '客戶病歷卡（治療、化學品、過敏）',
        'allergy_health' => '健康 / 過敏資訊',
    ],
    'kr' => [
        'law' => [
            'privacy' => '個人資訊保護法（개인정보보호법）',
            'vat' => '增值稅法（부가가치세법）',
            'income_tax' => '所得稅法（소득세법）',
            'electronic_commerce' => '電子商務法（전자상거래법）',
            'medical' => '醫療法（의료법）',
            'tourism' => '觀光振興法（관광진흥법）',
        ],
        'retention' => [
            'after_purpose' => '目的達成後立即刪除',
            '5years' => '5年',
            '10years' => '10年',
            '3years' => '3年',
            'consent_period' => '明確同意中指定的期限',
        ],
        'note' => [
            'reservation' => '將已完成的客戶資訊與稅務記錄分開管理',
            'payment' => '必須為銷售憑證目的保留',
            'customer_card' => '需要個人資料收集同意書；必須指定保留期限',
            'medical_record' => '醫療紀錄必須根據醫療法保留',
            'treatment_history' => '如用於常客管理，需要同意書',
            'allergy_info' => '健康相關資訊需要特殊處理',
            'guest_register' => '根據住宿業法需要保留',
        ],
        'tip' => [
            'purpose_delete' => '在客戶來訪後保留超出必要時間的客戶資料本身可能構成法律違規。',
            'tax_separate' => '稅務記錄（銷售憑證）和客戶個人資料必須分開管理。',
            'platform_booking' => '對於平台預約（如Naver、Kakao），平台管理的資料和自行管理的資料是分開的。',
            'beauty_consent' => '如果為常客管理維護客戶卡，您必須取得同意並指定保留期限。',
            'medical_strict' => '醫療紀錄必須根據醫療法嚴格保留，最低保留義務為10年。',
            'food_allergy' => '客戶過敏資訊被歸類為敏感資料，需要更嚴格的管理。',
        ],
        'ref' => [
            'pipc' => '個人資訊保護委員會（PIPC）',
            'law' => '韓國法制研究院',
        ],
    ],
    'jp' => [
        'law' => [
            'privacy' => '個人資訊保護法（個人情報保護法）',
            'corporate_tax' => '法人稅法（法人税法）',
            'medical_practitioners' => '醫師法（医師法）',
            'food_sanitation' => '食品衛生法（食品衛生法）',
            'inn_act' => '旅館業法（旅館業法）',
        ],
        'retention' => [
            'after_purpose' => '目的達成後刪除',
            '7years' => '7年',
            '5years' => '5年',
            '3years' => '3年',
            'consent_period' => '明確同意中指定的期限',
            'careful' => '作為敏感個人資訊嚴格管理',
        ],
        'note' => [
            'reservation' => '預約完成後儘早刪除個人資訊是原則',
            'payment' => '作為銷售相關帳簿有保留義務',
            'customer_card' => '需要個人資訊處理同意',
            'medical_record' => '醫療紀錄必須根據醫師法保留',
            'karte' => '病歷卡（治療記錄、化學品資訊、過敏）具有準醫療性質，需要謹慎管理',
            'sensitive_info' => '作為敏感個人資訊（健康資料）需要特別嚴格處理',
            'food_allergy' => '過敏資訊作為健康相關資料必須謹慎管理',
            'guest_register' => '根據旅館業法有住客登記簿保留義務',
        ],
        'tip' => [
            'purpose_delete' => '在預約後保留超出必要時間的客戶資料本身可能構成法律違規。',
            'tax_separate' => '稅務記錄（銷售憑證）和客戶個人資料必須分開管理。',
            'karte_caution' => '客戶病歷卡（治療記錄、化學品資訊、過敏）具有準醫療性質，需要更謹慎的管理。',
            'sensitive_info' => '藥物過敏等健康相關資訊被歸類為敏感個人資訊，需要更嚴格的處理。',
            'medical_strict' => '醫療紀錄必須根據醫師法嚴格保留，最低保留義務為5年。',
        ],
        'ref' => [
            'ppc' => '個人資訊保護委員會（PPC）',
            'e_gov' => 'e-Gov法令檢索',
        ],
    ],
    'default' => [
        'retention' => [
            'check_local' => '請查閱當地法律要求',
        ],
        'basis' => [
            'local_privacy' => '當地隱私保護法',
            'local_tax' => '當地稅法',
        ],
        'tip' => [
            'check_local_law' => '請務必查閱您所在國家的隱私保護和資料保留法律。',
            'minimize_data' => '儘量減少收集的個人資料，並立即刪除不必要的資訊。',
            'get_consent' => '收集客戶資料時務必取得同意，並指定保留期限。',
        ],
    ],
];
