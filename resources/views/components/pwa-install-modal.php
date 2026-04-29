<?php
/**
 * PWA 설치 안내 모달 + 우측 베너
 *
 * 사용법: 레이아웃의 </body> 직전에 include
 *   <?php include BASE_PATH . '/resources/views/components/pwa-install-modal.php'; ?>
 *
 * 동작:
 *   1. window.beforeinstallprompt 캡처 → 3초 뒤 모달 노출
 *   2. 이미 standalone 모드 (PWA 안)이면 표시 안 함
 *   3. "나중에" 클릭 → 모달 닫고 우측 하단 베너 노출 (지속)
 *   4. 베너 클릭 → 모달 다시 열림
 *   5. 베너 X 클릭 → 베너 숨김 + 7일 쿨다운
 *   6. "다시 보지 않기" 클릭 → 베너+모달 둘 다 숨김 + 1년 쿨다운
 *   7. 설치 완료(appinstalled) → 베너+모달 둘 다 자동 숨김
 */

// ── 결제·중요 흐름 페이지에서는 PWA 모달 HTML 출력 자체를 skip ──
// (UX 결정: 결제 도중 install prompt 띄우면 흐름 방해. body state 정리는
//  components/body-state-reset.php 가 모든 페이지에서 처리하므로 별도 cleanup
//  스크립트 emit 불필요)
$_skipPwaPaths = [
    '/service/order',     // 호스팅 신청 + /service/order/complete
    '/payment/',          // 결제 콜백/완료 흐름
    '/install',           // 설치 마법사
];
$_currentReqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
foreach ($_skipPwaPaths as $_skipPath) {
    if (str_starts_with($_currentReqPath, $_skipPath)) return;
}

// 어떤 PWA 컨텍스트인지 자동 감지 — admin 영역이면 admin 아이콘/이름, 아니면 front
$_pwaIsAdmin = isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/' . ($config['admin_path'] ?? 'admin'));
$_pwaIcon = $_pwaIsAdmin
    ? ($siteSettings['pwa_admin_icon'] ?? '')
    : ($siteSettings['pwa_front_icon'] ?? '');
$_pwaName = $_pwaIsAdmin
    ? ($siteSettings['pwa_admin_name'] ?? ($config['app_name'] ?? 'VosCMS'))
    : ($siteSettings['pwa_front_name'] ?? ($config['app_name'] ?? 'VosCMS'));
$_pwaThemeColor = $_pwaIsAdmin
    ? ($siteSettings['pwa_admin_theme_color'] ?? '#18181b')
    : ($siteSettings['pwa_front_theme_color'] ?? '#3b82f6');

