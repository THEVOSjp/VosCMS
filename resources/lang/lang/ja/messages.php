<?php
/**
 * Japanese Language File
 */

return [
    // Common
    'app_name' => 'RezlyX',
    'welcome' => 'ようこそ',
    'home' => 'ホーム',
    'back' => '戻る',
    'next' => '次へ',
    'cancel' => 'キャンセル',
    'confirm' => '確認',
    'save' => '保存',
    'delete' => '削除',
    'edit' => '編集',
    'search' => '検索',
    'loading' => '読み込み中...',
    'no_data' => 'データがありません。',
    'error' => 'エラーが発生しました。',
    'success' => '正常に処理されました。',

    // Auth
    'auth' => [
        'login' => 'ログイン',
        'logout' => 'ログアウト',
        'register' => '会員登録',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirm' => 'パスワード（確認）',
        'remember_me' => 'ログイン状態を保持',
        'forgot_password' => 'パスワードをお忘れですか？',
        'reset_password' => 'パスワード再設定',
        'invalid_credentials' => 'メールアドレスまたはパスワードが正しくありません。',
        'account_inactive' => 'このアカウントは無効です。',
    ],

    // Reservation
    'reservation' => [
        'title' => '予約',
        'new' => '新規予約',
        'my_reservations' => '予約履歴',
        'select_service' => 'サービス選択',
        'select_date' => '日付選択',
        'select_time' => '時間選択',
        'customer_info' => 'お客様情報',
        'payment' => 'お支払い',
        'confirmation' => '予約確認',
        'status' => [
            'pending' => '保留中',
            'confirmed' => '確定',
            'completed' => '完了',
            'cancelled' => 'キャンセル',
            'no_show' => 'ノーショー',
        ],
    ],

    // Services
    'service' => [
        'title' => 'サービス',
        'category' => 'カテゴリ',
        'price' => '料金',
        'duration' => '所要時間',
        'description' => '説明',
        'options' => 'オプション',
    ],

    // Member
    'member' => [
        'profile' => 'マイページ',
        'points' => 'ポイント',
        'grade' => '会員ランク',
        'reservations' => '予約履歴',
        'payments' => '決済履歴',
        'settings' => '設定',
    ],

    // Payment
    'payment' => [
        'title' => '決済',
        'amount' => '決済金額',
        'method' => '決済方法',
        'card' => 'クレジットカード',
        'bank_transfer' => '銀行振込',
        'virtual_account' => 'バーチャル口座',
        'points' => 'ポイント',
        'use_points' => 'ポイント使用',
        'available_points' => '利用可能ポイント',
        'complete' => '決済完了',
        'failed' => '決済失敗',
    ],

    // Time
    'time' => [
        'today' => '今日',
        'tomorrow' => '明日',
        'minutes' => '分',
        'hours' => '時間',
        'days' => '日',
    ],

    // Validation
    'validation' => [
        'required' => ':attributeは必須です。',
        'email' => '有効なメールアドレスを入力してください。',
        'min' => ':attributeは:min文字以上で入力してください。',
        'max' => ':attributeは:max文字以内で入力してください。',
        'confirmed' => ':attributeが一致しません。',
    ],
];
