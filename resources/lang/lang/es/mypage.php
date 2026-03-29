<?php

/**
 * My Page translations - Spanish
 */

return [
    // Main
    'title' => 'Mi pagina',
    'welcome' => 'Bienvenido, :name!',

    // Navigation
    'nav' => [
        'dashboard' => 'Panel de control',
        'reservations' => 'Mis reservas',
        'profile' => 'Perfil',
        'password' => 'Cambiar contrasena',
        'logout' => 'Cerrar sesion',
    ],

    // Dashboard
    'dashboard' => [
        'upcoming' => 'Proximas reservas',
        'recent' => 'Reservas recientes',
        'no_upcoming' => 'No hay proximas reservas.',
        'no_recent' => 'No hay reservas recientes.',
        'view_all' => 'Ver todas',
    ],

    // Reservations
    'reservations' => [
        'title' => 'Historial de reservas',
        'filter' => [
            'all' => 'Todas',
            'pending' => 'Pendientes',
            'confirmed' => 'Confirmadas',
            'completed' => 'Completadas',
            'cancelled' => 'Canceladas',
        ],
        'no_reservations' => 'No se encontraron reservas.',
        'booking_code' => 'Codigo de reserva',
        'service' => 'Servicio',
        'date' => 'Fecha',
        'status' => 'Estado',
        'actions' => 'Acciones',
        'view' => 'Ver',
        'cancel' => 'Cancelar',
    ],

    // Profile
    'profile' => [
        'title' => 'Configuracion del perfil',
        'info' => 'Informacion basica',
        'name' => 'Nombre',
        'email' => 'Correo electronico',
        'phone' => 'Telefono',
        'save' => 'Guardar',
        'success' => 'Perfil actualizado correctamente.',
    ],

    // Password
    'password' => [
        'title' => 'Cambiar contrasena',
        'current' => 'Contrasena actual',
        'new' => 'Nueva contrasena',
        'confirm' => 'Confirmar nueva contrasena',
        'change' => 'Cambiar contrasena',
        'success' => 'Contrasena cambiada correctamente.',
        'mismatch' => 'La contrasena actual es incorrecta.',
    ],

    // Stats
    'stats' => [
        'total_bookings' => 'Total de reservas',
        'completed' => 'Completadas',
        'cancelled' => 'Canceladas',
        'upcoming' => 'Proximas',
    ],
];
