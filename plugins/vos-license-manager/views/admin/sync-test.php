<?php
/**
 * [TEMP/DIAGNOSTIC] Market Sync 테스트 페이지
 *
 * voscms → market 전송 내용을 실시간으로 확인.
 * LicenseClient의 collectInstalledItems / syncInstalledItems / resolveProductKeys 동작 검증.
 */
require_once BASE_PATH . '/rzxlib/Core/License/LicenseClient.php';
require_once BASE_PATH . '/rzxlib/Core/License/LicenseStatus.php';

use RzxLib\Core\License\LicenseClient;

// Reflection으로 private 메서드 접근 (진단용)
$client = new LicenseClient();
$refl   = new ReflectionClass($client);

$collectMethod = $refl->getMethod('collectInstalledItems');
$collectMethod->setAccessible(true);
$items = $collectMethod->invoke($client);

$licenseKey = $_ENV['LICENSE_KEY'] ?? '';
$domain     = $_ENV['LICENSE_DOMAIN'] ?? $_SERVER['HTTP_HOST'] ?? '';
$marketUrl  = rtrim($_ENV['MARKET_SERVER'] ?? 'https://market.21ces.com/api', '/');

// 실제 전송 payload 구성 (LicenseClient.syncInstalledItems()와 동일)
$payload = array_map(function ($item) {
    $entry = ['slug' => $item['slug']];
    if (!empty($item['product_key'])) $entry['product_key'] = $item['product_key'];
    if (!empty($item['version']))     $entry['version']     = $item['version'];
    return $entry;
}, $items);

// 수동 sync 실행 요청
$syncResponse    = null;
$resolveResponse = null;
$syncError       = null;
$resolveError    = null;

if (($_GET['action'] ?? '') === 'run_sync') {
    // sync POST
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $marketUrl . '/market/sync',
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'vos_key' => $licenseKey,
            'domain'  => $domain,
            'items'   => $payload,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    $syncResponse = ['http_code' => $code, 'body' => $resp, 'curl_error' => $err];
    if ($err) $syncError = $err;

    // resolve-keys GET (product_key 없는 항목만)
    $missing = array_filter($items, fn($i) => empty($i['product_key']));
    $slugs   = array_column(array_values($missing), 'slug');
    if (!empty($slugs)) {
        $q  = http_build_query(['vos_key' => $licenseKey, 'slugs' => $slugs]);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $marketUrl . '/market/resolve-keys?' . $q,
            CURLOPT_HTTPGET        => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $r2 = curl_exec($ch);
        $c2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $e2 = curl_error($ch);
        curl_close($ch);
        $resolveResponse = ['http_code' => $c2, 'body' => $r2, 'curl_error' => $e2];
        if ($e2) $resolveError = $e2;
    }
}

$adminUrl = '/' . ($config['admin_path'] ?? 'admin');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>[DIAG] Market Sync 테스트</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { font-family: system-ui, sans-serif; }
  pre { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 12px; }
