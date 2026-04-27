<?php
/**
 * VosCMS Admin Sidebar — 동적 렌더러
 *
 * 메뉴 소스:
 *   1. config/admin-menu.php  — 코어 메뉴 (업데이트 시 보존)
 *   2. plugin.json menus.admin — 플러그인 메뉴 (PluginManager)
 *
 * 이 파일은 순수 렌더러로, 메뉴 정의를 포함하지 않습니다.
 * Core 업데이트 시 안전하게 덮어쓸 수 있습니다.
 */

// $adminUrl 보장
if (!isset($adminUrl)) {
    $adminUrl = '/' . ($config['admin_path'] ?? 'admin');
}

$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$adminPath = $config['admin_path'] ?? ($_ENV['ADMIN_PATH'] ?? 'admin');
$_locale = function_exists('current_locale') ? current_locale() : ($config['locale'] ?? 'ko');

// ── 메뉴 로드 (load_menu 공통 헬퍼) ──
$_allMenus = function_exists('load_menu') ? load_menu('admin') : [];

// 플러그인 동적 메뉴 (PluginManager의 menus.admin — plugin.json 스캔과 별도)
if (isset($pluginManager)) {
    foreach ($pluginManager->getAdminMenus() as $_pm) {
        // load_menu에서 이미 추가된 것과 중복 방지
        $_exists = false;
        foreach ($_allMenus as $_existing) {
            if (($existing['id'] ?? '') === ($_pm['id'] ?? '') || (($_existing['items'][0]['route'] ?? '') && ($_existing['items'][0]['route'] ?? '') === ($_pm['items'][0]['route'] ?? ''))) {
                $_exists = true; break;
            }
        }
        if (!$_exists) $_allMenus[] = array_merge($_pm, ['position' => $_pm['position'] ?? 50]);
    }
    usort($_allMenus, fn($a, $b) => ($a['position'] ?? 50) <=> ($b['position'] ?? 50));
}

// ── 헬퍼 함수 ──
// 다국어 텍스트 추출 (load_menu가 label을 번역하지만 title은 배열일 수 있음)
$_lt = function($val) use ($_locale) {
    if (!$val) return '';
    if (is_string($val)) return $val;
    if (is_array($val)) return $val[$_locale] ?? $val['en'] ?? $val['ko'] ?? reset($val) ?: '';
    return '';
};

// 현재 경로 (캐시)
$_adminCleanPath = rtrim(preg_replace('#^/' . preg_quote($adminPath, '#') . '#', '', parse_url($currentPath, PHP_URL_PATH) ?? ''), '/');

// 메뉴 활성 판별 — 하위 메뉴용 (접두사 매칭, 형제 중 가장 구체적인 것만 활성)
$_isActive = function($route, array $siblings = []) use ($_adminCleanPath) {
    if ($route === '') return false;
    $_route = '/' . ltrim($route, '/');
    // 정확 매칭
    if ($_adminCleanPath === $_route) return true;
    // 접두사 매칭 (하위 경로 허용)
    if (!str_starts_with($_adminCleanPath, $_route . '/')) return false;
    // 형제 라우트 중 더 구체적인 매칭이 있으면 현재 라우트는 비활성
    foreach ($siblings as $s) {
        $sr = '/' . ltrim($s, '/');
        if ($sr === $_route) continue;
        if (strlen($sr) > strlen($_route) && str_starts_with($_adminCleanPath, $sr . '/') || $_adminCleanPath === $sr) {
            return false; // 더 구체적인 형제가 매칭됨
        }
    }
    return true;
};

// 경로 하위 매칭 (부모 그룹 판별용)
$_pathStartsWith = function($route) use ($currentPath, $adminPath) {
    if ($route === '') return false;
    $_path = rtrim(preg_replace('#^/' . preg_quote($adminPath, '#') . '#', '', parse_url($currentPath, PHP_URL_PATH) ?? ''), '/');
    $_route = '/' . ltrim($route, '/');
    return $_path === $_route || str_starts_with($_path, $_route . '/');
};

