<?php
/**
 * Compliance (Data Management Guide) - English
 */
return [
    'data_type' => [
        'reservation' => 'Reservation info (name, contact, date/time)',
        'payment' => 'Payment receipts / sales slips',
        'cash_receipt' => 'Cash receipt issuance records',
        'customer_card' => 'Customer card (treatment history, etc.)',
        'medical_record' => 'Medical records',
        'treatment_history' => 'Treatment history (chemicals, allergies, etc.)',
        'allergy_info' => 'Allergy information',
        'guest_register' => 'Guest register',
        'receipt' => 'Receipts / vouchers',
        'karte' => 'Customer karte (treatment, chemicals, allergies)',
        'allergy_health' => 'Health / allergy information',
    ],
    'kr' => [
        'law' => [
            'privacy' => 'Personal Information Protection Act (개인정보보호법)',
            'vat' => 'Value Added Tax Act (부가가치세법)',
            'income_tax' => 'Income Tax Act (소득세법)',
            'electronic_commerce' => 'Electronic Commerce Act (전자상거래법)',
            'medical' => 'Medical Service Act (의료법)',
            'tourism' => 'Tourism Promotion Act (관광진흥법)',
        ],
        'retention' => [
            'after_purpose' => 'Delete immediately after purpose is fulfilled',
            '5years' => '5 years',
            '10years' => '10 years',
            '3years' => '3 years',
            'consent_period' => 'Period specified in explicit consent',
        ],
        'note' => [
            'reservation' => 'Manage completed customer info separately from tax records',
            'payment' => 'Must be retained for sales documentation purposes',
            'customer_card' => 'Requires consent form for personal data collection; retention period must be specified',
            'medical_record' => 'Medical records must be retained under the Medical Service Act',
            'treatment_history' => 'Consent form required if used for regular customer management',
            'allergy_info' => 'Health-related info requires special handling',
            'guest_register' => 'Retention required under accommodation business law',
        ],
        'tip' => [
            'purpose_delete' => 'Retaining customer data longer than necessary after a visit may itself constitute a legal violation.',
            'tax_separate' => 'Tax records (sales documentation) and customer personal data must be managed separately.',
            'platform_booking' => 'For platform bookings (e.g., Naver, Kakao), platform-managed data and self-managed data are separate.',
            'beauty_consent' => 'If maintaining customer cards for regular client management, you must obtain consent and specify the retention period.',
            'medical_strict' => 'Medical records must be strictly retained under the Medical Service Act, with a minimum 10-year retention obligation.',
            'food_allergy' => 'Customer allergy information is classified as sensitive data and requires stricter management.',
        ],
        'ref' => [
            'pipc' => 'Personal Information Protection Commission (PIPC)',
            'law' => 'Korea Legislation Research Institute',
        ],
    ],
    'jp' => [
        'law' => [
            'privacy' => 'Act on the Protection of Personal Information (個人情報保護法)',
            'corporate_tax' => 'Corporation Tax Act (法人税法)',
            'medical_practitioners' => 'Medical Practitioners Act (医師法)',
            'food_sanitation' => 'Food Sanitation Act (食品衛生法)',
            'inn_act' => 'Inn Business Act (旅館業法)',
        ],
        'retention' => [
            'after_purpose' => 'Delete after purpose is fulfilled',
            '7years' => '7 years',
            '5years' => '5 years',
            '3years' => '3 years',
            'consent_period' => 'Period specified in explicit consent',
            'careful' => 'Strict management as sensitive personal information',
        ],
        'note' => [
            'reservation' => 'Early deletion of personal info after reservation completion is the principle',
            'payment' => 'Retention obligation as sales-related books',
            'customer_card' => 'Consent for handling personal information is required',
            'medical_record' => 'Medical records must be retained under the Medical Practitioners Act',
            'karte' => 'Karte (treatment history, chemical info, allergies) has quasi-medical nature and requires careful management',
            'sensitive_info' => 'Requires particularly strict handling as sensitive personal information (health data)',
            'food_allergy' => 'Allergy information must be carefully managed as health-related data',
            'guest_register' => 'Guest register retention obligation under the Inn Business Act',
        ],
        'tip' => [
            'purpose_delete' => 'Retaining customer data longer than necessary after a reservation may itself constitute a legal violation.',
            'tax_separate' => 'Tax records (sales documentation) and customer personal data must be managed separately.',
            'karte_caution' => 'Customer karte (treatment history, chemical info, allergies) has quasi-medical nature and requires more careful management.',
            'sensitive_info' => 'Health-related information such as drug allergies is classified as sensitive personal information and requires stricter handling.',
            'medical_strict' => 'Medical records must be strictly retained under the Medical Practitioners Act, with a minimum 5-year retention obligation.',
        ],
        'ref' => [
            'ppc' => 'Personal Information Protection Commission (PPC)',
            'e_gov' => 'e-Gov Laws & Regulations Search',
        ],
    ],
    'default' => [
        'retention' => [
            'check_local' => 'Check local laws for requirements',
        ],
        'basis' => [
            'local_privacy' => 'Local privacy protection law',
            'local_tax' => 'Local tax law',
        ],
        'tip' => [
            'check_local_law' => 'Be sure to check your country\'s privacy protection and data retention laws.',
            'minimize_data' => 'Minimize the personal data you collect and delete unnecessary information immediately.',
            'get_consent' => 'Always obtain consent when collecting customer data and specify the retention period.',
        ],
    ],
];
