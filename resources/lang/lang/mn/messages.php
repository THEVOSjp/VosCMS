<?php
/**
 * Mongolian Language File (Монгол хэл)
 */

return [
    // Common
    'app_name' => 'RezlyX',
    'welcome' => 'Тавтай морил',
    'home' => 'Нүүр',
    'back' => 'Буцах',
    'next' => 'Дараах',
    'cancel' => 'Цуцлах',
    'confirm' => 'Баталгаажуулах',
    'save' => 'Хадгалах',
    'delete' => 'Устгах',
    'edit' => 'Засах',
    'search' => 'Хайх',
    'loading' => 'Ачааллаж байна...',
    'no_data' => 'Мэдээлэл олдсонгүй.',
    'error' => 'Алдаа гарлаа.',
    'success' => 'Амжилттай боловсруулагдлаа.',

    // Auth
    'auth' => [
        'login' => 'Нэвтрэх',
        'logout' => 'Гарах',
        'register' => 'Бүртгүүлэх',
        'email' => 'И-мэйл',
        'password' => 'Нууц үг',
        'password_confirm' => 'Нууц үг баталгаажуулах',
        'remember_me' => 'Намайг сана',
        'forgot_password' => 'Нууц үг мартсан уу?',
        'reset_password' => 'Нууц үг шинэчлэх',
        'invalid_credentials' => 'И-мэйл эсвэл нууц үг буруу байна.',
        'account_inactive' => 'Энэ бүртгэл идэвхгүй байна.',
    ],

    // Reservation
    'reservation' => [
        'title' => 'Захиалга',
        'new' => 'Шинэ захиалга',
        'my_reservations' => 'Миний захиалгууд',
        'select_service' => 'Үйлчилгээ сонгох',
        'select_date' => 'Огноо сонгох',
        'select_time' => 'Цаг сонгох',
        'customer_info' => 'Таны мэдээлэл',
        'payment' => 'Төлбөр',
        'confirmation' => 'Баталгаажуулалт',
        'status' => [
            'pending' => 'Хүлээгдэж буй',
            'confirmed' => 'Баталгаажсан',
            'completed' => 'Дууссан',
            'cancelled' => 'Цуцлагдсан',
            'no_show' => 'Ирээгүй',
        ],
    ],

    // Services
    'service' => [
        'title' => 'Үйлчилгээнүүд',
        'category' => 'Ангилал',
        'price' => 'Үнэ',
        'duration' => 'Үргэлжлэх хугацаа',
        'description' => 'Тайлбар',
        'options' => 'Сонголтууд',
    ],

    // Member
    'member' => [
        'profile' => 'Миний профайл',
        'points' => 'Оноо',
        'grade' => 'Гишүүнчлэлийн зэрэг',
        'reservations' => 'Захиалгын түүх',
        'payments' => 'Төлбөрийн түүх',
        'settings' => 'Тохиргоо',
    ],

    // Payment
    'payment' => [
        'title' => 'Төлбөр',
        'amount' => 'Дүн',
        'method' => 'Төлбөрийн арга',
        'card' => 'Кредит карт',
        'bank_transfer' => 'Банкны шилжүүлэг',
        'virtual_account' => 'Виртуал данс',
        'points' => 'Оноо',
        'use_points' => 'Оноо ашиглах',
        'available_points' => 'Боломжит оноо',
        'complete' => 'Төлбөр амжилттай',
        'failed' => 'Төлбөр амжилтгүй',
    ],

    // Time
    'time' => [
        'today' => 'Өнөөдөр',
        'tomorrow' => 'Маргааш',
        'minutes' => 'мин',
        'hours' => 'цаг',
        'days' => 'өдөр',
    ],

    // Validation
    'validation' => [
        'required' => ':attribute талбар заавал шаардлагатай.',
        'email' => 'Зөв и-мэйл хаяг оруулна уу.',
        'min' => ':attribute нь дор хаяж :min тэмдэгт байх ёстой.',
        'max' => ':attribute нь :max тэмдэгтээс хэтрэх ёсгүй.',
        'confirmed' => ':attribute баталгаажуулалт таарахгүй байна.',
    ],
];
