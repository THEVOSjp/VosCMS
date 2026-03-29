<?php

/**
 * Validation messages - German
 */

return [
    // Basic messages
    'accepted' => ':attribute muss akzeptiert werden.',
    'required' => 'Das Feld :attribute ist erforderlich.',
    'email' => 'Bitte geben Sie eine gueltige E-Mail-Adresse ein.',
    'min' => 'Das Feld :attribute muss mindestens :min Zeichen haben.',
    'max' => 'Das Feld :attribute darf maximal :max Zeichen haben.',
    'numeric' => 'Das Feld :attribute muss eine Zahl sein.',
    'integer' => 'Das Feld :attribute muss eine ganze Zahl sein.',
    'string' => 'Das Feld :attribute muss eine Zeichenkette sein.',
    'boolean' => 'Das Feld :attribute muss wahr oder falsch sein.',
    'array' => 'Das Feld :attribute muss ein Array sein.',
    'date' => 'Das Feld :attribute ist kein gueltiges Datum.',
    'same' => 'Die Bestaetigung von :attribute stimmt nicht ueberein.',
    'confirmed' => 'Die Bestaetigung von :attribute stimmt nicht ueberein.',
    'unique' => 'Das :attribute ist bereits vergeben.',
    'exists' => 'Das ausgewaehlte :attribute ist ungueltig.',
    'in' => 'Das ausgewaehlte :attribute ist ungueltig.',
    'not_in' => 'Das ausgewaehlte :attribute ist ungueltig.',
    'regex' => 'Das Format von :attribute ist ungueltig.',
    'url' => 'Das Format von :attribute ist ungueltig.',
    'alpha' => 'Das Feld :attribute darf nur Buchstaben enthalten.',
    'alpha_num' => 'Das Feld :attribute darf nur Buchstaben und Zahlen enthalten.',
    'alpha_dash' => 'Das Feld :attribute darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten.',

    // Size
    'size' => [
        'numeric' => 'Das Feld :attribute muss :size sein.',
        'string' => 'Das Feld :attribute muss :size Zeichen haben.',
        'array' => 'Das Feld :attribute muss :size Elemente enthalten.',
    ],

    'between' => [
        'numeric' => 'Das Feld :attribute muss zwischen :min und :max liegen.',
        'string' => 'Das Feld :attribute muss zwischen :min und :max Zeichen haben.',
        'array' => 'Das Feld :attribute muss zwischen :min und :max Elemente haben.',
    ],

    // File
    'file' => 'Das Feld :attribute muss eine Datei sein.',
    'image' => 'Das Feld :attribute muss ein Bild sein.',
    'mimes' => 'Das Feld :attribute muss eine Datei vom Typ: :values sein.',
    'max_file' => 'Das Feld :attribute darf maximal :max Kilobyte gross sein.',

    // Password
    'password' => [
        'lowercase' => 'Das Passwort muss mindestens einen Kleinbuchstaben enthalten.',
        'uppercase' => 'Das Passwort muss mindestens einen Grossbuchstaben enthalten.',
        'number' => 'Das Passwort muss mindestens eine Zahl enthalten.',
        'special' => 'Das Passwort muss mindestens ein Sonderzeichen enthalten.',
    ],

    // Date
    'date_format' => 'Das Feld :attribute entspricht nicht dem Format :format.',
    'after' => 'Das Feld :attribute muss ein Datum nach :date sein.',
    'before' => 'Das Feld :attribute muss ein Datum vor :date sein.',
    'after_or_equal' => 'Das Feld :attribute muss ein Datum nach oder gleich :date sein.',
    'before_or_equal' => 'Das Feld :attribute muss ein Datum vor oder gleich :date sein.',

    // Attribute names
    'attributes' => [
        'name' => 'Name',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'password_confirmation' => 'Passwortbestaetigung',
        'phone' => 'Telefon',
        'customer_name' => 'Name',
        'customer_email' => 'E-Mail',
        'customer_phone' => 'Telefon',
        'booking_date' => 'Buchungsdatum',
        'start_time' => 'Startzeit',
        'guests' => 'Gaeste',
        'service_id' => 'Dienstleistung',
        'category_id' => 'Kategorie',
        'duration' => 'Dauer',
        'price' => 'Preis',
        'description' => 'Beschreibung',
        'notes' => 'Anmerkungen',
        'reason' => 'Grund',
    ],
];
