<?php

/**
 * Authentication translations - Simplified Chinese (简体中文)
 */

return [
    // Login
    'login' => [
        'title' => '登录',
        'description' => '登录您的账户以管理预约',
        'email' => '邮箱',
        'email_placeholder' => 'example@email.com',
        'password' => '密码',
        'password_placeholder' => '••••••••',
        'remember' => '记住我',
        'forgot' => '忘记密码？',
        'submit' => '登录',
        'no_account' => '还没有账户？',
        'register_link' => '注册',
        'back_home' => '← 返回首页',
        'success' => '登录成功。',
        'failed' => '邮箱或密码错误。',
        'required' => '请输入邮箱和密码。',
        'error' => '登录时发生错误。',
        'social_only' => '此账户是通过社交登录注册的。请使用社交登录。',
    ],

    // Register
    'register' => [
        'title' => '注册',
        'description' => '使用RezlyX开始预约',
        'name' => '姓名',
        'name_placeholder' => '张三',
        'email' => '邮箱',
        'email_placeholder' => 'example@email.com',
        'phone' => '电话',
        'phone_placeholder' => '138-0000-0000',
        'phone_hint' => '选择国家代码并输入电话号码',
        'password' => '密码',
        'password_placeholder' => '至少12个字符',
        'password_hint' => '至少12个字符，包含大小写字母、数字和特殊字符',
        'password_confirm' => '确认密码',
        'password_confirm_placeholder' => '再次输入密码',
        'agree_terms' => ' 我同意',
        'agree_privacy' => ' 我同意',
        'submit' => '注册',
        'has_account' => '已有账户？',
        'login_link' => '登录',
        'success' => '注册成功。',
        'success_login' => '前往登录',
        'email_exists' => '此邮箱已注册。',
        'error' => '注册时发生错误。',
    ],

    // Forgot password
    'forgot' => [
        'title' => '忘记密码',
        'description' => '输入您的邮箱地址，我们将发送密码重置链接。',
        'email' => '邮箱',
        'submit' => '发送重置链接',
        'back_login' => '返回登录',
        'success' => '密码重置链接已发送到您的邮箱。',
        'not_found' => '未找到邮箱地址。',
    ],

    // Reset password
    'reset' => [
        'title' => '重置密码',
        'email' => '邮箱',
        'password' => '新密码',
        'password_confirm' => '确认新密码',
        'submit' => '重置密码',
        'success' => '您的密码已重置。',
        'invalid_token' => '无效的令牌。',
        'expired_token' => '令牌已过期。',
    ],

    // Logout
    'logout' => [
        'success' => '注销成功。',
    ],

    // Email verification
    'verify' => [
        'title' => '验证邮箱',
        'description' => '我们已向您的邮箱发送了验证邮件。请查收。',
        'resend' => '重新发送验证邮件',
        'success' => '邮箱验证成功。',
        'already_verified' => '邮箱已验证。',
    ],

    // Social login
    'social' => [
        'or' => '或',
        'google' => '使用Google登录',
        'kakao' => '使用Kakao登录',
        'naver' => '使用Naver登录',
        'line' => '使用LINE登录',
    ],

    // Social login buttons
    'login_with_line' => '使用LINE登录',
    'login_with_google' => '使用Google登录',
    'login_with_kakao' => '使用Kakao登录',
    'login_with_naver' => '使用Naver登录',
    'login_with_apple' => '使用Apple登录',
    'login_with_facebook' => '使用Facebook登录',
    'or_continue_with' => '或',

    // Terms Agreement
    'terms' => [
        'title' => '条款协议',
        'subtitle' => '请同意条款以使用服务',
        'agree_all' => '我同意所有条款',
        'required' => '必需',
        'optional' => '可选',
        'required_mark' => '必需',
        'required_note' => '* 表示必需项目',
        'required_alert' => '请同意所有必需条款。',
        'notice' => '如果您不同意条款，可能无法使用服务。',
        'view_content' => '查看内容',
        'hide_content' => '隐藏内容',
        'translation_pending' => '翻译进行中',
    ],

    // My Page
    'mypage' => [
        'title' => '我的页面',
        'welcome' => '您好，:name！',
        'member_since' => '会员自 :date',
        'menu' => [
            'dashboard' => '仪表板',
            'reservations' => '预约',
            'profile' => '个人资料',
            'settings' => '设置',
            'password' => '修改密码',
            'withdraw' => '注销账户',
            'logout' => '注销',
        ],
        'stats' => [
            'total_reservations' => '总预约',
            'upcoming' => '即将到来',
            'completed' => '已完成',
            'cancelled' => '已取消',
        ],
        'recent_reservations' => '最近预约',
        'no_reservations' => '未找到预约。',
        'view_all' => '查看全部',
        'quick_actions' => '快捷操作',
        'make_reservation' => '进行预约',
    ],

    // Profile
    'profile' => [
        'title' => '个人资料',
        'description' => '我的个人资料信息。',
        'edit_title' => '编辑个人资料',
        'edit_description' => '编辑个人信息。',
        'edit_button' => '编辑',
        'name' => '姓名',
        'email' => '邮箱',
        'email_hint' => '邮箱无法更改。',
        'phone' => '电话',
        'not_set' => '未设置',
        'submit' => '保存',
        'success' => '资料更新成功。',
        'error' => '更新资料时发生错误。',
    ],

    // Settings
    'settings' => [
        'title' => '隐私设置',
        'description' => '选择向其他用户显示的信息。',
        'info' => '禁用的项目不会向其他用户显示。名称始终显示。',
        'success' => '设置已保存。',
        'error' => '保存设置时发生错误。',
        'no_fields' => '没有可配置的字段。',
        'fields' => [
            'email' => '电子邮箱', 'email_desc' => '向其他用户显示您的电子邮箱。',
            'profile_photo' => '头像', 'profile_photo_desc' => '向其他用户显示您的头像。',
            'phone' => '电话号码', 'phone_desc' => '向其他用户显示您的电话号码。',
            'birth_date' => '出生日期', 'birth_date_desc' => '向其他用户显示您的出生日期。',
            'gender' => '性别', 'gender_desc' => '向其他用户显示您的性别。',
            'company' => '公司', 'company_desc' => '向其他用户显示您的公司。',
            'blog' => '博客', 'blog_desc' => '向其他用户显示您的博客地址。',
        ],
    ],

    // Change Password
    'password_change' => [
        'title' => '修改密码',
        'description' => '为了安全，请定期修改密码。',
        'current' => '当前密码',
        'current_placeholder' => '输入当前密码',
        'new' => '新密码',
        'new_placeholder' => '输入新密码',
        'confirm' => '确认新密码',
        'confirm_placeholder' => '再次输入新密码',
        'submit' => '修改密码',
        'success' => '密码修改成功。',
        'error' => '修改密码时发生错误。',
        'wrong_password' => '当前密码不正确。',
    ],

    // 注销账户
    'withdraw' => [
        'title' => '注销账户',
        'description' => '注销后个人信息将立即匿名化处理，注销后无法恢复账户。',
        'warning_title' => '注销前请务必确认',
        'warnings' => [
            'account' => '姓名、邮箱、手机号、出生日期、头像等所有个人信息将立即匿名化，将无法识别您的身份。',
            'reservation' => '如有进行中或即将到来的预约，请在注销前取消。注销后将无法修改或取消预约。',
            'payment' => '支付及交易记录将根据相关税法（韩国国税基本法5年、日本法人税法7年）以匿名化形式保留法定期限。',
            'recovery' => '注销后的账户无法恢复。可使用相同邮箱重新注册，但之前的预约记录、积分、消息等数据均不会恢复。',
            'social' => '如通过社交账号（Google、Kakao、LINE等）注册，相应的社交服务连接也将被解除。',
            'message' => '所有已接收的消息和通知记录将被永久删除。',
        ],
        'retention_notice' => '※ 根据相关法律法规需要保留的交易记录，将以无法识别个人身份的形式在法定期限内保留后彻底删除。',
        'reason' => '注销原因',
        'reason_placeholder' => '请选择注销原因',
        'reasons' => [
            'not_using' => '不再使用该服务',
            'other_service' => '转用其他服务',
            'dissatisfied' => '对服务不满意',
            'privacy' => '隐私安全顾虑',
            'too_many_emails' => '邮件/通知太多',
            'other' => '其他',
        ],
        'reason_other' => '其他原因',
        'reason_other_placeholder' => '请输入注销原因',
        'password' => '确认密码',
        'password_placeholder' => '输入当前密码',
        'password_hint' => '请输入当前密码以验证身份。',
        'confirm_text' => '我已阅读并理解以上所有内容，同意个人信息匿名化及账户注销。',
        'submit' => '注销账户',
        'success' => '账户已注销。感谢您的使用。',
        'wrong_password' => '密码不正确。',
        'error' => '注销处理过程中发生错误。',
        'confirm_required' => '请勾选同意注销。',
    ],
];
