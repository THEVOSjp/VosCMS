<?php
/**
 * Admin PWA Scripts
 * Include this before </body> in all admin pages
 */
?>
<!-- PWA Admin Service Worker Registration -->
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {
            try {
                const registration = await navigator.serviceWorker.register('/admin-sw.js', { scope: '/admin' });
                console.log('[Admin PWA] Service Worker registered:', registration.scope);

                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    console.log('[Admin PWA] New service worker installing...');

                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            console.log('[Admin PWA] New version available');
                            showAdminUpdateNotification();
                        }
                    });
                });
            } catch (error) {
                console.error('[Admin PWA] Service Worker registration failed:', error);
            }
        });
    }

    function showAdminUpdateNotification() {
        const banner = document.createElement('div');
        banner.className = 'fixed bottom-4 right-4 bg-blue-600 text-white p-4 rounded-lg shadow-lg z-50 max-w-sm';
        banner.innerHTML = `
            <p class="text-sm font-medium mb-2"><?= __('admin.pwa.update_available') ?? '새 버전이 있습니다' ?></p>
            <div class="flex gap-2">
                <button onclick="location.reload()" class="flex-1 bg-white text-blue-600 px-3 py-1.5 rounded text-sm font-medium hover:bg-blue-50">
                    <?= __('admin.pwa.update') ?? '업데이트' ?>
                </button>
                <button onclick="this.parentElement.parentElement.remove()" class="px-3 py-1.5 text-sm hover:bg-blue-700 rounded">
                    <?= __('admin.pwa.later') ?? '나중에' ?>
                </button>
            </div>
        `;
        document.body.appendChild(banner);
    }

    // Admin PWA Install
    let adminDeferredPrompt;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        adminDeferredPrompt = e;
        console.log('[Admin PWA] Install prompt ready');

        // Show install button if exists
        const installBtn = document.getElementById('admin-pwa-install-btn');
        if (installBtn) {
            installBtn.classList.remove('hidden');
        }
    });

    async function installAdminPWA() {
        if (!adminDeferredPrompt) return;
        adminDeferredPrompt.prompt();
        const { outcome } = await adminDeferredPrompt.userChoice;
        console.log('[Admin PWA] Install outcome:', outcome);
        adminDeferredPrompt = null;

        const installBtn = document.getElementById('admin-pwa-install-btn');
        if (installBtn) {
            installBtn.classList.add('hidden');
        }
    }

    window.addEventListener('appinstalled', () => {
        console.log('[Admin PWA] App installed');
        adminDeferredPrompt = null;
    });
</script>
