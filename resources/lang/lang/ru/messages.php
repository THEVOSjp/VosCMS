<?php
/**
 * Russian Language File
 */

return [
    // Common
    'app_name' => 'RezlyX',
    'welcome' => 'Добро пожаловать',
    'home' => 'Главная',
    'back' => 'Назад',
    'next' => 'Далее',
    'cancel' => 'Отмена',
    'confirm' => 'Подтвердить',
    'save' => 'Сохранить',
    'delete' => 'Удалить',
    'edit' => 'Редактировать',
    'search' => 'Поиск',
    'loading' => 'Загрузка...',
    'no_data' => 'Данные отсутствуют.',
    'error' => 'Произошла ошибка.',
    'success' => 'Успешно обработано.',

    // Auth
    'auth' => [
        'login' => 'Вход',
        'logout' => 'Выход',
        'register' => 'Регистрация',
        'email' => 'Эл. почта',
        'password' => 'Пароль',
        'password_confirm' => 'Подтверждение пароля',
        'remember_me' => 'Запомнить меня',
        'forgot_password' => 'Забыли пароль?',
        'reset_password' => 'Сброс пароля',
        'invalid_credentials' => 'Неверный email или пароль.',
        'account_inactive' => 'Этот аккаунт неактивен.',
    ],

    // Reservation
    'reservation' => [
        'title' => 'Бронирование',
        'new' => 'Новое бронирование',
        'my_reservations' => 'Мои бронирования',
        'select_service' => 'Выберите услугу',
        'select_date' => 'Выберите дату',
        'select_time' => 'Выберите время',
        'customer_info' => 'Ваши данные',
        'payment' => 'Оплата',
        'confirmation' => 'Подтверждение',
        'status' => [
            'pending' => 'Ожидает',
            'confirmed' => 'Подтверждено',
            'completed' => 'Завершено',
            'cancelled' => 'Отменено',
            'no_show' => 'Неявка',
        ],
    ],

    // Services
    'service' => [
        'title' => 'Услуги',
        'category' => 'Категория',
        'price' => 'Цена',
        'duration' => 'Продолжительность',
        'description' => 'Описание',
        'options' => 'Опции',
    ],

    // Member
    'member' => [
        'profile' => 'Мой профиль',
        'points' => 'Баллы',
        'grade' => 'Уровень членства',
        'reservations' => 'История бронирований',
        'payments' => 'История платежей',
        'settings' => 'Настройки',
    ],

    // Payment
    'payment' => [
        'title' => 'Оплата',
        'amount' => 'Сумма',
        'method' => 'Способ оплаты',
        'card' => 'Банковская карта',
        'bank_transfer' => 'Банковский перевод',
        'virtual_account' => 'Виртуальный счёт',
        'points' => 'Баллы',
        'use_points' => 'Использовать баллы',
        'available_points' => 'Доступные баллы',
        'complete' => 'Оплата завершена',
        'failed' => 'Ошибка оплаты',
    ],

    // Time
    'time' => [
        'today' => 'Сегодня',
        'tomorrow' => 'Завтра',
        'minutes' => 'мин',
        'hours' => 'часов',
        'days' => 'дней',
    ],

    // Validation
    'validation' => [
        'required' => 'Поле :attribute обязательно для заполнения.',
        'email' => 'Введите корректный адрес эл. почты.',
        'min' => 'Поле :attribute должно содержать не менее :min символов.',
        'max' => 'Поле :attribute не должно превышать :max символов.',
        'confirmed' => 'Подтверждение поля :attribute не совпадает.',
    ],
];