// 부모 메뉴 활성 판별 (route_prefix 지원 — 설정 탭 등 내부 라우트 포함)
$_isGroupActive = function($menu) use ($currentPath, $adminPath, $_isActive, $_pathStartsWith) {
    // route_prefix가 있으면 해당 prefix 하위 라우트 전체 매칭
    if (!empty($menu['route_prefix'])) {
        return $_pathStartsWith($menu['route_prefix']);
    }
    // 일반: 서브메뉴 항목 중 하나라도 활성이면
    foreach ($menu['items'] ?? [] as $_item) {
        if ($_isActive($_item['route'] ?? '')) return true;
    }
    // 단일 메뉴
    if (!empty($menu['route'])) return $_isActive($menu['route']);
    return false;
};

// 권한 체크
$_canShow = function($menu) {
    $perm = $menu['permission'] ?? null;
    $master = $menu['master'] ?? false;
    if ($master && !\RzxLib\Core\Auth\AdminAuth::isMaster()) return false;
    if ($perm && !\RzxLib\Core\Auth\AdminAuth::can($perm)) return false;
    return true;
};
?>
<script>
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        document.documentElement.classList.add('sidebar-is-collapsed');
    }
</script>
<style>
    #adminSidebar.sidebar-collapsed nav a,
    #adminSidebar.sidebar-collapsed nav button { padding-left: 0; padding-right: 0; justify-content: center; }
    #adminSidebar.sidebar-collapsed nav a svg.flex-shrink-0,
    #adminSidebar.sidebar-collapsed nav button svg.flex-shrink-0 { margin-right: 0; }
    #adminSidebar.sidebar-collapsed .p-6 { padding: 1rem 0.5rem; justify-content: center; }
    #adminSidebar.sidebar-collapsed #sidebarToggleBtn { margin: 0 auto; }
    #adminSidebar.sidebar-collapsed .sidebar-text { display: none; }
    #adminSidebar.sidebar-collapsed [id^="submenu_"] { display: none; }
    #adminSidebar.sidebar-collapsed #sidebarToggleIcon { transform: rotate(180deg); }
    .sidebar-is-collapsed #adminSidebar { width: 4rem !important; }
    .sidebar-is-collapsed main.flex-1 { margin-left: 4rem !important; }
    input[type="date"]::-webkit-calendar-picker-indicator,
    input[type="time"]::-webkit-calendar-picker-indicator { display: none !important; }
    input[type="date"], input[type="time"] { -webkit-appearance: none; }
</style>
<aside id="adminSidebar" class="w-64 bg-zinc-950 min-h-screen fixed transition-all duration-300 z-40 overflow-hidden">
    <div class="p-6 flex items-center justify-between">
        <a href="<?= $adminUrl ?>" class="text-xl font-bold text-white truncate sidebar-text">
            <?= htmlspecialchars($config['app_name'] ?? 'VosCMS') ?>
            <span class="text-blue-400 text-sm ml-1"><?= __('admin.title') ?></span>
        </a>
        <button id="sidebarToggleBtn" onclick="toggleSidebar()" class="p-1.5 text-zinc-400 hover:text-white hover:bg-zinc-800 rounded-lg transition flex-shrink-0" title="Toggle Sidebar">
            <svg id="sidebarToggleIcon" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
    </div>
    <nav class="mt-6">
