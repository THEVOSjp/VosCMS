<?php

/**
 * My Page translations - French
 */

return [
    // Main
    'title' => 'Mon espace',
    'welcome' => 'Bienvenue, :name !',

    // Navigation
    'nav' => [
        'dashboard' => 'Tableau de bord',
        'reservations' => 'Mes reservations',
        'profile' => 'Profil',
        'password' => 'Changer le mot de passe',
        'logout' => 'Deconnexion',
    ],

    // Dashboard
    'dashboard' => [
        'upcoming' => 'Reservations a venir',
        'recent' => 'Reservations recentes',
        'no_upcoming' => 'Aucune reservation a venir.',
        'no_recent' => 'Aucune reservation recente.',
        'view_all' => 'Voir tout',
    ],

    // Reservations
    'reservations' => [
        'title' => 'Historique des reservations',
        'filter' => [
            'all' => 'Toutes',
            'pending' => 'En attente',
            'confirmed' => 'Confirmees',
            'completed' => 'Terminees',
            'cancelled' => 'Annulees',
        ],
        'no_reservations' => 'Aucune reservation trouvee.',
        'booking_code' => 'Code de reservation',
        'service' => 'Service',
        'date' => 'Date',
        'status' => 'Statut',
        'actions' => 'Actions',
        'view' => 'Voir',
        'cancel' => 'Annuler',
    ],

    // Profile
    'profile' => [
        'title' => 'Parametres du profil',
        'info' => 'Informations de base',
        'name' => 'Nom',
        'email' => 'E-mail',
        'phone' => 'Telephone',
        'save' => 'Enregistrer',
        'success' => 'Profil mis a jour avec succes.',
    ],

    // Password
    'password' => [
        'title' => 'Changer le mot de passe',
        'current' => 'Mot de passe actuel',
        'new' => 'Nouveau mot de passe',
        'confirm' => 'Confirmer le nouveau mot de passe',
        'change' => 'Changer le mot de passe',
        'success' => 'Mot de passe change avec succes.',
        'mismatch' => 'Le mot de passe actuel est incorrect.',
    ],

    // Stats
    'stats' => [
        'total_bookings' => 'Total des reservations',
        'completed' => 'Terminees',
        'cancelled' => 'Annulees',
        'upcoming' => 'A venir',
    ],
];
