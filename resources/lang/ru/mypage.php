<?php

/**
 * My Page translations - Russian
 */

return [
    // Main
    'title' => 'Мой кабинет',
    'welcome' => 'Добро пожаловать, :name!',

    // Navigation
    'nav' => [
        'dashboard' => 'Панель управления',
        'reservations' => 'Мои бронирования',
        'profile' => 'Профиль',
        'password' => 'Изменить пароль',
        'logout' => 'Выход',
    ],

    // Dashboard
    'dashboard' => [
        'upcoming' => 'Предстоящие бронирования',
        'recent' => 'Недавние бронирования',
        'no_upcoming' => 'Нет предстоящих бронирований.',
        'no_recent' => 'Нет недавних бронирований.',
        'view_all' => 'Посмотреть все',
    ],

    // Reservations
    'reservations' => [
        'title' => 'История бронирований',
        'filter' => [
            'all' => 'Все',
            'pending' => 'Ожидают',
            'confirmed' => 'Подтверждённые',
            'completed' => 'Завершённые',
            'cancelled' => 'Отменённые',
        ],
        'no_reservations' => 'Бронирования не найдены.',
        'booking_code' => 'Код бронирования',
        'service' => 'Услуга',
        'date' => 'Дата',
        'status' => 'Статус',
        'actions' => 'Действия',
        'view' => 'Просмотр',
        'cancel' => 'Отменить',
    ],

    // Profile
    'profile' => [
        'title' => 'Настройки профиля',
        'info' => 'Основная информация',
        'name' => 'Имя',
        'email' => 'Эл. почта',
        'phone' => 'Телефон',
        'save' => 'Сохранить',
        'success' => 'Профиль успешно обновлён.',
    ],

    // Password
    'password' => [
        'title' => 'Изменение пароля',
        'current' => 'Текущий пароль',
        'new' => 'Новый пароль',
        'confirm' => 'Подтверждение нового пароля',
        'change' => 'Изменить пароль',
        'success' => 'Пароль успешно изменён.',
        'mismatch' => 'Текущий пароль неверен.',
    ],

    // Stats
    'stats' => [
        'total_bookings' => 'Всего бронирований',
        'completed' => 'Завершено',
        'cancelled' => 'Отменено',
        'upcoming' => 'Предстоящие',
    ],
];
