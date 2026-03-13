<?php
/**
 * Compliance (Data Management Guide) - Spanish
 */
return [
    'data_type' => [
        'reservation' => 'Información de reserva (nombre, contacto, fecha/hora)',
        'payment' => 'Recibos de pago / comprobantes de venta',
        'cash_receipt' => 'Registros de emisión de recibos de efectivo',
        'customer_card' => 'Ficha de cliente (historial de tratamiento, etc.)',
        'medical_record' => 'Registros médicos',
        'treatment_history' => 'Historial de tratamiento (químicos, alergias, etc.)',
        'allergy_info' => 'Información de alergias',
        'guest_register' => 'Registro de huéspedes',
        'receipt' => 'Recibos / comprobantes',
        'karte' => 'Ficha clínica del cliente (tratamiento, químicos, alergias)',
        'allergy_health' => 'Información de salud / alergias',
    ],
    'kr' => [
        'law' => [
            'privacy' => 'Ley de Protección de Información Personal (개인정보보호법)',
            'vat' => 'Ley del Impuesto al Valor Agregado (부가가치세법)',
            'income_tax' => 'Ley del Impuesto sobre la Renta (소득세법)',
            'electronic_commerce' => 'Ley de Comercio Electrónico (전자상거래법)',
            'medical' => 'Ley de Servicios Médicos (의료법)',
            'tourism' => 'Ley de Promoción del Turismo (관광진흥법)',
        ],
        'retention' => [
            'after_purpose' => 'Eliminar inmediatamente después de cumplir el propósito',
            '5years' => '5 años',
            '10years' => '10 años',
            '3years' => '3 años',
            'consent_period' => 'Período especificado en el consentimiento explícito',
        ],
        'note' => [
            'reservation' => 'Gestionar la información de clientes completada por separado de los registros fiscales',
            'payment' => 'Debe conservarse con fines de documentación de ventas',
            'customer_card' => 'Se requiere formulario de consentimiento para la recopilación de datos personales; se debe especificar el período de retención',
            'medical_record' => 'Los registros médicos deben conservarse según la Ley de Servicios Médicos',
            'treatment_history' => 'Se requiere formulario de consentimiento si se utiliza para la gestión de clientes habituales',
            'allergy_info' => 'La información relacionada con la salud requiere un tratamiento especial',
            'guest_register' => 'Retención requerida según la ley de establecimientos de alojamiento',
        ],
        'tip' => [
            'purpose_delete' => 'Retener datos de clientes más tiempo del necesario después de una visita puede constituir en sí mismo una violación legal.',
            'tax_separate' => 'Los registros fiscales (documentación de ventas) y los datos personales de clientes deben gestionarse por separado.',
            'platform_booking' => 'Para reservas de plataformas (ej. Naver, Kakao), los datos gestionados por la plataforma y los autogestionados son independientes.',
            'beauty_consent' => 'Si mantiene fichas de clientes para la gestión de clientes habituales, debe obtener consentimiento y especificar el período de retención.',
            'medical_strict' => 'Los registros médicos deben conservarse estrictamente según la Ley de Servicios Médicos, con una obligación mínima de retención de 10 años.',
            'food_allergy' => 'La información de alergias de los clientes se clasifica como datos sensibles y requiere una gestión más estricta.',
        ],
        'ref' => [
            'pipc' => 'Comisión de Protección de Información Personal (PIPC)',
            'law' => 'Instituto de Investigación Legislativa de Corea',
        ],
    ],
    'jp' => [
        'law' => [
            'privacy' => 'Ley de Protección de Información Personal (個人情報保護法)',
            'corporate_tax' => 'Ley del Impuesto de Sociedades (法人税法)',
            'medical_practitioners' => 'Ley de Profesionales Médicos (医師法)',
            'food_sanitation' => 'Ley de Higiene Alimentaria (食品衛生法)',
            'inn_act' => 'Ley de Establecimientos de Hospedaje (旅館業法)',
        ],
        'retention' => [
            'after_purpose' => 'Eliminar después de cumplir el propósito',
            '7years' => '7 años',
            '5years' => '5 años',
            '3years' => '3 años',
            'consent_period' => 'Período especificado en el consentimiento explícito',
            'careful' => 'Gestión estricta como información personal sensible',
        ],
        'note' => [
            'reservation' => 'La eliminación temprana de información personal tras completar la reserva es el principio',
            'payment' => 'Obligación de retención como libros relacionados con ventas',
            'customer_card' => 'Se requiere consentimiento para el tratamiento de información personal',
            'medical_record' => 'Los registros médicos deben conservarse según la Ley de Profesionales Médicos',
            'karte' => 'La ficha clínica (historial de tratamiento, información de químicos, alergias) tiene naturaleza cuasi-médica y requiere gestión cuidadosa',
            'sensitive_info' => 'Requiere un tratamiento particularmente estricto como información personal sensible (datos de salud)',
            'food_allergy' => 'La información de alergias debe gestionarse cuidadosamente como datos relacionados con la salud',
            'guest_register' => 'Obligación de retención del registro de huéspedes según la Ley de Establecimientos de Hospedaje',
        ],
        'tip' => [
            'purpose_delete' => 'Retener datos de clientes más tiempo del necesario después de una reserva puede constituir en sí mismo una violación legal.',
            'tax_separate' => 'Los registros fiscales (documentación de ventas) y los datos personales de clientes deben gestionarse por separado.',
            'karte_caution' => 'La ficha clínica del cliente (historial de tratamiento, información de químicos, alergias) tiene naturaleza cuasi-médica y requiere una gestión más cuidadosa.',
            'sensitive_info' => 'La información relacionada con la salud, como alergias a medicamentos, se clasifica como información personal sensible y requiere un tratamiento más estricto.',
            'medical_strict' => 'Los registros médicos deben conservarse estrictamente según la Ley de Profesionales Médicos, con una obligación mínima de retención de 5 años.',
        ],
        'ref' => [
            'ppc' => 'Comisión de Protección de Información Personal (PPC)',
            'e_gov' => 'Búsqueda de leyes y regulaciones e-Gov',
        ],
    ],
    'default' => [
        'retention' => [
            'check_local' => 'Consulte las leyes locales para conocer los requisitos',
        ],
        'basis' => [
            'local_privacy' => 'Ley local de protección de privacidad',
            'local_tax' => 'Ley fiscal local',
        ],
        'tip' => [
            'check_local_law' => 'Asegúrese de consultar las leyes de protección de privacidad y retención de datos de su país.',
            'minimize_data' => 'Minimice los datos personales que recopila y elimine la información innecesaria de inmediato.',
            'get_consent' => 'Obtenga siempre el consentimiento al recopilar datos de clientes y especifique el período de retención.',
        ],
    ],
];
