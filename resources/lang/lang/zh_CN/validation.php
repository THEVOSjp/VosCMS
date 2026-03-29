<?php

/**
 * Validation messages - Simplified Chinese (简体中文)
 */

return [
    // Basic messages
    'accepted' => '您必须同意:attribute。',
    'required' => ':attribute 字段为必填项。',
    'email' => '请输入有效的邮箱地址。',
    'min' => ':attribute 至少需要 :min 个字符。',
    'max' => ':attribute 不能超过 :max 个字符。',
    'numeric' => ':attribute 必须是数字。',
    'integer' => ':attribute 必须是整数。',
    'string' => ':attribute 必须是字符串。',
    'boolean' => ':attribute 必须是 true 或 false。',
    'array' => ':attribute 必须是数组。',
    'date' => ':attribute 不是有效的日期。',
    'same' => ':attribute 确认不匹配。',
    'confirmed' => ':attribute 确认不匹配。',
    'unique' => ':attribute 已被使用。',
    'exists' => '所选 :attribute 无效。',
    'in' => '所选 :attribute 无效。',
    'not_in' => '所选 :attribute 无效。',
    'regex' => ':attribute 格式无效。',
    'url' => ':attribute 格式无效。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_num' => ':attribute 只能包含字母和数字。',
    'alpha_dash' => ':attribute 只能包含字母、数字、短横线和下划线。',

    // Size
    'size' => [
        'numeric' => ':attribute 必须是 :size。',
        'string' => ':attribute 必须是 :size 个字符。',
        'array' => ':attribute 必须包含 :size 项。',
    ],

    'between' => [
        'numeric' => ':attribute 必须在 :min 和 :max 之间。',
        'string' => ':attribute 必须在 :min 和 :max 个字符之间。',
        'array' => ':attribute 必须包含 :min 到 :max 项。',
    ],

    // File
    'file' => ':attribute 必须是文件。',
    'image' => ':attribute 必须是图片。',
    'mimes' => ':attribute 必须是以下类型的文件：:values。',
    'max_file' => ':attribute 不能超过 :max KB。',

    // Password
    'password' => [
        'lowercase' => '密码必须包含至少一个小写字母。',
        'uppercase' => '密码必须包含至少一个大写字母。',
        'number' => '密码必须包含至少一个数字。',
        'special' => '密码必须包含至少一个特殊字符。',
    ],

    // Date
    'date_format' => ':attribute 与格式 :format 不匹配。',
    'after' => ':attribute 必须是 :date 之后的日期。',
    'before' => ':attribute 必须是 :date 之前的日期。',
    'after_or_equal' => ':attribute 必须是 :date 或之后的日期。',
    'before_or_equal' => ':attribute 必须是 :date 或之前的日期。',

    // Attribute names
    'attributes' => [
        'name' => '姓名',
        'email' => '邮箱',
        'password' => '密码',
        'password_confirmation' => '密码确认',
        'phone' => '电话',
        'customer_name' => '姓名',
        'customer_email' => '邮箱',
        'customer_phone' => '电话',
        'booking_date' => '预约日期',
        'start_time' => '开始时间',
        'guests' => '人数',
        'service_id' => '服务',
        'category_id' => '分类',
        'duration' => '时长',
        'price' => '价格',
        'description' => '描述',
        'notes' => '备注',
        'reason' => '原因',
    ],
];
