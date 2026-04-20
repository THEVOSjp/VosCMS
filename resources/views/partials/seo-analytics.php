<?php
/**
 * SEO 검증 + Analytics 태그
 * 관리자 설정: /theadmin/settings/seo
 *
 * 요구 변수: $siteSettings (레이아웃 header.php 에서 주입)
 */

$_ss = $siteSettings ?? [];
$_gv = trim($_ss['google_verification'] ?? '');
$_nv = trim($_ss['naver_verification'] ?? '');
$_ga = trim($_ss['ga_tracking_id'] ?? '');
$_gtm = trim($_ss['gtm_id'] ?? '');

if ($_gv !== ''): ?>
    <meta name="google-site-verification" content="<?= htmlspecialchars($_gv) ?>">
<?php endif;
if ($_nv !== ''): ?>
    <meta name="naver-site-verification" content="<?= htmlspecialchars($_nv) ?>">
<?php endif;

// Google Analytics 4 (gtag.js)
if ($_ga !== ''): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($_ga) ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= htmlspecialchars($_ga) ?>');
    </script>
<?php endif;

// Google Tag Manager (<head> 부분)
if ($_gtm !== ''): ?>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?= htmlspecialchars($_gtm) ?>');</script>
    <!-- End Google Tag Manager -->
<?php endif;
