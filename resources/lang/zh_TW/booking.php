<?php

/**
 * Booking translations - Traditional Chinese (繁體中文)
 */

return [
    // Page titles
    'title' => '立即預約',
    'service_list' => '服務列表',
    'select_service' => '選擇服務',
    'select_date' => '選擇日期',
    'select_time' => '選擇時間',
    'enter_info' => '填寫資訊',
    'confirm_booking' => '確認預約',
    'confirm_info' => '請確認您的預約資訊',
    'complete_booking' => '完成預約',
    'select_service_datetime' => '請選擇服務和首選日期/時間',
    'select_datetime' => '請選擇日期和時間',
    'no_services' => '目前沒有可用服務。',
    'contact_admin' => '請聯繫管理員。',
    'notes' => '特殊要求',
    'notes_placeholder' => '輸入任何特殊要求',
    'customer' => '客戶',
    'phone' => '電話',
    'date' => '日期',
    'time' => '時間',
    'total_price' => '總金額',
    'cancel_policy' => '預約時間前24小時內可取消。逾期取消可能收取取消費。',
    'success' => '您的預約已完成！確認訊息將發送給您。',

    // Steps
    'step' => [
        'service' => '選擇服務',
        'datetime' => '日期/時間',
        'info' => '資訊',
        'confirm' => '確認',
    ],

    // Service
    'service' => [
        'title' => '服務',
        'name' => '服務名稱',
        'description' => '描述',
        'duration' => '時長',
        'price' => '價格',
        'category' => '分類',
        'select' => '選擇',
        'view_detail' => '檢視詳情',
        'no_services' => '沒有可用服務。',
    ],

    // Date/Time
    'date' => [
        'title' => '預約日期',
        'select_date' => '請選擇日期',
        'available' => '可用',
        'unavailable' => '不可用',
        'fully_booked' => '已滿',
        'past_date' => '過去日期',
    ],

    'time' => [
        'title' => '預約時間',
        'select_time' => '請選擇時間',
        'available_slots' => '可用時段',
        'no_slots' => '沒有可用時段。',
        'remaining' => '剩餘 :count 個名額',
    ],

    // Booking form
    'form' => [
        'customer_name' => '姓名',
        'customer_email' => '電子郵件',
        'customer_phone' => '電話',
        'guests' => '人數',
        'notes' => '特殊要求',
        'notes_placeholder' => '輸入任何特殊要求',
    ],

    // Confirmation
    'confirm' => [
        'title' => '確認預約',
        'summary' => '預約摘要',
        'service_info' => '服務資訊',
        'booking_info' => '預約資訊',
        'customer_info' => '客戶資訊',
        'total_price' => '總計',
        'agree_terms' => '我同意預約條款',
        'submit' => '完成預約',
    ],

    // Complete
    'complete' => [
        'title' => '預約完成',
        'success' => '您的預約已完成！',
        'booking_code' => '預約編號',
        'check_email' => '確認郵件已發送到您的郵箱。',
        'view_detail' => '檢視預約詳情',
        'book_another' => '再次預約',
    ],

    // Lookup
    'lookup' => [
        'title' => '查詢預約',
        'description' => '輸入您的預約資訊以查找預約。',
        'booking_code' => '預約編號',
        'booking_code_placeholder' => 'RZ250301XXXXXX',
        'email' => '電子郵件',
        'email_placeholder' => '預約時使用的郵箱',
        'phone' => '電話號碼',
        'phone_placeholder' => '預約時使用的電話',
        'search' => '搜尋',
        'search_method' => '搜尋方式',
        'by_code' => '按預約編號搜尋',
        'by_email' => '按電子郵件搜尋',
        'by_phone' => '按電話搜尋',
        'not_found' => '未找到預約。請檢查您的資訊。',
        'input_required' => '請輸入預約編號和電子郵件或電話號碼。',
        'result_title' => '搜尋結果',
        'multiple_results' => '找到 :count 個預約。',
        'hint' => '為獲得準確結果，請輸入預約編號以及電子郵件或電話號碼。',
        'help_text' => '找不到您的預約？',
        'contact_support' => '聯繫客服',
    ],

    // Detail
    'detail' => [
        'title' => '預約詳情',
        'status' => '狀態',
        'booking_date' => '日期和時間',
        'service' => '服務',
        'guests' => '人數',
        'total_price' => '總價',
        'payment_status' => '付款狀態',
        'notes' => '特殊要求',
        'created_at' => '預約時間',
    ],

    // Cancel
    'cancel' => [
        'title' => '取消預約',
        'confirm' => '確定要取消此預約嗎？',
        'reason' => '取消原因',
        'reason_placeholder' => '請輸入取消原因',
        'submit' => '取消預約',
        'success' => '您的預約已取消。',
        'cannot_cancel' => '此預約無法取消。',
    ],

    // Status messages
    'status' => [
        'pending' => '已收到您的預約。請等待確認。',
        'confirmed' => '您的預約已確認。',
        'cancelled' => '您的預約已取消。',
        'completed' => '服務已完成。',
        'no_show' => '標記為未到。',
    ],

    // Error messages
    'error' => [
        'service_not_found' => '未找到服務。',
        'slot_unavailable' => '所選時段不可用。',
        'past_date' => '無法預約過去的日期。',
        'max_capacity' => '超出最大容量。',
        'booking_failed' => '處理預約時發生錯誤。',
        'required_fields' => '請輸入姓名和聯繫方式。',
    ],
];
