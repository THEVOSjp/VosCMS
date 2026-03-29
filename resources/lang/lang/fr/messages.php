<?php
/**
 * French Language File
 */

return [
    // Common
    'app_name' => 'RezlyX',
    'welcome' => 'Bienvenue',
    'home' => 'Accueil',
    'back' => 'Retour',
    'next' => 'Suivant',
    'cancel' => 'Annuler',
    'confirm' => 'Confirmer',
    'save' => 'Enregistrer',
    'delete' => 'Supprimer',
    'edit' => 'Modifier',
    'search' => 'Rechercher',
    'loading' => 'Chargement...',
    'no_data' => 'Aucune donnee disponible.',
    'error' => 'Une erreur est survenue.',
    'success' => 'Operation reussie.',

    // Auth
    'auth' => [
        'login' => 'Connexion',
        'logout' => 'Deconnexion',
        'register' => 'Inscription',
        'email' => 'E-mail',
        'password' => 'Mot de passe',
        'password_confirm' => 'Confirmer le mot de passe',
        'remember_me' => 'Se souvenir de moi',
        'forgot_password' => 'Mot de passe oublie ?',
        'reset_password' => 'Reinitialiser le mot de passe',
        'invalid_credentials' => 'E-mail ou mot de passe incorrect.',
        'account_inactive' => 'Ce compte est inactif.',
    ],

    // Reservation
    'reservation' => [
        'title' => 'Reservation',
        'new' => 'Nouvelle reservation',
        'my_reservations' => 'Mes reservations',
        'select_service' => 'Choisir un service',
        'select_date' => 'Choisir une date',
        'select_time' => 'Choisir une heure',
        'customer_info' => 'Vos informations',
        'payment' => 'Paiement',
        'confirmation' => 'Confirmation',
        'status' => [
            'pending' => 'En attente',
            'confirmed' => 'Confirmee',
            'completed' => 'Terminee',
            'cancelled' => 'Annulee',
            'no_show' => 'Absent',
        ],
    ],

    // Services
    'service' => [
        'title' => 'Services',
        'category' => 'Categorie',
        'price' => 'Prix',
        'duration' => 'Duree',
        'description' => 'Description',
        'options' => 'Options',
    ],

    // Member
    'member' => [
        'profile' => 'Mon profil',
        'points' => 'Points',
        'grade' => 'Niveau de membre',
        'reservations' => 'Historique des reservations',
        'payments' => 'Historique des paiements',
        'settings' => 'Parametres',
    ],

    // Payment
    'payment' => [
        'title' => 'Paiement',
        'amount' => 'Montant',
        'method' => 'Mode de paiement',
        'card' => 'Carte bancaire',
        'bank_transfer' => 'Virement bancaire',
        'virtual_account' => 'Compte virtuel',
        'points' => 'Points',
        'use_points' => 'Utiliser les points',
        'available_points' => 'Points disponibles',
        'complete' => 'Paiement effectue',
        'failed' => 'Echec du paiement',
    ],

    // Time
    'time' => [
        'today' => 'Aujourd\'hui',
        'tomorrow' => 'Demain',
        'minutes' => 'min',
        'hours' => 'heures',
        'days' => 'jours',
    ],

    // Validation
    'validation' => [
        'required' => 'Le champ :attribute est requis.',
        'email' => 'Veuillez entrer une adresse e-mail valide.',
        'min' => 'Le champ :attribute doit contenir au moins :min caracteres.',
        'max' => 'Le champ :attribute ne doit pas depasser :max caracteres.',
        'confirmed' => 'La confirmation de :attribute ne correspond pas.',
    ],
];
