<?php
/**
 * 관리자 서비스 상세 — 도메인 탭
 * $subs: domain 타입 구독 배열
 */
$allDomains = [];
foreach ($subs as $sub) {
    $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
    foreach ($meta['domains'] ?? [] as $dm) {
        $allDomains[] = [
            'name' => $dm,
            'sub_id' => $sub['id'],
            'is_free' => !empty($meta['free_subdomain']),
            'is_existing' => !empty($meta['existing']),
            'is_primary' => !empty($meta['primary_domain']) && $meta['primary_domain'] === $dm,
            'expires_at' => $sub['expires_at'],
        ];
    }
}
if (!empty($allDomains) && !array_filter($allDomains, fn($d) => $d['is_primary'])) {
    $allDomains[0]['is_primary'] = true;
}
$firstSub = $subs[0];
$fst = $statusLabels[$firstSub['status']] ?? ['알 수 없음', 'bg-gray-100 text-gray-500'];

// 네임서버 정보 (주문 metadata 또는 시스템 설정)
$orderMeta = json_decode($firstSub['metadata'] ?? '{}', true) ?: [];
$nameservers = $orderMeta['nameservers'] ?? [
    ['host' => 'ns.21ces.kr', 'ip' => ''],
    ['host' => 'ns1.21ces.kr', 'ip' => ''],
    ['host' => 'ns2.21ces.kr', 'ip' => ''],
    ['host' => 'ns3.21ces.kr', 'ip' => ''],
];
?>

<!-- 도메인 연결 정보 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <div class="flex items-center justify-between mb-3">
        <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">도메인 연결 정보</p>
        <span class="text-xs text-zinc-400">총 <?= count($allDomains) ?>개</span>
    </div>
    <div class="space-y-2">
        <?php foreach ($allDomains as $dm): ?>
        <div class="flex items-center justify-between px-3 py-2 <?= $dm['is_primary'] ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' : 'bg-gray-50 dark:bg-zinc-700/30' ?> rounded-lg text-xs">
            <div class="flex items-center gap-3">
                <?php if ($dm['is_primary']): ?>
                <span class="text-[10px] px-1.5 py-0.5 bg-blue-600 text-white rounded font-medium">대표</span>
                <?php endif; ?>
                <span class="font-mono font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($dm['name']) ?></span>
                <?php if ($dm['is_free']): ?>
                <span class="text-[10px] text-green-600">무료</span>
                <?php elseif ($dm['is_existing']): ?>
                <span class="text-[10px] text-zinc-400">보유</span>
                <?php else: ?>
                <span class="text-[10px] text-zinc-400">~<?= date('Y-m-d', strtotime($dm['expires_at'])) ?></span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-1.5">
                <?php if (!$dm['is_primary']): ?>
                <button disabled class="text-[10px] px-2 py-1 text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded cursor-not-allowed opacity-50" title="준비중">대표도메인 설정</button>
                <?php endif; ?>
                <button disabled class="text-[10px] px-2 py-1 text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded cursor-not-allowed opacity-50" title="준비중">DNS 설정</button>
                <button disabled class="text-[10px] px-2 py-1 text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded cursor-not-allowed opacity-50" title="준비중">검사</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- 도메인 연결 추가 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-2">도메인 연결</p>
    <div class="flex items-center gap-2">
        <input type="text" id="newDomainInput" placeholder="example.com" class="flex-1 max-w-xs px-3 py-1.5 text-xs font-mono border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
        <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="준비중">연결</button>
    </div>
    <p class="text-[10px] text-zinc-400 mt-1.5">도메인 입력 시 https://, www를 빼고 입력하세요. 예) domain.com</p>
</div>

<!-- 네임서버 정보 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-2">네임서버 정보</p>
    <p class="text-[10px] text-zinc-400 mb-3">도메인 구입 등록처에서 아래와 같이 네임서버를 변경하세요.</p>
    <table class="w-full text-xs">
        <thead>
            <tr class="text-[10px] text-zinc-400 border-b border-gray-200 dark:border-zinc-700">
                <th class="py-1.5 text-left w-16">구분</th>
                <th class="py-1.5 text-left">호스트네임</th>
                <th class="py-1.5 text-left">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
            <?php foreach ($nameservers as $i => $ns): ?>
            <tr>
                <td class="py-1.5 text-zinc-400"><?= ($i + 1) ?>차</td>
                <td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($ns['host'] ?? '-') ?></td>
                <td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($ns['ip'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 관리 버튼 -->
<div class="px-5 py-4 flex flex-wrap gap-2">
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="준비중">도메인 추가 구매</button>
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="준비중">도메인 이전</button>
</div>
