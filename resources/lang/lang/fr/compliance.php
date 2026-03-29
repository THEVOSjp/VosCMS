<?php
/**
 * Compliance (Data Management Guide) - French
 */
return [
    'data_type' => [
        'reservation' => 'Informations de réservation (nom, contact, date/heure)',
        'payment' => 'Reçus de paiement / bordereaux de vente',
        'cash_receipt' => 'Registres d\'émission de reçus en espèces',
        'customer_card' => 'Fiche client (historique de traitement, etc.)',
        'medical_record' => 'Dossiers médicaux',
        'treatment_history' => 'Historique de traitement (produits chimiques, allergies, etc.)',
        'allergy_info' => 'Informations sur les allergies',
        'guest_register' => 'Registre des clients',
        'receipt' => 'Reçus / justificatifs',
        'karte' => 'Fiche clinique client (traitement, produits chimiques, allergies)',
        'allergy_health' => 'Informations de santé / allergies',
    ],
    'kr' => [
        'law' => [
            'privacy' => 'Loi sur la protection des informations personnelles (개인정보보호법)',
            'vat' => 'Loi sur la taxe sur la valeur ajoutée (부가가치세법)',
            'income_tax' => 'Loi sur l\'impôt sur le revenu (소득세법)',
            'electronic_commerce' => 'Loi sur le commerce électronique (전자상거래법)',
            'medical' => 'Loi sur les services médicaux (의료법)',
            'tourism' => 'Loi sur la promotion du tourisme (관광진흥법)',
        ],
        'retention' => [
            'after_purpose' => 'Supprimer immédiatement après l\'accomplissement de l\'objectif',
            '5years' => '5 ans',
            '10years' => '10 ans',
            '3years' => '3 ans',
            'consent_period' => 'Période spécifiée dans le consentement explicite',
        ],
        'note' => [
            'reservation' => 'Gérer les informations clients terminées séparément des dossiers fiscaux',
            'payment' => 'Doit être conservé à des fins de documentation des ventes',
            'customer_card' => 'Formulaire de consentement requis pour la collecte de données personnelles ; la période de conservation doit être spécifiée',
            'medical_record' => 'Les dossiers médicaux doivent être conservés en vertu de la Loi sur les services médicaux',
            'treatment_history' => 'Formulaire de consentement requis si utilisé pour la gestion des clients réguliers',
            'allergy_info' => 'Les informations liées à la santé nécessitent un traitement spécial',
            'guest_register' => 'Conservation requise en vertu de la loi sur les établissements d\'hébergement',
        ],
        'tip' => [
            'purpose_delete' => 'Conserver les données clients plus longtemps que nécessaire après une visite peut en soi constituer une violation légale.',
            'tax_separate' => 'Les dossiers fiscaux (documentation des ventes) et les données personnelles des clients doivent être gérés séparément.',
            'platform_booking' => 'Pour les réservations via plateformes (ex. Naver, Kakao), les données gérées par la plateforme et les données autogérées sont distinctes.',
            'beauty_consent' => 'Si vous maintenez des fiches clients pour la gestion des clients réguliers, vous devez obtenir un consentement et spécifier la période de conservation.',
            'medical_strict' => 'Les dossiers médicaux doivent être strictement conservés en vertu de la Loi sur les services médicaux, avec une obligation minimale de conservation de 10 ans.',
            'food_allergy' => 'Les informations sur les allergies des clients sont classées comme données sensibles et nécessitent une gestion plus stricte.',
        ],
        'ref' => [
            'pipc' => 'Commission de protection des informations personnelles (PIPC)',
            'law' => 'Institut coréen de recherche législative',
        ],
    ],
    'jp' => [
        'law' => [
            'privacy' => 'Loi sur la protection des informations personnelles (個人情報保護法)',
            'corporate_tax' => 'Loi sur l\'impôt sur les sociétés (法人税法)',
            'medical_practitioners' => 'Loi sur les praticiens médicaux (医師法)',
            'food_sanitation' => 'Loi sur l\'hygiène alimentaire (食品衛生法)',
            'inn_act' => 'Loi sur les établissements d\'hébergement (旅館業法)',
        ],
        'retention' => [
            'after_purpose' => 'Supprimer après l\'accomplissement de l\'objectif',
            '7years' => '7 ans',
            '5years' => '5 ans',
            '3years' => '3 ans',
            'consent_period' => 'Période spécifiée dans le consentement explicite',
            'careful' => 'Gestion stricte en tant qu\'informations personnelles sensibles',
        ],
        'note' => [
            'reservation' => 'La suppression précoce des informations personnelles après l\'achèvement de la réservation est le principe',
            'payment' => 'Obligation de conservation en tant que livres liés aux ventes',
            'customer_card' => 'Le consentement pour le traitement des informations personnelles est requis',
            'medical_record' => 'Les dossiers médicaux doivent être conservés en vertu de la Loi sur les praticiens médicaux',
            'karte' => 'La fiche clinique (historique de traitement, informations chimiques, allergies) a un caractère quasi-médical et nécessite une gestion prudente',
            'sensitive_info' => 'Nécessite un traitement particulièrement strict en tant qu\'informations personnelles sensibles (données de santé)',
            'food_allergy' => 'Les informations sur les allergies doivent être soigneusement gérées en tant que données liées à la santé',
            'guest_register' => 'Obligation de conservation du registre des clients en vertu de la Loi sur les établissements d\'hébergement',
        ],
        'tip' => [
            'purpose_delete' => 'Conserver les données clients plus longtemps que nécessaire après une réservation peut en soi constituer une violation légale.',
            'tax_separate' => 'Les dossiers fiscaux (documentation des ventes) et les données personnelles des clients doivent être gérés séparément.',
            'karte_caution' => 'La fiche clinique du client (historique de traitement, informations chimiques, allergies) a un caractère quasi-médical et nécessite une gestion plus prudente.',
            'sensitive_info' => 'Les informations liées à la santé telles que les allergies médicamenteuses sont classées comme informations personnelles sensibles et nécessitent un traitement plus strict.',
            'medical_strict' => 'Les dossiers médicaux doivent être strictement conservés en vertu de la Loi sur les praticiens médicaux, avec une obligation minimale de conservation de 5 ans.',
        ],
        'ref' => [
            'ppc' => 'Commission de protection des informations personnelles (PPC)',
            'e_gov' => 'Recherche de lois et règlements e-Gov',
        ],
    ],
    'default' => [
        'retention' => [
            'check_local' => 'Vérifiez les exigences légales locales',
        ],
        'basis' => [
            'local_privacy' => 'Loi locale de protection de la vie privée',
            'local_tax' => 'Loi fiscale locale',
        ],
        'tip' => [
            'check_local_law' => 'Assurez-vous de vérifier les lois de votre pays en matière de protection de la vie privée et de conservation des données.',
            'minimize_data' => 'Minimisez les données personnelles collectées et supprimez immédiatement les informations inutiles.',
            'get_consent' => 'Obtenez toujours le consentement lors de la collecte de données clients et spécifiez la période de conservation.',
        ],
    ],
];
