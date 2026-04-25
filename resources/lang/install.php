<?php
/**
 * VosCMS 설치 마법사 다국어 번역
 * 13개 언어 지원
 */
return [
    'languages' => [
        'ko' => '한국어',
        'en' => 'English',
        'ja' => '日本語',
        'zh_CN' => '中文(简体)',
        'zh_TW' => '中文(繁體)',
        'de' => 'Deutsch',
        'es' => 'Español',
        'fr' => 'Français',
        'id' => 'Bahasa Indonesia',
        'mn' => 'Монгол',
        'ru' => 'Русский',
        'tr' => 'Türkçe',
        'vi' => 'Tiếng Việt',
    ],
    'translations' => [
        // ── 한국어 ──
        'ko' => [
            'welcome_title' => '설치 언어를 선택하세요',
            'welcome_desc' => '선택한 언어로 설치가 진행됩니다.',
            'start' => '설치 시작',
            'next' => '다음',
            'step1' => '환경 체크',
            'step2' => '데이터베이스 설정',
            'step3' => '테이블 생성',
            'step4' => '사이트 설정',
            'step5' => '설치 완료',
            'host' => '호스트',
            'port' => '포트',
            'db_name' => '데이터베이스 이름',
            'db_user' => '사용자명',
            'db_pass' => '비밀번호',
            'db_prefix' => '테이블 접두사',
            'db_name_hint' => '존재하지 않으면 자동 생성됩니다.',
            'db_required' => 'DB 이름과 사용자명은 필수입니다.',
            'db_fail' => 'DB 연결 실패',
            'connect_next' => '연결 테스트 + 다음',
            'db_success' => 'DB 연결 성공!',
            'migration_info' => '마이그레이션 파일 %d개를 실행합니다.',
            'create_tables' => '테이블 생성',
            'table_fail' => '테이블 생성 실패',
            'site_info' => '사이트 정보',
            'site_name' => '사이트 이름',
            'site_url' => '사이트 URL',
            'admin_path' => '관리자 경로',
            'admin_path_hint' => '기본값 <code>admin</code>은 자동화 공격 봇이 가장 먼저 탐색하는 경로입니다. 변경을 권장합니다.',
            'admin_path_why_title' => '왜 변경해야 하나요?',
            'admin_path_why_body' => '<strong>1. 자동 해킹 시도 차단</strong><br>인터넷에는 전 세계 웹사이트를 24시간 돌아다니며 관리자 페이지를 찾아 자동으로 비밀번호를 하나씩 대입해보는 프로그램(봇)이 매우 많습니다. 이 봇들이 제일 먼저 시도하는 주소가 <code>/admin</code>입니다. 관리자 경로를 다른 이름으로 바꾸면, 봇이 관리자 페이지 주소 자체를 찾지 못해서 이런 시도가 사이트에 도달하지 않습니다.<br><br><strong>2. 취약점 탐색 방지</strong><br>알려진 CMS 경로를 자동으로 찾아다니며 보안 허점을 탐색하는 프로그램도 있습니다. 예측하기 어려운 경로를 사용하면 이러한 탐색의 성공 가능성이 크게 낮아집니다.<br><br><strong>3. 강한 비밀번호만으로는 부족합니다</strong><br>아무리 비밀번호를 복잡하게 만들어도, 해킹 프로그램이 로그인 페이지 주소를 알면 계속 시도할 수 있습니다. 로그인 페이지 주소 자체를 숨기는 것이 더 근본적인 예방책입니다.<br><br><strong>권장:</strong> 추측하기 어려운 영문+숫자 조합을 사용하세요. (예: <code>mysite-cms2026</code>)',
            'language' => '기본 언어',
            'timezone_label' => '시간대',
            'admin_account' => '관리자 계정',
            'admin_name' => '이름',
            'email' => '이메일',
            'password' => '비밀번호',
            'pw_hint' => '8자 이상',
            'admin_required' => '관리자 이메일과 비밀번호는 필수입니다.',
            'pw_length' => '비밀번호는 8자 이상이어야 합니다.',
            'finish_install' => '설치 완료',
            'complete_title' => '설치 완료!',
            'complete_desc' => 'VosCMS가 성공적으로 설치되었습니다.',
            'license_info' => '라이선스 정보',
            'license_key' => '라이선스 키',
            'license_ok' => '라이선스 서버에 등록 완료',
            'license_fail_msg' => '라이선스 서버 연결 불가 — 다음 관리자 접속 시 자동 등록됩니다.',
            'license_env' => '이 키는 .env 파일에 저장되었습니다. 안전하게 보관하세요.',
            'admin_page' => '관리자 페이지',
            'go_admin' => '관리자로 이동',
            'env_fail' => '환경 요구사항을 충족하지 않습니다. 서버 설정을 확인하세요.',
            'save_fail' => '설정 저장 실패',
            'storage_perm' => 'storage/ 쓰기 권한',
            'env_perm' => '.env 쓰기 권한',
            'ic_warning_html' => <<<'HTML'
<p class="font-semibold mb-1">ionCube Loader가 필요합니다</p>
<p class="mb-2">VosCMS 코어 보안 모듈이 ionCube로 인코딩되어 있어, ionCube Loader 확장이 PHP에 설치되어 있어야 동작합니다.</p>
<ol class="list-decimal pl-5 space-y-1 text-xs">
<li><a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noopener" class="text-blue-600 underline">ionCube Loader 다운로드</a> (PHP %1$s · %2$s · %3$s)</li>
<li><code class="bg-amber-100 px-1 rounded">php.ini</code> 에 <code class="bg-amber-100 px-1 rounded">zend_extension = /path/to/ioncube_loader_lin_%4$s.so</code> 추가</li>
<li>웹 서버 (php-fpm / apache) 재시작</li>
<li>이 페이지 새로고침 → 설치 마법사 자동 통과</li>
</ol>
<p class="mt-2 text-xs text-amber-700">ⓘ 호스팅 환경이라면 호스팅사에 ionCube Loader 활성화를 요청하세요. 대부분 공유 호스팅에서 무료로 제공됩니다.</p>
<div class="mt-3 pt-3 border-t border-amber-200">
<p class="text-xs text-amber-800">ⓘ 테스트를 원하시면 <a href="https://voscms.com/service/order" target="_blank" rel="noopener" class="font-semibold text-blue-600 hover:text-blue-700 underline">VosCMS 무료 웹호스팅</a> 서비스를 이용해보세요.</p>
</div>
HTML,
            'backup_title' => '⚠️ 중요 안내 — 데이터 백업',
            'backup_body' => 'VosCMS는 개인정보(이메일·이름 등)를 <code>APP_KEY</code>로 암호화하여 저장합니다.<br><br><strong>설치 직후 반드시 아래 파일을 안전한 곳에 백업하세요:</strong>',
            'backup_item_env' => '<code>.env</code> 파일 (APP_KEY 포함 — 복호화 키)',
            'backup_item_db' => '데이터베이스 정기 덤프',
            'backup_item_uploads' => '<code>/storage/uploads/</code> 업로드 파일',
            'backup_warn' => '⚠️ APP_KEY를 잃으면 암호화된 모든 개인정보를 <strong>영구히 복호화할 수 없습니다.</strong>',
            'backup_check' => '위 내용을 이해했으며, <code>.env</code> 파일을 별도 백업하겠습니다.',
            'backup_confirm' => '확인 후 설치 시작',
            'backup_cancel' => '취소',
        ],

        // ── English ──
        'en' => [
            'welcome_title' => 'Select Installation Language',
            'welcome_desc' => 'The installation will proceed in your selected language.',
            'start' => 'Start Installation',
            'next' => 'Next',
            'step1' => 'Environment Check',
            'step2' => 'Database Setup',
            'step3' => 'Create Tables',
            'step4' => 'Site Settings',
            'step5' => 'Installation Complete',
            'host' => 'Host',
            'port' => 'Port',
            'db_name' => 'Database Name',
            'db_user' => 'Username',
            'db_pass' => 'Password',
            'db_prefix' => 'Table Prefix',
            'db_name_hint' => 'Will be created automatically if it doesn\'t exist.',
            'db_required' => 'Database name and username are required.',
            'db_fail' => 'Database connection failed',
            'connect_next' => 'Test Connection + Next',
            'db_success' => 'Database Connected!',
            'migration_info' => 'Executing %d migration files.',
            'create_tables' => 'Create Tables',
            'table_fail' => 'Table creation failed',
            'site_info' => 'Site Information',
            'site_name' => 'Site Name',
            'site_url' => 'Site URL',
            'admin_path' => 'Admin Path',
            'admin_path_hint' => 'The default <code>admin</code> path is the first target for automated attack bots. We recommend changing it.',
            'admin_path_why_title' => 'Why should I change this?',
            'admin_path_why_body' => '<strong>1. Stop automated hacking attempts</strong><br>There are countless programs (bots) on the internet that roam websites 24/7, find admin pages, and automatically try passwords one by one. The very first address these bots try is <code>/admin</code>. If you change the admin path to something else, bots simply cannot find your login page — so these attempts never even reach your site.<br><br><strong>2. Prevent vulnerability scanning</strong><br>Other automated programs scan websites looking for security flaws in known CMS paths. Using an unpredictable path greatly reduces the chance of being targeted.<br><br><strong>3. A strong password alone is not enough</strong><br>Even with a complex password, if a hacking program knows the login page address, it will keep trying. Hiding the login page address itself is a more fundamental prevention.<br><br><strong>Recommended:</strong> Use a hard-to-guess alphanumeric combination. (e.g., <code>mysite-cms2026</code>)',
            'language' => 'Default Language',
            'timezone_label' => 'Timezone',
            'admin_account' => 'Administrator Account',
            'admin_name' => 'Name',
            'email' => 'Email',
            'password' => 'Password',
            'pw_hint' => '8+ characters',
            'admin_required' => 'Admin email and password are required.',
            'pw_length' => 'Password must be at least 8 characters.',
            'finish_install' => 'Complete Installation',
            'complete_title' => 'Installation Complete!',
            'complete_desc' => 'VosCMS has been successfully installed.',
            'license_info' => 'License Information',
            'license_key' => 'License Key',
            'license_ok' => 'Registered with license server',
            'license_fail_msg' => 'Could not connect to license server — will register automatically on next admin login.',
            'license_env' => 'This key has been saved to the .env file. Keep it safe.',
            'admin_page' => 'Admin Page',
            'go_admin' => 'Go to Admin',
            'env_fail' => 'Server requirements not met. Please check your server configuration.',
            'save_fail' => 'Failed to save settings',
            'storage_perm' => 'storage/ write permission',
            'env_perm' => '.env write permission',
            'ic_warning_html' => <<<'HTML'
<p class="font-semibold mb-1">ionCube Loader Required</p>
<p class="mb-2">VosCMS core security modules are encoded with ionCube. The ionCube Loader extension must be installed in PHP.</p>
<ol class="list-decimal pl-5 space-y-1 text-xs">
<li><a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noopener" class="text-blue-600 underline">Download ionCube Loader</a> (PHP %1$s · %2$s · %3$s)</li>
<li>Add <code class="bg-amber-100 px-1 rounded">zend_extension = /path/to/ioncube_loader_lin_%4$s.so</code> to <code class="bg-amber-100 px-1 rounded">php.ini</code></li>
<li>Restart your web server (php-fpm / apache)</li>
<li>Refresh this page — installer should pass automatically</li>
</ol>
<p class="mt-2 text-xs text-amber-700">ⓘ On shared/managed hosting, ask your provider to enable ionCube Loader. Most providers offer this for free.</p>
<div class="mt-3 pt-3 border-t border-amber-200">
<p class="text-xs text-amber-800">ⓘ For testing, try the <a href="https://voscms.com/service/order" target="_blank" rel="noopener" class="font-semibold text-blue-600 hover:text-blue-700 underline">VosCMS Free Hosting</a> service.</p>
</div>
HTML,
            'backup_title' => '⚠️ Important Notice — Data Backup',
            'backup_body' => 'VosCMS encrypts personal data (email, names, etc.) using <code>APP_KEY</code>.<br><br><strong>Right after installation, back up the following files to a safe place:</strong>',
            'backup_item_env' => '<code>.env</code> file (contains APP_KEY — decryption key)',
            'backup_item_db' => 'Regular database dumps',
            'backup_item_uploads' => '<code>/storage/uploads/</code> uploaded files',
            'backup_warn' => '⚠️ If APP_KEY is lost, all encrypted personal data is <strong>permanently unrecoverable.</strong>',
            'backup_check' => 'I understand and will back up the <code>.env</code> file separately.',
            'backup_confirm' => 'I Understand, Start Installation',
            'backup_cancel' => 'Cancel',
        ],

        // ── 日本語 ──
        'ja' => [
            'welcome_title' => 'インストール言語を選択してください',
            'welcome_desc' => '選択した言語でインストールが進行します。',
            'start' => 'インストール開始',
            'next' => '次へ',
            'step1' => '環境チェック',
            'step2' => 'データベース設定',
            'step3' => 'テーブル作成',
            'step4' => 'サイト設定',
            'step5' => 'インストール完了',
            'host' => 'ホスト',
            'port' => 'ポート',
            'db_name' => 'データベース名',
            'db_user' => 'ユーザー名',
            'db_pass' => 'パスワード',
            'db_prefix' => 'テーブル接頭辞',
            'db_name_hint' => '存在しない場合は自動作成されます。',
            'db_required' => 'DB名とユーザー名は必須です。',
            'db_fail' => 'DB接続失敗',
            'connect_next' => '接続テスト + 次へ',
            'db_success' => 'DB接続成功！',
            'migration_info' => 'マイグレーションファイル%d件を実行します。',
            'create_tables' => 'テーブル作成',
            'table_fail' => 'テーブル作成失敗',
            'site_info' => 'サイト情報',
            'site_name' => 'サイト名',
            'site_url' => 'サイトURL',
            'admin_path' => '管理者パス',
            'admin_path_hint' => 'デフォルトの <code>admin</code> は自動攻撃ボットが最初に狙うパスです。変更を推奨します。',
            'admin_path_why_title' => 'なぜ変更すべきですか？',
            'admin_path_why_body' => '<strong>1. 自動ハッキングの試みを遮断</strong><br>インターネット上には、世界中のサイトを24時間巡回して管理者ページを探し、パスワードを自動で一つずつ試すプログラム（ボット）が大量に存在します。これらのボットが最初に試すアドレスが <code>/admin</code> です。管理者パスを別の名前に変えると、ボットはログインページのアドレスを見つけられないため、こうした試みがサイトに到達しなくなります。<br><br><strong>2. 脆弱性スキャンを防止</strong><br>既知のCMSパスを自動で探してセキュリティの穴を見つけようとするプログラムもあります。予測しにくいパスを使うことで、狙われる可能性を大幅に下げられます。<br><br><strong>3. 強いパスワードだけでは不十分</strong><br>パスワードをどれだけ複雑にしても、ハッキングプログラムがログインページのアドレスを知っていれば試し続けます。ログインページのアドレス自体を隠す方が、より根本的な対策になります。<br><br><strong>推奨：</strong>推測しにくい英数字の組み合わせを使用してください。（例：<code>mysite-cms2026</code>）',
            'language' => 'デフォルト言語',
            'timezone_label' => 'タイムゾーン',
            'admin_account' => '管理者アカウント',
            'admin_name' => '名前',
            'email' => 'メール',
            'password' => 'パスワード',
            'pw_hint' => '8文字以上',
            'admin_required' => '管理者メールとパスワードは必須です。',
            'pw_length' => 'パスワードは8文字以上必要です。',
            'finish_install' => 'インストール完了',
            'complete_title' => 'インストール完了！',
            'complete_desc' => 'VosCMSが正常にインストールされました。',
            'license_info' => 'ライセンス情報',
            'license_key' => 'ライセンスキー',
            'license_ok' => 'ライセンスサーバーに登録完了',
            'license_fail_msg' => 'ライセンスサーバーに接続できません — 次回管理者ログイン時に自動登録されます。',
            'license_env' => 'このキーは.envファイルに保存されています。大切に保管してください。',
            'admin_page' => '管理ページ',
            'go_admin' => '管理画面へ',
            'env_fail' => 'サーバー要件を満たしていません。サーバー設定を確認してください。',
            'save_fail' => '設定の保存に失敗',
            'storage_perm' => 'storage/ 書き込み権限',
            'env_perm' => '.env 書き込み権限',
            'ic_warning_html' => <<<'HTML'
<p class="font-semibold mb-1">ionCube Loader が必要です</p>
<p class="mb-2">VosCMS のコアセキュリティモジュールは ionCube でエンコードされており、PHP に ionCube Loader 拡張が必要です。</p>
<ol class="list-decimal pl-5 space-y-1 text-xs">
<li><a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noopener" class="text-blue-600 underline">ionCube Loader をダウンロード</a>(PHP %1$s · %2$s · %3$s)</li>
<li><code class="bg-amber-100 px-1 rounded">php.ini</code> に <code class="bg-amber-100 px-1 rounded">zend_extension = /path/to/ioncube_loader_lin_%4$s.so</code> を追加</li>
<li>Webサーバ(php-fpm / apache)を再起動</li>
<li>このページを再読み込み → インストールウィザード自動通過</li>
</ol>
<p class="mt-2 text-xs text-amber-700">ⓘ 共有・マネージドホスティングの場合、事業者に ionCube Loader 有効化を依頼してください。多くは無料で対応します。</p>
<div class="mt-3 pt-3 border-t border-amber-200">
<p class="text-xs text-amber-800">ⓘ テストするなら <a href="https://voscms.com/service/order" target="_blank" rel="noopener" class="font-semibold text-blue-600 hover:text-blue-700 underline">VosCMS 無料ホスティング</a>サービスをお試しください。</p>
</div>
HTML,
            'backup_title' => '⚠️ 重要なお知らせ — データバックアップ',
            'backup_body' => 'VosCMSは個人情報（メール・氏名など）を<code>APP_KEY</code>で暗号化して保存します。<br><br><strong>インストール後、必ず以下のファイルを安全な場所にバックアップしてください：</strong>',
            'backup_item_env' => '<code>.env</code>ファイル（APP_KEY含む — 復号キー）',
            'backup_item_db' => 'データベースの定期バックアップ',
            'backup_item_uploads' => '<code>/storage/uploads/</code>アップロードファイル',
            'backup_warn' => '⚠️ APP_KEYを失うと、暗号化された全ての個人情報を<strong>永久に復号できなくなります。</strong>',
            'backup_check' => '上記を理解し、<code>.env</code>ファイルを別途バックアップします。',
            'backup_confirm' => '確認して設置開始',
            'backup_cancel' => 'キャンセル',
        ],

        // ── 中文(简体) ──
        'zh_CN' => [
            'welcome_title' => '选择安装语言',
            'welcome_desc' => '安装将以您选择的语言进行。',
            'start' => '开始安装',
            'next' => '下一步',
            'step1' => '环境检查',
            'step2' => '数据库设置',
            'step3' => '创建数据表',
            'step4' => '站点设置',
            'step5' => '安装完成',
            'host' => '主机',
            'port' => '端口',
            'db_name' => '数据库名',
            'db_user' => '用户名',
            'db_pass' => '密码',
            'db_prefix' => '表前缀',
            'db_name_hint' => '如果不存在将自动创建。',
            'db_required' => '数据库名和用户名为必填项。',
            'db_fail' => '数据库连接失败',
            'connect_next' => '测试连接 + 下一步',
            'db_success' => '数据库连接成功！',
            'migration_info' => '正在执行 %d 个迁移文件。',
            'create_tables' => '创建数据表',
            'table_fail' => '数据表创建失败',
            'site_info' => '站点信息',
            'site_name' => '站点名称',
            'site_url' => '站点 URL',
            'admin_path' => '管理路径',
            'admin_path_hint' => '默认的 <code>admin</code> 路径是自动攻击机器人首先探测的目标，建议更改。',
            'admin_path_why_title' => '为什么需要更改？',
            'admin_path_why_body' => '<strong>1. 阻止自动黑客攻击</strong><br>网络上有大量程序（机器人）24小时游荡于各网站，寻找管理员页面并自动逐一尝试密码。这些机器人首先尝试的地址就是 <code>/admin</code>。将管理路径改为其他名称后，机器人根本找不到登录页面，攻击就无法到达您的网站。<br><br><strong>2. 防止漏洞扫描</strong><br>还有一些自动程序会扫描网站，寻找已知CMS路径中的安全漏洞。使用难以预测的路径可大幅降低被针对的概率。<br><br><strong>3. 仅凭强密码还不够</strong><br>即使密码再复杂，只要黑客程序知道登录页面地址，就会不断尝试。隐藏登录页面地址本身才是更根本的预防措施。<br><br><strong>建议：</strong>使用难以猜测的字母数字组合。（例如：<code>mysite-cms2026</code>）',
            'language' => '默认语言',
            'timezone_label' => '时区',
            'admin_account' => '管理员账户',
            'admin_name' => '姓名',
            'email' => '邮箱',
            'password' => '密码',
            'pw_hint' => '8位以上',
            'admin_required' => '管理员邮箱和密码为必填项。',
            'pw_length' => '密码至少需要8个字符。',
            'finish_install' => '完成安装',
            'complete_title' => '安装完成！',
            'complete_desc' => 'VosCMS 已成功安装。',
            'license_info' => '许可证信息',
            'license_key' => '许可证密钥',
            'license_ok' => '已在许可证服务器注册',
            'license_fail_msg' => '无法连接许可证服务器 — 下次管理员登录时将自动注册。',
            'license_env' => '此密钥已保存在 .env 文件中，请妥善保管。',
            'admin_page' => '管理页面',
            'go_admin' => '进入管理',
            'env_fail' => '未满足服务器要求，请检查服务器配置。',
            'save_fail' => '设置保存失败',
            'storage_perm' => 'storage/ 写入权限',
            'env_perm' => '.env 写入权限',
            'ic_warning_html' => <<<'HTML'
<p class="font-semibold mb-1">需要 ionCube Loader</p>
<p class="mb-2">VosCMS 核心安全模块使用 ionCube 编码，需要在 PHP 中安装 ionCube Loader 扩展。</p>
<ol class="list-decimal pl-5 space-y-1 text-xs">
<li><a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noopener" class="text-blue-600 underline">下载 ionCube Loader</a>（PHP %1$s · %2$s · %3$s）</li>
<li>在 <code class="bg-amber-100 px-1 rounded">php.ini</code> 中添加 <code class="bg-amber-100 px-1 rounded">zend_extension = /path/to/ioncube_loader_lin_%4$s.so</code></li>
<li>重启 Web 服务器（php-fpm / apache）</li>
<li>刷新此页面 — 安装向导将自动通过</li>
</ol>
<p class="mt-2 text-xs text-amber-700">ⓘ 如果使用共享/托管主机，请要求服务商启用 ionCube Loader。大多数免费提供。</p>
<div class="mt-3 pt-3 border-t border-amber-200">
<p class="text-xs text-amber-800">ⓘ 如需测试，请尝试 <a href="https://voscms.com/service/order" target="_blank" rel="noopener" class="font-semibold text-blue-600 hover:text-blue-700 underline">VosCMS 免费虚拟主机</a> 服务。</p>
</div>
HTML,
            'backup_title' => '⚠️ 重要提示 — 数据备份',
            'backup_body' => 'VosCMS 使用 <code>APP_KEY</code> 加密存储个人信息（邮箱、姓名等）。<br><br><strong>安装后请立即将以下文件备份至安全位置：</strong>',
            'backup_item_env' => '<code>.env</code> 文件（包含 APP_KEY — 解密密钥）',
            'backup_item_db' => '定期数据库备份',
            'backup_item_uploads' => '<code>/storage/uploads/</code> 上传文件',
            'backup_warn' => '⚠️ 丢失 APP_KEY 后，所有加密的个人信息将<strong>永久无法恢复。</strong>',
            'backup_check' => '我已理解，并将单独备份 <code>.env</code> 文件。',
            'backup_confirm' => '确认并开始安装',
            'backup_cancel' => '取消',
        ],

        // ── 中文(繁體) ──
        'zh_TW' => [
            'welcome_title' => '選擇安裝語言',
            'welcome_desc' => '安裝將以您選擇的語言進行。',
            'start' => '開始安裝',
            'next' => '下一步',
            'step1' => '環境檢查',
            'step2' => '資料庫設定',
            'step3' => '建立資料表',
            'step4' => '站台設定',
            'step5' => '安裝完成',
            'host' => '主機',
            'port' => '連接埠',
            'db_name' => '資料庫名稱',
            'db_user' => '使用者名稱',
            'db_pass' => '密碼',
            'db_prefix' => '資料表前綴',
            'db_name_hint' => '如果不存在將自動建立。',
            'db_required' => '資料庫名稱和使用者名稱為必填。',
            'db_fail' => '資料庫連線失敗',
            'connect_next' => '測試連線 + 下一步',
            'db_success' => '資料庫連線成功！',
            'migration_info' => '正在執行 %d 個遷移檔案。',
            'create_tables' => '建立資料表',
            'table_fail' => '資料表建立失敗',
            'site_info' => '站台資訊',
            'site_name' => '站台名稱',
            'site_url' => '站台 URL',
            'admin_path' => '管理路徑',
            'admin_path_hint' => '預設的 <code>admin</code> 路徑是自動攻擊機器人首先探測的目標，建議更改。',
            'admin_path_why_title' => '為什麼需要更改？',
            'admin_path_why_body' => '<strong>1. 阻止自動駭客攻擊</strong><br>網路上有大量程式（機器人）24小時遊走於各網站，尋找管理員頁面並自動逐一嘗試密碼。這些機器人首先嘗試的網址就是 <code>/admin</code>。將管理路徑改為其他名稱後，機器人根本找不到登入頁面，攻擊便無法到達您的網站。<br><br><strong>2. 防止漏洞掃描</strong><br>還有一些自動程式會掃描網站，尋找已知CMS路徑中的安全漏洞。使用難以預測的路徑可大幅降低被針對的機率。<br><br><strong>3. 僅憑強密碼還不夠</strong><br>即使密碼再複雜，只要駭客程式知道登入頁面網址，就會不斷嘗試。隱藏登入頁面網址本身才是更根本的預防措施。<br><br><strong>建議：</strong>使用難以猜測的字母數字組合。（例如：<code>mysite-cms2026</code>）',
            'language' => '預設語言',
            'timezone_label' => '時區',
            'admin_account' => '管理員帳號',
            'admin_name' => '姓名',
            'email' => '電子信箱',
            'password' => '密碼',
            'pw_hint' => '8位以上',
            'admin_required' => '管理員電子信箱和密碼為必填。',
            'pw_length' => '密碼至少需要8個字元。',
            'finish_install' => '完成安裝',
            'complete_title' => '安裝完成！',
            'complete_desc' => 'VosCMS 已成功安裝。',
            'license_info' => '授權資訊',
            'license_key' => '授權金鑰',
            'license_ok' => '已在授權伺服器註冊',
            'license_fail_msg' => '無法連線授權伺服器 — 下次管理員登入時將自動註冊。',
            'license_env' => '此金鑰已儲存在 .env 檔案中，請妥善保管。',
            'admin_page' => '管理頁面',
            'go_admin' => '進入管理',
            'env_fail' => '未滿足伺服器要求，請檢查伺服器配置。',
            'save_fail' => '設定儲存失敗',
            'storage_perm' => 'storage/ 寫入權限',
            'env_perm' => '.env 寫入權限',
            'ic_warning_html' => <<<'HTML'
<p class="font-semibold mb-1">需要 ionCube Loader</p>
<p class="mb-2">VosCMS 核心安全模組使用 ionCube 編碼，需要在 PHP 中安裝 ionCube Loader 擴充。</p>
<ol class="list-decimal pl-5 space-y-1 text-xs">
<li><a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noopener" class="text-blue-600 underline">下載 ionCube Loader</a>（PHP %1$s · %2$s · %3$s）</li>
<li>在 <code class="bg-amber-100 px-1 rounded">php.ini</code> 中加入 <code class="bg-amber-100 px-1 rounded">zend_extension = /path/to/ioncube_loader_lin_%4$s.so</code></li>
<li>重新啟動 Web 伺服器（php-fpm / apache）</li>
<li>重新整理此頁面 — 安裝精靈將自動通過</li>
</ol>
<p class="mt-2 text-xs text-amber-700">ⓘ 若使用共享/託管主機，請要求服務商啟用 ionCube Loader。大多數免費提供。</p>
<div class="mt-3 pt-3 border-t border-amber-200">
<p class="text-xs text-amber-800">ⓘ 如需測試，請嘗試 <a href="https://voscms.com/service/order" target="_blank" rel="noopener" class="font-semibold text-blue-600 hover:text-blue-700 underline">VosCMS 免費虛擬主機</a> 服務。</p>
</div>
HTML,
            'backup_title' => '⚠️ 重要提示 — 資料備份',
            'backup_body' => 'VosCMS 使用 <code>APP_KEY</code> 加密儲存個人資料（信箱、姓名等）。<br><br><strong>安裝後請立即將以下檔案備份至安全位置：</strong>',
            'backup_item_env' => '<code>.env</code> 檔案（含 APP_KEY — 解密金鑰）',
            'backup_item_db' => '定期資料庫備份',
            'backup_item_uploads' => '<code>/storage/uploads/</code> 上傳檔案',
            'backup_warn' => '⚠️ 遺失 APP_KEY 後，所有加密的個人資料將<strong>永久無法還原。</strong>',
            'backup_check' => '我已理解，並將單獨備份 <code>.env</code> 檔案。',
            'backup_confirm' => '確認並開始安裝',
            'backup_cancel' => '取消',
        ],

        // ── Deutsch ──
        'de' => [
            'welcome_title' => 'Installationssprache auswählen',
            'welcome_desc' => 'Die Installation wird in der gewählten Sprache durchgeführt.',
            'start' => 'Installation starten',
            'next' => 'Weiter',
            'step1' => 'Umgebungsprüfung',
            'step2' => 'Datenbankeinstellungen',
            'step3' => 'Tabellen erstellen',
            'step4' => 'Website-Einstellungen',
            'step5' => 'Installation abgeschlossen',
            'host' => 'Host',
            'port' => 'Port',
            'db_name' => 'Datenbankname',
            'db_user' => 'Benutzername',
            'db_pass' => 'Passwort',
            'db_prefix' => 'Tabellenpräfix',
            'db_name_hint' => 'Wird automatisch erstellt, falls nicht vorhanden.',
            'db_required' => 'Datenbankname und Benutzername sind erforderlich.',
            'db_fail' => 'Datenbankverbindung fehlgeschlagen',
            'connect_next' => 'Verbindung testen + Weiter',
            'db_success' => 'Datenbankverbindung erfolgreich!',
            'migration_info' => '%d Migrationsdateien werden ausgeführt.',
            'create_tables' => 'Tabellen erstellen',
            'table_fail' => 'Tabellenerstellung fehlgeschlagen',
            'site_info' => 'Website-Informationen',
            'site_name' => 'Website-Name',
            'site_url' => 'Website-URL',
            'admin_path' => 'Admin-Pfad',
            'admin_path_hint' => 'Der Standard-Pfad <code>admin</code> ist das erste Ziel automatisierter Angriffsbots. Eine Änderung wird empfohlen.',
            'admin_path_why_title' => 'Warum sollte ich das ändern?',
            'admin_path_why_body' => '<strong>1. Automatische Hackangriffe stoppen</strong><br>Im Internet gibt es unzählige Programme (Bots), die rund um die Uhr Websites durchsuchen, Admin-Seiten finden und automatisch Passwörter der Reihe nach ausprobieren. Die allererste Adresse, die diese Bots testen, ist <code>/admin</code>. Wenn Sie den Admin-Pfad umbenennen, können Bots Ihre Login-Seite gar nicht erst finden — und diese Angriffe erreichen Ihre Website erst gar nicht.<br><br><strong>2. Schwachstellen-Scans verhindern</strong><br>Andere automatische Programme durchsuchen bekannte CMS-Pfade nach Sicherheitslücken. Ein unvorhersehbarer Pfad verringert die Wahrscheinlichkeit, ins Visier zu geraten, erheblich.<br><br><strong>3. Ein starkes Passwort allein reicht nicht</strong><br>Selbst mit einem komplexen Passwort kann ein Hackprogramm weiter angreifen, solange es die Adresse der Login-Seite kennt. Die Login-Adresse selbst zu verstecken ist die grundlegendere Schutzmaßnahme.<br><br><strong>Empfehlung:</strong> Verwenden Sie eine schwer zu erratende Kombination aus Buchstaben und Zahlen. (z. B. <code>mysite-cms2026</code>)',
            'language' => 'Standardsprache',
            'timezone_label' => 'Zeitzone',
            'admin_account' => 'Administratorkonto',
            'admin_name' => 'Name',
            'email' => 'E-Mail',
            'password' => 'Passwort',
            'pw_hint' => 'Mindestens 8 Zeichen',
            'admin_required' => 'Admin-E-Mail und Passwort sind erforderlich.',
            'pw_length' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',
            'finish_install' => 'Installation abschließen',
            'complete_title' => 'Installation abgeschlossen!',
            'complete_desc' => 'VosCMS wurde erfolgreich installiert.',
            'license_info' => 'Lizenzinformationen',
            'license_key' => 'Lizenzschlüssel',
            'license_ok' => 'Beim Lizenzserver registriert',
            'license_fail_msg' => 'Lizenzserver nicht erreichbar — wird beim nächsten Admin-Login automatisch registriert.',
            'license_env' => 'Dieser Schlüssel wurde in der .env-Datei gespeichert. Bewahren Sie ihn sicher auf.',
            'admin_page' => 'Admin-Seite',
            'go_admin' => 'Zum Admin',
            'env_fail' => 'Serveranforderungen nicht erfüllt. Bitte Serverkonfiguration prüfen.',
            'save_fail' => 'Einstellungen konnten nicht gespeichert werden',
            'storage_perm' => 'storage/ Schreibberechtigung',
            'env_perm' => '.env Schreibberechtigung',
            'ic_warning_html' => <<<'HTML'
<p class="font-semibold mb-1">ionCube Loader erforderlich</p>
<p class="mb-2">VosCMS-Kern-Sicherheitsmodule sind mit ionCube verschlüsselt. Die ionCube Loader-Erweiterung muss in PHP installiert sein.</p>
<ol class="list-decimal pl-5 space-y-1 text-xs">
<li><a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noopener" class="text-blue-600 underline">ionCube Loader herunterladen</a> (PHP %1$s · %2$s · %3$s)</li>
<li>Fügen Sie <code class="bg-amber-100 px-1 rounded">zend_extension = /path/to/ioncube_loader_lin_%4$s.so</code> in <code class="bg-amber-100 px-1 rounded">php.ini</code> hinzu</li>
<li>Webserver neu starten (php-fpm / apache)</li>
<li>Diese Seite neu laden — Installation läuft automatisch weiter</li>
</ol>
<p class="mt-2 text-xs text-amber-700">ⓘ Bei Shared/Managed-Hosting bitten Sie Ihren Anbieter, ionCube Loader zu aktivieren. Die meisten bieten dies kostenlos an.</p>
<div class="mt-3 pt-3 border-t border-amber-200">
<p class="text-xs text-amber-800">ⓘ Zum Testen, nutzen Sie das <a href="https://voscms.com/service/order" target="_blank" rel="noopener" class="font-semibold text-blue-600 hover:text-blue-700 underline">kostenlose VosCMS-Hosting</a>.</p>
</div>
HTML,
            'backup_title' => '⚠️ Wichtiger Hinweis — Datensicherung',
            'backup_body' => 'VosCMS verschlüsselt persönliche Daten (E-Mail, Namen usw.) mit <code>APP_KEY</code>.<br><br><strong>Sichern Sie sofort nach der Installation folgende Dateien an einem sicheren Ort:</strong>',
            'backup_item_env' => '<code>.env</code>-Datei (enthält APP_KEY — Entschlüsselungsschlüssel)',
            'backup_item_db' => 'Regelmäßige Datenbank-Backups',
            'backup_item_uploads' => '<code>/storage/uploads/</code> hochgeladene Dateien',
            'backup_warn' => '⚠️ Bei Verlust des APP_KEY sind alle verschlüsselten Daten <strong>dauerhaft nicht wiederherstellbar.</strong>',
            'backup_check' => 'Ich verstehe und werde die <code>.env</code>-Datei separat sichern.',
            'backup_confirm' => 'Verstanden, Installation starten',
            'backup_cancel' => 'Abbrechen',
        ],

        // ── Español ──
        'es' => [
            'welcome_title' => 'Seleccionar idioma de instalación',
            'welcome_desc' => 'La instalación se realizará en el idioma seleccionado.',
            'start' => 'Iniciar instalación',
            'next' => 'Siguiente',
            'step1' => 'Verificación del entorno',
            'step2' => 'Configuración de base de datos',
            'step3' => 'Crear tablas',
            'step4' => 'Configuración del sitio',
            'step5' => 'Instalación completada',
            'host' => 'Host',
            'port' => 'Puerto',
            'db_name' => 'Nombre de la base de datos',
            'db_user' => 'Usuario',
            'db_pass' => 'Contraseña',
            'db_prefix' => 'Prefijo de tablas',
            'db_name_hint' => 'Se creará automáticamente si no existe.',
            'db_required' => 'El nombre de la base de datos y el usuario son obligatorios.',
            'db_fail' => 'Error de conexión a la base de datos',
            'connect_next' => 'Probar conexión + Siguiente',
            'db_success' => '¡Conexión exitosa!',
            'migration_info' => 'Ejecutando %d archivos de migración.',
            'create_tables' => 'Crear tablas',
            'table_fail' => 'Error al crear las tablas',
            'site_info' => 'Información del sitio',
            'site_name' => 'Nombre del sitio',
            'site_url' => 'URL del sitio',
            'admin_path' => 'Ruta de administración',
            'admin_path_hint' => 'La ruta predeterminada <code>admin</code> es el primer objetivo de los bots de ataque automatizados. Se recomienda cambiarla.',
            'admin_path_why_title' => '¿Por qué debo cambiarla?',
            'admin_path_why_body' => '<strong>1. Detener intentos de hackeo automatizados</strong><br>En internet hay infinidad de programas (bots) que recorren sitios web las 24 horas buscando páginas de administrador y probando contraseñas automáticamente una a una. La primera dirección que estos bots intentan es <code>/admin</code>. Si cambia la ruta de administración a otro nombre, los bots no podrán encontrar su página de inicio de sesión y estos intentos nunca llegarán a su sitio.<br><br><strong>2. Prevenir escaneos de vulnerabilidades</strong><br>Otros programas automáticos escanean rutas CMS conocidas buscando fallos de seguridad. Usar una ruta impredecible reduce considerablemente la probabilidad de ser atacado.<br><br><strong>3. Una contraseña fuerte sola no es suficiente</strong><br>Aunque su contraseña sea muy compleja, si un programa de hackeo conoce la dirección de la página de inicio de sesión, seguirá intentándolo. Ocultar la propia dirección de inicio de sesión es una prevención más fundamental.<br><br><strong>Recomendación:</strong> Use una combinación alfanumérica difícil de adivinar. (ej. <code>mysite-cms2026</code>)',
            'language' => 'Idioma predeterminado',
            'timezone_label' => 'Zona horaria',
            'admin_account' => 'Cuenta de administrador',
            'admin_name' => 'Nombre',
            'email' => 'Correo electrónico',
            'password' => 'Contraseña',
            'pw_hint' => '8+ caracteres',
            'admin_required' => 'El correo y la contraseña del administrador son obligatorios.',
            'pw_length' => 'La contraseña debe tener al menos 8 caracteres.',
            'finish_install' => 'Completar instalación',
            'complete_title' => '¡Instalación completada!',
            'complete_desc' => 'VosCMS se ha instalado correctamente.',
            'license_info' => 'Información de licencia',
            'license_key' => 'Clave de licencia',
            'license_ok' => 'Registrado en el servidor de licencias',
            'license_fail_msg' => 'No se pudo conectar al servidor de licencias — se registrará automáticamente en el próximo inicio de sesión.',
            'license_env' => 'Esta clave se ha guardado en el archivo .env. Consérvela de forma segura.',
            'admin_page' => 'Página de administración',
            'go_admin' => 'Ir al admin',
            'env_fail' => 'No se cumplen los requisitos del servidor. Verifique la configuración.',
            'save_fail' => 'Error al guardar la configuración',
            'storage_perm' => 'Permiso de escritura en storage/',
            'env_perm' => 'Permiso de escritura en .env',
            'ic_warning_html' => <<<'HTML'
<p class="font-semibold mb-1">Se requiere ionCube Loader</p>
<p class="mb-2">Los módulos de seguridad principales de VosCMS están codificados con ionCube. La extensión ionCube Loader debe estar instalada en PHP.</p>
<ol class="list-decimal pl-5 space-y-1 text-xs">
<li><a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noopener" class="text-blue-600 underline">Descargar ionCube Loader</a> (PHP %1$s · %2$s · %3$s)</li>
<li>Añade <code class="bg-amber-100 px-1 rounded">zend_extension = /path/to/ioncube_loader_lin_%4$s.so</code> a <code class="bg-amber-100 px-1 rounded">php.ini</code></li>
<li>Reinicia el servidor web (php-fpm / apache)</li>
<li>Refresca esta página — el instalador continuará automáticamente</li>
</ol>
<p class="mt-2 text-xs text-amber-700">ⓘ En hosting compartido/administrado, pide al proveedor que active ionCube Loader. La mayoría lo ofrece gratis.</p>
<div class="mt-3 pt-3 border-t border-amber-200">
<p class="text-xs text-amber-800">ⓘ Para probar, prueba el <a href="https://voscms.com/service/order" target="_blank" rel="noopener" class="font-semibold text-blue-600 hover:text-blue-700 underline">hosting gratuito de VosCMS</a>.</p>
</div>
HTML,
            'backup_title' => '⚠️ Aviso Importante — Copia de seguridad',
            'backup_body' => 'VosCMS encripta datos personales (correo, nombres, etc.) con <code>APP_KEY</code>.<br><br><strong>Inmediatamente después de la instalación, respalde los siguientes archivos en un lugar seguro:</strong>',
            'backup_item_env' => 'Archivo <code>.env</code> (contiene APP_KEY — clave de descifrado)',
            'backup_item_db' => 'Respaldos regulares de base de datos',
            'backup_item_uploads' => 'Archivos subidos en <code>/storage/uploads/</code>',
            'backup_warn' => '⚠️ Si se pierde APP_KEY, todos los datos personales encriptados <strong>no se podrán recuperar.</strong>',
            'backup_check' => 'Entiendo y respaldaré el archivo <code>.env</code> por separado.',
            'backup_confirm' => 'Entendido, iniciar instalación',
            'backup_cancel' => 'Cancelar',
        ],

        // ── Français ──
        'fr' => [
            'welcome_title' => 'Sélectionner la langue d\'installation',
            'welcome_desc' => 'L\'installation se déroulera dans la langue sélectionnée.',
            'start' => 'Démarrer l\'installation',
            'next' => 'Suivant',
            'step1' => 'Vérification de l\'environnement',
            'step2' => 'Configuration de la base de données',
            'step3' => 'Créer les tables',
            'step4' => 'Paramètres du site',
            'step5' => 'Installation terminée',
            'host' => 'Hôte',
            'port' => 'Port',
            'db_name' => 'Nom de la base de données',
            'db_user' => 'Nom d\'utilisateur',
            'db_pass' => 'Mot de passe',
            'db_prefix' => 'Préfixe des tables',
            'db_name_hint' => 'Sera créée automatiquement si elle n\'existe pas.',
            'db_required' => 'Le nom de la base de données et le nom d\'utilisateur sont obligatoires.',
            'db_fail' => 'Échec de la connexion à la base de données',
            'connect_next' => 'Tester la connexion + Suivant',
            'db_success' => 'Connexion réussie !',
            'migration_info' => 'Exécution de %d fichiers de migration.',
            'create_tables' => 'Créer les tables',
            'table_fail' => 'Échec de la création des tables',
            'site_info' => 'Informations du site',
            'site_name' => 'Nom du site',
            'site_url' => 'URL du site',
            'admin_path' => 'Chemin d\'administration',
            'admin_path_hint' => 'Le chemin par défaut <code>admin</code> est la première cible des bots d\'attaque automatisés. Il est recommandé de le modifier.',
            'admin_path_why_title' => 'Pourquoi le modifier ?',
            'admin_path_why_body' => '<strong>1. Stopper les tentatives de piratage automatiques</strong><br>Sur internet, d\'innombrables programmes (bots) parcourent les sites web 24h/24, trouvent les pages d\'administration et essaient les mots de passe automatiquement, un par un. La toute première adresse que ces bots tentent est <code>/admin</code>. En changeant le chemin d\'administration, les bots ne peuvent tout simplement pas trouver votre page de connexion, et ces tentatives n\'atteignent jamais votre site.<br><br><strong>2. Prévenir les analyses de vulnérabilités</strong><br>D\'autres programmes automatiques parcourent les chemins CMS connus à la recherche de failles de sécurité. Un chemin imprévisible réduit considérablement les risques d\'être ciblé.<br><br><strong>3. Un mot de passe fort seul ne suffit pas</strong><br>Même avec un mot de passe complexe, un programme de piratage peut continuer à essayer s\'il connaît l\'adresse de la page de connexion. Masquer l\'adresse de connexion elle-même est une prévention plus fondamentale.<br><br><strong>Recommandation :</strong> Utilisez une combinaison alphanumérique difficile à deviner. (ex. <code>mysite-cms2026</code>)',
            'language' => 'Langue par défaut',
            'timezone_label' => 'Fuseau horaire',
            'admin_account' => 'Compte administrateur',
            'admin_name' => 'Nom',
            'email' => 'E-mail',
            'password' => 'Mot de passe',
            'pw_hint' => '8 caractères minimum',
            'admin_required' => 'L\'e-mail et le mot de passe administrateur sont obligatoires.',
            'pw_length' => 'Le mot de passe doit comporter au moins 8 caractères.',
            'finish_install' => 'Terminer l\'installation',
            'complete_title' => 'Installation terminée !',
            'complete_desc' => 'VosCMS a été installé avec succès.',
            'license_info' => 'Informations de licence',
            'license_key' => 'Clé de licence',
            'license_ok' => 'Enregistré sur le serveur de licences',
            'license_fail_msg' => 'Impossible de se connecter au serveur de licences — enregistrement automatique lors de la prochaine connexion.',
            'license_env' => 'Cette clé a été enregistrée dans le fichier .env. Conservez-la en sécurité.',
            'admin_page' => 'Page d\'administration',
            'go_admin' => 'Aller à l\'admin',
            'env_fail' => 'Configuration serveur insuffisante. Veuillez vérifier la configuration.',
            'save_fail' => 'Échec de l\'enregistrement des paramètres',
            'storage_perm' => 'Permission d\'écriture storage/',
            'env_perm' => 'Permission d\'écriture .env',
            'ic_warning_html' => <<<'HTML'
<p class="font-semibold mb-1">ionCube Loader requis</p>
<p class="mb-2">Les modules de sécurité principaux de VosCMS sont encodés avec ionCube. L'extension ionCube Loader doit être installée dans PHP.</p>
<ol class="list-decimal pl-5 space-y-1 text-xs">
<li><a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noopener" class="text-blue-600 underline">Télécharger ionCube Loader</a> (PHP %1$s · %2$s · %3$s)</li>
<li>Ajouter <code class="bg-amber-100 px-1 rounded">zend_extension = /path/to/ioncube_loader_lin_%4$s.so</code> dans <code class="bg-amber-100 px-1 rounded">php.ini</code></li>
<li>Redémarrer le serveur web (php-fpm / apache)</li>
<li>Rafraîchir cette page — l'installateur continuera automatiquement</li>
</ol>
<p class="mt-2 text-xs text-amber-700">ⓘ Sur hébergement mutualisé/géré, demandez à votre fournisseur d'activer ionCube Loader. La plupart le proposent gratuitement.</p>
<div class="mt-3 pt-3 border-t border-amber-200">
<p class="text-xs text-amber-800">ⓘ Pour tester, essayez l'<a href="https://voscms.com/service/order" target="_blank" rel="noopener" class="font-semibold text-blue-600 hover:text-blue-700 underline">hébergement gratuit VosCMS</a>.</p>
</div>
HTML,
        ],

        // ── Bahasa Indonesia ──
        'id' => [
            'welcome_title' => 'Pilih Bahasa Instalasi',
            'welcome_desc' => 'Instalasi akan dilanjutkan dalam bahasa yang dipilih.',
            'start' => 'Mulai Instalasi',
            'next' => 'Selanjutnya',
            'step1' => 'Pemeriksaan Lingkungan',
            'step2' => 'Pengaturan Database',
            'step3' => 'Buat Tabel',
            'step4' => 'Pengaturan Situs',
            'step5' => 'Instalasi Selesai',
            'host' => 'Host',
            'port' => 'Port',
            'db_name' => 'Nama Database',
            'db_user' => 'Nama Pengguna',
            'db_pass' => 'Kata Sandi',
            'db_prefix' => 'Prefiks Tabel',
            'db_name_hint' => 'Akan dibuat otomatis jika belum ada.',
            'db_required' => 'Nama database dan nama pengguna wajib diisi.',
            'db_fail' => 'Koneksi database gagal',
            'connect_next' => 'Tes Koneksi + Selanjutnya',
            'db_success' => 'Database Terhubung!',
            'migration_info' => 'Menjalankan %d file migrasi.',
            'create_tables' => 'Buat Tabel',
            'table_fail' => 'Gagal membuat tabel',
            'site_info' => 'Informasi Situs',
            'site_name' => 'Nama Situs',
            'site_url' => 'URL Situs',
            'admin_path' => 'Path Admin',
            'admin_path_hint' => 'Path default <code>admin</code> adalah target pertama bot serangan otomatis. Disarankan untuk mengubahnya.',
            'admin_path_why_title' => 'Mengapa harus diubah?',
            'admin_path_why_body' => '<strong>1. Menghentikan percobaan peretasan otomatis</strong><br>Di internet terdapat banyak sekali program (bot) yang menjelajahi situs web 24 jam, mencari halaman admin, lalu mencoba kata sandi secara otomatis satu per satu. Alamat pertama yang selalu dicoba bot-bot ini adalah <code>/admin</code>. Jika Anda mengubah path admin ke nama lain, bot tidak akan menemukan halaman login Anda sehingga percobaan tersebut tidak pernah sampai ke situs Anda.<br><br><strong>2. Mencegah pemindaian kerentanan</strong><br>Program otomatis lain memindai path CMS yang dikenal untuk mencari celah keamanan. Menggunakan path yang sulit ditebak sangat mengurangi kemungkinan menjadi target.<br><br><strong>3. Kata sandi kuat saja tidak cukup</strong><br>Meski kata sandi sangat rumit, program peretasan akan terus mencoba selama mengetahui alamat halaman login. Menyembunyikan alamat halaman login itu sendiri adalah pencegahan yang lebih mendasar.<br><br><strong>Rekomendasi:</strong> Gunakan kombinasi huruf dan angka yang sulit ditebak. (contoh: <code>mysite-cms2026</code>)',
            'language' => 'Bahasa Default',
            'timezone_label' => 'Zona Waktu',
            'admin_account' => 'Akun Administrator',
            'admin_name' => 'Nama',
            'email' => 'Email',
            'password' => 'Kata Sandi',
            'pw_hint' => 'Minimal 8 karakter',
            'admin_required' => 'Email dan kata sandi admin wajib diisi.',
            'pw_length' => 'Kata sandi minimal 8 karakter.',
            'finish_install' => 'Selesaikan Instalasi',
            'complete_title' => 'Instalasi Selesai!',
            'complete_desc' => 'VosCMS berhasil diinstal.',
            'license_info' => 'Informasi Lisensi',
            'license_key' => 'Kunci Lisensi',
            'license_ok' => 'Terdaftar di server lisensi',
            'license_fail_msg' => 'Tidak dapat terhubung ke server lisensi — akan didaftarkan otomatis saat login admin berikutnya.',
            'license_env' => 'Kunci ini telah disimpan di file .env. Simpan dengan aman.',
            'admin_page' => 'Halaman Admin',
            'go_admin' => 'Ke Admin',
            'env_fail' => 'Persyaratan server tidak terpenuhi. Periksa konfigurasi server.',
            'save_fail' => 'Gagal menyimpan pengaturan',
            'storage_perm' => 'Izin tulis storage/',
            'env_perm' => 'Izin tulis .env',
            'ic_warning_html' => <<<'HTML'
<p class="font-semibold mb-1">ionCube Loader Diperlukan</p>
<p class="mb-2">Modul keamanan inti VosCMS dienkode dengan ionCube. Ekstensi ionCube Loader harus terpasang di PHP.</p>
<ol class="list-decimal pl-5 space-y-1 text-xs">
<li><a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noopener" class="text-blue-600 underline">Unduh ionCube Loader</a> (PHP %1$s · %2$s · %3$s)</li>
<li>Tambahkan <code class="bg-amber-100 px-1 rounded">zend_extension = /path/to/ioncube_loader_lin_%4$s.so</code> ke <code class="bg-amber-100 px-1 rounded">php.ini</code></li>
<li>Restart server web (php-fpm / apache)</li>
<li>Muat ulang halaman ini — installer otomatis melanjutkan</li>
</ol>
<p class="mt-2 text-xs text-amber-700">ⓘ Pada hosting berbagi/terkelola, minta penyedia mengaktifkan ionCube Loader. Sebagian besar menawarkan gratis.</p>
<div class="mt-3 pt-3 border-t border-amber-200">
<p class="text-xs text-amber-800">ⓘ Untuk pengujian, coba layanan <a href="https://voscms.com/service/order" target="_blank" rel="noopener" class="font-semibold text-blue-600 hover:text-blue-700 underline">Hosting Gratis VosCMS</a>.</p>
</div>
HTML,
            'backup_title' => '⚠️ Pemberitahuan Penting — Cadangan Data',
            'backup_body' => 'VosCMS mengenkripsi data pribadi (email, nama, dll.) menggunakan <code>APP_KEY</code>.<br><br><strong>Segera setelah instalasi, cadangkan file berikut ke tempat yang aman:</strong>',
            'backup_item_env' => 'File <code>.env</code> (berisi APP_KEY — kunci dekripsi)',
            'backup_item_db' => 'Dump database berkala',
            'backup_item_uploads' => 'File yang diunggah di <code>/storage/uploads/</code>',
            'backup_warn' => '⚠️ Jika APP_KEY hilang, semua data pribadi terenkripsi <strong>tidak dapat dipulihkan.</strong>',
            'backup_check' => 'Saya mengerti dan akan mencadangkan file <code>.env</code> secara terpisah.',
            'backup_confirm' => 'Mengerti, Mulai Instalasi',
            'backup_cancel' => 'Batal',
            'backup_title' => '⚠️ Avis Important — Sauvegarde',
            'backup_body' => 'VosCMS chiffre les données personnelles (email, noms, etc.) avec <code>APP_KEY</code>.<br><br><strong>Juste après l\'installation, sauvegardez les fichiers suivants dans un endroit sûr :</strong>',
            'backup_item_env' => 'Fichier <code>.env</code> (contient APP_KEY — clé de déchiffrement)',
            'backup_item_db' => 'Sauvegardes régulières de la base de données',
            'backup_item_uploads' => 'Fichiers téléversés <code>/storage/uploads/</code>',
            'backup_warn' => '⚠️ En cas de perte de APP_KEY, toutes les données chiffrées sont <strong>définitivement irrécupérables.</strong>',
            'backup_check' => 'J\'ai compris et sauvegarderai le fichier <code>.env</code> séparément.',
            'backup_confirm' => 'J\'ai compris, démarrer l\'installation',
            'backup_cancel' => 'Annuler',
        ],

        // ── Монгол ──
        'mn' => [
            'welcome_title' => 'Суулгах хэлийг сонгоно уу',
            'welcome_desc' => 'Суулгалт сонгосон хэлээр үргэлжилнэ.',
            'start' => 'Суулгалт эхлэх',
            'next' => 'Дараах',
            'step1' => 'Орчин шалгах',
            'step2' => 'Өгөгдлийн сангийн тохиргоо',
            'step3' => 'Хүснэгт үүсгэх',
            'step4' => 'Сайтын тохиргоо',
            'step5' => 'Суулгалт дууссан',
            'host' => 'Хост',
            'port' => 'Порт',
            'db_name' => 'Өгөгдлийн сангийн нэр',
            'db_user' => 'Хэрэглэгчийн нэр',
            'db_pass' => 'Нууц үг',
            'db_prefix' => 'Хүснэгтийн угтвар',
            'db_name_hint' => 'Байхгүй бол автоматаар үүсгэнэ.',
            'db_required' => 'Өгөгдлийн сангийн нэр болон хэрэглэгчийн нэр шаардлагатай.',
            'db_fail' => 'Өгөгдлийн сангийн холболт амжилтгүй',
            'connect_next' => 'Холболт шалгах + Дараах',
            'db_success' => 'Өгөгдлийн сан холбогдсон!',
            'migration_info' => '%d миграцийн файл ажиллуулж байна.',
            'create_tables' => 'Хүснэгт үүсгэх',
            'table_fail' => 'Хүснэгт үүсгэж чадсангүй',
            'site_info' => 'Сайтын мэдээлэл',
            'site_name' => 'Сайтын нэр',
            'site_url' => 'Сайтын URL',
            'admin_path' => 'Админ зам',
            'admin_path_hint' => 'Анхдагч <code>admin</code> зам нь автоматжуулсан халдлагын ботуудын эхний бай болдог. Өөрчлөхийг зөвлөж байна.',
            'admin_path_why_title' => 'Яагаад өөрчлөх ёстой вэ?',
            'admin_path_why_body' => '<strong>1. Автомат хакийн оролдлогыг зогсоох</strong><br>Интернэтэд дэлхийн вэбсайтуудыг 24 цаг тойрч, админ хуудас хайж, нууц үгийг автоматаар нэг нэгээр нь оролддог олон тооны программ (бот) байдаг. Эдгээр ботуудын хамгийн түрүүн туршдаг хаяг бол <code>/admin</code> юм. Админ замыг өөр нэрээр сольвол ботууд таны нэвтрэх хуудсыг олохгүй болж, ийм оролдлогууд таны сайтад хүрэхгүй болно.<br><br><strong>2. Эмзэг байдлын скан хийхээс сэргийлэх</strong><br>Бусад автомат программууд аюулгүй байдлын цоорхой хайж алдартай CMS замуудыг скан хийдэг. Таахад хэцүү зам ашиглах нь онилогдох магадлалыг эрс бууруулдаг.<br><br><strong>3. Зөвхөн хүчтэй нууц үг хангалтгүй</strong><br>Нууц үг хэчнээн төвөгтэй байсан ч хакийн программ нэвтрэх хуудасны хаягийг мэдэж байвал үргэлжлүүлэн оролдоно. Нэвтрэх хуудасны хаягийг өөрийг нь нуух нь илүү үндсэн арга хэмжээ юм.<br><br><strong>Зөвлөмж:</strong> Таахад хэцүү үсэг-тоон хослол ашиглана уу. (жнь: <code>mysite-cms2026</code>)',
            'language' => 'Үндсэн хэл',
            'timezone_label' => 'Цагийн бүс',
            'admin_account' => 'Админ бүртгэл',
            'admin_name' => 'Нэр',
            'email' => 'Имэйл',
            'password' => 'Нууц үг',
            'pw_hint' => '8+ тэмдэгт',
            'admin_required' => 'Админ имэйл болон нууц үг шаардлагатай.',
            'pw_length' => 'Нууц үг дор хаяж 8 тэмдэгт байх ёстой.',
            'finish_install' => 'Суулгалт дуусгах',
            'complete_title' => 'Суулгалт дууссан!',
            'complete_desc' => 'VosCMS амжилттай суулгагдлаа.',
            'license_info' => 'Лицензийн мэдээлэл',
            'license_key' => 'Лицензийн түлхүүр',
            'license_ok' => 'Лицензийн серверт бүртгэгдсэн',
            'license_fail_msg' => 'Лицензийн серверт холбогдож чадсангүй — дараагийн админ нэвтрэлтэд автоматаар бүртгэгдэнэ.',
            'license_env' => 'Энэ түлхүүр .env файлд хадгалагдсан. Найдвартай хадгална уу.',
            'admin_page' => 'Админ хуудас',
            'go_admin' => 'Админ руу очих',
            'env_fail' => 'Серверийн шаардлага хангахгүй байна. Серверийн тохиргоог шалгана уу.',
            'save_fail' => 'Тохиргоо хадгалж чадсангүй',
            'storage_perm' => 'storage/ бичих зөвшөөрөл',
            'env_perm' => '.env бичих зөвшөөрөл',
            'ic_warning_html' => <<<'HTML'
<p class="font-semibold mb-1">ionCube Loader шаардлагатай</p>
<p class="mb-2">VosCMS-ийн үндсэн аюулгүй байдлын модулиуд ionCube-ээр кодлогдсон. ionCube Loader өргөтгөл PHP-д суусан байх ёстой.</p>
<ol class="list-decimal pl-5 space-y-1 text-xs">
<li><a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noopener" class="text-blue-600 underline">ionCube Loader татах</a> (PHP %1$s · %2$s · %3$s)</li>
<li><code class="bg-amber-100 px-1 rounded">php.ini</code>-д <code class="bg-amber-100 px-1 rounded">zend_extension = /path/to/ioncube_loader_lin_%4$s.so</code> мөр нэмэх</li>
<li>Веб серверийг (php-fpm / apache) дахин эхлүүлэх</li>
<li>Хуудсыг шинэчлэх → суулгах визард автоматаар нэвтэрнэ</li>
</ol>
<p class="mt-2 text-xs text-amber-700">ⓘ Хуваалцсан хостинг бол үйлчилгээ үзүүлэгчээс ionCube Loader идэвхжүүлэхийг хүсэлт гаргана уу. Ихэнх нь үнэгүй.</p>
<div class="mt-3 pt-3 border-t border-amber-200">
<p class="text-xs text-amber-800">ⓘ Туршихыг хүсвэл <a href="https://voscms.com/service/order" target="_blank" rel="noopener" class="font-semibold text-blue-600 hover:text-blue-700 underline">VosCMS үнэгүй хостинг</a> үйлчилгээг ашиглаарай.</p>
</div>
HTML,
            'backup_title' => '⚠️ Чухал мэдэгдэл — Өгөгдлийн нөөц',
            'backup_body' => 'VosCMS нь хувийн мэдээлэл (и-мэйл, нэр зэрэг)-ийг <code>APP_KEY</code>-ээр шифрлэн хадгалдаг.<br><br><strong>Суулгасны дараа дараах файлуудыг аюулгүй газар нөөцлөнө үү:</strong>',
            'backup_item_env' => '<code>.env</code> файл (APP_KEY агуулсан — шифр тайлах түлхүүр)',
            'backup_item_db' => 'Мэдээллийн баазын тогтмол нөөц',
            'backup_item_uploads' => '<code>/storage/uploads/</code> байршуулсан файлууд',
            'backup_warn' => '⚠️ APP_KEY алдагдвал бүх шифрлэгдсэн хувийн мэдээллийг <strong>бүрмөсөн сэргээх боломжгүй болно.</strong>',
            'backup_check' => 'Би ойлгосон бөгөөд <code>.env</code> файлыг тусад нь нөөцлөнө.',
            'backup_confirm' => 'Ойлгосон, суулгалт эхлүүлэх',
            'backup_cancel' => 'Цуцлах',
        ],

        // ── Русский ──
        'ru' => [
            'welcome_title' => 'Выберите язык установки',
            'welcome_desc' => 'Установка будет выполнена на выбранном языке.',
            'start' => 'Начать установку',
            'next' => 'Далее',
            'step1' => 'Проверка окружения',
            'step2' => 'Настройка базы данных',
            'step3' => 'Создание таблиц',
            'step4' => 'Настройки сайта',
            'step5' => 'Установка завершена',
            'host' => 'Хост',
            'port' => 'Порт',
            'db_name' => 'Имя базы данных',
            'db_user' => 'Имя пользователя',
            'db_pass' => 'Пароль',
            'db_prefix' => 'Префикс таблиц',
            'db_name_hint' => 'Будет создана автоматически, если не существует.',
            'db_required' => 'Имя базы данных и пользователя обязательны.',
            'db_fail' => 'Ошибка подключения к базе данных',
            'connect_next' => 'Проверить соединение + Далее',
            'db_success' => 'База данных подключена!',
            'migration_info' => 'Выполняется %d файлов миграции.',
            'create_tables' => 'Создать таблицы',
            'table_fail' => 'Ошибка создания таблиц',
            'site_info' => 'Информация о сайте',
            'site_name' => 'Название сайта',
            'site_url' => 'URL сайта',
            'admin_path' => 'Путь администратора',
            'admin_path_hint' => 'Путь по умолчанию <code>admin</code> — первая цель автоматических атак ботов. Рекомендуется изменить его.',
            'admin_path_why_title' => 'Зачем менять?',
            'admin_path_why_body' => '<strong>1. Остановить автоматические попытки взлома</strong><br>В интернете существуют тысячи программ (ботов), которые круглосуточно обходят сайты, ищут страницы администратора и автоматически перебирают пароли один за другим. Первый адрес, который проверяют эти боты, — <code>/admin</code>. Если вы смените путь администратора, боты просто не смогут найти вашу страницу входа, и эти попытки никогда не достигнут вашего сайта.<br><br><strong>2. Защита от сканеров уязвимостей</strong><br>Другие автоматические программы сканируют известные пути CMS в поисках уязвимостей. Непредсказуемый путь значительно снижает вероятность стать мишенью.<br><br><strong>3. Одного надёжного пароля недостаточно</strong><br>Даже со сложным паролем хакерская программа будет продолжать попытки, пока знает адрес страницы входа. Скрыть сам адрес страницы входа — более фундаментальная мера защиты.<br><br><strong>Рекомендация:</strong> Используйте труднопредсказуемую комбинацию букв и цифр. (например: <code>mysite-cms2026</code>)',
            'language' => 'Язык по умолчанию',
            'timezone_label' => 'Часовой пояс',
            'admin_account' => 'Учётная запись администратора',
            'admin_name' => 'Имя',
            'email' => 'Эл. почта',
            'password' => 'Пароль',
            'pw_hint' => 'Не менее 8 символов',
            'admin_required' => 'E-mail и пароль администратора обязательны.',
            'pw_length' => 'Пароль должен содержать не менее 8 символов.',
            'finish_install' => 'Завершить установку',
            'complete_title' => 'Установка завершена!',
            'complete_desc' => 'VosCMS успешно установлен.',
            'license_info' => 'Информация о лицензии',
            'license_key' => 'Лицензионный ключ',
            'license_ok' => 'Зарегистрировано на сервере лицензий',
            'license_fail_msg' => 'Не удалось подключиться к серверу лицензий — регистрация произойдёт автоматически при следующем входе.',
            'license_env' => 'Этот ключ сохранён в файле .env. Храните его в безопасности.',
            'admin_page' => 'Страница администратора',
            'go_admin' => 'Перейти в админ',
            'env_fail' => 'Требования сервера не выполнены. Проверьте конфигурацию сервера.',
            'save_fail' => 'Ошибка сохранения настроек',
            'storage_perm' => 'Права записи storage/',
            'env_perm' => 'Права записи .env',
            'ic_warning_html' => <<<'HTML'
<p class="font-semibold mb-1">Требуется ionCube Loader</p>
<p class="mb-2">Основные модули безопасности VosCMS закодированы с помощью ionCube. Расширение ionCube Loader должно быть установлено в PHP.</p>
<ol class="list-decimal pl-5 space-y-1 text-xs">
<li><a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noopener" class="text-blue-600 underline">Скачать ionCube Loader</a> (PHP %1$s · %2$s · %3$s)</li>
<li>Добавьте <code class="bg-amber-100 px-1 rounded">zend_extension = /path/to/ioncube_loader_lin_%4$s.so</code> в <code class="bg-amber-100 px-1 rounded">php.ini</code></li>
<li>Перезапустите веб-сервер (php-fpm / apache)</li>
<li>Обновите эту страницу — установщик продолжится автоматически</li>
</ol>
<p class="mt-2 text-xs text-amber-700">ⓘ На общем/управляемом хостинге попросите провайдера включить ionCube Loader. Большинство предлагает это бесплатно.</p>
<div class="mt-3 pt-3 border-t border-amber-200">
<p class="text-xs text-amber-800">ⓘ Для тестирования попробуйте <a href="https://voscms.com/service/order" target="_blank" rel="noopener" class="font-semibold text-blue-600 hover:text-blue-700 underline">бесплатный хостинг VosCMS</a>.</p>
</div>
HTML,
            'backup_title' => '⚠️ Важное уведомление — Резервная копия',
            'backup_body' => 'VosCMS шифрует персональные данные (email, имена и т.д.) с помощью <code>APP_KEY</code>.<br><br><strong>Сразу после установки сохраните следующие файлы в безопасном месте:</strong>',
            'backup_item_env' => 'Файл <code>.env</code> (содержит APP_KEY — ключ расшифровки)',
            'backup_item_db' => 'Регулярные дампы базы данных',
            'backup_item_uploads' => 'Загруженные файлы <code>/storage/uploads/</code>',
            'backup_warn' => '⚠️ При потере APP_KEY все зашифрованные данные <strong>восстановить невозможно.</strong>',
            'backup_check' => 'Я понимаю и сохраню файл <code>.env</code> отдельно.',
            'backup_confirm' => 'Понятно, начать установку',
            'backup_cancel' => 'Отмена',
        ],

        // ── Türkçe ──
        'tr' => [
            'welcome_title' => 'Kurulum Dilini Seçin',
            'welcome_desc' => 'Kurulum seçilen dilde devam edecektir.',
            'start' => 'Kurulumu Başlat',
            'next' => 'İleri',
            'step1' => 'Ortam Kontrolü',
            'step2' => 'Veritabanı Ayarları',
            'step3' => 'Tabloları Oluştur',
            'step4' => 'Site Ayarları',
            'step5' => 'Kurulum Tamamlandı',
            'host' => 'Sunucu',
            'port' => 'Port',
            'db_name' => 'Veritabanı Adı',
            'db_user' => 'Kullanıcı Adı',
            'db_pass' => 'Şifre',
            'db_prefix' => 'Tablo Öneki',
            'db_name_hint' => 'Mevcut değilse otomatik oluşturulur.',
            'db_required' => 'Veritabanı adı ve kullanıcı adı zorunludur.',
            'db_fail' => 'Veritabanı bağlantısı başarısız',
            'connect_next' => 'Bağlantıyı Test Et + İleri',
            'db_success' => 'Veritabanı Bağlandı!',
            'migration_info' => '%d migrasyon dosyası çalıştırılıyor.',
            'create_tables' => 'Tabloları Oluştur',
            'table_fail' => 'Tablo oluşturma başarısız',
            'site_info' => 'Site Bilgileri',
            'site_name' => 'Site Adı',
            'site_url' => 'Site URL',
            'admin_path' => 'Yönetici Yolu',
            'admin_path_hint' => 'Varsayılan <code>admin</code> yolu, otomatik saldırı botlarının ilk hedefidir. Değiştirmeniz önerilir.',
            'admin_path_why_title' => 'Neden değiştirmeliyim?',
            'admin_path_why_body' => '<strong>1. Otomatik hackleme girişimlerini durdurun</strong><br>İnternette, 7/24 web sitelerini gezen, yönetici sayfalarını bulan ve şifreleri otomatik olarak tek tek deneyen çok sayıda program (bot) bulunmaktadır. Bu botların denediği ilk adres <code>/admin</code>\'dir. Yönetici yolunu başka bir isimle değiştirirseniz, botlar giriş sayfanızı bulamaz ve bu girişimler sitenize hiç ulaşamaz.<br><br><strong>2. Güvenlik açığı taramalarını önleyin</strong><br>Diğer otomatik programlar, güvenlik açıklarını bulmak için bilinen CMS yollarını tarar. Tahmin edilemez bir yol kullanmak, hedef alınma olasılığını önemli ölçüde azaltır.<br><br><strong>3. Güçlü bir parola tek başına yeterli değildir</strong><br>Parola ne kadar karmaşık olursa olsun, bir hackleme programı giriş sayfasının adresini bildiği sürece denemeye devam eder. Giriş sayfası adresinin kendisini gizlemek daha temel bir önlemdir.<br><br><strong>Öneri:</strong> Tahmin edilmesi zor bir harf-sayı kombinasyonu kullanın. (ör. <code>mysite-cms2026</code>)',
            'language' => 'Varsayılan Dil',
            'timezone_label' => 'Saat Dilimi',
            'admin_account' => 'Yönetici Hesabı',
            'admin_name' => 'Ad',
            'email' => 'E-posta',
            'password' => 'Şifre',
            'pw_hint' => 'En az 8 karakter',
            'admin_required' => 'Yönetici e-postası ve şifresi zorunludur.',
            'pw_length' => 'Şifre en az 8 karakter olmalıdır.',
            'finish_install' => 'Kurulumu Tamamla',
            'complete_title' => 'Kurulum Tamamlandı!',
            'complete_desc' => 'VosCMS başarıyla kuruldu.',
            'license_info' => 'Lisans Bilgileri',
            'license_key' => 'Lisans Anahtarı',
            'license_ok' => 'Lisans sunucusuna kayıtlı',
            'license_fail_msg' => 'Lisans sunucusuna bağlanılamadı — bir sonraki yönetici girişinde otomatik kayıt yapılacaktır.',
            'license_env' => 'Bu anahtar .env dosyasına kaydedildi. Güvenli bir şekilde saklayın.',
            'admin_page' => 'Yönetici Sayfası',
            'go_admin' => 'Yöneticiye Git',
            'env_fail' => 'Sunucu gereksinimleri karşılanmıyor. Sunucu yapılandırmasını kontrol edin.',
            'save_fail' => 'Ayarlar kaydedilemedi',
            'storage_perm' => 'storage/ yazma izni',
            'env_perm' => '.env yazma izni',
            'ic_warning_html' => <<<'HTML'
<p class="font-semibold mb-1">ionCube Loader Gerekli</p>
<p class="mb-2">VosCMS çekirdek güvenlik modülleri ionCube ile kodlanmıştır. ionCube Loader uzantısı PHP'ye kurulu olmalıdır.</p>
<ol class="list-decimal pl-5 space-y-1 text-xs">
<li><a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noopener" class="text-blue-600 underline">ionCube Loader indir</a> (PHP %1$s · %2$s · %3$s)</li>
<li><code class="bg-amber-100 px-1 rounded">php.ini</code>'ye <code class="bg-amber-100 px-1 rounded">zend_extension = /path/to/ioncube_loader_lin_%4$s.so</code> ekleyin</li>
<li>Web sunucusunu yeniden başlatın (php-fpm / apache)</li>
<li>Sayfayı yenileyin — kurulum otomatik devam edecek</li>
</ol>
<p class="mt-2 text-xs text-amber-700">ⓘ Paylaşımlı/yönetilen hostingde sağlayıcınızdan ionCube Loader'ı etkinleştirmesini isteyin. Çoğu ücretsiz sunar.</p>
<div class="mt-3 pt-3 border-t border-amber-200">
<p class="text-xs text-amber-800">ⓘ Test için <a href="https://voscms.com/service/order" target="_blank" rel="noopener" class="font-semibold text-blue-600 hover:text-blue-700 underline">VosCMS Ücretsiz Hosting</a> hizmetini deneyin.</p>
</div>
HTML,
            'backup_title' => '⚠️ Önemli Bildirim — Veri Yedeklemesi',
            'backup_body' => 'VosCMS kişisel verileri (e-posta, isimler vb.) <code>APP_KEY</code> ile şifreleyerek saklar.<br><br><strong>Kurulumdan hemen sonra aşağıdaki dosyaları güvenli bir yere yedekleyin:</strong>',
            'backup_item_env' => '<code>.env</code> dosyası (APP_KEY içerir — şifre çözme anahtarı)',
            'backup_item_db' => 'Düzenli veritabanı yedekleri',
            'backup_item_uploads' => '<code>/storage/uploads/</code> yüklenen dosyalar',
            'backup_warn' => '⚠️ APP_KEY kaybedilirse, tüm şifrelenmiş kişisel veriler <strong>kalıcı olarak kurtarılamaz.</strong>',
            'backup_check' => 'Anladım ve <code>.env</code> dosyasını ayrıca yedekleyeceğim.',
            'backup_confirm' => 'Anladım, Kuruluma Başla',
            'backup_cancel' => 'İptal',
        ],

        // ── Tiếng Việt ──
        'vi' => [
            'welcome_title' => 'Chọn ngôn ngữ cài đặt',
            'welcome_desc' => 'Quá trình cài đặt sẽ tiếp tục bằng ngôn ngữ đã chọn.',
            'start' => 'Bắt đầu cài đặt',
            'next' => 'Tiếp theo',
            'step1' => 'Kiểm tra môi trường',
            'step2' => 'Cấu hình cơ sở dữ liệu',
            'step3' => 'Tạo bảng',
            'step4' => 'Cài đặt trang web',
            'step5' => 'Cài đặt hoàn tất',
            'host' => 'Máy chủ',
            'port' => 'Cổng',
            'db_name' => 'Tên cơ sở dữ liệu',
            'db_user' => 'Tên người dùng',
            'db_pass' => 'Mật khẩu',
            'db_prefix' => 'Tiền tố bảng',
            'db_name_hint' => 'Sẽ được tạo tự động nếu chưa tồn tại.',
            'db_required' => 'Tên cơ sở dữ liệu và tên người dùng là bắt buộc.',
            'db_fail' => 'Kết nối cơ sở dữ liệu thất bại',
            'connect_next' => 'Kiểm tra kết nối + Tiếp theo',
            'db_success' => 'Kết nối thành công!',
            'migration_info' => 'Đang thực thi %d tệp migration.',
            'create_tables' => 'Tạo bảng',
            'table_fail' => 'Tạo bảng thất bại',
            'site_info' => 'Thông tin trang web',
            'site_name' => 'Tên trang web',
            'site_url' => 'URL trang web',
            'admin_path' => 'Đường dẫn quản trị',
            'admin_path_hint' => 'Đường dẫn mặc định <code>admin</code> là mục tiêu đầu tiên của các bot tấn công tự động. Nên thay đổi đường dẫn này.',
            'admin_path_why_title' => 'Tại sao nên thay đổi?',
            'admin_path_why_body' => '<strong>1. Chặn các cuộc tấn công tự động</strong><br>Trên internet có rất nhiều chương trình (bot) chạy suốt 24/7, dò tìm các trang quản trị rồi tự động thử mật khẩu từng cái một. Địa chỉ đầu tiên những bot này thử luôn là <code>/admin</code>. Nếu bạn đổi đường dẫn quản trị thành tên khác, bot sẽ không tìm được trang đăng nhập của bạn và các cuộc tấn công này không bao giờ đến được website của bạn.<br><br><strong>2. Ngăn chặn quét lỗ hổng bảo mật</strong><br>Các chương trình tự động khác quét các đường dẫn CMS phổ biến để tìm lỗ hổng. Dùng đường dẫn khó đoán sẽ giảm đáng kể khả năng bị nhắm mục tiêu.<br><br><strong>3. Chỉ có mật khẩu mạnh thôi chưa đủ</strong><br>Dù mật khẩu phức tạp đến đâu, nếu chương trình hack biết địa chỉ trang đăng nhập, nó sẽ tiếp tục thử. Ẩn chính địa chỉ trang đăng nhập là biện pháp phòng ngừa căn bản hơn.<br><br><strong>Khuyến nghị:</strong> Dùng tổ hợp chữ-số khó đoán. (ví dụ: <code>mysite-cms2026</code>)',
            'language' => 'Ngôn ngữ mặc định',
            'timezone_label' => 'Múi giờ',
            'admin_account' => 'Tài khoản quản trị viên',
            'admin_name' => 'Tên',
            'email' => 'Email',
            'password' => 'Mật khẩu',
            'pw_hint' => 'Tối thiểu 8 ký tự',
            'admin_required' => 'Email và mật khẩu quản trị viên là bắt buộc.',
            'pw_length' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'finish_install' => 'Hoàn tất cài đặt',
            'complete_title' => 'Cài đặt hoàn tất!',
            'complete_desc' => 'VosCMS đã được cài đặt thành công.',
            'license_info' => 'Thông tin giấy phép',
            'license_key' => 'Khóa giấy phép',
            'license_ok' => 'Đã đăng ký với máy chủ giấy phép',
            'license_fail_msg' => 'Không thể kết nối máy chủ giấy phép — sẽ tự động đăng ký khi đăng nhập quản trị lần sau.',
            'license_env' => 'Khóa này đã được lưu trong tệp .env. Hãy bảo quản an toàn.',
            'admin_page' => 'Trang quản trị',
            'go_admin' => 'Đến trang quản trị',
            'env_fail' => 'Không đáp ứng yêu cầu máy chủ. Vui lòng kiểm tra cấu hình.',
            'save_fail' => 'Lưu cài đặt thất bại',
            'storage_perm' => 'Quyền ghi storage/',
            'env_perm' => 'Quyền ghi .env',
            'ic_warning_html' => <<<'HTML'
<p class="font-semibold mb-1">Cần ionCube Loader</p>
<p class="mb-2">Các module bảo mật cốt lõi của VosCMS được mã hóa bằng ionCube. Tiện ích ionCube Loader phải được cài đặt trong PHP.</p>
<ol class="list-decimal pl-5 space-y-1 text-xs">
<li><a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noopener" class="text-blue-600 underline">Tải ionCube Loader</a> (PHP %1$s · %2$s · %3$s)</li>
<li>Thêm <code class="bg-amber-100 px-1 rounded">zend_extension = /path/to/ioncube_loader_lin_%4$s.so</code> vào <code class="bg-amber-100 px-1 rounded">php.ini</code></li>
<li>Khởi động lại máy chủ web (php-fpm / apache)</li>
<li>Tải lại trang — trình cài đặt sẽ tự động tiếp tục</li>
</ol>
<p class="mt-2 text-xs text-amber-700">ⓘ Trên hosting chia sẻ/quản lý, yêu cầu nhà cung cấp bật ionCube Loader. Hầu hết miễn phí.</p>
<div class="mt-3 pt-3 border-t border-amber-200">
<p class="text-xs text-amber-800">ⓘ Để thử, hãy dùng dịch vụ <a href="https://voscms.com/service/order" target="_blank" rel="noopener" class="font-semibold text-blue-600 hover:text-blue-700 underline">Hosting miễn phí VosCMS</a>.</p>
</div>
HTML,
            'backup_title' => '⚠️ Thông báo quan trọng — Sao lưu dữ liệu',
            'backup_body' => 'VosCMS mã hóa thông tin cá nhân (email, tên, v.v.) bằng <code>APP_KEY</code>.<br><br><strong>Ngay sau khi cài đặt, hãy sao lưu các tệp sau vào nơi an toàn:</strong>',
            'backup_item_env' => 'Tệp <code>.env</code> (chứa APP_KEY — khóa giải mã)',
            'backup_item_db' => 'Sao lưu cơ sở dữ liệu định kỳ',
            'backup_item_uploads' => 'Tệp đã tải lên <code>/storage/uploads/</code>',
            'backup_warn' => '⚠️ Nếu mất APP_KEY, tất cả dữ liệu cá nhân được mã hóa sẽ <strong>không thể khôi phục vĩnh viễn.</strong>',
            'backup_check' => 'Tôi đã hiểu và sẽ sao lưu tệp <code>.env</code> riêng.',
            'backup_confirm' => 'Đã hiểu, bắt đầu cài đặt',
            'backup_cancel' => 'Hủy',
        ],
    ],
];
