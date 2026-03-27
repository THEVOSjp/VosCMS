<?php
/**
 * RezlyX Widget Renderer Module
 *
 * 위젯을 DB에서 로드하고 HTML로 렌더링하는 공통 모듈.
 * 모든 위젯 페이지(홈, 커스텀 등)에서 재사용 가능.
 *
 * 사용법:
 *   $renderer = new WidgetRenderer($pdo, 'home', $currentLocale, $baseUrl);
 *   $widgets = $renderer->getWidgets();
 *   if ($renderer->hasWidgets()) {
 *       echo $renderer->renderAll();
 *   }
 *   // 또는 개별 렌더링:
 *   foreach ($widgets as $w) {
 *       echo $renderer->render($w);
 *   }
 */

namespace RzxLib\Core\Modules;

class WidgetRenderer
{
    private $pdo;
    private $pageSlug;
    private $locale;
    private $baseUrl;
    private $widgets = null;
    private ?WidgetLoader $loader = null;

    public function __construct(\PDO $pdo, string $pageSlug = 'home', string $locale = 'ko', string $baseUrl = '')
    {
        $this->pdo = $pdo;
        $this->pageSlug = $pageSlug;
        $this->locale = $locale;
        $this->baseUrl = $baseUrl;

        // 파일 기반 위젯 로더 자동 초기화
        $widgetsDir = defined('BASE_PATH') ? BASE_PATH . '/widgets' : dirname(__DIR__, 3) . '/widgets';
        if (is_dir($widgetsDir)) {
            $this->loader = new WidgetLoader($pdo, $widgetsDir);
        }
    }

    public function getBaseUrl(): string { return $this->baseUrl; }
    public function getLocale(): string { return $this->locale; }
    public function getLoader(): ?WidgetLoader { return $this->loader; }

