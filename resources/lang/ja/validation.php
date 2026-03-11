<?php

/**
 * バリデーションメッセージ - 日本語
 */

return [
    // 基本メッセージ
    'accepted' => ':attributeに同意する必要があります。',
    'required' => ':attributeは必須です。',
    'email' => '有効なメールアドレスを入力してください。',
    'min' => ':attributeは:min文字以上で入力してください。',
    'max' => ':attributeは:max文字以内で入力してください。',
    'numeric' => ':attributeは数値で入力してください。',
    'integer' => ':attributeは整数で入力してください。',
    'string' => ':attributeは文字列で入力してください。',
    'boolean' => ':attributeはtrueまたはfalseで入力してください。',
    'array' => ':attributeは配列で入力してください。',
    'date' => '有効な日付を入力してください。',
    'same' => ':attribute確認が一致しません。',
    'confirmed' => ':attribute確認が一致しません。',
    'unique' => 'この:attributeは既に使用されています。',
    'exists' => '選択された:attributeは無効です。',
    'in' => '選択された:attributeは無効です。',
    'not_in' => '選択された:attributeは無効です。',
    'regex' => ':attributeの形式が正しくありません。',
    'url' => '有効なURLを入力してください。',
    'alpha' => ':attributeは文字のみ使用できます。',
    'alpha_num' => ':attributeは文字と数字のみ使用できます。',
    'alpha_dash' => ':attributeは文字、数字、ダッシュ、アンダースコアのみ使用できます。',

    // サイズ
    'size' => [
        'numeric' => ':attributeは:sizeである必要があります。',
        'string' => ':attributeは:size文字である必要があります。',
        'array' => ':attributeは:size個の項目を含む必要があります。',
    ],

    'between' => [
        'numeric' => ':attributeは:minから:maxの間である必要があります。',
        'string' => ':attributeは:minから:max文字の間である必要があります。',
        'array' => ':attributeは:minから:max個の項目を含む必要があります。',
    ],

    // ファイル
    'file' => ':attributeはファイルである必要があります。',
    'image' => ':attributeは画像ファイルである必要があります。',
    'mimes' => ':attributeは:values形式である必要があります。',
    'max_file' => ':attributeは:maxKBを超えることはできません。',

    // パスワード
    'password' => [
        'lowercase' => 'パスワードには小文字を含める必要があります。',
        'uppercase' => 'パスワードには大文字を含める必要があります。',
        'number' => 'パスワードには数字を含める必要があります。',
        'special' => 'パスワードには特殊文字を含める必要があります。',
    ],

    // 日付
    'date_format' => ':attributeは:format形式である必要があります。',
    'after' => ':attributeは:date以降の日付である必要があります。',
    'before' => ':attributeは:date以前の日付である必要があります。',
    'after_or_equal' => ':attributeは:date以降または同じ日付である必要があります。',
    'before_or_equal' => ':attributeは:date以前または同じ日付である必要があります。',

    // 属性名
    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード確認',
        'phone' => '電話番号',
        'customer_name' => 'お名前',
        'customer_email' => 'メールアドレス',
        'customer_phone' => '電話番号',
        'booking_date' => '予約日',
        'start_time' => '開始時間',
        'guests' => '人数',
        'service_id' => 'サービス',
        'category_id' => 'カテゴリ',
        'duration' => '所要時間',
        'price' => '料金',
        'description' => '説明',
        'notes' => 'メモ',
        'reason' => '理由',
    ],
];