<?php
    $_menuIdx = 0;
    $_anyActive = false; // 다른 메뉴가 활성이면 대시보드 비활성

    // 먼저 활성 메뉴가 있는지 체크 (대시보드 활성 판별용)
    foreach ($_allMenus as $_menu) {
        if (($_menu['id'] ?? '') === 'dashboard') continue;
        if ($_isGroupActive($_menu)) { $_anyActive = true; break; }
    }

    // 3개 영역 분리: top (코어 상단) / main (자동 추가/플러그인) / bottom (코어 하단)
    $_sectioned = ['top' => [], 'main' => [], 'bottom' => []];
    foreach ($_allMenus as $_m) {
        $_sec = $_m['section'] ?? 'main';
        if (!isset($_sectioned[$_sec])) $_sec = 'main';
        $_sectioned[$_sec][] = $_m;
    }

    $_sectionRendered = false;
    foreach ($_sectioned as $_sectionKey => $_sectionMenus):
        // 영역 내 표시 가능한 메뉴가 하나도 없으면 구분선 포함 통째로 건너뛰기
        $_hasVisible = false;
        foreach ($_sectionMenus as $_chk) {
            if ($_canShow($_chk)) { $_hasVisible = true; break; }
        }
        if (!$_hasVisible) continue;

        if ($_sectionRendered):
?>
        <div class="my-2 mx-3 border-t border-zinc-800/60"></div>
<?php
        endif;
        $_sectionRendered = true;

    foreach ($_sectionMenus as $_menu):
        if (!$_canShow($_menu)) continue;

        $_id = $_menu['id'] ?? 'menu_' . $_menuIdx;
        $_title = $_lt($_menu['title'] ?? '');
        $_icon = $_menu['icon'] ?? '';
        $_route = $_menu['route'] ?? '';
        $_items = $_menu['items'] ?? [];
        $_submenuId = 'submenu_' . $_menuIdx;
        $_menuIdx++;

        // 활성 상태 판별
        $_isMenuActive = false;
        if ($_id === 'dashboard') {
            $_isMenuActive = !$_anyActive;
        } else {
            $_isMenuActive = $_isGroupActive($_menu);
        }

        // 표시 가능한 서브메뉴 항목 필터링
        $_visibleItems = [];
        foreach ($_items as $_item) {
            if (!empty($_item['master']) && !\RzxLib\Core\Auth\AdminAuth::isMaster()) continue;
            if (!empty($_item['permission']) && !\RzxLib\Core\Auth\AdminAuth::can($_item['permission'])) continue;
            $_visibleItems[] = $_item;
        }

        if (empty($_visibleItems)):
            // ── 단일 메뉴 (서브메뉴 없음) ──
?>
        <a href="<?= $adminUrl ?>/<?= $_route ?>" class="flex items-center px-6 py-3 <?= $_isMenuActive ? 'text-white bg-blue-600' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white' ?>" title="<?= htmlspecialchars($_title) ?>">
            <?php if ($_icon): ?><svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $_icon ?>"/></svg><?php endif; ?>
            <span class="sidebar-text"><?= htmlspecialchars($_title) ?></span>
        </a>
<?php
        else:
            // ── 서브메뉴 있는 메뉴 ──
?>
        <div class="has-submenu" data-submenu="<?= $_submenuId ?>">
            <button onclick="toggleSubmenu('<?= $_submenuId ?>')" class="flex items-center justify-between w-full px-6 py-3 <?= $_isMenuActive ? 'text-white bg-blue-600' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white' ?>">
                <div class="flex items-center">
                    <?php if ($_icon): ?><svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $_icon ?>"/></svg><?php endif; ?>
                    <span class="sidebar-text"><?= htmlspecialchars($_title) ?></span>
                </div>
                <svg class="w-4 h-4 transition-transform sidebar-text <?= $_isMenuActive ? 'rotate-180' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="<?= $_submenuId ?>" class="<?= $_isMenuActive ? '' : 'hidden' ?> bg-zinc-900">
<?php       $_siblingRoutes = array_map(fn($i) => $i['route'] ?? '', $_visibleItems);
            foreach ($_visibleItems as $_item):
                $_itemTitle = $_lt($_item['title'] ?? '');
                $_itemRoute = $_item['route'] ?? '';
                $_itemIcon = $_item['icon'] ?? '';
                $_itemActive = $_isActive($_itemRoute, $_siblingRoutes);