    /**
     * 페이지에 배치된 위젯 목록 로드
     */
    public function getWidgets(): array
    {
        if ($this->widgets !== null) return $this->widgets;

        try {
            $stmt = $this->pdo->prepare("
                SELECT pw.*, w.slug as widget_slug, w.name as widget_name, w.template, w.css, w.js,
                       w.type as widget_type, w.default_config, w.config_schema
                FROM rzx_page_widgets pw
                JOIN rzx_widgets w ON pw.widget_id = w.id
                WHERE pw.page_slug = ? AND pw.is_active = 1 AND w.is_active = 1
                ORDER BY pw.sort_order ASC
            ");
            $stmt->execute([$this->pageSlug]);
            $this->widgets = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->widgets = [];
        }

        return $this->widgets;
    }

    /**
     * 위젯이 배치되어 있는지 확인
     */
    public function hasWidgets(): bool
    {
        return !empty($this->getWidgets());
    }

    /**
     * 모든 위젯을 순서대로 렌더링
     */
    public function renderAll(): string
    {
        $html = '';
        foreach ($this->getWidgets() as $w) {
            $html .= $this->render($w);
        }
        return $html;
    }

    /**
     * 단일 위젯 렌더링
     */
    public function render(array $widget): string
    {
        $config = $this->mergeConfig($widget);
        $config['_widget_id'] = $widget['id'] ?? null;
        $slug = $widget['widget_slug'];

        // 1순위: 파일 기반 위젯 (widgets/{slug}/render.php)
        if ($this->loader && $this->loader->hasRender($slug)) {
            $result = $this->loader->render($slug, $config, $widget, $this);
            if ($result !== '') return $result;
        }

        // 2순위: 내장 위젯 렌더링 (WidgetRenderer 메서드)
        $method = 'render' . str_replace('-', '', ucwords($slug, '-'));
        if (method_exists($this, $method)) {
            return $this->$method($config, $widget);
        }

        // 3순위: 커스텀 위젯 (DB template 기반)
        return $this->renderCustom($config, $widget);
    }

    /**
     * 위젯 설정 병합 (default + instance config)
     */
    private function mergeConfig(array $widget): array
    {
        $default = json_decode($widget['default_config'] ?? '{}', true) ?: [];
        $instance = json_decode($widget['config'] ?? '{}', true) ?: [];
        return array_merge($default, $instance);
    }

    /**
     * 다국어 텍스트 가져오기
     * 폴백 체인: db_trans(설정언어→영어→기본언어) → config[locale] → config[en] → config[기본언어] → config 문자열 → $fallback
     */
    public function t(array $config, string $key, string $fallback = '', ?int $widgetId = null): string
    {
        // 1. db_trans 폴백 체인 (위젯 ID가 있으면)
        $wid = $widgetId ?? ($config['_widget_id'] ?? null);
        if ($wid && function_exists('db_trans')) {
            $dbValue = db_trans('widget.' . $wid . '.' . $key);
            if (!empty($dbValue)) return $dbValue;
        }

        // 2. config 배열 (빈 문자열도 스킵하여 폴백)
        if (isset($config[$key])) {
            if (is_array($config[$key])) {
                $arr = $config[$key];
                $defaultLocale = $_ENV['DEFAULT_LOCALE'] ?? 'ko';
                // 폴백 체인: 현재 로케일 → 영어 → 기본언어
                $chain = [$this->locale];
                if ($this->locale !== 'en') $chain[] = 'en';
                if ($this->locale !== $defaultLocale && $defaultLocale !== 'en') $chain[] = $defaultLocale;
                foreach ($chain as $loc) {
                    if (!empty($arr[$loc])) return $arr[$loc];
                }
                return $fallback;
            }
            return $config[$key];
        }
        return $fallback;
    }

    // ========================================
    // 내장 위젯 렌더러
    // ========================================

    private function renderHero(array $c, array $w): string
    {
        $title = htmlspecialchars($this->t($c, 'title', __('home.hero.title_1')));
        $subtitle = nl2br(htmlspecialchars($this->t($c, 'subtitle', __('home.hero.subtitle'))));
        $layout = $c['layout'] ?? 'center';
        $height = $c['height'] ?? 'medium';
        $bgType = $c['bg_type'] ?? 'gradient';

        // 높이 클래스
        $heightMap = ['small' => 'py-16', 'medium' => 'py-24', 'large' => 'py-32', 'full' => 'py-24 min-h-screen flex items-center'];
        $hClass = $heightMap[$height] ?? $heightMap['medium'];

        // 배경 스타일
        $bgStyle = '';
        $overlayHtml = '';
        $bgVideoHtml = '';
        if ($bgType === 'video' && !empty($c['bg_video'])) {
            $rawVideo = $c['bg_video'];
            // 상대경로를 절대경로로 변환
            if (!str_starts_with($rawVideo, 'http') && !str_starts_with($rawVideo, '//')) {
                $rawVideo = $this->baseUrl . '/' . ltrim($rawVideo, '/');
            }
            $from = htmlspecialchars($c['bg_gradient_from'] ?? '#2563eb');
            $to = htmlspecialchars($c['bg_gradient_to'] ?? '#1e40af');
            $bgStyle = "background:linear-gradient(135deg,{$from},{$to});";
            $opacity = ($c['bg_overlay'] ?? 50) / 100;
            $overlayHtml = '<div class="absolute inset-0 bg-black z-[5]" style="opacity:' . $opacity . '"></div>';

            // YouTube / Vimeo 감지 → iframe embed
            $ytId = $this->extractYouTubeId($rawVideo);
            $vimeoId = $this->extractVimeoId($rawVideo);
            $iframeStyle = 'pointer-events:none;position:absolute;top:50%;left:50%;width:max(100%,177.78vh);height:max(100%,56.25vw);transform:translate(-50%,-50%);';
            if ($ytId) {
                $bgVideoHtml = '<div class="absolute inset-0 overflow-hidden z-[1]"><iframe src="https://www.youtube.com/embed/' . htmlspecialchars($ytId) . '?autoplay=1&mute=1&loop=1&playlist=' . htmlspecialchars($ytId) . '&controls=0&showinfo=0&rel=0&modestbranding=1&playsinline=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="' . $iframeStyle . '"></iframe></div>';
            } elseif ($vimeoId) {
                $bgVideoHtml = '<div class="absolute inset-0 overflow-hidden z-[1]"><iframe src="https://player.vimeo.com/video/' . htmlspecialchars($vimeoId) . '?autoplay=1&muted=1&loop=1&background=1" frameborder="0" allow="autoplay" allowfullscreen style="' . $iframeStyle . '"></iframe></div>';
            } else {
                // 일반 비디오 파일 (mp4, webm 등)
                $bgVideo = htmlspecialchars($rawVideo);
                $bgVideoHtml = '<video class="absolute inset-0 w-full h-full object-cover z-[1]" autoplay muted loop playsinline><source src="' . $bgVideo . '"></video>';
            }
        } elseif ($bgType === 'image' && !empty($c['bg_image'])) {
            $bgImg = htmlspecialchars($c['bg_image']);
            $bgStyle = "background-image:url('{$bgImg}');background-size:cover;background-position:center;";
            $opacity = ($c['bg_overlay'] ?? 50) / 100;
            $overlayHtml = '<div class="absolute inset-0 bg-black z-[5]" style="opacity:' . $opacity . '"></div>';
        } elseif ($bgType === 'solid') {
            $bgColor = htmlspecialchars($c['bg_color'] ?? '#2563eb');
            $bgStyle = "background-color:{$bgColor};";
        } else {
            $from = htmlspecialchars($c['bg_gradient_from'] ?? '#2563eb');
            $to = htmlspecialchars($c['bg_gradient_to'] ?? '#1e40af');
            $bgStyle = "background:linear-gradient(135deg,{$from},{$to});";
        }

        // 버튼 렌더링
        $buttons = $c['buttons'] ?? [];
        if (empty($buttons)) {
            $btnText = htmlspecialchars($this->t($c, 'cta_text', __('home.hero.cta_booking')));
            $btnUrl = htmlspecialchars($c['cta_url'] ?? $this->baseUrl . '/booking');
            $buttonsHtml = '<a data-widget-field="cta_text" href="' . $btnUrl . '" class="inline-flex items-center px-8 py-4 bg-white text-blue-600 font-semibold rounded-xl hover:bg-blue-50 transition shadow-lg">' . $btnText . '<svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>';
        } else {
            $buttonsHtml = '<div class="flex flex-wrap gap-4 justify-center">';
            foreach ($buttons as $btn) {
                $bText = htmlspecialchars($this->tBtn($btn, 'text', 'Button'));
                $bUrl = htmlspecialchars($btn['url'] ?? '#');
                $bStyle = $btn['style'] ?? 'primary';
                $bClass = $bStyle === 'secondary'
                    ? 'px-8 py-4 border-2 border-white text-white font-semibold rounded-xl hover:bg-white/10 transition'
                    : ($bStyle === 'outline'
                        ? 'px-8 py-4 border border-white/50 text-white font-medium rounded-xl hover:bg-white/10 transition'
                        : 'px-8 py-4 bg-white text-blue-600 font-semibold rounded-xl hover:bg-blue-50 transition shadow-lg');
                $buttonsHtml .= '<a href="' . $bUrl . '" class="inline-flex items-center ' . $bClass . '">' . $bText . '</a>';
            }
            $buttonsHtml .= '</div>';
        }

        // 히어로 이미지(들)
        $heroImgHtml = '';
        $heroImagesOverlay = '';
        $heroImages = $c['hero_images'] ?? [];
        // 하위호환: 기존 hero_image 단일 필드
        if (empty($heroImages) && !empty($c['hero_image'])) {
            $heroImages = [['url' => $c['hero_image'], 'position' => 'center', 'layer' => 1, 'size' => 'medium']];
        }
        if (!empty($heroImages)) {
            // 레이아웃이 left-image/right-image면 첫 이미지를 텍스트 옆에 배치
            if (($layout === 'left-image' || $layout === 'right-image') && !empty($heroImages[0]['url'])) {
                $firstImg = htmlspecialchars($heroImages[0]['url']);
                $heroImgHtml = '<div class="flex-shrink-0"><img src="' . $firstImg . '" alt="" class="w-full max-w-md rounded-2xl shadow-2xl"></div>';
                array_shift($heroImages);
            }
            // 나머지 이미지들은 절대 위치로 오버레이
            foreach ($heroImages as $hi) {
                if (empty($hi['url'])) continue;
                $hiUrl = htmlspecialchars($hi['url']);
                $hiPos = $hi['position'] ?? 'center';
                $hiLayer = intval($hi['layer'] ?? 1);
                $hiSize = $hi['size'] ?? 'medium';
                $sizeMap = ['small' => 'w-24 md:w-32', 'medium' => 'w-40 md:w-56', 'large' => 'w-56 md:w-80', 'full' => 'w-full'];
                $sizeClass = $sizeMap[$hiSize] ?? $sizeMap['medium'];
                $posStyle = $this->heroImagePosition($hiPos);
                $heroImagesOverlay .= '<img src="' . $hiUrl . '" alt="" class="absolute ' . $sizeClass . ' object-contain pointer-events-none" style="' . $posStyle . 'z-index:' . ($hiLayer + 5) . ';">';
            }
        }

        // 레이아웃별 렌더링
        $inner = '';
        if ($layout === 'left-image' || $layout === 'right-image') {
            $textBlock = '<div class="flex-1 ' . ($layout === 'right-image' ? 'text-left' : 'text-right') . '">'
                . '<h1 data-widget-field="title" class="text-4xl md:text-5xl font-bold mb-6">' . $title . '</h1>'
                . '<p data-widget-field="subtitle" class="text-xl text-white/80 mb-8">' . $subtitle . '</p>'
                . $buttonsHtml . '</div>';
            $imgBlock = $heroImgHtml ?: '<div class="flex-1"></div>';
            $flexDir = $layout === 'left-image' ? 'flex-row-reverse' : '';
            $inner = '<div class="w-full ' . $hClass . '"><div class="w-full flex flex-col md:flex-row items-center gap-12 px-8 ' . $flexDir . '">' . $textBlock . $imgBlock . '</div></div>';
        } elseif ($layout === 'fullscreen') {
            $inner = '<div class="w-full ' . $hClass . '"><div class="w-full text-center">'
                . '<h1 data-widget-field="title" class="text-5xl md:text-6xl font-bold mb-6">' . $title . '</h1>'
                . '<p data-widget-field="subtitle" class="text-xl text-white/80 mb-8 max-w-2xl mx-auto">' . $subtitle . '</p>'
                . $buttonsHtml . '</div></div>';
        } else {
            // center (기본)
            $titleStyle = $this->elementPositionStyle($c, 'title');
            $subtitleStyle = $this->elementPositionStyle($c, 'subtitle');
            $inner = '<div class="w-full ' . $hClass . '"><div class="w-full text-center' . (!empty($c['element_positions']) ? ' relative' : '') . '">'
                . '<h1 data-widget-field="title" class="text-4xl md:text-5xl font-bold mb-6"' . ($titleStyle ? ' style="' . $titleStyle . '"' : '') . '>' . $title . '</h1>'
                . '<p data-widget-field="subtitle" class="text-xl text-white/80 mb-8 max-w-2xl mx-auto"' . ($subtitleStyle ? ' style="' . $subtitleStyle . '"' : '') . '>' . $subtitle . '</p>'
                . $buttonsHtml . '</div></div>';
        }

        $sectionClass = 'relative text-white overflow-hidden' . ($height === 'full' ? ' min-h-screen' : '');
        return '<section class="' . $sectionClass . '" style="' . $bgStyle . '">'
            . $bgVideoHtml
            . $overlayHtml
            . $heroImagesOverlay
            . '<div class="relative z-20 w-full">' . $inner . '</div>'
            . '</section>';
    }

    /**
     * 히어로 이미지 9방향 위치 → CSS style
     */
    public function heroImagePosition(string $pos): string
    {
        $map = [
            'top-left'      => 'top:5%;left:5%;',
            'top-center'    => 'top:5%;left:50%;transform:translateX(-50%);',
            'top-right'     => 'top:5%;right:5%;',
            'center-left'   => 'top:50%;left:5%;transform:translateY(-50%);',
            'center'        => 'top:50%;left:50%;transform:translate(-50%,-50%);',
            'center-right'  => 'top:50%;right:5%;transform:translateY(-50%);',
            'bottom-left'   => 'bottom:5%;left:5%;',
            'bottom-center' => 'bottom:5%;left:50%;transform:translateX(-50%);',
            'bottom-right'  => 'bottom:5%;right:5%;',
        ];
        return $map[$pos] ?? $map['center'];
    }

    /**
     * element_positions 기반 인라인 스타일 반환
     * config['element_positions']['title'] = 'top-center' → style="position:absolute;top:8%;left:50%;..."
     */
    public function elementPositionStyle(array $config, string $fieldKey): string
    {
        $positions = $config['element_positions'] ?? [];
        if (empty($positions[$fieldKey])) return '';

        $pos = $positions[$fieldKey];
        $map = [
            'top-left'      => 'position:absolute;top:8%;left:8%;z-index:20;',
            'top-center'    => 'position:absolute;top:8%;left:50%;transform:translateX(-50%);z-index:20;',
            'top-right'     => 'position:absolute;top:8%;right:8%;z-index:20;',
            'center-left'   => 'position:absolute;top:50%;left:8%;transform:translateY(-50%);z-index:20;',
            'center'        => 'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:20;',
            'center-right'  => 'position:absolute;top:50%;right:8%;transform:translateY(-50%);z-index:20;',
            'bottom-left'   => 'position:absolute;bottom:8%;left:8%;z-index:20;',
            'bottom-center' => 'position:absolute;bottom:8%;left:50%;transform:translateX(-50%);z-index:20;',
            'bottom-right'  => 'position:absolute;bottom:8%;right:8%;z-index:20;',
        ];
        return $map[$pos] ?? '';
    }

    /**
     * 버튼 배열 내 다국어 텍스트
     */
    public function tBtn(array $btn, string $key, string $fallback = ''): string
    {
        if (isset($btn[$key])) {
            if (is_array($btn[$key])) {
                $arr = $btn[$key];
                $defaultLocale = $_ENV['DEFAULT_LOCALE'] ?? 'ko';
                $chain = [$this->locale];
                if ($this->locale !== 'en') $chain[] = 'en';
                if ($this->locale !== $defaultLocale && $defaultLocale !== 'en') $chain[] = $defaultLocale;
                foreach ($chain as $loc) {
                    if (!empty($arr[$loc])) return $arr[$loc];
                }
                return $fallback;
            }
            return $btn[$key];
        }
        return $fallback;
    }

    /**
     * YouTube URL에서 영상 ID 추출
     */
    public function extractYouTubeId(string $url): ?string
    {
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Vimeo URL에서 영상 ID 추출
     */
    public function extractVimeoId(string $url): ?string
    {
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    private function renderFeatures(array $c, array $w): string
    {
        $icons = [
            'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
            'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        ];
        $colors = ['blue', 'green', 'purple'];
        $keys = ['mobile', 'realtime', 'easy_payment'];

        $cards = '';
        for ($i = 0; $i < 3; $i++) {
            $fc = $colors[$i];
            $title = htmlspecialchars(__('home.features.' . $keys[$i] . '.title'));
            $desc = htmlspecialchars(__('home.features.' . $keys[$i] . '.desc'));
            $cards .= <<<CARD
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-8 text-center hover:shadow-lg transition">
    <div class="w-16 h-16 bg-{$fc}-100 dark:bg-{$fc}-900/50 rounded-xl flex items-center justify-center mx-auto mb-6">
        <svg class="w-8 h-8 text-{$fc}-600 dark:text-{$fc}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{$icons[$i]}"/></svg>
    </div>
    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">{$title}</h3>
    <p class="text-gray-600 dark:text-zinc-400">{$desc}</p>
</div>
CARD;
        }

        $sTitle = htmlspecialchars(__('home.features.title'));
        $sSub = htmlspecialchars(__('home.features.subtitle'));
        return <<<HTML
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{$sTitle}</h2>
            <p class="text-gray-600 dark:text-zinc-400">{$sSub}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">{$cards}</div>
    </div>
</section>
HTML;
    }

    private function renderServices(array $c, array $w): string
    {
        $limit = (int)($c['count'] ?? 6);
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM rzx_services WHERE is_active = 1 ORDER BY sort_order ASC LIMIT ?");
            $stmt->execute([$limit]);
            $services = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $services = [];
        }

        $cards = '';
        foreach ($services as $svc) {
            $name = htmlspecialchars($svc['name'] ?? '');
            $desc = htmlspecialchars($svc['description'] ?? '');
            $price = !empty($c['show_price']) ? '<span class="text-blue-600 dark:text-blue-400 font-bold">' . number_format($svc['price'] ?? 0) . '원</span>' : '';
            $dur = !empty($c['show_duration']) ? '<span class="text-sm text-zinc-500">' . ($svc['duration'] ?? 0) . '분</span>' : '';
            $cards .= <<<CARD
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 hover:shadow-lg transition">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{$name}</h3>
    <p class="text-sm text-gray-600 dark:text-zinc-400 mb-4">{$desc}</p>
    <div class="flex items-center justify-between">{$price}{$dur}</div>
</div>
CARD;
        }

        $sTitle = htmlspecialchars(__('home.services.title'));
        $sSub = htmlspecialchars(__('home.services.subtitle'));
        return <<<HTML
<section class="py-20 bg-gray-50 dark:bg-zinc-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{$sTitle}</h2>
            <p class="text-gray-600 dark:text-zinc-400">{$sSub}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">{$cards}</div>
    </div>
</section>
HTML;
    }

    private function renderStats(array $c, array $w): string
    {
        $stats = $c['items'] ?? [
            ['number' => '10,000+', 'label' => __('home.stats.total_bookings')],
            ['number' => '98%', 'label' => __('home.stats.satisfaction')],
            ['number' => '500+', 'label' => __('home.stats.partners')],
            ['number' => '24/7', 'label' => __('home.stats.support')],
        ];
        $items = '';
        foreach ($stats as $s) {
            $num = htmlspecialchars($s['number'] ?? '');
            $lbl = htmlspecialchars($s['label'] ?? '');
            $items .= "<div><p class=\"text-3xl font-bold text-blue-600 dark:text-blue-400\">{$num}</p><p class=\"text-sm text-gray-600 dark:text-zinc-400 mt-1\">{$lbl}</p></div>";
        }
        return <<<HTML
<section class="py-16 bg-white dark:bg-zinc-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">{$items}</div>
    </div>
</section>
HTML;
    }

    private function renderTestimonials(array $c, array $w): string
    {
        $reviews = $c['items'] ?? [];
        $cards = '';
        foreach ($reviews as $rv) {
            $stars = str_repeat('&#9733;', (int)($rv['rating'] ?? 5));
            $content = htmlspecialchars($rv['content'] ?? '');
            $name = htmlspecialchars($rv['name'] ?? '');
            $cards .= <<<CARD
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6">
    <div class="flex text-yellow-400 mb-3">{$stars}</div>
    <p class="text-gray-600 dark:text-zinc-400 mb-4">{$content}</p>
    <p class="font-semibold text-gray-900 dark:text-white">{$name}</p>
</div>
CARD;
        }
        $sTitle = htmlspecialchars(__('home.testimonials.title'));
        $sSub = htmlspecialchars(__('home.testimonials.subtitle'));
        return <<<HTML
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{$sTitle}</h2>
            <p class="text-gray-600 dark:text-zinc-400">{$sSub}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">{$cards}</div>
    </div>
</section>
HTML;
    }

    private function renderCta(array $c, array $w): string
    {
        $title = htmlspecialchars($this->t($c, 'title', __('home.cta.title')));
        $subtitle = nl2br(htmlspecialchars($this->t($c, 'subtitle', __('home.cta.subtitle'))));
        $btnText = htmlspecialchars($this->t($c, 'btn_text', __('home.cta.start_free')));
        $btnUrl = htmlspecialchars($c['btn_url'] ?? $this->baseUrl . '/booking');

        return <<<HTML
<section class="py-20 bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-800 dark:to-zinc-900 text-white">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h2 data-widget-field="title" class="text-3xl font-bold mb-4">{$title}</h2>
        <p data-widget-field="subtitle" class="text-blue-100 mb-8">{$subtitle}</p>
        <a data-widget-field="btn_text" href="{$btnUrl}" class="inline-flex items-center px-8 py-4 bg-white text-blue-600 font-semibold rounded-xl hover:bg-blue-50 transition shadow-lg">{$btnText}</a>
    </div>
</section>
HTML;
    }

    private function renderText(array $c, array $w): string
    {
        $content = $this->t($c, 'content', '');
        $customCss = trim($c['custom_css'] ?? '');
        $customJs = trim($c['custom_js'] ?? '');
        $scopeId = 'rzx-text-' . mt_rand(1000, 9999);
        // content 내 <style> 태그의 전역 셀렉터를 스코핑
        if (preg_match('/<style/i', $content)) {
            $content = preg_replace_callback('/<style([^>]*)>(.*?)<\/style>/is', function($m) use ($scopeId) {
                return '<style' . $m[1] . '>' . $this->scopeWidgetCss($m[2], '#' . $scopeId) . '</style>';
            }, $content);
        }
        // 커스텀 CSS 스코핑
        $cssBlock = '';
        if ($customCss) {
            $scopedCss = $this->scopeWidgetCss($customCss, '#' . $scopeId);
            $cssBlock = '<style>' . $scopedCss . '</style>';
        }
        // 커스텀 JS
        $jsBlock = '';
        if ($customJs) {
            $jsBlock = '<script>(function(){var el=document.getElementById("' . $scopeId . '");' . $customJs . '})();</script>';
        }
        return <<<HTML
{$cssBlock}
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4">
        <div id="{$scopeId}" data-widget-field="content" class="page-content text-gray-700 dark:text-zinc-300">{$content}</div>
    </div>
</section>
{$jsBlock}
HTML;
    }

    private function renderSpacer(array $c, array $w): string
    {
        $h = (int)($c['height'] ?? 48);
        return "<div style=\"height:{$h}px\"></div>";
    }

    /**
     * 커스텀 위젯 (template 기반)
     */
    private function renderCustom(array $config, array $widget): string
    {
        $tpl = $widget['template'] ?? '';
        if (empty($tpl)) return '';

        // {{변수}} 치환
        foreach ($config as $key => $val) {
            if (is_string($val)) {
                $tpl = str_replace('{{' . $key . '}}', htmlspecialchars($val), $tpl);
            } elseif (is_array($val) && isset($val[$this->locale])) {
                $tpl = str_replace('{{' . $key . '}}', htmlspecialchars($val[$this->locale]), $tpl);
            }
        }

        // 위젯 CSS를 스코핑 (body, * 등 전역 셀렉터가 페이지를 오염시키지 않도록)
        $scopeId = 'rzx-widget-' . ($widget['widget_slug'] ?? 'custom') . '-' . mt_rand(1000, 9999);
        $css = '';
        if (!empty($widget['css'])) {
            $scopedCss = $this->scopeWidgetCss($widget['css'], '#' . $scopeId);
            $css = '<style>' . $scopedCss . '</style>';
        }
        $js = !empty($widget['js']) ? '<script>' . $widget['js'] . '</script>' : '';

        return $css . '<div id="' . $scopeId . '">' . $tpl . '</div>' . $js;
    }

    /**
     * 위젯 CSS를 특정 스코프로 제한
     * body, html, *, :root 등 전역 셀렉터를 스코프 ID로 교체
     */
    public function scopeWidgetCss(string $css, string $scope): string
    {
        // body, html 셀렉터를 스코프로 교체 (멀티라인 대응: s 플래그)
        $css = preg_replace('/\bbody\b(?=[^{}]*\{)/s', $scope, $css);
        $css = preg_replace('/\bhtml\b(?=[^{}]*\{)/s', $scope, $css);
        // *, *::before, *::after 전역 셀렉터를 스코프 하위로 제한
        $css = preg_replace('/(?<![a-zA-Z0-9_-])\*(?=\s*[,{:]|\s*::)/s', $scope . ' *', $css);
        // :root 를 스코프로 교체
        $css = preg_replace('/:root\b/s', $scope, $css);
        return $css;
    }
}
