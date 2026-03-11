<?php

/**
 * Validation messages - Indonesian
 */

return [
    // Basic messages
    'accepted' => ':attribute harus diterima.',
    'required' => ':attribute wajib diisi.',
    'email' => 'Masukkan alamat email yang valid.',
    'min' => ':attribute minimal harus :min karakter.',
    'max' => ':attribute tidak boleh lebih dari :max karakter.',
    'numeric' => ':attribute harus berupa angka.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'string' => ':attribute harus berupa string.',
    'boolean' => ':attribute harus bernilai true atau false.',
    'array' => ':attribute harus berupa array.',
    'date' => ':attribute bukan tanggal yang valid.',
    'same' => 'Konfirmasi :attribute tidak cocok.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'unique' => ':attribute sudah digunakan.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'in' => ':attribute yang dipilih tidak valid.',
    'not_in' => ':attribute yang dipilih tidak valid.',
    'regex' => 'Format :attribute tidak valid.',
    'url' => 'Format :attribute tidak valid.',
    'alpha' => ':attribute hanya boleh berisi huruf.',
    'alpha_num' => ':attribute hanya boleh berisi huruf dan angka.',
    'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, tanda hubung dan garis bawah.',

    // Size
    'size' => [
        'numeric' => ':attribute harus :size.',
        'string' => ':attribute harus :size karakter.',
        'array' => ':attribute harus berisi :size item.',
    ],

    'between' => [
        'numeric' => ':attribute harus antara :min dan :max.',
        'string' => ':attribute harus antara :min dan :max karakter.',
        'array' => ':attribute harus memiliki antara :min dan :max item.',
    ],

    // File
    'file' => ':attribute harus berupa file.',
    'image' => ':attribute harus berupa gambar.',
    'mimes' => ':attribute harus berupa file bertipe: :values.',
    'max_file' => ':attribute tidak boleh lebih dari :max kilobyte.',

    // Password
    'password' => [
        'lowercase' => 'Kata sandi harus mengandung minimal satu huruf kecil.',
        'uppercase' => 'Kata sandi harus mengandung minimal satu huruf besar.',
        'number' => 'Kata sandi harus mengandung minimal satu angka.',
        'special' => 'Kata sandi harus mengandung minimal satu karakter khusus.',
    ],

    // Date
    'date_format' => ':attribute tidak cocok dengan format :format.',
    'after' => ':attribute harus tanggal setelah :date.',
    'before' => ':attribute harus tanggal sebelum :date.',
    'after_or_equal' => ':attribute harus tanggal setelah atau sama dengan :date.',
    'before_or_equal' => ':attribute harus tanggal sebelum atau sama dengan :date.',

    // Attribute names
    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
        'phone' => 'telepon',
        'customer_name' => 'nama',
        'customer_email' => 'email',
        'customer_phone' => 'telepon',
        'booking_date' => 'tanggal pemesanan',
        'start_time' => 'waktu mulai',
        'guests' => 'tamu',
        'service_id' => 'layanan',
        'category_id' => 'kategori',
        'duration' => 'durasi',
        'price' => 'harga',
        'description' => 'deskripsi',
        'notes' => 'catatan',
        'reason' => 'alasan',
    ],
];
