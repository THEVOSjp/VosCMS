<?php

/**
 * Booking translations - Spanish
 */

return [
    // Page titles
    'title' => 'Reservar ahora',
    'service_list' => 'Lista de servicios',
    'select_service' => 'Seleccionar servicio',
    'select_date' => 'Seleccionar fecha',
    'select_time' => 'Seleccionar hora',
    'enter_info' => 'Ingresar informacion',
    'confirm_booking' => 'Confirmar reserva',
    'confirm_info' => 'Por favor, confirme su informacion de reserva',
    'complete_booking' => 'Completar reserva',
    'select_service_datetime' => 'Por favor, seleccione su servicio y fecha/hora preferida',
    'staff_designation_guide' => 'Para reservas con personal designado, por favor vaya a la página de personal',
    'go_staff_booking' => 'Reserva con personal designado',
    'select_datetime' => 'Por favor, seleccione una fecha y hora',
    'no_services' => 'No hay servicios disponibles actualmente.',
    'contact_admin' => 'Por favor, contacte al administrador.',
    'notes' => 'Solicitudes especiales',
    'notes_placeholder' => 'Ingrese cualquier solicitud especial',
    'customer' => 'Cliente',
    'phone' => 'Telefono',
    'date_label' => 'Fecha',
    'time_label' => 'Hora',
    'total_price' => 'Monto total',
    'cancel_policy' => 'Las cancelaciones estan permitidas hasta 24 horas antes de la hora de la reserva. Puede aplicarse una tarifa de cancelacion para cancelaciones posteriores.',
    'success' => '¡Reserva completada!',
    'success_desc' => 'Se enviará una confirmación. Por favor, guarde su número de reserva.',
    'submitting' => 'Procesando...',
    'select_staff' => 'Seleccione un empleado',
    'no_preference' => 'Sin preferencia',
    'staff' => 'Personal',
    'designation_fee' => 'Tarifa de designación',
    'designation_fee_badge' => '+:amount',
    'loading_slots' => 'Comprobando horarios disponibles...',
    'no_available_slots' => 'No hay horarios disponibles en la fecha seleccionada.',
    'items_selected' => 'seleccionados',
    'total_duration' => 'Duración total',

    // Steps
    'step' => [
        'service' => 'Seleccionar servicio',
        'datetime' => 'Fecha/Hora',
        'info' => 'Informacion',
        'confirm' => 'Confirmar',
    ],

    // Service
    'service' => [
        'title' => 'Servicio',
        'name' => 'Nombre del servicio',
        'description' => 'Descripcion',
        'duration' => 'Duracion',
        'price' => 'Precio',
        'category' => 'Categoria',
        'select' => 'Seleccionar',
        'view_detail' => 'Ver detalles',
        'no_services' => 'No hay servicios disponibles.',
    ],

    // Date/Time
    'date' => [
        'title' => 'Fecha de reserva',
        'select_date' => 'Por favor, seleccione una fecha',
        'available' => 'Disponible',
        'unavailable' => 'No disponible',
        'fully_booked' => 'Completo',
        'past_date' => 'Fecha pasada',
    ],

    'time' => [
        'title' => 'Hora de reserva',
        'select_time' => 'Por favor, seleccione una hora',
        'available_slots' => 'Horarios disponibles',
        'no_slots' => 'No hay horarios disponibles.',
        'remaining' => ':count lugares restantes',
    ],

    // Booking form
    'form' => [
        'customer_name' => 'Nombre',
        'customer_email' => 'Correo electronico',
        'customer_phone' => 'Telefono',
        'guests' => 'Numero de invitados',
        'notes' => 'Solicitudes especiales',
        'notes_placeholder' => 'Ingrese cualquier solicitud especial',
    ],

    // Confirmation
    'confirm' => [
        'title' => 'Confirmar reserva',
        'summary' => 'Resumen de la reserva',
        'service_info' => 'Informacion del servicio',
        'booking_info' => 'Informacion de la reserva',
        'customer_info' => 'Informacion del cliente',
        'total_price' => 'Total',
        'agree_terms' => 'Acepto los terminos de reserva',
        'submit' => 'Completar reserva',
    ],

    // Complete
    'complete' => [
        'title' => 'Reserva completada',
        'success' => 'Su reserva ha sido completada!',
        'booking_code' => 'Codigo de reserva',
        'check_email' => 'Se ha enviado un correo de confirmacion a su direccion de correo electronico.',
        'view_detail' => 'Ver detalles de la reserva',
        'book_another' => 'Hacer otra reserva',
    ],

    // Lookup
    'lookup' => [
        'title' => 'Buscar reserva',
        'description' => 'Ingrese su informacion de reserva para encontrar su reservacion.',
        'booking_code' => 'Codigo de reserva',
        'booking_code_placeholder' => 'RZ250301XXXXXX',
        'email' => 'Correo electronico',
        'email_placeholder' => 'Correo usado para la reserva',
        'phone' => 'Numero de telefono',
        'phone_placeholder' => 'Numero de telefono usado para la reserva',
        'search' => 'Buscar',
        'search_method' => 'Metodo de busqueda',
        'by_code' => 'Buscar por codigo de reserva',
        'by_email' => 'Buscar por correo electronico',
        'by_phone' => 'Buscar por telefono',
        'not_found' => 'Reserva no encontrada. Por favor, verifique su informacion.',
        'input_required' => 'Por favor, ingrese un codigo de reserva y correo electronico o numero de telefono.',
        'result_title' => 'Resultados de busqueda',
        'multiple_results' => ':count reservas encontradas.',
        'hint' => 'Para resultados precisos, ingrese un codigo de reserva junto con su correo electronico o numero de telefono.',
        'help_text' => 'No puede encontrar su reserva?',
        'contact_support' => 'Contactar soporte',
    ],

    // Detail
    'detail' => [
        'title' => 'Detalles de la reserva',
        'status' => 'Estado',
        'booking_date' => 'Fecha y hora',
        'service' => 'Servicio',
        'services' => 'Servicios',
        'guests' => 'Invitados',
        'total_price' => 'Precio total',
        'payment_status' => 'Estado del pago',
        'notes' => 'Solicitudes especiales',
        'created_at' => 'Reservado el',
        'duration_unit' => 'min',
        'staff_not_assigned' => 'No asignado',
        'back_to_lookup' => 'Búsqueda de reservas',
        'payment' => 'Detalles del pago',
        'total' => 'Subtotal',
        'discount' => 'Descuento',
        'points_used' => 'Puntos utilizados',
        'final_amount' => 'Cantidad final',
        'staff' => 'Personal',
        'designation_fee' => 'Tarifa de designación',
        'cancel_info' => 'Detalles de la cancelación',
        'cancelled_at' => 'Cancelado en',
        'cancel_reason' => 'Motivo de la cancelación',
    ],

    // Cancel
    'cancel' => [
        'title' => 'Cancelar reserva',
        'confirm' => 'Esta seguro de que desea cancelar esta reserva?',
        'reason' => 'Motivo de cancelacion',
        'reason_placeholder' => 'Por favor, ingrese el motivo de la cancelacion',
        'submit' => 'Cancelar reserva',
        'success' => 'Su reserva ha sido cancelada.',
        'cannot_cancel' => 'Esta reserva no puede ser cancelada.',
    ],

    // Status messages
    'status' => [
        'pending' => 'Su reserva ha sido recibida. Por favor, espere la confirmacion.',
        'confirmed' => 'Su reserva ha sido confirmada.',
        'cancelled' => 'Su reserva ha sido cancelada.',
        'completed' => 'Servicio completado.',
        'no_show' => 'Marcado como no presentado.',
    ],

    // Payment status
    'payment' => [
        'unpaid' => 'No pagado',
        'paid' => 'Pagado',
        'partial' => 'Parcial',
        'refunded' => 'Reembolsado',
    ],

    // Error messages
    'error' => [
        'service_not_found' => 'Servicio no encontrado.',
        'slot_unavailable' => 'El horario seleccionado no esta disponible.',
        'past_date' => 'No se puede reservar para fechas pasadas.',
        'max_capacity' => 'Capacidad maxima excedida.',
        'booking_failed' => 'Ocurrio un error al procesar su reserva.',
        'required_fields' => 'Por favor, ingrese su nombre e informacion de contacto.',
        'invalid_service' => 'Servicio no válido.',
    ],

    'member_discount' => 'Descuento de miembro',
    'use_points' => 'Usar puntos',
    'points_balance' => 'Saldo',
    'use_all' => 'Usar todo',
    'points_default_name' => 'Puntos',
    'deposit_pay_now' => 'Depósito (Pagar ahora)',
    'deposit_remaining_later' => 'El saldo restante se cobrará en el servicio',
    'next' => 'Siguiente',
    'categories' => 'categorías',
    'service_count' => 'servicios',
    'expected_points' => 'Puntos estimados',
    'reservation_complete' => 'Reserva completada',
    'reservation_complete_desc' => 'Por favor revise su reserva',
    'reservation_number' => 'No. Reserva',
    'check_summary' => 'Ver detalles',
];
