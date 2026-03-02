<?php

/**
 * マイページ翻訳 - 日本語
 */

return [
    // メイン
    'title' => 'マイページ',
    'welcome' => ':nameさん、ようこそ！',

    // ナビゲーション
    'nav' => [
        'dashboard' => 'ダッシュボード',
        'reservations' => '予約履歴',
        'profile' => 'プロフィール',
        'password' => 'パスワード変更',
        'logout' => 'ログアウト',
    ],

    // ダッシュボード
    'dashboard' => [
        'upcoming' => '今後の予約',
        'recent' => '最近の予約',
        'no_upcoming' => '今後の予約はありません。',
        'no_recent' => '最近の予約はありません。',
        'view_all' => 'すべて表示',
    ],

    // 予約履歴
    'reservations' => [
        'title' => '予約履歴',
        'filter' => [
            'all' => 'すべて',
            'pending' => '保留中',
            'confirmed' => '確定',
            'completed' => '完了',
            'cancelled' => 'キャンセル',
        ],
        'no_reservations' => '予約履歴がありません。',
        'booking_code' => '予約番号',
        'service' => 'サービス',
        'date' => '予約日',
        'status' => 'ステータス',
        'actions' => '操作',
        'view' => '詳細',
        'cancel' => 'キャンセル',
    ],

    // プロフィール
    'profile' => [
        'title' => 'プロフィール設定',
        'info' => '基本情報',
        'name' => 'お名前',
        'email' => 'メールアドレス',
        'phone' => '電話番号',
        'save' => '保存',
        'success' => 'プロフィールが更新されました。',
    ],

    // パスワード
    'password' => [
        'title' => 'パスワード変更',
        'current' => '現在のパスワード',
        'new' => '新しいパスワード',
        'confirm' => '新しいパスワード（確認）',
        'change' => '変更',
        'success' => 'パスワードが変更されました。',
        'mismatch' => '現在のパスワードが一致しません。',
    ],

    // 統計
    'stats' => [
        'total_bookings' => '総予約数',
        'completed' => '完了',
        'cancelled' => 'キャンセル',
        'upcoming' => '予定',
    ],
];
