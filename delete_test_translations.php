<?php
/**
 * 테스트 번역 데이터 삭제 스크립트
 * 원본 언어(ko)만 남기고 테스트용 영어/일본어 번역 삭제
 */

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;dbname=rezlyx_dev;charset=utf8mb4",
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );

    echo "<h2>테스트 번역 데이터 삭제</h2>";

    // 삭제 전 현재 상태 확인
    echo "<h3>삭제 전 상태:</h3>";
    $stmt = $pdo->query("SELECT lang_key, locale, LEFT(content, 40) as preview FROM rzx_translations WHERE lang_key LIKE 'term.%' ORDER BY lang_key, locale");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>lang_key</th><th>locale</th><th>preview</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $bgColor = $row['locale'] === 'ko' ? '#e8f5e9' : '#fff3e0';
        echo "<tr style='background:{$bgColor}'><td>{$row['lang_key']}</td><td>{$row['locale']}</td><td>" . htmlspecialchars($row['preview']) . "</td></tr>";
    }
    echo "</table>";

    // 테스트 데이터 삭제 (원본 언어 제외)
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        // 원본 언어(content가 가장 긴 것)를 찾아서 그것만 남기고 삭제
        // 간단히: ko 외의 짧은 content를 가진 번역만 삭제
        $deleteStmt = $pdo->prepare("
            DELETE t1 FROM rzx_translations t1
            INNER JOIN (
                SELECT lang_key, MAX(LENGTH(content)) as max_len
                FROM rzx_translations
                WHERE lang_key LIKE 'term.%'
                GROUP BY lang_key
            ) t2 ON t1.lang_key = t2.lang_key
            WHERE t1.lang_key LIKE 'term.%'
            AND LENGTH(t1.content) < t2.max_len
        ");
        $deleteStmt->execute();
        $deletedCount = $deleteStmt->rowCount();

        echo "<p style='color:green; font-weight:bold;'>✓ {$deletedCount}개의 테스트 번역이 삭제되었습니다.</p>";

        // 삭제 후 상태 확인
        echo "<h3>삭제 후 상태 (원본만 남음):</h3>";
        $stmt = $pdo->query("SELECT lang_key, locale, LEFT(content, 40) as preview, LENGTH(content) as len FROM rzx_translations WHERE lang_key LIKE 'term.%' ORDER BY lang_key, locale");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>lang_key</th><th>locale (원본)</th><th>preview</th><th>길이</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr style='background:#e8f5e9'><td>{$row['lang_key']}</td><td>{$row['locale']}</td><td>" . htmlspecialchars($row['preview']) . "</td><td>{$row['len']}</td></tr>";
        }
        echo "</table>";

        echo "<p>이제 영어나 일본어로 접속하면 원본 언어(ko)가 표시되고, '번역 준비 중' 배지가 표시됩니다.</p>";
    } else {
        echo "<p><a href='?confirm=yes' style='color:red; font-weight:bold;'>⚠ 클릭하여 테스트 번역 삭제 (원본만 남김)</a></p>";
    }

    echo "<hr>";
    echo "<p><a href='/rezlyx/register'>회원가입 페이지 (한국어)</a></p>";
    echo "<p><a href='/rezlyx/register?lang=en'>회원가입 페이지 (영어 - fallback 테스트)</a></p>";
    echo "<p><a href='/rezlyx/register?lang=ja'>회원가입 페이지 (일본어 - fallback 테스트)</a></p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>오류: " . $e->getMessage() . "</p>";
}
