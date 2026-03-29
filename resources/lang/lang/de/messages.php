<?php
/**
 * German Language File
 */

return [
    // Common
    'app_name' => 'RezlyX',
    'welcome' => 'Willkommen',
    'home' => 'Startseite',
    'back' => 'Zurueck',
    'next' => 'Weiter',
    'cancel' => 'Abbrechen',
    'confirm' => 'Bestaetigen',
    'save' => 'Speichern',
    'delete' => 'Loeschen',
    'edit' => 'Bearbeiten',
    'search' => 'Suchen',
    'loading' => 'Laden...',
    'no_data' => 'Keine Daten verfuegbar.',
    'error' => 'Ein Fehler ist aufgetreten.',
    'success' => 'Erfolgreich verarbeitet.',

    // Auth
    'auth' => [
        'login' => 'Anmelden',
        'logout' => 'Abmelden',
        'register' => 'Registrieren',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'password_confirm' => 'Passwort bestaetigen',
        'remember_me' => 'Angemeldet bleiben',
        'forgot_password' => 'Passwort vergessen?',
        'reset_password' => 'Passwort zuruecksetzen',
        'invalid_credentials' => 'Ungueltige E-Mail oder Passwort.',
        'account_inactive' => 'Dieses Konto ist inaktiv.',
    ],

    // Reservation
    'reservation' => [
        'title' => 'Reservierung',
        'new' => 'Neue Reservierung',
        'my_reservations' => 'Meine Reservierungen',
        'select_service' => 'Service auswaehlen',
        'select_date' => 'Datum auswaehlen',
        'select_time' => 'Uhrzeit auswaehlen',
        'customer_info' => 'Ihre Informationen',
        'payment' => 'Zahlung',
        'confirmation' => 'Bestaetigung',
        'status' => [
            'pending' => 'Ausstehend',
            'confirmed' => 'Bestaetigt',
            'completed' => 'Abgeschlossen',
            'cancelled' => 'Storniert',
            'no_show' => 'Nicht erschienen',
        ],
    ],

    // Services
    'service' => [
        'title' => 'Dienstleistungen',
        'category' => 'Kategorie',
        'price' => 'Preis',
        'duration' => 'Dauer',
        'description' => 'Beschreibung',
        'options' => 'Optionen',
    ],

    // Member
    'member' => [
        'profile' => 'Mein Profil',
        'points' => 'Punkte',
        'grade' => 'Mitgliedschaftsstufe',
        'reservations' => 'Reservierungsverlauf',
        'payments' => 'Zahlungsverlauf',
        'settings' => 'Einstellungen',
    ],

    // Payment
    'payment' => [
        'title' => 'Zahlung',
        'amount' => 'Betrag',
        'method' => 'Zahlungsmethode',
        'card' => 'Kreditkarte',
        'bank_transfer' => 'Bankueberweisung',
        'virtual_account' => 'Virtuelles Konto',
        'points' => 'Punkte',
        'use_points' => 'Punkte verwenden',
        'available_points' => 'Verfuegbare Punkte',
        'complete' => 'Zahlung abgeschlossen',
        'failed' => 'Zahlung fehlgeschlagen',
    ],

    // Time
    'time' => [
        'today' => 'Heute',
        'tomorrow' => 'Morgen',
        'minutes' => 'Min.',
        'hours' => 'Stunden',
        'days' => 'Tage',
    ],

    // Validation
    'validation' => [
        'required' => 'Das Feld :attribute ist erforderlich.',
        'email' => 'Bitte geben Sie eine gueltige E-Mail-Adresse ein.',
        'min' => 'Das Feld :attribute muss mindestens :min Zeichen haben.',
        'max' => 'Das Feld :attribute darf maximal :max Zeichen haben.',
        'confirmed' => 'Die :attribute Bestaetigung stimmt nicht ueberein.',
    ],
];
