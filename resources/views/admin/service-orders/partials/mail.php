<?php
/**
 * 관리자 서비스 상세 — 메일 탭
 * $subs: mail 타입 구독 배열
 */
// 모든 메일 계정 수집
$allAccounts = [];
foreach ($subs as $sub) {
    $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
    $isBiz = stripos($sub['label'], '비즈니스') !== false || stripos($sub['label'], 'ビジネス') !== false;
    foreach ($meta['mail_accounts'] ?? [] as $ma) {
        $allAccounts[] = [
            'address' => $ma['address'] ?? '',
            'has_password' => !empty($ma['password']),
            'type' => $isBiz ? '비즈니스' : '기본',
            'type_color' => $isBiz ? 'amber' : 'green',
            'sub_id' => $sub['id'],
            'sub_label' => $sub['label'],
        ];
    }
}

// 메일서버 정보 (시스템 설정 또는 주문 metadata)
$firstMeta = json_decode($subs[0]['metadata'] ?? '{}', true) ?: [];
$mailServer = $firstMeta['mail_server'] ?? [];
?>

<!-- 메일서버 정보 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-2">메일서버 정보</p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <table class="text-xs">
            <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                <tr><td class="py-1.5 text-zinc-400 w-28">받는메일 서버</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['imap_host'] ?? '-') ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400">IMAP 포트</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['imap_port'] ?? '993') ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400">보내는메일 서버</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['smtp_host'] ?? '-') ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400">SMTP 포트</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['smtp_port'] ?? '587') ?></td></tr>
            </tbody>
        </table>
        <table class="text-xs">
            <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                <tr><td class="py-1.5 text-zinc-400 w-28">보안 연결</td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['encryption'] ?? 'SSL/TLS') ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400">웹메일</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['webmail_url'] ?? '-') ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400">사용자 계정</td><td class="py-1.5 text-zinc-800 dark:text-zinc-200">아이디@도메인</td></tr>
                <tr><td class="py-1.5 text-zinc-400">비밀번호</td><td class="py-1.5 text-zinc-800 dark:text-zinc-200">설정한 비밀번호</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- 메일 계정 목록 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <div class="flex items-center justify-between mb-3">
        <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">메일 계정 (<?= count($allAccounts) ?>개)</p>
        <button disabled class="text-[10px] px-2 py-1 text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded cursor-not-allowed opacity-50" title="준비중">+ 계정 추가</button>
    </div>
    <div class="space-y-1">
        <?php foreach ($allAccounts as $i => $acc): ?>
        <div class="flex items-center gap-3 px-3 py-2 bg-gray-50 dark:bg-zinc-700/30 rounded text-xs">
            <span class="text-zinc-400 w-4"><?= $i + 1 ?></span>
            <span class="font-mono font-medium text-zinc-800 dark:text-zinc-200 flex-1"><?= htmlspecialchars($acc['address']) ?></span>
            <span class="text-[10px] px-1.5 py-0.5 bg-<?= $acc['type_color'] ?>-50 text-<?= $acc['type_color'] ?>-600 rounded"><?= $acc['type'] ?></span>
            <span class="text-zinc-400 w-24"><?= $acc['has_password'] ? '비밀번호 설정됨' : '미설정' ?></span>
            <button disabled class="text-[10px] px-2 py-1 text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded cursor-not-allowed opacity-50" title="준비중">비밀번호 변경</button>
            <button disabled class="text-[10px] px-2 py-1 text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded cursor-not-allowed opacity-50" title="준비중">삭제</button>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- 관리 버튼 -->
<div class="px-5 py-4 flex flex-wrap gap-2">
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="준비중">이메일 설정 안내 발송</button>
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="준비중">스팸필터 설정</button>
</div>
