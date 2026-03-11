<script>
// ─── 드래그 & 드롭 (메뉴 순서 변경 + 크로스 사이트맵 이동) ───
(function() {
    var dragItem = null;
    var dragId = null;
    var dragSitemapId = null;
    var dropZone = null;
    var dropTarget = null;

    var tree = document.getElementById('sitemapTree');
    if (!tree) return;

    tree.addEventListener('dragstart', function(e) {
        var item = e.target.closest('.tree-item[data-type="menuItem"]');
        if (!item) { e.preventDefault(); return; }
        dragItem = item;
        dragId = item.dataset.id;
        dragSitemapId = item.dataset.sitemapId;
        item.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', dragId);
        console.log('[DnD] Start drag:', dragId, item.dataset.title, 'sitemap:', dragSitemapId);
    });

    tree.addEventListener('dragend', function(e) {
        if (dragItem) dragItem.classList.remove('dragging');
        clearDI();
        dragItem = null;
        dragId = null;
        dragSitemapId = null;
        dropZone = null;
        dropTarget = null;
    });

    tree.addEventListener('dragover', function(e) {
        if (!dragItem) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        // 메뉴 아이템 또는 사이트맵 루트에 드롭 가능
        var target = e.target.closest('.tree-item[data-type="menuItem"], .tree-item[data-type="sitemap"]');
        if (!target || target === dragItem) {
            clearDI();
            dropTarget = null;
            return;
        }

        // 사이트맵 루트인 경우 항상 'inside' 존만 허용
        if (target.dataset.type === 'sitemap') {
            clearDI();
            dropTarget = target;
            dropZone = 'inside';
            target.classList.add('drag-over-inside');
            return;
        }

        // 메뉴 아이템: 자기 자식으로 드롭 방지
        if (isDesc(dragItem, target)) {
            clearDI();
            dropTarget = null;
            return;
        }

        var rect = target.getBoundingClientRect();
        var y = e.clientY - rect.top;
        var h = rect.height;

        clearDI();
        dropTarget = target;

        if (y < h * 0.25) {
            dropZone = 'top';
            target.classList.add('drag-over-top');
        } else if (y > h * 0.75) {
            dropZone = 'bottom';
            target.classList.add('drag-over-bottom');
        } else {
            dropZone = 'inside';
            target.classList.add('drag-over-inside');
        }
    });

    tree.addEventListener('dragleave', function(e) {
        var target = e.target.closest('.tree-item');
        if (target) {
            target.classList.remove('drag-over-top', 'drag-over-bottom', 'drag-over-inside');
        }
    });

    tree.addEventListener('drop', function(e) {
        e.preventDefault();
        if (!dragItem || !dropTarget || dropTarget === dragItem) return;

        var targetType = dropTarget.dataset.type;

        // 사이트맵 루트에 드롭: 해당 사이트맵의 최상위 메뉴로 이동
        if (targetType === 'sitemap') {
            var targetSitemapId = dropTarget.dataset.id;
            console.log('[DnD] Drop on sitemap root:', targetSitemapId);
            handleCrossSitemapDrop(dragId, null, targetSitemapId);
            cleanup();
            return;
        }

        // 메뉴 아이템에 드롭
        var targetId = dropTarget.dataset.id;
        var targetSitemapId = dropTarget.dataset.sitemapId;
        var targetParentId = dropTarget.dataset.parentId || null;
        var isCrossSitemap = (dragSitemapId !== targetSitemapId);

        console.log('[DnD] Drop:', dragId, 'on', targetId, 'zone:', dropZone,
                    'cross-sitemap:', isCrossSitemap,
                    'from:', dragSitemapId, 'to:', targetSitemapId);

        if (dropZone === 'inside') {
            // 대상 내부에 자식으로 삽입
            apiCall('move_menu_item', {
                id: parseInt(dragId),
                parent_id: parseInt(targetId),
                sitemap_id: parseInt(targetSitemapId),
                sort_order: 999
            });
        } else {
            // 위/아래: 형제로 삽입 (같은 사이트맵이든 다른 사이트맵이든)
            reorderSiblings(dragId, targetId, targetParentId, targetSitemapId, dropZone);
        }

        cleanup();
    });

    function cleanup() {
        clearDI();
        dragItem = null;
        dragId = null;
        dragSitemapId = null;
    }

    function clearDI() {
        tree.querySelectorAll('.drag-over-top, .drag-over-bottom, .drag-over-inside').forEach(function(el) {
            el.classList.remove('drag-over-top', 'drag-over-bottom', 'drag-over-inside');
        });
    }

    function isDesc(dragEl, targetEl) {
        var next = dragEl.nextElementSibling;
        if (next && next.classList.contains('tree-children')) {
            if (next.contains(targetEl)) return true;
        }
        return false;
    }

    /**
     * 사이트맵 루트에 드롭 시: 해당 사이트맵 최상위에 추가
     */
    function handleCrossSitemapDrop(movedId, parentId, sitemapId) {
        // 해당 사이트맵의 최상위 형제 목록 수집
        var siblings = getSibsBySitemap(parentId, sitemapId);
        var newOrder = [];

        // 기존 형제에서 이동 아이템 제외 후 맨 뒤에 추가
        for (var i = 0; i < siblings.length; i++) {
            var sid = siblings[i].dataset.id;
            if (sid === movedId) continue;
            newOrder.push(sid);
        }
        newOrder.push(movedId);

        console.log('[DnD] Cross-sitemap move to root:', newOrder, 'sitemap:', sitemapId);
        apiCall('reorder_menu_items', {
            sitemap_id: parseInt(sitemapId),
            parent_id: parentId ? parseInt(parentId) : null,
            order: newOrder.map(function(id) { return parseInt(id); })
        });
    }

    /**
     * 형제 순서 재정렬 (크로스 사이트맵 지원)
     * 대상의 사이트맵/부모 기준으로 형제를 수집하고 이동 아이템을 원하는 위치에 삽입
     */
    function reorderSiblings(movedId, targetId, parentId, sitemapId, position) {
        // 대상 사이트맵의 형제 수집 (이동 아이템의 원래 사이트맵이 아닌 대상 기준)
        var siblings = getSibsBySitemap(parentId, sitemapId);
        var newOrder = [];

        for (var i = 0; i < siblings.length; i++) {
            var sid = siblings[i].dataset.id;
            if (sid === movedId) continue; // 이동 아이템은 새 위치에 삽입
            if (sid === targetId) {
                if (position === 'top') {
                    newOrder.push(movedId);
                    newOrder.push(sid);
                } else {
                    newOrder.push(sid);
                    newOrder.push(movedId);
                }
            } else {
                newOrder.push(sid);
            }
        }

        // movedId가 아직 없으면 (다른 사이트맵에서 온 경우) 대상 위치에 삽입
        if (newOrder.indexOf(movedId) === -1) {
            var idx = newOrder.indexOf(targetId);
            if (idx === -1) {
                newOrder.push(movedId);
            } else if (position === 'top') {
                newOrder.splice(idx, 0, movedId);
            } else {
                newOrder.splice(idx + 1, 0, movedId);
            }
        }

        console.log('[DnD] Reorder:', newOrder, 'parent:', parentId, 'sitemap:', sitemapId);
        apiCall('reorder_menu_items', {
            sitemap_id: parseInt(sitemapId),
            parent_id: parentId ? parseInt(parentId) : null,
            order: newOrder.map(function(id) { return parseInt(id); })
        });
    }

    /**
     * 특정 사이트맵 + 부모 조건에 맞는 형제 메뉴 수집
     */
    function getSibsBySitemap(parentId, sitemapId) {
        var all = tree.querySelectorAll('.tree-item[data-type="menuItem"][data-sitemap-id="' + sitemapId + '"]');
        var result = [];
        for (var i = 0; i < all.length; i++) {
            if ((all[i].dataset.parentId || null) === (parentId || null)) {
                result.push(all[i]);
            }
        }
        return result;
    }
})();
</script>
