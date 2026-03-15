<?php

/**
 * Booking translations - German
 */

return [
    // Page titles
    'title' => 'Jetzt buchen',
    'service_list' => 'Dienstleistungsliste',
    'select_service' => 'Dienstleistung auswaehlen',
    'select_date' => 'Datum auswaehlen',
    'select_time' => 'Uhrzeit auswaehlen',
    'enter_info' => 'Informationen eingeben',
    'confirm_booking' => 'Buchung bestaetigen',
    'confirm_info' => 'Bitte ueberpruefen Sie Ihre Buchungsinformationen',
    'complete_booking' => 'Buchung abschliessen',
    'select_service_datetime' => 'Bitte waehlen Sie Ihre Dienstleistung und bevorzugte Datum/Uhrzeit',
    'select_datetime' => 'Bitte waehlen Sie ein Datum und eine Uhrzeit',
    'no_services' => 'Derzeit keine Dienstleistungen verfuegbar.',
    'contact_admin' => 'Bitte kontaktieren Sie den Administrator.',
    'notes' => 'Besondere Wuensche',
    'notes_placeholder' => 'Geben Sie besondere Wuensche ein',
    'customer' => 'Kunde',
    'phone' => 'Telefon',
    'date_label' => 'Datum',
    'time_label' => 'Uhrzeit',
    'total_price' => 'Gesamtbetrag',
    'cancel_policy' => 'Stornierungen sind bis zu 24 Stunden vor der Reservierungszeit moeglich. Bei spaeteren Stornierungen kann eine Gebuehr anfallen.',
    'success' => 'Buchung abgeschlossen!',
    'success_desc' => 'Eine Bestätigung wird gesendet. Bitte bewahren Sie Ihre Buchungsnummer auf.',
    'submitting' => 'Verarbeitung...',
    'select_staff' => 'Bitte wählen Sie einen Mitarbeiter',
    'no_preference' => 'Keine Präferenz',
    'staff' => 'Mitarbeiter',
    'designation_fee' => 'Nominierungsgebühr',
    'designation_fee_badge' => '+:amount',
    'loading_slots' => 'Verfügbare Zeiten werden geprüft...',
    'no_available_slots' => 'Keine verfügbaren Zeiten am gewählten Datum.',
    'items_selected' => 'ausgewählt',
    'total_duration' => 'Gesamtdauer',

    // Steps
    'step' => [
        'service' => 'Dienstleistung waehlen',
        'datetime' => 'Datum/Uhrzeit',
        'info' => 'Informationen',
        'confirm' => 'Bestaetigen',
    ],

    // Service
    'service' => [
        'title' => 'Dienstleistung',
        'name' => 'Dienstleistungsname',
        'description' => 'Beschreibung',
        'duration' => 'Dauer',
        'price' => 'Preis',
        'category' => 'Kategorie',
        'select' => 'Auswaehlen',
        'view_detail' => 'Details anzeigen',
        'no_services' => 'Keine Dienstleistungen verfuegbar.',
    ],

    // Date/Time
    'date' => [
        'title' => 'Buchungsdatum',
        'select_date' => 'Bitte waehlen Sie ein Datum',
        'available' => 'Verfuegbar',
        'unavailable' => 'Nicht verfuegbar',
        'fully_booked' => 'Ausgebucht',
        'past_date' => 'Vergangenes Datum',
    ],

    'time' => [
        'title' => 'Buchungszeit',
        'select_time' => 'Bitte waehlen Sie eine Uhrzeit',
        'available_slots' => 'Verfuegbare Zeitfenster',
        'no_slots' => 'Keine verfuegbaren Zeitfenster.',
        'remaining' => ':count Plaetze verfuegbar',
    ],

    // Booking form
    'form' => [
        'customer_name' => 'Name',
        'customer_email' => 'E-Mail',
        'customer_phone' => 'Telefon',
        'guests' => 'Anzahl der Gaeste',
        'notes' => 'Besondere Wuensche',
        'notes_placeholder' => 'Geben Sie besondere Wuensche ein',
    ],

    // Confirmation
    'confirm' => [
        'title' => 'Buchung bestaetigen',
        'summary' => 'Buchungszusammenfassung',
        'service_info' => 'Dienstleistungsinformationen',
        'booking_info' => 'Buchungsinformationen',
        'customer_info' => 'Kundeninformationen',
        'total_price' => 'Gesamt',
        'agree_terms' => 'Ich stimme den Buchungsbedingungen zu',
        'submit' => 'Buchung abschliessen',
    ],

    // Complete
    'complete' => [
        'title' => 'Buchung abgeschlossen',
        'success' => 'Ihre Buchung wurde abgeschlossen!',
        'booking_code' => 'Buchungscode',
        'check_email' => 'Eine Bestaetigungs-E-Mail wurde an Ihre E-Mail-Adresse gesendet.',
        'view_detail' => 'Buchungsdetails anzeigen',
        'book_another' => 'Weitere Buchung erstellen',
    ],

    // Lookup
    'lookup' => [
        'title' => 'Buchung suchen',
        'description' => 'Geben Sie Ihre Buchungsinformationen ein, um Ihre Reservierung zu finden.',
        'booking_code' => 'Buchungscode',
        'booking_code_placeholder' => 'RZ250301XXXXXX',
        'email' => 'E-Mail',
        'email_placeholder' => 'Fuer Buchung verwendete E-Mail',
        'phone' => 'Telefonnummer',
        'phone_placeholder' => 'Fuer Buchung verwendete Telefonnummer',
        'search' => 'Suchen',
        'search_method' => 'Suchmethode',
        'by_code' => 'Nach Buchungscode suchen',
        'by_email' => 'Nach E-Mail suchen',
        'by_phone' => 'Nach Telefon suchen',
        'not_found' => 'Buchung nicht gefunden. Bitte ueberpruefen Sie Ihre Angaben.',
        'input_required' => 'Bitte geben Sie einen Buchungscode und E-Mail oder Telefonnummer ein.',
        'result_title' => 'Suchergebnisse',
        'multiple_results' => ':count Buchungen gefunden.',
        'hint' => 'Fuer genaue Ergebnisse geben Sie einen Buchungscode zusammen mit Ihrer E-Mail oder Telefonnummer ein.',
        'help_text' => 'Koennen Sie Ihre Buchung nicht finden?',
        'contact_support' => 'Support kontaktieren',
    ],

    // Detail
    'detail' => [
        'title' => 'Buchungsdetails',
        'status' => 'Status',
        'booking_date' => 'Datum & Uhrzeit',
        'service' => 'Dienstleistung',
        'guests' => 'Gaeste',
        'total_price' => 'Gesamtpreis',
        'payment_status' => 'Zahlungsstatus',
        'notes' => 'Besondere Wuensche',
        'created_at' => 'Gebucht am',
    ],

    // Cancel
    'cancel' => [
        'title' => 'Buchung stornieren',
        'confirm' => 'Moechten Sie diese Buchung wirklich stornieren?',
        'reason' => 'Stornierungsgrund',
        'reason_placeholder' => 'Bitte geben Sie den Stornierungsgrund ein',
        'submit' => 'Buchung stornieren',
        'success' => 'Ihre Buchung wurde storniert.',
        'cannot_cancel' => 'Diese Buchung kann nicht storniert werden.',
    ],

    // Status messages
    'status' => [
        'pending' => 'Ihre Buchung wurde empfangen. Bitte warten Sie auf die Bestaetigung.',
        'confirmed' => 'Ihre Buchung wurde bestaetigt.',
        'cancelled' => 'Ihre Buchung wurde storniert.',
        'completed' => 'Dienstleistung abgeschlossen.',
        'no_show' => 'Als nicht erschienen markiert.',
    ],

    // Error messages
    'error' => [
        'service_not_found' => 'Dienstleistung nicht gefunden.',
        'slot_unavailable' => 'Das ausgewaehlte Zeitfenster ist nicht verfuegbar.',
        'past_date' => 'Buchungen fuer vergangene Daten sind nicht moeglich.',
        'max_capacity' => 'Maximale Kapazitaet ueberschritten.',
        'booking_failed' => 'Bei der Verarbeitung Ihrer Buchung ist ein Fehler aufgetreten.',
        'required_fields' => 'Bitte geben Sie Ihren Namen und Kontaktdaten ein.',
        'invalid_service' => 'Ungültiger Service.',
    ],
];
