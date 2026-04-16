<!-- ⑤ 웹 호스팅 -->
<?php
$_defPlans = [
    ['label'=>'무료','capacity'=>'50MB','price'=>0,'features'=>'광고 포함,1개월','locked'=>true],
    ['label'=>'입문','capacity'=>'500MB','price'=>3000,'features'=>''],
    ['label'=>'추천','capacity'=>'1GB','price'=>5000,'features'=>''],
    ['label'=>'비즈니스','capacity'=>'3GB','price'=>10000,'features'=>''],
    ['label'=>'프로','capacity'=>'5GB','price'=>18000,'features'=>''],
    ['label'=>'엔터프라이즈','capacity'=>'10GB','price'=>30000,'features'=>''],
    ['label'=>'대용량','capacity'=>'15GB','price'=>45000,'features'=>''],
    ['label'=>'프리미엄','capacity'=>'20GB','price'=>55000,'features'=>''],
    ['label'=>'맥스','capacity'=>'30GB','price'=>80000,'features'=>''],
];
$_defPeriods = [
    ['months'=>1,'discount'=>0],['months'=>6,'discount'=>5],['months'=>12,'discount'=>10],
    ['months'=>24,'discount'=>15],['months'=>36,'discount'=>20],['months'=>60,'discount'=>30],
];
$_defStorage = [
    ['capacity'=>'1GB','price'=>2000],['capacity'=>'3GB','price'=>5000],['capacity'=>'5GB','price'=>8000],
    ['capacity'=>'10GB','price'=>14000],['capacity'=>'20GB','price'=>25000],['capacity'=>'50GB','price'=>50000],
];
$_defFeatures = ['SSL 인증서 무료','일일 백업','PHP 8.3','기본 메일 5개'];

$_plans = json_decode($serviceSettings['service_hosting_plans'] ?? '', true) ?: $_defPlans;
$_periods = json_decode($serviceSettings['service_hosting_periods'] ?? '', true) ?: $_defPeriods;
$_storage = json_decode($serviceSettings['service_hosting_storage'] ?? '', true) ?: $_defStorage;
$_features = json_decode($serviceSettings['service_hosting_features'] ?? '', true) ?: $_defFeatures;
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
            웹 호스팅
        </h3>
    </div>
    <div class="p-6 space-y-8">

        <!-- 호스팅 플랜 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">용량 / 플랜</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="tblPlans">
                    <thead>
                        <tr class="text-left text-xs text-zinc-400 dark:text-zinc-500 border-b border-zinc-100 dark:border-zinc-700">
                            <th class="pb-2 pr-2 w-24">플랜명</th>
                            <th class="pb-2 pr-2 w-20">용량</th>
                            <th class="pb-2 pr-2 w-28">월 가격 (<?= $_dispCur ?>)</th>
                            <th class="pb-2 pr-2">서비스 내용 (콤마 구분)</th>
                            <th class="pb-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="planRows">
                        <?php foreach ($_plans as $i => $p): $locked = !empty($p['locked']); ?>
                        <tr class="border-b border-zinc-50 dark:border-zinc-700/50 plan-row" data-locked="<?= $locked ? '1' : '0' ?>">
                            <td class="py-2 pr-2"><input type="text" value="<?= htmlspecialchars($p['label']) ?>" class="plan-label <?= $_inp ?> text-xs" <?= $locked ? '' : '' ?>></td>
                            <td class="py-2 pr-2"><input type="text" value="<?= htmlspecialchars($p['capacity']) ?>" class="plan-cap <?= $_inp ?> text-xs"></td>
                            <td class="py-2 pr-2"><input type="number" value="<?= (int)$p['price'] ?>" class="plan-price <?= $_inp ?> text-xs" min="0"></td>
                            <td class="py-2 pr-2"><input type="text" value="<?= htmlspecialchars($p['features'] ?? '') ?>" class="plan-feat <?= $_inp ?> text-xs" placeholder="광고 포함, 1개월"></td>
                            <td class="py-2 text-center"><?php if (!$locked): ?><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button><?php else: ?><span class="text-zinc-300 dark:text-zinc-600 text-[10px]">기본</span><?php endif; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" onclick="addPlanRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">+ 호스팅 항목 추가</button>
        </div>

        <!-- 계약 기간 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">계약 기간</h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2" id="periodRows">
                <?php foreach ($_periods as $pd): ?>
                <div class="period-item flex items-center gap-1.5 p-2.5 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg relative group">
                    <input type="number" value="<?= (int)$pd['months'] ?>" class="period-months w-12 text-center text-xs font-bold bg-transparent border-0 focus:ring-0 p-0 text-zinc-900 dark:text-white" min="1">
                    <span class="text-[10px] text-zinc-400">개월</span>
                    <input type="number" value="<?= (int)$pd['discount'] ?>" class="period-disc w-10 text-center text-xs bg-transparent border-0 focus:ring-0 p-0 text-blue-600 dark:text-blue-400" min="0" max="100">
                    <span class="text-[10px] text-zinc-400">%</span>
                    <button type="button" onclick="this.closest('.period-item').remove()" class="absolute -top-1.5 -right-1.5 hidden group-hover:flex w-4 h-4 bg-red-500 text-white rounded-full items-center justify-center text-[10px] leading-none">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addPeriodRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">+ 계약기간 추가</button>
        </div>

        <!-- 추가 용량 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">추가 용량</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="tblStorage">
                    <thead>
                        <tr class="text-left text-xs text-zinc-400 dark:text-zinc-500 border-b border-zinc-100 dark:border-zinc-700">
                            <th class="pb-2 pr-2 w-24">용량</th>
                            <th class="pb-2 pr-2">월 가격 (<?= $_dispCur ?>)</th>
                            <th class="pb-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="storageRows">
                        <?php foreach ($_storage as $st): ?>
                        <tr class="border-b border-zinc-50 dark:border-zinc-700/50 storage-row">
                            <td class="py-2 pr-2"><input type="text" value="<?= htmlspecialchars($st['capacity']) ?>" class="stor-cap <?= $_inp ?> text-xs"></td>
                            <td class="py-2 pr-2"><input type="number" value="<?= (int)$st['price'] ?>" class="stor-price <?= $_inp ?> text-xs" min="0"></td>
                            <td class="py-2 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" onclick="addStorageRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">+ 추가용량 추가</button>
        </div>

        <!-- 공통 서비스 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">공통 서비스</h4>
            <div class="space-y-1.5" id="featureRows">
                <?php foreach ($_features as $ft): ?>
                <div class="feat-item flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                    <input type="text" value="<?= htmlspecialchars($ft) ?>" class="feat-text <?= $_inp ?> text-xs flex-1">
                    <button type="button" onclick="this.closest('.feat-item').remove()" class="text-red-400 hover:text-red-600 p-1 shrink-0"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addFeatureRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">+ 공통 서비스 추가</button>
        </div>

    </div>
</div>
