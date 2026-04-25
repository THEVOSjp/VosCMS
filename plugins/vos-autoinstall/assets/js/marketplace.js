/**
 * VosCMS Marketplace - Alpine.js Components
 */

document.addEventListener('alpine:init', () => {
    // 설치 진행 상태 관리
    Alpine.data('installManager', () => ({
        installing: false,
        progress: 0,
        status: '',
        error: '',

        async install(itemId, adminUrl) {
            this.installing = true;
            this.progress = 10;
            this.status = 'Downloading...';
            this.error = '';

            try {
                const response = await fetch(`${adminUrl}/autoinstall/install`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `item_id=${itemId}`
                });

                this.progress = 70;
                this.status = 'Installing...';

                const data = await response.json();

                if (data.success) {
                    this.progress = 100;
                    this.status = 'Complete!';
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    this.error = data.message || 'Installation failed';
                    this.installing = false;
                }
            } catch (err) {
                this.error = 'Network error';
                this.installing = false;
            }
        }
    }));

    // 라이선스 키 복사
    Alpine.data('licenseKey', () => ({
        copied: false,

        async copy(key) {
            try {
                await navigator.clipboard.writeText(key);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            } catch {
                // Fallback
                const input = document.createElement('input');
                input.value = key;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            }
        }
    }));
});
