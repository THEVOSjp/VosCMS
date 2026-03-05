<?php
/**
 * 약관 번역 데이터 추가 스크립트
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

    // rzx_settings에서 기본 약관 데이터 가져오기
    $stmt = $pdo->query("SELECT `key`, `value` FROM rzx_settings WHERE `key` LIKE 'member_term_%'");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key']] = $row['value'];
    }

    $inserted = 0;
    $skipped = 0;

    for ($i = 1; $i <= 5; $i++) {
        $consent = $settings["member_term_{$i}_consent"] ?? 'disabled';
        if ($consent === 'disabled') {
            continue;
        }

        $defaultTitle = $settings["member_term_{$i}_title"] ?? '';
        $defaultContent = $settings["member_term_{$i}_content"] ?? '';

        if (empty($defaultTitle)) {
            continue;
        }

        echo "<p>약관 {$i} 발견: {$defaultTitle}</p>";

        // 각 언어별 번역 데이터 추가
        $translations = [
            'ko' => [
                'title' => $defaultTitle,
                'content' => $defaultContent
            ],
            'en' => [
                'title' => $i == 1 ? 'Terms of Service' : ($i == 2 ? 'Privacy Policy' : "Terms {$i}"),
                'content' => $i == 1
                    ? '<h2>Terms of Service</h2><p>Welcome to our service. By using our service, you agree to these terms.</p><h3>1. Acceptance of Terms</h3><p>By accessing our service, you confirm that you have read and agree to these terms.</p><h3>2. Use of Service</h3><p>You may use our service only for lawful purposes and in accordance with these Terms.</p>'
                    : ($i == 2
                        ? '<h2>Privacy Policy</h2><p>Your privacy is important to us. This policy explains how we collect, use, and protect your personal information.</p><h3>1. Information Collection</h3><p>We collect information you provide directly, such as name and email.</p><h3>2. Information Use</h3><p>We use your information to provide and improve our services.</p>'
                        : "Content for Terms {$i} in English")
            ],
            'ja' => [
                'title' => $i == 1 ? '利用規約' : ($i == 2 ? 'プライバシーポリシー' : "規約 {$i}"),
                'content' => $i == 1
                    ? '<h2>利用規約</h2><p>当サービスへようこそ。本サービスをご利用いただくことで、これらの規約に同意したものとみなされます。</p><h3>1. 規約の承諾</h3><p>本サービスにアクセスすることで、これらの規約を読み、同意したことを確認します。</p><h3>2. サービスの利用</h3><p>本サービスは、合法的な目的で、これらの規約に従ってのみご利用いただけます。</p>'
                    : ($i == 2
                        ? '<h2>プライバシーポリシー</h2><p>お客様のプライバシーは私たちにとって重要です。このポリシーでは、個人情報の収集、使用、保護について説明します。</p><h3>1. 情報の収集</h3><p>お名前やメールアドレスなど、直接提供された情報を収集します。</p><h3>2. 情報の使用</h3><p>サービスの提供と改善のために情報を使用します。</p>'
                        : "規約 {$i} の日本語コンテンツ")
            ]
        ];

        foreach ($translations as $locale => $data) {
            // title 추가
            $stmt = $pdo->prepare("SELECT id FROM rzx_translations WHERE lang_key = ? AND locale = ?");
            $stmt->execute(["term.{$i}.title", $locale]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO rzx_translations (lang_key, locale, content, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $stmt->execute(["term.{$i}.title", $locale, $data['title']]);
                $inserted++;
            } else {
                $skipped++;
            }

            // content 추가
            $stmt = $pdo->prepare("SELECT id FROM rzx_translations WHERE lang_key = ? AND locale = ?");
            $stmt->execute(["term.{$i}.content", $locale]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO rzx_translations (lang_key, locale, content, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $stmt->execute(["term.{$i}.content", $locale, $data['content']]);
                $inserted++;
            } else {
                $skipped++;
            }
        }
    }

    echo "<h2>번역 데이터 추가 완료</h2>";
    echo "<p>추가됨: {$inserted}개, 스킵됨(이미 존재): {$skipped}개</p>";

    // 확인
    echo "<h3>현재 저장된 번역 데이터:</h3>";
    $stmt = $pdo->query("SELECT lang_key, locale, LEFT(content, 50) as preview FROM rzx_translations WHERE lang_key LIKE 'term.%' ORDER BY lang_key, locale");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>lang_key</th><th>locale</th><th>content preview</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['lang_key']}</td><td>{$row['locale']}</td><td>" . htmlspecialchars($row['preview']) . "</td></tr>";
    }
    echo "</table>";

    echo "<p><a href='/rezlyx/register'>회원가입 페이지로 이동</a></p>";
    echo "<p><a href='/rezlyx/register?lang=en'>영어로 테스트</a> | <a href='/rezlyx/register?lang=ja'>일본어로 테스트</a></p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>오류: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
