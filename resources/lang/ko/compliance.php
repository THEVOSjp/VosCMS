<?php
/**
 * 컴플라이언스 (데이터 관리 가이드) 번역 - 한국어
 */
return [
    // 데이터 유형
    'data_type' => [
        'reservation' => '예약 정보 (이름, 연락처, 일시)',
        'payment' => '결제 영수증·매출 전표',
        'cash_receipt' => '현금영수증 발행 기록',
        'customer_card' => '고객 카드 (시술 이력 등)',
        'medical_record' => '의료 기록 (진료 기록부)',
        'treatment_history' => '시술 이력 (약제, 알레르기 등)',
        'allergy_info' => '알레르기 정보',
        'guest_register' => '숙박자 명부',
        'receipt' => '영수증·증빙',
        'karte' => '고객 카르테 (시술·약제·알레르기)',
        'allergy_health' => '건강·알레르기 정보',
    ],

    // === 한국 (KR) ===
    'kr' => [
        'law' => [
            'privacy' => '개인정보보호법',
            'vat' => '부가가치세법',
            'income_tax' => '소득세법',
            'electronic_commerce' => '전자상거래법',
            'medical' => '의료법',
            'tourism' => '관광진흥법',
        ],
        'retention' => [
            'after_purpose' => '목적 달성 후 즉시 삭제',
            '5years' => '5년',
            '10years' => '10년',
            '3years' => '3년',
            'consent_period' => '명시적 동의 받은 기간',
        ],
        'note' => [
            'reservation' => '방문 완료된 고객 정보는 세무 목적과 분리하여 관리',
            'payment' => '매출 증빙 목적으로 보관 필수',
            'customer_card' => '개인정보 수집·이용 동의서 필요, 보관 기간 명시',
            'medical_record' => '진료 기록부는 의료법에 의해 보관 의무',
            'treatment_history' => '단골 관리 목적이라면 동의서 필수',
            'allergy_info' => '건강 관련 정보로 특별 관리 필요',
            'guest_register' => '숙박업법에 따른 보관 의무',
        ],
        'tip' => [
            'purpose_delete' => '예약 후 방문 완료된 고객 정보는 필요 이상으로 오래 보관하는 것 자체가 법 위반이 될 수 있습니다.',
            'tax_separate' => '세무 목적(매출 증빙)과 고객 개인정보는 분리해서 관리해야 합니다.',
            'platform_booking' => '네이버 예약, 카카오 예약 등 플랫폼 예약은 플랫폼 측 관리 부분과 직접 관리 부분이 분리됩니다.',
            'beauty_consent' => '고객 카드(단골 관리용)를 운영한다면, 개인정보 수집·이용 동의서를 받고 보관 기간을 명시해야 합니다.',
            'medical_strict' => '진료 기록부는 의료법에 의해 엄격히 보관해야 하며, 최소 10년 보관 의무가 있습니다.',
            'food_allergy' => '고객 알레르기 정보를 수집하는 경우, 민감 정보로 분류되어 더 엄격한 관리가 필요합니다.',
        ],
        'ref' => [
            'pipc' => '개인정보보호위원회 (PIPC)',
            'law' => '국가법령정보센터',
        ],
    ],

    // === 일본 (JP) ===
    'jp' => [
        'law' => [
            'privacy' => '개인정보보호법 (個人情報保護法)',
            'corporate_tax' => '법인세법 (法人税法)',
            'medical_practitioners' => '의사법 (医師法)',
            'food_sanitation' => '식품위생법 (食品衛生法)',
            'inn_act' => '여관업법 (旅館業法)',
        ],
        'retention' => [
            'after_purpose' => '목적 달성 후 삭제',
            '7years' => '7년',
            '5years' => '5년',
            '3years' => '3년',
            'consent_period' => '명시적 동의를 받은 기간',
            'careful' => '요배려 개인정보로서 엄격 관리',
        ],
        'note' => [
            'reservation' => '예약 완료 후 개인정보는 조기 삭제가 원칙',
            'payment' => '매출 관련 장부로서 보관 의무',
            'customer_card' => '개인정보 취급에 관한 동의 필요',
            'medical_record' => '진료 기록은 의사법에 의해 보관 의무',
            'karte' => '카르테(시술 이력, 약제 정보, 알레르기 등)는 준의료적 성격이 있어 신중히 관리',
            'sensitive_info' => '요배려 개인정보(건강 정보 등)로서 특히 엄격한 취급이 필요',
            'food_allergy' => '알레르기 정보는 건강 관련 정보로서 신중히 관리',
            'guest_register' => '여관업법에 따른 숙박자 명부 보관 의무',
        ],
        'tip' => [
            'purpose_delete' => '예약 완료 후 고객 정보를 필요 이상으로 오래 보관하는 것 자체가 법률 위반이 될 수 있습니다.',
            'tax_separate' => '세무 목적(매출 증빙)과 고객 개인정보는 분리하여 관리해야 합니다.',
            'karte_caution' => '고객 카르테(시술 이력, 약제 정보, 알레르기 등)는 준의료적 성격이 있어 보다 신중한 관리가 필요합니다.',
            'sensitive_info' => '약제 알레르기 등 건강 관련 정보는 요배려 개인정보로 분류되어 더 엄격한 취급이 요구됩니다.',
            'medical_strict' => '진료 기록은 의사법에 의해 엄격히 보관할 의무가 있으며, 최소 5년간 보관이 필요합니다.',
        ],
        'ref' => [
            'ppc' => '개인정보보호위원회 (PPC)',
            'e_gov' => 'e-Gov 법령검색',
        ],
    ],

    // === 기본 (기타 국가) ===
    'default' => [
        'retention' => [
            'check_local' => '해당 국가 법률 확인 필요',
        ],
        'basis' => [
            'local_privacy' => '해당 국가 개인정보보호법',
            'local_tax' => '해당 국가 세법',
        ],
        'tip' => [
            'check_local_law' => '운영 국가의 개인정보보호법 및 데이터 보관 관련 법률을 반드시 확인하세요.',
            'minimize_data' => '수집하는 개인정보는 최소한으로 제한하고, 불필요한 정보는 즉시 삭제하세요.',
            'get_consent' => '고객 정보를 수집할 때는 반드시 동의를 받고, 보관 기간을 명시하세요.',
        ],
    ],
];
