<!-- ⑤ 웹 호스팅 -->
<?php
$_defPlans = [
    ['_id'=>'free',       'label'=>'무료',       'capacity'=>'50MB',  'price'=>0,     'features'=>'광고 포함,1개월','locked'=>true],
    ['_id'=>'starter',    'label'=>'입문',       'capacity'=>'500MB', 'price'=>3000,  'features'=>''],
    ['_id'=>'recommend',  'label'=>'추천',       'capacity'=>'1GB',   'price'=>5000,  'features'=>''],
    ['_id'=>'business',   'label'=>'비즈니스',   'capacity'=>'3GB',   'price'=>10000, 'features'=>''],
    ['_id'=>'pro',        'label'=>'프로',       'capacity'=>'5GB',   'price'=>18000, 'features'=>''],
    ['_id'=>'enterprise', 'label'=>'엔터프라이즈','capacity'=>'10GB',  'price'=>30000, 'features'=>''],
    ['_id'=>'large',      'label'=>'대용량',     'capacity'=>'15GB',  'price'=>45000, 'features'=>''],
    ['_id'=>'premium',    'label'=>'프리미엄',   'capacity'=>'20GB',  'price'=>55000, 'features'=>''],
    ['_id'=>'max',        'label'=>'맥스',       'capacity'=>'30GB',  'price'=>80000, 'features'=>''],
];
$_defPeriods = [
    ['months'=>1,'discount'=>0],['months'=>6,'discount'=>5],['months'=>12,'discount'=>10],
    ['months'=>24,'discount'=>15],['months'=>36,'discount'=>20],['months'=>60,'discount'=>30],
];
$_defStorage = [
    ['capacity'=>'1GB','price'=>2000],['capacity'=>'3GB','price'=>5000],['capacity'=>'5GB','price'=>8000],
    ['capacity'=>'10GB','price'=>14000],['capacity'=>'20GB','price'=>25000],['capacity'=>'50GB','price'=>50000],
];
// 공통 서비스: 다국어 입력 위해 객체 배열로 변경 (기존 string 도 호환)
$_defFeatures = [
    ['_id'=>'ssl',     'text'=>'SSL 인증서 무료'],
    ['_id'=>'backup',  'text'=>'일일 백업'],
    ['_id'=>'php',     'text'=>'PHP 8.3'],
    ['_id'=>'mail',    'text'=>'기본 메일 5개'],
];

$_plans    = json_decode($serviceSettings['service_hosting_plans']    ?? '', true) ?: $_defPlans;
$_periods  = json_decode($serviceSettings['service_hosting_periods']  ?? '', true) ?: $_defPeriods;
$_storage  = json_decode($serviceSettings['service_hosting_storage']  ?? '', true) ?: $_defStorage;
$_features = json_decode($serviceSettings['service_hosting_features'] ?? '', true) ?: $_defFeatures;

