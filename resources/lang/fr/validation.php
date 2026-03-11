<?php

/**
 * Validation messages - French
 */

return [
    // Basic messages
    'accepted' => ':attribute doit être accepté.',
    'required' => 'Le champ :attribute est requis.',
    'email' => 'Veuillez entrer une adresse e-mail valide.',
    'min' => 'Le champ :attribute doit contenir au moins :min caracteres.',
    'max' => 'Le champ :attribute ne doit pas depasser :max caracteres.',
    'numeric' => 'Le champ :attribute doit etre un nombre.',
    'integer' => 'Le champ :attribute doit etre un entier.',
    'string' => 'Le champ :attribute doit etre une chaine de caracteres.',
    'boolean' => 'Le champ :attribute doit etre vrai ou faux.',
    'array' => 'Le champ :attribute doit etre un tableau.',
    'date' => 'Le champ :attribute n\'est pas une date valide.',
    'same' => 'La confirmation de :attribute ne correspond pas.',
    'confirmed' => 'La confirmation de :attribute ne correspond pas.',
    'unique' => 'Le champ :attribute est deja utilise.',
    'exists' => 'Le champ :attribute selectionne est invalide.',
    'in' => 'Le champ :attribute selectionne est invalide.',
    'not_in' => 'Le champ :attribute selectionne est invalide.',
    'regex' => 'Le format de :attribute est invalide.',
    'url' => 'Le format de :attribute est invalide.',
    'alpha' => 'Le champ :attribute ne peut contenir que des lettres.',
    'alpha_num' => 'Le champ :attribute ne peut contenir que des lettres et des chiffres.',
    'alpha_dash' => 'Le champ :attribute ne peut contenir que des lettres, chiffres, tirets et underscores.',

    // Size
    'size' => [
        'numeric' => 'Le champ :attribute doit etre :size.',
        'string' => 'Le champ :attribute doit contenir :size caracteres.',
        'array' => 'Le champ :attribute doit contenir :size elements.',
    ],

    'between' => [
        'numeric' => 'Le champ :attribute doit etre compris entre :min et :max.',
        'string' => 'Le champ :attribute doit contenir entre :min et :max caracteres.',
        'array' => 'Le champ :attribute doit contenir entre :min et :max elements.',
    ],

    // File
    'file' => 'Le champ :attribute doit etre un fichier.',
    'image' => 'Le champ :attribute doit etre une image.',
    'mimes' => 'Le champ :attribute doit etre un fichier de type : :values.',
    'max_file' => 'Le champ :attribute ne doit pas depasser :max kilo-octets.',

    // Password
    'password' => [
        'lowercase' => 'Le mot de passe doit contenir au moins une lettre minuscule.',
        'uppercase' => 'Le mot de passe doit contenir au moins une lettre majuscule.',
        'number' => 'Le mot de passe doit contenir au moins un chiffre.',
        'special' => 'Le mot de passe doit contenir au moins un caractere special.',
    ],

    // Date
    'date_format' => 'Le champ :attribute ne correspond pas au format :format.',
    'after' => 'Le champ :attribute doit etre une date posterieure a :date.',
    'before' => 'Le champ :attribute doit etre une date anterieure a :date.',
    'after_or_equal' => 'Le champ :attribute doit etre une date posterieure ou egale a :date.',
    'before_or_equal' => 'Le champ :attribute doit etre une date anterieure ou egale a :date.',

    // Attribute names
    'attributes' => [
        'name' => 'nom',
        'email' => 'e-mail',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'phone' => 'telephone',
        'customer_name' => 'nom',
        'customer_email' => 'e-mail',
        'customer_phone' => 'telephone',
        'booking_date' => 'date de reservation',
        'start_time' => 'heure de debut',
        'guests' => 'personnes',
        'service_id' => 'service',
        'category_id' => 'categorie',
        'duration' => 'duree',
        'price' => 'prix',
        'description' => 'description',
        'notes' => 'notes',
        'reason' => 'raison',
    ],
];
