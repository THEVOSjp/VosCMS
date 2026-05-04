<?php

/**
 * 認証関連翻訳 - 日本語
 */

return [
    // ログイン
    'login' => [
        'title' => 'ログイン',
        'description' => 'アカウントにログインして予約を管理しましょう',
        'email' => 'メールアドレス',
        'email_placeholder' => 'example@email.com',
        'password' => 'パスワード',
        'password_placeholder' => '••••••••',
        'remember' => 'ログイン状態を維持',
        'forgot' => 'パスワードをお忘れですか？',
        'submit' => 'ログイン',
        'no_account' => 'アカウントをお持ちでないですか？',
        'register_link' => '新規登録',
        'back_home' => '← ホームに戻る',
        'success' => 'ログインしました。',
        'failed' => 'メールアドレスまたはパスワードが正しくありません。',
        'required' => 'メールアドレスとパスワードを入力してください。',
        'error' => 'ログイン処理中にエラーが発生しました。',
        'social_only' => 'ソーシャルログインで登録されたアカウントです。ソーシャルログインをご利用ください。',
    ],

    // 新規登録
    'admin_login' => [
        'title' => '管理者ログイン',
        'subtitle' => '管理者アカウントでサインインしてください',
        'errors' => [
            'account_inactive' => 'アカウントが無効です。管理者にお問い合わせください。',
            'not_admin' => 'このアカウントには管理者権限がありません。',
            'user_inactive' => '連携された会員アカウントが無効です。',
            'staff_inactive' => '連携されたスタッフアカウントが無効です。',
            'login_failed' => 'ログインに失敗しました。',
        ],
    ],

    'register' => [
        'title' => '新規登録',
        'description' => 'RezlyXで予約を始めましょう',
        'name' => 'お名前',
        'name_placeholder' => '山田太郎',
        'furigana' => 'ふりがな',
        'furigana_placeholder' => 'やまだたろう',
        'email' => 'メールアドレス',
        'email_placeholder' => 'example@email.com',
        'phone' => '電話番号',
        'phone_placeholder' => '090-1234-5678',
        'phone_hint' => '国コードを選択して電話番号を入力してください',
        'password' => 'パスワード',
        'password_placeholder' => '12文字以上',
        'password_hint' => '12文字以上、大文字・小文字・数字・特殊文字を含む',
        'password_confirm' => 'パスワード（確認）',
        'password_confirm_placeholder' => 'パスワード再入力',
        'agree_terms' => 'に同意します',
        'agree_privacy' => 'に同意します',
        'submit' => '登録する',
        'has_account' => 'すでにアカウントをお持ちですか？',
        'login_link' => 'ログイン',
        'success' => '登録が完了しました。',
        'success_login' => 'ログインへ',
        'email_exists' => 'このメールアドレスは既に登録されています。',
        'error' => '登録処理中にエラーが発生しました。',
    ],

    // パスワード忘れ
    'forgot' => [
        'title' => 'パスワードをお忘れの方',
        'description' => '登録したメールアドレスを入力してください。パスワード再設定リンクをお送りします。',
        'email' => 'メールアドレス',
        'submit' => '再設定リンクを送信',
        'back_login' => 'ログインに戻る',
        'success' => 'パスワード再設定リンクをメールで送信しました。',
        'not_found' => '登録されていないメールアドレスです。',
    ],

    // パスワード再設定
    'reset' => [
        'title' => 'パスワード再設定',
        'email' => 'メールアドレス',
        'password' => '新しいパスワード',
        'password_confirm' => '新しいパスワード（確認）',
        'submit' => 'パスワードを再設定',
        'success' => 'パスワードが再設定されました。',
        'invalid_token' => '無効なトークンです。',
        'expired_token' => 'トークンの有効期限が切れています。',
    ],

    // ログアウト
    'logout' => [
        'success' => 'ログアウトしました。',
    ],

    // メール認証
    'verify' => [
        'title' => 'メール認証',
        'description' => '登録したメールアドレスに認証メールを送信しました。メールをご確認ください。',
        'resend' => '認証メールを再送信',
        'success' => 'メールが認証されました。',
        'already_verified' => 'すでに認証済みです。',
    ],

    // ソーシャルログイン
    'social' => [
        'or' => 'または',
        'google' => 'Googleでログイン',
        'kakao' => 'カカオでログイン',
        'naver' => 'NAVERでログイン',
        'line' => 'LINEでログイン',
    ],

    // ソーシャルログインボタン
    'login_with_line' => 'LINEでログイン',
    'login_with_google' => 'Googleでログイン',
    'login_with_kakao' => 'カカオでログイン',
    'login_with_naver' => 'NAVERでログイン',
    'login_with_apple' => 'Appleでログイン',
    'login_with_facebook' => 'Facebookでログイン',
    'or_continue_with' => 'または',

    // 利用規約同意
    'terms' => [
        'title' => '利用規約同意',
        'subtitle' => 'サービスをご利用いただくには、利用規約に同意してください',
        'agree_all' => 'すべての利用規約に同意します',
        'required' => '必須',
        'optional' => '任意',
        'required_mark' => '必須',
        'required_note' => '* は必須項目です',
        'required_alert' => '必須の利用規約に同意してください。',
        'notice' => '利用規約に同意されない場合、サービスをご利用いただけない場合があります。',
        'view_content' => '内容を見る',
        'view_full' => '全文を見る',
        'hide_content' => '内容を閉じる',
        'translation_pending' => '翻訳準備中',
    ],

    // マイページ
    'mypage' => [
        'title' => 'マイページ',
        'welcome' => ':name様、こんにちは！',
        'member_since' => ':date登録',
        'menu' => [
            'dashboard' => 'ダッシュボード',
            'reservations' => '予約履歴',
            'profile' => 'プロフィール',
            'services' => 'サービス管理',
            'custom_projects' => '制作プロジェクト',
            'messages' => 'メッセージ',
            'settings' => '設定',
            'password' => 'パスワード変更',
            'withdraw' => '退会',
            'logout' => 'ログアウト',
        ],
        'stats' => [
            'total_reservations' => '総予約数',
            'upcoming' => '予定の予約',
            'completed' => '完了した予約',
            'cancelled' => 'キャンセル',
        ],
        'recent_reservations' => '最近の予約',
        'no_reservations' => '予約履歴がありません。',
        'view_all' => 'すべて見る',
        'quick_actions' => 'クイックメニュー',
        'make_reservation' => '新規予約',
        'messages' => [
            'title' => 'メッセージ',
            'mark_all_read' => 'すべて既読',
            'empty' => 'メッセージがありません。',
            'tab_notifications' => '通知',
            'tab_conversations' => '会話',
            'conversations' => '会話一覧',
            'no_conversations' => '会話がありません',
            'new' => '新規',
            'new_message' => '新規メッセージ',
            'recipient' => '宛先',
            'recipient_placeholder' => 'ニックネームまたはメールで検索',
            'body' => '本文',
            'placeholder_message' => 'メッセージを入力...',
            'send' => '送信',
            'select_conversation' => '会話を選択してください',
        ],
    ],

    // プロフィール
    'profile' => [
            'services' => 'サービス管理',
        'title' => 'プロフィール',
        'description' => 'プロフィール情報です。',
        'edit_title' => 'プロフィール編集',
        'edit_description' => '個人情報を編集します。',
        'edit_button' => '編集',
        'name' => 'お名前',
        'email' => 'メールアドレス',
        'email_hint' => 'メールアドレスは変更できません。',
        'phone' => '電話番号',
        'not_set' => '未設定',
        'submit' => '保存',
        'success' => 'プロフィールが更新されました。',
        'error' => 'プロフィールの更新中にエラーが発生しました。',
    ],

    // 設定
    'settings' => [
        'title' => 'プライバシー設定',
        'description' => '他のユーザーに表示する情報を選択します。',
        'info' => '無効にした項目は他のユーザーに表示されません。名前は常に表示されます。',
        'success' => '設定が保存されました。',
        'error' => '設定の保存中にエラーが発生しました。',
        'no_fields' => '設定可能な項目がありません。',
        'fields' => [
            'email' => 'メールアドレス', 'email_desc' => 'メールアドレスを他のユーザーに表示します。',
            'profile_photo' => 'プロフィール写真', 'profile_photo_desc' => 'プロフィール写真を他のユーザーに表示します。',
            'phone' => '電話番号', 'phone_desc' => '電話番号を他のユーザーに表示します。',
            'birth_date' => '生年月日', 'birth_date_desc' => '生年月日を他のユーザーに表示します。',
            'gender' => '性別', 'gender_desc' => '性別を他のユーザーに表示します。',
            'company' => '会社', 'company_desc' => '会社情報を他のユーザーに表示します。',
            'blog' => 'ブログ', 'blog_desc' => 'ブログURLを他のユーザーに表示します。',
        ],
    ],

    // パスワード変更
    'password_change' => [
        'title' => 'パスワード変更',
        'description' => 'セキュリティのため、定期的にパスワードを変更してください。',
        'current' => '現在のパスワード',
        'current_placeholder' => '現在のパスワードを入力',
        'new' => '新しいパスワード',
        'new_placeholder' => '新しいパスワードを入力',
        'confirm' => '新しいパスワード（確認）',
        'confirm_placeholder' => '新しいパスワードを再入力',
        'submit' => 'パスワードを変更',
        'success' => 'パスワードが変更されました。',
        'error' => 'パスワードの変更中にエラーが発生しました。',
        'wrong_password' => '現在のパスワードが正しくありません。',
    ],

    // 退会
    'withdraw' => [
        'title' => '退会',
        'description' => '退会すると個人情報は即座に匿名化され、退会後はアカウントを復元できません。',
        'warning_title' => '退会前に必ずご確認ください',
        'warnings' => [
            'account' => '氏名、メールアドレス、電話番号、生年月日、プロフィール写真などすべての個人情報が即座に匿名化されます。本人の特定は不可能になります。',
            'reservation' => '進行中または予定されている予約がある場合は、退会前に必ずキャンセルしてください。退会後は予約の変更・キャンセルができません。',
            'payment' => '決済および売上関連記録は、関連税法（韓国国税基本法5年、日本法人税法7年）に基づき、匿名化された状態で法定保存期間中保持されます。',
            'recovery' => '退会処理されたアカウントは復元できません。同じメールアドレスで再登録は可能ですが、以前の予約履歴・ポイント・メッセージなど既存データは一切復元されません。',
            'social' => 'ソーシャルログイン（Google、カカオ、LINEなど）で登録した場合、該当ソーシャルサービスとの連携も解除されます。',
            'message' => '受信したメッセージ、通知履歴はすべて削除され、確認できなくなります。',
        ],
        'retention_notice' => '※ 関連法令により保管が必要な取引記録は、個人を特定できない形で法定期間保管後、完全に削除されます。',
        'reason' => '退会理由',
        'reason_placeholder' => '退会理由を選択してください',
        'reasons' => [
            'not_using' => 'サービスを利用しなくなった',
            'other_service' => '他のサービスに乗り換え',
            'dissatisfied' => 'サービスに不満',
            'privacy' => '個人情報保護の懸念',
            'too_many_emails' => 'メール・通知が多すぎる',
            'other' => 'その他',
        ],
        'reason_other' => 'その他の理由',
        'reason_other_placeholder' => '退会理由を入力してください',
        'password' => 'パスワード確認',
        'password_placeholder' => '現在のパスワードを入力',
        'password_hint' => '本人確認のため、現在のパスワードを入力してください。',
        'confirm_text' => '上記の案内事項をすべて確認し、個人情報の匿名化および退会に同意します。',
        'submit' => '退会する',
        'success' => '退会が完了しました。ご利用ありがとうございました。',
        'wrong_password' => 'パスワードが一致しません。',
        'error' => '退会処理中にエラーが発生しました。',
        'confirm_required' => '退会への同意にチェックを入れてください。',
    ],
];
