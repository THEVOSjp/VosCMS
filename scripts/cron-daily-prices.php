#!/usr/bin/env php
<?php
/**
 * 매일 자정 자동 실행 — 도메인 가격 크롤링 + 환율 갱신
 *
 * crontab: 0 0 * * * /usr/bin/php /var/www/voscms/scripts/cron-daily-prices.php >> /var/www/voscms/storage/logs/cron-prices.log 2>&1
 */
date_default_timezone_set('Asia/Tokyo');
$start = microtime(true);
$log = function($msg) { echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL; };

$log('===== 가격 자동 갱신 시작 =====');

// 도메인 가격 크롤링
$log('xdomain.ne.jp 크롤링 중...');
$_GET = ['refresh' => 1];
ob_start();
include dirname(__DIR__) . '/api/domain-prices-crawl.php';
$domainResult = json_decode(ob_get_clean(), true);
if ($domainResult['success'] ?? false) {
    $log('도메인 크롤링 완료: ' . $domainResult['count'] . '개 TLD');
} else {
    $log('도메인 크롤링 실패: ' . ($domainResult['message'] ?? 'unknown'));
}

$elapsed = round(microtime(true) - $start, 2);
$log("===== 완료 ({$elapsed}s) =====\n");
