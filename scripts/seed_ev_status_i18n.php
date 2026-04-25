<?php
/**
 * 이슈 게시판 status 확장변수 다국어 시드
 *  - board_ev.6.status.title (처리 단계)
 *  - board_ev.6.status.description
 *  - board_ev.6.status.options (4개 항목 JSON 배열)
 *  - board_ev.6.status.default_value (접수)
 */
define('BASE_PATH', '/var/www/voscms');
foreach (file(BASE_PATH . '/.env') as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    [$k, $v] = array_map('trim', explode('=', $line, 2) + [1 => '']);
    $_ENV[$k] = trim($v, '"\'');
}
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = new PDO('mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'] . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$T = require '/var/www/voscms/scripts/seed_issue_i18n_data.php';
$badges = $T['status_badges']; // 13 langs × 4 stages

$title = [
    'ko' => '처리 단계', 'en' => 'Status', 'ja' => '処理ステージ',
    'de' => 'Status', 'es' => 'Estado', 'fr' => 'Statut',
    'id' => 'Status', 'mn' => 'Шат', 'ru' => 'Этап',
    'tr' => 'Durum', 'vi' => 'Giai đoạn', 'zh_CN' => '处理阶段', 'zh_TW' => '處理階段',
];
$desc = [
    'ko' => '이슈의 처리 진행 상태입니다. 운영자가 단계를 갱신합니다.',
    'en' => 'The processing status of the issue. Operators update the stage.',
    'ja' => 'イシューの処理進捗状態です。運営者がステージを更新します。',
    'de' => 'Bearbeitungsstatus der Issue. Operatoren aktualisieren die Stufe.',
    'es' => 'Estado de procesamiento del problema. Los operadores actualizan la etapa.',
    'fr' => "Statut de traitement du ticket. Les opérateurs mettent à jour l'étape.",
    'id' => 'Status pemrosesan isu. Operator memperbarui tahap.',
    'mn' => 'Асуудлын боловсруулалтын төлөв. Операторууд үе шатыг шинэчилнэ.',
    'ru' => 'Статус обработки проблемы. Операторы обновляют этап.',
    'tr' => 'Sorunun işlem durumu. Operatörler aşamayı günceller.',
    'vi' => 'Trạng thái xử lý sự cố. Người vận hành cập nhật giai đoạn.',
    'zh_CN' => '问题的处理状态。运营人员更新阶段。',
    'zh_TW' => '議題的處理狀態。營運者更新階段。',
];

$ins = $pdo->prepare("INSERT INTO {$prefix}translations (lang_key, locale, content, created_at, updated_at)
                     VALUES (?,?,?,NOW(),NOW())
                     ON DUPLICATE KEY UPDATE content=VALUES(content), updated_at=NOW()");

$count = 0;
foreach ($badges as $loc => $arr) {
    $ins->execute(["board_ev.6.status.title", $loc, $title[$loc]]);
    $ins->execute(["board_ev.6.status.description", $loc, $desc[$loc]]);
    $ins->execute(["board_ev.6.status.options", $loc, json_encode($arr, JSON_UNESCAPED_UNICODE)]);
    $ins->execute(["board_ev.6.status.default_value", $loc, $arr[0]]); // 첫 단계 = 접수에 해당
    $count += 4;
}

echo "✅ Inserted $count translations for board_ev.6.status (13 langs × 4 fields)\n";
