<?php

/**
 * Authentication translations - German
 */

return [
    // Login
    'login' => [
        'title' => 'Anmelden',
        'description' => 'Melden Sie sich an, um Ihre Reservierungen zu verwalten',
        'email' => 'E-Mail',
        'email_placeholder' => 'beispiel@email.com',
        'password' => 'Passwort',
        'password_placeholder' => '••••••••',
        'remember' => 'Angemeldet bleiben',
        'forgot' => 'Passwort vergessen?',
        'submit' => 'Anmelden',
        'no_account' => 'Noch kein Konto?',
        'register_link' => 'Registrieren',
        'back_home' => '← Zurueck zur Startseite',
        'success' => 'Erfolgreich angemeldet.',
        'failed' => 'Ungueltige E-Mail oder Passwort.',
        'required' => 'Bitte geben Sie E-Mail und Passwort ein.',
        'error' => 'Bei der Anmeldung ist ein Fehler aufgetreten.',
        'social_only' => 'Dieses Konto wurde ueber Social Login registriert. Bitte verwenden Sie Social Login.',
    ],

    // Register
    'register' => [
        'title' => 'Registrieren',
        'description' => 'Starten Sie mit RezlyX Ihre Reservierungen',
        'name' => 'Name',
        'name_placeholder' => 'Max Mustermann',
        'email' => 'E-Mail',
        'phone' => 'Telefon',
        'phone_placeholder' => '+49 123 4567890',
        'phone_hint' => 'Waehlen Sie die Landesvorwahl und geben Sie Ihre Telefonnummer ein',
        'password' => 'Passwort',
        'password_placeholder' => 'Mindestens 12 Zeichen',
        'password_hint' => 'Min. 12 Zeichen mit Gross-, Kleinbuchstaben, Zahl und Sonderzeichen',
        'password_confirm' => 'Passwort bestaetigen',
        'password_confirm_placeholder' => 'Passwort erneut eingeben',
        'agree_terms' => ' Ich stimme zu',
        'agree_privacy' => ' Ich stimme zu',
        'submit' => 'Registrieren',
        'has_account' => 'Bereits ein Konto?',
        'login_link' => 'Anmelden',
        'success' => 'Registrierung erfolgreich abgeschlossen.',
        'success_login' => 'Zur Anmeldung',
        'email_exists' => 'Diese E-Mail ist bereits registriert.',
        'error' => 'Bei der Registrierung ist ein Fehler aufgetreten.',
    ],

    // Forgot password
    'forgot' => [
        'title' => 'Passwort vergessen',
        'description' => 'Geben Sie Ihre E-Mail-Adresse ein und wir senden Ihnen einen Link zum Zuruecksetzen des Passworts.',
        'email' => 'E-Mail',
        'submit' => 'Link senden',
        'back_login' => 'Zurueck zur Anmeldung',
        'success' => 'Ein Link zum Zuruecksetzen des Passworts wurde an Ihre E-Mail gesendet.',
        'not_found' => 'E-Mail-Adresse nicht gefunden.',
    ],

    // Reset password
    'reset' => [
        'title' => 'Passwort zuruecksetzen',
        'email' => 'E-Mail',
        'password' => 'Neues Passwort',
        'password_confirm' => 'Neues Passwort bestaetigen',
        'submit' => 'Passwort zuruecksetzen',
        'success' => 'Ihr Passwort wurde zurueckgesetzt.',
        'invalid_token' => 'Ungueltiger Token.',
        'expired_token' => 'Token ist abgelaufen.',
    ],

    // Logout
    'logout' => [
        'success' => 'Erfolgreich abgemeldet.',
    ],

    // Email verification
    'verify' => [
        'title' => 'E-Mail verifizieren',
        'description' => 'Wir haben eine Verifizierungs-E-Mail an Ihre Adresse gesendet. Bitte ueberpruefen Sie Ihre E-Mail.',
        'resend' => 'Verifizierungs-E-Mail erneut senden',
        'success' => 'E-Mail erfolgreich verifiziert.',
        'already_verified' => 'E-Mail ist bereits verifiziert.',
    ],

    // Social login
    'social' => [
        'or' => 'oder',
        'google' => 'Mit Google anmelden',
        'kakao' => 'Mit Kakao anmelden',
        'naver' => 'Mit Naver anmelden',
        'line' => 'Mit LINE anmelden',
    ],

    // Social login buttons
    'login_with_line' => 'Mit LINE anmelden',
    'login_with_google' => 'Mit Google anmelden',
    'login_with_kakao' => 'Mit Kakao anmelden',
    'login_with_naver' => 'Mit Naver anmelden',
    'login_with_apple' => 'Mit Apple anmelden',
    'login_with_facebook' => 'Mit Facebook anmelden',
    'or_continue_with' => 'oder',

    // Terms Agreement
    'terms' => [
        'title' => 'Nutzungsbedingungen',
        'subtitle' => 'Bitte stimmen Sie den Bedingungen zu, um den Service zu nutzen',
        'agree_all' => 'Ich stimme allen Bedingungen zu',
        'required' => 'Erforderlich',
        'optional' => 'Optional',
        'required_mark' => 'Erforderlich',
        'required_note' => '* kennzeichnet erforderliche Felder',
        'required_alert' => 'Bitte stimmen Sie allen erforderlichen Bedingungen zu.',
        'notice' => 'Sie koennen den Service moeglicherweise nicht nutzen, wenn Sie den Bedingungen nicht zustimmen.',
        'view_content' => 'Inhalt anzeigen',
        'hide_content' => 'Inhalt ausblenden',
        'translation_pending' => 'Uebersetzung in Bearbeitung',
    ],

    // My Page
    'mypage' => [
        'title' => 'Meine Seite',
        'welcome' => 'Hallo, :name!',
        'member_since' => 'Mitglied seit :date',
        'menu' => [
            'dashboard' => 'Dashboard',
            'reservations' => 'Reservierungen',
            'profile' => 'Profil',
            'settings' => 'Einstellungen',
            'password' => 'Passwort aendern',
            'withdraw' => 'Konto löschen',
            'logout' => 'Abmelden',
        ],
        'stats' => [
            'total_reservations' => 'Reservierungen gesamt',
            'upcoming' => 'Anstehend',
            'completed' => 'Abgeschlossen',
            'cancelled' => 'Storniert',
        ],
        'recent_reservations' => 'Aktuelle Reservierungen',
        'no_reservations' => 'Keine Reservierungen gefunden.',
        'view_all' => 'Alle anzeigen',
        'quick_actions' => 'Schnellaktionen',
        'make_reservation' => 'Reservierung erstellen',
    ],

    // Profile
    'profile' => [
        'title' => 'Profil',
        'description' => 'Meine Profilinformationen.',
        'edit_title' => 'Profil bearbeiten',
        'edit_description' => 'Persönliche Informationen bearbeiten.',
        'edit_button' => 'Bearbeiten',
        'name' => 'Name',
        'email' => 'E-Mail',
        'email_hint' => 'E-Mail kann nicht geaendert werden.',
        'phone' => 'Telefon',
        'not_set' => 'Nicht festgelegt',
        'submit' => 'Speichern',
        'success' => 'Profil erfolgreich aktualisiert.',
        'error' => 'Beim Aktualisieren des Profils ist ein Fehler aufgetreten.',
    ],

    // Settings
    'settings' => [
        'title' => 'Datenschutzeinstellungen',
        'description' => 'Wählen Sie, welche Informationen anderen Benutzern angezeigt werden.',
        'info' => 'Deaktivierte Elemente sind für andere Benutzer nicht sichtbar. Der Name ist immer sichtbar.',
        'success' => 'Einstellungen gespeichert.',
        'error' => 'Fehler beim Speichern der Einstellungen.',
        'no_fields' => 'Keine konfigurierbaren Felder verfügbar.',
        'fields' => [
            'email' => 'E-Mail', 'email_desc' => 'Ihre E-Mail-Adresse für andere Benutzer anzeigen.',
            'profile_photo' => 'Profilbild', 'profile_photo_desc' => 'Profilbild anderen Benutzern anzeigen.',
            'phone' => 'Telefonnummer', 'phone_desc' => 'Telefonnummer anderen Benutzern anzeigen.',
            'birth_date' => 'Geburtsdatum', 'birth_date_desc' => 'Geburtsdatum anderen Benutzern anzeigen.',
            'gender' => 'Geschlecht', 'gender_desc' => 'Geschlecht anderen Benutzern anzeigen.',
            'company' => 'Unternehmen', 'company_desc' => 'Unternehmen anderen Benutzern anzeigen.',
            'blog' => 'Blog', 'blog_desc' => 'Blog-URL anderen Benutzern anzeigen.',
        ],
    ],

    // Change Password
    'password_change' => [
        'title' => 'Passwort aendern',
        'description' => 'Bitte aendern Sie Ihr Passwort regelmaessig fuer mehr Sicherheit.',
        'current' => 'Aktuelles Passwort',
        'current_placeholder' => 'Aktuelles Passwort eingeben',
        'new' => 'Neues Passwort',
        'new_placeholder' => 'Neues Passwort eingeben',
        'confirm' => 'Neues Passwort bestaetigen',
        'confirm_placeholder' => 'Neues Passwort erneut eingeben',
        'submit' => 'Passwort aendern',
        'success' => 'Passwort erfolgreich geaendert.',
        'error' => 'Beim Aendern des Passworts ist ein Fehler aufgetreten.',
        'wrong_password' => 'Aktuelles Passwort ist falsch.',
    ],

    // Konto löschen
    'withdraw' => [
        'title' => 'Konto löschen',
        'description' => 'Ihre persönlichen Daten werden bei der Kontolöschung sofort anonymisiert. Dieser Vorgang kann nicht rückgängig gemacht werden.',
        'warning_title' => 'Bitte lesen Sie sorgfältig vor dem Fortfahren',
        'warnings' => [
            'account' => 'Alle persönlichen Daten wie Name, E-Mail, Telefonnummer, Geburtsdatum und Profilbild werden sofort anonymisiert. Eine Identifizierung ist danach nicht mehr möglich.',
            'reservation' => 'Falls Sie aktive oder bevorstehende Reservierungen haben, stornieren Sie diese bitte vor der Kontolöschung. Nach der Löschung können Reservierungen nicht mehr geändert oder storniert werden.',
            'payment' => 'Zahlungs- und Transaktionsdaten werden gemäß den geltenden Steuergesetzen (Korea: 5 Jahre, Japan: 7 Jahre) in anonymisierter Form für die gesetzliche Aufbewahrungsfrist gespeichert.',
            'recovery' => 'Gelöschte Konten können nicht wiederhergestellt werden. Eine erneute Registrierung mit derselben E-Mail ist möglich, aber alle vorherigen Daten wie Reservierungen, Punkte und Nachrichten werden nicht wiederhergestellt.',
            'social' => 'Wenn Sie sich über Social Login (Google, Kakao, LINE usw.) registriert haben, wird die Verbindung zu diesem sozialen Dienst ebenfalls getrennt.',
            'message' => 'Alle empfangenen Nachrichten und Benachrichtigungsverläufe werden dauerhaft gelöscht.',
        ],
        'retention_notice' => '※ Gesetzlich vorgeschriebene Transaktionsdaten werden in nicht identifizierbarer Form für den gesetzlichen Zeitraum aufbewahrt und anschließend vollständig gelöscht.',
        'reason' => 'Grund für die Löschung',
        'reason_placeholder' => 'Bitte wählen Sie einen Grund',
        'reasons' => [
            'not_using' => 'Nutze den Service nicht mehr',
            'other_service' => 'Wechsel zu einem anderen Service',
            'dissatisfied' => 'Unzufrieden mit dem Service',
            'privacy' => 'Datenschutzbedenken',
            'too_many_emails' => 'Zu viele E-Mails/Benachrichtigungen',
            'other' => 'Sonstiges',
        ],
        'reason_other' => 'Sonstiger Grund',
        'reason_other_placeholder' => 'Bitte geben Sie Ihren Grund ein',
        'password' => 'Passwort bestätigen',
        'password_placeholder' => 'Aktuelles Passwort eingeben',
        'password_hint' => 'Bitte geben Sie Ihr aktuelles Passwort zur Verifizierung ein.',
        'confirm_text' => 'Ich habe alle obigen Informationen gelesen und verstanden und stimme der Anonymisierung meiner Daten und der Kontolöschung zu.',
        'submit' => 'Konto löschen',
        'success' => 'Ihr Konto wurde gelöscht. Vielen Dank für die Nutzung unseres Services.',
        'wrong_password' => 'Falsches Passwort.',
        'error' => 'Beim Löschen des Kontos ist ein Fehler aufgetreten.',
        'confirm_required' => 'Bitte bestätigen Sie die Zustimmung.',
    ],
];
