<?php
/**
 * Spanish Language File
 */

return [
    // Common
    'app_name' => 'RezlyX',
    'welcome' => 'Bienvenido',
    'home' => 'Inicio',
    'back' => 'Volver',
    'next' => 'Siguiente',
    'cancel' => 'Cancelar',
    'confirm' => 'Confirmar',
    'save' => 'Guardar',
    'delete' => 'Eliminar',
    'edit' => 'Editar',
    'search' => 'Buscar',
    'loading' => 'Cargando...',
    'no_data' => 'No hay datos disponibles.',
    'error' => 'Ha ocurrido un error.',
    'success' => 'Procesado correctamente.',

    // Auth
    'auth' => [
        'login' => 'Iniciar sesion',
        'logout' => 'Cerrar sesion',
        'register' => 'Registrarse',
        'email' => 'Correo electronico',
        'password' => 'Contrasena',
        'password_confirm' => 'Confirmar contrasena',
        'remember_me' => 'Recordarme',
        'forgot_password' => 'Olvido su contrasena?',
        'reset_password' => 'Restablecer contrasena',
        'invalid_credentials' => 'Correo o contrasena invalidos.',
        'account_inactive' => 'Esta cuenta esta inactiva.',
    ],

    // Reservation
    'reservation' => [
        'title' => 'Reserva',
        'new' => 'Nueva reserva',
        'my_reservations' => 'Mis reservas',
        'select_service' => 'Seleccionar servicio',
        'select_date' => 'Seleccionar fecha',
        'select_time' => 'Seleccionar hora',
        'customer_info' => 'Su informacion',
        'payment' => 'Pago',
        'confirmation' => 'Confirmacion',
        'status' => [
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmada',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            'no_show' => 'No se presento',
        ],
    ],

    // Services
    'service' => [
        'title' => 'Servicios',
        'category' => 'Categoria',
        'price' => 'Precio',
        'duration' => 'Duracion',
        'description' => 'Descripcion',
        'options' => 'Opciones',
    ],

    // Member
    'member' => [
        'profile' => 'Mi perfil',
        'points' => 'Puntos',
        'grade' => 'Nivel de membresia',
        'reservations' => 'Historial de reservas',
        'payments' => 'Historial de pagos',
        'settings' => 'Configuracion',
    ],

    // Payment
    'payment' => [
        'title' => 'Pago',
        'amount' => 'Monto',
        'method' => 'Metodo de pago',
        'card' => 'Tarjeta de credito',
        'bank_transfer' => 'Transferencia bancaria',
        'virtual_account' => 'Cuenta virtual',
        'points' => 'Puntos',
        'use_points' => 'Usar puntos',
        'available_points' => 'Puntos disponibles',
        'complete' => 'Pago completado',
        'failed' => 'Pago fallido',
    ],

    // Time
    'time' => [
        'today' => 'Hoy',
        'tomorrow' => 'Manana',
        'minutes' => 'min',
        'hours' => 'horas',
        'days' => 'dias',
    ],

    // Validation
    'validation' => [
        'required' => 'El campo :attribute es obligatorio.',
        'email' => 'Por favor, ingrese un correo electronico valido.',
        'min' => 'El campo :attribute debe tener al menos :min caracteres.',
        'max' => 'El campo :attribute no puede tener mas de :max caracteres.',
        'confirmed' => 'La confirmacion de :attribute no coincide.',
    ],
];
