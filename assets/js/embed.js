/**
 * RezlyX Embed SDK v3.0
 * 외부 사이트에서 RezlyX 콘텐츠를 삽입하는 스크립트
 *
 * 사용법:
 *   <div data-rzx-src="https://mysite.com/rezlyx/board/notice"></div>
 *   <script src="https://mysite.com/rezlyx/assets/js/embed.js"></script>
 *
 * 옵션 (data 속성):
 *   data-rzx-src        : 콘텐츠 URL (필수)
 *   data-rzx-theme      : 테마 (light/dark/auto, 기본: auto)
 *   data-rzx-locale     : 언어 (ko/en/ja 등, 기본: 자동 감지)
 *   data-rzx-height     : 최대 높이 (예: 600px, 기본: auto)
 *   data-rzx-loading    : 로딩 텍스트 (기본: Loading...)
 *   data-rzx-auto-height: 높이 자동 조절 (true/false, 기본: true)
 *   data-rzx-interval   : 높이 감시 간격 ms (기본: 500)
 *   data-rzx-links      : 링크 동작 (blank/self/parent, 기본: blank)
 *   data-rzx-cache      : 캐시 사용 (true/false, 기본: true)
 *   data-rzx-cache-ttl  : 캐시 만료 시간 초 (기본: 300 = 5분)
 *   data-rzx-auth       : 인증 모드 (none/token/cookie, 기본: none)
 *   data-rzx-token      : API 토큰 (auth=token일 때)
 *
 * 글로벌 설정 (스크립트 태그 전에 설정):
 *   window.RZX_CONFIG = {
 *     token: 'your-api-token',
 *     locale: 'ko',
 *     theme: 'dark',
 *     cacheTTL: 600,
 *   };
 *
 * 이벤트 콜백:
 *   RezlyXEmbed.on('load', (el, src) => { ... })
 *   RezlyXEmbed.on('error', (el, src, err) => { ... })
 *   RezlyXEmbed.on('resize', (el, height) => { ... })
 *   RezlyXEmbed.on('themeChange', (el, theme) => { ... })
 *   RezlyXEmbed.on('cacheHit', (el, src) => { ... })
 *   RezlyXEmbed.on('authError', (el, src, status) => { ... })
 */