?>
                <a href="<?= $adminUrl ?>/<?= $_itemRoute ?>" class="flex items-center px-6 py-2.5 pl-14 <?= $_itemActive ? 'text-white bg-blue-600/80' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white' ?> text-sm">
                    <?php if ($_itemIcon): ?><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $_itemIcon ?>"/></svg><?php endif; ?>
                    <?= htmlspecialchars($_itemTitle) ?>
                </a>
<?php       endforeach; ?>
            </div>
        </div>
<?php
        endif;
    endforeach;
    endforeach;
?>
    </nav>
    <div class="absolute bottom-0 w-full p-4 border-t border-zinc-800/50">
        <?php
        $versionFile = BASE_PATH . '/version.json';
        $versionInfo = file_exists($versionFile) ? json_decode(file_get_contents($versionFile), true) : ['version' => '1.0.0'];
        ?>
        <div class="flex items-center justify-between text-zinc-500 text-xs">
            <span class="font-medium sidebar-text">VosCMS</span>
            <span class="sidebar-text">v<?= htmlspecialchars($versionInfo['version'] ?? '1.0.0') ?></span>
        </div>
    </div>
</aside>

<!-- 플라이아웃 팝업 (접힌 사이드바 호버 시) -->
<div id="sidebarFlyout" class="fixed hidden bg-zinc-900 rounded-r-lg shadow-2xl border border-zinc-700/50 py-1.5 min-w-[200px] z-50" style="left:4rem">
    <div id="flyoutTitle" class="px-4 py-2 text-xs font-semibold text-zinc-300 border-b border-zinc-700/50 mb-1"></div>
    <div id="flyoutLinks"></div>
</div>

