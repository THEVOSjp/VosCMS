<?php
/**
 * 일본인 테스트 스태프 데이터 시드
 * 사용: php seed_staff_test.php
 */

// .env 파일 파싱
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim(trim($v), '"');
        }
    }
}

$pdo = new PDO(
    "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]
);

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 서비스 ID 목록 가져오기
$svcRows = $pdo->query("SELECT id FROM {$prefix}services WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);

$staffData = [
    [
        'name' => '田中 美咲',
        'name_i18n' => json_encode(['ko' => '다나카 미사키', 'en' => 'Misaki Tanaka', 'ja' => '田中 美咲'], JSON_UNESCAPED_UNICODE),
        'email' => 'misaki.tanaka@example.com',
        'phone' => '090-1234-5678',
        'bio' => '10年以上の経験を持つシニアトレーナー。お客様一人ひとりに合わせたプログラムを提供します。',
        'bio_i18n' => json_encode([
            'ko' => '10년 이상의 경험을 가진 시니어 트레이너. 고객 한 분 한 분에 맞춘 프로그램을 제공합니다.',
            'en' => 'Senior trainer with over 10 years of experience. Provides personalized programs for each client.',
            'ja' => '10年以上の経験を持つシニアトレーナー。お客様一人ひとりに合わせたプログラムを提供します。',
        ], JSON_UNESCAPED_UNICODE),
        'position_id' => 1,
        'sort_order' => 10,
    ],
    [
        'name' => '佐藤 健太',
        'name_i18n' => json_encode(['ko' => '사토 켄타', 'en' => 'Kenta Sato', 'ja' => '佐藤 健太'], JSON_UNESCAPED_UNICODE),
        'email' => 'kenta.sato@example.com',
        'phone' => '080-2345-6789',
        'bio' => 'スポーツ科学修士。最新のトレーニング理論に基づいた指導を行います。',
        'bio_i18n' => json_encode([
            'ko' => '스포츠 과학 석사. 최신 트레이닝 이론에 기반한 지도를 합니다.',
            'en' => 'Master in Sports Science. Provides guidance based on the latest training theories.',
            'ja' => 'スポーツ科学修士。最新のトレーニング理論に基づいた指導を行います。',
        ], JSON_UNESCAPED_UNICODE),
        'position_id' => 2,
        'sort_order' => 11,
    ],
    [
        'name' => '鈴木 花',
        'name_i18n' => json_encode(['ko' => '스즈키 하나', 'en' => 'Hana Suzuki', 'ja' => '鈴木 花'], JSON_UNESCAPED_UNICODE),
        'email' => 'hana.suzuki@example.com',
        'phone' => '070-3456-7890',
        'bio' => 'ヨガインストラクター資格保持者。心と体のバランスを整えるお手伝いをします。',
        'bio_i18n' => json_encode([
            'ko' => '요가 강사 자격 보유자. 마음과 몸의 밸런스를 잡는 도움을 드립니다.',
            'en' => 'Certified yoga instructor. Helps balance mind and body.',
            'ja' => 'ヨガインストラクター資格保持者。心と体のバランスを整えるお手伝いをします。',
        ], JSON_UNESCAPED_UNICODE),
        'position_id' => 3,
        'sort_order' => 12,
    ],
    [
        'name' => '高橋 翔太',
        'name_i18n' => json_encode(['ko' => '다카하시 쇼타', 'en' => 'Shota Takahashi', 'ja' => '高橋 翔太'], JSON_UNESCAPED_UNICODE),
        'email' => 'shota.takahashi@example.com',
        'phone' => '090-4567-8901',
        'bio' => '元プロアスリート。パフォーマンス向上と怪我予防の専門家です。',
        'bio_i18n' => json_encode([
            'ko' => '전 프로 선수. 퍼포먼스 향상과 부상 예방 전문가입니다.',
            'en' => 'Former professional athlete. Specialist in performance improvement and injury prevention.',
            'ja' => '元プロアスリート。パフォーマンス向上と怪我予防の専門家です。',
        ], JSON_UNESCAPED_UNICODE),
        'position_id' => 4,
        'sort_order' => 13,
    ],
    [
        'name' => '渡辺 さくら',
        'name_i18n' => json_encode(['ko' => '와타나베 사쿠라', 'en' => 'Sakura Watanabe', 'ja' => '渡辺 さくら'], JSON_UNESCAPED_UNICODE),
        'email' => 'sakura.watanabe@example.com',
        'phone' => '080-5678-9012',
        'bio' => 'エステティシャン歴8年。お肌のお悩みに寄り添い、最適なケアをご提案します。',
        'bio_i18n' => json_encode([
            'ko' => '에스테티션 경력 8년. 피부 고민에 맞춰 최적의 케어를 제안합니다.',
            'en' => '8 years as an esthetician. Offers optimal skincare solutions tailored to your needs.',
            'ja' => 'エステティシャン歴8年。お肌のお悩みに寄り添い、最適なケアをご提案します。',
        ], JSON_UNESCAPED_UNICODE),
        'position_id' => 5,
        'sort_order' => 14,
    ],
];

$sql = "INSERT INTO {$prefix}staff (name, name_i18n, email, phone, bio, bio_i18n, position_id, is_active, sort_order)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)";
$stmt = $pdo->prepare($sql);

$svcStmt = $pdo->prepare("INSERT INTO {$prefix}staff_services (staff_id, service_id) VALUES (?, ?)");

foreach ($staffData as $i => $s) {
    $stmt->execute([
        $s['name'], $s['name_i18n'], $s['email'], $s['phone'],
        $s['bio'], $s['bio_i18n'], $s['position_id'], $s['sort_order']
    ]);
    $staffId = $pdo->lastInsertId();

    // 각 스태프에 랜덤 2~4개 서비스 배정
    $shuffled = $svcRows;
    shuffle($shuffled);
    $count = min(rand(2, 4), count($shuffled));
    for ($j = 0; $j < $count; $j++) {
        $svcStmt->execute([$staffId, $shuffled[$j]]);
    }

    echo "Created: {$s['name']} (ID: {$staffId}, services: {$count})\n";
}

echo "\nDone! 5 staff members created.\n";
