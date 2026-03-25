<?php

/**
 * 予約関連翻訳 - 日本語
 */

return [
    // ページタイトル
    'title' => '予約する',
    'service_list' => 'サービス一覧',
    'select_service' => 'サービス選択',
    'select_date' => '日付選択',
    'select_time' => '時間選択',
    'enter_info' => '情報入力',
    'confirm_booking' => '予約確認',
    'confirm_info' => '予約情報をご確認ください',
    'complete_booking' => '予約を完了する',
    'select_service_datetime' => 'ご希望のサービスと日時を選択してください',
    'staff_designation_guide' => 'スタッフ指名予約はスタッフページからお進みください',
    'go_staff_booking' => 'スタッフ指名予約',
    'select_datetime' => '日時を選択してください',
    'no_services' => '現在登録されているサービスがありません。',
    'contact_admin' => '管理者にお問い合わせください。',
    'notes' => 'ご要望',
    'notes_placeholder' => 'ご要望があればご記入ください',
    'customer' => '予約者',
    'phone' => '電話番号',
    'date_label' => '日付',
    'time_label' => '時間',
    'total_price' => 'お支払い金額',
    'cancel_policy' => '予約確定後、予約時間の24時間前までキャンセル可能です。それ以降のキャンセルにはキャンセル料が発生する場合があります。',
    'success' => '予約が完了しました！',
    'success_desc' => '確認メッセージが送信されます。下記の予約番号を保管してください。',
    'submitting' => '処理中...',
    'select_staff' => 'スタッフを選択してください',
    'no_preference' => '指定なし',
    'staff' => '担当スタッフ',
    'designation_fee' => '指名料',
    'designation_fee_badge' => '+:amount',
    'loading_slots' => '空き時間を確認中...',
    'no_available_slots' => '選択した日付に予約可能な時間がありません。',
    'items_selected' => '件選択',
    'total_duration' => '合計所要時間',

    // ステップ
    'step' => [
        'service' => 'サービス選択',
        'datetime' => '日時',
        'info' => '情報入力',
        'confirm' => '確認',
    ],

    // サービス
    'service' => [
        'title' => 'サービス',
        'name' => 'サービス名',
        'description' => '説明',
        'duration' => '所要時間',
        'price' => '料金',
        'category' => 'カテゴリ',
        'select' => '選択',
        'view_detail' => '詳細を見る',
        'no_services' => '利用可能なサービスがありません。',
    ],

    // 日付/時間
    'date' => [
        'title' => '予約日',
        'select_date' => '日付を選択してください',
        'available' => '予約可能',
        'unavailable' => '予約不可',
        'fully_booked' => '満席',
        'past_date' => '過去の日付',
    ],

    'time' => [
        'title' => '予約時間',
        'select_time' => '時間を選択してください',
        'available_slots' => '予約可能な時間帯',
        'no_slots' => '予約可能な時間帯がありません。',
        'remaining' => '残り:count席',
    ],

    // 予約フォーム
    'form' => [
        'customer_name' => 'お名前',
        'customer_email' => 'メールアドレス',
        'customer_phone' => '電話番号',
        'guests' => '人数',
        'notes' => 'ご要望',
        'notes_placeholder' => 'ご要望があればご記入ください',
    ],

    // 予約確認
    'confirm' => [
        'title' => '予約確認',
        'summary' => '予約内容の確認',
        'service_info' => 'サービス情報',
        'booking_info' => '予約情報',
        'customer_info' => 'お客様情報',
        'total_price' => '合計金額',
        'agree_terms' => '予約規約に同意します',
        'submit' => '予約する',
    ],

    // 予約完了
    'complete' => [
        'title' => '予約完了',
        'success' => 'ご予約が完了しました！',
        'booking_code' => '予約番号',
        'check_email' => '確認メールをお送りしました。',
        'view_detail' => '予約詳細を見る',
        'book_another' => '別の予約をする',
    ],

    // 予約照会
    'lookup' => [
        'title' => '予約照会',
        'description' => '予約情報を入力して予約を確認してください。',
        'booking_code' => '予約番号',
        'booking_code_placeholder' => 'RZ250301XXXXXX',
        'email' => 'メールアドレス',
        'email_placeholder' => '予約時のメールアドレス',
        'phone' => '電話番号',
        'phone_placeholder' => '予約時の電話番号',
        'search' => '検索',
        'search_method' => '検索方法',
        'by_code' => '予約番号で検索',
        'by_email' => 'メールアドレスで検索',
        'by_phone' => '電話番号で検索',
        'not_found' => '予約が見つかりません。入力情報をご確認ください。',
        'input_required' => '予約番号とメールアドレスまたは電話番号を入力してください。',
        'result_title' => '検索結果',
        'multiple_results' => ':count件の予約が見つかりました。',
        'hint' => '予約番号とメールアドレスまたは電話番号を一緒に入力すると正確な検索が可能です。',
        'help_text' => '予約が見つかりませんか？',
        'contact_support' => 'サポートに問い合わせる',
    ],

    // 予約詳細
    'detail' => [
        'title' => '予約詳細',
        'status' => '予約状況',
        'booking_date' => '予約日時',
        'service' => 'サービス',
        'guests' => '人数',
        'total_price' => 'お支払い金額',
        'payment_status' => '支払い状況',
        'notes' => 'ご要望',
        'created_at' => '予約日時',
    ],

    // 予約キャンセル
    'cancel' => [
        'title' => '予約キャンセル',
        'confirm' => '本当に予約をキャンセルしますか？',
        'reason' => 'キャンセル理由',
        'reason_placeholder' => 'キャンセル理由をご記入ください',
        'submit' => '予約をキャンセル',
        'success' => '予約がキャンセルされました。',
        'cannot_cancel' => 'この予約はキャンセルできません。',
    ],

    // ステータスメッセージ
    'status' => [
        'pending' => 'ご予約を受け付けました。確定をお待ちください。',
        'confirmed' => 'ご予約が確定しました。',
        'cancelled' => 'ご予約がキャンセルされました。',
        'completed' => 'ご利用が完了しました。',
        'no_show' => 'ノーショーとして処理されました。',
    ],

    // エラーメッセージ
    'error' => [
        'service_not_found' => 'サービスが見つかりません。',
        'slot_unavailable' => '選択した時間帯は予約できません。',
        'past_date' => '過去の日付は予約できません。',
        'max_capacity' => '予約可能人数を超えています。',
        'booking_failed' => '予約処理中にエラーが発生しました。',
        'required_fields' => 'お名前と連絡先を入力してください。',
        'invalid_service' => '無効なサービスです。',
    ],

    'member_discount' => '会員割引',
    'use_points' => 'ポイント使用',
    'points_balance' => '残高',
    'use_all' => '全額使用',
    'points_default_name' => 'ポイント',
    'deposit_pay_now' => '予約金（お支払い金額）',
    'deposit_remaining_later' => '残額はサービス利用時にお支払いいただきます',
    'next' => '次へ',
    'categories' => '件のカテゴリ',
    'service_count' => '件のサービス',
];
