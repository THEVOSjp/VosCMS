<?php

/**
 * Validation messages - Turkish (Türkçe)
 */

return [
    // Basic messages
    'accepted' => ':attribute kabul edilmelidir.',
    'required' => ':attribute alanı zorunludur.',
    'email' => 'Lütfen geçerli bir e-posta adresi girin.',
    'min' => ':attribute en az :min karakter olmalıdır.',
    'max' => ':attribute en fazla :max karakter olabilir.',
    'numeric' => ':attribute bir sayı olmalıdır.',
    'integer' => ':attribute bir tam sayı olmalıdır.',
    'string' => ':attribute bir metin olmalıdır.',
    'boolean' => ':attribute true veya false olmalıdır.',
    'array' => ':attribute bir dizi olmalıdır.',
    'date' => ':attribute geçerli bir tarih değil.',
    'same' => ':attribute onayı eşleşmiyor.',
    'confirmed' => ':attribute onayı eşleşmiyor.',
    'unique' => ':attribute zaten alınmış.',
    'exists' => 'Seçilen :attribute geçersiz.',
    'in' => 'Seçilen :attribute geçersiz.',
    'not_in' => 'Seçilen :attribute geçersiz.',
    'regex' => ':attribute formatı geçersiz.',
    'url' => ':attribute formatı geçersiz.',
    'alpha' => ':attribute sadece harf içerebilir.',
    'alpha_num' => ':attribute sadece harf ve rakam içerebilir.',
    'alpha_dash' => ':attribute sadece harf, rakam, tire ve alt çizgi içerebilir.',

    // Size
    'size' => [
        'numeric' => ':attribute :size olmalıdır.',
        'string' => ':attribute :size karakter olmalıdır.',
        'array' => ':attribute :size öğe içermelidir.',
    ],

    'between' => [
        'numeric' => ':attribute :min ile :max arasında olmalıdır.',
        'string' => ':attribute :min ile :max karakter arasında olmalıdır.',
        'array' => ':attribute :min ile :max öğe arasında olmalıdır.',
    ],

    // File
    'file' => ':attribute bir dosya olmalıdır.',
    'image' => ':attribute bir görsel olmalıdır.',
    'mimes' => ':attribute şu türde bir dosya olmalıdır: :values.',
    'max_file' => ':attribute :max KB\'dan büyük olamaz.',

    // Password
    'password' => [
        'lowercase' => 'Şifre en az bir küçük harf içermelidir.',
        'uppercase' => 'Şifre en az bir büyük harf içermelidir.',
        'number' => 'Şifre en az bir rakam içermelidir.',
        'special' => 'Şifre en az bir özel karakter içermelidir.',
    ],

    // Date
    'date_format' => ':attribute :format formatıyla eşleşmiyor.',
    'after' => ':attribute :date tarihinden sonra olmalıdır.',
    'before' => ':attribute :date tarihinden önce olmalıdır.',
    'after_or_equal' => ':attribute :date tarihine eşit veya sonra olmalıdır.',
    'before_or_equal' => ':attribute :date tarihine eşit veya önce olmalıdır.',

    // Attribute names
    'attributes' => [
        'name' => 'ad',
        'email' => 'e-posta',
        'password' => 'şifre',
        'password_confirmation' => 'şifre onayı',
        'phone' => 'telefon',
        'customer_name' => 'ad',
        'customer_email' => 'e-posta',
        'customer_phone' => 'telefon',
        'booking_date' => 'rezervasyon tarihi',
        'start_time' => 'başlangıç saati',
        'guests' => 'misafirler',
        'service_id' => 'hizmet',
        'category_id' => 'kategori',
        'duration' => 'süre',
        'price' => 'fiyat',
        'description' => 'açıklama',
        'notes' => 'notlar',
        'reason' => 'sebep',
    ],
];