$_pwaIconUrl = $_pwaIcon ? rtrim($baseUrl ?? '', '/') . $_pwaIcon : '';
?>
<!-- ── PWA 설치 안내 모달 ── -->
<div id="pwaInstallModal"
     class="hidden fixed inset-0 z-[9999] items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
     role="dialog" aria-modal="true" aria-labelledby="pwaInstallTitle">
    <div class="relative w-full max-w-md bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl overflow-hidden animate-pwa-modal-in">
        <!-- 헤더 (앱 아이콘 + 닫기) -->
        <div class="relative px-6 pt-6 pb-4">
            <button type="button"
                    onclick="window.pwaInstallModal.close('later')"
                    class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
                    aria-label="<?= htmlspecialchars(__('common.pwa.modal_later')) ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="flex items-center gap-4">
                <?php if ($_pwaIconUrl): ?>
                    <div class="flex-shrink-0 w-16 h-16 rounded-2xl overflow-hidden shadow-lg ring-1 ring-zinc-200 dark:ring-zinc-700"
                         style="background:<?= htmlspecialchars($_pwaThemeColor) ?>;">
                        <img src="<?= htmlspecialchars($_pwaIconUrl) ?>" alt="" class="w-full h-full object-cover">
                    </div>
                <?php else: ?>
                    <div class="flex-shrink-0 w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shadow-lg"
                         style="background:<?= htmlspecialchars($_pwaThemeColor) ?>;color:#fff;">
                        📱
                    </div>
                <?php endif; ?>

                <div class="min-w-0 flex-1">
                    <h3 id="pwaInstallTitle" class="text-lg font-bold text-zinc-900 dark:text-white leading-tight">
                        <?= htmlspecialchars(__('common.pwa.modal_title')) ?>
                    </h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 truncate">
                        <?= htmlspecialchars($_pwaName) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- 본문 (서브타이틀 + 3가지 가치) -->
        <div class="px-6 pb-2">
            <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed">
                <?= htmlspecialchars(__('common.pwa.modal_subtitle')) ?>
            </p>

            <ul class="mt-5 space-y-3">
                <?php for ($i = 1; $i <= 3; $i++): ?>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-9 h-9 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-lg">
                        <?= htmlspecialchars(__('common.pwa.modal_bullet_' . $i . '_icon')) ?>
                    </span>
                    <span class="flex-1 pt-1.5 text-sm text-zinc-700 dark:text-zinc-300">
                        <?= htmlspecialchars(__('common.pwa.modal_bullet_' . $i)) ?>
                    </span>
                </li>
                <?php endfor; ?>
            </ul>
        </div>

        <!-- 버튼 영역 -->
        <div class="px-6 pt-4 pb-5 space-y-2">
            <button type="button"
                    onclick="window.pwaInstallModal.install()"
                    class="w-full py-3 px-4 rounded-xl font-semibold text-white text-base transition shadow-md hover:shadow-lg active:scale-[0.98]"
                    style="background:<?= htmlspecialchars($_pwaThemeColor) ?>;">
                <?= htmlspecialchars(__('common.pwa.modal_install_btn')) ?>
            </button>

            <div class="flex items-center justify-between gap-2 pt-1">
                <button type="button"
                        onclick="window.pwaInstallModal.close('later')"
                        class="flex-1 py-2 px-3 text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-white transition">
                    <?= htmlspecialchars(__('common.pwa.modal_later')) ?>
                </button>
                <span class="text-zinc-300 dark:text-zinc-700">·</span>
                <button type="button"
                        onclick="window.pwaInstallModal.close('dismiss')"
                        class="flex-1 py-2 px-3 text-sm text-zinc-400 dark:text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                    <?= htmlspecialchars(__('common.pwa.modal_dismiss')) ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── 우측 세로 중앙 미니 베너 (모달 "나중에" 후 지속 표시) ── -->
<div id="pwaInstallBanner"
     class="hidden fixed right-5 top-1/2 z-[9998] items-center gap-2 pl-2 pr-1 py-1.5 bg-white dark:bg-zinc-900 rounded-full shadow-2xl ring-1 ring-zinc-200 dark:ring-zinc-700 animate-pwa-banner-in">
    <button type="button"
            onclick="window.pwaInstallModal.openFromBanner()"
            class="flex items-center gap-2.5 pl-1 pr-2 py-1 rounded-full hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
        <?php if ($_pwaIconUrl): ?>
            <span class="flex-shrink-0 w-8 h-8 rounded-lg overflow-hidden ring-1 ring-zinc-200 dark:ring-zinc-700"
                  style="background:<?= htmlspecialchars($_pwaThemeColor) ?>;">
                <img src="<?= htmlspecialchars($_pwaIconUrl) ?>" alt="" class="w-full h-full object-cover">
            </span>
        <?php else: ?>
            <span class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-base"
                  style="background:<?= htmlspecialchars($_pwaThemeColor) ?>;color:#fff;">📱</span>
        <?php endif; ?>
        <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 whitespace-nowrap">
            <?= htmlspecialchars(__('common.pwa.modal_install_btn')) ?>
        </span>
    </button>
    <button type="button"
            onclick="window.pwaInstallModal.dismissBanner()"
            class="flex-shrink-0 w-7 h-7 flex items-center justify-center rounded-full text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
            aria-label="<?= htmlspecialchars(__('common.pwa.modal_later')) ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>

