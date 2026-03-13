<?php
/**
 * Compliance (Data Management Guide) - Turkish
 */
return [
    'data_type' => [
        'reservation' => 'Rezervasyon bilgileri (ad, iletişim, tarih/saat)',
        'payment' => 'Ödeme makbuzları / satış fişleri',
        'cash_receipt' => 'Nakit makbuz düzenleme kayıtları',
        'customer_card' => 'Müşteri kartı (tedavi geçmişi vb.)',
        'medical_record' => 'Tıbbi kayıtlar',
        'treatment_history' => 'Tedavi geçmişi (kimyasallar, alerjiler vb.)',
        'allergy_info' => 'Alerji bilgileri',
        'guest_register' => 'Misafir kayıt defteri',
        'receipt' => 'Makbuzlar / fişler',
        'karte' => 'Müşteri klinik kartı (tedavi, kimyasallar, alerjiler)',
        'allergy_health' => 'Sağlık / alerji bilgileri',
    ],
    'kr' => [
        'law' => [
            'privacy' => 'Kişisel Bilgilerin Korunması Kanunu (개인정보보호법)',
            'vat' => 'Katma Değer Vergisi Kanunu (부가가치세법)',
            'income_tax' => 'Gelir Vergisi Kanunu (소득세법)',
            'electronic_commerce' => 'Elektronik Ticaret Kanunu (전자상거래법)',
            'medical' => 'Tıbbi Hizmetler Kanunu (의료법)',
            'tourism' => 'Turizm Teşvik Kanunu (관광진흥법)',
        ],
        'retention' => [
            'after_purpose' => 'Amaç gerçekleştirildikten sonra derhal silinir',
            '5years' => '5 yıl',
            '10years' => '10 yıl',
            '3years' => '3 yıl',
            'consent_period' => 'Açık onayda belirtilen süre',
        ],
        'note' => [
            'reservation' => 'Tamamlanmış müşteri bilgilerini vergi kayıtlarından ayrı yönetin',
            'payment' => 'Satış belgelendirme amaçlarıyla saklanmalıdır',
            'customer_card' => 'Kişisel veri toplama için onay formu gereklidir; saklama süresi belirtilmelidir',
            'medical_record' => 'Tıbbi kayıtlar Tıbbi Hizmetler Kanunu kapsamında saklanmalıdır',
            'treatment_history' => 'Düzenli müşteri yönetimi için kullanılıyorsa onay formu gereklidir',
            'allergy_info' => 'Sağlıkla ilgili bilgiler özel işlem gerektirir',
            'guest_register' => 'Konaklama işletmesi kanunu kapsamında saklama gereklidir',
        ],
        'tip' => [
            'purpose_delete' => 'Bir ziyaretten sonra müşteri verilerini gerekenden daha uzun süre saklamak başlı başına bir yasal ihlal oluşturabilir.',
            'tax_separate' => 'Vergi kayıtları (satış belgeleri) ve müşteri kişisel verileri ayrı yönetilmelidir.',
            'platform_booking' => 'Platform rezervasyonları için (örn. Naver, Kakao), platform tarafından yönetilen veriler ve kendi yönettiğiniz veriler ayrıdır.',
            'beauty_consent' => 'Düzenli müşteri yönetimi için müşteri kartları tutuyorsanız, onay almanız ve saklama süresini belirtmeniz gerekir.',
            'medical_strict' => 'Tıbbi kayıtlar Tıbbi Hizmetler Kanunu kapsamında sıkı bir şekilde saklanmalıdır; minimum 10 yıllık saklama yükümlülüğü vardır.',
            'food_allergy' => 'Müşteri alerji bilgileri hassas veri olarak sınıflandırılır ve daha sıkı yönetim gerektirir.',
        ],
        'ref' => [
            'pipc' => 'Kişisel Bilgileri Koruma Komisyonu (PIPC)',
            'law' => 'Kore Mevzuat Araştırma Enstitüsü',
        ],
    ],
    'jp' => [
        'law' => [
            'privacy' => 'Kişisel Bilgilerin Korunması Kanunu (個人情報保護法)',
            'corporate_tax' => 'Kurumlar Vergisi Kanunu (法人税法)',
            'medical_practitioners' => 'Tıp Uygulayıcıları Kanunu (医師法)',
            'food_sanitation' => 'Gıda Hijyeni Kanunu (食品衛生法)',
            'inn_act' => 'Konaklama İşletmesi Kanunu (旅館業法)',
        ],
        'retention' => [
            'after_purpose' => 'Amaç gerçekleştirildikten sonra silinir',
            '7years' => '7 yıl',
            '5years' => '5 yıl',
            '3years' => '3 yıl',
            'consent_period' => 'Açık onayda belirtilen süre',
            'careful' => 'Hassas kişisel bilgi olarak sıkı yönetim',
        ],
        'note' => [
            'reservation' => 'Rezervasyon tamamlandıktan sonra kişisel bilgilerin erken silinmesi ilkedir',
            'payment' => 'Satışla ilgili defterler olarak saklama yükümlülüğü',
            'customer_card' => 'Kişisel bilgilerin işlenmesi için onay gereklidir',
            'medical_record' => 'Tıbbi kayıtlar Tıp Uygulayıcıları Kanunu kapsamında saklanmalıdır',
            'karte' => 'Klinik kart (tedavi geçmişi, kimyasal bilgiler, alerjiler) yarı-tıbbi niteliktedir ve dikkatli yönetim gerektirir',
            'sensitive_info' => 'Hassas kişisel bilgi (sağlık verileri) olarak özellikle sıkı işlem gerektirir',
            'food_allergy' => 'Alerji bilgileri sağlıkla ilgili veri olarak dikkatle yönetilmelidir',
            'guest_register' => 'Konaklama İşletmesi Kanunu kapsamında misafir kayıt defteri saklama yükümlülüğü',
        ],
        'tip' => [
            'purpose_delete' => 'Bir rezervasyondan sonra müşteri verilerini gerekenden daha uzun süre saklamak başlı başına bir yasal ihlal oluşturabilir.',
            'tax_separate' => 'Vergi kayıtları (satış belgeleri) ve müşteri kişisel verileri ayrı yönetilmelidir.',
            'karte_caution' => 'Müşteri klinik kartı (tedavi geçmişi, kimyasal bilgiler, alerjiler) yarı-tıbbi niteliktedir ve daha dikkatli yönetim gerektirir.',
            'sensitive_info' => 'İlaç alerjileri gibi sağlıkla ilgili bilgiler hassas kişisel bilgi olarak sınıflandırılır ve daha sıkı işlem gerektirir.',
            'medical_strict' => 'Tıbbi kayıtlar Tıp Uygulayıcıları Kanunu kapsamında sıkı bir şekilde saklanmalıdır; minimum 5 yıllık saklama yükümlülüğü vardır.',
        ],
        'ref' => [
            'ppc' => 'Kişisel Bilgileri Koruma Komisyonu (PPC)',
            'e_gov' => 'e-Gov Kanun ve Yönetmelik Arama',
        ],
    ],
    'default' => [
        'retention' => [
            'check_local' => 'Yerel yasal gereksinimleri kontrol edin',
        ],
        'basis' => [
            'local_privacy' => 'Yerel gizlilik koruma kanunu',
            'local_tax' => 'Yerel vergi kanunu',
        ],
        'tip' => [
            'check_local_law' => 'Ülkenizdeki gizlilik koruma ve veri saklama yasalarını mutlaka kontrol edin.',
            'minimize_data' => 'Topladığınız kişisel verileri en aza indirin ve gereksiz bilgileri derhal silin.',
            'get_consent' => 'Müşteri verilerini toplarken her zaman onay alın ve saklama süresini belirtin.',
        ],
    ],
];
