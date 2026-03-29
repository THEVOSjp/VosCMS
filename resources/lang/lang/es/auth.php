<?php

/**
 * Authentication translations - Spanish
 */

return [
    // Login
    'login' => [
        'title' => 'Iniciar sesion',
        'description' => 'Inicie sesion en su cuenta para gestionar sus reservas',
        'email' => 'Correo electronico',
        'email_placeholder' => 'ejemplo@correo.com',
        'password' => 'Contrasena',
        'password_placeholder' => '********',
        'remember' => 'Recordarme',
        'forgot' => 'Olvido su contrasena?',
        'submit' => 'Iniciar sesion',
        'no_account' => 'No tiene una cuenta?',
        'register_link' => 'Registrarse',
        'back_home' => '<- Volver al inicio',
        'success' => 'Sesion iniciada correctamente.',
        'failed' => 'Correo o contrasena invalidos.',
        'required' => 'Por favor, ingrese su correo y contrasena.',
        'error' => 'Ocurrio un error durante el inicio de sesion.',
        'social_only' => 'Esta cuenta fue registrada mediante inicio de sesion social. Por favor, use el inicio de sesion social.',
    ],

    // Register
    'register' => [
        'title' => 'Registrarse',
        'description' => 'Comience a hacer reservas con RezlyX',
        'name' => 'Nombre',
        'name_placeholder' => 'Juan Perez',
        'email' => 'Correo electronico',
        'email_placeholder' => 'ejemplo@correo.com',
        'phone' => 'Telefono',
        'phone_placeholder' => '+34 612 345 678',
        'phone_hint' => 'Seleccione el codigo de pais e ingrese su numero de telefono',
        'password' => 'Contrasena',
        'password_placeholder' => 'Al menos 12 caracteres',
        'password_hint' => 'Minimo 12 caracteres con mayuscula, minuscula, numero y caracter especial',
        'password_confirm' => 'Confirmar contrasena',
        'password_confirm_placeholder' => 'Reingrese la contrasena',
        'agree_terms' => ' Acepto',
        'agree_privacy' => ' Acepto',
        'submit' => 'Registrarse',
        'has_account' => 'Ya tiene una cuenta?',
        'login_link' => 'Iniciar sesion',
        'success' => 'Registro completado correctamente.',
        'success_login' => 'Ir a iniciar sesion',
        'email_exists' => 'Este correo ya esta registrado.',
        'error' => 'Ocurrio un error durante el registro.',
    ],

    // Forgot password
    'forgot' => [
        'title' => 'Olvido su contrasena',
        'description' => 'Ingrese su correo electronico y le enviaremos un enlace para restablecer su contrasena.',
        'email' => 'Correo electronico',
        'submit' => 'Enviar enlace de restablecimiento',
        'back_login' => 'Volver a iniciar sesion',
        'success' => 'Se ha enviado un enlace para restablecer la contrasena a su correo.',
        'not_found' => 'Direccion de correo no encontrada.',
    ],

    // Reset password
    'reset' => [
        'title' => 'Restablecer contrasena',
        'email' => 'Correo electronico',
        'password' => 'Nueva contrasena',
        'password_confirm' => 'Confirmar nueva contrasena',
        'submit' => 'Restablecer contrasena',
        'success' => 'Su contrasena ha sido restablecida.',
        'invalid_token' => 'Token invalido.',
        'expired_token' => 'El token ha expirado.',
    ],

    // Logout
    'logout' => [
        'success' => 'Sesion cerrada correctamente.',
    ],

    // Email verification
    'verify' => [
        'title' => 'Verificar correo electronico',
        'description' => 'Hemos enviado un correo de verificacion a su direccion. Por favor, revise su correo.',
        'resend' => 'Reenviar correo de verificacion',
        'success' => 'Correo verificado correctamente.',
        'already_verified' => 'El correo ya esta verificado.',
    ],

    // Social login
    'social' => [
        'or' => 'o',
        'google' => 'Iniciar sesion con Google',
        'kakao' => 'Iniciar sesion con Kakao',
        'naver' => 'Iniciar sesion con Naver',
        'line' => 'Iniciar sesion con LINE',
    ],

    // Social login buttons
    'login_with_line' => 'Iniciar sesion con LINE',
    'login_with_google' => 'Iniciar sesion con Google',
    'login_with_kakao' => 'Iniciar sesion con Kakao',
    'login_with_naver' => 'Iniciar sesion con Naver',
    'login_with_apple' => 'Iniciar sesion con Apple',
    'login_with_facebook' => 'Iniciar sesion con Facebook',
    'or_continue_with' => 'o',

    // Terms Agreement
    'terms' => [
        'title' => 'Acuerdo de terminos',
        'subtitle' => 'Por favor, acepte los terminos para usar el servicio',
        'agree_all' => 'Acepto todos los terminos',
        'required' => 'Obligatorio',
        'optional' => 'Opcional',
        'required_mark' => 'Obligatorio',
        'required_note' => '* indica elementos obligatorios',
        'required_alert' => 'Por favor, acepte todos los terminos obligatorios.',
        'notice' => 'Es posible que no pueda usar el servicio si no acepta los terminos.',
        'view_content' => 'Ver contenido',
        'hide_content' => 'Ocultar contenido',
        'translation_pending' => 'Traduccion en progreso',
    ],

    // My Page
    'mypage' => [
        'title' => 'Mi pagina',
        'welcome' => 'Hola, :name!',
        'member_since' => 'Miembro desde :date',
        'menu' => [
            'dashboard' => 'Panel de control',
            'reservations' => 'Reservas',
            'profile' => 'Perfil',
            'settings' => 'Configuración',
            'password' => 'Cambiar contrasena',
            'withdraw' => 'Eliminar cuenta',
            'logout' => 'Cerrar sesion',
        ],
        'stats' => [
            'total_reservations' => 'Total de reservas',
            'upcoming' => 'Proximas',
            'completed' => 'Completadas',
            'cancelled' => 'Canceladas',
        ],
        'recent_reservations' => 'Reservas recientes',
        'no_reservations' => 'No se encontraron reservas.',
        'view_all' => 'Ver todas',
        'quick_actions' => 'Acciones rapidas',
        'make_reservation' => 'Hacer una reserva',
    ],

    // Profile
    'profile' => [
        'title' => 'Perfil',
        'description' => 'Mi información de perfil.',
        'edit_title' => 'Editar perfil',
        'edit_description' => 'Edita tu información personal.',
        'edit_button' => 'Editar',
        'name' => 'Nombre',
        'email' => 'Correo electronico',
        'email_hint' => 'El correo electronico no se puede cambiar.',
        'phone' => 'Telefono',
        'not_set' => 'No configurado',
        'submit' => 'Guardar',
        'success' => 'Perfil actualizado correctamente.',
        'error' => 'Ocurrio un error al actualizar el perfil.',
    ],

    // Settings
    'settings' => [
        'title' => 'Configuración de privacidad',
        'description' => 'Elige qué información mostrar a otros usuarios.',
        'info' => 'Los elementos desactivados no serán visibles para otros usuarios. El nombre siempre es visible.',
        'success' => 'Configuración guardada.',
        'error' => 'Error al guardar la configuración.',
        'no_fields' => 'No hay campos configurables.',
        'fields' => [
            'email' => 'Correo electrónico', 'email_desc' => 'Mostrar su correo electrónico a otros usuarios.',
            'profile_photo' => 'Foto de perfil', 'profile_photo_desc' => 'Mostrar foto de perfil a otros usuarios.',
            'phone' => 'Teléfono', 'phone_desc' => 'Mostrar teléfono a otros usuarios.',
            'birth_date' => 'Fecha de nacimiento', 'birth_date_desc' => 'Mostrar fecha de nacimiento a otros usuarios.',
            'gender' => 'Género', 'gender_desc' => 'Mostrar género a otros usuarios.',
            'company' => 'Empresa', 'company_desc' => 'Mostrar empresa a otros usuarios.',
            'blog' => 'Blog', 'blog_desc' => 'Mostrar URL del blog a otros usuarios.',
        ],
    ],

    // Change Password
    'password_change' => [
        'title' => 'Cambiar contrasena',
        'description' => 'Por favor, cambie su contrasena regularmente por seguridad.',
        'current' => 'Contrasena actual',
        'current_placeholder' => 'Ingrese la contrasena actual',
        'new' => 'Nueva contrasena',
        'new_placeholder' => 'Ingrese la nueva contrasena',
        'confirm' => 'Confirmar nueva contrasena',
        'confirm_placeholder' => 'Reingrese la nueva contrasena',
        'submit' => 'Cambiar contrasena',
        'success' => 'Contrasena cambiada correctamente.',
        'error' => 'Ocurrio un error al cambiar la contrasena.',
        'wrong_password' => 'La contrasena actual es incorrecta.',
    ],

    // Eliminar cuenta
    'withdraw' => [
        'title' => 'Eliminar cuenta',
        'description' => 'Su información personal será anonimizada inmediatamente al eliminar la cuenta. Esta acción no se puede deshacer.',
        'warning_title' => 'Lea atentamente antes de continuar',
        'warnings' => [
            'account' => 'Toda la información personal, incluyendo nombre, correo electrónico, teléfono, fecha de nacimiento y foto de perfil, será anonimizada inmediatamente. Ya no será posible identificarle.',
            'reservation' => 'Si tiene reservas activas o programadas, cancélelas antes de eliminar su cuenta. Después de la eliminación, no podrá modificar ni cancelar reservas.',
            'payment' => 'Los registros de pago y transacciones se conservarán de forma anónima durante el período legalmente requerido (5 años según la ley fiscal coreana, 7 años según la ley fiscal japonesa).',
            'recovery' => 'Las cuentas eliminadas no pueden recuperarse. Puede registrarse nuevamente con el mismo correo, pero los datos anteriores como reservas, puntos y mensajes no se restaurarán.',
            'social' => 'Si se registró mediante inicio de sesión social (Google, Kakao, LINE, etc.), la conexión con ese servicio también se eliminará.',
            'message' => 'Todos los mensajes recibidos y el historial de notificaciones se eliminarán permanentemente.',
        ],
        'retention_notice' => '※ Los registros de transacciones requeridos por las leyes aplicables se conservarán en forma no identificable durante el período legal y luego se eliminarán permanentemente.',
        'reason' => 'Motivo de eliminación',
        'reason_placeholder' => 'Seleccione un motivo',
        'reasons' => [
            'not_using' => 'Ya no uso el servicio',
            'other_service' => 'Cambio a otro servicio',
            'dissatisfied' => 'Insatisfecho con el servicio',
            'privacy' => 'Preocupaciones de privacidad',
            'too_many_emails' => 'Demasiados correos/notificaciones',
            'other' => 'Otro',
        ],
        'reason_other' => 'Otro motivo',
        'reason_other_placeholder' => 'Ingrese su motivo',
        'password' => 'Confirmar contraseña',
        'password_placeholder' => 'Ingrese contraseña actual',
        'password_hint' => 'Ingrese su contraseña actual para verificar su identidad.',
        'confirm_text' => 'He leído y comprendido toda la información anterior, y acepto la anonimización de mis datos personales y la eliminación de mi cuenta.',
        'submit' => 'Eliminar cuenta',
        'success' => 'Su cuenta ha sido eliminada. Gracias por usar nuestro servicio.',
        'wrong_password' => 'Contraseña incorrecta.',
        'error' => 'Ocurrió un error al eliminar la cuenta.',
        'confirm_required' => 'Marque la casilla de aceptación.',
    ],
];
