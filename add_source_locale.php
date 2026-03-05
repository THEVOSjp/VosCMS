<?php
/**
 * rzx_translations 테이블에 source_locale 컬럼 추가
 * 한 번 실행 후 삭제하세요.
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

    echo "<h2>source_locale 컬럼 추가</h2>";

    // 1. 컬럼이 이미 존재하는지 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM rzx_translations LIKE 'source_locale'");
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        // 2. source_locale 컬럼 추가
        $pdo->exec("ALTER TABLE rzx_translations ADD COLUMN source_locale VARCHAR(5) DEFAULT NULL AFTER locale");
        echo "<p style='color:green;'>✓ source_locale 컬럼이 추가되었습니다.</p>";
    } else {
        echo "<p style='color:blue;'>ℹ source_locale 컬럼이 이미 존재합니다.</p>";
    }

    // 3. 기존 데이터에 source_locale 설정 (content 길이가 가장 긴 것을 원본으로)
    echo "<h3>기존 데이터에 source_locale 설정</h3>";

    // 각 lang_key별로 원본 언어 찾기 (content 길이가 가장 긴 것)
    $stmt = $pdo->query("
        SELECT t1.lang_key, t1.locale as source_locale
        FROM rzx_translations t1
        INNER JOIN (
            SELECT lang_key, MAX(LENGTH(content)) as max_len
            FROM rzx_translations
            WHERE content IS NOT NULL AND content != ''
            GROUP BY lang_key
        ) t2 ON t1.lang_key = t2.lang_key AND LENGTH(t1.content) = t2.max_len
        GROUP BY t1.lang_key
    ");
    $sources = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updated = 0;
    foreach ($sources as $source) {
        $updateStmt = $pdo->prepare("UPDATE rzx_translations SET source_locale = ? WHERE lang_key = ?");
        $updateStmt->execute([$source['source_locale'], $source['lang_key']]);
        $updated += $updateStmt->rowCount();
    }

    echo "<p>✓ {$updated}개의 row에 source_locale이 설정되었습니다.</p>";

    // 4. 현재 상태 확인
    echo "<h3>현재 테이블 구조:</h3>";
    $stmt = $pdo->query("DESCRIBE rzx_translations");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $highlight = $row['Field'] === 'source_locale' ? "style='background:#e8f5e9;'" : "";
        echo "<tr {$highlight}><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";

    // 5. 데이터 확인
    echo "<h3>현재 번역 데이터:</h3>";
    $stmt = $pdo->query("SELECT lang_key, locale, source_locale, LEFT(content, 30) as preview FROM rzx_translations WHERE lang_key LIKE 'term.%' ORDER BY lang_key, locale");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>lang_key</th><th>locale</th><th>source_locale</th><th>원본?</th><th>preview</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $isSource = ($row['locale'] === $row['source_locale']) ? '✓ 원본' : '';
        $bgColor = $isSource ? '#e8f5e9' : '#ffffff';
        echo "<tr style='background:{$bgColor}'>";
        echo "<td>{$row['lang_key']}</td>";
        echo "<td>{$row['locale']}</td>";
        echo "<td>{$row['source_locale']}</td>";
        echo "<td>{$isSource}</td>";
        echo "<td>" . htmlspecialchars($row['preview']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<hr>";
    echo "<p style='color:green; font-weight:bold;'>✓ source_locale 컬럼 추가 완료!</p>";
    echo "<p>functions.php의 db_trans() 및 is_translation_fallback() 함수가 source_locale을 사용하도록 이미 수정되었습니다.</p>";
    echo "<p><a href='/rezlyx/register?lang=en'>영어로 테스트</a> | <a href='/rezlyx/register?lang=ja'>일본어로 테스트</a></p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>오류: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
