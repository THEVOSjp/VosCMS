<?php
/**
 * 적립금 - 기본 설정 + 포인트 부여/차감 + 그룹 연동 + 레벨 포인트 + 초기화
 * points.php 에서 include
 */
$maxLevel = (int)($settings['point_max_level'] ?? 30);
$pointName = $settings['point_name'] ?? 'point';
$pointEnabled = ($settings['point_module_enabled'] ?? 'N') === 'Y';
$disableDownload = ($settings['point_disable_download'] ?? 'N') === 'Y';
$disableRead = ($settings['point_disable_read'] ?? 'N') === 'Y';
$exchangeEnabled = ($settings['point_exchange_enabled'] ?? 'N') === 'Y';
$exchangeRate = (float)($settings['point_exchange_rate'] ?? 1);
$exchangeUnit = (int)($settings['point_exchange_unit'] ?? 1000);
$exchangeMinPoints = (int)($settings['point_exchange_min'] ?? 1000);
$weightPayment = (int)($settings['point_weight_payment'] ?? 3);
$weightActivity = (int)($settings['point_weight_activity'] ?? 1);
$levelIcon = $settings['point_level_icon'] ?? 'default';
$groupReset = $settings['point_group_reset'] ?? 'replace';
$groupRatchet = $settings['point_group_ratchet'] ?? 'demote';
$expression = $settings['point_expression'] ?? 'Math.pow(l, 2) * 90';

// 포인트 부여/차감 기본값
$pkeys = [
    'signup' => 10, 'login' => 5,
    'insert_document' => 10, 'insert_comment' => 5, 'upload_file' => 5,
    'download_file' => -5,
    'read_document' => 0, 'voter' => 0, 'blamer' => 0,
    'voter_comment' => 0, 'blamer_comment' => 0,
    'download_file_author' => 0, 'read_document_author' => 0,
    'voted' => 0, 'blamed' => 0, 'voted_comment' => 0, 'blamed_comment' => 0,
];
$pvals = [];
foreach ($pkeys as $k => $def) {
    $pvals[$k] = (int)($settings['point_' . $k] ?? $def);
}

// 삭제시 회수/기간 제한 설정
$revertKeys = ['insert_document', 'insert_comment', 'upload_file'];
$limitKeys = ['insert_comment', 'read_document', 'voter', 'blamer', 'voted', 'blamed', 'read_document_author', 'voter_comment', 'blamer_comment', 'voted_comment', 'blamed_comment'];
$exceptNotice = ['read_document', 'read_document_author'];
?>