</style>
</head>
<body class="bg-zinc-50 p-8">
<div class="max-w-5xl mx-auto">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-zinc-900">🔍 Market Sync 진단</h1>
    <a href="<?= $adminUrl ?>/license-manager" class="text-sm text-blue-600 hover:underline">← 돌아가기</a>
  </div>

  <!-- 환경 정보 -->
  <div class="bg-white rounded-lg border border-zinc-200 p-5 mb-5">
    <h2 class="text-sm font-bold text-zinc-600 uppercase mb-3">환경</h2>
    <table class="w-full text-sm">
      <tr class="border-b"><td class="py-1.5 font-mono text-zinc-500 w-40">LICENSE_KEY</td><td class="py-1.5 font-mono"><?= htmlspecialchars($licenseKey ?: '(empty)') ?></td></tr>
      <tr class="border-b"><td class="py-1.5 font-mono text-zinc-500">LICENSE_DOMAIN</td><td class="py-1.5 font-mono"><?= htmlspecialchars($domain ?: '(empty)') ?></td></tr>
      <tr class="border-b"><td class="py-1.5 font-mono text-zinc-500">MARKET_SERVER</td><td class="py-1.5 font-mono"><?= htmlspecialchars($marketUrl) ?></td></tr>
      <tr><td class="py-1.5 font-mono text-zinc-500">수집 아이템 수</td><td class="py-1.5 font-mono font-bold"><?= count($items) ?>개</td></tr>
    </table>
  </div>

  <!-- 수집된 아이템 -->
  <div class="bg-white rounded-lg border border-zinc-200 p-5 mb-5">
    <h2 class="text-sm font-bold text-zinc-600 uppercase mb-3">수집된 아이템 (collectInstalledItems)</h2>
    <?php if (empty($items)): ?>
      <p class="text-red-500 text-sm">⚠️ 아이템이 0개 수집됨! plugins/ themes/ widgets/ 디렉토리 및 JSON 구조를 확인하세요.</p>
    <?php else: ?>
    <table class="w-full text-sm">
      <thead class="bg-zinc-50 border-b">
        <tr>
          <th class="text-left px-3 py-2 text-zinc-500 font-medium">slug</th>
          <th class="text-left px-3 py-2 text-zinc-500 font-medium">version</th>
          <th class="text-left px-3 py-2 text-zinc-500 font-medium">product_key</th>
          <th class="text-left px-3 py-2 text-zinc-500 font-medium">manifest_path</th>
        </tr>
      </thead>
      <tbody class="divide-y">
      <?php foreach ($items as $it): ?>
      <tr>
        <td class="px-3 py-1.5 font-mono"><?= htmlspecialchars($it['slug']) ?></td>
        <td class="px-3 py-1.5 font-mono text-zinc-500"><?= htmlspecialchars($it['version'] ?? '-') ?></td>
        <td class="px-3 py-1.5 font-mono text-zinc-500"><?= htmlspecialchars($it['product_key'] ?? '-') ?></td>
        <td class="px-3 py-1.5 font-mono text-[10px] text-zinc-400"><?= htmlspecialchars(str_replace(BASE_PATH, '', $it['manifest_path'])) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- 전송 Payload -->
  <div class="bg-white rounded-lg border border-zinc-200 p-5 mb-5">
    <h2 class="text-sm font-bold text-zinc-600 uppercase mb-3">Sync 전송 Payload (POST <?= htmlspecialchars($marketUrl) ?>/market/sync)</h2>
    <pre class="bg-zinc-900 text-green-300 p-4 rounded overflow-auto max-h-80"><?= htmlspecialchars(json_encode([
      'vos_key' => $licenseKey,
      'domain'  => $domain,
      'items'   => $payload,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
  </div>

  <!-- 실행 버튼 -->
  <div class="bg-white rounded-lg border border-zinc-200 p-5 mb-5">
    <h2 class="text-sm font-bold text-zinc-600 uppercase mb-3">실행</h2>
    <a href="?action=run_sync" class="inline-block px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">▶ 지금 Sync 전송</a>
    <p class="text-xs text-zinc-400 mt-2">※ 실제 API 호출. 정상 응답이면 market DB에 반영됩니다.</p>
  </div>

  <!-- Sync 응답 -->
  <?php if ($syncResponse): ?>
  <div class="bg-white rounded-lg border border-zinc-200 p-5 mb-5">
    <h2 class="text-sm font-bold text-zinc-600 uppercase mb-3">
      Sync 응답
      <span class="ml-2 px-2 py-0.5 text-xs rounded <?= $syncResponse['http_code'] >= 200 && $syncResponse['http_code'] < 300 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
        HTTP <?= $syncResponse['http_code'] ?>
      </span>
    </h2>
    <?php if ($syncResponse['curl_error']): ?>
    <p class="text-red-600 text-sm mb-2">curl error: <?= htmlspecialchars($syncResponse['curl_error']) ?></p>
    <?php endif; ?>
    <pre class="bg-zinc-900 text-green-300 p-4 rounded overflow-auto max-h-80"><?php
      $dec = json_decode($syncResponse['body'] ?? '', true);
      echo htmlspecialchars(is_array($dec)
        ? json_encode($dec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : (string)($syncResponse['body'] ?? ''));
    ?></pre>
  </div>
  <?php endif; ?>

  <!-- Resolve-keys 응답 -->
  <?php if ($resolveResponse): ?>
  <div class="bg-white rounded-lg border border-zinc-200 p-5 mb-5">
    <h2 class="text-sm font-bold text-zinc-600 uppercase mb-3">
      Resolve-keys 응답
      <span class="ml-2 px-2 py-0.5 text-xs rounded <?= $resolveResponse['http_code'] >= 200 && $resolveResponse['http_code'] < 300 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
        HTTP <?= $resolveResponse['http_code'] ?>
      </span>
    </h2>
    <?php if ($resolveResponse['curl_error']): ?>
    <p class="text-red-600 text-sm mb-2">curl error: <?= htmlspecialchars($resolveResponse['curl_error']) ?></p>
    <?php endif; ?>
    <pre class="bg-zinc-900 text-green-300 p-4 rounded overflow-auto max-h-80"><?php
      $dec = json_decode($resolveResponse['body'] ?? '', true);
      echo htmlspecialchars(is_array($dec)
        ? json_encode($dec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : (string)($resolveResponse['body'] ?? ''));
    ?></pre>
  </div>
  <?php endif; ?>

  <p class="text-xs text-zinc-400 text-center mt-8">[진단용 임시 페이지] · 운영 안정화 후 제거</p>
</div>
</body>
</html>
