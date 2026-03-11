<?php

/**
 * Validation messages - Mongolian (Монгол)
 */

return [
    // Basic messages
    'accepted' => ':attribute-г зөвшөөрөх шаардлагатай.',
    'required' => ':attribute талбар шаардлагатай.',
    'email' => 'Зөв имэйл хаяг оруулна уу.',
    'min' => ':attribute дор хаяж :min тэмдэгт байх ёстой.',
    'max' => ':attribute :max тэмдэгтээс хэтрэхгүй байх ёстой.',
    'numeric' => ':attribute тоо байх ёстой.',
    'integer' => ':attribute бүхэл тоо байх ёстой.',
    'string' => ':attribute мөр байх ёстой.',
    'boolean' => ':attribute true эсвэл false байх ёстой.',
    'array' => ':attribute массив байх ёстой.',
    'date' => ':attribute зөв огноо биш байна.',
    'same' => ':attribute баталгаажуулалт таарахгүй байна.',
    'confirmed' => ':attribute баталгаажуулалт таарахгүй байна.',
    'unique' => ':attribute аль хэдийн ашиглагдаж байна.',
    'exists' => 'Сонгосон :attribute буруу байна.',
    'in' => 'Сонгосон :attribute буруу байна.',
    'not_in' => 'Сонгосон :attribute буруу байна.',
    'regex' => ':attribute формат буруу байна.',
    'url' => ':attribute формат буруу байна.',
    'alpha' => ':attribute зөвхөн үсэг агуулах ёстой.',
    'alpha_num' => ':attribute зөвхөн үсэг болон тоо агуулах ёстой.',
    'alpha_dash' => ':attribute зөвхөн үсэг, тоо, зураас, доогуур зураас агуулах ёстой.',

    // Size
    'size' => [
        'numeric' => ':attribute :size байх ёстой.',
        'string' => ':attribute :size тэмдэгт байх ёстой.',
        'array' => ':attribute :size зүйл агуулах ёстой.',
    ],

    'between' => [
        'numeric' => ':attribute :min болон :max хооронд байх ёстой.',
        'string' => ':attribute :min болон :max тэмдэгтийн хооронд байх ёстой.',
        'array' => ':attribute :min-аас :max хүртэл зүйл агуулах ёстой.',
    ],

    // File
    'file' => ':attribute файл байх ёстой.',
    'image' => ':attribute зураг байх ёстой.',
    'mimes' => ':attribute дараах төрлийн файл байх ёстой: :values.',
    'max_file' => ':attribute :max KB-аас хэтрэхгүй байх ёстой.',

    // Password
    'password' => [
        'lowercase' => 'Нууц үг дор хаяж нэг жижиг үсэг агуулах ёстой.',
        'uppercase' => 'Нууц үг дор хаяж нэг том үсэг агуулах ёстой.',
        'number' => 'Нууц үг дор хаяж нэг тоо агуулах ёстой.',
        'special' => 'Нууц үг дор хаяж нэг тусгай тэмдэгт агуулах ёстой.',
    ],

    // Date
    'date_format' => ':attribute :format форматтай таарахгүй байна.',
    'after' => ':attribute :date-ийн дараах огноо байх ёстой.',
    'before' => ':attribute :date-ийн өмнөх огноо байх ёстой.',
    'after_or_equal' => ':attribute :date эсвэл түүнээс хойш байх ёстой.',
    'before_or_equal' => ':attribute :date эсвэл түүнээс өмнө байх ёстой.',

    // Attribute names
    'attributes' => [
        'name' => 'нэр',
        'email' => 'имэйл',
        'password' => 'нууц үг',
        'password_confirmation' => 'нууц үг баталгаажуулалт',
        'phone' => 'утас',
        'customer_name' => 'нэр',
        'customer_email' => 'имэйл',
        'customer_phone' => 'утас',
        'booking_date' => 'захиалгын огноо',
        'start_time' => 'эхлэх цаг',
        'guests' => 'зочид',
        'service_id' => 'үйлчилгээ',
        'category_id' => 'ангилал',
        'duration' => 'хугацаа',
        'price' => 'үнэ',
        'description' => 'тайлбар',
        'notes' => 'тэмдэглэл',
        'reason' => 'шалтгаан',
    ],
];
