<?php
/**
 * Compliance (Data Management Guide) - German
 */
return [
    'data_type' => [
        'reservation' => 'Reservierungsinformationen (Name, Kontakt, Datum/Uhrzeit)',
        'payment' => 'Zahlungsbelege / Verkaufsbelege',
        'cash_receipt' => 'Barquittungsausstellungsunterlagen',
        'customer_card' => 'Kundenkarte (Behandlungsverlauf usw.)',
        'medical_record' => 'Medizinische Unterlagen',
        'treatment_history' => 'Behandlungsverlauf (Chemikalien, Allergien usw.)',
        'allergy_info' => 'Allergieinformationen',
        'guest_register' => 'Gästeregister',
        'receipt' => 'Quittungen / Belege',
        'karte' => 'Kundenkartei (Behandlung, Chemikalien, Allergien)',
        'allergy_health' => 'Gesundheits- / Allergieinformationen',
    ],
    'kr' => [
        'law' => [
            'privacy' => 'Gesetz zum Schutz personenbezogener Daten (개인정보보호법)',
            'vat' => 'Umsatzsteuergesetz (부가가치세법)',
            'income_tax' => 'Einkommensteuergesetz (소득세법)',
            'electronic_commerce' => 'Gesetz über den elektronischen Handel (전자상거래법)',
            'medical' => 'Medizingesetz (의료법)',
            'tourism' => 'Tourismusförderungsgesetz (관광진흥법)',
        ],
        'retention' => [
            'after_purpose' => 'Sofort nach Zweckerfüllung löschen',
            '5years' => '5 Jahre',
            '10years' => '10 Jahre',
            '3years' => '3 Jahre',
            'consent_period' => 'In der ausdrücklichen Einwilligung angegebener Zeitraum',
        ],
        'note' => [
            'reservation' => 'Abgeschlossene Kundendaten getrennt von Steuerunterlagen verwalten',
            'payment' => 'Muss für Verkaufsdokumentationszwecke aufbewahrt werden',
            'customer_card' => 'Einwilligungsformular für die Erhebung personenbezogener Daten erforderlich; Aufbewahrungsfrist muss angegeben werden',
            'medical_record' => 'Medizinische Unterlagen müssen gemäß dem Medizingesetz aufbewahrt werden',
            'treatment_history' => 'Einwilligungsformular erforderlich bei Verwendung für Stammkundenverwaltung',
            'allergy_info' => 'Gesundheitsbezogene Informationen erfordern besondere Behandlung',
            'guest_register' => 'Aufbewahrung gemäß Beherbergungsgewerberecht erforderlich',
        ],
        'tip' => [
            'purpose_delete' => 'Die Aufbewahrung von Kundendaten über die erforderliche Zeit nach einem Besuch hinaus kann selbst einen Rechtsverstoß darstellen.',
            'tax_separate' => 'Steuerunterlagen (Verkaufsdokumentation) und personenbezogene Kundendaten müssen getrennt verwaltet werden.',
            'platform_booking' => 'Bei Plattformbuchungen (z.B. Naver, Kakao) sind plattformverwaltete und selbstverwaltete Daten getrennt.',
            'beauty_consent' => 'Wenn Kundenkarten für die Stammkundenverwaltung geführt werden, müssen Sie eine Einwilligung einholen und die Aufbewahrungsfrist angeben.',
            'medical_strict' => 'Medizinische Unterlagen müssen gemäß dem Medizingesetz streng aufbewahrt werden, mit einer Mindestaufbewahrungspflicht von 10 Jahren.',
            'food_allergy' => 'Allergiedaten von Kunden werden als sensible Daten eingestuft und erfordern eine strengere Verwaltung.',
        ],
        'ref' => [
            'pipc' => 'Kommission zum Schutz personenbezogener Daten (PIPC)',
            'law' => 'Koreanisches Institut für Gesetzgebungsforschung',
        ],
    ],
    'jp' => [
        'law' => [
            'privacy' => 'Gesetz zum Schutz personenbezogener Daten (個人情報保護法)',
            'corporate_tax' => 'Körperschaftsteuergesetz (法人税法)',
            'medical_practitioners' => 'Ärztegesetz (医師法)',
            'food_sanitation' => 'Lebensmittelhygienegesetz (食品衛生法)',
            'inn_act' => 'Beherbergungsgewerbegesetz (旅館業法)',
        ],
        'retention' => [
            'after_purpose' => 'Nach Zweckerfüllung löschen',
            '7years' => '7 Jahre',
            '5years' => '5 Jahre',
            '3years' => '3 Jahre',
            'consent_period' => 'In der ausdrücklichen Einwilligung angegebener Zeitraum',
            'careful' => 'Strenge Verwaltung als sensible personenbezogene Daten',
        ],
        'note' => [
            'reservation' => 'Frühzeitige Löschung personenbezogener Daten nach Reservierungsabschluss ist das Prinzip',
            'payment' => 'Aufbewahrungspflicht als verkaufsbezogene Bücher',
            'customer_card' => 'Einwilligung zur Verarbeitung personenbezogener Daten erforderlich',
            'medical_record' => 'Medizinische Unterlagen müssen gemäß dem Ärztegesetz aufbewahrt werden',
            'karte' => 'Kartei (Behandlungsverlauf, Chemikalieninformationen, Allergien) hat quasi-medizinischen Charakter und erfordert sorgfältige Verwaltung',
            'sensitive_info' => 'Erfordert besonders strenge Behandlung als sensible personenbezogene Daten (Gesundheitsdaten)',
            'food_allergy' => 'Allergieinformationen müssen als gesundheitsbezogene Daten sorgfältig verwaltet werden',
            'guest_register' => 'Aufbewahrungspflicht des Gästeregisters gemäß dem Beherbergungsgewerbegesetz',
        ],
        'tip' => [
            'purpose_delete' => 'Die Aufbewahrung von Kundendaten über die erforderliche Zeit nach einer Reservierung hinaus kann selbst einen Rechtsverstoß darstellen.',
            'tax_separate' => 'Steuerunterlagen (Verkaufsdokumentation) und personenbezogene Kundendaten müssen getrennt verwaltet werden.',
            'karte_caution' => 'Kundenkartei (Behandlungsverlauf, Chemikalieninformationen, Allergien) hat quasi-medizinischen Charakter und erfordert sorgfältigere Verwaltung.',
            'sensitive_info' => 'Gesundheitsbezogene Informationen wie Arzneimittelallergien werden als sensible personenbezogene Daten eingestuft und erfordern strengere Behandlung.',
            'medical_strict' => 'Medizinische Unterlagen müssen gemäß dem Ärztegesetz streng aufbewahrt werden, mit einer Mindestaufbewahrungspflicht von 5 Jahren.',
        ],
        'ref' => [
            'ppc' => 'Kommission zum Schutz personenbezogener Daten (PPC)',
            'e_gov' => 'e-Gov Gesetzes- und Verordnungssuche',
        ],
    ],
    'default' => [
        'retention' => [
            'check_local' => 'Prüfen Sie die lokalen gesetzlichen Anforderungen',
        ],
        'basis' => [
            'local_privacy' => 'Lokales Datenschutzgesetz',
            'local_tax' => 'Lokales Steuergesetz',
        ],
        'tip' => [
            'check_local_law' => 'Überprüfen Sie unbedingt die Datenschutz- und Datenaufbewahrungsgesetze Ihres Landes.',
            'minimize_data' => 'Minimieren Sie die erhobenen personenbezogenen Daten und löschen Sie unnötige Informationen sofort.',
            'get_consent' => 'Holen Sie bei der Erhebung von Kundendaten stets eine Einwilligung ein und geben Sie die Aufbewahrungsfrist an.',
        ],
    ],
];
