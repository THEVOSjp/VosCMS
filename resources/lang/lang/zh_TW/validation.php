<?php

/**
 * Validation messages - Traditional Chinese (繁體中文)
 */

return [
    // Basic messages
    'accepted' => '您必須同意:attribute。',
    'required' => ':attribute 欄位為必填項。',
    'email' => '請輸入有效的電子郵件地址。',
    'min' => ':attribute 至少需要 :min 個字元。',
    'max' => ':attribute 不能超過 :max 個字元。',
    'numeric' => ':attribute 必須是數字。',
    'integer' => ':attribute 必須是整數。',
    'string' => ':attribute 必須是字串。',
    'boolean' => ':attribute 必須是 true 或 false。',
    'array' => ':attribute 必須是陣列。',
    'date' => ':attribute 不是有效的日期。',
    'same' => ':attribute 確認不符合。',
    'confirmed' => ':attribute 確認不符合。',
    'unique' => ':attribute 已被使用。',
    'exists' => '所選 :attribute 無效。',
    'in' => '所選 :attribute 無效。',
    'not_in' => '所選 :attribute 無效。',
    'regex' => ':attribute 格式無效。',
    'url' => ':attribute 格式無效。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_num' => ':attribute 只能包含字母和數字。',
    'alpha_dash' => ':attribute 只能包含字母、數字、短橫線和底線。',

    // Size
    'size' => [
        'numeric' => ':attribute 必須是 :size。',
        'string' => ':attribute 必須是 :size 個字元。',
        'array' => ':attribute 必須包含 :size 項。',
    ],

    'between' => [
        'numeric' => ':attribute 必須在 :min 和 :max 之間。',
        'string' => ':attribute 必須在 :min 和 :max 個字元之間。',
        'array' => ':attribute 必須包含 :min 到 :max 項。',
    ],

    // File
    'file' => ':attribute 必須是檔案。',
    'image' => ':attribute 必須是圖片。',
    'mimes' => ':attribute 必須是以下類型的檔案：:values。',
    'max_file' => ':attribute 不能超過 :max KB。',

    // Password
    'password' => [
        'lowercase' => '密碼必須包含至少一個小寫字母。',
        'uppercase' => '密碼必須包含至少一個大寫字母。',
        'number' => '密碼必須包含至少一個數字。',
        'special' => '密碼必須包含至少一個特殊字元。',
    ],

    // Date
    'date_format' => ':attribute 與格式 :format 不符合。',
    'after' => ':attribute 必須是 :date 之後的日期。',
    'before' => ':attribute 必須是 :date 之前的日期。',
    'after_or_equal' => ':attribute 必須是 :date 或之後的日期。',
    'before_or_equal' => ':attribute 必須是 :date 或之前的日期。',

    // Attribute names
    'attributes' => [
        'name' => '姓名',
        'email' => '電子郵件',
        'password' => '密碼',
        'password_confirmation' => '密碼確認',
        'phone' => '電話',
        'customer_name' => '姓名',
        'customer_email' => '電子郵件',
        'customer_phone' => '電話',
        'booking_date' => '預約日期',
        'start_time' => '開始時間',
        'guests' => '人數',
        'service_id' => '服務',
        'category_id' => '分類',
        'duration' => '時長',
        'price' => '價格',
        'description' => '描述',
        'notes' => '備註',
        'reason' => '原因',
    ],
];
