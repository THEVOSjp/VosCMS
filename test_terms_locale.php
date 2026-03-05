<?php
/**
 * 약관 다국어 로딩 테스트
 */
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>약관 다국어 테스트</h2>";

// 현재 로케일 확인
$currentLocale = current_locale();
echo "<p><strong>현재 로케일:</strong> {$currentLocale}</p>";

// GET 파라미터 확인
echo "<p><strong>GET lang:</strong> " . ($_GET['lang'] ?? '없음') . "</p>";

// 쿠키 확인
echo "<p><strong>쿠키 locale:</strong> " . ($_COOKIE['locale'] ?? '없음') . "</p>";

// rzx_settings에서 약관 데이터 확인
echo "<h3>rzx_settings 테이블 약관 데이터:</h3>";
$settings = get_settings();
for ($i = 1; $i <= 2; $i++) {
    $title = $settings["member_term_{$i}_title"] ?? '';
    $content = $settings["member_term_{$i}_content"] ?? '';
    $consent = $settings["member_term_{$i}_consent"] ?? 'disabled';

    echo "<div style='border:1px solid #ccc; padding:10px; margin:10px 0;'>";
    echo "<p><strong>약관 {$i}:</strong></p>";
    echo "<p>consent: {$consent}</p>";
    echo "<p>title: " . htmlspecialchars(mb_substr($title, 0, 50)) . "...</p>";
    echo "<p>content 길이: " . strlen($content) . " bytes</p>";
    echo "</div>";
}

// rzx_translations에서 약관 번역 데이터 확인
echo "<h3>rzx_translations 테이블 약관 데이터:</h3>";
try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;dbname=rezlyx_dev;charset=utf8mb4",
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->query("SELECT lang_key, locale, LENGTH(content) as len FROM rzx_translations WHERE lang_key LIKE 'term.%'");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo "<p style='color:orange;'>번역 데이터 없음 (rzx_settings 기본값 사용)</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>lang_key</th><th>locale</th><th>content 길이</th></tr>";
        foreach ($rows as $row) {
            echo "<tr><td>{$row['lang_key']}</td><td>{$row['locale']}</td><td>{$row['len']}</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>DB 오류: " . $e->getMessage() . "</p>";
}

// db_trans() 테스트
echo "<h3>db_trans() 함수 테스트:</h3>";
for ($i = 1; $i <= 2; $i++) {
    $defaultTitle = $settings["member_term_{$i}_title"] ?? '';
    $defaultContent = $settings["member_term_{$i}_content"] ?? '';

    $translatedTitle = db_trans("term.{$i}.title", $currentLocale, $defaultTitle);
    $translatedContent = db_trans("term.{$i}.content", $currentLocale, $defaultContent);

    echo "<div style='border:1px solid #007bff; padding:10px; margin:10px 0;'>";
    echo "<p><strong>약관 {$i} (locale: {$currentLocale}):</strong></p>";
    echo "<p>title: " . htmlspecialchars(mb_substr($translatedTitle, 0, 100)) . "</p>";
    echo "<p>content 길이: " . strlen($translatedContent) . " bytes</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='?lang=ko'>한국어로 테스트</a> | <a href='?lang=en'>영어로 테스트</a> | <a href='?lang=ja'>일본어로 테스트</a></p>";
