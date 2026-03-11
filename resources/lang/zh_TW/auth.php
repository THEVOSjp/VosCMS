<?php

/**
 * Authentication translations - Traditional Chinese (繁體中文)
 */

return [
    // Login
    'login' => [
        'title' => '登入',
        'description' => '登入您的帳戶以管理預約',
        'email' => '電子郵件',
        'email_placeholder' => 'example@email.com',
        'password' => '密碼',
        'password_placeholder' => '••••••••',
        'remember' => '記住我',
        'forgot' => '忘記密碼？',
        'submit' => '登入',
        'no_account' => '還沒有帳戶？',
        'register_link' => '註冊',
        'back_home' => '← 返回首頁',
        'success' => '登入成功。',
        'failed' => '電子郵件或密碼錯誤。',
        'required' => '請輸入電子郵件和密碼。',
        'error' => '登入時發生錯誤。',
        'social_only' => '此帳戶是透過社群登入註冊的。請使用社群登入。',
    ],

    // Register
    'register' => [
        'title' => '註冊',
        'description' => '使用RezlyX開始預約',
        'name' => '姓名',
        'name_placeholder' => '王小明',
        'email' => '電子郵件',
        'phone' => '電話',
        'phone_placeholder' => '0912-345-678',
        'phone_hint' => '選擇國碼並輸入電話號碼',
        'password' => '密碼',
        'password_placeholder' => '至少12個字元',
        'password_hint' => '至少12個字元，包含大小寫字母、數字和特殊字元',
        'password_confirm' => '確認密碼',
        'password_confirm_placeholder' => '再次輸入密碼',
        'agree_terms' => ' 我同意',
        'agree_privacy' => ' 我同意',
        'submit' => '註冊',
        'has_account' => '已有帳戶？',
        'login_link' => '登入',
        'success' => '註冊成功。',
        'success_login' => '前往登入',
        'email_exists' => '此電子郵件已註冊。',
        'error' => '註冊時發生錯誤。',
    ],

    // Forgot password
    'forgot' => [
        'title' => '忘記密碼',
        'description' => '輸入您的電子郵件地址，我們將發送密碼重設連結。',
        'email' => '電子郵件',
        'submit' => '發送重設連結',
        'back_login' => '返回登入',
        'success' => '密碼重設連結已發送到您的郵箱。',
        'not_found' => '未找到電子郵件地址。',
    ],

    // Reset password
    'reset' => [
        'title' => '重設密碼',
        'email' => '電子郵件',
        'password' => '新密碼',
        'password_confirm' => '確認新密碼',
        'submit' => '重設密碼',
        'success' => '您的密碼已重設。',
        'invalid_token' => '無效的權杖。',
        'expired_token' => '權杖已過期。',
    ],

    // Logout
    'logout' => [
        'success' => '登出成功。',
    ],

    // Email verification
    'verify' => [
        'title' => '驗證電子郵件',
        'description' => '我們已向您的郵箱發送了驗證郵件。請查收。',
        'resend' => '重新發送驗證郵件',
        'success' => '電子郵件驗證成功。',
        'already_verified' => '電子郵件已驗證。',
    ],

    // Social login
    'social' => [
        'or' => '或',
        'google' => '使用Google登入',
        'kakao' => '使用Kakao登入',
        'naver' => '使用Naver登入',
        'line' => '使用LINE登入',
    ],

    // Social login buttons
    'login_with_line' => '使用LINE登入',
    'login_with_google' => '使用Google登入',
    'login_with_kakao' => '使用Kakao登入',
    'login_with_naver' => '使用Naver登入',
    'login_with_apple' => '使用Apple登入',
    'login_with_facebook' => '使用Facebook登入',
    'or_continue_with' => '或',

    // Terms Agreement
    'terms' => [
        'title' => '條款協議',
        'subtitle' => '請同意條款以使用服務',
        'agree_all' => '我同意所有條款',
        'required' => '必要',
        'optional' => '選擇性',
        'required_mark' => '必要',
        'required_note' => '* 表示必要項目',
        'required_alert' => '請同意所有必要條款。',
        'notice' => '如果您不同意條款，可能無法使用服務。',
        'view_content' => '檢視內容',
        'hide_content' => '隱藏內容',
        'translation_pending' => '翻譯進行中',
    ],

    // My Page
    'mypage' => [
        'title' => '我的頁面',
        'welcome' => '您好，:name！',
        'member_since' => '會員自 :date',
        'menu' => [
            'dashboard' => '儀表板',
            'reservations' => '預約',
            'profile' => '個人資料',
            'settings' => '設定',
            'password' => '變更密碼',
            'withdraw' => '刪除帳戶',
            'logout' => '登出',
        ],
        'stats' => [
            'total_reservations' => '總預約',
            'upcoming' => '即將到來',
            'completed' => '已完成',
            'cancelled' => '已取消',
        ],
        'recent_reservations' => '最近預約',
        'no_reservations' => '未找到預約。',
        'view_all' => '檢視全部',
        'quick_actions' => '快捷操作',
        'make_reservation' => '進行預約',
    ],

    // Profile
    'profile' => [
        'title' => '個人資料',
        'description' => '我的個人資料資訊。',
        'edit_title' => '編輯個人資料',
        'edit_description' => '編輯個人資訊。',
        'edit_button' => '編輯',
        'name' => '姓名',
        'email' => '電子郵件',
        'email_hint' => '電子郵件無法變更。',
        'phone' => '電話',
        'not_set' => '未設定',
        'submit' => '儲存',
        'success' => '資料更新成功。',
        'error' => '更新資料時發生錯誤。',
    ],

    // Settings
    'settings' => [
        'title' => '隱私設定',
        'description' => '選擇向其他用戶顯示的資訊。',
        'info' => '停用的項目不會向其他用戶顯示。名稱始終顯示。',
        'success' => '設定已儲存。',
        'error' => '儲存設定時發生錯誤。',
        'no_fields' => '沒有可設定的欄位。',
        'fields' => [
            'email' => '電子郵件', 'email_desc' => '向其他使用者顯示您的電子郵件。',
            'profile_photo' => '大頭照', 'profile_photo_desc' => '向其他用戶顯示您的大頭照。',
            'phone' => '電話號碼', 'phone_desc' => '向其他用戶顯示您的電話號碼。',
            'birth_date' => '出生日期', 'birth_date_desc' => '向其他用戶顯示您的出生日期。',
            'gender' => '性別', 'gender_desc' => '向其他用戶顯示您的性別。',
            'company' => '公司', 'company_desc' => '向其他用戶顯示您的公司。',
            'blog' => '部落格', 'blog_desc' => '向其他用戶顯示您的部落格網址。',
        ],
    ],

    // Change Password
    'password_change' => [
        'title' => '變更密碼',
        'description' => '為了安全，請定期變更密碼。',
        'current' => '目前密碼',
        'current_placeholder' => '輸入目前密碼',
        'new' => '新密碼',
        'new_placeholder' => '輸入新密碼',
        'confirm' => '確認新密碼',
        'confirm_placeholder' => '再次輸入新密碼',
        'submit' => '變更密碼',
        'success' => '密碼變更成功。',
        'error' => '變更密碼時發生錯誤。',
        'wrong_password' => '目前密碼不正確。',
    ],

    // 刪除帳戶
    'withdraw' => [
        'title' => '刪除帳戶',
        'description' => '刪除帳戶後個人資訊將立即匿名化處理，帳戶刪除後無法復原。',
        'warning_title' => '刪除前請務必確認',
        'warnings' => [
            'account' => '姓名、電子郵件、電話號碼、出生日期、大頭貼等所有個人資訊將立即匿名化，將無法識別您的身分。',
            'reservation' => '如有進行中或即將到來的預約，請在刪除帳戶前取消。刪除後將無法修改或取消預約。',
            'payment' => '付款及交易紀錄將根據相關稅法（韓國國稅基本法5年、日本法人稅法7年）以匿名化形式保留法定期限。',
            'recovery' => '刪除後的帳戶無法復原。可使用相同電子郵件重新註冊，但之前的預約紀錄、點數、訊息等資料均不會恢復。',
            'social' => '如透過社群登入（Google、Kakao、LINE等）註冊，相應的社群服務連結也將被解除。',
            'message' => '所有已接收的訊息和通知紀錄將被永久刪除。',
        ],
        'retention_notice' => '※ 根據相關法律法規需要保留的交易紀錄，將以無法識別個人身分的形式在法定期限內保留後徹底刪除。',
        'reason' => '刪除原因',
        'reason_placeholder' => '請選擇刪除原因',
        'reasons' => [
            'not_using' => '不再使用此服務',
            'other_service' => '轉換至其他服務',
            'dissatisfied' => '對服務不滿意',
            'privacy' => '隱私安全疑慮',
            'too_many_emails' => '郵件/通知太多',
            'other' => '其他',
        ],
        'reason_other' => '其他原因',
        'reason_other_placeholder' => '請輸入刪除原因',
        'password' => '確認密碼',
        'password_placeholder' => '輸入目前密碼',
        'password_hint' => '請輸入目前密碼以驗證身分。',
        'confirm_text' => '我已閱讀並理解以上所有內容，同意個人資訊匿名化及帳戶刪除。',
        'submit' => '刪除帳戶',
        'success' => '帳戶已刪除。感謝您的使用。',
        'wrong_password' => '密碼不正確。',
        'error' => '刪除處理過程中發生錯誤。',
        'confirm_required' => '請勾選同意刪除。',
    ],
];