<!-- ===== 기본 섹션 ===== -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 mb-6">
    <div class="p-4 border-b dark:border-zinc-700 flex items-center justify-between cursor-pointer" onclick="toggleSection('secBasic')">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('points.basic') ?></h2>
        <svg class="w-5 h-5 text-zinc-400 sec-arrow transition-transform" data-section="secBasic" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>
    <div id="secBasic" class="p-5 space-y-5">
        <!-- 포인트 모듈 켜기 -->
        <div class="flex items-center justify-between">
            <div>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('points.module_enable') ?></label>
                <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('points.module_enable_desc') ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="cfgEnabled" class="sr-only peer" <?= $pointEnabled ? 'checked' : '' ?>>
                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
        </div>
        <!-- 포인트 이름 -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('points.point_name') ?></label>
                <input type="text" id="cfgName" value="<?= htmlspecialchars($pointName) ?>" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                <p class="text-xs text-zinc-400 mt-1"><?= __('points.point_name_desc') ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('points.max_level') ?></label>
                <input type="number" id="cfgMaxLevel" value="<?= $maxLevel ?>" min="1" max="1000" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                <p class="text-xs text-zinc-400 mt-1"><?= __('points.max_level_desc') ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('points.level_icon') ?></label>
                <select id="cfgLevelIcon" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                    <option value="default" <?= $levelIcon === 'default' ? 'selected' : '' ?>>default</option>
                </select>
            </div>
        </div>
        <!-- 금지 옵션 -->
        <div class="flex flex-wrap gap-6">
            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                <input type="checkbox" id="cfgDisableDownload" class="rounded" <?= $disableDownload ? 'checked' : '' ?>>
                <?= __('points.disable_download') ?>
            </label>
            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                <input type="checkbox" id="cfgDisableRead" class="rounded" <?= $disableRead ? 'checked' : '' ?>>
                <?= __('points.disable_read') ?>
            </label>
        </div>
        <!-- 적립금 환전 -->
        <div class="border-t dark:border-zinc-700 pt-5">
            <div class="flex items-center justify-between">
                <div>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('points.exchange_enable') ?></label>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('points.exchange_enable_desc') ?></p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="cfgExchangeEnabled" class="sr-only peer" <?= $exchangeEnabled ? 'checked' : '' ?> onchange="document.getElementById('exchangeFields').classList.toggle('hidden', !this.checked)">
                    <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>
            <div id="exchangeFields" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 <?= $exchangeEnabled ? '' : 'hidden' ?>">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('points.exchange_rate') ?></label>
                    <div class="flex items-center gap-2">
                        <input type="number" id="cfgExchangeRate" value="<?= $exchangeRate ?>" min="0.01" step="0.01" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                    </div>
                    <p class="text-xs text-zinc-400 mt-1"><?= __('points.exchange_rate_desc') ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('points.exchange_unit') ?></label>
                    <div class="flex items-center gap-2">
                        <input type="number" id="cfgExchangeUnit" value="<?= $exchangeUnit ?>" min="1" step="100" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                        <span class="text-xs text-zinc-400 whitespace-nowrap">point</span>
                    </div>
                    <p class="text-xs text-zinc-400 mt-1"><?= __('points.exchange_unit_desc') ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('points.exchange_min') ?></label>
                    <div class="flex items-center gap-2">
                        <input type="number" id="cfgExchangeMin" value="<?= $exchangeMinPoints ?>" min="0" step="100" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                        <span class="text-xs text-zinc-400 whitespace-nowrap">point</span>
                    </div>
                    <p class="text-xs text-zinc-400 mt-1"><?= __('points.exchange_min_desc') ?></p>
                </div>
            </div>
        </div>
        <!-- 누적 포인트 가중치 -->
        <div class="border-t dark:border-zinc-700 pt-5">
            <div class="mb-3">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('points.weight_title') ?></label>
                <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('points.weight_desc') ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('points.weight_payment') ?></label>
                    <input type="number" id="cfgWeightPayment" value="<?= $weightPayment ?>" min="1" max="100" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                    <p class="text-xs text-zinc-400 mt-1"><?= __('points.weight_payment_desc') ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('points.weight_activity') ?></label>
                    <input type="number" id="cfgWeightActivity" value="<?= $weightActivity ?>" min="1" max="100" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                    <p class="text-xs text-zinc-400 mt-1"><?= __('points.weight_activity_desc') ?></p>
                </div>
            </div>
        </div>
        <div class="flex justify-end mt-4">
            <button onclick="saveBasic(this)" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"><?= __('points.save') ?></button>
            <div class="msg-area mt-2 text-sm"></div>
        </div>
    </div>
</div>

