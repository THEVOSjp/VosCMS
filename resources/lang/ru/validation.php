<?php

/**
 * Validation messages - Russian
 */

return [
    // Basic messages
    'accepted' => ':attribute должен быть принят.',
    'required' => 'Поле :attribute обязательно для заполнения.',
    'email' => 'Введите корректный адрес эл. почты.',
    'min' => 'Поле :attribute должно содержать не менее :min символов.',
    'max' => 'Поле :attribute не должно превышать :max символов.',
    'numeric' => 'Поле :attribute должно быть числом.',
    'integer' => 'Поле :attribute должно быть целым числом.',
    'string' => 'Поле :attribute должно быть строкой.',
    'boolean' => 'Поле :attribute должно иметь значение истина или ложь.',
    'array' => 'Поле :attribute должно быть массивом.',
    'date' => 'Поле :attribute не является корректной датой.',
    'same' => 'Подтверждение поля :attribute не совпадает.',
    'confirmed' => 'Подтверждение поля :attribute не совпадает.',
    'unique' => 'Такое значение поля :attribute уже существует.',
    'exists' => 'Выбранное значение поля :attribute недопустимо.',
    'in' => 'Выбранное значение поля :attribute недопустимо.',
    'not_in' => 'Выбранное значение поля :attribute недопустимо.',
    'regex' => 'Формат поля :attribute недопустим.',
    'url' => 'Формат поля :attribute недопустим.',
    'alpha' => 'Поле :attribute может содержать только буквы.',
    'alpha_num' => 'Поле :attribute может содержать только буквы и цифры.',
    'alpha_dash' => 'Поле :attribute может содержать только буквы, цифры, дефисы и подчёркивания.',

    // Size
    'size' => [
        'numeric' => 'Поле :attribute должно быть равно :size.',
        'string' => 'Поле :attribute должно содержать :size символов.',
        'array' => 'Поле :attribute должно содержать :size элементов.',
    ],

    'between' => [
        'numeric' => 'Поле :attribute должно быть от :min до :max.',
        'string' => 'Поле :attribute должно содержать от :min до :max символов.',
        'array' => 'Поле :attribute должно содержать от :min до :max элементов.',
    ],

    // File
    'file' => 'Поле :attribute должно быть файлом.',
    'image' => 'Поле :attribute должно быть изображением.',
    'mimes' => 'Поле :attribute должно быть файлом типа: :values.',
    'max_file' => 'Поле :attribute не должно превышать :max килобайт.',

    // Password
    'password' => [
        'lowercase' => 'Пароль должен содержать хотя бы одну строчную букву.',
        'uppercase' => 'Пароль должен содержать хотя бы одну заглавную букву.',
        'number' => 'Пароль должен содержать хотя бы одну цифру.',
        'special' => 'Пароль должен содержать хотя бы один специальный символ.',
    ],

    // Date
    'date_format' => 'Поле :attribute не соответствует формату :format.',
    'after' => 'Поле :attribute должно быть датой после :date.',
    'before' => 'Поле :attribute должно быть датой до :date.',
    'after_or_equal' => 'Поле :attribute должно быть датой после или равной :date.',
    'before_or_equal' => 'Поле :attribute должно быть датой до или равной :date.',

    // Attribute names
    'attributes' => [
        'name' => 'имя',
        'email' => 'эл. почта',
        'password' => 'пароль',
        'password_confirmation' => 'подтверждение пароля',
        'phone' => 'телефон',
        'customer_name' => 'имя',
        'customer_email' => 'эл. почта',
        'customer_phone' => 'телефон',
        'booking_date' => 'дата бронирования',
        'start_time' => 'время начала',
        'guests' => 'гости',
        'service_id' => 'услуга',
        'category_id' => 'категория',
        'duration' => 'продолжительность',
        'price' => 'цена',
        'description' => 'описание',
        'notes' => 'примечания',
        'reason' => 'причина',
    ],
];
