<?php
/**
 * RezlyX Admin - 위젯 그리드 스냅 드래그 JS
 * 인라인 편집 모드에서 텍스트/버튼/이미지 요소를 3x3 그리드로 드래그 이동
 * data-widget-field 요소에 드래그 핸들 추가, 스냅 위치 저장
 */
?>
<script>
var WBGrid = (function() {
    'use strict';

    var GRID_POSITIONS = [
        'top-left', 'top-center', 'top-right',
        'center-left', 'center', 'center-right',
        'bottom-left', 'bottom-center', 'bottom-right'
    ];

    // 그리드 셀 CSS 위치 (컨테이너 기준 %)
    var GRID_CSS = {
        'top-left':      { top: '8%',  left: '8%',  transform: '' },
        'top-center':    { top: '8%',  left: '50%', transform: 'translateX(-50%)' },
        'top-right':     { top: '8%',  right: '8%', transform: '' },
        'center-left':   { top: '50%', left: '8%',  transform: 'translateY(-50%)' },
        'center':        { top: '50%', left: '50%', transform: 'translate(-50%,-50%)' },
        'center-right':  { top: '50%', right: '8%', transform: 'translateY(-50%)' },
        'bottom-left':   { bottom: '8%', left: '8%',  transform: '' },
        'bottom-center': { bottom: '8%', left: '50%', transform: 'translateX(-50%)' },
        'bottom-right':  { bottom: '8%', right: '8%', transform: '' }
    };

    var gridOverlay = null;
    var activeContainer = null;
    var dragTarget = null;
    var dragHandle = null;

    // ===== 그리드 오버레이 생성 =====
    function showGridOverlay(container) {
        if (gridOverlay) removeGridOverlay();
        activeContainer = container;
        container.style.position = 'relative';

        gridOverlay = document.createElement('div');
        gridOverlay.className = 'wbgrid-overlay';
        gridOverlay.style.cssText = 'position:absolute;inset:0;z-index:40;pointer-events:none;';

        // 3x3 그리드 셀
        GRID_POSITIONS.forEach(function(pos) {
            var cell = document.createElement('div');
            cell.className = 'wbgrid-cell';
            cell.dataset.gridPos = pos;
            cell.style.cssText = 'position:absolute;width:30%;height:30%;border:1px dashed rgba(59,130,246,0.2);border-radius:8px;transition:background 0.15s,border-color 0.15s;pointer-events:auto;';

            // 셀 위치 지정
            var row = pos.split('-')[0] || pos;
            var col = pos.split('-')[1] || pos;
            if (pos === 'center') { row = 'center'; col = 'center'; }

            if (row === 'top') cell.style.top = '2%';
            else if (row === 'center') cell.style.top = '35%';
            else cell.style.top = '68%';

            if (col === 'left') cell.style.left = '2%';
            else if (col === 'center') cell.style.left = '35%';
            else cell.style.left = '68%';

            // 셀 라벨
            var label = document.createElement('span');
            label.style.cssText = 'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:9px;color:rgba(59,130,246,0.3);white-space:nowrap;pointer-events:none;';
            label.textContent = pos;
            cell.appendChild(label);

            gridOverlay.appendChild(cell);
        });

        container.appendChild(gridOverlay);
        console.log('[WBGrid] Grid overlay shown');
    }

    function removeGridOverlay() {
        if (gridOverlay && gridOverlay.parentNode) gridOverlay.remove();
        gridOverlay = null;
        activeContainer = null;
    }

    // ===== 드래그 핸들 추가 =====
    function addDragHandles(container) {
        var editables = container.querySelectorAll('[data-widget-field]');
        editables.forEach(function(el) {
            // 이미 핸들 있으면 스킵
            if (el.querySelector('.wbgrid-handle')) return;

            el.style.position = 'relative';
            var handle = document.createElement('div');
            handle.className = 'wbgrid-handle';
            handle.style.cssText = 'position:absolute;top:-8px;left:-8px;width:18px;height:18px;background:#3b82f6;border:2px solid white;border-radius:50%;cursor:grab;z-index:45;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.2);transition:transform 0.15s;';
            handle.innerHTML = '<svg width="10" height="10" fill="white" viewBox="0 0 24 24"><path d="M12 2l3 3h-2v4h4v-2l3 3-3 3v-2h-4v4h2l-3 3-3-3h2v-4H7v2l-3-3 3-3v2h4V5H9l3-3z"/></svg>';
            handle.title = 'Drag to reposition';

            handle.addEventListener('mousedown', function(e) {
                e.preventDefault();
                e.stopPropagation();
                startDrag(el, e);
            });

            handle.addEventListener('mouseenter', function() {
                handle.style.transform = 'scale(1.2)';
            });
            handle.addEventListener('mouseleave', function() {
                if (!dragTarget) handle.style.transform = '';
            });

            el.appendChild(handle);
        });
    }

    function removeDragHandles(container) {
        container.querySelectorAll('.wbgrid-handle').forEach(function(h) { h.remove(); });
    }

    // ===== 드래그 로직 =====
    function startDrag(el, e) {
        dragTarget = el;
        el.contentEditable = 'false'; // 드래그 중 편집 비활성
        document.body.style.cursor = 'grabbing';
        console.log('[WBGrid] Drag started:', el.dataset.widgetField);

        function onMove(ev) {
            if (!gridOverlay) return;
            var cells = gridOverlay.querySelectorAll('.wbgrid-cell');
            cells.forEach(function(cell) {
                var r = cell.getBoundingClientRect();
                if (ev.clientX >= r.left && ev.clientX <= r.right && ev.clientY >= r.top && ev.clientY <= r.bottom) {
                    cell.style.background = 'rgba(59,130,246,0.15)';
                    cell.style.borderColor = 'rgba(59,130,246,0.6)';
                } else {
                    cell.style.background = '';
                    cell.style.borderColor = 'rgba(59,130,246,0.2)';
                }
            });
        }

        function onUp(ev) {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
            document.body.style.cursor = '';
            el.contentEditable = 'true';

            // 어떤 셀에 드롭했는지 찾기
            if (gridOverlay) {
                var cells = gridOverlay.querySelectorAll('.wbgrid-cell');
                var droppedPos = null;
                cells.forEach(function(cell) {
                    var r = cell.getBoundingClientRect();
                    if (ev.clientX >= r.left && ev.clientX <= r.right && ev.clientY >= r.top && ev.clientY <= r.bottom) {
                        droppedPos = cell.dataset.gridPos;
                    }
                    cell.style.background = '';
                    cell.style.borderColor = 'rgba(59,130,246,0.2)';
                });

                if (droppedPos) {
                    console.log('[WBGrid] Dropped at:', droppedPos, 'field:', el.dataset.widgetField);
                    applyPosition(el, droppedPos);
                    // config에 element_positions 업데이트
                    updateElementPosition(el.dataset.widgetField, droppedPos);
                }
            }
            dragTarget = null;
        }

        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    }

    // ===== 위치 적용 =====
    function applyPosition(el, pos) {
        // 부모가 absolute/relative 컨테이너일 때 CSS 적용
        var css = GRID_CSS[pos];
        if (!css) return;
        // 리셋
        el.style.position = 'absolute';
        el.style.top = el.style.bottom = el.style.left = el.style.right = '';
        el.style.transform = css.transform || '';
        if (css.top) el.style.top = css.top;
        if (css.bottom) el.style.bottom = css.bottom;
        if (css.left) el.style.left = css.left;
        if (css.right) el.style.right = css.right;
        el.style.zIndex = '20';
    }

    // ===== config에 element_positions 저장 =====
    function updateElementPosition(fieldKey, pos) {
        // WBInline에서 현재 편집 중인 블록의 config에 반영
        var inlineBlock = document.querySelector('.widget-block .widget-inline-container');
        if (!inlineBlock) return;
        var block = inlineBlock.closest('.widget-block');
        if (!block) return;

        var config = {};
        try { config = JSON.parse(block.dataset.config || '{}'); } catch(e) {}
        if (!config.element_positions || typeof config.element_positions !== 'object') {
            config.element_positions = {};
        }
        config.element_positions[fieldKey] = pos;
        block.dataset.config = JSON.stringify(config);
        console.log('[WBGrid] element_positions updated:', config.element_positions);
    }

    // ===== 기존 element_positions 로 초기 위치 적용 =====
    function applyExistingPositions(container, config) {
        var positions = config.element_positions;
        if (!positions || typeof positions !== 'object') return;
        Object.keys(positions).forEach(function(fieldKey) {
            var el = container.querySelector('[data-widget-field="' + fieldKey + '"]');
            if (el) {
                applyPosition(el, positions[fieldKey]);
                console.log('[WBGrid] Restored position:', fieldKey, '→', positions[fieldKey]);
            }
        });
    }

    console.log('[WBGrid] Grid snap module ready');

    return {
        showGridOverlay: showGridOverlay,
        removeGridOverlay: removeGridOverlay,
        addDragHandles: addDragHandles,
        removeDragHandles: removeDragHandles,
        applyExistingPositions: applyExistingPositions,
        GRID_CSS: GRID_CSS
    };
})();
</script>
