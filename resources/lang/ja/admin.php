<?php

/**
 * 管理者翻訳 - 日本語
 */

return [
    // 共通
    'title' => '管理者',
    'dashboard' => 'ダッシュボード',
    'back_to_site' => 'サイトに戻る',
    'dark_mode' => 'ダークモード切替',

    // ナビゲーション
    'nav' => [
        'dashboard' => 'ダッシュボード',
        'reservations' => '予約管理',
        'services' => 'サービス管理',
        'categories' => 'カテゴリ管理',
        'time_slots' => '時間帯管理',
        'members' => '会員管理',
        'members_list' => '会員一覧',
        'members_settings' => '会員設定',
        'members_groups' => '会員グループ',
        'points' => 'ポイント管理',
        'users' => 'ユーザー管理',
        'settings' => '設定',
        'site_management' => 'サイト管理',
        'menu_management' => 'メニュー管理',
        'design_management' => 'デザイン管理',
        'page_management' => 'ページ管理',
    ],

    // ダッシュボード
    'stats' => [
        'today_reservations' => '本日の予約',
        'pending_reservations' => '保留中の予約',
        'monthly_revenue' => '今月の売上',
        'active_services' => '有効なサービス',
        'total_users' => '総ユーザー数',
    ],

    // 予約管理
    'reservations' => [
        'title' => '予約管理',
        'list' => '予約一覧',
        'calendar' => 'カレンダー表示',
        'statistics' => '統計',
        'create' => '予約追加',
        'edit' => '予約編集',
        'detail' => '予約詳細',

        'filter' => [
            'all' => 'すべて',
            'today' => '本日',
            'pending' => '保留中',
            'confirmed' => '確定',
        ],

        'actions' => [
            'confirm' => '確定',
            'cancel' => 'キャンセル',
            'complete' => '完了',
            'no_show' => 'ノーショー',
            'edit' => '編集',
            'delete' => '削除',
        ],

        'confirm_msg' => '予約を確定しますか？',
        'cancel_msg' => '予約をキャンセルしますか？',
        'complete_msg' => '完了として処理しますか？',
        'noshow_msg' => 'ノーショーとして処理しますか？',

        'success' => [
            'created' => '予約が作成されました。',
            'updated' => '予約が更新されました。',
            'confirmed' => '予約が確定されました。',
            'cancelled' => '予約がキャンセルされました。',
            'completed' => '予約が完了処理されました。',
        ],
    ],

    // サービス管理
    'services' => [
        'title' => 'サービス管理',
        'list' => 'サービス一覧',
        'create' => 'サービス追加',
        'edit' => 'サービス編集',
        'detail' => 'サービス詳細',

        'fields' => [
            'name' => 'サービス名',
            'slug' => 'URLスラッグ',
            'description' => '説明',
            'short_description' => '短い説明',
            'duration' => '所要時間（分）',
            'price' => '料金',
            'category' => 'カテゴリ',
            'is_active' => '有効',
            'max_capacity' => '最大収容人数',
            'buffer_time' => 'バッファ時間（分）',
            'advance_booking_days' => '予約可能期間（日）',
            'min_notice_hours' => '最小通知時間（時間）',
        ],

        'success' => [
            'created' => 'サービスが作成されました。',
            'updated' => 'サービスが更新されました。',
            'deleted' => 'サービスが削除されました。',
            'activated' => 'サービスが有効になりました。',
            'deactivated' => 'サービスが無効になりました。',
        ],

        'error' => [
            'has_reservations' => '予約があるサービスは削除できません。',
        ],
    ],

    // カテゴリ管理
    'categories' => [
        'title' => 'カテゴリ管理',
        'list' => 'カテゴリ一覧',
        'create' => 'カテゴリ追加',
        'edit' => 'カテゴリ編集',

        'fields' => [
            'name' => 'カテゴリ名',
            'slug' => 'URLスラッグ',
            'description' => '説明',
            'parent' => '親カテゴリ',
            'sort_order' => '並び順',
            'is_active' => '有効',
        ],

        'success' => [
            'created' => 'カテゴリが作成されました。',
            'updated' => 'カテゴリが更新されました。',
            'deleted' => 'カテゴリが削除されました。',
        ],
    ],

    // 時間帯管理
    'time_slots' => [
        'title' => '時間帯管理',
        'default_slots' => 'デフォルト時間帯',
        'blocked_dates' => 'ブロックされた日付',

        'fields' => [
            'day_of_week' => '曜日',
            'start_time' => '開始時間',
            'end_time' => '終了時間',
            'max_bookings' => '最大予約数',
            'service' => 'サービス',
            'specific_date' => '特定の日付',
        ],

        'block_date' => '日付をブロック',
        'unblock_date' => 'ブロック解除',
    ],

    // ユーザー管理
    'users' => [
        'title' => 'ユーザー管理',
        'list' => 'ユーザー一覧',
        'create' => 'ユーザー追加',
        'edit' => 'ユーザー編集',
        'detail' => 'ユーザー詳細',

        'fields' => [
            'name' => '名前',
            'email' => 'メールアドレス',
            'phone' => '電話番号',
            'role' => '権限',
            'is_active' => '有効',
            'created_at' => '登録日',
            'last_login' => '最終ログイン',
        ],

        'roles' => [
            'user' => '一般ユーザー',
            'admin' => '管理者',
            'super_admin' => '最高管理者',
        ],
    ],

    // 設定
    'settings' => [
        'title' => 'システム設定',
        'general' => '一般設定',
        'booking' => '予約設定',
        'email' => 'メール設定',
        'payment' => '支払い設定',

        // 設定タブ
        'tabs' => [
            'general' => '一般',
            'seo' => 'SEO',
            'pwa' => 'PWA',
            'system' => 'システム',
        ],

        // 管理者パス
        'admin_path' => [
            'title' => '管理者アクセスパス',
            'description' => 'セキュリティのため、管理者ページのアクセスパスを変更できます。',
            'current_url' => '現在のアクセスURL',
            'label' => '管理者パス',
            'hint' => '英字、数字、ハイフン（-）、アンダースコア（_）のみ使用できます。',
            'warning' => 'パス変更後は新しいアドレスでアクセスする必要があります。',
            'button' => 'パス変更',
            'changed' => '管理者パスが変更されました。新しいパスでアクセス中です。',
            'error_empty' => '管理者パスを入力してください。',
            'error_invalid' => '管理者パスには英字、数字、ハイフン、アンダースコアのみ使用できます。',
            'error_reserved' => '予約されたパスは使用できません。',
        ],

        // サイト設定
        'site' => [
            'title' => 'サイト基本設定',
            'category_label' => 'サイト分類（業種）',
            'category_description' => '予約システムが適用される業種を選択してください。業種に応じて最適化された機能が提供されます。',
            'category_placeholder' => '-- 業種を選択してください --',
            'categories' => [
                'beauty_salon' => '美容室 / ヘアサロン',
                'nail_salon' => 'ネイルサロン',
                'skincare' => 'スキンケア / エステ',
                'massage' => 'マッサージ / スパ',
                'hospital' => '病院 / クリニック',
                'dental' => '歯科',
                'studio' => 'スタジオ / 写真館',
                'restaurant' => 'レストラン / カフェ',
                'accommodation' => '宿泊 / ホテル / ペンション',
                'sports' => 'スポーツ / フィットネス / ゴルフ',
                'education' => '教育 / 塾 / レッスン',
                'consulting' => 'コンサルティング / 相談',
                'pet' => 'ペットサービス / 動物病院',
                'car' => '自動車整備 / 洗車',
                'other' => 'その他',
            ],
            'name' => 'サイト名',
            'tagline' => 'サイトタイトル',
            'tagline_hint' => 'サイトのスローガンや短い説明を入力してください。',
            'url' => 'サイトURL',
        ],

        // 多言語入力
        'multilang' => [
            'button_title' => '多言語入力',
            'modal_title' => '多言語入力',
            'modal_description' => '各言語ごとに内容を入力してください。',
            'tab_ko' => '韓国語',
            'tab_en' => '英語',
            'tab_ja' => '日本語',
            'placeholder' => '内容を入力...',
            'save' => '保存',
            'cancel' => 'キャンセル',
            'saved' => '多言語内容が保存されました。',
            'error' => '保存中にエラーが発生しました。',
        ],

        // ロゴ設定
        'logo' => [
            'title' => 'ロゴ設定',
            'type_label' => 'ロゴ表示形式',
            'type_text' => 'テキストのみ',
            'type_image' => '画像のみ',
            'type_image_text' => '画像 + テキスト',
            'image_label' => 'ロゴ画像',
            'current' => '現在のロゴ',
            'preview' => '新しいロゴプレビュー',
            'display_preview' => '表示プレビュー',
            'hint' => 'JPG、PNG、GIF、SVG、WebP対応（推奨サイズ：高さ40px）',
            'delete' => '削除',
            'delete_confirm' => 'ロゴ画像を削除しますか？',
        ],

        // SEO設定
        'seo' => [
            'title' => 'SEO設定',
            'description' => '検索エンジン最適化の設定を管理します。',

            // メタタグ
            'meta' => [
                'title' => 'メタタグ',
                'description_label' => 'メタ説明（Meta Description）',
                'description_hint' => '検索結果に表示されるサイト説明です。150-160文字推奨。',
                'keywords_label' => 'メタキーワード（Meta Keywords）',
                'keywords_hint' => 'カンマで区切って入力（例：予約、ビューティー、ヘアサロン）',
                'keywords_placeholder' => '予約、ビューティー、ヘアサロン、ネイル',
            ],

            // オープングラフ
            'og' => [
                'title' => 'ソーシャルメディア（Open Graph）',
                'description' => 'SNS共有時に表示される情報を設定します。',
                'image_label' => '代表画像（OG Image）',
                'image_hint' => '推奨サイズ：1200x630ピクセル（JPG、PNG、WebP）',
                'image_current' => '現在の画像',
                'image_preview' => '新しい画像プレビュー',
                'image_delete' => '削除',
                'image_delete_confirm' => '代表画像を削除しますか？',
            ],

            // 検索エンジン
            'search_engine' => [
                'title' => '検索エンジン設定',
                'robots_label' => '検索エンジン公開',
                'robots_index' => '検索許可（index, follow）',
                'robots_noindex' => '検索不許可（noindex, nofollow）',
                'robots_hint' => 'サイトが検索エンジンに表示されるかどうかを設定します。',
            ],

            // ウェブマスターツール
            'webmaster' => [
                'title' => 'ウェブマスターツール認証',
                'google_label' => 'Google Search Console',
                'google_hint' => 'Google Search Consoleメタタグのcontent値を入力してください。',
                'google_placeholder' => 'XXXXXXXXXXXXXXXX',
                'naver_label' => 'Naverウェブマスターツール',
                'naver_hint' => 'Naverウェブマスターツールメタタグのcontent値を入力してください。',
                'naver_placeholder' => 'XXXXXXXXXXXXXXXX',
            ],

            // アナリティクス
            'analytics' => [
                'title' => '分析ツール連携',
                'ga_label' => 'Google AnalyticsトラッキングID',
                'ga_hint' => 'G-XXXXXXXXXXまたはUA-XXXXXXXXX-X形式で入力してください。',
                'ga_placeholder' => 'G-XXXXXXXXXX',
                'gtm_label' => 'Google Tag Manager ID',
                'gtm_hint' => 'GTM-XXXXXXX形式で入力してください。',
                'gtm_placeholder' => 'GTM-XXXXXXX',
            ],

            // 保存メッセージ
            'success' => 'SEO設定が保存されました。',
        ],

        // PWA設定
        'pwa' => [
            'title' => 'PWA設定',
            'description' => 'プログレッシブウェブアプリ（PWA）の設定を管理します。',

            // フロントエンドPWA
            'front' => [
                'title' => 'フロントエンドPWA',
                'description' => 'ユーザー向けウェブアプリの設定です。',
                'name_label' => 'アプリ名',
                'name_placeholder' => 'アプリ名を入力',
                'short_name_label' => '短い名前',
                'short_name_placeholder' => '短い名前',
                'short_name_hint' => '最大12文字。ホーム画面でスペースが限られている場合に表示されます。',
                'description_label' => 'アプリの説明',
                'theme_color_label' => 'テーマカラー',
                'bg_color_label' => '背景色',
                'display_label' => '表示モード',
                'icon_label' => 'アプリアイコン',
            ],

            // 管理者PWA
            'admin' => [
                'title' => '管理者PWA',
                'description' => '管理者向けウェブアプリの設定です。',
                'name_label' => 'アプリ名',
                'short_name_label' => '短い名前',
                'theme_color_label' => 'テーマカラー',
                'bg_color_label' => '背景色',
                'icon_label' => 'アプリアイコン',
            ],

            // 共通
            'icon_current' => '現在のアイコン',
            'icon_hint' => 'PNGまたはWebP形式、512x512ピクセル推奨',
            'icon_delete' => 'アイコンを削除',
            'icon_delete_confirm' => 'アイコンを削除しますか？',
            'icon_deleted' => 'アイコンが削除されました。',
            'error_icon_type' => '無効な画像形式です。PNGまたはWebPのみ使用可能です。',
            'success' => 'PWA設定が保存されました。',
        ],

        // システム情報
        'system' => [
            // タブメニュー
            'tabs' => [
                'info' => '情報管理',
                'cache' => 'キャッシュ管理',
                'mode' => 'モード管理',
                'logs' => 'ログ管理',
                'updates' => 'アップデート',
            ],
            'app' => [
                'title' => 'アプリケーション情報',
                'name' => 'アプリ名',
                'version' => 'バージョン',
                'environment' => '環境',
                'debug_mode' => 'デバッグモード',
                'debug_warning' => '本番環境ではデバッグモードを無効にしてください。',
                'url' => 'URL',
                'locale' => '言語',
            ],
            'php' => [
                'title' => 'PHP情報',
                'version' => 'PHPバージョン',
                'sapi' => 'SAPI',
                'timezone' => 'タイムゾーン',
                'memory_limit' => 'メモリ制限',
                'max_execution_time' => '最大実行時間',
                'upload_max_filesize' => '最大アップロードサイズ',
                'post_max_size' => '最大POSTサイズ',
                'display_errors' => 'エラー表示',
                'extensions' => '必須拡張機能',
            ],
            'db' => [
                'title' => 'データベース情報',
                'driver' => 'ドライバ',
                'version' => 'バージョン',
                'host' => 'ホスト',
                'database' => 'データベース',
                'charset' => '文字セット',
                'collation' => '照合順序',
            ],
            'server' => [
                'title' => 'サーバー情報',
                'os' => 'オペレーティングシステム',
                'os_family' => 'OS系統',
                'software' => 'サーバーソフトウェア',
                'document_root' => 'ドキュメントルート',
                'current_time' => '現在時刻',
            ],
            'status' => [
                'on' => 'オン',
                'off' => 'オフ',
            ],
            // キャッシュ管理
            'cache' => [
                'title' => 'キャッシュ管理',
                'description' => 'アプリケーションキャッシュを管理します。キャッシュを削除すると一時的にパフォーマンスが低下する場合があります。',
                'view' => 'ビューキャッシュ',
                'view_desc' => 'コンパイル済みビューテンプレートキャッシュ',
                'config' => '設定キャッシュ',
                'config_desc' => 'アプリケーション設定キャッシュ',
                'route' => 'ルートキャッシュ',
                'route_desc' => 'ルーティング情報キャッシュ',
                'clear' => '削除',
                'clear_all' => 'すべてのキャッシュを削除',
                'cached' => 'キャッシュ済み',
                'not_cached' => 'なし',
                'confirm_clear' => 'キャッシュを削除しますか？',
                'cleared' => 'キャッシュが削除されました。',
            ],
            // モード管理
            'mode' => [
                'title' => 'モード管理',
                'description' => 'アプリケーション実行モードを管理します。',
                'debug' => 'デバッグモード',
                'debug_desc' => '詳細なエラーメッセージを表示します。本番環境では無効にしてください。',
                'maintenance' => 'メンテナンスモード',
                'maintenance_desc' => 'サイトメンテナンス中のユーザーアクセスをブロックします。',
                'environment' => '環境',
                'environment_desc' => '現在のアプリケーション実行環境',
                'env_notice' => 'デバッグモードと環境設定は.envファイルで変更できます。',
                'enable_maintenance' => 'メンテナンスモードを有効化',
                'disable_maintenance' => 'メンテナンスモードを無効化',
                'confirm_enable_maintenance' => 'メンテナンスモードを有効にしますか？管理者を除くすべてのユーザーがサイトにアクセスできなくなります。',
                'confirm_disable_maintenance' => 'メンテナンスモードを無効にしますか？',
                'maintenance_enabled' => 'メンテナンスモードが有効になりました。',
                'maintenance_disabled' => 'メンテナンスモードが無効になりました。',
                'maintenance_message' => '現在サイトメンテナンス中です。しばらくしてから再度アクセスしてください。',
                // デバッグモード切り替え
                'enable_debug' => 'デバッグモードを有効化',
                'disable_debug' => 'デバッグモードを無効化',
                'confirm_enable_debug' => 'デバッグモードを有効にしますか？エラーの詳細が表示されます。',
                'confirm_disable_debug' => 'デバッグモードを無効にしますか？',
                'debug_enabled' => 'デバッグモードが有効になりました。',
                'debug_disabled' => 'デバッグモードが無効になりました。',
                'debug_error' => 'デバッグモードの設定中にエラーが発生しました。',
                'debug_env_locked' => '.envファイルでAPP_DEBUG=trueが設定されているため、無効にできません。',
            ],
            // ログ管理
            'logs' => [
                'title' => 'ログ管理',
                'description' => 'アプリケーションログファイルを管理します。',
                'filename' => 'ファイル名',
                'size' => 'サイズ',
                'modified' => '更新日',
                'actions' => '操作',
                'view' => '表示',
                'delete' => '削除',
                'download' => 'ダウンロード',
                'copy' => 'コピー',
                'copied' => 'クリップボードにコピーしました。',
                'selected' => ':count件選択',
                'delete_selected' => '選択削除',
                'confirm_delete_selected' => '選択したログファイルを削除しますか？',
                'selected_deleted' => ':count件のログファイルが削除されました。',
                'total_files' => '合計:count件',
                'last_lines' => '最新:count行表示',
                'showing_first' => ':total件中:count件表示',
                'clear_all' => 'すべてのログを削除',
                'no_logs' => 'ログファイルがありません。',
                'no_logs_desc' => 'システムログがまだ生成されていません。',
                'confirm_delete' => 'このログファイルを削除しますか？',
                'confirm_clear_all' => 'すべてのログファイルを削除しますか？この操作は元に戻せません。',
                'deleted' => 'ログファイルが削除されました。',
                'all_cleared' => 'すべてのログファイルが削除されました。',
                'back_to_list' => 'リストに戻る',
            ],
            // アップデート管理
            'updates' => [
                'title' => 'アップデート管理',
                'description' => 'GitHubを通じてシステムアップデートを管理します。',
                'current_version' => '現在のバージョン',
                'channel' => 'チャンネル',
                'check_update' => 'アップデート確認',
                'checking' => '確認中...',
                'check_failed' => 'アップデート確認に失敗しました。',
                'up_to_date' => '最新バージョンを使用しています。',
                'new_version_available' => '新しいバージョンがあります！',
                'view_details' => '詳細を見る',
                'release_notes' => 'リリースノート',
                'no_notes' => 'リリースノートがありません。',
                'no_releases' => 'リリースが見つかりません。',
                'requirements' => 'システム要件',
                'writable_root' => 'ルートディレクトリ書き込み権限',
                'writable_storage' => 'Storageディレクトリ書き込み権限',
                'not_available' => '不可',
                'requirements_warning' => '一部の要件が満たされていないため、自動アップデートが制限される場合があります。',
                'notes_title' => 'アップデート案内',
                'note_backup' => 'アップデート前に自動でバックアップが作成されます。',
                'note_maintenance' => 'アップデート中はサイトがメンテナンスモードに切り替わります。',
                'note_rollback' => 'アップデート失敗時は自動で以前のバージョンに復元されます。',
                // バックアップ関連
                'backups' => 'バックアップ一覧',
                'no_backups' => 'バックアップがありません。',
                'restore' => '復元',
                'confirm_restore' => 'このバックアップから復元しますか？現在のファイルが上書きされます。',
                'restore_failed' => '復元に失敗しました。',
                // アップデート実行
                'update_now' => '今すぐアップデート',
                'confirm_update' => 'アップデートを実行しますか？自動的にバックアップが作成されます。',
                'update_failed' => 'アップデートに失敗しました。',
                'reload_page' => 'まもなくページが更新されます。',
            ],
        ],

        // システム情報（レガシー）
        'system_info' => [
            'title' => 'システム情報',
            'php_version' => 'PHPバージョン',
            'environment' => '環境',
            'timezone' => 'タイムゾーン',
            'debug_mode' => 'デバッグモード',
            'enabled' => '有効',
            'disabled' => '無効',
        ],

        'fields' => [
            'app_name' => 'サイト名',
            'app_timezone' => 'タイムゾーン',
            'app_locale' => 'デフォルト言語',
            'admin_path' => '管理者パス',
            'booking_auto_confirm' => '予約自動確定',
            'booking_email_notification' => 'メール通知',
            'booking_advance_days' => '予約可能期間（日）',
        ],

        'success' => 'サイト設定が保存されました。',
        'error_save' => '保存に失敗しました',
        'error_image_type' => '許可されていない画像形式です。（JPG、PNG、GIF、SVG、WebPのみ）',
        'logo_deleted' => 'ロゴ画像が削除されました。',
    ],

    // サイト管理
    'site' => [
        // メニュー管理
        'menus' => [
            'title' => 'メニュー管理',
            'description' => 'サイトナビゲーションメニューを管理します。',
            'list' => 'メニュー一覧',
            'add' => 'メニュー追加',
            'coming_soon' => 'メニュー管理機能準備中',
            'coming_soon_desc' => 'まもなくナビゲーションメニューを管理できるようになります。',
        ],

        // デザイン管理
        'design' => [
            'title' => 'デザイン管理',
            'description' => 'サイトのデザインとテーマを管理します。',
            'theme_title' => 'テーマ設定',
            'theme_desc' => 'サイトの色テーマとスタイルを変更します。',
            'layout_title' => 'レイアウト設定',
            'layout_desc' => 'ページレイアウトと構造を変更します。',
            'header_footer_title' => 'ヘッダー/フッター',
            'header_footer_desc' => 'ヘッダーとフッターのデザインを変更します。',
            'coming_soon' => '準備中',
        ],

        // ページ管理
        'pages' => [
            'title' => 'ページ管理',
            'description' => 'カスタムページを作成・管理します。',
            'list' => 'ページ一覧',
            'add' => '新規ページ',
            'system_page' => 'システムページ',
            'custom_page' => 'カスタムページ',
            'empty' => 'カスタムページがまだありません。',
            'empty_hint' => '新しいページを追加してサイトを拡張しましょう。',
            'home' => 'ホーム',
            'terms' => '利用規約',
            'privacy' => 'プライバシーポリシー',
        ],
    ],

    // 共通ボタン
    'buttons' => [
        'save' => '保存',
        'cancel' => 'キャンセル',
        'delete' => '削除',
        'edit' => '編集',
        'add' => '追加',
        'create' => '作成',
        'update' => '更新',
        'search' => '検索',
        'reset' => 'リセット',
        'confirm' => '確認',
        'back' => '戻る',
        'close' => '閉じる',
    ],

    // 共通メッセージ
    'messages' => [
        'confirm_delete' => '本当に削除しますか？',
        'no_data' => 'データがありません。',
        'loading' => '読み込み中...',
        'processing' => '処理中...',
    ],

    // 共通テキスト
    'common' => [
        'yes' => 'はい',
        'no' => 'いいえ',
        'recommended' => '推奨',
        'enabled' => '有効',
        'disabled' => '無効',
        'active' => 'アクティブ',
        'inactive' => '非アクティブ',
        'actions' => '操作',
        'showing' => '表示中',
        'of' => '/',
        'prev' => '前へ',
        'next' => '次へ',
    ],

    // 会員管理
    'members' => [
        'title' => '会員管理',
        'list' => '会員一覧',
        'create' => '会員追加',
        'edit' => '会員編集',
        'detail' => '会員詳細',

        // 会員設定
        'settings' => [
            'title' => '会員設定',

            // タブメニュー
            'tabs' => [
                'general' => '基本設定',
                'features' => '機能設定',
                'terms' => '約款設定',
                'register' => '会員登録',
                'login' => 'ログイン',
                'design' => 'デザイン',
            ],

            // 基本設定
            'general' => [
                'title' => '基本設定',
                'description' => '会員システムの基本動作を設定します。',

                // 会員登録許可
                'registration_mode' => '会員登録許可',
                'registration_mode_desc' => '会員登録を受け付けるか選択します。URLキーを使用する場合、一致する文字列を含むURLでアクセスした場合のみ登録できます。',
                'registration_url_key' => 'URLキーが一致する場合のみ許可',
                'url_key_label' => 'URLキー',
                'url_key_placeholder' => '例: secretkey123',
                'url_key_hint' => '会員登録URLに?key=値の形式で含める必要があります。（例: /register?key=secretkey123）',

                // メール認証
                'email_verification' => 'メール認証を使用',
                'email_verification_desc' => '入力されたメールアドレスに認証メールを送信して会員登録を確認します。登録者が認証メールのリンクをクリックしないと正常にログインできません。',

                // 認証メール有効期間
                'email_validity' => '認証メール有効期間',
                'email_validity_desc' => '会員登録認証メール、ID/パスワード回復メールなどの有効期間を制限できます。',
                'days' => '日',

                // 会員プロフィール写真表示
                'show_profile_photo' => '会員プロフィール写真を表示',
                'show_profile_photo_desc' => '管理者会員一覧ページでプロフィール画像を表示するオプションです。会員一覧でプロフィール写真を表示したくない場合は「いいえ」を選択してください。',

                // パスワード変更時に他デバイスをログアウト
                'logout_on_password_change' => 'パスワード変更時に他デバイスをログアウト',
                'logout_on_password_change_desc' => 'パスワードを変更すると、現在のデバイス（ブラウザ）を除くすべてのセッションがログアウトされます。',

                // ID/PW回復方法
                'password_recovery_method' => 'ID/PW回復方法',
                'password_recovery_method_desc' => 'ID/PW回復機能を使用する際の新しいパスワード変更方法を選択します。',
                'recovery_link' => 'パスワード変更ページのリンクを送信',
                'recovery_random' => 'ランダムパスワードを送信',

                // 会員情報同期
                'sync_title' => '会員情報同期',
                'sync_description' => '会員情報と投稿/コメント情報を同期します。',
                'sync_warning' => 'データが多い場合は時間がかかる場合があります。ユーザーが多い場合は、必ずサービスを停止してから実行してください。',
                'sync_button' => '同期を実行',
                'sync_confirm' => '会員情報の同期を実行しますか？データが多い場合は時間がかかる場合があります。',
                'sync_complete' => '会員情報の同期が完了しました。',
                'sync_error' => '同期中にエラーが発生しました',

                // レガシー設定
                'auto_login' => '登録後自動ログイン',
                'auto_login_desc' => '会員登録完了後、自動的にログインします。',
                'default_group' => 'デフォルト会員グループ',
                'default_group_desc' => '新規会員に自動的に付与されるグループ',
                'groups' => [
                    'member' => '一般会員',
                    'vip' => 'VIP会員',
                    'pending' => '承認待ち',
                ],
            ],

            // 機能設定
            'features' => [
                'title' => '機能設定',
                'description' => '会員に提供する機能を設定します。',
                'view_scrap' => 'スクラップ閲覧',
                'view_scrap_desc' => 'スクラップしたコンテンツを確認できます。',
                'view_bookmark' => 'ブックマーク閲覧',
                'view_bookmark_desc' => 'ブックマークしたコンテンツを確認できます。',
                'view_posts' => '投稿閲覧',
                'view_posts_desc' => '作成した投稿を確認できます。',
                'view_comments' => 'コメント閲覧',
                'view_comments_desc' => '作成したコメントを確認できます。',
                'auto_login_manage' => '自動ログイン管理',
                'auto_login_manage_desc' => '会員が自動ログインデバイスを管理できます。',
            ],

            // 約款設定
            'terms' => [
                'title' => '約款設定',
                'description' => '会員登録時に同意を得る約款を設定します。最大5つの約款を登録できます。',
                'term_section' => '会員登録約款',
                'term_title' => '約款タイトル',
                'term_title_placeholder' => '例: 利用規約、プライバシーポリシー',
                'term_content' => '約款内容',
                'consent_required' => '同意必須',
                'consent_required_option' => '必須',
                'consent_optional_option' => '任意',
                'consent_disabled_option' => '無効',
            ],

            // 会員登録設定
            'register' => [
                'title' => '会員登録設定',
                'description' => '会員登録フォームとプロセスを設定します。',
                'form_fields' => '会員登録入力項目',
                'form_fields_desc' => '会員登録時に入力する項目を選択します。',
                'required_note' => '* マークの項目は必須項目です。',
                'fields' => [
                    'name' => '名前',
                    'email' => 'メールアドレス',
                    'password' => 'パスワード',
                    'phone' => '電話番号',
                    'birth_date' => '生年月日',
                    'gender' => '性別',
                    'company' => '会社/所属',
                    'blog' => 'ブログ',
                    'profile_photo' => 'プロフィール写真',
                ],
                'use_captcha' => 'CAPTCHAを使用',
                'use_captcha_desc' => 'ボット登録を防ぐためにCAPTCHAを表示します。',
                'email_provider' => 'メールプロバイダー管理',
                'email_provider_desc' => '特定のドメインのメールアドレスでのみ登録を許可したり、特定のドメインをブロックできます。（例：naver.com、gmail.com）',
                'email_provider_none' => '制限なし',
                'email_provider_allow' => '許可',
                'email_provider_block' => 'ブロック',
                'email_provider_placeholder' => '例：naver.com、gmail.com',
                'email_provider_hint' => '複数のドメインは1つずつ追加してください。何も入力しない場合、メールアドレスは制限されません。',
                'email_provider_invalid' => '無効なドメイン形式です。',
                'email_provider_duplicate' => 'このドメインは既に登録されています。',
                'welcome_email' => 'ウェルカムメールを送信',
                'welcome_email_desc' => '登録完了後にウェルカムメールを送信します。',
                'redirect_url' => '登録後リダイレクトURL',
                'redirect_url_desc' => '会員登録完了後にリダイレクトするURLを入力します。',
                'redirect_url_placeholder' => '例：/welcome または https://example.com/welcome',
                'redirect_url_hint' => '空欄の場合、デフォルトページ（マイページまたはホーム）にリダイレクトされます。',
            ],

            // ログイン設定
            'login' => [
                'title' => 'ログイン設定',
                'description' => 'ログイン方法とセキュリティオプションを設定します。',
                'method' => 'ログイン方法',
                'method_desc' => '会員ログイン時に使用するID種類を選択します。',
                'method_email' => 'メールアドレス',
                'method_phone' => '電話番号',
                'method_both' => 'メールまたは電話番号',
                'remember_me' => 'ログイン状態を維持',
                'remember_me_desc' => 'ログイン状態維持のチェックボックスを表示します。',
                'attempts' => 'ログイン試行制限',
                'attempts_desc' => 'アカウントロックまでに許可される失敗回数',
                'unlimited' => '無制限',
                'times' => '回',
                'lockout' => 'アカウントロック時間',
                'lockout_desc' => 'ログイン失敗後のアカウントロック時間',
                'minutes' => '分',
                'hour' => '時間',
                'hours' => '時間',
                'seconds' => '秒',
                'brute_force' => 'ブルートフォース対策',
                'brute_force_desc' => '短時間内に1つのIPアドレスから試行できるログイン回数を制限します。',
                'single_device' => '他デバイスをログアウト',
                'single_device_desc' => '一度に1つのデバイスからのみログインできるようにします。',
                'login_redirect_url' => 'ログイン後のリダイレクトURL',
                'login_redirect_url_desc' => 'ログイン後のリダイレクト先URLを設定できます。空欄の場合、ログイン前のページに戻ります。',
                'logout_redirect_url' => 'ログアウト後のリダイレクトURL',
                'logout_redirect_url_desc' => 'ログアウト後のリダイレクト先URLを設定できます。空欄の場合、ログアウト前のページに戻ります。',
                'redirect_url_placeholder' => '例：/mypage または https://example.com',
            ],

            // デザイン設定
            'design' => [
                'title' => 'デザイン設定',
                'description' => '会員関連ページのデザインを設定します。',
                'mypage_layout' => 'マイページレイアウト',
                'layout_width' => '全体幅',
                'layout_left_width' => '左側幅',
                'layout_content_width' => 'コンテンツ幅',
                'layout_right_width' => '右側幅',
                'layout_style' => 'レイアウトスタイル',
                'layout' => 'レイアウト',
                'layout_desc' => '会員ページに適用するレイアウトを選択します。',
                'layout_none' => '未使用',
                'layout_basic' => '基本レイアウト',
                'layout_sidebar_type' => 'サイドバーレイアウト',
                'no_layouts_found' => 'layoutsフォルダにレイアウトがありません。レイアウトフォルダを追加してください。',
                'skin' => 'スキン',
                'skin_desc' => '会員ページに適用するスキンを選択します。',
                'skin_default' => '会員基本スキン (default)',
                'skin_modern' => 'モダンスキン',
                'skin_classic' => 'クラシックスキン',
                'colorset' => 'カラーセット',
                'colorset_desc' => 'スキンに適用するカラーテーマを選択します。',
                'colorset_default' => '基本',
                'colorset_blue' => 'ブルー',
                'colorset_green' => 'グリーン',
                'colorset_purple' => 'パープル',
                'mobile_layout' => 'モバイルレイアウト',
                'mobile_layout_desc' => 'モバイルで使用するレイアウトを選択します。',
                'mobile_responsive' => 'PCと同じレスポンシブレイアウトを使用',
                'mobile_skin' => 'モバイルスキン',
                'mobile_skin_desc' => 'モバイルで使用するスキンを選択します。',
                'mobile_skin_responsive' => 'PCと同じレスポンシブスキンを使用',
                'mobile_skin_mobile' => 'モバイル専用スキン',
                'form_style' => 'フォームスタイル',
                'form_style_desc' => 'ログイン/会員登録フォームのスタイルを選択します。',
                'style_default' => 'デフォルト',
                'style_card' => 'カード型',
                'style_minimal' => 'ミニマル',
                'login_background' => 'ログインページ背景',
                'login_background_desc' => 'ログインページの背景スタイル',
                'bg_none' => 'なし（デフォルト）',
                'bg_gradient' => 'グラデーション',
                'bg_image' => '画像',
                'bg_pattern' => 'パターン',
                'register_layout' => '会員登録ページレイアウト',
                'register_layout_desc' => '会員登録フォームのレイアウト形式',
                'layout_single' => '単一ページ',
                'layout_steps' => 'ステップ式',
                'layout_split' => '左右分割',
                'social_login' => 'ソーシャルログイン機能',
                'social_login_desc' => 'ログイン/会員登録ページにソーシャルログインボタンを表示します。',
                'social_google' => 'Googleログイン',
                'social_line' => 'LINEログイン',
                'social_kakao' => 'カカオトークログイン',
                'profile_style' => 'マイページスタイル',
                'profile_style_desc' => '会員マイページのレイアウトスタイル',
                'profile_card' => 'カード型',
                'profile_sidebar' => 'サイドバー型',
                'profile_tabs' => 'タブ型',

                // スキン追加
                'add_skin' => '新規スキン追加',
                'direct_register' => '直接登録',
                'direct_register_desc' => 'スキンファイルを直接アップロードします',
                'marketplace' => 'マーケットプレイスから購入',
                'marketplace_desc' => '様々なスキンを探す',
            ],
        ],

        // 会員グループ
        'groups' => [
            'title' => '会員グループ',
            'list' => 'グループ一覧',
            'create' => 'グループ追加',
            'edit' => 'グループ編集',
            'fields' => [
                'name' => 'グループ名',
                'description' => '説明',
                'discount_rate' => '割引率（%）',
                'point_rate' => 'ポイント率（%）',
                'is_default' => 'デフォルトグループ',
            ],
        ],

        // ポイント
        'points' => [
            'title' => 'ポイント管理',
            'history' => 'ポイント履歴',
            'add' => 'ポイント付与',
            'deduct' => 'ポイント控除',
            'fields' => [
                'member' => '会員',
                'amount' => '金額',
                'reason' => '理由',
                'balance' => '残高',
            ],
        ],
    ],
];
