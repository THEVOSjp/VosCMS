<?php
// 그리드 배치 검사용 임시 페이지
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>그리드 배치 검사</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .highlight { background-color: rgba(59, 130, 246, 0.1); border: 2px solid rgb(59, 130, 246); }
    </style>
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">🔍 스태프 상세 페이지 - 그리드 배치 검사</h1>

        <!-- 검사 영역 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- 결과 패널 -->
            <div id="resultPanel" class="order-2 lg:order-1">
                <div class="bg-white border-2 border-blue-300 rounded-lg p-6">
                    <h2 class="text-2xl font-bold mb-6 text-blue-900">📊 검사 결과</h2>

                    <!-- 번들 섹션 -->
                    <div class="mb-6 p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                        <h3 class="text-lg font-bold mb-3 text-blue-900">📦 추천 쿠폰(번들) 섹션</h3>
                        <div id="bundleResult" class="space-y-2 text-sm">
                            <p class="text-gray-500">로드 중...</p>
                        </div>
                    </div>

                    <!-- 서비스 섹션 -->
                    <div class="p-4 bg-green-50 rounded-lg border-l-4 border-green-500">
                        <h3 class="text-lg font-bold mb-3 text-green-900">🛎️ 담당 서비스 메뉴 섹션</h3>
                        <div id="serviceResult" class="space-y-2 text-sm">
                            <p class="text-gray-500">로드 중...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 페이지 미리보기 (데스크톱) -->
            <div class="order-1 lg:order-2">
                <div class="bg-white border-2 border-gray-300 rounded-lg overflow-hidden shadow-lg">
                    <div class="bg-gray-100 px-4 py-3 border-b border-gray-300">
                        <h3 class="font-bold text-gray-700">📱 데스크톱 보기 (1920px)</h3>
                    </div>
                    <iframe id="staffFrame" src="http://localhost/rezlyx_salon/staff/10"
                            style="width: 100%; height: 600px; border: none;">
                    </iframe>
                </div>

                <!-- 반응형 뷰 버튼 -->
                <div class="mt-4 flex gap-2 justify-center">
                    <button onclick="setViewport(1920, 'desktop')" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium">
                        💻 데스크톱
                    </button>
                    <button onclick="setViewport(768, 'tablet')" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 text-sm font-medium">
                        📱 태블릿
                    </button>
                    <button onclick="setViewport(375, 'mobile')" class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 text-sm font-medium">
                        📲 모바일
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const staffFrame = document.getElementById('staffFrame');

        function updateDisplay() {
            try {
                const doc = staffFrame.contentDocument || staffFrame.contentWindow.document;
                if (!doc) {
                    showError('iframe 접근 불가');
                    return;
                }

                // 번들 섹션 검사
                const bundleGrid = doc.querySelector('#sdBundleList');
                if (bundleGrid) {
                    const classes = bundleGrid.getAttribute('class');
                    const items = bundleGrid.querySelectorAll('.sd-bundle-card').length;
                    const isCorrect = classes.includes('grid-cols-1') &&
                                    classes.includes('sm:grid-cols-2') &&
                                    classes.includes('lg:grid-cols-3');

                    document.getElementById('bundleResult').innerHTML = `
                        <div class="space-y-2">
                            <p><strong>클래스:</strong></p>
                            <code class="block bg-gray-100 p-2 rounded text-xs break-words">${escapeHtml(classes)}</code>
                            <p class="mt-2"><strong>아이템:</strong> ${items}개</p>
                            <p class="mt-2"><strong>상태:</strong> ${isCorrect ? '✅ 정상 (3열)' : '❌ 오류 (3열 설정 확인)'}</p>
                            <ul class="mt-2 ml-4 list-disc text-xs">
                                <li>모바일: 1열 ${classes.includes('grid-cols-1') ? '✅' : '❌'}</li>
                                <li>태블릿: 2열 ${classes.includes('sm:grid-cols-2') ? '✅' : '❌'}</li>
                                <li>데스크톱: 3열 ${classes.includes('lg:grid-cols-3') ? '✅' : '❌'}</li>
                            </ul>
                        </div>
                    `;
                } else {
                    document.getElementById('bundleResult').innerHTML = '<p class="text-red-600 font-bold">❌ #sdBundleList 요소를 찾을 수 없습니다</p>';
                }

                // 서비스 섹션 검사 (lg:grid-cols-4)
                const allGrids = doc.querySelectorAll('.grid');
                let serviceFound = false;
                let serviceHtml = '';

                allGrids.forEach((grid, idx) => {
                    const classes = grid.getAttribute('class');
                    if (classes && classes.includes('lg:grid-cols-4')) {
                        serviceFound = true;
                        const items = grid.querySelectorAll('[data-cat]').length;
                        const isCorrect = classes.includes('grid-cols-1') &&
                                        classes.includes('sm:grid-cols-2') &&
                                        classes.includes('lg:grid-cols-4');

                        serviceHtml = `
                            <div class="space-y-2">
                                <p><strong>클래스:</strong></p>
                                <code class="block bg-gray-100 p-2 rounded text-xs break-words">${escapeHtml(classes)}</code>
                                <p class="mt-2"><strong>아이템:</strong> ${items}개</p>
                                <p class="mt-2"><strong>상태:</strong> ${isCorrect ? '✅ 정상 (4열)' : '❌ 오류 (4열 설정 확인)'}</p>
                                <ul class="mt-2 ml-4 list-disc text-xs">
                                    <li>모바일: 1열 ${classes.includes('grid-cols-1') ? '✅' : '❌'}</li>
                                    <li>태블릿: 2열 ${classes.includes('sm:grid-cols-2') ? '✅' : '❌'}</li>
                                    <li>데스크톱: 4열 ${classes.includes('lg:grid-cols-4') ? '✅' : '❌'}</li>
                                </ul>
                            </div>
                        `;
                    }
                });

                if (!serviceFound) {
                    serviceHtml = '<p class="text-red-600 font-bold">❌ lg:grid-cols-4 그리드를 찾을 수 없습니다</p>';
                }

                document.getElementById('serviceResult').innerHTML = serviceHtml;

            } catch (e) {
                showError(e.message);
            }
        }

        function showError(msg) {
            const html = `<p class="text-red-600 font-bold">⚠️ ${msg}</p><p class="text-xs text-gray-600 mt-2">크로스 도메인 제약이 있을 수 있습니다. 브라우저 콘솔을 확인하세요.</p>`;
            document.getElementById('bundleResult').innerHTML = html;
            document.getElementById('serviceResult').innerHTML = html;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function setViewport(width, type) {
            const heights = { desktop: 600, tablet: 800, mobile: 1000 };
            staffFrame.style.width = '100%';
            staffFrame.style.height = heights[type] + 'px';
            staffFrame.dataset.viewport = type;
        }

        // iframe 로드 후 검사
        staffFrame.onload = function() {
            console.log('📄 iframe 로드 완료');
            setTimeout(updateDisplay, 1000);
        };

        // 초기 로드 시도
        setTimeout(updateDisplay, 2000);
    </script>
</body>
</html>
