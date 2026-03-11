<?php

/**
 * Authentication translations - French
 */

return [
    // Login
    'login' => [
        'title' => 'Connexion',
        'description' => 'Connectez-vous a votre compte pour gerer vos reservations',
        'email' => 'E-mail',
        'email_placeholder' => 'exemple@email.com',
        'password' => 'Mot de passe',
        'password_placeholder' => '••••••••',
        'remember' => 'Se souvenir de moi',
        'forgot' => 'Mot de passe oublie ?',
        'submit' => 'Se connecter',
        'no_account' => 'Vous n\'avez pas de compte ?',
        'register_link' => 'S\'inscrire',
        'back_home' => '← Retour a l\'accueil',
        'success' => 'Connexion reussie.',
        'failed' => 'E-mail ou mot de passe incorrect.',
        'required' => 'Veuillez entrer votre e-mail et mot de passe.',
        'error' => 'Une erreur est survenue lors de la connexion.',
        'social_only' => 'Ce compte a ete enregistre via une connexion sociale. Veuillez utiliser la connexion sociale.',
    ],

    // Register
    'register' => [
        'title' => 'Inscription',
        'description' => 'Commencez a effectuer des reservations avec RezlyX',
        'name' => 'Nom',
        'name_placeholder' => 'Jean Dupont',
        'email' => 'E-mail',
        'phone' => 'Telephone',
        'phone_placeholder' => '06 12 34 56 78',
        'phone_hint' => 'Selectionnez l\'indicatif pays et entrez votre numero',
        'password' => 'Mot de passe',
        'password_placeholder' => 'Au moins 12 caracteres',
        'password_hint' => 'Min 12 caracteres avec majuscule, minuscule, chiffre et caractere special',
        'password_confirm' => 'Confirmer le mot de passe',
        'password_confirm_placeholder' => 'Ressaisir le mot de passe',
        'agree_terms' => ' J\'accepte',
        'agree_privacy' => ' J\'accepte',
        'submit' => 'S\'inscrire',
        'has_account' => 'Vous avez deja un compte ?',
        'login_link' => 'Se connecter',
        'success' => 'Inscription reussie.',
        'success_login' => 'Aller a la connexion',
        'email_exists' => 'Cet e-mail est deja enregistre.',
        'error' => 'Une erreur est survenue lors de l\'inscription.',
    ],

    // Forgot password
    'forgot' => [
        'title' => 'Mot de passe oublie',
        'description' => 'Entrez votre adresse e-mail et nous vous enverrons un lien de reinitialisation.',
        'email' => 'E-mail',
        'submit' => 'Envoyer le lien de reinitialisation',
        'back_login' => 'Retour a la connexion',
        'success' => 'Le lien de reinitialisation a ete envoye a votre e-mail.',
        'not_found' => 'Adresse e-mail introuvable.',
    ],

    // Reset password
    'reset' => [
        'title' => 'Reinitialiser le mot de passe',
        'email' => 'E-mail',
        'password' => 'Nouveau mot de passe',
        'password_confirm' => 'Confirmer le nouveau mot de passe',
        'submit' => 'Reinitialiser le mot de passe',
        'success' => 'Votre mot de passe a ete reinitialise.',
        'invalid_token' => 'Jeton invalide.',
        'expired_token' => 'Le jeton a expire.',
    ],

    // Logout
    'logout' => [
        'success' => 'Deconnexion reussie.',
    ],

    // Email verification
    'verify' => [
        'title' => 'Verifier l\'e-mail',
        'description' => 'Nous avons envoye un e-mail de verification a votre adresse. Veuillez verifier votre e-mail.',
        'resend' => 'Renvoyer l\'e-mail de verification',
        'success' => 'E-mail verifie avec succes.',
        'already_verified' => 'L\'e-mail est deja verifie.',
    ],

    // Social login
    'social' => [
        'or' => 'ou',
        'google' => 'Se connecter avec Google',
        'kakao' => 'Se connecter avec Kakao',
        'naver' => 'Se connecter avec Naver',
        'line' => 'Se connecter avec LINE',
    ],

    // Social login buttons
    'login_with_line' => 'Se connecter avec LINE',
    'login_with_google' => 'Se connecter avec Google',
    'login_with_kakao' => 'Se connecter avec Kakao',
    'login_with_naver' => 'Se connecter avec Naver',
    'login_with_apple' => 'Se connecter avec Apple',
    'login_with_facebook' => 'Se connecter avec Facebook',
    'or_continue_with' => 'ou',

    // Terms Agreement
    'terms' => [
        'title' => 'Accord des conditions',
        'subtitle' => 'Veuillez accepter les conditions pour utiliser le service',
        'agree_all' => 'J\'accepte toutes les conditions',
        'required' => 'Requis',
        'optional' => 'Optionnel',
        'required_mark' => 'Requis',
        'required_note' => '* indique les elements requis',
        'required_alert' => 'Veuillez accepter toutes les conditions requises.',
        'notice' => 'Vous ne pourrez peut-etre pas utiliser le service si vous n\'acceptez pas les conditions.',
        'view_content' => 'Voir le contenu',
        'hide_content' => 'Masquer le contenu',
        'translation_pending' => 'Traduction en cours',
    ],

    // My Page
    'mypage' => [
        'title' => 'Mon espace',
        'welcome' => 'Bonjour, :name !',
        'member_since' => 'Membre depuis :date',
        'menu' => [
            'dashboard' => 'Tableau de bord',
            'reservations' => 'Reservations',
            'profile' => 'Profil',
            'settings' => 'Paramètres',
            'password' => 'Changer le mot de passe',
            'withdraw' => 'Supprimer le compte',
            'logout' => 'Deconnexion',
        ],
        'stats' => [
            'total_reservations' => 'Total des reservations',
            'upcoming' => 'A venir',
            'completed' => 'Terminees',
            'cancelled' => 'Annulees',
        ],
        'recent_reservations' => 'Reservations recentes',
        'no_reservations' => 'Aucune reservation trouvee.',
        'view_all' => 'Voir tout',
        'quick_actions' => 'Actions rapides',
        'make_reservation' => 'Effectuer une reservation',
    ],

    // Profile
    'profile' => [
        'title' => 'Profil',
        'description' => 'Mes informations de profil.',
        'edit_title' => 'Modifier le profil',
        'edit_description' => 'Modifiez vos informations personnelles.',
        'edit_button' => 'Modifier',
        'name' => 'Nom',
        'email' => 'E-mail',
        'email_hint' => 'L\'e-mail ne peut pas etre modifie.',
        'phone' => 'Telephone',
        'not_set' => 'Non défini',
        'submit' => 'Enregistrer',
        'success' => 'Profil mis a jour avec succes.',
        'error' => 'Une erreur est survenue lors de la mise a jour du profil.',
    ],

    // Settings
    'settings' => [
        'title' => 'Paramètres de confidentialité',
        'description' => 'Choisissez les informations à afficher aux autres utilisateurs.',
        'info' => 'Les éléments désactivés ne seront pas visibles par les autres utilisateurs. Le nom est toujours visible.',
        'success' => 'Paramètres enregistrés.',
        'error' => 'Erreur lors de l\'enregistrement des paramètres.',
        'no_fields' => 'Aucun champ configurable.',
        'fields' => [
            'email' => 'E-mail', 'email_desc' => 'Afficher votre adresse e-mail aux autres utilisateurs.',
            'profile_photo' => 'Photo de profil', 'profile_photo_desc' => 'Afficher la photo de profil aux autres utilisateurs.',
            'phone' => 'Téléphone', 'phone_desc' => 'Afficher le téléphone aux autres utilisateurs.',
            'birth_date' => 'Date de naissance', 'birth_date_desc' => 'Afficher la date de naissance aux autres utilisateurs.',
            'gender' => 'Genre', 'gender_desc' => 'Afficher le genre aux autres utilisateurs.',
            'company' => 'Entreprise', 'company_desc' => 'Afficher l\'entreprise aux autres utilisateurs.',
            'blog' => 'Blog', 'blog_desc' => 'Afficher l\'URL du blog aux autres utilisateurs.',
        ],
    ],

    // Change Password
    'password_change' => [
        'title' => 'Changer le mot de passe',
        'description' => 'Veuillez changer regulierement votre mot de passe pour votre securite.',
        'current' => 'Mot de passe actuel',
        'current_placeholder' => 'Entrez le mot de passe actuel',
        'new' => 'Nouveau mot de passe',
        'new_placeholder' => 'Entrez le nouveau mot de passe',
        'confirm' => 'Confirmer le nouveau mot de passe',
        'confirm_placeholder' => 'Ressaisir le nouveau mot de passe',
        'submit' => 'Changer le mot de passe',
        'success' => 'Mot de passe change avec succes.',
        'error' => 'Une erreur est survenue lors du changement de mot de passe.',
        'wrong_password' => 'Le mot de passe actuel est incorrect.',
    ],

    // Supprimer le compte
    'withdraw' => [
        'title' => 'Supprimer le compte',
        'description' => 'Vos informations personnelles seront immédiatement anonymisées lors de la suppression du compte. Cette action est irréversible.',
        'warning_title' => 'Veuillez lire attentivement avant de continuer',
        'warnings' => [
            'account' => 'Toutes les informations personnelles (nom, e-mail, téléphone, date de naissance, photo de profil) seront immédiatement anonymisées. Votre identification ne sera plus possible.',
            'reservation' => 'Si vous avez des réservations en cours ou à venir, veuillez les annuler avant de supprimer votre compte. Après la suppression, aucune modification ni annulation ne sera possible.',
            'payment' => 'Les registres de paiement et de transactions seront conservés sous forme anonymisée pendant la durée légale requise (5 ans selon la loi fiscale coréenne, 7 ans selon la loi fiscale japonaise).',
            'recovery' => 'Les comptes supprimés ne peuvent pas être récupérés. Vous pouvez vous réinscrire avec le même e-mail, mais les données précédentes (réservations, points, messages) ne seront pas restaurées.',
            'social' => 'Si vous vous êtes inscrit via un login social (Google, Kakao, LINE, etc.), la connexion avec ce service sera également supprimée.',
            'message' => 'Tous les messages reçus et l\'historique des notifications seront définitivement supprimés.',
        ],
        'retention_notice' => '※ Les enregistrements de transactions requis par la loi seront conservés sous une forme non identifiable pendant la durée légale, puis définitivement supprimés.',
        'reason' => 'Raison de la suppression',
        'reason_placeholder' => 'Veuillez sélectionner une raison',
        'reasons' => [
            'not_using' => 'Je n\'utilise plus le service',
            'other_service' => 'Passage à un autre service',
            'dissatisfied' => 'Insatisfait du service',
            'privacy' => 'Préoccupations de confidentialité',
            'too_many_emails' => 'Trop d\'e-mails/notifications',
            'other' => 'Autre',
        ],
        'reason_other' => 'Autre raison',
        'reason_other_placeholder' => 'Veuillez entrer votre raison',
        'password' => 'Confirmer le mot de passe',
        'password_placeholder' => 'Entrez le mot de passe actuel',
        'password_hint' => 'Veuillez entrer votre mot de passe actuel pour vérifier votre identité.',
        'confirm_text' => 'J\'ai lu et compris toutes les informations ci-dessus et j\'accepte l\'anonymisation de mes données personnelles et la suppression de mon compte.',
        'submit' => 'Supprimer le compte',
        'success' => 'Votre compte a été supprimé. Merci d\'avoir utilisé notre service.',
        'wrong_password' => 'Mot de passe incorrect.',
        'error' => 'Une erreur est survenue lors de la suppression du compte.',
        'confirm_required' => 'Veuillez cocher la case d\'acceptation.',
    ],
];
