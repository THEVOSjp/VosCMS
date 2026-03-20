/**
 * RezlyX 게시판 자동 링크 + OG 카드 프리뷰
 *
 * 에디터: rzxAutoLinkInEditor(editorEl) — Summernote onKeyup/onPaste
 * 본문:   자동 실행 (.board-content 내 URL → 링크 + OG 카드)
 * 에디터 카드: rzxInsertOgCard(editorId, url) — 에디터에 OG 카드 삽입
 */

(function() {
    'use strict';

    var urlPattern = /(?<!\w)((?:https?:\/\/|ftp:\/\/|www\.)[^\s<>"')\]]+)/gi;
    var ogApiUrl = (document.querySelector('meta[name="base-url"]')?.content || '') + '/board/api/og';

    // ─── 에디터: 텍스트 URL → <a> 자동 변환 ───
    window.rzxAutoLinkInEditor = function(editorEl) {
        var editable = editorEl.querySelector ? editorEl.querySelector('.note-editable') : editorEl;
        if (!editable) return;

        var walker = document.createTreeWalker(editable, NodeFilter.SHOW_TEXT, null, false);
        var textNodes = [];
        while (walker.nextNode()) {
            var node = walker.currentNode;
            if (node.parentElement && node.parentElement.closest('a, .rzx-og-card')) continue;
            if (urlPattern.test(node.textContent)) textNodes.push(node);
            urlPattern.lastIndex = 0;
        }

        textNodes.forEach(function(node) {
            var text = node.textContent;
            var parts = text.split(urlPattern);
            if (parts.length <= 1) return;

            var frag = document.createDocumentFragment();
            parts.forEach(function(part) {
                urlPattern.lastIndex = 0;
                if (urlPattern.test(part)) {
                    var href = part.startsWith('www.') ? 'https://' + part : part;
                    var a = document.createElement('a');
                    a.href = href;
                    a.textContent = part;
                    a.target = '_blank';
                    a.rel = 'noopener noreferrer';
                    frag.appendChild(a);
                } else {
                    frag.appendChild(document.createTextNode(part));
                }
                urlPattern.lastIndex = 0;
            });
            node.parentNode.replaceChild(frag, node);
        });
    };

    // ─── 에디터: OG 카드 삽입 ───
    window.rzxInsertOgCard = async function(editorId, url) {
        if (!url) return;
        var href = url.startsWith('www.') ? 'https://' + url : url;
        console.log('[OgCard] Fetching:', href);

        try {
            var fd = new FormData();
            fd.append('url', href);
            var resp = await fetch(ogApiUrl, { method: 'POST', body: fd });
            var data = await resp.json();
            if (!data.success || !data.og?.title) {
                console.log('[OgCard] No OG data for:', href);
                return;
            }

            var og = data.og;
            var cardHtml = buildOgCardHtml(og);
            $('#' + editorId).summernote('pasteHTML', cardHtml + '<p><br></p>');
            console.log('[OgCard] Inserted card for:', og.title);
        } catch (e) {
            console.error('[OgCard] Error:', e);
        }
    };

    // ─── OG 카드 HTML 생성 ───
    function buildOgCardHtml(og) {
        var esc = function(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; };
        var img = og.image ? '<div style="flex-shrink:0;width:120px;min-height:80px;background:#f4f4f5;overflow:hidden;border-radius:8px 0 0 8px"><img src="' + esc(og.image) + '" style="width:100%;height:100%;object-fit:cover;display:block" onerror="this.parentElement.style.display=\'none\'"></div>' : '';
        var favicon = og.favicon ? '<img src="' + esc(og.favicon) + '" style="width:14px;height:14px;border-radius:2px;display:inline;vertical-align:middle" onerror="this.style.display=\'none\'">&nbsp;' : '';

        return '<div class="rzx-og-card" contenteditable="false" style="margin:12px auto;border:1px solid #d4d4d8;border-radius:8px;overflow:hidden;max-width:480px;cursor:pointer" onclick="window.open(\'' + esc(og.url) + '\',\'_blank\')">'
            + '<a href="' + esc(og.url) + '" target="_blank" rel="noopener noreferrer" style="display:flex;text-decoration:none;color:inherit">'
            + img
            + '<div style="flex:1;padding:12px;min-width:0">'
            + '<div style="font-size:14px;font-weight:600;color:#18181b;line-height:1.3;margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + esc(og.title) + '</div>'
            + (og.description ? '<div style="font-size:12px;color:#71717a;line-height:1.4;margin-bottom:6px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">' + esc(og.description) + '</div>' : '')
            + '<div style="font-size:11px;color:#a1a1aa">' + favicon + esc(og.domain || og.site_name || '') + '</div>'
            + '</div></a></div>';
    }

    // ─── 본문: URL → 링크 변환 + OG 카드 렌더링 ───
    window.rzxAutoLinkContent = function(container) {
        if (!container) return;

        // 1. 이미 있는 .rzx-og-card는 스킵
        // 2. 텍스트 URL → <a> 변환
        var walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT, null, false);
        var textNodes = [];
        while (walker.nextNode()) {
            var node = walker.currentNode;
            if (node.parentElement && node.parentElement.closest('a, .rzx-og-card, code, pre, script, style')) continue;
            if (urlPattern.test(node.textContent)) textNodes.push(node);
            urlPattern.lastIndex = 0;
        }

        textNodes.forEach(function(node) {
            var text = node.textContent;
            var parts = text.split(urlPattern);
            if (parts.length <= 1) return;

            var frag = document.createDocumentFragment();
            parts.forEach(function(part) {
                urlPattern.lastIndex = 0;
                if (urlPattern.test(part)) {
                    var href = part.startsWith('www.') ? 'https://' + part : part;
                    var a = document.createElement('a');
                    a.href = href;
                    a.textContent = part;
                    a.target = '_blank';
                    a.rel = 'noopener noreferrer';
                    a.className = 'text-blue-600 dark:text-blue-400 hover:underline break-all';
                    frag.appendChild(a);
                } else {
                    frag.appendChild(document.createTextNode(part));
                }
                urlPattern.lastIndex = 0;
            });
            node.parentNode.replaceChild(frag, node);
        });

        // 3. 단독 URL 링크 → OG 카드로 변환
        // 블록 부모(p, div 등)를 찾아서, 그 안의 텍스트가 URL 하나뿐이면 단독 링크로 판단
        var standaloneLinks = container.querySelectorAll('a[href]');
        standaloneLinks.forEach(function(a) {
            if (a.closest('.rzx-og-card')) return;
            var href = a.href;
            var linkText = (a.textContent || '').trim();
            // URL과 텍스트가 같아야 단독 URL (사용자가 별도 텍스트를 넣은 링크는 제외)
            if (linkText !== href && linkText !== href.replace(/\/$/, '') && !linkText.startsWith('http') && !linkText.startsWith('www.')) return;

            // 블록 부모를 찾기 (p, div, li, td 등)
            var blockParent = a.parentElement;
            while (blockParent && !['P','DIV','LI','TD','BLOCKQUOTE'].includes(blockParent.tagName)) {
                blockParent = blockParent.parentElement;
            }
            if (!blockParent || blockParent === container) return;

            // 블록 부모의 텍스트가 링크 텍스트와 같으면 단독 URL
            var blockText = (blockParent.textContent || '').trim();
            if (blockText !== linkText) return;

            // OG 카드로 변환 (블록 부모를 교체)
            fetchAndReplaceWithCard(a, href, blockParent);
        });

        console.log('[AutoLink] Applied to', textNodes.length, 'text nodes');
    };

    async function fetchAndReplaceWithCard(linkEl, url, parentEl) {
        try {
            var fd = new FormData();
            fd.append('url', url);
            var resp = await fetch(ogApiUrl, { method: 'POST', body: fd });
            var data = await resp.json();
            if (!data.success || !data.og?.title) return;

            var card = document.createElement('div');
            card.innerHTML = buildOgCardHtml(data.og);
            parentEl.replaceWith(card.firstElementChild);
            console.log('[OgCard] Rendered card:', data.og.title);
        } catch (e) {
            // OG 실패 시 일반 링크 유지
        }
    }

    // ─── 자동 실행 ───
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.board-content').forEach(function(el) {
            rzxAutoLinkContent(el);
        });
    });
})();