<!-- ===== 포인트 부여/차감 ===== -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 mb-6">
    <div class="p-4 border-b dark:border-zinc-700 flex items-center justify-between cursor-pointer" onclick="toggleSection('secActions')">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('points.actions') ?></h2>
        <svg class="w-5 h-5 text-zinc-400 sec-arrow transition-transform" data-section="secActions" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>
    <div id="secActions" class="p-5">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b dark:border-zinc-700 text-left">
                    <th class="pb-2 font-medium text-zinc-600 dark:text-zinc-400 w-1/3"><?= __('points.action_type') ?></th>
                    <th class="pb-2 font-medium text-zinc-600 dark:text-zinc-400 w-28"><?= __('points.point_value') ?></th>
                    <th class="pb-2 font-medium text-zinc-600 dark:text-zinc-400"><?= __('points.options') ?></th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-zinc-700">
                <?php
                $actionLabels = [
                    'signup' => __('points.act_signup'),
                    'login' => __('points.act_login'),
                    'insert_document' => __('points.act_insert_document'),
                    'insert_comment' => __('points.act_insert_comment'),
                    'upload_file' => __('points.act_upload_file'),
                    'download_file' => __('points.act_download_file'),
                    'read_document' => __('points.act_read_document'),
                    'voter' => __('points.act_voter'),
                    'blamer' => __('points.act_blamer'),
                    'voter_comment' => __('points.act_voter_comment'),
                    'blamer_comment' => __('points.act_blamer_comment'),
                    'download_file_author' => __('points.act_download_file_author'),
                    'read_document_author' => __('points.act_read_document_author'),
                    'voted' => __('points.act_voted'),
                    'blamed' => __('points.act_blamed'),
                    'voted_comment' => __('points.act_voted_comment'),
                    'blamed_comment' => __('points.act_blamed_comment'),
                ];
                foreach ($actionLabels as $key => $label):
                    $val = $pvals[$key] ?? 0;
                    $hasRevert = in_array($key, $revertKeys);
                    $hasLimit = in_array($key, $limitKeys);
                    $hasNotice = in_array($key, $exceptNotice);
                    $revertVal = ($settings['point_' . $key . '_revert'] ?? 'N') === 'Y';
                    $limitVal = (int)($settings['point_' . $key . '_limit'] ?? 0);
                    $noticeVal = ($settings['point_' . $key . '_except_notice'] ?? 'N') === 'Y';
                ?>
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                    <td class="py-2.5 text-zinc-800 dark:text-zinc-200"><?= $label ?></td>
                    <td class="py-2.5">
                        <div class="flex items-center gap-1">
                            <input type="number" id="act_<?= $key ?>" value="<?= $val ?>" class="w-20 px-2 py-1 border rounded dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm text-right">
                            <span class="text-xs text-zinc-400">point</span>
                        </div>
                    </td>
                    <td class="py-2.5">
                        <div class="flex flex-wrap items-center gap-3 text-xs">
                            <?php if ($hasRevert): ?>
                            <label class="flex items-center gap-1 text-zinc-600 dark:text-zinc-400">
                                <input type="checkbox" id="rv_<?= $key ?>" class="rounded" <?= $revertVal ? 'checked' : '' ?>>
                                <?= __('points.revert_on_delete') ?>
                            </label>
                            <?php endif; ?>
                            <?php if ($hasNotice): ?>
                            <label class="flex items-center gap-1 text-zinc-600 dark:text-zinc-400">
                                <input type="checkbox" id="en_<?= $key ?>" class="rounded" <?= $noticeVal ? 'checked' : '' ?>>
                                <?= __('points.except_notice') ?>
                            </label>
                            <?php endif; ?>
                            <?php if ($hasLimit): ?>
                            <span class="text-zinc-500 dark:text-zinc-400"><?= __('points.limit_after') ?></span>
                            <input type="number" id="lm_<?= $key ?>" value="<?= $limitVal ?>" min="0" class="w-16 px-2 py-1 border rounded dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm text-right">
                            <span class="text-zinc-500 dark:text-zinc-400"><?= __('points.limit_days') ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="flex justify-end mt-4">
            <button onclick="saveActions(this)" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"><?= __('points.save') ?></button>
            <div class="msg-area mt-2 text-sm"></div>
        </div>
    </div>
</div>

<!-- ===== 그룹 연동 ===== -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 mb-6">
    <div class="p-4 border-b dark:border-zinc-700 flex items-center justify-between cursor-pointer" onclick="toggleSection('secGroup')">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('points.group_link') ?></h2>
        <svg class="w-5 h-5 text-zinc-400 sec-arrow transition-transform" data-section="secGroup" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>
    <div id="secGroup" class="p-5 space-y-4">
        <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('points.group_link_desc') ?></p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('points.group_reset_mode') ?></label>
                <select id="cfgGroupReset" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                    <option value="replace" <?= $groupReset === 'replace' ? 'selected' : '' ?>><?= __('points.group_reset_replace') ?></option>
                    <option value="add" <?= $groupReset === 'add' ? 'selected' : '' ?>><?= __('points.group_reset_add') ?></option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('points.group_ratchet') ?></label>
                <select id="cfgGroupRatchet" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                    <option value="demote" <?= $groupRatchet === 'demote' ? 'selected' : '' ?>><?= __('points.group_ratchet_demote') ?></option>
                    <option value="keep" <?= $groupRatchet === 'keep' ? 'selected' : '' ?>><?= __('points.group_ratchet_keep') ?></option>
                </select>
            </div>
        </div>
        <!-- 그룹별 레벨 -->
        <div class="space-y-2">
            <?php foreach ($groups as $g): ?>
            <div class="flex items-center gap-4">
                <span class="w-32 text-sm text-zinc-700 dark:text-zinc-300 font-medium"><?= htmlspecialchars($g['name']) ?></span>
                <span class="text-xs text-zinc-400"><?= __('points.group_default') ?></span>
                <input type="number" id="grp_<?= $g['id'] ?>" min="0" max="1000" value="<?= (int)($settings['point_group_' . $g['id']] ?? 0) ?>" class="w-20 px-2 py-1 border rounded dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm text-right" placeholder="<?= __('points.level') ?>">
                <span class="text-xs text-zinc-400"><?= __('points.level') ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="flex justify-end">
            <button onclick="saveGroup(this)" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"><?= __('points.save') ?></button>
            <div class="msg-area mt-2 text-sm"></div>
        </div>
    </div>
</div>

