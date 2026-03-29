<?php

/**
 * Validation messages - English
 */

return [
    // Basic messages
    'accepted' => 'The :attribute must be accepted.',
    'required' => 'The :attribute field is required.',
    'email' => 'Please enter a valid email address.',
    'min' => 'The :attribute must be at least :min characters.',
    'max' => 'The :attribute may not be greater than :max characters.',
    'numeric' => 'The :attribute must be a number.',
    'integer' => 'The :attribute must be an integer.',
    'string' => 'The :attribute must be a string.',
    'boolean' => 'The :attribute must be true or false.',
    'array' => 'The :attribute must be an array.',
    'date' => 'The :attribute is not a valid date.',
    'same' => 'The :attribute confirmation does not match.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'unique' => 'The :attribute has already been taken.',
    'exists' => 'The selected :attribute is invalid.',
    'in' => 'The selected :attribute is invalid.',
    'not_in' => 'The selected :attribute is invalid.',
    'regex' => 'The :attribute format is invalid.',
    'url' => 'The :attribute format is invalid.',
    'alpha' => 'The :attribute may only contain letters.',
    'alpha_num' => 'The :attribute may only contain letters and numbers.',
    'alpha_dash' => 'The :attribute may only contain letters, numbers, dashes and underscores.',

    // Size
    'size' => [
        'numeric' => 'The :attribute must be :size.',
        'string' => 'The :attribute must be :size characters.',
        'array' => 'The :attribute must contain :size items.',
    ],

    'between' => [
        'numeric' => 'The :attribute must be between :min and :max.',
        'string' => 'The :attribute must be between :min and :max characters.',
        'array' => 'The :attribute must have between :min and :max items.',
    ],

    // File
    'file' => 'The :attribute must be a file.',
    'image' => 'The :attribute must be an image.',
    'mimes' => 'The :attribute must be a file of type: :values.',
    'max_file' => 'The :attribute may not be greater than :max kilobytes.',

    // Password
    'password' => [
        'lowercase' => 'Password must contain at least one lowercase letter.',
        'uppercase' => 'Password must contain at least one uppercase letter.',
        'number' => 'Password must contain at least one number.',
        'special' => 'Password must contain at least one special character.',
    ],

    // Date
    'date_format' => 'The :attribute does not match the format :format.',
    'after' => 'The :attribute must be a date after :date.',
    'before' => 'The :attribute must be a date before :date.',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date.',
    'before_or_equal' => 'The :attribute must be a date before or equal to :date.',

    // Attribute names
    'attributes' => [
        'name' => 'name',
        'email' => 'email',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'phone' => 'phone',
        'customer_name' => 'name',
        'customer_email' => 'email',
        'customer_phone' => 'phone',
        'booking_date' => 'booking date',
        'start_time' => 'start time',
        'guests' => 'guests',
        'service_id' => 'service',
        'category_id' => 'category',
        'duration' => 'duration',
        'price' => 'price',
        'description' => 'description',
        'notes' => 'notes',
        'reason' => 'reason',
    ],
];