<script>
    // ===== 사이드바 접기/펼치기 =====
    function toggleSidebar() {
        var sidebar = document.getElementById('adminSidebar');
        var isCollapsed = sidebar.classList.contains('sidebar-collapsed');
        var main = document.querySelector('main.flex-1');

        if (isCollapsed) {
            document.documentElement.classList.remove('sidebar-is-collapsed');
            sidebar.classList.remove('sidebar-collapsed');
            sidebar.style.width = '16rem';
            if (main) main.style.marginLeft = '16rem';
            localStorage.setItem('sidebarCollapsed', 'false');
        } else {
            document.documentElement.classList.add('sidebar-is-collapsed');
            sidebar.classList.add('sidebar-collapsed');
            sidebar.style.width = '4rem';
            if (main) main.style.marginLeft = '4rem';
            localStorage.setItem('sidebarCollapsed', 'true');
        }
    }

    // 페이지 로드 시 저장된 상태 복원
    (function() {
        var main = document.querySelector('main.flex-1');
        if (main) main.style.transition = 'margin-left 0.3s';
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            var sidebar = document.getElementById('adminSidebar');
            sidebar.classList.add('sidebar-collapsed');
            sidebar.style.width = '4rem';
            if (main) main.style.marginLeft = '4rem';
        }
    })();

    // ===== 서브메뉴 토글 (범용) =====
    function toggleSubmenu(id) {
        if (document.getElementById('adminSidebar').classList.contains('sidebar-collapsed')) return;
        var sub = document.getElementById(id);
        if (!sub) return;
        sub.classList.toggle('hidden');
        var arrow = sub.previousElementSibling?.querySelector('svg:last-child');
        if (arrow) arrow.classList.toggle('rotate-180');
    }

    // ===== 플라이아웃 (접힌 상태 호버) =====
    (function() {
        var flyout = document.getElementById('sidebarFlyout');
        var flyoutTitle = document.getElementById('flyoutTitle');
        var flyoutLinks = document.getElementById('flyoutLinks');
        var hideTimer = null;

        function showFlyout(menuDiv) {
            var sidebar = document.getElementById('adminSidebar');
            if (!sidebar.classList.contains('sidebar-collapsed')) return;
            var submenuId = menuDiv.dataset.submenu;
            var submenu = document.getElementById(submenuId);
            if (!submenu) return;
            clearTimeout(hideTimer);
            var titleEl = menuDiv.querySelector('.sidebar-text');
            flyoutTitle.textContent = titleEl ? titleEl.textContent.trim() : '';
            flyoutLinks.innerHTML = '';
            submenu.querySelectorAll('a').forEach(function(a) {
                var clone = a.cloneNode(true);
                clone.className = 'flex items-center px-4 py-2 text-sm text-zinc-300 hover:bg-zinc-800 hover:text-white transition';
                clone.querySelectorAll('svg').forEach(function(svg) { svg.className.baseVal = 'w-4 h-4 mr-2.5 flex-shrink-0'; });
                flyoutLinks.appendChild(clone);
            });
            var rect = menuDiv.querySelector('button').getBoundingClientRect();
            flyout.style.top = rect.top + 'px';
            flyout.classList.remove('hidden');
        }

        function scheduleFlyoutHide() {
            hideTimer = setTimeout(function() { flyout.classList.add('hidden'); }, 200);
        }

        document.querySelectorAll('.has-submenu').forEach(function(menuDiv) {
            menuDiv.addEventListener('mouseenter', function() { showFlyout(menuDiv); });
            menuDiv.addEventListener('mouseleave', scheduleFlyoutHide);
        });
        flyout.addEventListener('mouseenter', function() { clearTimeout(hideTimer); });
        flyout.addEventListener('mouseleave', function() { flyout.classList.add('hidden'); });
    })();

    // ===== date/time input 캘린더/시계 아이콘 + 요일 표시 =====
    (function() {
        var DAYS = <?php
            $_calLocale2 = $config['locale'] ?? 'ko';
            $_calLangFile2 = BASE_PATH . '/resources/lang/' . $_calLocale2 . '/reservations.php';
            $_calLang2 = file_exists($_calLangFile2) ? (include $_calLangFile2) : [];
            echo json_encode($_calLang2['cal_weekdays'] ?? ['일','월','화','수','목','금','토']);
        ?>;
        var calSvg = '<svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
        var timeSvg = '<svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

        function enhance(input) {
            if (input.dataset.dayInit) return;
            input.dataset.dayInit = '1';
            var isDate = input.type === 'date';
            var wrap = document.createElement('div');
            wrap.style.cssText = 'position:relative;display:inline-flex;align-items:center;width:100%;';
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(input);
            var iconBtn = document.createElement('span');
            iconBtn.innerHTML = isDate ? calSvg : timeSvg;
            iconBtn.style.cssText = 'position:absolute;right:8px;top:50%;transform:translateY(-50%);cursor:pointer;opacity:0.5;';
            iconBtn.className = 'text-zinc-500 dark:text-zinc-400 hover:opacity-100';
            wrap.appendChild(iconBtn);
            input.style.paddingRight = '32px';
            iconBtn.addEventListener('click', function() { try { input.showPicker(); } catch(e) { input.focus(); } });
            if (isDate) {
                var lbl = document.createElement('span');
                lbl.style.cssText = 'margin-left:6px;font-size:12px;font-weight:600;white-space:nowrap;';
                wrap.insertAdjacentElement('afterend', lbl);
                function update() {
                    var v = input.value;
                    if (!v) { lbl.textContent = ''; return; }
                    var d = new Date(v + 'T00:00:00');
                    if (isNaN(d.getTime())) { lbl.textContent = ''; return; }
                    var day = d.getDay();
                    lbl.textContent = '[' + DAYS[day] + ']';
                    lbl.style.color = day === 0 ? '#ef4444' : day === 6 ? '#3b82f6' : '#71717a';
                }
                input.addEventListener('change', update);
                update();
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input[type="date"], input[type="time"]').forEach(enhance);
        });
        new MutationObserver(function(muts) {
            muts.forEach(function(m) {
                m.addedNodes.forEach(function(n) {
                    if (n.nodeType !== 1) return;
                    if (n.matches && (n.matches('input[type="date"]') || n.matches('input[type="time"]'))) enhance(n);
                    if (n.querySelectorAll) n.querySelectorAll('input[type="date"], input[type="time"]').forEach(enhance);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    })();
</script>
