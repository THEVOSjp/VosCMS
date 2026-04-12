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
  'de' => 'Ist VosCMS kostenlos?',
  'en' => 'VosCMS is free?',
  'es' => '¿VosCMS es gratuito?',
  'fr' => 'VosCMS est-il gratuit ?',
  'id' => 'Apakah VosCMS gratis?',
  'ja' => 'VosCMSは無料ですか？',
  'mn' => 'VosCMS үнэгүй юу?',
  'ru' => 'VosCMS бесплатна?',
  'tr' => 'VosCMS ücretsiz mi?',
  'vi' => 'VosCMS có miễn phí không?',
  'zh_CN' => 'VosCMS 是免费的吗？',
  'zh_TW' => 'VosCMS 是免費的嗎？',
),
            'content_translations'=>array (
  'de' => '<p>Ja, der VosCMS-Kern ist vollständig kostenlos und Open Source (GPLv2). Jeder kann ihn frei herunterladen und verwenden.</p><p>Kostenpflichtige Plugins und Themes können separat im Marktplatz erworben werden.</p>',
  'en' => '<p>Yes, VosCMS core is completely free and open-source (GPLv2). Anyone can freely download and use it.</p><p>Paid plugins and themes can be purchased separately from the marketplace.</p>',
  'es' => '<p>Sí, el núcleo de VosCMS es completamente gratuito y de código abierto (GPLv2). Cualquier persona puede descargarlo y usarlo libremente.</p><p>Los plugins y temas de pago se pueden adquirir por separado en el marketplace.</p>',
  'fr' => '<p>Oui, le cœur de VosCMS est entièrement gratuit et open source (GPLv2). Tout le monde peut le télécharger et l\'utiliser librement.</p><p>Les plugins et thèmes payants peuvent être achetés séparément sur le marketplace.</p>',
  'id' => '<p>Ya, inti VosCMS sepenuhnya gratis dan open source (GPLv2). Siapa saja dapat mengunduh dan menggunakannya secara bebas.</p><p>Plugin dan tema berbayar dapat dibeli terpisah di marketplace.</p>',
  'ja' => '<p>はい、VosCMSコアは完全無料でオープンソース（GPLv2）です。誰でも自由にダウンロードして使用できます。</p><p>有料プラグインやテーマはマーケットプレイスで別途購入可能です。</p>',
  'mn' => '<p>Тийм, VosCMS-ийн цөм бүрэн үнэгүй бөгөөд нээлттэй эх (GPLv2) юм. Хэн ч чөлөөтэй татаж авч ашиглах боломжтой.</p><p>Төлбөртэй плагин болон тема маркетплейсээс тусад нь худалдан авах боломжтой.</p>',
  'ru' => '<p>Да, ядро VosCMS полностью бесплатно и имеет открытый исходный код (GPLv2). Любой может свободно скачать и использовать его.</p><p>Платные плагины и темы можно приобрести отдельно на маркетплейсе.</p>',
  'tr' => '<p>Evet, VosCMS çekirdeği tamamen ücretsiz ve açık kaynaklıdır (GPLv2). Herkes özgürce indirip kullanabilir.</p><p>Ücretli eklentiler ve temalar pazaryerinden ayrıca satın alınabilir.</p>',
  'vi' => '<p>Có, lõi VosCMS hoàn toàn miễn phí và mã nguồn mở (GPLv2). Bất kỳ ai cũng có thể tải về và sử dụng tự do.</p><p>Các plugin và giao diện trả phí có thể mua riêng trên marketplace.</p>',
  'zh_CN' => '<p>是的，VosCMS 核心完全免费且开源（GPLv2）。任何人都可以自由下载和使用。</p><p>付费插件和主题可以在市场中单独购买。</p>',
  'zh_TW' => '<p>是的，VosCMS 核心完全免費且開源（GPLv2）。任何人都可以自由下載和使用。</p><p>付費外掛和佈景主題可以在市場中單獨購買。</p>',
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
  'de' => 'Welche Serverumgebung wird benötigt?',
  'en' => 'What server requirements are needed?',
  'es' => '¿Qué entorno de servidor se necesita?',
  'fr' => 'Quel environnement serveur est requis ?',
  'id' => 'Lingkungan server apa yang dibutuhkan?',
  'ja' => 'どのようなサーバー環境が必要ですか？',
  'mn' => 'Ямар серверийн орчин шаардлагатай вэ?',
  'ru' => 'Какие серверные требования необходимы?',
  'tr' => 'Hangi sunucu ortamı gereklidir?',
  'vi' => 'Cần môi trường máy chủ nào?',
  'zh_CN' => '需要什么服务器环境？',
  'zh_TW' => '需要什麼伺服器環境？',
),
            'content_translations'=>array (
  'de' => '<p>Für VosCMS wird folgende Umgebung benötigt:</p>
<h4>Voraussetzungen</h4>
<ul>
<li><strong>PHP 8.1+</strong> (8.3 empfohlen)</li>
<li><strong>MySQL 8.0+</strong> oder <strong>MariaDB 10.4+</strong></li>
<li><strong>Nginx</strong> oder <strong>Apache</strong></li>
<li><strong>HTTPS</strong> (SSL-Zertifikat erforderlich — keine Installation auf localhost)</li>
</ul>
<h4>Erforderliche PHP-Erweiterungen</h4>
<ul>
<li>PDO, mbstring, json, openssl, curl, zip</li>
<li><strong>ionCube Loader</strong> — Erforderlich für Lizenzauthentifizierung und Kerncode-Schutz. ionCube Loader muss auf Ihrem Server installiert sein.</li>
</ul>
<h4>ionCube Loader Installation</h4>
<p>ionCube Loader wird zum Schutz des VosCMS-Lizenzclients und der Kerndateien verwendet.</p>
<ul>
<li><strong>VPS/Dediziert:</strong> Laden Sie <code>ioncube_loaders_lin_x86-64.tar.gz</code> herunter, installieren Sie es im PHP-Erweiterungsverzeichnis und fügen Sie <code>zend_extension=ioncube_loader_lin_8.3.so</code> in der php.ini hinzu.</li>
<li><strong>Shared Hosting:</strong> Bitten Sie Ihren Hosting-Anbieter, ionCube Loader zu aktivieren. Einige Shared-Hosting-Anbieter unterstützen dies möglicherweise nicht.</li>
<li><strong>Überprüfung:</strong> Führen Sie <code>php -m | grep ionCube</code> aus, um die Installation zu überprüfen.</li>
</ul>
<h4>Empfohlene Serverspezifikationen</h4>
<ul>
<li>2 vCPU, 2 GB RAM, 40 GB SSD oder mehr</li>
<li>Ubuntu 22.04+ oder Rocky Linux 8+</li>
</ul>',
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
  'es' => '<p>VosCMS requiere el siguiente entorno:</p>
<h4>Requisitos</h4>
<ul>
<li><strong>PHP 8.1+</strong> (8.3 recomendado)</li>
<li><strong>MySQL 8.0+</strong> o <strong>MariaDB 10.4+</strong></li>
<li><strong>Nginx</strong> o <strong>Apache</strong></li>
<li><strong>HTTPS</strong> (certificado SSL obligatorio — no se puede instalar en localhost)</li>
</ul>
<h4>Extensiones PHP requeridas</h4>
<ul>
<li>PDO, mbstring, json, openssl, curl, zip</li>
<li><strong>ionCube Loader</strong> — Necesario para la autenticación de licencias y la protección del código principal. ionCube Loader debe estar instalado en su servidor.</li>
</ul>
<h4>Instalación de ionCube Loader</h4>
<p>ionCube Loader se utiliza para proteger el cliente de licencias y los archivos principales de VosCMS.</p>
<ul>
<li><strong>VPS/Dedicado:</strong> Descargue <code>ioncube_loaders_lin_x86-64.tar.gz</code>, instálelo en el directorio de extensiones de PHP y agregue <code>zend_extension=ioncube_loader_lin_8.3.so</code> en php.ini.</li>
<li><strong>Hosting compartido:</strong> Solicite a su proveedor de hosting que habilite ionCube Loader. Algunos hostings compartidos pueden no soportarlo.</li>
<li><strong>Verificar:</strong> Ejecute <code>php -m | grep ionCube</code> para comprobar la instalación.</li>
</ul>
<h4>Especificaciones recomendadas del servidor</h4>
<ul>
<li>2 vCPU, 2 GB RAM, 40 GB SSD o más</li>
<li>Ubuntu 22.04+ o Rocky Linux 8+</li>
</ul>',
  'fr' => '<p>VosCMS nécessite l\'environnement suivant :</p>
<h4>Prérequis</h4>
<ul>
<li><strong>PHP 8.1+</strong> (8.3 recommandé)</li>
<li><strong>MySQL 8.0+</strong> ou <strong>MariaDB 10.4+</strong></li>
<li><strong>Nginx</strong> ou <strong>Apache</strong></li>
<li><strong>HTTPS</strong> (certificat SSL requis — installation impossible sur localhost)</li>
</ul>
<h4>Extensions PHP requises</h4>
<ul>
<li>PDO, mbstring, json, openssl, curl, zip</li>
<li><strong>ionCube Loader</strong> — Requis pour l\'authentification des licences et la protection du code principal. ionCube Loader doit être installé sur votre serveur.</li>
</ul>
<h4>Installation d\'ionCube Loader</h4>
<p>ionCube Loader est utilisé pour protéger le client de licence et les fichiers principaux de VosCMS.</p>
<ul>
<li><strong>VPS/Dédié :</strong> Téléchargez <code>ioncube_loaders_lin_x86-64.tar.gz</code>, installez-le dans le répertoire des extensions PHP et ajoutez <code>zend_extension=ioncube_loader_lin_8.3.so</code> dans php.ini.</li>
<li><strong>Hébergement mutualisé :</strong> Demandez à votre hébergeur d\'activer ionCube Loader. Certains hébergements mutualisés peuvent ne pas le supporter.</li>
<li><strong>Vérification :</strong> Exécutez <code>php -m | grep ionCube</code> pour vérifier l\'installation.</li>
</ul>
<h4>Spécifications serveur recommandées</h4>
<ul>
<li>2 vCPU, 2 Go RAM, 40 Go SSD ou plus</li>
<li>Ubuntu 22.04+ ou Rocky Linux 8+</li>
</ul>',
  'id' => '<p>VosCMS membutuhkan lingkungan berikut:</p>
<h4>Persyaratan</h4>
<ul>
<li><strong>PHP 8.1+</strong> (8.3 direkomendasikan)</li>
<li><strong>MySQL 8.0+</strong> atau <strong>MariaDB 10.4+</strong></li>
<li><strong>Nginx</strong> atau <strong>Apache</strong></li>
<li><strong>HTTPS</strong> (sertifikat SSL wajib — tidak dapat diinstal di localhost)</li>
</ul>
<h4>Ekstensi PHP yang diperlukan</h4>
<ul>
<li>PDO, mbstring, json, openssl, curl, zip</li>
<li><strong>ionCube Loader</strong> — Diperlukan untuk autentikasi lisensi dan perlindungan kode inti. ionCube Loader harus diinstal di server Anda.</li>
</ul>
<h4>Instalasi ionCube Loader</h4>
<p>ionCube Loader digunakan untuk melindungi klien lisensi dan file inti VosCMS.</p>
<ul>
<li><strong>VPS/Dedicated:</strong> Unduh <code>ioncube_loaders_lin_x86-64.tar.gz</code>, instal ke direktori ekstensi PHP, dan tambahkan <code>zend_extension=ioncube_loader_lin_8.3.so</code> di php.ini.</li>
<li><strong>Shared Hosting:</strong> Minta penyedia hosting Anda untuk mengaktifkan ionCube Loader. Beberapa shared hosting mungkin tidak mendukungnya.</li>
<li><strong>Verifikasi:</strong> Jalankan <code>php -m | grep ionCube</code> untuk memeriksa instalasi.</li>
</ul>
<h4>Spesifikasi server yang direkomendasikan</h4>
<ul>
<li>2 vCPU, 2 GB RAM, 40 GB SSD atau lebih</li>
<li>Ubuntu 22.04+ atau Rocky Linux 8+</li>
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
  'mn' => '<p>VosCMS ажиллуулахад дараах орчин шаардлагатай:</p>
<h4>Шаардлага</h4>
<ul>
<li><strong>PHP 8.1+</strong> (8.3 санал болгосон)</li>
<li><strong>MySQL 8.0+</strong> эсвэл <strong>MariaDB 10.4+</strong></li>
<li><strong>Nginx</strong> эсвэл <strong>Apache</strong></li>
<li><strong>HTTPS</strong> (SSL сертификат заавал — localhost дээр суулгах боломжгүй)</li>
</ul>
<h4>Шаардлагатай PHP өргөтгөлүүд</h4>
<ul>
<li>PDO, mbstring, json, openssl, curl, zip</li>
<li><strong>ionCube Loader</strong> — Лицензийн баталгаажуулалт болон үндсэн кодын хамгаалалтад шаардлагатай. Серверт ionCube Loader суулгасан байх ёстой.</li>
</ul>
<h4>ionCube Loader суулгах</h4>
<p>ionCube Loader нь VosCMS-ийн лицензийн клиент болон үндсэн файлуудыг хамгаалахад ашиглагддаг.</p>
<ul>
<li><strong>VPS/Зориулалтын:</strong> <code>ioncube_loaders_lin_x86-64.tar.gz</code> татаж аваад PHP өргөтгөлийн хавтаст суулгаж, php.ini-д <code>zend_extension=ioncube_loader_lin_8.3.so</code> нэмнэ.</li>
<li><strong>Хуваалцсан хостинг:</strong> Хостинг нийлүүлэгчээсээ ionCube Loader идэвхжүүлэхийг хүснэ үү. Зарим хуваалцсан хостинг дэмжихгүй байж болно.</li>
<li><strong>Шалгах:</strong> <code>php -m | grep ionCube</code> командаар шалгах боломжтой.</li>
</ul>
<h4>Санал болгох серверийн тохиргоо</h4>
<ul>
<li>2 vCPU, 2 GB RAM, 40 GB SSD ба түүнээс дээш</li>
<li>Ubuntu 22.04+ эсвэл Rocky Linux 8+</li>
</ul>',
  'ru' => '<p>Для работы VosCMS необходима следующая среда:</p>
<h4>Требования</h4>
<ul>
<li><strong>PHP 8.1+</strong> (рекомендуется 8.3)</li>
<li><strong>MySQL 8.0+</strong> или <strong>MariaDB 10.4+</strong></li>
<li><strong>Nginx</strong> или <strong>Apache</strong></li>
<li><strong>HTTPS</strong> (SSL-сертификат обязателен — установка на localhost невозможна)</li>
</ul>
<h4>Обязательные расширения PHP</h4>
<ul>
<li>PDO, mbstring, json, openssl, curl, zip</li>
<li><strong>ionCube Loader</strong> — Необходим для аутентификации лицензий и защиты основного кода. ionCube Loader должен быть установлен на вашем сервере.</li>
</ul>
<h4>Установка ionCube Loader</h4>
<p>ionCube Loader используется для защиты лицензионного клиента и основных файлов VosCMS.</p>
<ul>
<li><strong>VPS/Выделенный:</strong> Загрузите <code>ioncube_loaders_lin_x86-64.tar.gz</code>, установите в директорию расширений PHP и добавьте <code>zend_extension=ioncube_loader_lin_8.3.so</code> в php.ini.</li>
<li><strong>Общий хостинг:</strong> Попросите вашего хостинг-провайдера включить ionCube Loader. Некоторые общие хостинги могут не поддерживать его.</li>
<li><strong>Проверка:</strong> Выполните <code>php -m | grep ionCube</code> для проверки установки.</li>
</ul>
<h4>Рекомендуемые характеристики сервера</h4>
<ul>
<li>2 vCPU, 2 ГБ RAM, 40 ГБ SSD или больше</li>
<li>Ubuntu 22.04+ или Rocky Linux 8+</li>
</ul>',
  'tr' => '<p>VosCMS için aşağıdaki ortam gereklidir:</p>
<h4>Gereksinimler</h4>
<ul>
<li><strong>PHP 8.1+</strong> (8.3 önerilir)</li>
<li><strong>MySQL 8.0+</strong> veya <strong>MariaDB 10.4+</strong></li>
<li><strong>Nginx</strong> veya <strong>Apache</strong></li>
<li><strong>HTTPS</strong> (SSL sertifikası zorunlu — localhost üzerine kurulamaz)</li>
</ul>
<h4>Gerekli PHP Uzantıları</h4>
<ul>
<li>PDO, mbstring, json, openssl, curl, zip</li>
<li><strong>ionCube Loader</strong> — Lisans doğrulama ve çekirdek kod koruması için gereklidir. Sunucunuzda ionCube Loader kurulu olmalıdır.</li>
</ul>
<h4>ionCube Loader Kurulumu</h4>
<p>ionCube Loader, VosCMS lisans istemcisi ve çekirdek dosyalarını korumak için kullanılır.</p>
<ul>
<li><strong>VPS/Özel:</strong> <code>ioncube_loaders_lin_x86-64.tar.gz</code> dosyasını indirin, PHP uzantı dizinine kurun ve php.ini\'ye <code>zend_extension=ioncube_loader_lin_8.3.so</code> ekleyin.</li>
<li><strong>Paylaşımlı Hosting:</strong> Hosting sağlayıcınızdan ionCube Loader\'ı etkinleştirmesini isteyin. Bazı paylaşımlı hostingler desteklemeyebilir.</li>
<li><strong>Doğrulama:</strong> Kurulumu kontrol etmek için <code>php -m | grep ionCube</code> komutunu çalıştırın.</li>
</ul>
<h4>Önerilen Sunucu Özellikleri</h4>
<ul>
<li>2 vCPU, 2 GB RAM, 40 GB SSD veya daha fazla</li>
<li>Ubuntu 22.04+ veya Rocky Linux 8+</li>
</ul>',
  'vi' => '<p>VosCMS yêu cầu môi trường sau:</p>
<h4>Yêu cầu</h4>
<ul>
<li><strong>PHP 8.1+</strong> (khuyến nghị 8.3)</li>
<li><strong>MySQL 8.0+</strong> hoặc <strong>MariaDB 10.4+</strong></li>
<li><strong>Nginx</strong> hoặc <strong>Apache</strong></li>
<li><strong>HTTPS</strong> (bắt buộc chứng chỉ SSL — không thể cài đặt trên localhost)</li>
</ul>
<h4>Phần mở rộng PHP bắt buộc</h4>
<ul>
<li>PDO, mbstring, json, openssl, curl, zip</li>
<li><strong>ionCube Loader</strong> — Cần thiết cho xác thực giấy phép và bảo vệ mã nguồn lõi. ionCube Loader phải được cài đặt trên máy chủ của bạn.</li>
</ul>
<h4>Cài đặt ionCube Loader</h4>
<p>ionCube Loader được sử dụng để bảo vệ máy khách giấy phép và các tệp lõi của VosCMS.</p>
<ul>
<li><strong>VPS/Dedicated:</strong> Tải <code>ioncube_loaders_lin_x86-64.tar.gz</code>, cài vào thư mục extension PHP và thêm <code>zend_extension=ioncube_loader_lin_8.3.so</code> vào php.ini.</li>
<li><strong>Shared Hosting:</strong> Yêu cầu nhà cung cấp hosting kích hoạt ionCube Loader. Một số shared hosting có thể không hỗ trợ.</li>
<li><strong>Xác minh:</strong> Chạy <code>php -m | grep ionCube</code> để kiểm tra cài đặt.</li>
</ul>
<h4>Thông số máy chủ khuyến nghị</h4>
<ul>
<li>2 vCPU, 2 GB RAM, 40 GB SSD trở lên</li>
<li>Ubuntu 22.04+ hoặc Rocky Linux 8+</li>
</ul>',
  'zh_CN' => '<p>VosCMS 需要以下环境：</p>
<h4>必备条件</h4>
<ul>
<li><strong>PHP 8.1+</strong>（推荐 8.3）</li>
<li><strong>MySQL 8.0+</strong> 或 <strong>MariaDB 10.4+</strong></li>
<li><strong>Nginx</strong> 或 <strong>Apache</strong></li>
<li><strong>HTTPS</strong>（必须有 SSL 证书 — 无法在 localhost 上安装）</li>
</ul>
<h4>必需的 PHP 扩展</h4>
<ul>
<li>PDO、mbstring、json、openssl、curl、zip</li>
<li><strong>ionCube Loader</strong> — 用于许可证认证和核心代码保护。服务器上必须安装 ionCube Loader。</li>
</ul>
<h4>ionCube Loader 安装</h4>
<p>ionCube Loader 用于保护 VosCMS 许可证客户端和核心文件。</p>
<ul>
<li><strong>VPS/独立服务器：</strong>下载 <code>ioncube_loaders_lin_x86-64.tar.gz</code>，安装到 PHP 扩展目录，并在 php.ini 中添加 <code>zend_extension=ioncube_loader_lin_8.3.so</code>。</li>
<li><strong>共享主机：</strong>请联系您的主机提供商启用 ionCube Loader。部分共享主机可能不支持。</li>
<li><strong>验证：</strong>运行 <code>php -m | grep ionCube</code> 检查安装情况。</li>
</ul>
<h4>推荐服务器配置</h4>
<ul>
<li>2 vCPU、2 GB RAM、40 GB SSD 或以上</li>
<li>Ubuntu 22.04+ 或 Rocky Linux 8+</li>
</ul>',
  'zh_TW' => '<p>VosCMS 需要以下環境：</p>
<h4>必備條件</h4>
<ul>
<li><strong>PHP 8.1+</strong>（建議 8.3）</li>
<li><strong>MySQL 8.0+</strong> 或 <strong>MariaDB 10.4+</strong></li>
<li><strong>Nginx</strong> 或 <strong>Apache</strong></li>
<li><strong>HTTPS</strong>（必須有 SSL 憑證 — 無法在 localhost 上安裝）</li>
</ul>
<h4>必要的 PHP 擴充</h4>
<ul>
<li>PDO、mbstring、json、openssl、curl、zip</li>
<li><strong>ionCube Loader</strong> — 用於授權認證和核心程式碼保護。伺服器上必須安裝 ionCube Loader。</li>
</ul>
<h4>ionCube Loader 安裝</h4>
<p>ionCube Loader 用於保護 VosCMS 授權用戶端和核心檔案。</p>
<ul>
<li><strong>VPS/獨立伺服器：</strong>下載 <code>ioncube_loaders_lin_x86-64.tar.gz</code>，安裝到 PHP 擴充目錄，並在 php.ini 中加入 <code>zend_extension=ioncube_loader_lin_8.3.so</code>。</li>
<li><strong>共享主機：</strong>請聯繫您的主機供應商啟用 ionCube Loader。部分共享主機可能不支援。</li>
<li><strong>驗證：</strong>執行 <code>php -m | grep ionCube</code> 檢查安裝情況。</li>
</ul>
<h4>建議伺服器規格</h4>
<ul>
<li>2 vCPU、2 GB RAM、40 GB SSD 或以上</li>
<li>Ubuntu 22.04+ 或 Rocky Linux 8+</li>
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
  'de' => 'Wie installiere ich Plugins?',
  'en' => 'How do I install plugins?',
  'es' => '¿Cómo instalo plugins?',
  'fr' => 'Comment installer des plugins ?',
  'id' => 'Bagaimana cara menginstal plugin?',
  'ja' => 'プラグインはどうやってインストールしますか？',
  'mn' => 'Плагиныг хэрхэн суулгах вэ?',
  'ru' => 'Как установить плагины?',
  'tr' => 'Eklentileri nasıl kurarım?',
  'vi' => 'Làm sao để cài đặt plugin?',
  'zh_CN' => '如何安装插件？',
  'zh_TW' => '如何安裝外掛？',
),
            'content_translations'=>array (
  'de' => '<p>Gehen Sie im Admin-Panel zu <strong>Automatische Installation</strong>, um Plugins aus dem Marktplatz zu suchen und mit einem Klick zu installieren.</p><p>Alternativ können Sie die ZIP-Datei herunterladen, in das Verzeichnis <code>plugins/</code> hochladen und über Admin > Plugin-Verwaltung aktivieren.</p>',
  'en' => '<p>Go to <strong>Auto Install</strong> in the admin panel to search and install plugins from the marketplace with one click.</p><p>Or download the ZIP file, upload it to the <code>plugins/</code> directory, and activate it from Admin > Plugin Management.</p>',
  'es' => '<p>Vaya a <strong>Instalación automática</strong> en el panel de administración para buscar e instalar plugins del marketplace con un clic.</p><p>También puede descargar el archivo ZIP, subirlo al directorio <code>plugins/</code> y activarlo desde Admin > Gestión de plugins.</p>',
  'fr' => '<p>Accédez à <strong>Installation automatique</strong> dans le panneau d\'administration pour rechercher et installer des plugins depuis le marketplace en un clic.</p><p>Vous pouvez également télécharger le fichier ZIP, le placer dans le répertoire <code>plugins/</code> et l\'activer depuis Admin > Gestion des plugins.</p>',
  'id' => '<p>Buka <strong>Instal Otomatis</strong> di panel admin untuk mencari dan menginstal plugin dari marketplace dengan satu klik.</p><p>Atau unduh file ZIP, unggah ke direktori <code>plugins/</code>, dan aktifkan dari Admin > Manajemen Plugin.</p>',
  'ja' => '<p>管理パネルの<strong>自動インストール</strong>メニューで、マーケットプレイスのプラグインを検索してワンクリックでインストールできます。</p><p>またはZIPファイルをダウンロードして<code>plugins/</code>ディレクトリにアップロードし、管理パネル > プラグイン管理で有効化することもできます。</p>',
  'mn' => '<p>Админ панелын <strong>Автомат суулгалт</strong> хэсэгт орж маркетплейсээс плагин хайж нэг товшилтоор суулгах боломжтой.</p><p>Мөн ZIP файлыг татаж аваад <code>plugins/</code> хавтаст байршуулж, Админ > Плагин удирдлагаас идэвхжүүлж болно.</p>',
  'ru' => '<p>Перейдите в <strong>Автоустановка</strong> в панели администратора, чтобы найти и установить плагины из маркетплейса одним щелчком.</p><p>Или скачайте ZIP-файл, загрузите его в директорию <code>plugins/</code> и активируйте через Админ > Управление плагинами.</p>',
  'tr' => '<p>Yönetim panelinde <strong>Otomatik Kurulum</strong> bölümüne giderek pazaryerindeki eklentileri arayın ve tek tıkla kurun.</p><p>Veya ZIP dosyasını indirip <code>plugins/</code> dizinine yükleyin ve Yönetim > Eklenti Yönetimi\'nden etkinleştirin.</p>',
  'vi' => '<p>Truy cập <strong>Cài đặt tự động</strong> trong bảng quản trị để tìm kiếm và cài đặt plugin từ marketplace chỉ với một cú nhấp.</p><p>Hoặc tải file ZIP, tải lên thư mục <code>plugins/</code> và kích hoạt từ Admin > Quản lý Plugin.</p>',
  'zh_CN' => '<p>在管理面板中进入<strong>自动安装</strong>，即可从市场搜索并一键安装插件。</p><p>或者下载 ZIP 文件，上传到 <code>plugins/</code> 目录，然后在管理员 > 插件管理中激活。</p>',
  'zh_TW' => '<p>在管理面板中進入<strong>自動安裝</strong>，即可從市場搜尋並一鍵安裝外掛。</p><p>或者下載 ZIP 檔案，上傳到 <code>plugins/</code> 目錄，然後在管理員 > 外掛管理中啟用。</p>',
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
  'de' => 'Wie richte ich die Mehrsprachigkeit ein?',
  'en' => 'How do I set up multilingual?',
  'es' => '¿Cómo configuro el multilingüe?',
  'fr' => 'Comment configurer le multilingue ?',
  'id' => 'Bagaimana cara mengatur multibahasa?',
  'ja' => '多言語はどう設定しますか？',
  'mn' => 'Олон хэлийг хэрхэн тохируулах вэ?',
  'ru' => 'Как настроить мультиязычность?',
  'tr' => 'Çoklu dil desteğini nasıl ayarlarım?',
  'vi' => 'Làm sao để thiết lập đa ngôn ngữ?',
  'zh_CN' => '如何设置多语言？',
  'zh_TW' => '如何設定多語言？',
),
            'content_translations'=>array (
  'de' => '<p>VosCMS unterstützt standardmäßig 13 Sprachen. Sie können die unterstützten Sprachen unter Admin > Einstellungen > Sprache auswählen.</p><p>Beiträge, Menüs und Seiten unterstützen alle Mehrsprachigkeit. Wenn Benutzer die Sprache wechseln, werden die Übersetzungen automatisch angezeigt.</p>',
  'en' => '<p>VosCMS supports 13 languages by default. You can select supported languages in Admin > Settings > Language.</p><p>Posts, menus, and pages all support multilingual. When users switch languages, translations are displayed automatically.</p>',
  'es' => '<p>VosCMS soporta 13 idiomas de forma predeterminada. Puede seleccionar los idiomas compatibles en Admin > Configuración > Idioma.</p><p>Las publicaciones, menús y páginas admiten multilingüe. Cuando los usuarios cambian de idioma, las traducciones se muestran automáticamente.</p>',
  'fr' => '<p>VosCMS prend en charge 13 langues par défaut. Vous pouvez sélectionner les langues supportées dans Admin > Paramètres > Langue.</p><p>Les articles, menus et pages prennent tous en charge le multilingue. Lorsque les utilisateurs changent de langue, les traductions s\'affichent automatiquement.</p>',
  'id' => '<p>VosCMS mendukung 13 bahasa secara default. Anda dapat memilih bahasa yang didukung di Admin > Pengaturan > Bahasa.</p><p>Postingan, menu, dan halaman semuanya mendukung multibahasa. Ketika pengguna beralih bahasa, terjemahan ditampilkan secara otomatis.</p>',
  'ja' => '<p>VosCMSは標準で13言語をサポートしています。管理パネル > 設定 > 言語設定でサポート言語を選択できます。</p><p>投稿、メニュー、ページすべてが多言語対応しており、ユーザーが言語を切り替えると自動的に翻訳が表示されます。</p>',
  'mn' => '<p>VosCMS нь анхдагчаар 13 хэлийг дэмждэг. Админ > Тохиргоо > Хэл хэсгээс дэмжих хэлүүдийг сонгож болно.</p><p>Нийтлэл, цэс, хуудас бүгд олон хэлийг дэмждэг. Хэрэглэгч хэл солиход орчуулга автоматаар харагдана.</p>',
  'ru' => '<p>VosCMS по умолчанию поддерживает 13 языков. Вы можете выбрать поддерживаемые языки в разделе Админ > Настройки > Язык.</p><p>Записи, меню и страницы поддерживают мультиязычность. При переключении языка пользователем переводы отображаются автоматически.</p>',
  'tr' => '<p>VosCMS varsayılan olarak 13 dili destekler. Desteklenen dilleri Yönetim > Ayarlar > Dil bölümünden seçebilirsiniz.</p><p>Gönderiler, menüler ve sayfaların tümü çoklu dil desteğine sahiptir. Kullanıcılar dil değiştirdiğinde çeviriler otomatik olarak gösterilir.</p>',
  'vi' => '<p>VosCMS hỗ trợ 13 ngôn ngữ theo mặc định. Bạn có thể chọn các ngôn ngữ được hỗ trợ tại Admin > Cài đặt > Ngôn ngữ.</p><p>Bài viết, menu và trang đều hỗ trợ đa ngôn ngữ. Khi người dùng chuyển ngôn ngữ, bản dịch sẽ được hiển thị tự động.</p>',
  'zh_CN' => '<p>VosCMS 默认支持 13 种语言。您可以在管理员 > 设置 > 语言中选择支持的语言。</p><p>文章、菜单和页面都支持多语言。当用户切换语言时，翻译会自动显示。</p>',
  'zh_TW' => '<p>VosCMS 預設支援 13 種語言。您可以在管理員 > 設定 > 語言中選擇支援的語言。</p><p>文章、選單和頁面都支援多語言。當使用者切換語言時，翻譯會自動顯示。</p>',
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
  'de' => 'Wie verwende ich Widgets?',
  'en' => 'How do I use widgets?',
  'es' => '¿Cómo uso los widgets?',
  'fr' => 'Comment utiliser les widgets ?',
  'id' => 'Bagaimana cara menggunakan widget?',
  'ja' => 'ウィジェットはどう使いますか？',
  'mn' => 'Виджетийг хэрхэн ашиглах вэ?',
  'ru' => 'Как использовать виджеты?',
  'tr' => 'Widget\'ları nasıl kullanırım?',
  'vi' => 'Làm sao để sử dụng widget?',
  'zh_CN' => '如何使用小部件？',
  'zh_TW' => '如何使用小工具？',
),
            'content_translations'=>array (
  'de' => '<p>Gehen Sie zu Admin > Website > Seitenverwaltung, wählen Sie eine Seite und nutzen Sie den Widget-Builder.</p><p>Platzieren Sie Widgets per Drag-and-Drop und passen Sie die Einstellungen jedes Widgets an. Es stehen verschiedene Widgets zur Verfügung, darunter Hero-Banner, Funktionsübersichten, Statistiken und CTA.</p>',
  'en' => '<p>Go to Admin > Site > Page Management, select a page and use the Widget Builder.</p><p>Drag and drop widgets, and customize each widget\'s settings. Various widgets are available including hero banners, features, stats, and CTA.</p>',
  'es' => '<p>Vaya a Admin > Sitio > Gestión de páginas, seleccione una página y use el Constructor de widgets.</p><p>Arrastre y suelte widgets y personalice la configuración de cada uno. Hay diversos widgets disponibles, como banners hero, funciones, estadísticas y CTA.</p>',
  'fr' => '<p>Allez dans Admin > Site > Gestion des pages, sélectionnez une page et utilisez le Constructeur de widgets.</p><p>Glissez-déposez les widgets et personnalisez les paramètres de chacun. Divers widgets sont disponibles : bannières hero, fonctionnalités, statistiques et CTA.</p>',
  'id' => '<p>Buka Admin > Situs > Manajemen Halaman, pilih halaman dan gunakan Widget Builder.</p><p>Seret dan lepas widget, lalu sesuaikan pengaturan masing-masing widget. Tersedia berbagai widget termasuk banner hero, fitur, statistik, dan CTA.</p>',
  'ja' => '<p>管理パネル > サイト > ページ管理で、ページを選択してウィジェットビルダーを使用します。</p><p>ドラッグ＆ドロップでウィジェットを配置し、各ウィジェットの設定をカスタマイズできます。ヒーローバナー、機能紹介、統計、CTAなど多彩なウィジェットが用意されています。</p>',
  'mn' => '<p>Админ > Сайт > Хуудас удирдлага руу ороод хуудас сонгоод Виджет бүтээгчийг ашиглана.</p><p>Виджетүүдийг чирж буулгаж байрлуулаад тус бүрийн тохиргоог өөрчилнө. Хийро баннер, онцлог, статистик, CTA зэрэг олон төрлийн виджет бэлэн байна.</p>',
  'ru' => '<p>Перейдите в Админ > Сайт > Управление страницами, выберите страницу и используйте Конструктор виджетов.</p><p>Перетаскивайте виджеты и настраивайте параметры каждого из них. Доступны различные виджеты: героические баннеры, функции, статистика и CTA.</p>',
  'tr' => '<p>Yönetim > Site > Sayfa Yönetimi\'ne gidin, bir sayfa seçin ve Widget Oluşturucu\'yu kullanın.</p><p>Widget\'ları sürükleyip bırakın ve her birinin ayarlarını özelleştirin. Hero banner, özellikler, istatistikler ve CTA dahil çeşitli widget\'lar mevcuttur.</p>',
  'vi' => '<p>Truy cập Admin > Trang web > Quản lý trang, chọn một trang và sử dụng Trình tạo Widget.</p><p>Kéo và thả widget, tùy chỉnh cài đặt của từng widget. Có nhiều widget đa dạng bao gồm banner hero, tính năng, thống kê và CTA.</p>',
  'zh_CN' => '<p>进入管理员 > 站点 > 页面管理，选择一个页面并使用小部件构建器。</p><p>拖放小部件，并自定义每个小部件的设置。提供多种小部件，包括英雄横幅、功能介绍、统计和 CTA 等。</p>',
  'zh_TW' => '<p>進入管理員 > 網站 > 頁面管理，選擇一個頁面並使用小工具建構器。</p><p>拖放小工具，並自訂每個小工具的設定。提供多種小工具，包括主視覺橫幅、功能介紹、統計和 CTA 等。</p>',
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
  'de' => 'Was ist der Unterschied zwischen VosCMS und RezlyX?',
  'en' => 'What is the difference between VosCMS and RezlyX?',
  'es' => '¿Cuál es la diferencia entre VosCMS y RezlyX?',
  'fr' => 'Quelle est la différence entre VosCMS et RezlyX ?',
  'id' => 'Apa perbedaan antara VosCMS dan RezlyX?',
  'ja' => 'VosCMSとRezlyXの違いは？',
  'mn' => 'VosCMS болон RezlyX-ийн ялгаа юу вэ?',
  'ru' => 'В чём разница между VosCMS и RezlyX?',
  'tr' => 'VosCMS ile RezlyX arasındaki fark nedir?',
  'vi' => 'VosCMS và RezlyX khác nhau ở điểm nào?',
  'zh_CN' => 'VosCMS 和 RezlyX 有什么区别？',
  'zh_TW' => 'VosCMS 和 RezlyX 有什麼區別？',
),
            'content_translations'=>array (
  'de' => '<p>VosCMS ist der kostenlose Open-Source-CMS-Kern. RezlyX ist ein All-in-One-Paket, das VosCMS sowie Salonverwaltung (Reservierungen/Dienstleistungen/Personal), POS, Kiosk und Anwesenheits-Plugins enthält.</p><p>Sie können VosCMS allein für eine allgemeine Website/Community installieren und bei Bedarf Plugins aus dem Marktplatz hinzufügen.</p>',
  'en' => '<p>VosCMS is the free open-source CMS core. RezlyX is an all-in-one bundle that includes VosCMS plus salon management (reservations/services/staff), POS, kiosk, and attendance plugins.</p><p>You can install VosCMS alone for a general website/community, then add plugins from the marketplace as needed.</p>',
  'es' => '<p>VosCMS es el núcleo CMS gratuito y de código abierto. RezlyX es un paquete todo en uno que incluye VosCMS más gestión de salón (reservas/servicios/personal), POS, kiosco y plugins de asistencia.</p><p>Puede instalar solo VosCMS para un sitio web/comunidad general y luego agregar plugins del marketplace según sea necesario.</p>',
  'fr' => '<p>VosCMS est le cœur CMS gratuit et open source. RezlyX est une offre tout-en-un qui comprend VosCMS ainsi que la gestion de salon (réservations/services/personnel), POS, kiosque et plugins de gestion des présences.</p><p>Vous pouvez installer VosCMS seul pour un site web/communauté général, puis ajouter des plugins depuis le marketplace selon vos besoins.</p>',
  'id' => '<p>VosCMS adalah inti CMS gratis dan open source. RezlyX adalah paket all-in-one yang mencakup VosCMS ditambah manajemen salon (reservasi/layanan/staf), POS, kiosk, dan plugin absensi.</p><p>Anda dapat menginstal VosCMS saja untuk website/komunitas umum, lalu menambahkan plugin dari marketplace sesuai kebutuhan.</p>',
  'ja' => '<p>VosCMSは無料のオープンソースCMSコアです。RezlyXはVosCMSにサロン管理（予約/サービス/スタッフ）、POS、キオスク、勤怠管理プラグインをすべて含むオールインワンバンドル製品です。</p><p>VosCMSだけをインストールすれば一般的なホームページ/コミュニティとして使用し、必要なプラグインをマーケットプレイスから追加できます。</p>',
  'mn' => '<p>VosCMS бол үнэгүй нээлттэй эхийн CMS цөм юм. RezlyX нь VosCMS дээр салон удирдлага (захиалга/үйлчилгээ/ажилтан), POS, киоск, ирцийн удирдлагын плагинуудыг бүгдийг нь багтаасан бүх-нэг-д багц бүтээгдэхүүн юм.</p><p>VosCMS-ийг дангаар нь суулгаж ерөнхий вэбсайт/коммьюнити болгон ашиглаж, маркетплейсээс шаардлагатай плагинуудыг нэмж болно.</p>',
  'ru' => '<p>VosCMS — это бесплатное ядро CMS с открытым исходным кодом. RezlyX — это комплексное решение «всё в одном», включающее VosCMS, а также управление салоном (бронирования/услуги/персонал), POS, киоск и плагины учёта рабочего времени.</p><p>Вы можете установить только VosCMS для обычного сайта/сообщества, а затем добавлять плагины из маркетплейса по мере необходимости.</p>',
  'tr' => '<p>VosCMS, ücretsiz açık kaynak CMS çekirdeğidir. RezlyX, VosCMS\'in yanı sıra salon yönetimi (rezervasyonlar/hizmetler/personel), POS, kiosk ve yoklama eklentilerini içeren hepsi bir arada bir pakettir.</p><p>Genel bir web sitesi/topluluk için yalnızca VosCMS\'i kurabilir, ardından ihtiyaç duyduğunuz eklentileri pazaryerinden ekleyebilirsiniz.</p>',
  'vi' => '<p>VosCMS là lõi CMS miễn phí và mã nguồn mở. RezlyX là gói tất-cả-trong-một bao gồm VosCMS cùng quản lý salon (đặt lịch/dịch vụ/nhân viên), POS, kiosk và plugin chấm công.</p><p>Bạn có thể cài đặt riêng VosCMS cho website/cộng đồng chung, sau đó thêm plugin từ marketplace khi cần.</p>',
  'zh_CN' => '<p>VosCMS 是免费的开源 CMS 核心。RezlyX 是一个一站式套装产品，包含 VosCMS 以及沙龙管理（预约/服务/员工）、POS、自助终端和考勤插件。</p><p>您可以仅安装 VosCMS 用作普通网站/社区，然后根据需要从市场添加插件。</p>',
  'zh_TW' => '<p>VosCMS 是免費的開源 CMS 核心。RezlyX 是一個一站式套裝產品，包含 VosCMS 以及沙龍管理（預約/服務/員工）、POS、自助終端和出勤外掛。</p><p>您可以僅安裝 VosCMS 用作一般網站/社群，然後根據需要從市場新增外掛。</p>',
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
  'de' => 'Wie viele Foren kann ich erstellen?',
  'en' => 'How many boards can I create?',
  'es' => '¿Cuántos foros puedo crear?',
  'fr' => 'Combien de forums puis-je créer ?',
  'id' => 'Berapa banyak papan yang bisa saya buat?',
  'ja' => '掲示板はいくつまで作成できますか？',
  'mn' => 'Хэдэн самбар үүсгэж болох вэ?',
  'ru' => 'Сколько досок можно создать?',
  'tr' => 'Kaç tane pano oluşturabilirim?',
  'vi' => 'Có thể tạo bao nhiêu diễn đàn?',
  'zh_CN' => '可以创建多少个版块？',
  'zh_TW' => '可以建立多少個版塊？',
),
            'content_translations'=>array (
  'de' => '<p>Sie können unbegrenzt viele Foren erstellen. Erstellen Sie Foren frei für verschiedene Zwecke wie Ankündigungen, Freie Foren, Q&A, FAQ, Galerien usw.</p><p>Jedes Forum kann individuelle Einstellungen für Skin, Berechtigungen, Kategorien und erweiterte Variablen haben.</p>',
  'en' => '<p>You can create unlimited boards. Create boards freely for different purposes like notices, free boards, Q&A, FAQ, galleries, etc.</p><p>Each board can have individual skin, permission, category, and extra variable settings.</p>',
  'es' => '<p>Puede crear foros ilimitados. Cree foros libremente para diferentes propósitos como avisos, foros libres, preguntas y respuestas, FAQ, galerías, etc.</p><p>Cada foro puede tener configuraciones individuales de skin, permisos, categorías y variables adicionales.</p>',
  'fr' => '<p>Vous pouvez créer un nombre illimité de forums. Créez des forums librement pour différents usages : annonces, forum libre, Q&R, FAQ, galeries, etc.</p><p>Chaque forum peut avoir des paramètres individuels de skin, permissions, catégories et variables supplémentaires.</p>',
  'id' => '<p>Anda dapat membuat papan tanpa batas. Buat papan dengan bebas untuk berbagai tujuan seperti pengumuman, papan bebas, tanya jawab, FAQ, galeri, dll.</p><p>Setiap papan dapat memiliki pengaturan skin, izin, kategori, dan variabel tambahan secara individual.</p>',
  'ja' => '<p>制限なく無制限に掲示板を作成できます。お知らせ、自由掲示板、Q&A、FAQ、ギャラリーなど用途別に自由に作成できます。</p><p>各掲示板ごとにスキン、権限、カテゴリ、拡張変数を個別設定できます。</p>',
  'mn' => '<p>Хязгааргүй олон самбар үүсгэх боломжтой. Мэдэгдэл, чөлөөт самбар, Асуулт&Хариулт, FAQ, галерей зэрэг зориулалтаар чөлөөтэй үүсгэж болно.</p><p>Самбар бүрт скин, зөвшөөрөл, ангилал, нэмэлт хувьсагчийг тус тусад нь тохируулж болно.</p>',
  'ru' => '<p>Вы можете создавать неограниченное количество досок. Создавайте доски свободно для различных целей: объявления, свободные доски, вопросы и ответы, FAQ, галереи и т.д.</p><p>Каждая доска может иметь индивидуальные настройки скина, прав доступа, категорий и дополнительных переменных.</p>',
  'tr' => '<p>Sınırsız sayıda pano oluşturabilirsiniz. Duyurular, serbest panolar, soru-cevap, SSS, galeriler gibi farklı amaçlar için özgürce panolar oluşturun.</p><p>Her pano için ayrı skin, izin, kategori ve ek değişken ayarları yapılabilir.</p>',
  'vi' => '<p>Bạn có thể tạo không giới hạn số lượng diễn đàn. Tạo diễn đàn tự do cho các mục đích khác nhau như thông báo, diễn đàn tự do, hỏi đáp, FAQ, thư viện ảnh, v.v.</p><p>Mỗi diễn đàn có thể có cài đặt riêng về skin, quyền hạn, danh mục và biến mở rộng.</p>',
  'zh_CN' => '<p>可以无限制地创建版块。您可以根据不同用途自由创建公告、自由版块、问答、FAQ、图库等。</p><p>每个版块都可以单独设置皮肤、权限、分类和扩展变量。</p>',
  'zh_TW' => '<p>可以無限制地建立版塊。您可以根據不同用途自由建立公告、自由版塊、問答、FAQ、圖庫等。</p><p>每個版塊都可以單獨設定面板、權限、分類和擴充變數。</p>',
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
  'de' => 'Wie konfiguriere ich die Ersteinrichtung nach der Installation?',
  'en' => 'How do I configure initial settings after installation?',
  'es' => '¿Cómo configuro los ajustes iniciales después de la instalación?',
  'fr' => 'Comment configurer les paramètres initiaux après l\'installation ?',
  'id' => 'Bagaimana cara mengatur pengaturan awal setelah instalasi?',
  'ja' => 'インストール後の初期設定はどうすればいいですか？',
  'mn' => 'Суулгасны дараа анхны тохиргоог хэрхэн хийх вэ?',
  'ru' => 'Как настроить начальные параметры после установки?',
  'tr' => 'Kurulumdan sonra başlangıç ayarlarını nasıl yapılandırırım?',
  'vi' => 'Làm sao để cấu hình cài đặt ban đầu sau khi cài đặt?',
  'zh_CN' => '安装后如何进行初始设置？',
  'zh_TW' => '安裝後如何進行初始設定？',
),
            'content_translations'=>array (
  'de' => '<p>Melden Sie sich nach der Installation im Admin-Panel an und konfigurieren Sie in dieser Reihenfolge:</p><ol><li><strong>Website-Einstellungen</strong> — Seitenname, URL, Logo, Favicon</li><li><strong>Spracheinstellungen</strong> — Unterstützte Sprachen auswählen, Standardsprache festlegen</li><li><strong>Menü-Verwaltung</strong> — Hauptmenü, Footer-Menü konfigurieren</li><li><strong>Seitenverwaltung</strong> — Startseiten-Widgets bearbeiten</li><li><strong>Foren erstellen</strong> — Benötigte Foren hinzufügen</li></ol>',
  'en' => '<p>After installation, log in to the admin panel and configure in this order:</p><ol><li><strong>Site Settings</strong> — Site name, URL, logo, favicon</li><li><strong>Language Settings</strong> — Select supported languages, set default</li><li><strong>Menu Management</strong> — Configure main menu, footer menu</li><li><strong>Page Management</strong> — Edit homepage widgets</li><li><strong>Create Boards</strong> — Add needed boards</li></ol>',
  'es' => '<p>Después de la instalación, inicie sesión en el panel de administración y configure en este orden:</p><ol><li><strong>Configuración del sitio</strong> — Nombre del sitio, URL, logo, favicon</li><li><strong>Configuración de idioma</strong> — Seleccionar idiomas compatibles, establecer el predeterminado</li><li><strong>Gestión de menú</strong> — Configurar menú principal y menú del pie de página</li><li><strong>Gestión de páginas</strong> — Editar widgets de la página de inicio</li><li><strong>Crear foros</strong> — Agregar los foros necesarios</li></ol>',
  'fr' => '<p>Après l\'installation, connectez-vous au panneau d\'administration et configurez dans cet ordre :</p><ol><li><strong>Paramètres du site</strong> — Nom du site, URL, logo, favicon</li><li><strong>Paramètres de langue</strong> — Sélectionner les langues supportées, définir la langue par défaut</li><li><strong>Gestion des menus</strong> — Configurer le menu principal et le menu de pied de page</li><li><strong>Gestion des pages</strong> — Modifier les widgets de la page d\'accueil</li><li><strong>Créer des forums</strong> — Ajouter les forums nécessaires</li></ol>',
  'id' => '<p>Setelah instalasi, masuk ke panel admin dan konfigurasi dengan urutan berikut:</p><ol><li><strong>Pengaturan Situs</strong> — Nama situs, URL, logo, favicon</li><li><strong>Pengaturan Bahasa</strong> — Pilih bahasa yang didukung, atur default</li><li><strong>Manajemen Menu</strong> — Konfigurasi menu utama, menu footer</li><li><strong>Manajemen Halaman</strong> — Edit widget halaman utama</li><li><strong>Buat Papan</strong> — Tambahkan papan yang diperlukan</li></ol>',
  'ja' => '<p>インストール完了後、管理パネルにログインして以下の順序で設定してください：</p><ol><li><strong>サイト設定</strong> — サイト名、URL、ロゴ、ファビコン</li><li><strong>言語設定</strong> — サポート言語の選択、デフォルト言語設定</li><li><strong>メニュー管理</strong> — メインメニュー、フッターメニュー構成</li><li><strong>ページ管理</strong> — ホームページウィジェット編集</li><li><strong>掲示板作成</strong> — 必要な掲示板を追加</li></ol>',
  'mn' => '<p>Суулгасны дараа админ панелд нэвтэрч дараах дарааллаар тохируулна:</p><ol><li><strong>Сайтын тохиргоо</strong> — Сайтын нэр, URL, лого, фавикон</li><li><strong>Хэлний тохиргоо</strong> — Дэмжих хэлүүдийг сонгох, үндсэн хэлийг тохируулах</li><li><strong>Цэс удирдлага</strong> — Үндсэн цэс, хөлийн цэс тохируулах</li><li><strong>Хуудас удирдлага</strong> — Нүүр хуудасны виджетүүдийг засах</li><li><strong>Самбар үүсгэх</strong> — Шаардлагатай самбаруудыг нэмэх</li></ol>',
  'ru' => '<p>После установки войдите в панель администратора и настройте в следующем порядке:</p><ol><li><strong>Настройки сайта</strong> — Название сайта, URL, логотип, фавикон</li><li><strong>Настройки языка</strong> — Выбрать поддерживаемые языки, установить язык по умолчанию</li><li><strong>Управление меню</strong> — Настроить главное меню, меню подвала</li><li><strong>Управление страницами</strong> — Редактировать виджеты главной страницы</li><li><strong>Создать доски</strong> — Добавить необходимые доски</li></ol>',
  'tr' => '<p>Kurulumdan sonra yönetim paneline giriş yapın ve şu sırayla yapılandırın:</p><ol><li><strong>Site Ayarları</strong> — Site adı, URL, logo, favicon</li><li><strong>Dil Ayarları</strong> — Desteklenen dilleri seçin, varsayılan dili belirleyin</li><li><strong>Menü Yönetimi</strong> — Ana menü, alt bilgi menüsünü yapılandırın</li><li><strong>Sayfa Yönetimi</strong> — Ana sayfa widget\'larını düzenleyin</li><li><strong>Pano Oluştur</strong> — Gerekli panoları ekleyin</li></ol>',
  'vi' => '<p>Sau khi cài đặt, đăng nhập vào bảng quản trị và cấu hình theo thứ tự sau:</p><ol><li><strong>Cài đặt trang</strong> — Tên trang, URL, logo, favicon</li><li><strong>Cài đặt ngôn ngữ</strong> — Chọn ngôn ngữ hỗ trợ, đặt mặc định</li><li><strong>Quản lý menu</strong> — Cấu hình menu chính, menu chân trang</li><li><strong>Quản lý trang</strong> — Chỉnh sửa widget trang chủ</li><li><strong>Tạo diễn đàn</strong> — Thêm các diễn đàn cần thiết</li></ol>',
  'zh_CN' => '<p>安装完成后，登录管理面板并按以下顺序进行配置：</p><ol><li><strong>站点设置</strong> — 站点名称、URL、Logo、Favicon</li><li><strong>语言设置</strong> — 选择支持的语言，设置默认语言</li><li><strong>菜单管理</strong> — 配置主菜单、页脚菜单</li><li><strong>页面管理</strong> — 编辑首页小部件</li><li><strong>创建版块</strong> — 添加所需的版块</li></ol>',
  'zh_TW' => '<p>安裝完成後，登入管理面板並按以下順序進行設定：</p><ol><li><strong>網站設定</strong> — 網站名稱、URL、Logo、Favicon</li><li><strong>語言設定</strong> — 選擇支援的語言，設定預設語言</li><li><strong>選單管理</strong> — 設定主選單、頁尾選單</li><li><strong>頁面管理</strong> — 編輯首頁小工具</li><li><strong>建立版塊</strong> — 新增所需的版塊</li></ol>',
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
  'de' => 'Wie richte ich HTTPS (SSL) ein?',
  'en' => 'How do I set up HTTPS (SSL)?',
  'es' => '¿Cómo configuro HTTPS (SSL)?',
  'fr' => 'Comment configurer HTTPS (SSL) ?',
  'id' => 'Bagaimana cara mengatur HTTPS (SSL)?',
  'ja' => 'HTTPS（SSL）証明書はどう設定しますか？',
  'mn' => 'HTTPS (SSL) сертификатыг хэрхэн тохируулах вэ?',
  'ru' => 'Как настроить HTTPS (SSL)?',
  'tr' => 'HTTPS (SSL) nasıl ayarlanır?',
  'vi' => 'Làm sao để thiết lập HTTPS (SSL)?',
  'zh_CN' => '如何设置 HTTPS (SSL)？',
  'zh_TW' => '如何設定 HTTPS (SSL)？',
),
            'content_translations'=>array (
  'de' => '<p>VosCMS erfordert aus Sicherheitsgründen HTTPS. SSL-Zertifikat-Einrichtung:</p><ul><li><strong>Let\'s Encrypt (Kostenlos)</strong> — Verwenden Sie <code>certbot</code>, um kostenlose SSL-Zertifikate mit automatischer Verlängerung zu erhalten.</li><li><strong>Hosting-Anbieter</strong> — Die meisten Hosting-Anbieter bieten kostenloses SSL an. Aktivieren Sie es im Hosting-Panel.</li><li><strong>Kostenpflichtige Zertifikate</strong> — EV/OV-Unternehmenszertifikate können bei Zertifizierungsstellen erworben werden.</li></ul><p>Überprüfen Sie nach der Einrichtung, dass <code>APP_URL</code> in <code>.env</code> mit <code>https://</code> beginnt.</p>',
  'en' => '<p>VosCMS requires HTTPS for security. SSL certificate setup:</p><ul><li><strong>Let\'s Encrypt (Free)</strong> — Use <code>certbot</code> to get free SSL certificates with auto-renewal.</li><li><strong>Hosting Provider</strong> — Most hosting providers offer free SSL. Enable it from the hosting panel.</li><li><strong>Paid Certificates</strong> — Enterprise EV/OV certificates can be purchased from certificate authorities.</li></ul><p>After setup, verify <code>APP_URL</code> in <code>.env</code> starts with <code>https://</code>.</p>',
  'es' => '<p>VosCMS requiere HTTPS por seguridad. Configuración del certificado SSL:</p><ul><li><strong>Let\'s Encrypt (Gratuito)</strong> — Use <code>certbot</code> para obtener certificados SSL gratuitos con renovación automática.</li><li><strong>Proveedor de hosting</strong> — La mayoría de los proveedores de hosting ofrecen SSL gratuito. Actívelo desde el panel de hosting.</li><li><strong>Certificados de pago</strong> — Los certificados empresariales EV/OV se pueden adquirir en autoridades de certificación.</li></ul><p>Después de la configuración, verifique que <code>APP_URL</code> en <code>.env</code> comience con <code>https://</code>.</p>',
  'fr' => '<p>VosCMS exige HTTPS pour la sécurité. Configuration du certificat SSL :</p><ul><li><strong>Let\'s Encrypt (Gratuit)</strong> — Utilisez <code>certbot</code> pour obtenir des certificats SSL gratuits avec renouvellement automatique.</li><li><strong>Hébergeur</strong> — La plupart des hébergeurs proposent un SSL gratuit. Activez-le depuis le panneau d\'hébergement.</li><li><strong>Certificats payants</strong> — Les certificats EV/OV pour entreprises peuvent être achetés auprès d\'autorités de certification.</li></ul><p>Après la configuration, vérifiez que <code>APP_URL</code> dans <code>.env</code> commence par <code>https://</code>.</p>',
  'id' => '<p>VosCMS memerlukan HTTPS untuk keamanan. Pengaturan sertifikat SSL:</p><ul><li><strong>Let\'s Encrypt (Gratis)</strong> — Gunakan <code>certbot</code> untuk mendapatkan sertifikat SSL gratis dengan perpanjangan otomatis.</li><li><strong>Penyedia Hosting</strong> — Sebagian besar penyedia hosting menawarkan SSL gratis. Aktifkan dari panel hosting.</li><li><strong>Sertifikat Berbayar</strong> — Sertifikat EV/OV perusahaan dapat dibeli dari otoritas sertifikasi.</li></ul><p>Setelah pengaturan, pastikan <code>APP_URL</code> di <code>.env</code> dimulai dengan <code>https://</code>.</p>',
  'ja' => '<p>VosCMSはセキュリティのためHTTPSが必須です。SSL証明書の設定方法：</p><ul><li><strong>Let\'s Encrypt（無料）</strong> — <code>certbot</code>で無料SSL証明書を取得できます。自動更新にも対応。</li><li><strong>ホスティング提供</strong> — ほとんどのホスティングが無料SSLを提供しています。</li><li><strong>有料証明書</strong> — 企業向けEV/OV証明書は認証局から購入可能。</li></ul><p>設定後、<code>.env</code>の<code>APP_URL</code>が<code>https://</code>で始まっていることを確認してください。</p>',
  'mn' => '<p>VosCMS аюулгүй байдлын үүднээс HTTPS шаарддаг. SSL сертификат тохируулах:</p><ul><li><strong>Let\'s Encrypt (Үнэгүй)</strong> — <code>certbot</code> ашиглан автомат сунгалттай үнэгүй SSL сертификат авах боломжтой.</li><li><strong>Хостинг нийлүүлэгч</strong> — Ихэнх хостинг нийлүүлэгч үнэгүй SSL санал болгодог. Хостинг панелаас идэвхжүүлнэ.</li><li><strong>Төлбөртэй сертификат</strong> — Байгууллагын EV/OV сертификатыг гэрчилгээний байгууллагаас худалдан авч болно.</li></ul><p>Тохируулсны дараа <code>.env</code> файл дахь <code>APP_URL</code> нь <code>https://</code>-аар эхэлж байгаа эсэхийг шалгана уу.</p>',
  'ru' => '<p>VosCMS требует HTTPS для безопасности. Настройка SSL-сертификата:</p><ul><li><strong>Let\'s Encrypt (Бесплатно)</strong> — Используйте <code>certbot</code> для получения бесплатных SSL-сертификатов с автоматическим продлением.</li><li><strong>Хостинг-провайдер</strong> — Большинство хостинг-провайдеров предлагают бесплатный SSL. Включите его в панели хостинга.</li><li><strong>Платные сертификаты</strong> — Корпоративные сертификаты EV/OV можно приобрести у удостоверяющих центров.</li></ul><p>После настройки убедитесь, что <code>APP_URL</code> в <code>.env</code> начинается с <code>https://</code>.</p>',
  'tr' => '<p>VosCMS güvenlik için HTTPS gerektirir. SSL sertifikası kurulumu:</p><ul><li><strong>Let\'s Encrypt (Ücretsiz)</strong> — Otomatik yenileme ile ücretsiz SSL sertifikaları almak için <code>certbot</code> kullanın.</li><li><strong>Hosting Sağlayıcı</strong> — Çoğu hosting sağlayıcı ücretsiz SSL sunar. Hosting panelinden etkinleştirin.</li><li><strong>Ücretli Sertifikalar</strong> — Kurumsal EV/OV sertifikaları sertifika yetkililerinden satın alınabilir.</li></ul><p>Kurulumdan sonra <code>.env</code> dosyasındaki <code>APP_URL</code> değerinin <code>https://</code> ile başladığını doğrulayın.</p>',
  'vi' => '<p>VosCMS yêu cầu HTTPS để đảm bảo bảo mật. Thiết lập chứng chỉ SSL:</p><ul><li><strong>Let\'s Encrypt (Miễn phí)</strong> — Sử dụng <code>certbot</code> để nhận chứng chỉ SSL miễn phí với gia hạn tự động.</li><li><strong>Nhà cung cấp hosting</strong> — Hầu hết nhà cung cấp hosting đều cung cấp SSL miễn phí. Kích hoạt từ bảng điều khiển hosting.</li><li><strong>Chứng chỉ trả phí</strong> — Chứng chỉ EV/OV doanh nghiệp có thể mua từ tổ chức cấp chứng chỉ.</li></ul><p>Sau khi thiết lập, hãy xác minh <code>APP_URL</code> trong <code>.env</code> bắt đầu bằng <code>https://</code>.</p>',
  'zh_CN' => '<p>VosCMS 出于安全考虑要求使用 HTTPS。SSL 证书设置：</p><ul><li><strong>Let\'s Encrypt（免费）</strong> — 使用 <code>certbot</code> 获取免费 SSL 证书，支持自动续期。</li><li><strong>主机提供商</strong> — 大多数主机提供商提供免费 SSL。在主机面板中启用即可。</li><li><strong>付费证书</strong> — 企业级 EV/OV 证书可从证书颁发机构购买。</li></ul><p>设置完成后，请确认 <code>.env</code> 中的 <code>APP_URL</code> 以 <code>https://</code> 开头。</p>',
  'zh_TW' => '<p>VosCMS 基於安全考量要求使用 HTTPS。SSL 憑證設定：</p><ul><li><strong>Let\'s Encrypt（免費）</strong> — 使用 <code>certbot</code> 取得免費 SSL 憑證，支援自動續期。</li><li><strong>主機供應商</strong> — 大多數主機供應商提供免費 SSL。在主機面板中啟用即可。</li><li><strong>付費憑證</strong> — 企業級 EV/OV 憑證可從憑證頒發機構購買。</li></ul><p>設定完成後，請確認 <code>.env</code> 中的 <code>APP_URL</code> 以 <code>https://</code> 開頭。</p>',
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
  'de' => 'Welche Seiten kann ich mit dem Widget-Builder erstellen?',
  'en' => 'What pages can I create with the Widget Builder?',
  'es' => '¿Qué páginas puedo crear con el Constructor de widgets?',
  'fr' => 'Quelles pages puis-je créer avec le Constructeur de widgets ?',
  'id' => 'Halaman apa saja yang bisa dibuat dengan Widget Builder?',
  'ja' => 'ウィジェットビルダーでどんなページが作れますか？',
  'mn' => 'Виджет бүтээгчээр ямар хуудас хийж болох вэ?',
  'ru' => 'Какие страницы можно создать с помощью Конструктора виджетов?',
  'tr' => 'Widget Oluşturucu ile hangi sayfaları oluşturabilirim?',
  'vi' => 'Có thể tạo những trang nào bằng Trình tạo Widget?',
  'zh_CN' => '使用小部件构建器可以创建哪些页面？',
  'zh_TW' => '使用小工具建構器可以建立哪些頁面？',
),
            'content_translations'=>array (
  'de' => '<p>Mit dem Widget-Builder können Sie ohne Programmierung verschiedene Seiten erstellen:</p><ul><li>Hauptseite mit Hero-Banner</li><li>Service-/Funktionsübersichtsseite</li><li>Portfolio-/Galerieseite</li><li>Kundenbewertungsseite</li><li>CTA-Landingpage</li></ul><p>Platzieren Sie über 20 Widgets per Drag-and-Drop und passen Sie Texte, Farben und Bilder für jedes Widget frei an.</p>',
  'en' => '<p>With the Widget Builder, you can create various pages without coding:</p><ul><li>Main page with hero banner</li><li>Service/feature introduction page</li><li>Portfolio/gallery page</li><li>Customer testimonial page</li><li>CTA landing page</li></ul><p>Drag and drop 20+ widgets and freely customize text, colors, and images for each widget.</p>',
  'es' => '<p>Con el Constructor de widgets, puede crear diversas páginas sin programar:</p><ul><li>Página principal con banner hero</li><li>Página de presentación de servicios/funciones</li><li>Página de portafolio/galería</li><li>Página de testimonios de clientes</li><li>Página de aterrizaje CTA</li></ul><p>Arrastre y suelte más de 20 widgets y personalice libremente el texto, colores e imágenes de cada widget.</p>',
  'fr' => '<p>Avec le Constructeur de widgets, vous pouvez créer diverses pages sans coder :</p><ul><li>Page principale avec bannière hero</li><li>Page de présentation des services/fonctionnalités</li><li>Page portfolio/galerie</li><li>Page de témoignages clients</li><li>Page d\'atterrissage CTA</li></ul><p>Glissez-déposez plus de 20 widgets et personnalisez librement le texte, les couleurs et les images de chaque widget.</p>',
  'id' => '<p>Dengan Widget Builder, Anda dapat membuat berbagai halaman tanpa coding:</p><ul><li>Halaman utama dengan banner hero</li><li>Halaman pengenalan layanan/fitur</li><li>Halaman portofolio/galeri</li><li>Halaman testimoni pelanggan</li><li>Halaman landing CTA</li></ul><p>Seret dan lepas lebih dari 20 widget dan sesuaikan teks, warna, dan gambar setiap widget secara bebas.</p>',
  'ja' => '<p>ウィジェットビルダーを使えば、コーディングなしで様々なページを作成できます：</p><ul><li>ヒーローバナー付きメインページ</li><li>サービス/機能紹介ページ</li><li>ポートフォリオ/ギャラリーページ</li><li>お客様の声ページ</li><li>CTAランディングページ</li></ul><p>20以上のウィジェットをドラッグ＆ドロップで配置し、テキスト、色、画像を自由に設定できます。</p>',
  'mn' => '<p>Виджет бүтээгчээр кодлохгүйгээр олон төрлийн хуудас хийж болно:</p><ul><li>Хийро баннертай нүүр хуудас</li><li>Үйлчилгээ/онцлог танилцуулга хуудас</li><li>Портфолио/галерей хуудас</li><li>Хэрэглэгчдийн сэтгэгдлийн хуудас</li><li>CTA буух хуудас</li></ul><p>20 гаруй виджетийг чирж буулгаж байрлуулаад текст, өнгө, зургийг чөлөөтэй тохируулж болно.</p>',
  'ru' => '<p>С помощью Конструктора виджетов вы можете создавать различные страницы без программирования:</p><ul><li>Главная страница с героическим баннером</li><li>Страница представления услуг/функций</li><li>Страница портфолио/галереи</li><li>Страница отзывов клиентов</li><li>Посадочная страница CTA</li></ul><p>Перетаскивайте более 20 виджетов и свободно настраивайте текст, цвета и изображения для каждого виджета.</p>',
  'tr' => '<p>Widget Oluşturucu ile kodlama yapmadan çeşitli sayfalar oluşturabilirsiniz:</p><ul><li>Hero banner\'lı ana sayfa</li><li>Hizmet/özellik tanıtım sayfası</li><li>Portfolyo/galeri sayfası</li><li>Müşteri değerlendirme sayfası</li><li>CTA açılış sayfası</li></ul><p>20\'den fazla widget\'ı sürükleyip bırakarak yerleştirin ve her widget\'ın metin, renk ve görsellerini özgürce özelleştirin.</p>',
  'vi' => '<p>Với Trình tạo Widget, bạn có thể tạo nhiều trang khác nhau mà không cần lập trình:</p><ul><li>Trang chủ với banner hero</li><li>Trang giới thiệu dịch vụ/tính năng</li><li>Trang portfolio/thư viện ảnh</li><li>Trang đánh giá khách hàng</li><li>Trang đích CTA</li></ul><p>Kéo và thả hơn 20 widget và tùy chỉnh tự do văn bản, màu sắc và hình ảnh cho từng widget.</p>',
  'zh_CN' => '<p>使用小部件构建器，您可以无需编程创建各种页面：</p><ul><li>带英雄横幅的主页</li><li>服务/功能介绍页</li><li>作品集/图库页</li><li>客户评价页</li><li>CTA 着陆页</li></ul><p>拖放 20 多个小部件，自由自定义每个小部件的文字、颜色和图片。</p>',
  'zh_TW' => '<p>使用小工具建構器，您可以無需撰寫程式碼建立各種頁面：</p><ul><li>帶主視覺橫幅的首頁</li><li>服務/功能介紹頁</li><li>作品集/圖庫頁</li><li>客戶評價頁</li><li>CTA 到達頁</li></ul><p>拖放 20 多個小工具，自由自訂每個小工具的文字、顏色和圖片。</p>',
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
  'de' => 'Kann ich eigene Plugins entwickeln?',
  'en' => 'Can I develop my own plugins?',
  'es' => '¿Puedo desarrollar mis propios plugins?',
  'fr' => 'Puis-je développer mes propres plugins ?',
  'id' => 'Bisakah saya mengembangkan plugin sendiri?',
  'ja' => '自分でプラグインを開発できますか？',
  'mn' => 'Өөрөө плагин хөгжүүлж болох уу?',
  'ru' => 'Могу ли я разрабатывать собственные плагины?',
  'tr' => 'Kendi eklentilerimi geliştirebilir miyim?',
  'vi' => 'Tôi có thể tự phát triển plugin không?',
  'zh_CN' => '我可以自己开发插件吗？',
  'zh_TW' => '我可以自行開發外掛嗎？',
),
            'content_translations'=>array (
  'de' => '<p>Ja, jeder kann VosCMS-Plugins entwickeln. Registrieren Sie sich im Entwicklerportal, um loszulegen.</p><h4>Plugin-Entwicklungsprozess</h4><ol><li>Verzeichnis <code>plugins/my-plugin/</code> erstellen</li><li><code>plugin.json</code>-Manifest schreiben (Routen, Menüs, Migrationen)</li><li>View-Dateien und DB-Migrationen erstellen</li><li>Als ZIP verpacken und über das Entwicklerportal einreichen</li><li>Nach Prüfung und Genehmigung im Marktplatz veröffentlicht</li></ol><p>Sie können sowohl kostenlose als auch kostenpflichtige Einträge registrieren. Bei kostenpflichtigem Verkauf erhalten Sie 70 % des Umsatzes.</p>',
  'en' => '<p>Yes, anyone can develop VosCMS plugins. Sign up at the Developer Portal to get started.</p><h4>Plugin Development Process</h4><ol><li>Create <code>plugins/my-plugin/</code> directory</li><li>Write <code>plugin.json</code> manifest (routes, menus, migrations)</li><li>Create view files and DB migrations</li><li>Package as ZIP and submit via Developer Portal</li><li>Published on marketplace after review approval</li></ol><p>You can register both free and paid items. For paid sales, you receive 70% of revenue.</p>',
  'es' => '<p>Sí, cualquier persona puede desarrollar plugins para VosCMS. Regístrese en el Portal de Desarrolladores para comenzar.</p><h4>Proceso de desarrollo de plugins</h4><ol><li>Crear el directorio <code>plugins/my-plugin/</code></li><li>Escribir el manifiesto <code>plugin.json</code> (rutas, menús, migraciones)</li><li>Crear archivos de vista y migraciones de BD</li><li>Empaquetar como ZIP y enviar a través del Portal de Desarrolladores</li><li>Publicación en el marketplace tras aprobación de revisión</li></ol><p>Puede registrar elementos gratuitos y de pago. En ventas de pago, recibe el 70% de los ingresos.</p>',
  'fr' => '<p>Oui, tout le monde peut développer des plugins VosCMS. Inscrivez-vous sur le Portail Développeur pour commencer.</p><h4>Processus de développement de plugins</h4><ol><li>Créer le répertoire <code>plugins/my-plugin/</code></li><li>Écrire le manifeste <code>plugin.json</code> (routes, menus, migrations)</li><li>Créer les fichiers de vue et les migrations de BDD</li><li>Empaqueter en ZIP et soumettre via le Portail Développeur</li><li>Publication sur le marketplace après approbation</li></ol><p>Vous pouvez enregistrer des éléments gratuits et payants. Pour les ventes payantes, vous recevez 70 % des revenus.</p>',
  'id' => '<p>Ya, siapa saja dapat mengembangkan plugin VosCMS. Daftar di Portal Developer untuk memulai.</p><h4>Proses Pengembangan Plugin</h4><ol><li>Buat direktori <code>plugins/my-plugin/</code></li><li>Tulis manifes <code>plugin.json</code> (rute, menu, migrasi)</li><li>Buat file tampilan dan migrasi DB</li><li>Paketkan sebagai ZIP dan kirim melalui Portal Developer</li><li>Dipublikasikan di marketplace setelah disetujui</li></ol><p>Anda dapat mendaftarkan item gratis maupun berbayar. Untuk penjualan berbayar, Anda menerima 70% dari pendapatan.</p>',
  'ja' => '<p>はい、誰でもVosCMSプラグインを開発できます。開発者ポータルで登録してすぐに始められます。</p><h4>プラグイン開発手順</h4><ol><li><code>plugins/my-plugin/</code>ディレクトリ作成</li><li><code>plugin.json</code>マニフェスト作成（ルート、メニュー、マイグレーション定義）</li><li>ビューファイル、DBマイグレーション作成</li><li>ZIPでパッケージ化して開発者ポータルから提出</li><li>審査承認後マーケットプレイスで公開</li></ol><p>有料・無料どちらも自由に登録でき、有料販売時は収益の70%を受け取れます。</p>',
  'mn' => '<p>Тийм, хэн ч VosCMS плагин хөгжүүлж болно. Хөгжүүлэгчийн порталд бүртгүүлж эхлээрэй.</p><h4>Плагин хөгжүүлэх үйл явц</h4><ol><li><code>plugins/my-plugin/</code> хавтас үүсгэх</li><li><code>plugin.json</code> манифест бичих (маршрут, цэс, шилжилт)</li><li>View файл, DB шилжилт үүсгэх</li><li>ZIP хэлбэрээр багцалж Хөгжүүлэгчийн порталаар илгээх</li><li>Шалгалт дууссаны дараа маркетплейсд нийтлэгдэнэ</li></ol><p>Үнэгүй болон төлбөртэй аль алийг нь чөлөөтэй бүртгүүлж болох бөгөөд төлбөртэй борлуулалтаас орлогын 70%-ийг авна.</p>',
  'ru' => '<p>Да, любой может разрабатывать плагины для VosCMS. Зарегистрируйтесь на Портале разработчиков, чтобы начать.</p><h4>Процесс разработки плагинов</h4><ol><li>Создайте директорию <code>plugins/my-plugin/</code></li><li>Напишите манифест <code>plugin.json</code> (маршруты, меню, миграции)</li><li>Создайте файлы представлений и миграции БД</li><li>Упакуйте в ZIP и отправьте через Портал разработчиков</li><li>Публикация на маркетплейсе после одобрения</li></ol><p>Вы можете регистрировать как бесплатные, так и платные продукты. При платных продажах вы получаете 70% дохода.</p>',
  'tr' => '<p>Evet, herkes VosCMS eklentileri geliştirebilir. Başlamak için Geliştirici Portalı\'na kaydolun.</p><h4>Eklenti Geliştirme Süreci</h4><ol><li><code>plugins/my-plugin/</code> dizinini oluşturun</li><li><code>plugin.json</code> manifest dosyasını yazın (rotalar, menüler, migrasyonlar)</li><li>Görünüm dosyaları ve DB migrasyonları oluşturun</li><li>ZIP olarak paketleyin ve Geliştirici Portalı üzerinden gönderin</li><li>İnceleme onayından sonra pazaryerinde yayınlanır</li></ol><p>Ücretsiz ve ücretli öğeleri serbestçe kaydedebilirsiniz. Ücretli satışlarda gelirin %70\'ini alırsınız.</p>',
  'vi' => '<p>Có, bất kỳ ai cũng có thể phát triển plugin VosCMS. Đăng ký tại Cổng Nhà phát triển để bắt đầu.</p><h4>Quy trình phát triển plugin</h4><ol><li>Tạo thư mục <code>plugins/my-plugin/</code></li><li>Viết manifest <code>plugin.json</code> (routes, menu, migrations)</li><li>Tạo file view và DB migrations</li><li>Đóng gói ZIP và gửi qua Cổng Nhà phát triển</li><li>Được xuất bản trên marketplace sau khi duyệt</li></ol><p>Bạn có thể đăng ký cả mục miễn phí và trả phí. Đối với bán hàng trả phí, bạn nhận 70% doanh thu.</p>',
  'zh_CN' => '<p>是的，任何人都可以开发 VosCMS 插件。在开发者门户注册即可开始。</p><h4>插件开发流程</h4><ol><li>创建 <code>plugins/my-plugin/</code> 目录</li><li>编写 <code>plugin.json</code> 清单文件（路由、菜单、迁移）</li><li>创建视图文件和数据库迁移</li><li>打包为 ZIP 并通过开发者门户提交</li><li>审核通过后在市场发布</li></ol><p>您可以注册免费和付费项目。付费销售时可获得 70% 的收入。</p>',
  'zh_TW' => '<p>是的，任何人都可以開發 VosCMS 外掛。在開發者入口網站註冊即可開始。</p><h4>外掛開發流程</h4><ol><li>建立 <code>plugins/my-plugin/</code> 目錄</li><li>撰寫 <code>plugin.json</code> 清單檔案（路由、選單、遷移）</li><li>建立視圖檔案和資料庫遷移</li><li>打包為 ZIP 並透過開發者入口網站提交</li><li>審核通過後在市場發布</li></ol><p>您可以註冊免費和付費項目。付費銷售時可獲得 70% 的收入。</p>',
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
  'de' => 'Wie füge ich Übersetzungen zu Beiträgen hinzu?',
  'en' => 'How do I add translations to posts?',
  'es' => '¿Cómo agrego traducciones a las publicaciones?',
  'fr' => 'Comment ajouter des traductions aux articles ?',
  'id' => 'Bagaimana cara menambahkan terjemahan ke postingan?',
  'ja' => '投稿の翻訳はどう登録しますか？',
  'mn' => 'Нийтлэлд орчуулга хэрхэн нэмэх вэ?',
  'ru' => 'Как добавить переводы к записям?',
  'tr' => 'Gönderilere çeviri nasıl eklenir?',
  'vi' => 'Làm sao để thêm bản dịch cho bài viết?',
  'zh_CN' => '如何为文章添加翻译？',
  'zh_TW' => '如何為文章新增翻譯？',
),
            'content_translations'=>array (
  'de' => '<p>Verfassen Sie den Originalbeitrag in der aktuellen Sprache. Um Übersetzungen hinzuzufügen:</p><ol><li>Wechseln Sie auf der Bearbeitungsseite die Sprache.</li><li>Übersetzen Sie Titel und Inhalt und speichern Sie.</li><li>Übersetzungen werden separat in der Tabelle <code>rzx_translations</code> gespeichert; der Originalbeitrag wird nicht verändert.</li></ol><p>Sprachen ohne Übersetzung greifen automatisch auf Englisch und dann auf die Originalsprache zurück.</p>',
  'en' => '<p>Write the original post in the current language. To add translations:</p><ol><li>Switch language on the post edit page.</li><li>Translate the title and content, then save.</li><li>Translations are stored separately in <code>rzx_translations</code> table; the original post is not modified.</li></ol><p>Languages without translations automatically fall back to English, then the original language.</p>',
  'es' => '<p>Escriba la publicación original en el idioma actual. Para agregar traducciones:</p><ol><li>Cambie el idioma en la página de edición de la publicación.</li><li>Traduzca el título y el contenido, luego guarde.</li><li>Las traducciones se almacenan por separado en la tabla <code>rzx_translations</code>; la publicación original no se modifica.</li></ol><p>Los idiomas sin traducción recurren automáticamente al inglés y luego al idioma original.</p>',
  'fr' => '<p>Rédigez l\'article original dans la langue actuelle. Pour ajouter des traductions :</p><ol><li>Changez la langue sur la page d\'édition de l\'article.</li><li>Traduisez le titre et le contenu, puis enregistrez.</li><li>Les traductions sont stockées séparément dans la table <code>rzx_translations</code> ; l\'article original n\'est pas modifié.</li></ol><p>Les langues sans traduction basculent automatiquement vers l\'anglais, puis vers la langue originale.</p>',
  'id' => '<p>Tulis postingan asli dalam bahasa saat ini. Untuk menambahkan terjemahan:</p><ol><li>Ganti bahasa di halaman edit postingan.</li><li>Terjemahkan judul dan konten, lalu simpan.</li><li>Terjemahan disimpan terpisah di tabel <code>rzx_translations</code>; postingan asli tidak diubah.</li></ol><p>Bahasa tanpa terjemahan secara otomatis fallback ke bahasa Inggris, kemudian bahasa asli.</p>',
  'ja' => '<p>投稿作成時に現在の言語で原文を作成します。他の言語の翻訳を追加するには：</p><ol><li>投稿編集ページで言語を切り替えます。</li><li>その言語でタイトルと本文を翻訳して保存します。</li><li>翻訳は<code>rzx_translations</code>テーブルに別途保存され、元の投稿は変更されません。</li></ol><p>翻訳がない言語は英語→原文の順で自動フォールバックされます。</p>',
  'mn' => '<p>Одоогийн хэл дээр эх нийтлэлийг бичнэ. Орчуулга нэмэхийн тулд:</p><ol><li>Нийтлэл засварлах хуудсанд хэлийг сольно.</li><li>Гарчиг болон агуулгыг орчуулж хадгална.</li><li>Орчуулга <code>rzx_translations</code> хүснэгтэд тусад нь хадгалагдах бөгөөд эх нийтлэл өөрчлөгдөхгүй.</li></ol><p>Орчуулга байхгүй хэл англи хэл рүү, дараа нь эх хэл рүү автоматаар буцна.</p>',
  'ru' => '<p>Напишите оригинальную запись на текущем языке. Чтобы добавить переводы:</p><ol><li>Переключите язык на странице редактирования записи.</li><li>Переведите заголовок и содержание, затем сохраните.</li><li>Переводы хранятся отдельно в таблице <code>rzx_translations</code>; оригинальная запись не изменяется.</li></ol><p>Языки без переводов автоматически переключаются на английский, затем на язык оригинала.</p>',
  'tr' => '<p>Orijinal gönderiyi mevcut dilde yazın. Çeviri eklemek için:</p><ol><li>Gönderi düzenleme sayfasında dili değiştirin.</li><li>Başlığı ve içeriği çevirin, ardından kaydedin.</li><li>Çeviriler <code>rzx_translations</code> tablosunda ayrı saklanır; orijinal gönderi değiştirilmez.</li></ol><p>Çevirisi olmayan diller otomatik olarak İngilizce\'ye, ardından orijinal dile geri döner.</p>',
  'vi' => '<p>Viết bài gốc bằng ngôn ngữ hiện tại. Để thêm bản dịch:</p><ol><li>Chuyển ngôn ngữ trên trang chỉnh sửa bài viết.</li><li>Dịch tiêu đề và nội dung, sau đó lưu.</li><li>Bản dịch được lưu riêng trong bảng <code>rzx_translations</code>; bài viết gốc không bị thay đổi.</li></ol><p>Ngôn ngữ không có bản dịch sẽ tự động chuyển về tiếng Anh, sau đó về ngôn ngữ gốc.</p>',
  'zh_CN' => '<p>用当前语言撰写原始文章。要添加翻译：</p><ol><li>在文章编辑页面切换语言。</li><li>翻译标题和内容，然后保存。</li><li>翻译内容单独存储在 <code>rzx_translations</code> 表中，原始文章不会被修改。</li></ol><p>没有翻译的语言会自动回退到英语，然后是原始语言。</p>',
  'zh_TW' => '<p>用目前的語言撰寫原始文章。要新增翻譯：</p><ol><li>在文章編輯頁面切換語言。</li><li>翻譯標題和內容，然後儲存。</li><li>翻譯內容單獨儲存在 <code>rzx_translations</code> 表中，原始文章不會被修改。</li></ol><p>沒有翻譯的語言會自動回退到英語，然後是原始語言。</p>',
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
  'de' => 'Wie bezahle ich für kostenpflichtige Plugins?',
  'en' => 'How do I pay for paid plugins?',
  'es' => '¿Cómo pago por los plugins de pago?',
  'fr' => 'Comment payer les plugins payants ?',
  'id' => 'Bagaimana cara membayar plugin berbayar?',
  'ja' => '有料プラグインの決済方法は？',
  'mn' => 'Төлбөртэй плагины төлбөрийг хэрхэн хийх вэ?',
  'ru' => 'Как оплатить платные плагины?',
  'tr' => 'Ücretli eklentiler için nasıl ödeme yapılır?',
  'vi' => 'Làm sao để thanh toán plugin trả phí?',
  'zh_CN' => '如何支付付费插件？',
  'zh_TW' => '如何支付付費外掛？',
),
            'content_translations'=>array (
  'de' => '<p>Beim Kauf kostenpflichtiger Plugins aus dem Marktplatz wird die Kreditkartenzahlung über Stripe unterstützt.</p><ul><li>Suchen Sie nach Plugins unter Admin > Automatische Installation.</li><li>Klicken Sie auf „Kaufen", um zur Stripe-Zahlungsseite zu gelangen.</li><li>Nach der Zahlung wird automatisch eine Lizenz ausgestellt und Sie können herunterladen.</li><li>Gekaufte Plugins finden Sie unter „Installationsverlauf" zur Ein-Klick-Installation.</li></ul>',
  'en' => '<p>When purchasing paid plugins from the marketplace, credit card payment via Stripe is supported.</p><ul><li>Search for plugins in Admin > Auto Install.</li><li>Click "Purchase" to go to the Stripe payment page.</li><li>After payment, a license is automatically issued and you can download.</li><li>Purchased plugins can be found in "Install History" for one-click installation.</li></ul>',
  'es' => '<p>Al comprar plugins de pago en el marketplace, se acepta el pago con tarjeta de crédito a través de Stripe.</p><ul><li>Busque plugins en Admin > Instalación automática.</li><li>Haga clic en "Comprar" para ir a la página de pago de Stripe.</li><li>Después del pago, se emite automáticamente una licencia y puede descargar.</li><li>Los plugins comprados se encuentran en "Historial de instalación" para instalación con un clic.</li></ul>',
  'fr' => '<p>Lors de l\'achat de plugins payants sur le marketplace, le paiement par carte bancaire via Stripe est accepté.</p><ul><li>Recherchez des plugins dans Admin > Installation automatique.</li><li>Cliquez sur « Acheter » pour accéder à la page de paiement Stripe.</li><li>Après le paiement, une licence est automatiquement délivrée et vous pouvez télécharger.</li><li>Les plugins achetés se trouvent dans « Historique d\'installation » pour une installation en un clic.</li></ul>',
  'id' => '<p>Saat membeli plugin berbayar dari marketplace, pembayaran kartu kredit melalui Stripe didukung.</p><ul><li>Cari plugin di Admin > Instal Otomatis.</li><li>Klik "Beli" untuk menuju halaman pembayaran Stripe.</li><li>Setelah pembayaran, lisensi otomatis diterbitkan dan Anda dapat mengunduh.</li><li>Plugin yang dibeli dapat ditemukan di "Riwayat Instalasi" untuk instalasi satu klik.</li></ul>',
  'ja' => '<p>マーケットプレイスで有料プラグインを購入する際、Stripeによるクレジットカード決済に対応しています。</p><ul><li>管理パネル > 自動インストールでプラグインを検索します。</li><li>「購入」ボタンをクリックするとStripe決済ページに移動します。</li><li>決済完了後、自動的にライセンスが発行されダウンロード可能になります。</li><li>購入したプラグインは「インストール履歴」でワンクリックインストールできます。</li></ul>',
  'mn' => '<p>Маркетплейсээс төлбөртэй плагин худалдан авахад Stripe-аар зээлийн картын төлбөр хийх боломжтой.</p><ul><li>Админ > Автомат суулгалт хэсгээс плагин хайна.</li><li>"Худалдан авах" дээр дарж Stripe төлбөрийн хуудас руу шилжинэ.</li><li>Төлбөр хийсний дараа лиценз автоматаар олгогдож татаж авах боломжтой болно.</li><li>Худалдан авсан плагинуудыг "Суулгалтын түүх" хэсгээс нэг товшилтоор суулгаж болно.</li></ul>',
  'ru' => '<p>При покупке платных плагинов на маркетплейсе поддерживается оплата кредитной картой через Stripe.</p><ul><li>Найдите плагины в Админ > Автоустановка.</li><li>Нажмите «Купить», чтобы перейти на страницу оплаты Stripe.</li><li>После оплаты лицензия выдаётся автоматически, и вы можете скачать плагин.</li><li>Купленные плагины можно найти в «Истории установок» для установки в один клик.</li></ul>',
  'tr' => '<p>Pazaryerinden ücretli eklentiler satın alırken Stripe üzerinden kredi kartı ödemesi desteklenmektedir.</p><ul><li>Yönetim > Otomatik Kurulum\'da eklentileri arayın.</li><li>"Satın Al" düğmesine tıklayarak Stripe ödeme sayfasına gidin.</li><li>Ödeme sonrasında lisans otomatik olarak verilir ve indirebilirsiniz.</li><li>Satın alınan eklentiler "Kurulum Geçmişi"nde tek tıkla kurulum için bulunabilir.</li></ul>',
  'vi' => '<p>Khi mua plugin trả phí từ marketplace, hệ thống hỗ trợ thanh toán bằng thẻ tín dụng qua Stripe.</p><ul><li>Tìm kiếm plugin tại Admin > Cài đặt tự động.</li><li>Nhấn "Mua" để chuyển đến trang thanh toán Stripe.</li><li>Sau khi thanh toán, giấy phép được cấp tự động và bạn có thể tải về.</li><li>Plugin đã mua có thể tìm thấy trong "Lịch sử cài đặt" để cài đặt một chạm.</li></ul>',
  'zh_CN' => '<p>从市场购买付费插件时，支持通过 Stripe 进行信用卡支付。</p><ul><li>在管理员 > 自动安装中搜索插件。</li><li>点击"购买"跳转到 Stripe 支付页面。</li><li>支付完成后自动颁发许可证，即可下载。</li><li>已购买的插件可在"安装历史"中一键安装。</li></ul>',
  'zh_TW' => '<p>從市場購買付費外掛時，支援透過 Stripe 進行信用卡付款。</p><ul><li>在管理員 > 自動安裝中搜尋外掛。</li><li>點擊「購買」跳轉到 Stripe 付款頁面。</li><li>付款完成後自動核發授權，即可下載。</li><li>已購買的外掛可在「安裝紀錄」中一鍵安裝。</li></ul>',
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
  'de' => 'Auf wie vielen Websites kann ich eine Lizenz verwenden?',
  'en' => 'How many sites can I use with one license?',
  'es' => '¿En cuántos sitios puedo usar una licencia?',
  'fr' => 'Sur combien de sites puis-je utiliser une licence ?',
  'id' => 'Berapa situs yang bisa menggunakan satu lisensi?',
  'ja' => 'ライセンスは何サイトで使用できますか？',
  'mn' => 'Нэг лицензээр хэдэн сайтад ашиглах боломжтой вэ?',
  'ru' => 'На скольких сайтах можно использовать одну лицензию?',
  'tr' => 'Bir lisansı kaç sitede kullanabilirim?',
  'vi' => 'Một giấy phép có thể dùng cho bao nhiêu trang web?',
  'zh_CN' => '一个许可证可以用于多少个网站？',
  'zh_TW' => '一個授權可以用於多少個網站？',
),
            'content_translations'=>array (
  'de' => '<p>Eine Standardlizenz kann auf <strong>1 Domain</strong> verwendet werden. Sie wird pro Domain aktiviert, und für weitere Domains sind zusätzliche Lizenzen erforderlich.</p><ul><li><strong>Einzelne Website</strong> — 1 Domain (Standard)</li><li><strong>Unbegrenzt</strong> — Mehrere Domains (einige Plugins)</li></ul><p>Wenn Sie die Domain ändern müssen, kontaktieren Sie uns — bis zu 2 kostenlose Änderungen pro Jahr sind möglich.</p>',
  'en' => '<p>A standard license can be used on <strong>1 domain</strong>. It is activated per domain, and additional licenses are needed for other domains.</p><ul><li><strong>Single Site</strong> — 1 domain (default)</li><li><strong>Unlimited</strong> — Multiple domains (some plugins)</li></ul><p>If you need to change domains, contact us for up to 2 free changes per year.</p>',
  'es' => '<p>Una licencia estándar se puede usar en <strong>1 dominio</strong>. Se activa por dominio y se necesitan licencias adicionales para otros dominios.</p><ul><li><strong>Sitio único</strong> — 1 dominio (predeterminado)</li><li><strong>Ilimitado</strong> — Múltiples dominios (algunos plugins)</li></ul><p>Si necesita cambiar de dominio, contáctenos para hasta 2 cambios gratuitos por año.</p>',
  'fr' => '<p>Une licence standard peut être utilisée sur <strong>1 domaine</strong>. Elle est activée par domaine, et des licences supplémentaires sont nécessaires pour d\'autres domaines.</p><ul><li><strong>Site unique</strong> — 1 domaine (par défaut)</li><li><strong>Illimité</strong> — Plusieurs domaines (certains plugins)</li></ul><p>Si vous devez changer de domaine, contactez-nous pour jusqu\'à 2 changements gratuits par an.</p>',
  'id' => '<p>Lisensi standar dapat digunakan di <strong>1 domain</strong>. Diaktifkan per domain, dan lisensi tambahan diperlukan untuk domain lain.</p><ul><li><strong>Situs Tunggal</strong> — 1 domain (default)</li><li><strong>Tidak Terbatas</strong> — Beberapa domain (beberapa plugin)</li></ul><p>Jika perlu mengubah domain, hubungi kami untuk hingga 2 perubahan gratis per tahun.</p>',
  'ja' => '<p>標準ライセンスは<strong>1つのドメイン</strong>で使用できます。ドメインベースでアクティベーションされ、他のドメインで使用するには追加ライセンスが必要です。</p><ul><li><strong>シングルサイト</strong> — 1ドメイン（デフォルト）</li><li><strong>無制限</strong> — 複数ドメイン（一部プラグイン）</li></ul><p>ドメイン変更が必要な場合、お問い合わせいただければ年2回まで無料で変更できます。</p>',
  'mn' => '<p>Стандарт лиценз <strong>1 домэйн</strong> дээр ашиглах боломжтой. Домэйн бүрт идэвхжүүлдэг бөгөөд бусад домэйнд нэмэлт лиценз шаардлагатай.</p><ul><li><strong>Нэг сайт</strong> — 1 домэйн (үндсэн)</li><li><strong>Хязгааргүй</strong> — Олон домэйн (зарим плагин)</li></ul><p>Домэйн солих шаардлагатай бол бидэнтэй холбогдоно уу — жилд 2 удаа хүртэл үнэгүй солих боломжтой.</p>',
  'ru' => '<p>Стандартная лицензия может использоваться на <strong>1 домене</strong>. Она активируется для каждого домена, и для других доменов требуются дополнительные лицензии.</p><ul><li><strong>Один сайт</strong> — 1 домен (по умолчанию)</li><li><strong>Безлимитная</strong> — Несколько доменов (некоторые плагины)</li></ul><p>Если вам нужно сменить домен, свяжитесь с нами — до 2 бесплатных замен в год.</p>',
  'tr' => '<p>Standart bir lisans <strong>1 alan adında</strong> kullanılabilir. Alan adı bazında etkinleştirilir ve diğer alan adları için ek lisanslar gereklidir.</p><ul><li><strong>Tek Site</strong> — 1 alan adı (varsayılan)</li><li><strong>Sınırsız</strong> — Birden fazla alan adı (bazı eklentiler)</li></ul><p>Alan adını değiştirmeniz gerekirse, yılda 2 adede kadar ücretsiz değişiklik için bizimle iletişime geçin.</p>',
  'vi' => '<p>Giấy phép tiêu chuẩn có thể sử dụng trên <strong>1 tên miền</strong>. Kích hoạt theo tên miền và cần giấy phép bổ sung cho các tên miền khác.</p><ul><li><strong>Trang đơn</strong> — 1 tên miền (mặc định)</li><li><strong>Không giới hạn</strong> — Nhiều tên miền (một số plugin)</li></ul><p>Nếu cần thay đổi tên miền, hãy liên hệ chúng tôi để được thay đổi miễn phí tối đa 2 lần mỗi năm.</p>',
  'zh_CN' => '<p>标准许可证可以在 <strong>1 个域名</strong>上使用。按域名激活，其他域名需要额外许可证。</p><ul><li><strong>单站点</strong> — 1 个域名（默认）</li><li><strong>无限制</strong> — 多个域名（部分插件）</li></ul><p>如需更换域名，请联系我们，每年最多可免费更换 2 次。</p>',
  'zh_TW' => '<p>標準授權可以在 <strong>1 個網域</strong>上使用。按網域啟用，其他網域需要額外授權。</p><ul><li><strong>單一網站</strong> — 1 個網域（預設）</li><li><strong>無限制</strong> — 多個網域（部分外掛）</li></ul><p>如需更換網域，請聯繫我們，每年最多可免費更換 2 次。</p>',
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
