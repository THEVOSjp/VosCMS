<?php
/**
 * Messages - Simplified Chinese (简体中文)
 */

return [
    // Common
    'app_name' => 'RezlyX',
    'welcome' => '欢迎',
    'home' => '首页',
    'back' => '返回',
    'next' => '下一步',
    'cancel' => '取消',
    'confirm' => '确认',
    'save' => '保存',
    'delete' => '删除',
    'edit' => '编辑',
    'search' => '搜索',
    'loading' => '加载中...',
    'no_data' => '暂无数据。',
    'error' => '发生错误。',
    'success' => '处理成功。',

    // Auth
    'auth' => [
        'login' => '登录',
        'logout' => '注销',
        'register' => '注册',
        'email' => '邮箱',
        'password' => '密码',
        'password_confirm' => '确认密码',
        'remember_me' => '记住我',
        'forgot_password' => '忘记密码？',
        'reset_password' => '重置密码',
        'invalid_credentials' => '邮箱或密码错误。',
        'account_inactive' => '此账户已被停用。',
    ],

    // Reservation
    'reservation' => [
        'title' => '预约',
        'new' => '新建预约',
        'my_reservations' => '我的预约',
        'select_service' => '选择服务',
        'select_date' => '选择日期',
        'select_time' => '选择时间',
        'customer_info' => '您的信息',
        'payment' => '支付',
        'confirmation' => '确认',
        'status' => [
            'pending' => '待处理',
            'confirmed' => '已确认',
            'completed' => '已完成',
            'cancelled' => '已取消',
            'no_show' => '未到',
        ],
    ],

    // Services
    'service' => [
        'title' => '服务',
        'category' => '分类',
        'price' => '价格',
        'duration' => '时长',
        'description' => '描述',
        'options' => '选项',
    ],

    // Member
    'member' => [
        'profile' => '我的资料',
        'points' => '积分',
        'grade' => '会员等级',
        'reservations' => '预约记录',
        'payments' => '支付记录',
        'settings' => '设置',
    ],

    // Payment
    'payment' => [
        'title' => '支付',
        'amount' => '金额',
        'method' => '支付方式',
        'card' => '信用卡',
        'bank_transfer' => '银行转账',
        'virtual_account' => '虚拟账户',
        'points' => '积分',
        'use_points' => '使用积分',
        'available_points' => '可用积分',
        'complete' => '支付完成',
        'failed' => '支付失败',
    ],

    // Time
    'time' => [
        'today' => '今天',
        'tomorrow' => '明天',
        'minutes' => '分钟',
        'hours' => '小时',
        'days' => '天',
    ],

    // Validation
    'validation' => [
        'required' => ':attribute 字段为必填项。',
        'email' => '请输入有效的邮箱地址。',
        'min' => ':attribute 至少需要 :min 个字符。',
        'max' => ':attribute 不能超过 :max 个字符。',
        'confirmed' => ':attribute 确认不匹配。',
    ],
];
