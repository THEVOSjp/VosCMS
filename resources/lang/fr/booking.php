<?php

/**
 * Booking translations - French
 */

return [
    // Page titles
    'title' => 'Reserver maintenant',
    'service_list' => 'Liste des services',
    'select_service' => 'Choisir un service',
    'select_date' => 'Choisir une date',
    'select_time' => 'Choisir une heure',
    'enter_info' => 'Entrer les informations',
    'confirm_booking' => 'Confirmer la reservation',
    'confirm_info' => 'Veuillez confirmer vos informations de reservation',
    'complete_booking' => 'Finaliser la reservation',
    'select_service_datetime' => 'Veuillez selectionner votre service et la date/heure souhaitee',
    'staff_designation_guide' => 'Pour les réservations avec un employé désigné, veuillez accéder à la page du personnel',
    'go_staff_booking' => 'Réservation personnel désigné',
    'select_datetime' => 'Veuillez selectionner une date et une heure',
    'no_services' => 'Aucun service disponible actuellement.',
    'contact_admin' => 'Veuillez contacter l\'administrateur.',
    'notes' => 'Demandes speciales',
    'notes_placeholder' => 'Entrez vos demandes speciales',
    'customer' => 'Client',
    'phone' => 'Telephone',
    'date_label' => 'Date',
    'time_label' => 'Heure',
    'total_price' => 'Montant total',
    'cancel_policy' => 'Les annulations sont autorisees jusqu\'a 24 heures avant l\'heure de reservation. Des frais d\'annulation peuvent s\'appliquer pour les annulations ulterieures.',
    'success' => 'Réservation terminée !',
    'success_desc' => 'Une confirmation vous sera envoyée. Veuillez conserver votre numéro de réservation.',
    'submitting' => 'Traitement...',
    'select_staff' => 'Sélectionnez un membre du personnel',
    'no_preference' => 'Pas de préférence',
    'staff' => 'Personnel',
    'designation_fee' => 'Frais de désignation',
    'designation_fee_badge' => '+:amount',
    'loading_slots' => 'Vérification des créneaux disponibles...',
    'no_available_slots' => 'Aucun créneau disponible à la date sélectionnée.',
    'items_selected' => 'sélectionnés',
    'total_duration' => 'Durée totale',

    // Steps
    'step' => [
        'service' => 'Choisir un service',
        'datetime' => 'Date/Heure',
        'info' => 'Informations',
        'confirm' => 'Confirmer',
    ],

    // Service
    'service' => [
        'title' => 'Service',
        'name' => 'Nom du service',
        'description' => 'Description',
        'duration' => 'Duree',
        'price' => 'Prix',
        'category' => 'Categorie',
        'select' => 'Selectionner',
        'view_detail' => 'Voir les details',
        'no_services' => 'Aucun service disponible.',
    ],

    // Date/Time
    'date' => [
        'title' => 'Date de reservation',
        'select_date' => 'Veuillez selectionner une date',
        'available' => 'Disponible',
        'unavailable' => 'Indisponible',
        'fully_booked' => 'Complet',
        'past_date' => 'Date passee',
    ],

    'time' => [
        'title' => 'Heure de reservation',
        'select_time' => 'Veuillez selectionner une heure',
        'available_slots' => 'Creneaux disponibles',
        'no_slots' => 'Aucun creneau disponible.',
        'remaining' => ':count places restantes',
    ],

    // Booking form
    'form' => [
        'customer_name' => 'Nom',
        'customer_email' => 'E-mail',
        'customer_phone' => 'Telephone',
        'guests' => 'Nombre de personnes',
        'notes' => 'Demandes speciales',
        'notes_placeholder' => 'Entrez vos demandes speciales',
    ],

    // Confirmation
    'confirm' => [
        'title' => 'Confirmer la reservation',
        'summary' => 'Resume de la reservation',
        'service_info' => 'Informations sur le service',
        'booking_info' => 'Informations de reservation',
        'customer_info' => 'Informations client',
        'total_price' => 'Total',
        'agree_terms' => 'J\'accepte les conditions de reservation',
        'submit' => 'Finaliser la reservation',
    ],

    // Complete
    'complete' => [
        'title' => 'Reservation terminee',
        'success' => 'Votre reservation a ete effectuee !',
        'booking_code' => 'Code de reservation',
        'check_email' => 'Un e-mail de confirmation a ete envoye a votre adresse.',
        'view_detail' => 'Voir les details de la reservation',
        'book_another' => 'Effectuer une autre reservation',
    ],

    // Lookup
    'lookup' => [
        'title' => 'Trouver une reservation',
        'description' => 'Entrez vos informations de reservation pour trouver votre reservation.',
        'booking_code' => 'Code de reservation',
        'booking_code_placeholder' => 'RZ250301XXXXXX',
        'email' => 'E-mail',
        'email_placeholder' => 'E-mail utilise pour la reservation',
        'phone' => 'Numero de telephone',
        'phone_placeholder' => 'Numero utilise pour la reservation',
        'search' => 'Rechercher',
        'search_method' => 'Methode de recherche',
        'by_code' => 'Rechercher par code de reservation',
        'by_email' => 'Rechercher par e-mail',
        'by_phone' => 'Rechercher par telephone',
        'not_found' => 'Reservation introuvable. Veuillez verifier vos informations.',
        'input_required' => 'Veuillez entrer un code de reservation et un e-mail ou un numero de telephone.',
        'result_title' => 'Resultats de recherche',
        'multiple_results' => ':count reservations trouvees.',
        'hint' => 'Pour des resultats precis, entrez un code de reservation avec votre e-mail ou numero de telephone.',
        'help_text' => 'Vous ne trouvez pas votre reservation ?',
        'contact_support' => 'Contacter le support',
    ],

    // Detail
    'detail' => [
        'title' => 'Details de la reservation',
        'status' => 'Statut',
        'booking_date' => 'Date et heure',
        'service' => 'Service',
        'guests' => 'Personnes',
        'total_price' => 'Prix total',
        'payment_status' => 'Statut du paiement',
        'notes' => 'Demandes speciales',
        'created_at' => 'Reserve le',
    ],

    // Cancel
    'cancel' => [
        'title' => 'Annuler la reservation',
        'confirm' => 'Etes-vous sur de vouloir annuler cette reservation ?',
        'reason' => 'Raison de l\'annulation',
        'reason_placeholder' => 'Veuillez entrer la raison de l\'annulation',
        'submit' => 'Annuler la reservation',
        'success' => 'Votre reservation a ete annulee.',
        'cannot_cancel' => 'Cette reservation ne peut pas etre annulee.',
    ],

    // Status messages
    'status' => [
        'pending' => 'Votre reservation a ete recue. Veuillez attendre la confirmation.',
        'confirmed' => 'Votre reservation a ete confirmee.',
        'cancelled' => 'Votre reservation a ete annulee.',
        'completed' => 'Service termine.',
        'no_show' => 'Marque comme absent.',
    ],

    // Error messages
    'error' => [
        'service_not_found' => 'Service introuvable.',
        'slot_unavailable' => 'Le creneau selectionne n\'est pas disponible.',
        'past_date' => 'Impossible de reserver pour des dates passees.',
        'max_capacity' => 'Capacite maximale depassee.',
        'booking_failed' => 'Une erreur est survenue lors du traitement de votre reservation.',
        'required_fields' => 'Veuillez entrer votre nom et vos coordonnees.',
        'invalid_service' => 'Service invalide.',
    ],

    'member_discount' => 'Réduction membre',
    'use_points' => 'Utiliser les points',
    'points_balance' => 'Solde',
    'use_all' => 'Tout utiliser',
    'points_default_name' => 'Points',
    'deposit_pay_now' => 'Acompte (À payer)',
    'deposit_remaining_later' => 'Le solde restant sera facturé lors du service',
];