<style>
@keyframes pwa-modal-in {
    from { opacity: 0; transform: translateY(20px) scale(0.96); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.animate-pwa-modal-in { animation: pwa-modal-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
#pwaInstallModal.flex { display: flex !important; }

/* 우측 세로 중앙 정렬 — top:50% + translateY(-50%) */
#pwaInstallBanner { transform: translateY(-50%); }
@keyframes pwa-banner-in {
    from { opacity: 0; transform: translate(120%, -50%); }
    to   { opacity: 1; transform: translate(0, -50%); }
}
.animate-pwa-banner-in {
    animation: pwa-banner-in 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    animation-fill-mode: forwards;
}
#pwaInstallBanner.flex { display: flex !important; }
</style>

<script>
(function () {
    'use strict';

    // ── 쿨다운 키: front / admin 컨텍스트 별도 관리 ──
    const SCOPE = <?= json_encode($_pwaIsAdmin ? 'admin' : 'front') ?>;
    const COOLDOWN_KEY = 'pwa_install_cooldown_' + SCOPE;
    const BANNER_KEY = 'pwa_banner_visible_' + SCOPE;
    const LATER_DAYS = 7;
    const DISMISS_DAYS = 365;
    const SHOW_DELAY_MS = 3000;

    // ── 결제·중요 흐름에서는 모달/베너 자동 노출 차단 ──
    // (결제 직후 갑자기 모달이 뜨면 잔여 레이어처럼 보여 사용자 흐름을 방해)
    const SKIP_PATHS = [
        /^\/service\/order/,           // 호스팅 서비스 신청 + 완료 페이지
        /^\/payment\//,                // 결제 콜백/완료
        /^\/install/,                  // 설치 마법사 (혹시 모를 케이스)
    ];
    function isSkippedPath() {
        return SKIP_PATHS.some(re => re.test(location.pathname));
    }

    let _deferredPrompt = null;
    let _modal = null;
    let _banner = null;

    function getModal() {
        if (!_modal) _modal = document.getElementById('pwaInstallModal');
        return _modal;
    }
    function getBanner() {
        if (!_banner) _banner = document.getElementById('pwaInstallBanner');
        return _banner;
    }

    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true ||
               document.referrer.startsWith('android-app://');
    }

    function isInCooldown() {
        try {
            const until = parseInt(localStorage.getItem(COOLDOWN_KEY) || '0', 10);
            return Date.now() < until;
        } catch (e) { return false; }
    }
    function setCooldown(days) {
        try { localStorage.setItem(COOLDOWN_KEY, String(Date.now() + days * 86400 * 1000)); } catch (e) {}
    }
    function clearCooldown() {
        try { localStorage.removeItem(COOLDOWN_KEY); } catch (e) {}
    }
    function setBannerFlag(on) {
        try {
            if (on) localStorage.setItem(BANNER_KEY, '1');
            else    localStorage.removeItem(BANNER_KEY);
        } catch (e) {}
    }
    function isBannerFlagged() {
        try { return localStorage.getItem(BANNER_KEY) === '1'; } catch (e) { return false; }
    }

    function showModal() {
        const m = getModal();
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    function hideModal() {
        const m = getModal();
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = '';
    }
    function showBanner() {
        const b = getBanner();
        if (!b) return;
        b.classList.remove('hidden');
        b.classList.add('flex');
    }
    function hideBanner() {
        const b = getBanner();
        if (!b) return;
        b.classList.add('hidden');
        b.classList.remove('flex');
    }

    // ── 외부 인터페이스 ──
    window.pwaInstallModal = {
        async install() {
            if (!_deferredPrompt) { hideModal(); return; }
            try {
                _deferredPrompt.prompt();
                const { outcome } = await _deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    // 설치 수락 → 베너+모달 모두 숨김 (appinstalled 이벤트가 곧 발생)
                    setBannerFlag(false);
                    hideBanner();
                } else {
                    // 사용자가 시스템 prompt 에서 거절 → 베너 유지
                    setBannerFlag(true);
                    showBanner();
                }
            } catch (e) {
                console.warn('[PWA] install error:', e);
            }
            _deferredPrompt = null;
            hideModal();
        },
        // 모달 "나중에" → 베너로 전환 (쿨다운 설정 안 함)
        // 모달 "다시 보지 않기" → 베너+모달 영구 숨김 (1년 쿨다운)
        close(reason) {
            hideModal();
            if (reason === 'dismiss') {
                setCooldown(DISMISS_DAYS);
                setBannerFlag(false);
                hideBanner();
            } else {
                // "나중에" → 베너로 minimize
                setBannerFlag(true);
                showBanner();
            }
        },
        // 베너 클릭 → 모달 다시 열기 (베너는 그대로 유지)
        openFromBanner() {
            showModal();
        },
        // 베너 X 클릭 → 베너 숨김 + 7일 쿨다운
        dismissBanner() {
            hideBanner();
            setBannerFlag(false);
            setCooldown(LATER_DAYS);
        },
        // 디버그용 — 콘솔에서 호출
        forceShow() { clearCooldown(); setBannerFlag(false); hideBanner(); showModal(); },
        forceShowBanner() { clearCooldown(); setBannerFlag(true); hideModal(); showBanner(); },
        diagnose() {
            console.group('[PWA 진단]');
            console.log('SCOPE:', SCOPE);
            console.log('isStandalone():', isStandalone());
            console.log('isBannerFlagged():', isBannerFlagged());
            console.log('isInCooldown():', isInCooldown());
            try {
                const until = parseInt(localStorage.getItem(COOLDOWN_KEY) || '0', 10);
                if (until) console.log('cooldown 만료:', new Date(until).toLocaleString());
            } catch (e) {}
            console.log('window.deferredPrompt 존재:', !!window.deferredPrompt);
            console.log('  → null 이면 Chrome 이 beforeinstallprompt 를 던지지 않음 = PWA 이미 설치됐거나 설치 불가능 상태');
            console.log('display-mode:standalone matches:', window.matchMedia('(display-mode: standalone)').matches);
            console.groupEnd();
        },
    };

    // ── beforeinstallprompt 캡처 ──
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        _deferredPrompt = e;
        window.deferredPrompt = e;

        if (isStandalone()) return;

        // 결제·서비스 신청 흐름 — 모달/베너 모두 차단 (사용자 결제 흐름 방해 방지)
        if (isSkippedPath()) return;

        // 베너 깃발이 켜져있으면 베너만 띄움 (이전에 "나중에" 했던 경우)
        if (isBannerFlagged()) {
            showBanner();
            return;
        }

        // 쿨다운 중이면 아무것도 안 함
        if (isInCooldown()) return;

        // 어드민 컨텍스트: 모달 건너뛰고 바로 베너 표시 (관리자는 빠른 접근 우선)
        if (SCOPE === 'admin') {
            setBannerFlag(true);
            showBanner();
            return;
        }

        // 프론트: 페이지 안정화 후 모달 노출 (자세한 안내)
        setTimeout(showModal, SHOW_DELAY_MS);
    });

    // 설치 완료 → 모든 UI 숨김 + 플래그 정리
    window.addEventListener('appinstalled', () => {
        _deferredPrompt = null;
        setBannerFlag(false);
        clearCooldown();
        hideModal();
        hideBanner();
    });

    // 페이지 로드 시 베너 깃발 살아있고 standalone 아니면 베너 미리 보임
    // (beforeinstallprompt 가 이번 세션엔 안 올 수도 있어서 — 깃발만으로 베너 표시)
    document.addEventListener('DOMContentLoaded', () => {
        if (isStandalone()) return;
        if (isSkippedPath()) return;  // 결제·서비스 신청 흐름 차단
        if (isBannerFlagged()) {
            showBanner();
        }
    });

    // ESC 키로 모달 닫기 (베너로 전환)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const m = getModal();
            if (m && m.classList.contains('flex')) {
                window.pwaInstallModal.close('later');
            }
        }
    });

    // 모달 배경 클릭 시 닫기 (베너로 전환)
    document.addEventListener('click', (e) => {
        const m = getModal();
        if (m && e.target === m && m.classList.contains('flex')) {
            window.pwaInstallModal.close('later');
        }
    });
})();
</script>
