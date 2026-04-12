<?php
/**
 * VosCMS 기본 게시판 시드 데이터
 * 설치 시 자동 생성되는 게시판, 카테고리, 게시글, 번역 데이터
 * 생성일: 2026-04-12
 */
return [
    'categories' => [
        ['board_slug'=>'notice','name'=>'공지','slug'=>'notice','sort'=>1,'translations'=>array (
  'de' => 'Ankündigung',
  'en' => 'Notice',
  'es' => 'Aviso',
  'fr' => 'Annonce',
  'id' => 'Pengumuman',
  'ja' => 'お知らせ',
  'ko' => '공지',
  'mn' => 'Мэдэгдэл',
  'ru' => 'Объявление',
  'tr' => 'Duyuru',
  'vi' => 'Thông báo',
  'zh_CN' => '公告',
  'zh_TW' => '公告',
)],
        ['board_slug'=>'notice','name'=>'업데이트','slug'=>'update','sort'=>2,'translations'=>array (
  'de' => 'Update',
  'en' => 'Update',
  'es' => 'Actualización',
  'fr' => 'Mise à jour',
  'id' => 'Pembaruan',
  'ja' => 'アップデート',
  'ko' => '업데이트',
  'mn' => 'Шинэчлэлт',
  'ru' => 'Обновление',
  'tr' => 'Güncelleme',
  'vi' => 'Cập nhật',
  'zh_CN' => '更新',
  'zh_TW' => '更新',
)],
        ['board_slug'=>'notice','name'=>'보안','slug'=>'security','sort'=>3,'translations'=>array (
  'de' => 'Sicherheit',
  'en' => 'Security',
  'es' => 'Seguridad',
  'fr' => 'Sécurité',
  'id' => 'Keamanan',
  'ja' => 'セキュリティ',
  'ko' => '보안',
  'mn' => 'Аюулгүй байдал',
  'ru' => 'Безопасность',
  'tr' => 'Güvenlik',
  'vi' => 'Bảo mật',
  'zh_CN' => '安全',
  'zh_TW' => '安全',
)],
        ['board_slug'=>'notice','name'=>'점검','slug'=>'maintenance','sort'=>4,'translations'=>array (
  'de' => 'Wartung',
  'en' => 'Maintenance',
  'es' => 'Mantenimiento',
  'fr' => 'Maintenance',
  'id' => 'Pemeliharaan',
  'ja' => 'メンテナンス',
  'ko' => '점검',
  'mn' => 'Засвар үйлчилгээ',
  'ru' => 'Обслуживание',
  'tr' => 'Bakım',
  'vi' => 'Bảo trì',
  'zh_CN' => '维护',
  'zh_TW' => '維護',
)],
        ['board_slug'=>'notice','name'=>'이벤트','slug'=>'event','sort'=>5,'translations'=>array (
  'de' => 'Veranstaltung',
  'en' => 'Event',
  'es' => 'Evento',
  'fr' => 'Événement',
  'id' => 'Acara',
  'ja' => 'イベント',
  'ko' => '이벤트',
  'mn' => 'Арга хэмжээ',
  'ru' => 'Событие',
  'tr' => 'Etkinlik',
  'vi' => 'Sự kiện',
  'zh_CN' => '活动',
  'zh_TW' => '活動',
)],
        ['board_slug'=>'faq','name'=>'일반','slug'=>'general','sort'=>1,'translations'=>array (
  'de' => 'Allgemein',
  'en' => 'General',
  'es' => 'General',
  'fr' => 'Général',
  'id' => 'Umum',
  'ja' => '一般',
  'ko' => '일반',
  'mn' => 'Ерөнхий',
  'ru' => 'Общие',
  'tr' => 'Genel',
  'vi' => 'Chung',
  'zh_CN' => '常规',
  'zh_TW' => '一般',
)],
        ['board_slug'=>'faq','name'=>'설치/설정','slug'=>'setup','sort'=>2,'translations'=>array (
  'de' => 'Installation/Einrichtung',
  'en' => 'Setup',
  'es' => 'Instalación/Configuración',
  'fr' => 'Installation/Configuration',
  'id' => 'Instalasi/Pengaturan',
  'ja' => 'インストール/設定',
  'ko' => '설치/설정',
  'mn' => 'Суулгалт/Тохиргоо',
  'ru' => 'Установка/Настройка',
  'tr' => 'Kurulum/Ayarlar',
  'vi' => 'Cài đặt/Thiết lập',
  'zh_CN' => '安装/设置',
  'zh_TW' => '安裝/設定',
)],
        ['board_slug'=>'faq','name'=>'플러그인/위젯','slug'=>'plugins','sort'=>3,'translations'=>array (
  'de' => 'Plugins/Widgets',
  'en' => 'Plugins/Widgets',
  'es' => 'Plugins/Widgets',
  'fr' => 'Plugins/Widgets',
  'id' => 'Plugin/Widget',
  'ja' => 'プラグイン/ウィジェット',
  'ko' => '플러그인/위젯',
  'mn' => 'Плагин/Виджет',
  'ru' => 'Плагины/Виджеты',
  'tr' => 'Eklentiler/Widget',
  'vi' => 'Plugin/Widget',
  'zh_CN' => '插件/小部件',
  'zh_TW' => '外掛/小工具',
)],
        ['board_slug'=>'faq','name'=>'다국어','slug'=>'i18n','sort'=>4,'translations'=>array (
  'de' => 'Mehrsprachig',
  'en' => 'Multilingual',
  'es' => 'Multilingüe',
  'fr' => 'Multilingue',
  'id' => 'Multibahasa',
  'ja' => '多言語',
  'ko' => '다국어',
  'mn' => 'Олон хэл',
  'ru' => 'Мультиязычность',
  'tr' => 'Çoklu Dil',
  'vi' => 'Đa ngôn ngữ',
  'zh_CN' => '多语言',
  'zh_TW' => '多語言',
)],
        ['board_slug'=>'faq','name'=>'요금/라이선스','slug'=>'pricing','sort'=>5,'translations'=>array (
  'de' => 'Preise/Lizenz',
  'en' => 'Pricing/License',
  'es' => 'Precios/Licencia',
  'fr' => 'Tarifs/Licence',
  'id' => 'Harga/Lisensi',
  'ja' => '料金/ライセンス',
  'ko' => '요금/라이선스',
  'mn' => 'Үнэ/Лиценз',
  'ru' => 'Цены/Лицензия',
  'tr' => 'Fiyat/Lisans',
  'vi' => 'Giá/Giấy phép',
  'zh_CN' => '价格/许可',
  'zh_TW' => '價格/授權',
)],
    ],

    'posts' => [
        [
            'board_slug'=>'faq',
            'cat_slug'=>'general',
            'title'=>'VosCMS란 무엇인가요?',
            'content'=>'<p><strong>Q:</strong> VosCMS란 무엇인가요?</p><p><strong>A:</strong> VosCMS(Value Of Style CMS)는 플러그인 아키텍처를 갖춘 모듈형 콘텐츠 관리 시스템입니다. 13개 언어를 지원하고, 다양한 레이아웃 스킨, 마켓플레이스를 통한 확장 기능을 제공합니다. 무료 오픈소스입니다.</p>',
            'nick_name'=>'Admin',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'de' => 'Was ist VosCMS?',
  'en' => 'What is VosCMS?',
  'es' => '¿Qué es VosCMS?',
  'fr' => 'Qu\'est-ce que VosCMS ?',
  'id' => 'Apa itu VosCMS?',
  'ja' => 'VosCMSとは？',
  'mn' => 'VosCMS гэж юу вэ?',
  'ru' => 'Что такое VosCMS?',
  'tr' => 'VosCMS nedir?',
  'vi' => 'VosCMS là gì?',
  'zh_CN' => '什么是 VosCMS？',
  'zh_TW' => '什麼是 VosCMS？',
),
            'content_translations'=>array (
  'de' => '<p><strong>F:</strong> Was ist VosCMS?</p><p><strong>A:</strong> VosCMS (Value Of Style CMS) ist ein modulares CMS mit Plugin-Architektur, das 13 Sprachen, verschiedene Layout-Skins und erweiterbare Funktionen über einen Marktplatz unterstützt. Es ist kostenlos und Open Source.</p>',
  'en' => '<p><strong>Q:</strong> What is VosCMS?</p><p><strong>A:</strong> VosCMS (Value Of Style CMS) is a modular content management system with plugin architecture, supporting 13 languages, multiple layout skins, and extensible features through a marketplace. It is free and open-source.</p>',
  'es' => '<p><strong>P:</strong> ¿Qué es VosCMS?</p><p><strong>R:</strong> VosCMS (Value Of Style CMS) es un sistema de gestión de contenidos modular con arquitectura de plugins, que admite 13 idiomas, múltiples skins de diseño y funciones extensibles a través de un marketplace. Es gratuito y de código abierto.</p>',
  'fr' => '<p><strong>Q :</strong> Qu\'est-ce que VosCMS ?</p><p><strong>R :</strong> VosCMS (Value Of Style CMS) est un CMS modulaire avec une architecture de plugins, supportant 13 langues, plusieurs skins de mise en page et des fonctionnalités extensibles via un marketplace. Il est gratuit et open source.</p>',
  'id' => '<p><strong>T:</strong> Apa itu VosCMS?</p><p><strong>J:</strong> VosCMS (Value Of Style CMS) adalah CMS modular dengan arsitektur plugin, mendukung 13 bahasa, berbagai skin layout, dan fitur yang dapat diperluas melalui marketplace. Gratis dan open source.</p>',
  'ja' => '<p><strong>Q:</strong> VosCMSとは何ですか？</p><p><strong>A:</strong> VosCMS（Value Of Style CMS）はプラグインアーキテクチャを備えたモジュラーCMSです。13言語をサポートし、複数のレイアウトスキン、マーケットプレイスを通じた拡張機能を提供します。無料のオープンソースです。</p>',
  'mn' => '<p><strong>А:</strong> VosCMS гэж юу вэ?</p><p><strong>Х:</strong> VosCMS (Value Of Style CMS) нь плагин архитектуртай модулар CMS бөгөөд 13 хэлийг дэмждэг, олон загварын скин, маркетплейсээр дамжуулан өргөтгөх боломжтой. Үнэгүй, нээлттэй эх.</p>',
  'ru' => '<p><strong>В:</strong> Что такое VosCMS?</p><p><strong>О:</strong> VosCMS (Value Of Style CMS) — модульная CMS с архитектурой плагинов, поддерживающая 13 языков, различные скины макетов и расширяемые функции через маркетплейс. Бесплатная и с открытым кодом.</p>',
  'tr' => '<p><strong>S:</strong> VosCMS nedir?</p><p><strong>C:</strong> VosCMS (Value Of Style CMS), eklenti mimarisine sahip modüler bir CMS\'dir. 13 dili destekler, çeşitli düzen skinleri ve pazaryeri aracılığıyla genişletilebilir özellikler sunar. Ücretsiz ve açık kaynaktır.</p>',
  'vi' => '<p><strong>H:</strong> VosCMS là gì?</p><p><strong>Đ:</strong> VosCMS (Value Of Style CMS) là CMS mô-đun với kiến trúc plugin, hỗ trợ 13 ngôn ngữ, nhiều skin bố cục và tính năng mở rộng qua marketplace. Miễn phí và mã nguồn mở.</p>',
  'zh_CN' => '<p><strong>问：</strong>什么是 VosCMS？</p><p><strong>答：</strong>VosCMS（Value Of Style CMS）是一个模块化的内容管理系统，采用插件架构，支持 13 种语言、多种布局皮肤，以及通过市场扩展功能。免费开源。</p>',
  'zh_TW' => '<p><strong>問：</strong>什麼是 VosCMS？</p><p><strong>答：</strong>VosCMS（Value Of Style CMS）是一個模組化的內容管理系統，採用外掛架構，支援 13 種語言、多種版面面板，以及透過市場擴展功能。免費開源。</p>',
),
        ],
        [
            'board_slug'=>'faq',
            'cat_slug'=>'pricing',
            'title'=>'VosCMS는 무료인가요?',
            'content'=>'<p>네, VosCMS 코어는 완전히 무료이며 오픈소스(GPLv2)입니다. 누구나 자유롭게 다운로드하여 사용할 수 있습니다.</p><p>유료 플러그인과 테마는 마켓플레이스에서 별도 구매할 수 있습니다.</p>',
            'nick_name'=>'VosCMS',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'en' => 'VosCMS is free?',
  'ja' => 'VosCMSは無料ですか？',
),
            'content_translations'=>array (
  'en' => '<p>Yes, VosCMS core is completely free and open-source (GPLv2). Anyone can freely download and use it.</p><p>Paid plugins and themes can be purchased separately from the marketplace.</p>',
  'ja' => '<p>はい、VosCMSコアは完全無料でオープンソース（GPLv2）です。誰でも自由にダウンロードして使用できます。</p><p>有料プラグインやテーマはマーケットプレイスで別途購入可能です。</p>',
),
        ],
        [
            'board_slug'=>'faq',
            'cat_slug'=>'setup',
            'title'=>'어떤 서버 환경이 필요한가요?',
            'content'=>'<p>VosCMS를 실행하려면 다음 환경이 필요합니다:</p>
<h4>필수 요구사항</h4>
<ul>
<li><strong>PHP 8.1 이상</strong> (8.3 권장)</li>
<li><strong>MySQL 8.0+</strong> 또는 <strong>MariaDB 10.4+</strong></li>
<li><strong>Nginx</strong> 또는 <strong>Apache</strong> 웹서버</li>
<li><strong>HTTPS</strong> (SSL 인증서 필수 — localhost 설치 불가)</li>
</ul>
<h4>필수 PHP 확장 모듈</h4>
<ul>
<li>PDO, mbstring, json, openssl, curl, zip</li>
<li><strong>ionCube Loader</strong> — 라이선스 인증 및 코어 보호에 필요합니다. 서버에 ionCube Loader가 설치되어 있어야 합니다.</li>
</ul>
<h4>ionCube Loader 설치 안내</h4>
<p>ionCube Loader는 VosCMS의 라이선스 클라이언트 및 핵심 코어 파일을 보호하기 위해 사용됩니다.</p>
<ul>
<li><strong>VPS/전용서버:</strong> <code>ioncube_loaders_lin_x86-64.tar.gz</code>를 다운로드하여 PHP extension 디렉토리에 설치하고, <code>zend_extension=ioncube_loader_lin_8.3.so</code>를 php.ini에 추가합니다.</li>
<li><strong>공유호스팅:</strong> 호스팅 제공업체에 ionCube Loader 활성화를 요청하세요. 일부 공유호스팅에서는 직접 설치가 불가능할 수 있습니다.</li>
<li><strong>확인 방법:</strong> <code>php -m | grep ionCube</code> 명령으로 설치 여부를 확인할 수 있습니다.</li>
</ul>
<h4>권장 서버 사양</h4>
<ul>
<li>2 vCPU, 2GB RAM, 40GB SSD 이상</li>
<li>Ubuntu 22.04+ 또는 Rocky Linux 8+</li>
</ul>',
            'nick_name'=>'VosCMS',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'en' => 'What server requirements are needed?',
  'ja' => 'どのようなサーバー環境が必要ですか？',
),
            'content_translations'=>array (
  'en' => '<p>VosCMS requires the following environment:</p>
<h4>Required</h4>
<ul>
<li><strong>PHP 8.1+</strong> (8.3 recommended)</li>
<li><strong>MySQL 8.0+</strong> or <strong>MariaDB 10.4+</strong></li>
<li><strong>Nginx</strong> or <strong>Apache</strong></li>
<li><strong>HTTPS</strong> (SSL certificate required — cannot install on localhost)</li>
</ul>
<h4>Required PHP Extensions</h4>
<ul>
<li>PDO, mbstring, json, openssl, curl, zip</li>
<li><strong>ionCube Loader</strong> — Required for license authentication and core code protection. ionCube Loader must be installed on your server.</li>
</ul>
<h4>ionCube Loader Installation</h4>
<p>ionCube Loader is used to protect VosCMS license client and core files.</p>
<ul>
<li><strong>VPS/Dedicated:</strong> Download <code>ioncube_loaders_lin_x86-64.tar.gz</code>, install to PHP extension directory, and add <code>zend_extension=ioncube_loader_lin_8.3.so</code> to php.ini.</li>
<li><strong>Shared Hosting:</strong> Ask your hosting provider to enable ionCube Loader. Some shared hosts may not support it.</li>
<li><strong>Verify:</strong> Run <code>php -m | grep ionCube</code> to check installation.</li>
</ul>
<h4>Recommended Server Specs</h4>
<ul>
<li>2 vCPU, 2GB RAM, 40GB SSD or more</li>
<li>Ubuntu 22.04+ or Rocky Linux 8+</li>
</ul>',
  'ja' => '<p>VosCMSの動作には以下の環境が必要です：</p>
<h4>必須要件</h4>
<ul>
<li><strong>PHP 8.1以上</strong>（8.3推奨）</li>
<li><strong>MySQL 8.0+</strong> または <strong>MariaDB 10.4+</strong></li>
<li><strong>Nginx</strong> または <strong>Apache</strong></li>
<li><strong>HTTPS</strong>（SSL証明書必須 — localhostへのインストール不可）</li>
</ul>
<h4>必須PHP拡張モジュール</h4>
<ul>
<li>PDO, mbstring, json, openssl, curl, zip</li>
<li><strong>ionCube Loader</strong> — ライセンス認証とコアコード保護に必要です。サーバーにionCube Loaderがインストールされている必要があります。</li>
</ul>
<h4>ionCube Loaderのインストール</h4>
<p>ionCube LoaderはVosCMSのライセンスクライアントとコアファイルの保護に使用されます。</p>
<ul>
<li><strong>VPS/専用サーバー:</strong> <code>ioncube_loaders_lin_x86-64.tar.gz</code>をダウンロードし、PHP拡張ディレクトリにインストール後、php.iniに<code>zend_extension=ioncube_loader_lin_8.3.so</code>を追加。</li>
<li><strong>共有ホスティング:</strong> ホスティング会社にionCube Loaderの有効化を依頼してください。一部の共有ホスティングでは直接インストールできない場合があります。</li>
<li><strong>確認方法:</strong> <code>php -m | grep ionCube</code>コマンドで確認できます。</li>
</ul>
<h4>推奨サーバースペック</h4>
<ul>
<li>2 vCPU、2GB RAM、40GB SSD以上</li>
<li>Ubuntu 22.04+ または Rocky Linux 8+</li>
</ul>',
),
        ],
        [
            'board_slug'=>'faq',
            'cat_slug'=>'plugins',
            'title'=>'플러그인은 어떻게 설치하나요?',
            'content'=>'<p>관리자 패널에서 <strong>자동 설치</strong> 메뉴로 이동하면 마켓플레이스에 등록된 플러그인을 검색하고 원클릭으로 설치할 수 있습니다.</p><p>또는 ZIP 파일을 다운로드하여 <code>plugins/</code> 디렉토리에 직접 업로드한 후, 관리자 패널 > 플러그인 관리에서 활성화할 수도 있습니다.</p>',
            'nick_name'=>'VosCMS',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'en' => 'How do I install plugins?',
  'ja' => 'プラグインはどうやってインストールしますか？',
),
            'content_translations'=>array (
  'en' => '<p>Go to <strong>Auto Install</strong> in the admin panel to search and install plugins from the marketplace with one click.</p><p>Or download the ZIP file, upload it to the <code>plugins/</code> directory, and activate it from Admin > Plugin Management.</p>',
  'ja' => '<p>管理パネルの<strong>自動インストール</strong>メニューで、マーケットプレイスのプラグインを検索してワンクリックでインストールできます。</p><p>またはZIPファイルをダウンロードして<code>plugins/</code>ディレクトリにアップロードし、管理パネル > プラグイン管理で有効化することもできます。</p>',
),
        ],
        [
            'board_slug'=>'faq',
            'cat_slug'=>'i18n',
            'title'=>'다국어는 어떻게 설정하나요?',
            'content'=>'<p>VosCMS는 기본으로 13개 언어를 지원합니다. 관리자 패널 > 설정 > 언어 설정에서 지원 언어를 선택할 수 있습니다.</p><p>게시글, 메뉴, 페이지 모두 다국어를 지원하며, 사용자가 언어를 전환하면 자동으로 해당 언어 번역이 표시됩니다.</p>',
            'nick_name'=>'VosCMS',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'en' => 'How do I set up multilingual?',
  'ja' => '多言語はどう設定しますか？',
),
            'content_translations'=>array (
  'en' => '<p>VosCMS supports 13 languages by default. You can select supported languages in Admin > Settings > Language.</p><p>Posts, menus, and pages all support multilingual. When users switch languages, translations are displayed automatically.</p>',
  'ja' => '<p>VosCMSは標準で13言語をサポートしています。管理パネル > 設定 > 言語設定でサポート言語を選択できます。</p><p>投稿、メニュー、ページすべてが多言語対応しており、ユーザーが言語を切り替えると自動的に翻訳が表示されます。</p>',
),
        ],
        [
            'board_slug'=>'faq',
            'cat_slug'=>'plugins',
            'title'=>'위젯은 어떻게 사용하나요?',
            'content'=>'<p>관리자 패널 > 사이트 > 페이지 관리에서 원하는 페이지를 선택한 후 위젯 빌더를 사용합니다.</p><p>드래그 앤 드롭으로 위젯을 배치하고, 각 위젯의 설정을 커스터마이징할 수 있습니다. 히어로 배너, 기능 소개, 통계, CTA 등 다양한 위젯이 제공됩니다.</p>',
            'nick_name'=>'VosCMS',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'en' => 'How do I use widgets?',
  'ja' => 'ウィジェットはどう使いますか？',
),
            'content_translations'=>array (
  'en' => '<p>Go to Admin > Site > Page Management, select a page and use the Widget Builder.</p><p>Drag and drop widgets, and customize each widget\'s settings. Various widgets are available including hero banners, features, stats, and CTA.</p>',
  'ja' => '<p>管理パネル > サイト > ページ管理で、ページを選択してウィジェットビルダーを使用します。</p><p>ドラッグ＆ドロップでウィジェットを配置し、各ウィジェットの設定をカスタマイズできます。ヒーローバナー、機能紹介、統計、CTAなど多彩なウィジェットが用意されています。</p>',
),
        ],
        [
            'board_slug'=>'faq',
            'cat_slug'=>'general',
            'title'=>'VosCMS와 RezlyX는 무엇이 다른가요?',
            'content'=>'<p>VosCMS는 무료 오픈소스 CMS 코어입니다. RezlyX는 VosCMS에 살롱 관리(예약/서비스/스태프), POS, 키오스크, 근태관리 플러그인을 모두 포함한 올인원 번들 제품입니다.</p><p>VosCMS만 설치하면 일반 홈페이지/커뮤니티로 사용하고, 필요한 플러그인을 마켓플레이스에서 추가하는 방식입니다.</p>',
            'nick_name'=>'VosCMS',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'en' => 'What is the difference between VosCMS and RezlyX?',
  'ja' => 'VosCMSとRezlyXの違いは？',
),
            'content_translations'=>array (
  'en' => '<p>VosCMS is the free open-source CMS core. RezlyX is an all-in-one bundle that includes VosCMS plus salon management (reservations/services/staff), POS, kiosk, and attendance plugins.</p><p>You can install VosCMS alone for a general website/community, then add plugins from the marketplace as needed.</p>',
  'ja' => '<p>VosCMSは無料のオープンソースCMSコアです。RezlyXはVosCMSにサロン管理（予約/サービス/スタッフ）、POS、キオスク、勤怠管理プラグインをすべて含むオールインワンバンドル製品です。</p><p>VosCMSだけをインストールすれば一般的なホームページ/コミュニティとして使用し、必要なプラグインをマーケットプレイスから追加できます。</p>',
),
        ],
        [
            'board_slug'=>'faq',
            'cat_slug'=>'general',
            'title'=>'게시판은 몇 개까지 만들 수 있나요?',
            'content'=>'<p>제한 없이 무제한으로 게시판을 생성할 수 있습니다. 공지사항, 자유게시판, Q&A, FAQ, 갤러리 등 용도별로 자유롭게 만들 수 있습니다.</p><p>각 게시판마다 스킨, 권한, 카테고리, 확장변수를 개별 설정할 수 있습니다.</p>',
            'nick_name'=>'VosCMS',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'en' => 'How many boards can I create?',
  'ja' => '掲示板はいくつまで作成できますか？',
),
            'content_translations'=>array (
  'en' => '<p>You can create unlimited boards. Create boards freely for different purposes like notices, free boards, Q&A, FAQ, galleries, etc.</p><p>Each board can have individual skin, permission, category, and extra variable settings.</p>',
  'ja' => '<p>制限なく無制限に掲示板を作成できます。お知らせ、自由掲示板、Q&A、FAQ、ギャラリーなど用途別に自由に作成できます。</p><p>各掲示板ごとにスキン、権限、カテゴリ、拡張変数を個別設定できます。</p>',
),
        ],
        [
            'board_slug'=>'faq',
            'cat_slug'=>'setup',
            'title'=>'설치 후 초기 설정은 어떻게 하나요?',
            'content'=>'<p>설치 완료 후 관리자 패널에 로그인하여 다음 순서로 설정하세요:</p><ol><li><strong>사이트 설정</strong> — 사이트 이름, URL, 로고, 파비콘</li><li><strong>언어 설정</strong> — 지원 언어 선택, 기본 언어 설정</li><li><strong>메뉴 관리</strong> — 메인 메뉴, 푸터 메뉴 구성</li><li><strong>페이지 관리</strong> — 홈페이지 위젯 편집</li><li><strong>게시판 생성</strong> — 필요한 게시판 추가</li></ol>',
            'nick_name'=>'VosCMS',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'en' => 'How do I configure initial settings after installation?',
  'ja' => 'インストール後の初期設定はどうすればいいですか？',
),
            'content_translations'=>array (
  'en' => '<p>After installation, log in to the admin panel and configure in this order:</p><ol><li><strong>Site Settings</strong> — Site name, URL, logo, favicon</li><li><strong>Language Settings</strong> — Select supported languages, set default</li><li><strong>Menu Management</strong> — Configure main menu, footer menu</li><li><strong>Page Management</strong> — Edit homepage widgets</li><li><strong>Create Boards</strong> — Add needed boards</li></ol>',
  'ja' => '<p>インストール完了後、管理パネルにログインして以下の順序で設定してください：</p><ol><li><strong>サイト設定</strong> — サイト名、URL、ロゴ、ファビコン</li><li><strong>言語設定</strong> — サポート言語の選択、デフォルト言語設定</li><li><strong>メニュー管理</strong> — メインメニュー、フッターメニュー構成</li><li><strong>ページ管理</strong> — ホームページウィジェット編集</li><li><strong>掲示板作成</strong> — 必要な掲示板を追加</li></ol>',
),
        ],
        [
            'board_slug'=>'faq',
            'cat_slug'=>'setup',
            'title'=>'HTTPS(SSL) 인증서는 어떻게 설정하나요?',
            'content'=>'<p>VosCMS는 보안을 위해 HTTPS가 필수입니다. SSL 인증서 설정 방법:</p><ul><li><strong>Let\'s Encrypt (무료)</strong> — <code>certbot</code>을 사용하여 무료 SSL 인증서를 발급받을 수 있습니다. 자동 갱신도 지원됩니다.</li><li><strong>호스팅 업체 제공</strong> — 대부분의 호스팅 업체에서 무료 SSL을 제공합니다. 호스팅 패널에서 활성화하세요.</li><li><strong>유료 인증서</strong> — 기업용 EV/OV 인증서는 인증기관에서 구매할 수 있습니다.</li></ul><p>설치 후 <code>.env</code> 파일의 <code>APP_URL</code>이 <code>https://</code>로 시작하는지 확인하세요.</p>',
            'nick_name'=>'VosCMS',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'en' => 'How do I set up HTTPS (SSL)?',
  'ja' => 'HTTPS（SSL）証明書はどう設定しますか？',
),
            'content_translations'=>array (
  'en' => '<p>VosCMS requires HTTPS for security. SSL certificate setup:</p><ul><li><strong>Let\'s Encrypt (Free)</strong> — Use <code>certbot</code> to get free SSL certificates with auto-renewal.</li><li><strong>Hosting Provider</strong> — Most hosting providers offer free SSL. Enable it from the hosting panel.</li><li><strong>Paid Certificates</strong> — Enterprise EV/OV certificates can be purchased from certificate authorities.</li></ul><p>After setup, verify <code>APP_URL</code> in <code>.env</code> starts with <code>https://</code>.</p>',
  'ja' => '<p>VosCMSはセキュリティのためHTTPSが必須です。SSL証明書の設定方法：</p><ul><li><strong>Let\'s Encrypt（無料）</strong> — <code>certbot</code>で無料SSL証明書を取得できます。自動更新にも対応。</li><li><strong>ホスティング提供</strong> — ほとんどのホスティングが無料SSLを提供しています。</li><li><strong>有料証明書</strong> — 企業向けEV/OV証明書は認証局から購入可能。</li></ul><p>設定後、<code>.env</code>の<code>APP_URL</code>が<code>https://</code>で始まっていることを確認してください。</p>',
),
        ],
        [
            'board_slug'=>'faq',
            'cat_slug'=>'plugins',
            'title'=>'위젯 빌더로 어떤 페이지를 만들 수 있나요?',
            'content'=>'<p>위젯 빌더를 사용하면 코딩 없이 다양한 페이지를 만들 수 있습니다:</p><ul><li>히어로 배너가 있는 메인 페이지</li><li>서비스/기능 소개 페이지</li><li>포트폴리오/갤러리 페이지</li><li>고객 후기 페이지</li><li>CTA(행동 유도) 랜딩 페이지</li></ul><p>20개 이상의 위젯을 드래그 앤 드롭으로 배치하고, 각 위젯의 텍스트, 색상, 이미지를 자유롭게 설정할 수 있습니다.</p>',
            'nick_name'=>'VosCMS',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'en' => 'What pages can I create with the Widget Builder?',
  'ja' => 'ウィジェットビルダーでどんなページが作れますか？',
),
            'content_translations'=>array (
  'en' => '<p>With the Widget Builder, you can create various pages without coding:</p><ul><li>Main page with hero banner</li><li>Service/feature introduction page</li><li>Portfolio/gallery page</li><li>Customer testimonial page</li><li>CTA landing page</li></ul><p>Drag and drop 20+ widgets and freely customize text, colors, and images for each widget.</p>',
  'ja' => '<p>ウィジェットビルダーを使えば、コーディングなしで様々なページを作成できます：</p><ul><li>ヒーローバナー付きメインページ</li><li>サービス/機能紹介ページ</li><li>ポートフォリオ/ギャラリーページ</li><li>お客様の声ページ</li><li>CTAランディングページ</li></ul><p>20以上のウィジェットをドラッグ＆ドロップで配置し、テキスト、色、画像を自由に設定できます。</p>',
),
        ],
        [
            'board_slug'=>'faq',
            'cat_slug'=>'plugins',
            'title'=>'직접 플러그인을 개발할 수 있나요?',
            'content'=>'<p>네, 누구나 VosCMS 플러그인을 개발할 수 있습니다. 개발자 포털에서 가입 후 바로 시작할 수 있습니다.</p><h4>플러그인 개발 절차</h4><ol><li><code>plugins/my-plugin/</code> 디렉토리 생성</li><li><code>plugin.json</code> 매니페스트 작성 (라우트, 메뉴, 마이그레이션 정의)</li><li>뷰 파일, DB 마이그레이션 작성</li><li>ZIP으로 패키징 후 개발자 포털에서 제출</li><li>심사 승인 후 마켓플레이스에 공개</li></ol><p>유료/무료 모두 자유롭게 등록할 수 있으며, 유료 판매 시 수익의 70%를 받을 수 있습니다.</p>',
            'nick_name'=>'VosCMS',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'en' => 'Can I develop my own plugins?',
  'ja' => '自分でプラグインを開発できますか？',
),
            'content_translations'=>array (
  'en' => '<p>Yes, anyone can develop VosCMS plugins. Sign up at the Developer Portal to get started.</p><h4>Plugin Development Process</h4><ol><li>Create <code>plugins/my-plugin/</code> directory</li><li>Write <code>plugin.json</code> manifest (routes, menus, migrations)</li><li>Create view files and DB migrations</li><li>Package as ZIP and submit via Developer Portal</li><li>Published on marketplace after review approval</li></ol><p>You can register both free and paid items. For paid sales, you receive 70% of revenue.</p>',
  'ja' => '<p>はい、誰でもVosCMSプラグインを開発できます。開発者ポータルで登録してすぐに始められます。</p><h4>プラグイン開発手順</h4><ol><li><code>plugins/my-plugin/</code>ディレクトリ作成</li><li><code>plugin.json</code>マニフェスト作成（ルート、メニュー、マイグレーション定義）</li><li>ビューファイル、DBマイグレーション作成</li><li>ZIPでパッケージ化して開発者ポータルから提出</li><li>審査承認後マーケットプレイスで公開</li></ol><p>有料・無料どちらも自由に登録でき、有料販売時は収益の70%を受け取れます。</p>',
),
        ],
        [
            'board_slug'=>'faq',
            'cat_slug'=>'i18n',
            'title'=>'게시글 번역은 어떻게 등록하나요?',
            'content'=>'<p>게시글 작성 시 현재 언어로 원본을 작성합니다. 다른 언어 번역을 추가하려면:</p><ol><li>게시글 수정 페이지에서 언어를 전환합니다.</li><li>해당 언어로 제목과 본문을 번역하여 저장합니다.</li><li>번역은 <code>rzx_translations</code> 테이블에 별도 저장되며, 원본 게시글은 변경되지 않습니다.</li></ol><p>번역이 없는 언어는 영어 → 원본 순서로 자동 폴백됩니다.</p>',
            'nick_name'=>'VosCMS',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'en' => 'How do I add translations to posts?',
  'ja' => '投稿の翻訳はどう登録しますか？',
),
            'content_translations'=>array (
  'en' => '<p>Write the original post in the current language. To add translations:</p><ol><li>Switch language on the post edit page.</li><li>Translate the title and content, then save.</li><li>Translations are stored separately in <code>rzx_translations</code> table; the original post is not modified.</li></ol><p>Languages without translations automatically fall back to English, then the original language.</p>',
  'ja' => '<p>投稿作成時に現在の言語で原文を作成します。他の言語の翻訳を追加するには：</p><ol><li>投稿編集ページで言語を切り替えます。</li><li>その言語でタイトルと本文を翻訳して保存します。</li><li>翻訳は<code>rzx_translations</code>テーブルに別途保存され、元の投稿は変更されません。</li></ol><p>翻訳がない言語は英語→原文の順で自動フォールバックされます。</p>',
),
        ],
        [
            'board_slug'=>'faq',
            'cat_slug'=>'pricing',
            'title'=>'유료 플러그인 결제는 어떻게 하나요?',
            'content'=>'<p>마켓플레이스에서 유료 플러그인을 구매할 때 신용카드 결제를 지원합니다.</p><ul><li>관리자 패널 &gt; 자동 설치에서 플러그인을 검색합니다.</li><li>\"구매\" 버튼을 클릭하면 결제 페이지로 이동합니다.</li><li>결제 완료 후 자동으로 라이선스가 발급되고 다운로드 할 수 있습니다.</li><li>구매한 플러그인은 \"설치 내역\"에서 확인하고 원 클릭 설치가 가능합니다.</li></ul>',
            'nick_name'=>'VosCMS',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'en' => 'How do I pay for paid plugins?',
  'ja' => '有料プラグインの決済方法は？',
),
            'content_translations'=>array (
  'en' => '<p>When purchasing paid plugins from the marketplace, credit card payment via Stripe is supported.</p><ul><li>Search for plugins in Admin > Auto Install.</li><li>Click "Purchase" to go to the Stripe payment page.</li><li>After payment, a license is automatically issued and you can download.</li><li>Purchased plugins can be found in "Install History" for one-click installation.</li></ul>',
  'ja' => '<p>マーケットプレイスで有料プラグインを購入する際、Stripeによるクレジットカード決済に対応しています。</p><ul><li>管理パネル > 自動インストールでプラグインを検索します。</li><li>「購入」ボタンをクリックするとStripe決済ページに移動します。</li><li>決済完了後、自動的にライセンスが発行されダウンロード可能になります。</li><li>購入したプラグインは「インストール履歴」でワンクリックインストールできます。</li></ul>',
),
        ],
        [
            'board_slug'=>'faq',
            'cat_slug'=>'pricing',
            'title'=>'라이선스는 몇 개의 사이트에서 사용할 수 있나요?',
            'content'=>'<p>기본 라이선스는 <strong>1개 도메인</strong>에서 사용할 수 있습니다. 도메인 기반으로 활성화되며, 다른 도메인에서 사용하려면 추가 라이선스가 필요합니다.</p><ul><li><strong>단일 사이트</strong> — 1개 도메인 (기본)</li><li><strong>무제한</strong> — 제한 없이 여러 도메인 (일부 플러그인)</li></ul><p>도메인을 변경해야 할 경우 본사에 문의하시면 연간 1회까지 무료로 변경할 수 있습니다.</p>',
            'nick_name'=>'VosCMS',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'en' => 'How many sites can I use with one license?',
  'ja' => 'ライセンスは何サイトで使用できますか？',
),
            'content_translations'=>array (
  'en' => '<p>A standard license can be used on <strong>1 domain</strong>. It is activated per domain, and additional licenses are needed for other domains.</p><ul><li><strong>Single Site</strong> — 1 domain (default)</li><li><strong>Unlimited</strong> — Multiple domains (some plugins)</li></ul><p>If you need to change domains, contact us for up to 2 free changes per year.</p>',
  'ja' => '<p>標準ライセンスは<strong>1つのドメイン</strong>で使用できます。ドメインベースでアクティベーションされ、他のドメインで使用するには追加ライセンスが必要です。</p><ul><li><strong>シングルサイト</strong> — 1ドメイン（デフォルト）</li><li><strong>無制限</strong> — 複数ドメイン（一部プラグイン）</li></ul><p>ドメイン変更が必要な場合、お問い合わせいただければ年2回まで無料で変更できます。</p>',
),
        ],
        [
            'board_slug'=>'free',
            'cat_slug'=>null,
            'title'=>'안녕하세요! 샘플 게시글입니다.',
            'content'=>'<p>자유게시판의 샘플 게시글입니다. 자유롭게 글을 작성해 보세요.</p><p>관리자 패널에서 게시판 설정, 스킨, 권한을 커스터마이징할 수 있습니다.</p>',
            'nick_name'=>'VosCMS',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'de' => 'Hallo! Dies ist ein Beispielbeitrag.',
  'en' => 'Hello! This is a sample post.',
  'es' => '¡Hola! Esta es una publicación de ejemplo.',
  'fr' => 'Bonjour ! Ceci est un exemple de publication.',
  'id' => 'Halo! Ini adalah postingan contoh.',
  'ja' => 'こんにちは！サンプル投稿です。',
  'mn' => 'Сайн байна уу! Энэ бол жишээ нийтлэл.',
  'ru' => 'Привет! Это пример записи.',
  'tr' => 'Merhaba! Bu bir örnek gönderidir.',
  'vi' => 'Xin chào! Đây là bài viết mẫu.',
  'zh_CN' => '你好！这是一篇示例帖子。',
  'zh_TW' => '你好！這是一篇範例文章。',
),
            'content_translations'=>array (
  'de' => '<p>Dies ist ein Beispielbeitrag im Freien Forum. Schreiben Sie hier, was Sie möchten.</p><p>Sie können Board-Einstellungen, Skins und Berechtigungen im Admin-Panel anpassen.</p>',
  'en' => '<p>This is a sample post on the Free Board. Feel free to write anything here.</p><p>You can customize board settings, skins, and permissions from the admin panel.</p>',
  'es' => '<p>Esta es una publicación de ejemplo en el Foro Libre. Siéntase libre de escribir cualquier cosa aquí.</p><p>Puede personalizar la configuración, skins y permisos desde el panel de administración.</p>',
  'fr' => '<p>Ceci est un exemple de publication sur le Forum Libre. N\'hésitez pas à écrire ce que vous voulez.</p><p>Vous pouvez personnaliser les paramètres, skins et permissions depuis le panneau d\'administration.</p>',
  'id' => '<p>Ini adalah postingan contoh di Papan Bebas. Silakan tulis apa saja di sini.</p><p>Anda dapat menyesuaikan pengaturan papan, skin, dan izin dari panel admin.</p>',
  'ja' => '<p>これは自由掲示板のサンプル投稿です。何でも自由に書いてみてください。</p><p>管理パネルから掲示板の設定、スキン、権限をカスタマイズできます。</p>',
  'mn' => '<p>Энэ бол Чөлөөт самбар дахь жишээ нийтлэл. Юу ч чөлөөтэй бичээрэй.</p><p>Админ панелаас самбарын тохиргоо, скин, зөвшөөрлийг тохируулах боломжтой.</p>',
  'ru' => '<p>Это пример записи на Свободной доске. Пишите здесь что угодно.</p><p>Вы можете настроить параметры доски, скины и разрешения в панели администратора.</p>',
  'tr' => '<p>Bu, Serbest Panodaki bir örnek gönderidir. Buraya istediğinizi yazabilirsiniz.</p><p>Yönetim panelinden pano ayarlarını, skinleri ve izinleri özelleştirebilirsiniz.</p>',
  'vi' => '<p>Đây là bài viết mẫu trên Bảng Tự do. Hãy viết bất cứ điều gì bạn muốn.</p><p>Bạn có thể tùy chỉnh cài đặt bảng, skin và quyền từ bảng quản trị.</p>',
  'zh_CN' => '<p>这是自由版块的示例帖子。随意写下您想说的话。</p><p>您可以从管理面板自定义版块设置、皮肤和权限。</p>',
  'zh_TW' => '<p>這是自由版塊的範例文章。隨意寫下您想說的話。</p><p>您可以從管理面板自訂版塊設定、面板和權限。</p>',
),
        ],
        [
            'board_slug'=>'notice',
            'cat_slug'=>'notice',
            'title'=>'VosCMS에 오신 것을 환영합니다!',
            'content'=>'<h2>VosCMS를 설치해 주셔서 감사합니다!</h2>
<p>VosCMS는 플러그인 기반의 오픈소스 CMS로, 13개 언어를 지원하며 유연한 레이아웃 시스템을 제공합니다.</p>
<h3>주요 기능</h3>
<ul>
<li><strong>플러그인 시스템</strong> — 마켓플레이스에서 플러그인을 설치하여 기능을 확장할 수 있습니다.</li>
<li><strong>위젯 빌더</strong> — 드래그 앤 드롭으로 페이지를 자유롭게 구성할 수 있습니다.</li>
<li><strong>다국어 지원</strong> — 13개 언어를 기본 지원하며, 추가 언어 확장이 가능합니다.</li>
<li><strong>다크 모드</strong> — 관리자 패널과 프론트엔드 모두 다크 모드를 지원합니다.</li>
<li><strong>스킨/테마</strong> — 다양한 스킨과 테마로 사이트 디자인을 변경할 수 있습니다.</li>
</ul>
<h3>시작하기</h3>
<p>관리자 패널에서 사이트 설정, 메뉴 관리, 페이지 편집, 플러그인 설치를 시작해 보세요.</p>
<p>더 자세한 정보는 <a href=\"https://voscms.com\">voscms.com</a>을 방문해 주세요.</p>',
            'nick_name'=>'VosCMS',
            'is_notice'=>1,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'de' => 'Willkommen bei VosCMS!',
  'en' => 'Welcome to VosCMS!',
  'es' => '¡Bienvenido a VosCMS!',
  'fr' => 'Bienvenue sur VosCMS !',
  'id' => 'Selamat datang di VosCMS!',
  'ja' => 'VosCMSへようこそ！',
  'mn' => 'VosCMS-д тавтай морилно уу!',
  'ru' => 'Добро пожаловать в VosCMS!',
  'tr' => 'VosCMS\'e hoş geldiniz!',
  'vi' => 'Chào mừng đến với VosCMS!',
  'zh_CN' => '欢迎使用 VosCMS！',
  'zh_TW' => '歡迎使用 VosCMS！',
),
            'content_translations'=>array (
  'de' => '<h2>Vielen Dank für die Installation von VosCMS!</h2><p>VosCMS ist ein plugin-basiertes Open-Source-CMS, das 13 Sprachen unterstützt und ein flexibles Layout-System bietet.</p><h3>Hauptfunktionen</h3><ul><li><strong>Plugin-System</strong> — Erweitern Sie die Funktionalität durch Plugins.</li><li><strong>Widget-Builder</strong> — Seiten frei per Drag-and-Drop erstellen.</li><li><strong>Mehrsprachig</strong> — 13 Sprachen standardmäßig.</li><li><strong>Dark Mode</strong> — Admin-Panel und Frontend unterstützen Dark Mode.</li><li><strong>Skins & Themes</strong> — Design mit Skins und Themes ändern.</li></ul>',
  'en' => '<h2>Thank you for installing VosCMS!</h2><p>VosCMS is a plugin-based open-source CMS that supports 13 languages and provides a flexible layout system.</p><h3>Key Features</h3><ul><li><strong>Plugin System</strong> — Extend functionality by installing plugins from the marketplace.</li><li><strong>Widget Builder</strong> — Build pages freely with drag-and-drop widgets.</li><li><strong>Multilingual</strong> — 13 languages supported out of the box.</li><li><strong>Dark Mode</strong> — Both admin panel and frontend support dark mode.</li><li><strong>Skins & Themes</strong> — Change your site design with various skins and themes.</li></ul><h3>Getting Started</h3><p>Start with site settings, menu management, page editing, and plugin installation from the admin panel.</p>',
  'es' => '<h2>¡Gracias por instalar VosCMS!</h2><p>VosCMS es un CMS de código abierto basado en plugins que admite 13 idiomas y ofrece un sistema de diseño flexible.</p><h3>Características principales</h3><ul><li><strong>Sistema de plugins</strong> — Amplíe la funcionalidad instalando plugins.</li><li><strong>Constructor de widgets</strong> — Cree páginas con arrastrar y soltar.</li><li><strong>Multilingüe</strong> — 13 idiomas soportados.</li><li><strong>Modo oscuro</strong> — Admin y frontend admiten modo oscuro.</li><li><strong>Skins y temas</strong> — Cambie el diseño con skins y temas.</li></ul>',
  'fr' => '<h2>Merci d\'avoir installé VosCMS !</h2><p>VosCMS est un CMS open source basé sur des plugins, prenant en charge 13 langues avec un système de mise en page flexible.</p><h3>Fonctionnalités</h3><ul><li><strong>Plugins</strong> — Étendez les fonctionnalités avec des plugins.</li><li><strong>Widgets</strong> — Créez des pages par glisser-déposer.</li><li><strong>Multilingue</strong> — 13 langues nativement.</li><li><strong>Mode sombre</strong> — Admin et frontend supportés.</li><li><strong>Skins et thèmes</strong> — Changez le design librement.</li></ul>',
  'id' => '<h2>Terima kasih telah menginstal VosCMS!</h2><p>VosCMS adalah CMS open source berbasis plugin yang mendukung 13 bahasa dengan sistem tata letak fleksibel.</p><h3>Fitur Utama</h3><ul><li><strong>Sistem Plugin</strong> — Perluas fungsionalitas dengan plugin.</li><li><strong>Widget Builder</strong> — Buat halaman dengan seret dan lepas.</li><li><strong>Multibahasa</strong> — 13 bahasa didukung.</li><li><strong>Mode Gelap</strong> — Admin dan frontend mendukung.</li><li><strong>Skin & Tema</strong> — Ubah desain dengan skin dan tema.</li></ul>',
  'ja' => '<h2>VosCMSをインストールしていただきありがとうございます！</h2><p>VosCMSはプラグインベースのオープンソースCMSで、13言語をサポートし、柔軟なレイアウトシステムを提供します。</p><h3>主な機能</h3><ul><li><strong>プラグインシステム</strong> — マーケットプレイスからプラグインをインストールして機能を拡張。</li><li><strong>ウィジェットビルダー</strong> — ドラッグ＆ドロップでページを自由に構成。</li><li><strong>多言語対応</strong> — 13言語を標準サポート。</li><li><strong>ダークモード</strong> — 管理パネルとフロントエンド両方対応。</li><li><strong>スキン・テーマ</strong> — 多様なスキンとテーマでデザイン変更可能。</li></ul><h3>はじめに</h3><p>管理パネルからサイト設定、メニュー管理、ページ編集、プラグインのインストールを始めましょう。</p>',
  'mn' => '<h2>VosCMS суулгасанд баярлалаа!</h2><p>VosCMS нь плагин дээр суурилсан нээлттэй эхийн CMS, 13 хэлийг дэмждэг, уян хатан загварын системтэй.</p><h3>Гол онцлогууд</h3><ul><li><strong>Плагин систем</strong> — Плагин суулгаж функц өргөтгөх.</li><li><strong>Виджет бүтээгч</strong> — Чирж буулгах аргаар хуудас бүтээх.</li><li><strong>Олон хэл</strong> — 13 хэл стандартаар.</li><li><strong>Харанхуй горим</strong> — Админ, фронтенд хоёулаа дэмждэг.</li><li><strong>Скин, тема</strong> — Дизайныг скин, темаар өөрчлөх.</li></ul>',
  'ru' => '<h2>Спасибо за установку VosCMS!</h2><p>VosCMS — CMS с открытым кодом на основе плагинов, поддерживает 13 языков и гибкую систему макетов.</p><h3>Основные возможности</h3><ul><li><strong>Система плагинов</strong> — Расширяйте функционал плагинами.</li><li><strong>Конструктор виджетов</strong> — Создавайте страницы перетаскиванием.</li><li><strong>Мультиязычность</strong> — 13 языков из коробки.</li><li><strong>Тёмная тема</strong> — Админка и фронтенд поддерживают.</li><li><strong>Скины и темы</strong> — Меняйте дизайн скинами и темами.</li></ul>',
  'tr' => '<h2>VosCMS\'i kurduğunuz için teşekkürler!</h2><p>VosCMS, 13 dili destekleyen eklenti tabanlı açık kaynak CMS\'dir.</p><h3>Temel Özellikler</h3><ul><li><strong>Eklenti Sistemi</strong> — Eklentilerle işlevselliği genişletin.</li><li><strong>Widget Oluşturucu</strong> — Sürükle-bırak ile sayfalar oluşturun.</li><li><strong>Çoklu Dil</strong> — 13 dil hazır.</li><li><strong>Karanlık Mod</strong> — Admin ve ön yüz destekler.</li><li><strong>Skinler ve Temalar</strong> — Tasarımı skinler ve temalarla değiştirin.</li></ul>',
  'vi' => '<h2>Cảm ơn bạn đã cài đặt VosCMS!</h2><p>VosCMS là CMS mã nguồn mở dựa trên plugin, hỗ trợ 13 ngôn ngữ với hệ thống bố cục linh hoạt.</p><h3>Tính năng chính</h3><ul><li><strong>Hệ thống Plugin</strong> — Mở rộng chức năng bằng plugin.</li><li><strong>Trình tạo Widget</strong> — Xây dựng trang với kéo và thả.</li><li><strong>Đa ngôn ngữ</strong> — 13 ngôn ngữ sẵn sàng.</li><li><strong>Chế độ tối</strong> — Admin và giao diện đều hỗ trợ.</li><li><strong>Skin & Giao diện</strong> — Thay đổi thiết kế với skin và giao diện.</li></ul>',
  'zh_CN' => '<h2>感谢您安装 VosCMS！</h2><p>VosCMS 是基于插件的开源 CMS，支持 13 种语言，提供灵活的布局系统。</p><h3>主要功能</h3><ul><li><strong>插件系统</strong> — 通过插件扩展功能。</li><li><strong>小部件构建器</strong> — 拖放构建页面。</li><li><strong>多语言</strong> — 开箱支持 13 种语言。</li><li><strong>暗黑模式</strong> — 管理面板和前端均支持。</li><li><strong>皮肤与主题</strong> — 用皮肤和主题更改设计。</li></ul>',
  'zh_TW' => '<h2>感謝您安裝 VosCMS！</h2><p>VosCMS 是基於外掛的開源 CMS，支援 13 種語言，提供靈活的版面系統。</p><h3>主要功能</h3><ul><li><strong>外掛系統</strong> — 透過外掛擴展功能。</li><li><strong>小工具建構器</strong> — 拖放建構頁面。</li><li><strong>多語言</strong> — 開箱支援 13 種語言。</li><li><strong>深色模式</strong> — 管理面板和前端均支援。</li><li><strong>面板與佈景主題</strong> — 用面板和佈景主題更改設計。</li></ul>',
),
        ],
        [
            'board_slug'=>'qna',
            'cat_slug'=>null,
            'title'=>'비밀번호를 어떻게 변경하나요?',
            'content'=>'<p>비밀번호를 잊어버렸습니다. 어떻게 재설정하나요?</p><p><strong>답변:</strong> 로그인 페이지에서 \"비밀번호 찾기\"를 클릭하고 등록된 이메일을 입력하세요. 비밀번호 재설정 링크가 이메일로 전송됩니다.</p>',
            'nick_name'=>'User',
            'is_notice'=>0,
            'original_locale'=>'ko',
            'title_translations'=>array (
  'de' => 'Wie ändere ich mein Passwort?',
  'en' => 'How do I change my password?',
  'es' => '¿Cómo cambio mi contraseña?',
  'fr' => 'Comment changer mon mot de passe ?',
  'id' => 'Bagaimana cara mengubah kata sandi?',
  'ja' => 'パスワードの変更方法は？',
  'mn' => 'Нууц үгээ хэрхэн солих вэ?',
  'ru' => 'Как изменить пароль?',
  'tr' => 'Şifremi nasıl değiştiririm?',
  'vi' => 'Làm sao để đổi mật khẩu?',
  'zh_CN' => '如何更改密码？',
  'zh_TW' => '如何變更密碼？',
),
            'content_translations'=>array (
  'de' => '<p>Ich habe mein Passwort vergessen. Wie kann ich es zurücksetzen?</p><p><strong>Antwort:</strong> Klicken Sie auf der Login-Seite auf „Passwort vergessen" und geben Sie Ihre registrierte E-Mail ein.</p>',
  'en' => '<p>I forgot my password. How can I reset it?</p><p><strong>Answer:</strong> Click "Forgot Password" on the login page and enter your registered email. A password reset link will be sent to your email.</p>',
  'es' => '<p>Olvidé mi contraseña. ¿Cómo puedo restablecerla?</p><p><strong>Respuesta:</strong> Haga clic en "¿Olvidó su contraseña?" en la página de inicio de sesión e ingrese su correo registrado.</p>',
  'fr' => '<p>J\'ai oublié mon mot de passe. Comment le réinitialiser ?</p><p><strong>Réponse :</strong> Cliquez sur « Mot de passe oublié » sur la page de connexion et entrez votre e-mail enregistré.</p>',
  'id' => '<p>Saya lupa kata sandi. Bagaimana cara meresetnya?</p><p><strong>Jawaban:</strong> Klik "Lupa Kata Sandi" di halaman login dan masukkan email terdaftar Anda.</p>',
  'ja' => '<p>パスワードを忘れてしまいました。リセットするにはどうすればいいですか？</p><p><strong>回答:</strong> ログインページの「パスワードをお忘れですか？」をクリックし、登録メールアドレスを入力してください。パスワードリセットリンクが送信されます。</p>',
  'mn' => '<p>Нууц үгээ мартсан. Хэрхэн шинэчлэх вэ?</p><p><strong>Хариулт:</strong> Нэвтрэх хуудаснаас "Нууц үг мартсан" дээр дарж, бүртгэлтэй имэйлээ оруулна уу.</p>',
  'ru' => '<p>Я забыл пароль. Как его сбросить?</p><p><strong>Ответ:</strong> Нажмите «Забыли пароль?» на странице входа и введите зарегистрированный email.</p>',
  'tr' => '<p>Şifremi unuttum. Nasıl sıfırlayabilirim?</p><p><strong>Cevap:</strong> Giriş sayfasında "Şifremi Unuttum" a tıklayın ve kayıtlı e-postanızı girin.</p>',
  'vi' => '<p>Tôi quên mật khẩu. Làm sao để đặt lại?</p><p><strong>Trả lời:</strong> Nhấn "Quên mật khẩu" trên trang đăng nhập và nhập email đã đăng ký.</p>',
  'zh_CN' => '<p>我忘记了密码。如何重置？</p><p><strong>回答：</strong>在登录页面点击"忘记密码"，输入注册邮箱即可收到重置链接。</p>',
  'zh_TW' => '<p>我忘記了密碼。如何重設？</p><p><strong>回答：</strong>在登入頁面點擊「忘記密碼」，輸入註冊信箱即可收到重設連結。</p>',
),
        ],
    ],
];
