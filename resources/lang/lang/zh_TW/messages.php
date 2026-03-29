<?php
/**
 * Messages - Traditional Chinese (繁體中文)
 */

return [
    // Common
    'app_name' => 'RezlyX',
    'welcome' => '歡迎',
    'home' => '首頁',
    'back' => '返回',
    'next' => '下一步',
    'cancel' => '取消',
    'confirm' => '確認',
    'save' => '儲存',
    'delete' => '刪除',
    'edit' => '編輯',
    'search' => '搜尋',
    'loading' => '載入中...',
    'no_data' => '暫無資料。',
    'error' => '發生錯誤。',
    'success' => '處理成功。',

    // Auth
    'auth' => [
        'login' => '登入',
        'logout' => '登出',
        'register' => '註冊',
        'email' => '電子郵件',
        'password' => '密碼',
        'password_confirm' => '確認密碼',
        'remember_me' => '記住我',
        'forgot_password' => '忘記密碼？',
        'reset_password' => '重設密碼',
        'invalid_credentials' => '電子郵件或密碼錯誤。',
        'account_inactive' => '此帳戶已被停用。',
    ],

    // Reservation
    'reservation' => [
        'title' => '預約',
        'new' => '新增預約',
        'my_reservations' => '我的預約',
        'select_service' => '選擇服務',
        'select_date' => '選擇日期',
        'select_time' => '選擇時間',
        'customer_info' => '您的資訊',
        'payment' => '付款',
        'confirmation' => '確認',
        'status' => [
            'pending' => '待處理',
            'confirmed' => '已確認',
            'completed' => '已完成',
            'cancelled' => '已取消',
            'no_show' => '未到',
        ],
    ],

    // Services
    'service' => [
        'title' => '服務',
        'category' => '分類',
        'price' => '價格',
        'duration' => '時長',
        'description' => '描述',
        'options' => '選項',
    ],

    // Member
    'member' => [
        'profile' => '我的資料',
        'points' => '點數',
        'grade' => '會員等級',
        'reservations' => '預約記錄',
        'payments' => '付款記錄',
        'settings' => '設定',
    ],

    // Payment
    'payment' => [
        'title' => '付款',
        'amount' => '金額',
        'method' => '付款方式',
        'card' => '信用卡',
        'bank_transfer' => '銀行轉帳',
        'virtual_account' => '虛擬帳戶',
        'points' => '點數',
        'use_points' => '使用點數',
        'available_points' => '可用點數',
        'complete' => '付款完成',
        'failed' => '付款失敗',
    ],

    // Time
    'time' => [
        'today' => '今天',
        'tomorrow' => '明天',
        'minutes' => '分鐘',
        'hours' => '小時',
        'days' => '天',
    ],

    // Validation
    'validation' => [
        'required' => ':attribute 欄位為必填項。',
        'email' => '請輸入有效的電子郵件地址。',
        'min' => ':attribute 至少需要 :min 個字元。',
        'max' => ':attribute 不能超過 :max 個字元。',
        'confirmed' => ':attribute 確認不符合。',
    ],
];