// 다국어 매핑 안정화: _id 자동 부여 (deterministic crc32 해시)
foreach ($_plans as $i => $p) {
    if (!empty($p['_id'])) continue;
    $_plans[$i]['_id'] = 'p' . dechex(crc32(($p['label'] ?? '') . $i));
}
// features 가 string 배열로 저장된 구버전 호환: 객체로 변환
foreach ($_features as $i => $f) {
    if (is_string($f)) {
        $_features[$i] = ['_id' => 'f' . dechex(crc32($f . $i)), 'text' => $f];
    } elseif (empty($f['_id'])) {
        $_features[$i]['_id'] = 'f' . dechex(crc32(($f['text'] ?? '') . $i));
    }
}
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
            <?= __('services.admin.hosting.section_title') ?>
        </h3>
    </div>
    <div class="p-6 space-y-8">

        <!-- 호스팅 플랜 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3"><?= __('services.admin.hosting.plans_section') ?></h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="tblPlans">
                    <thead>
                        <tr class="text-left text-xs text-zinc-400 dark:text-zinc-500 border-b border-zinc-100 dark:border-zinc-700">
                            <th class="pb-2 pr-2 w-32"><?= __('services.admin.hosting.col_plan_name') ?></th>
                            <th class="pb-2 pr-2 w-20"><?= __('services.admin.hosting.col_capacity') ?></th>
                            <th class="pb-2 pr-2 w-28"><?= __('services.admin.hosting.col_monthly_price') ?> (<?= $_dispCur ?>)</th>
                            <th class="pb-2 pr-2 w-20"><?= __('services.admin.hosting.col_free_mail') ?></th>
                            <th class="pb-2 pr-2"><?= __('services.admin.hosting.col_features') ?></th>
                            <th class="pb-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="planRows">
                        <?php foreach ($_plans as $i => $p): $locked = !empty($p['locked']); $sid = $p['_id']; ?>
                        <tr class="border-b border-zinc-50 dark:border-zinc-700/50 plan-row" data-stable-id="<?= htmlspecialchars($sid) ?>" data-locked="<?= $locked ? '1' : '0' ?>">
                            <td class="py-2 pr-2"><?php rzx_multilang_input("plan_label_{$sid}", $p['label'] ?? '', "service.hosting.plan.{$sid}.label", ['class'=>'plan-label text-xs']); ?></td>
                            <td class="py-2 pr-2"><input type="text" value="<?= htmlspecialchars($p['capacity']) ?>" class="plan-cap <?= $_inp ?> text-xs"></td>
                            <td class="py-2 pr-2"><input type="number" value="<?= (int)$p['price'] ?>" class="plan-price <?= $_inp ?> text-xs" min="0"></td>
                            <td class="py-2 pr-2"><input type="number" value="<?= isset($p['free_mail_count']) ? (int)$p['free_mail_count'] : 5 ?>" class="plan-mail <?= $_inp ?> text-xs" min="0" max="50"></td>
                            <td class="py-2 pr-2"><?php rzx_multilang_input("plan_feat_{$sid}", $p['features'] ?? '', "service.hosting.plan.{$sid}.features", ['class'=>'plan-feat text-xs', 'placeholder'=>__('services.admin.hosting.placeholder_features')]); ?></td>
                            <td class="py-2 text-center"><?php if (!$locked): ?><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button><?php else: ?><span class="text-zinc-300 dark:text-zinc-600 text-[10px]"><?= __('services.admin.hosting.locked_label') ?></span><?php endif; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" onclick="addPlanRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline"><?= __('services.admin.hosting.add_plan') ?></button>
        </div>

        <!-- 계약 기간 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3"><?= __('services.admin.hosting.period_section') ?></h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2" id="periodRows">
                <?php foreach ($_periods as $pd): ?>
                <div class="period-item flex items-center gap-1.5 p-2.5 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg relative group">
                    <input type="number" value="<?= (int)$pd['months'] ?>" class="period-months w-12 text-center text-xs font-bold bg-transparent border-0 focus:ring-0 p-0 text-zinc-900 dark:text-white" min="1">
                    <span class="text-[10px] text-zinc-400"><?= __('services.admin.hosting.period_unit') ?></span>
                    <input type="number" value="<?= (int)$pd['discount'] ?>" class="period-disc w-10 text-center text-xs bg-transparent border-0 focus:ring-0 p-0 text-blue-600 dark:text-blue-400" min="0" max="100">
                    <span class="text-[10px] text-zinc-400">%</span>
                    <button type="button" onclick="this.closest('.period-item').remove()" class="absolute -top-1.5 -right-1.5 hidden group-hover:flex w-4 h-4 bg-red-500 text-white rounded-full items-center justify-center text-[10px] leading-none">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addPeriodRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline"><?= __('services.admin.hosting.add_period') ?></button>
        </div>

        <!-- 추가 용량 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3"><?= __('services.admin.hosting.storage_section') ?></h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="tblStorage">
                    <thead>
                        <tr class="text-left text-xs text-zinc-400 dark:text-zinc-500 border-b border-zinc-100 dark:border-zinc-700">
                            <th class="pb-2 pr-2 w-24"><?= __('services.admin.hosting.col_capacity') ?></th>
                            <th class="pb-2 pr-2"><?= __('services.admin.hosting.col_monthly_price') ?> (<?= $_dispCur ?>)</th>
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
            <button type="button" onclick="addStorageRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline"><?= __('services.admin.hosting.add_storage') ?></button>
        </div>

        <!-- 공통 서비스 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3"><?= __('services.admin.hosting.common_services_section') ?></h4>
            <div class="space-y-1.5" id="featureRows">
                <?php foreach ($_features as $ft): $fsid = $ft['_id']; ?>
                <div class="feat-item flex items-center gap-2" data-stable-id="<?= htmlspecialchars($fsid) ?>">
                    <svg class="w-3.5 h-3.5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                    <div class="flex-1"><?php rzx_multilang_input("feat_text_{$fsid}", $ft['text'] ?? '', "service.hosting.feature.{$fsid}.text", ['class'=>'feat-text text-xs']); ?></div>
                    <button type="button" onclick="this.closest('.feat-item').remove()" class="text-red-400 hover:text-red-600 p-1 shrink-0"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addFeatureRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline"><?= __('services.admin.hosting.add_feature') ?></button>
        </div>

    </div>
</div>

<!-- 저장 버튼 (탭 전용) -->
<div class="flex justify-end mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
    <button type="button" onclick="saveHostingSettings()" class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <?= __('services.admin.hosting.save_button') ?>
    </button>
</div>

<!-- 다국어 입력 모달 (한 페이지에 한 번만 include) -->
<?php
$_mlModal = BASE_PATH . '/resources/views/admin/components/multilang-modal.php';
if (file_exists($_mlModal)) include_once $_mlModal;
?>
