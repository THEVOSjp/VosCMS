<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>예약 조회 테스트</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Pretendard', sans-serif; }
        .test-section { margin-bottom: 40px; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .test-section h2 { font-size: 1.5em; margin-bottom: 10px; color: #1f2937; }
        .test-description { color: #6b7280; margin-bottom: 15px; }
        .test-form { background: #f9fafb; padding: 15px; border-radius: 6px; margin-bottom: 15px; }
        .form-group { margin-bottom: 10px; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 5px; color: #374151; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; }
        .btn { padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500; }
        .btn:hover { background: #2563eb; }
        .result-frame { width: 100%; height: 800px; border: 1px solid #e5e7eb; border-radius: 4px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .status.success { background: #d1fae5; color: #065f46; }
        .status.error { background: #fee2e2; color: #7f1d1d; }
        .status.pending { background: #fef3c7; color: #78350f; }
    </style>
</head>
<body class="bg-gray-100">
<div class="max-w-6xl mx-auto py-8 px-4">
    <h1 class="text-3xl font-bold mb-2">예약 조회 위젯 테스트</h1>
    <p class="text-gray-600 mb-8">booking lookup 페이지의 검색 기능 및 결과 표시를 테스트합니다</p>

    <?php
    // Get test data
    $db = new \PDO(
        'mysql:host=127.0.0.1;dbname=rezlyx_salon;charset=utf8mb4',
        'root',
        '',
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]
    );

    $testEmail = '';
    $testPhone = '';
    $testCode = '';
    $totalCount = 0;

    try {
        // Get counts
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM rzx_reservations");
        $result = $stmt->fetch();
        $totalCount = $result['cnt'];

        // Get test data
        $stmt = $db->query("SELECT customer_email FROM rzx_reservations WHERE customer_email IS NOT NULL AND customer_email != '' LIMIT 1");
        $result = $stmt->fetch();
        $testEmail = $result['customer_email'] ?? '';

        $stmt = $db->query("SELECT customer_phone FROM rzx_reservations WHERE customer_phone IS NOT NULL AND customer_phone != '' LIMIT 1");
        $result = $stmt->fetch();
        $testPhone = $result['customer_phone'] ?? '';

        $stmt = $db->query("SELECT reservation_number FROM rzx_reservations LIMIT 1");
        $result = $stmt->fetch();
        $testCode = $result['reservation_number'] ?? '';
    } catch (\Exception $e) {
        echo '<div class="status error">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8">
        <h3 class="font-semibold text-blue-900 mb-2">테스트 데이터</h3>
        <ul class="text-blue-800 text-sm space-y-1">
            <li><strong>총 예약 건수:</strong> <?php echo $totalCount; ?></li>
            <li><strong>테스트 이메일:</strong> <?php echo htmlspecialchars($testEmail); ?></li>
            <li><strong>테스트 전화번호:</strong> <?php echo htmlspecialchars($testPhone); ?></li>
            <li><strong>테스트 예약번호:</strong> <?php echo htmlspecialchars($testCode); ?></li>
        </ul>
    </div>

    <!-- Test 1: Initial Form -->
    <div class="test-section">
        <h2>1. 초기 검색 폼 (입력 전)</h2>
        <p class="test-description">아무 입력 없이 페이지를 로드했을 때의 상태</p>
        <iframe class="result-frame" src="/rezlyx_salon/lookup"></iframe>
    </div>

    <!-- Test 2: Email Search -->
    <div class="test-section">
        <h2>2. 이메일로 검색</h2>
        <p class="test-description">등록된 고객 이메일로 예약을 조회합니다</p>
        <div class="test-form">
            <div class="form-group">
                <label>이메일:</label>
                <input type="email" id="emailInput" value="<?php echo htmlspecialchars($testEmail); ?>" readonly>
            </div>
            <button class="btn" onclick="submitTest('email')">이 정보로 검색</button>
            <div id="emailStatus" class="status pending" style="display:none;"></div>
        </div>
        <div id="emailResult">
            <p class="text-gray-500 text-center py-20">검색 버튼을 클릭하여 결과를 로드하세요</p>
        </div>
    </div>

    <!-- Test 3: Phone Search -->
    <div class="test-section">
        <h2>3. 전화번호로 검색</h2>
        <p class="test-description">등록된 고객 전화번호로 예약을 조회합니다</p>
        <div class="test-form">
            <div class="form-group">
                <label>전화번호:</label>
                <input type="tel" id="phoneInput" value="<?php echo htmlspecialchars($testPhone); ?>" readonly>
            </div>
            <button class="btn" onclick="submitTest('phone')">이 정보로 검색</button>
            <div id="phoneStatus" class="status pending" style="display:none;"></div>
        </div>
        <div id="phoneResult">
            <p class="text-gray-500 text-center py-20">검색 버튼을 클릭하여 결과를 로드하세요</p>
        </div>
    </div>

    <!-- Test 4: Reservation Code Search -->
    <div class="test-section">
        <h2>4. 예약번호로 검색</h2>
        <p class="test-description">예약번호로 정확히 조회합니다</p>
        <div class="test-form">
            <div class="form-group">
                <label>예약번호:</label>
                <input type="text" id="codeInput" value="<?php echo htmlspecialchars($testCode); ?>" readonly>
            </div>
            <button class="btn" onclick="submitTest('code')">이 정보로 검색</button>
            <div id="codeStatus" class="status pending" style="display:none;"></div>
        </div>
        <div id="codeResult">
            <p class="text-gray-500 text-center py-20">검색 버튼을 클릭하여 결과를 로드하세요</p>
        </div>
    </div>

    <!-- Test 5: No Results -->
    <div class="test-section">
        <h2>5. 검색 결과 없음</h2>
        <p class="test-description">존재하지 않는 정보로 검색할 때의 동작</p>
        <div class="test-form">
            <div class="form-group">
                <label>이메일 (존재하지 않는):</label>
                <input type="email" id="noResultInput" value="nonexistent999@test.com" readonly>
            </div>
            <button class="btn" onclick="submitTest('noResult')">이 정보로 검색</button>
            <div id="noResultStatus" class="status pending" style="display:none;"></div>
        </div>
        <div id="noResultResult">
            <p class="text-gray-500 text-center py-20">검색 버튼을 클릭하여 결과를 로드하세요</p>
        </div>
    </div>

</div>

<script>
function submitTest(testType) {
    const resultDivId = testType + 'Result';
    const statusDivId = testType + 'Status';
    const resultDiv = document.getElementById(resultDivId);
    const statusDiv = document.getElementById(statusDivId);

    statusDiv.style.display = 'block';
    statusDiv.className = 'status pending';
    statusDiv.textContent = '검색 중...';

    let formData = new FormData();

    if (testType === 'email') {
        formData.append('email', document.getElementById('emailInput').value);
    } else if (testType === 'phone') {
        formData.append('phone', document.getElementById('phoneInput').value);
    } else if (testType === 'code') {
        formData.append('booking_code', document.getElementById('codeInput').value);
    } else if (testType === 'noResult') {
        formData.append('email', document.getElementById('noResultInput').value);
    }

    fetch('/rezlyx_salon/lookup', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Extract just the main content area
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const mainContent = doc.querySelector('main');

        if (mainContent) {
            resultDiv.innerHTML = mainContent.innerHTML;
            statusDiv.className = 'status success';
            statusDiv.textContent = '✓ 검색 완료';
        } else {
            resultDiv.innerHTML = '<p class="text-gray-500 text-center py-20">결과를 로드할 수 없습니다</p>';
            statusDiv.className = 'status error';
            statusDiv.textContent = '✗ 오류 발생';
        }
    })
    .catch(error => {
        statusDiv.className = 'status error';
        statusDiv.textContent = '✗ 요청 실패: ' + error.message;
        console.error(error);
    });
}
</script>

</body>
</html>
