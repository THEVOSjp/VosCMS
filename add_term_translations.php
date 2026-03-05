<?php
/**
 * 약관 1, 2 다국어 번역 추가 스크립트
 * 약관 3(취소 환불 규정)은 제외
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

    echo "<h2>약관 1, 2 다국어 번역 추가</h2>";

    // 번역 데이터 정의
    $translations = [
        // 약관 1: 이용 약관 (원본: ko)
        'term.1.title' => [
            'source_locale' => 'ko',
            'ko' => '이용 약관',
            'en' => 'Terms of Service',
            'ja' => '利用規約'
        ],
        'term.1.content' => [
            'source_locale' => 'ko',
            'ko' => null, // 기존 유지
            'en' => '<h1>Terms of Service</h1>
<p>Last updated: March 2, 2026</p>

<h2>1. Agreement to Terms</h2>
<p>By accessing or using RezlyX services, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our services.</p>

<h2>2. Description of Service</h2>
<p>RezlyX provides an online reservation management platform that allows businesses to manage bookings, schedules, and customer information efficiently.</p>

<h2>3. User Accounts</h2>
<p>To use certain features of our service, you must register for an account. You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>

<h2>4. User Obligations</h2>
<ul>
<li>Provide accurate and complete information during registration</li>
<li>Keep your account information up to date</li>
<li>Not share your account with others</li>
<li>Use the service only for lawful purposes</li>
</ul>

<h2>5. Intellectual Property</h2>
<p>All content, features, and functionality of RezlyX are owned by the company and are protected by international copyright, trademark, and other intellectual property laws.</p>

<h2>6. Limitation of Liability</h2>
<p>RezlyX shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of or inability to use the service.</p>

<h2>7. Changes to Terms</h2>
<p>We reserve the right to modify these terms at any time. We will notify users of any material changes by posting the new Terms of Service on this page.</p>

<h2>8. Contact Us</h2>
<p>If you have any questions about these Terms of Service, please contact us at support@rezlyx.com.</p>',

            'ja' => '<h1>利用規約</h1>
<p>最終更新日：2026年3月2日</p>

<h2>1. 規約への同意</h2>
<p>RezlyXのサービスにアクセスまたは使用することにより、お客様はこの利用規約に拘束されることに同意したものとみなされます。これらの規約に同意されない場合は、当社のサービスをご利用にならないでください。</p>

<h2>2. サービスの説明</h2>
<p>RezlyXは、企業が予約、スケジュール、顧客情報を効率的に管理できるオンライン予約管理プラットフォームを提供します。</p>

<h2>3. ユーザーアカウント</h2>
<p>当社のサービスの特定の機能を使用するには、アカウントを登録する必要があります。お客様は、アカウント認証情報の機密性を維持し、アカウントで発生するすべての活動に責任を負います。</p>

<h2>4. ユーザーの義務</h2>
<ul>
<li>登録時に正確かつ完全な情報を提供すること</li>
<li>アカウント情報を最新の状態に保つこと</li>
<li>アカウントを他人と共有しないこと</li>
<li>合法的な目的でのみサービスを使用すること</li>
</ul>

<h2>5. 知的財産権</h2>
<p>RezlyXのすべてのコンテンツ、機能は当社が所有し、国際著作権法、商標法、その他の知的財産法によって保護されています。</p>

<h2>6. 責任の制限</h2>
<p>RezlyXは、サービスの使用または使用不能から生じる間接的、偶発的、特別、結果的、または懲罰的損害について責任を負いません。</p>

<h2>7. 規約の変更</h2>
<p>当社はいつでもこれらの規約を変更する権利を留保します。重要な変更については、このページに新しい利用規約を掲載することでユーザーに通知します。</p>

<h2>8. お問い合わせ</h2>
<p>この利用規約についてご質問がある場合は、support@rezlyx.com までお問い合わせください。</p>'
        ],

        // 약관 2: 개인정보처리방침 (원본: ko)
        'term.2.title' => [
            'source_locale' => 'ko',
            'ko' => '개인정보처리방침',
            'en' => 'Privacy Policy',
            'ja' => 'プライバシーポリシー'
        ],
        'term.2.content' => [
            'source_locale' => 'ko',
            'ko' => null, // 기존 유지
            'en' => '<h1>Privacy Policy</h1>
<p>Last updated: March 2, 2026</p>

<p>RezlyX ("Company") is committed to protecting your privacy and personal information in accordance with applicable privacy laws.</p>

<h2>1. Information We Collect</h2>
<h3>1.1 Information You Provide</h3>
<ul>
<li>Name and contact information (email, phone number)</li>
<li>Account credentials</li>
<li>Payment information</li>
<li>Reservation and booking details</li>
</ul>

<h3>1.2 Information Collected Automatically</h3>
<ul>
<li>Device information and IP address</li>
<li>Browser type and settings</li>
<li>Usage data and analytics</li>
<li>Cookies and similar technologies</li>
</ul>

<h2>2. How We Use Your Information</h2>
<ul>
<li>To provide and maintain our services</li>
<li>To process reservations and transactions</li>
<li>To communicate with you about your account</li>
<li>To improve our services and user experience</li>
<li>To comply with legal obligations</li>
</ul>

<h2>3. Information Sharing</h2>
<p>We do not sell your personal information. We may share your information with:</p>
<ul>
<li>Service providers who assist in our operations</li>
<li>Business partners with your consent</li>
<li>Legal authorities when required by law</li>
</ul>

<h2>4. Data Security</h2>
<p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

<h2>5. Your Rights</h2>
<p>You have the right to:</p>
<ul>
<li>Access your personal information</li>
<li>Correct inaccurate data</li>
<li>Request deletion of your data</li>
<li>Withdraw consent at any time</li>
</ul>

<h2>6. Data Retention</h2>
<p>We retain your personal information only for as long as necessary to fulfill the purposes outlined in this policy or as required by law.</p>

<h2>7. Contact Us</h2>
<p>For privacy-related inquiries, please contact our Data Protection Officer at privacy@rezlyx.com.</p>',

            'ja' => '<h1>プライバシーポリシー</h1>
<p>最終更新日：2026年3月2日</p>

<p>RezlyX（以下「当社」）は、適用されるプライバシー法に従い、お客様のプライバシーと個人情報の保護に努めています。</p>

<h2>1. 収集する情報</h2>
<h3>1.1 お客様が提供する情報</h3>
<ul>
<li>氏名および連絡先情報（メールアドレス、電話番号）</li>
<li>アカウント認証情報</li>
<li>支払い情報</li>
<li>予約および予約詳細</li>
</ul>

<h3>1.2 自動的に収集される情報</h3>
<ul>
<li>デバイス情報およびIPアドレス</li>
<li>ブラウザの種類と設定</li>
<li>利用データと分析</li>
<li>Cookieおよび類似技術</li>
</ul>

<h2>2. 情報の使用目的</h2>
<ul>
<li>サービスの提供および維持</li>
<li>予約および取引の処理</li>
<li>アカウントに関するご連絡</li>
<li>サービスおよびユーザー体験の向上</li>
<li>法的義務の遵守</li>
</ul>

<h2>3. 情報の共有</h2>
<p>当社はお客様の個人情報を販売しません。以下の場合に情報を共有することがあります：</p>
<ul>
<li>当社の運営を支援するサービスプロバイダー</li>
<li>お客様の同意を得たビジネスパートナー</li>
<li>法律で要求された場合の法的機関</li>
</ul>

<h2>4. データセキュリティ</h2>
<p>当社は、不正アクセス、改ざん、開示、または破壊からお客様の個人情報を保護するために、適切な技術的および組織的措置を講じています。</p>

<h2>5. お客様の権利</h2>
<p>お客様には以下の権利があります：</p>
<ul>
<li>個人情報へのアクセス</li>
<li>不正確なデータの訂正</li>
<li>データの削除要求</li>
<li>いつでも同意を撤回</li>
</ul>

<h2>6. データの保持</h2>
<p>当社は、このポリシーに記載された目的を達成するため、または法律で要求される期間に限り、お客様の個人情報を保持します。</p>

<h2>7. お問い合わせ</h2>
<p>プライバシーに関するお問い合わせは、データ保護責任者（privacy@rezlyx.com）までご連絡ください。</p>'
        ]
    ];

    $inserted = 0;
    $updated = 0;
    $skipped = 0;

    foreach ($translations as $langKey => $data) {
        $sourceLocale = $data['source_locale'];

        foreach (['ko', 'en', 'ja'] as $locale) {
            if (!isset($data[$locale]) || $data[$locale] === null) {
                // null이면 기존 데이터 유지, source_locale만 업데이트
                $stmt = $pdo->prepare("UPDATE rzx_translations SET source_locale = ? WHERE lang_key = ? AND locale = ?");
                $stmt->execute([$sourceLocale, $langKey, $locale]);
                if ($stmt->rowCount() > 0) {
                    echo "<p>✓ {$langKey} ({$locale}): source_locale 업데이트</p>";
                }
                continue;
            }

            $content = $data[$locale];

            // 존재 여부 확인
            $checkStmt = $pdo->prepare("SELECT id FROM rzx_translations WHERE lang_key = ? AND locale = ?");
            $checkStmt->execute([$langKey, $locale]);

            if ($checkStmt->fetch()) {
                // 업데이트
                $stmt = $pdo->prepare("UPDATE rzx_translations SET content = ?, source_locale = ?, updated_at = NOW() WHERE lang_key = ? AND locale = ?");
                $stmt->execute([$content, $sourceLocale, $langKey, $locale]);
                $updated++;
                echo "<p>✓ {$langKey} ({$locale}): 업데이트됨</p>";
            } else {
                // 삽입
                $stmt = $pdo->prepare("INSERT INTO rzx_translations (lang_key, locale, content, source_locale, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$langKey, $locale, $content, $sourceLocale]);
                $inserted++;
                echo "<p>✓ {$langKey} ({$locale}): 새로 추가됨</p>";
            }
        }
    }

    echo "<hr>";
    echo "<h3>결과 요약</h3>";
    echo "<p>새로 추가: {$inserted}개</p>";
    echo "<p>업데이트: {$updated}개</p>";

    // 현재 상태 확인
    echo "<h3>현재 번역 데이터:</h3>";
    $stmt = $pdo->query("SELECT lang_key, locale, source_locale, LEFT(content, 40) as preview FROM rzx_translations WHERE lang_key LIKE 'term.1%' OR lang_key LIKE 'term.2%' ORDER BY lang_key, FIELD(locale, 'ko', 'en', 'ja')");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background:#f0f0f0;'><th>lang_key</th><th>locale</th><th>source</th><th>원본?</th><th>preview</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $isSource = ($row['locale'] === $row['source_locale']) ? '✓' : '';
        $bgColor = $isSource ? '#e8f5e9' : '#ffffff';
        echo "<tr style='background:{$bgColor}'>";
        echo "<td>{$row['lang_key']}</td>";
        echo "<td>{$row['locale']}</td>";
        echo "<td>{$row['source_locale']}</td>";
        echo "<td style='text-align:center;'>{$isSource}</td>";
        echo "<td>" . htmlspecialchars($row['preview']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<hr>";
    echo "<p style='color:green; font-weight:bold;'>✓ 완료!</p>";
    echo "<p><a href='/rezlyx/register'>한국어로 테스트</a> | <a href='/rezlyx/register?lang=en'>영어로 테스트</a> | <a href='/rezlyx/register?lang=ja'>일본어로 테스트</a></p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>오류: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
