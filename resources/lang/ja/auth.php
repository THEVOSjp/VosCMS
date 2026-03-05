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
    'register' => [
        'title' => '新規登録',
        'description' => 'RezlyXで予約を始めましょう',
        'name' => 'お名前',
        'name_placeholder' => '山田太郎',
        'furigana' => 'ふりがな',
        'furigana_placeholder' => 'やまだたろう',
        'email' => 'メールアドレス',
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
            'profile' => 'プロフィール編集',
            'password' => 'パスワード変更',
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
    ],

    // プロフィール
    'profile' => [
        'title' => 'プロフィール編集',
        'description' => '個人情報を編集します。',
        'name' => 'お名前',
        'email' => 'メールアドレス',
        'email_hint' => 'メールアドレスは変更できません。',
        'phone' => '電話番号',
        'submit' => '保存',
        'success' => 'プロフィールが更新されました。',
        'error' => 'プロフィールの更新中にエラーが発生しました。',
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
];