<!-- ===== 레벨 포인트 ===== -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 mb-6">
    <div class="p-4 border-b dark:border-zinc-700 flex items-center justify-between cursor-pointer" onclick="toggleSection('secLevels')">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('points.level_points') ?></h2>
        <svg class="w-5 h-5 text-zinc-400 sec-arrow transition-transform" data-section="secLevels" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>
    <div id="secLevels" class="p-5 space-y-4">
        <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('points.level_formula_desc') ?></p>
        <div class="flex items-center gap-2">
            <input type="text" id="cfgExpression" value="<?= htmlspecialchars($expression) ?>" placeholder="Math.pow(l, 2) * 90" class="flex-1 px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm font-mono">
            <button onclick="calcLevels()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm"><?= __('points.calc_levels') ?></button>
            <button onclick="resetLevels()" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 text-sm"><?= __('points.reset') ?></button>
        </div>
        <!-- 레벨 테이블 -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b dark:border-zinc-700 text-left">
                        <th class="pb-2 w-16 font-medium text-zinc-600 dark:text-zinc-400"><?= __('points.level') ?></th>
                        <th class="pb-2 w-16 font-medium text-zinc-600 dark:text-zinc-400"><?= __('points.level_icon_col') ?></th>
                        <th class="pb-2 w-40 font-medium text-zinc-600 dark:text-zinc-400"><?= __('points.point_value') ?></th>
                        <th class="pb-2 font-medium text-zinc-600 dark:text-zinc-400"><?= __('points.member_group') ?></th>
                    </tr>
                </thead>
                <tbody id="levelTableBody" class="divide-y dark:divide-zinc-700">
                    <?php for ($i = 1; $i <= $maxLevel; $i++):
                        $lp = $levels[$i]['point'] ?? 0;
                        $lg = $levels[$i]['group_id'] ?? '';
                    ?>
                    <tr class="level-row hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <td class="py-2 text-zinc-800 dark:text-zinc-200 font-medium"><?= $i ?></td>
                        <td class="py-2"><span class="inline-block w-6 h-6 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded text-xs font-bold flex items-center justify-center"><?= $i ?></span></td>
                        <td class="py-2">
                            <div class="flex items-center gap-1">
                                <input type="number" class="lvl-point w-28 px-2 py-1 border rounded dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm text-right" data-level="<?= $i ?>" value="<?= $lp ?>">
                                <span class="text-xs text-zinc-400">point</span>
                            </div>
                        </td>
                        <td class="py-2">
                            <?php
                            // 기본 그룹(normal) ID 찾기
                            $defaultGroupId = '';
                            foreach ($groups as $g) { if ($g['slug'] === 'normal') { $defaultGroupId = $g['id']; break; } }
                            $selectedGroup = $lg ?: $defaultGroupId;
                            ?>
                            <select class="lvl-group px-2 py-1 border rounded dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm" data-level="<?= $i ?>">
                                <option value=""></option>
                                <?php foreach ($groups as $g):
                                    if ($g['slug'] === 'staff') continue; // 스태프 그룹 제외
                                ?>
                                <option value="<?= $g['id'] ?>" <?= $selectedGroup === $g['id'] ? 'selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
        <div class="flex justify-end">
            <button onclick="saveLevels(this)" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"><?= __('points.save') ?></button>
            <div class="msg-area mt-2 text-sm"></div>
        </div>
    </div>
</div>

<!-- ===== 포인트 초기화 ===== -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 mb-6">
    <div class="p-4 border-b dark:border-zinc-700 flex items-center justify-between cursor-pointer" onclick="toggleSection('secReset')">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('points.point_reset') ?></h2>
        <svg class="w-5 h-5 text-zinc-400 sec-arrow transition-transform" data-section="secReset" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>
    <div id="secReset" class="p-5 space-y-6">
        <!-- 설정 초기화 -->
        <div class="border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-300 mb-1"><?= __('points.reset_settings') ?></h4>
            <p class="text-xs text-amber-700 dark:text-amber-400 mb-3"><?= __('points.reset_settings_desc') ?></p>
            <button onclick="resetSettingsToDefault()" class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 text-sm"><?= __('points.reset_settings_btn') ?></button>
        </div>
        <!-- 회원 포인트 초기화 -->
        <div class="border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-red-800 dark:text-red-300 mb-1"><?= __('points.point_reset') ?></h4>
            <p class="text-xs text-red-700 dark:text-red-400 mb-3"><?= __('points.reset_warning') ?></p>
            <button onclick="resetAllPoints()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm"><?= __('points.reset_all') ?></button>
        </div>
    </div>
</div>
