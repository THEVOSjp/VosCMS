<?php

/**
 * Validation messages - Spanish
 */

return [
    // Basic messages
    'accepted' => ':attribute debe ser aceptado.',
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'Por favor, ingrese una direccion de correo electronico valida.',
    'min' => 'El campo :attribute debe tener al menos :min caracteres.',
    'max' => 'El campo :attribute no puede tener mas de :max caracteres.',
    'numeric' => 'El campo :attribute debe ser un numero.',
    'integer' => 'El campo :attribute debe ser un numero entero.',
    'string' => 'El campo :attribute debe ser una cadena de texto.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'array' => 'El campo :attribute debe ser una matriz.',
    'date' => 'El campo :attribute no es una fecha valida.',
    'same' => 'La confirmacion de :attribute no coincide.',
    'confirmed' => 'La confirmacion de :attribute no coincide.',
    'unique' => 'El :attribute ya ha sido tomado.',
    'exists' => 'El :attribute seleccionado no es valido.',
    'in' => 'El :attribute seleccionado no es valido.',
    'not_in' => 'El :attribute seleccionado no es valido.',
    'regex' => 'El formato de :attribute no es valido.',
    'url' => 'El formato de :attribute no es valido.',
    'alpha' => 'El campo :attribute solo puede contener letras.',
    'alpha_num' => 'El campo :attribute solo puede contener letras y numeros.',
    'alpha_dash' => 'El campo :attribute solo puede contener letras, numeros, guiones y guiones bajos.',

    // Size
    'size' => [
        'numeric' => 'El campo :attribute debe ser :size.',
        'string' => 'El campo :attribute debe tener :size caracteres.',
        'array' => 'El campo :attribute debe contener :size elementos.',
    ],

    'between' => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
        'array' => 'El campo :attribute debe tener entre :min y :max elementos.',
    ],

    // File
    'file' => 'El campo :attribute debe ser un archivo.',
    'image' => 'El campo :attribute debe ser una imagen.',
    'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'max_file' => 'El campo :attribute no puede ser mayor de :max kilobytes.',

    // Password
    'password' => [
        'lowercase' => 'La contrasena debe contener al menos una letra minuscula.',
        'uppercase' => 'La contrasena debe contener al menos una letra mayuscula.',
        'number' => 'La contrasena debe contener al menos un numero.',
        'special' => 'La contrasena debe contener al menos un caracter especial.',
    ],

    // Date
    'date_format' => 'El campo :attribute no coincide con el formato :format.',
    'after' => 'El campo :attribute debe ser una fecha posterior a :date.',
    'before' => 'El campo :attribute debe ser una fecha anterior a :date.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha posterior o igual a :date.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha anterior o igual a :date.',

    // Attribute names
    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electronico',
        'password' => 'contrasena',
        'password_confirmation' => 'confirmacion de contrasena',
        'phone' => 'telefono',
        'customer_name' => 'nombre',
        'customer_email' => 'correo electronico',
        'customer_phone' => 'telefono',
        'booking_date' => 'fecha de reserva',
        'start_time' => 'hora de inicio',
        'guests' => 'invitados',
        'service_id' => 'servicio',
        'category_id' => 'categoria',
        'duration' => 'duracion',
        'price' => 'precio',
        'description' => 'descripcion',
        'notes' => 'notas',
        'reason' => 'motivo',
    ],
];