(function() {
    'use strict';

    const RZX_VERSION = '3.0';
    const RZX_CLASS = 'rzx-embed-container';
    const _listeners = {};
    const _globalConfig = window.RZX_CONFIG || {};

    // === 캐시 시스템 ===
    const _cache = {
        _store: {},

        _key(url) {
            return 'rzx_embed_' + url;
        },

        get(url, ttl) {
            const key = this._key(url);
            // 메모리 캐시 우선
            if (this._store[key]) {
                if (Date.now() - this._store[key].time < ttl * 1000) {
                    return this._store[key].html;
                }
                delete this._store[key];
            }
            // sessionStorage 폴백
            try {
                const raw = sessionStorage.getItem(key);
                if (raw) {
                    const data = JSON.parse(raw);
                    if (Date.now() - data.time < ttl * 1000) {
                        this._store[key] = data;
                        return data.html;
                    }
                    sessionStorage.removeItem(key);
                }
            } catch(e) {}
            return null;
        },

        set(url, html) {
            const key = this._key(url);
            const data = { html, time: Date.now() };
            this._store[key] = data;
            try {
                sessionStorage.setItem(key, JSON.stringify(data));
            } catch(e) {}
        },

        clear(url) {
            if (url) {
                const key = this._key(url);
                delete this._store[key];
                try { sessionStorage.removeItem(key); } catch(e) {}
            } else {
                this._store = {};
                try {
                    Object.keys(sessionStorage).forEach(k => {
                        if (k.startsWith('rzx_embed_')) sessionStorage.removeItem(k);
                    });
                } catch(e) {}
            }
        }
    };

    // === 다국어 자동 감지 ===
    function detectLocale() {
        // 1. 글로벌 설정
        if (_globalConfig.locale) return _globalConfig.locale;
        // 2. HTML lang 속성
        const htmlLang = document.documentElement.lang;
        if (htmlLang) return normalizeLocale(htmlLang);
        // 3. 브라우저 언어
        const navLang = navigator.language || navigator.userLanguage || '';
        return normalizeLocale(navLang);
    }

    function normalizeLocale(lang) {
        if (!lang) return '';
        lang = lang.toLowerCase().replace('-', '_');
        // 지원 언어 매핑
        const map = {
            'ko': 'ko', 'ko_kr': 'ko',
            'en': 'en', 'en_us': 'en', 'en_gb': 'en',
            'ja': 'ja', 'ja_jp': 'ja',
            'zh': 'zh_CN', 'zh_cn': 'zh_CN', 'zh_tw': 'zh_TW', 'zh_hant': 'zh_TW',
            'de': 'de', 'de_de': 'de',
            'es': 'es', 'es_es': 'es',
            'fr': 'fr', 'fr_fr': 'fr',
            'id': 'id', 'id_id': 'id',
            'mn': 'mn', 'mn_mn': 'mn',
            'ru': 'ru', 'ru_ru': 'ru',
            'tr': 'tr', 'tr_tr': 'tr',
            'vi': 'vi', 'vi_vn': 'vi',
        };
        return map[lang] || map[lang.split('_')[0]] || lang.split('_')[0];
    }

    // === 스타일 삽입 (1회) ===
    function injectStyles() {
        if (document.getElementById('rzx-embed-styles')) return;
        const style = document.createElement('style');
        style.id = 'rzx-embed-styles';
        style.textContent = `
            .${RZX_CLASS} { position: relative; min-height: 60px; transition: height 0.3s ease; }
            .${RZX_CLASS}.loading { min-height: 120px; }
            .${RZX_CLASS}.loading::before {
                content: '';
                position: absolute; top: 50%; left: 50%;
                width: 24px; height: 24px; margin: -12px 0 0 -12px;
                border: 3px solid #e5e7eb; border-top-color: #3b82f6;
                border-radius: 50%; animation: rzx-spin 0.8s linear infinite;
            }
            .${RZX_CLASS}.loading::after {
                content: attr(data-loading);
                position: absolute; top: calc(50% + 24px); left: 0; right: 0;
                text-align: center; color: #9ca3af; font-size: 13px;
                font-family: system-ui, -apple-system, sans-serif;
            }
            .${RZX_CLASS}.error {
                padding: 20px; text-align: center; color: #ef4444;
                font-size: 14px; font-family: system-ui, sans-serif;
                background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;
            }
            .${RZX_CLASS} img { max-width: 100%; height: auto; }
            @keyframes rzx-spin { to { transform: rotate(360deg); } }
        `;
        document.head.appendChild(style);
    }

    // === 이벤트 시스템 ===
    function emit(event, ...args) {
        (_listeners[event] || []).forEach(fn => {
            try { fn(...args); } catch(e) { console.error('[RezlyX Embed] Callback error:', e); }
        });
    }

    function on(event, fn) {
        if (!_listeners[event]) _listeners[event] = [];
        _listeners[event].push(fn);
    }

    function off(event, fn) {
        if (!_listeners[event]) return;
        if (fn) {
            _listeners[event] = _listeners[event].filter(f => f !== fn);
        } else {
            delete _listeners[event];
        }
    }

    // === URL 빌더 ===
    function buildUrl(src, options) {
        const url = new URL(src, window.location.origin);
        url.searchParams.set('no_layout', '1');
        if (options.locale) url.searchParams.set('lang', options.locale);
        if (options.theme && options.theme !== 'auto') url.searchParams.set('theme', options.theme);
        return url.toString();
    }

    // === 테마 감지 & 매칭 ===
    function detectTheme() {
        if (_globalConfig.theme && _globalConfig.theme !== 'auto') return _globalConfig.theme;
        const html = document.documentElement;
        if (html.classList.contains('dark')) return 'dark';
        if (html.dataset.theme === 'dark') return 'dark';
        if (document.body?.classList.contains('dark')) return 'dark';
        if (window.matchMedia?.('(prefers-color-scheme: dark)').matches) return 'dark';
        return 'light';
    }

    function watchTheme(el) {
        if (!window.matchMedia) return null;
        const mq = window.matchMedia('(prefers-color-scheme: dark)');
        const handler = (e) => {
            const theme = e.matches ? 'dark' : 'light';
            el.classList.toggle('dark', theme === 'dark');
            emit('themeChange', el, theme);
        };
        mq.addEventListener('change', handler);

        // DOM 변경 감시 (호스트 페이지의 class 변경)
        let mutObs = null;
        try {
            mutObs = new MutationObserver(() => {
                const theme = detectTheme();
                el.classList.toggle('dark', theme === 'dark');
            });
            mutObs.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-theme'] });
        } catch(e) {}

        return () => {
            mq.removeEventListener('change', handler);
            if (mutObs) mutObs.disconnect();
        };
    }

    // === 높이 자동 조절 ===
    function setupAutoHeight(el, interval) {
        let lastHeight = 0;
        let observer = null;

        if (window.ResizeObserver) {
            observer = new ResizeObserver(() => {
                const h = el.scrollHeight;
                if (h !== lastHeight && h > 0) {
                    lastHeight = h;
                    el.style.height = h + 'px';
                    emit('resize', el, h);
                }
            });
            observer.observe(el);
        } else {
            const timer = setInterval(() => {
                const h = el.scrollHeight;
                if (h !== lastHeight && h > 0) {
                    lastHeight = h;
                    el.style.height = h + 'px';
                    emit('resize', el, h);
                }
            }, interval);
            return () => clearInterval(timer);
        }
        return () => { if (observer) observer.disconnect(); };
    }

    // === 인증 헤더 생성 ===
    function getAuthHeaders(options) {
        const headers = { 'X-Requested-With': 'RezlyX-Embed' };
        const authMode = options.auth || 'none';

        if (authMode === 'token') {
            const token = options.token || _globalConfig.token || '';
            if (token) {
                headers['Authorization'] = 'Bearer ' + token;
            }
        }
        return headers;
    }

    function getCredentials(options) {
        const authMode = options.auth || 'none';
        if (authMode === 'cookie') return 'include';
        return 'omit';
    }

    // === 콘텐츠 로드 ===
    async function loadContent(el) {
        const src = el.dataset.rzxSrc;
        if (!src) return;

        const options = {
            theme: el.dataset.rzxTheme || _globalConfig.theme || 'auto',
            locale: el.dataset.rzxLocale || _globalConfig.locale || detectLocale(),
            maxHeight: el.dataset.rzxHeight || '',
            loadingText: el.dataset.rzxLoading || 'Loading...',
            autoHeight: el.dataset.rzxAutoHeight !== 'false',
            heightInterval: parseInt(el.dataset.rzxInterval) || 500,
            linkTarget: el.dataset.rzxLinks || 'blank',
            useCache: el.dataset.rzxCache !== 'false',
            cacheTTL: parseInt(el.dataset.rzxCacheTtl) || parseInt(_globalConfig.cacheTTL) || 300,
            auth: el.dataset.rzxAuth || _globalConfig.auth || 'none',
            token: el.dataset.rzxToken || _globalConfig.token || '',
        };

        const url = buildUrl(src, options);

        // 컨테이너 설정
        el.classList.add(RZX_CLASS, 'loading');
        el.setAttribute('data-loading', options.loadingText);
        if (options.maxHeight) {
            el.style.maxHeight = options.maxHeight;
            el.style.overflowY = 'auto';
        }

        // 테마 적용
        const theme = options.theme === 'auto' ? detectTheme() : options.theme;
        if (theme === 'dark') el.classList.add('dark');

        // 테마 변경 감시
        let unwatchTheme = null;
        if (options.theme === 'auto') {
            unwatchTheme = watchTheme(el);
        }

        try {
            let html = null;

            // 캐시 확인
            if (options.useCache) {
                html = _cache.get(url, options.cacheTTL);
                if (html !== null) {
                    emit('cacheHit', el, src);
                    console.log('[RezlyX Embed] Cache hit:', src);
                }
            }

            // 캐시 없으면 fetch
            if (html === null) {
                const response = await fetch(url, {
                    mode: 'cors',
                    credentials: getCredentials(options),
                    headers: getAuthHeaders(options),
                });

                if (response.status === 401 || response.status === 403) {
                    emit('authError', el, src, response.status);
                    throw new Error(`Auth failed (${response.status})`);
                }

                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                html = await response.text();

                // 캐시 저장
                if (options.useCache) {
                    _cache.set(url, html);
                }
            }

            el.classList.remove('loading');
            el.innerHTML = html;

            // 내부 스크립트 실행
            el.querySelectorAll('script').forEach(oldScript => {
                const newScript = document.createElement('script');
                if (oldScript.src) {
                    newScript.src = oldScript.src;
                } else {
                    newScript.textContent = oldScript.textContent;
                }
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            // 링크 동작 설정
            const linkTarget = options.linkTarget === 'self' ? '_self'
                : options.linkTarget === 'parent' ? '_parent' : '_blank';
            el.querySelectorAll('a[href]').forEach(a => {
                const href = a.getAttribute('href');
                if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                    a.setAttribute('target', linkTarget);
                    if (linkTarget === '_blank') a.setAttribute('rel', 'noopener noreferrer');
                }
            });

            // 내부 폼 처리 (인증 모드일 때)
            if (options.auth !== 'none') {
                el.querySelectorAll('form').forEach(form => {
                    form.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        try {
                            const formData = new FormData(form);
                            const resp = await fetch(form.action || url, {
                                method: form.method || 'POST',
                                body: formData,
                                credentials: getCredentials(options),
                                headers: { 'X-Requested-With': 'RezlyX-Embed',
                                    ...(options.token ? { 'Authorization': 'Bearer ' + options.token } : {})
                                },
                            });
                            if (resp.ok) {
                                // 캐시 무효화 후 리로드
                                _cache.clear(url);
                                loadContent(el);
                            }
                        } catch(err) {
                            console.error('[RezlyX Embed] Form submit error:', err);
                        }
                    });
                });
            }

            // 높이 자동 조절
            let unwatchHeight = null;
            if (options.autoHeight && !options.maxHeight) {
                unwatchHeight = setupAutoHeight(el, options.heightInterval);
            }

            // cleanup 저장
            el._rzxCleanup = () => {
                if (unwatchTheme) unwatchTheme();
                if (unwatchHeight) unwatchHeight();
            };

            emit('load', el, src);
            console.log('[RezlyX Embed] Loaded:', src, options.auth !== 'none' ? '(auth: ' + options.auth + ')' : '');

        } catch (err) {
            el.classList.remove('loading');
            el.classList.add('error');
            el.textContent = err.message.includes('Auth') ? 'Authentication required.' : 'Failed to load content.';
            emit('error', el, src, err);
            console.error('[RezlyX Embed] Error:', src, err.message);
        }
    }

    // === 언로드 ===
    function unload(el) {
        if (el._rzxCleanup) {
            el._rzxCleanup();
            delete el._rzxCleanup;
        }
        el.innerHTML = '';
        el.classList.remove(RZX_CLASS, 'loading', 'error', 'dark');
        el.removeAttribute('style');
    }

    // === 초기화 ===
    function init() {
        injectStyles();
        document.querySelectorAll('[data-rzx-src]').forEach(loadContent);
        console.log('[RezlyX Embed] SDK v' + RZX_VERSION + ' initialized',
            _globalConfig.locale ? '(locale: ' + _globalConfig.locale + ')' : '',
            _globalConfig.token ? '(auth: token)' : '');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // === 글로벌 API ===
    window.RezlyXEmbed = {
        version: RZX_VERSION,
        load: loadContent,
        unload: unload,
        reload: function(selector) {
            const els = selector
                ? document.querySelectorAll(selector)
                : document.querySelectorAll('[data-rzx-src]');
            els.forEach(el => { unload(el); loadContent(el); });
        },
        on: on,
        off: off,
        clearCache: _cache.clear.bind(_cache),
        setToken: function(token) { _globalConfig.token = token; },
        setLocale: function(locale) { _globalConfig.locale = locale; },
    };
})();
