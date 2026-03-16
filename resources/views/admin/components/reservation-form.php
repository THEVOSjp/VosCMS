<?php
/**
 * 예약 생성 폼 공용 컴포넌트
 * create.php(페이지)와 캘린더 모달에서 공용 사용
 *
 * 필수 변수 ($resForm 배열):
 *   services         — 서비스 목록 [{id, name, description, price, duration}, ...]
 *   adminUrl         — 관리자 URL
 *   csrfToken        — CSRF 토큰
 *   currencySymbol   — 통화 기호
 *   currencyPosition — prefix | suffix
 *   formId           — 고유 폼 ID ('createForm' | 'rzxCalAddForm')
 *   mode             — 'page' | 'modal'
 *   defaultDate      — 기본 날짜 (Y-m-d)
 *   old              — 이전 입력값 (page 모드용)
 */

$fId = $resForm['formId'];
$fMode = $resForm['mode'];
$fOld = $resForm['old'] ?? [];
$fServices = $resForm['services'] ?? [];
$fSymbol = $resForm['currencySymbol'];
$fPosition = $resForm['currencyPosition'];
$fBaseUrl = $resForm['baseUrl'] ?? (isset($config['app_url']) ? $config['app_url'] : '');

// 포맷 헬퍼 (PHP)
if (!function_exists('resFormFormatPrice')) {
    function resFormFormatPrice(float $amount, string $symbol, string $position): string {
        $formatted = number_format($amount);
        return $position === 'suffix' ? $formatted . $symbol : $symbol . $formatted;
    }
}
?>

<form method="POST" action="<?= $resForm['adminUrl'] ?>/reservations" id="<?= $fId ?>">
    <input type="hidden" name="_token" value="<?= $resForm['csrfToken'] ?>">
    <input type="hidden" name="user_id" id="<?= $fId ?>_userId" value="">
    <?php if (!empty($resForm['source'])): ?>
    <input type="hidden" name="source" value="<?= htmlspecialchars($resForm['source']) ?>">
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <!-- 좌측: 서비스 선택 (3/5) -->
        <div class="lg:col-span-3">
            <div class="<?= $fMode === 'page' ? 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 h-full' : '' ?>">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">서비스 선택</h3>
                    <div class="flex items-center gap-2">
                        <input type="text" id="<?= $fId ?>_svcSearch" placeholder="검색..."
                               class="px-3 py-1.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100 w-36">
                        <span id="<?= $fId ?>_selectedCount" class="text-sm text-zinc-500 dark:text-zinc-400">0개 선택</span>
                    </div>
                </div>

                <!-- 카테고리 필터 -->
                <?php
                $fCategories = [];
                foreach ($fServices as $s) {
                    $cid = $s['category_id'] ?? '';
                    $cname = $s['category_name'] ?? '';
                    if ($cid && $cname && !isset($fCategories[$cid])) {
                        $fCategories[$cid] = $cname;
                    }
                }
                ?>
                <?php if (!empty($fCategories)): ?>
                <div id="<?= $fId ?>_catFilter" class="flex flex-wrap gap-1.5 mb-3">
                    <button type="button" class="rf-cat-btn px-3 py-1 text-xs font-medium rounded-full transition-all bg-blue-600 text-white" data-cat="">전체</button>
                    <?php foreach ($fCategories as $cid => $cname): ?>
                    <button type="button" class="rf-cat-btn px-3 py-1 text-xs font-medium rounded-full transition-all bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600" data-cat="<?= htmlspecialchars($cid) ?>"><?= htmlspecialchars($cname) ?></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div id="<?= $fId ?>_svcList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 <?= $fMode === 'page' ? 'max-h-[calc(100vh-380px)]' : 'max-h-[50vh]' ?> overflow-y-auto pr-1">
                    <?php foreach ($fServices as $svc):
                        $oldIds = $fOld['service_ids'] ?? [];
                        $isSelected = in_array($svc['id'], $oldIds);
                        $price = (float)($svc['price'] ?? 0);
                        $duration = (int)($svc['duration'] ?? 30);
                        $svcImage = $svc['image'] ?? '';
                        $hasImage = !empty($svcImage);
                        $svcCatId = $svc['category_id'] ?? '';
                        $svcCatName = $svc['category_name'] ?? '';
                    ?>
                    <div class="rf-card group relative rounded-xl border-2 cursor-pointer transition-all overflow-hidden
                        <?= $isSelected ? 'border-blue-500 ring-2 ring-blue-500/30' : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 hover:shadow-md' ?>"
                        data-name="<?= htmlspecialchars(strtolower($svc['name'])) ?>"
                        data-cat="<?= htmlspecialchars($svcCatId) ?>"
                        data-price="<?= $price ?>" data-duration="<?= $duration ?>"
                        style="min-height:140px;<?php if ($hasImage): ?>background-image:url('<?= htmlspecialchars($fBaseUrl . '/storage/' . $svcImage) ?>');background-size:cover;background-position:center<?php endif; ?>">
                        <input type="checkbox" name="service_ids[]" value="<?= $svc['id'] ?>"
                               class="sr-only rf-check" <?= $isSelected ? 'checked' : '' ?>
                               data-price="<?= $price ?>" data-duration="<?= $duration ?>">
                        <?php if (!$hasImage): ?>
                        <div class="absolute inset-0 bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-700 dark:to-zinc-800"></div>
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                        <div class="absolute inset-0 bg-blue-500/20 <?= $isSelected ? '' : 'hidden' ?> rf-overlay"></div>
                        <!-- 선택 체크 -->
                        <div class="absolute top-2 right-2 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all shadow-sm z-10
                            <?= $isSelected ? 'border-blue-500 bg-blue-500' : 'border-white/70 bg-black/20 group-hover:bg-black/40' ?>">
                            <svg class="w-3.5 h-3.5 text-white <?= $isSelected ? '' : 'hidden' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <?php if ($svcCatName): ?>
                        <div class="absolute top-2 left-2 z-10">
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-black/40 text-white/90 backdrop-blur-sm"><?= htmlspecialchars($svcCatName) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="absolute bottom-0 left-0 right-0 p-3 z-10">
                            <p class="text-sm font-bold text-white truncate drop-shadow-sm"><?= htmlspecialchars($svc['name']) ?></p>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-white/70 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <?= $duration ?>분
                                </span>
                                <span class="text-sm font-bold text-white drop-shadow-sm"><?= resFormFormatPrice($price, $fSymbol, $fPosition) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- 선택 요약 -->
                <div id="<?= $fId ?>_summary" class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 hidden">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-600 dark:text-zinc-400">총 소요시간</span>
                        <span id="<?= $fId ?>_totalDuration" class="font-medium text-zinc-900 dark:text-white">0분</span>
                    </div>
                    <div class="flex items-center justify-between text-sm mt-1">
                        <span class="text-zinc-600 dark:text-zinc-400">총 금액</span>
                        <span id="<?= $fId ?>_totalPrice" class="text-lg font-bold text-blue-600 dark:text-blue-400"><?= resFormFormatPrice(0, $fSymbol, $fPosition) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 우측: 예약 정보 입력 (2/5) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- 일시 -->
            <div class="<?= $fMode === 'page' ? 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6' : '' ?>">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">일시 선택</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">날짜 <span class="text-red-500">*</span></label>
                        <input type="date" name="reservation_date" id="<?= $fId ?>_date"
                               value="<?= $fOld['reservation_date'] ?? $resForm['defaultDate'] ?>" required
                               class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">시작 <span class="text-red-500">*</span></label>
                            <input type="time" name="start_time" id="<?= $fId ?>_startTime" value="<?= $fOld['start_time'] ?? '09:00' ?>" required
                                   class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                        </div>
                        <div>
                            <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">종료</label>
                            <input type="time" name="end_time" id="<?= $fId ?>_endTime" value="<?= $fOld['end_time'] ?? '10:00' ?>"
                                   class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 고객 정보 -->
            <div class="<?= $fMode === 'page' ? 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6' : '' ?>">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">고객 정보</h3>
                <div class="space-y-3">
                    <div class="relative">
                        <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">이름 <span class="text-red-500">*</span></label>
                        <input type="text" name="customer_name" id="<?= $fId ?>_name" value="<?= htmlspecialchars($fOld['customer_name'] ?? '') ?>" required autocomplete="off"
                               class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                        <div id="<?= $fId ?>_nameDropdown" class="absolute z-50 left-0 right-0 top-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden"></div>
                    </div>
                    <div>
                        <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">전화번호 <span class="text-red-500">*</span></label>
                        <input type="tel" name="customer_phone" id="<?= $fId ?>_phone" value="<?= htmlspecialchars($fOld['customer_phone'] ?? '') ?>" required
                               class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100" placeholder="010-1234-5678">
                    </div>
                    <div>
                        <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">이메일</label>
                        <input type="email" name="customer_email" value="<?= htmlspecialchars($fOld['customer_email'] ?? '') ?>"
                               class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                    </div>
                    <div>
                        <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">메모</label>
                        <textarea name="notes" rows="2"
                            class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100 resize-none"
                            placeholder="고객 요청사항 등"><?= htmlspecialchars($fOld['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <?php include __DIR__ . '/reservation-form-staff.php'; ?>

            <!-- 버튼 -->
            <div class="flex gap-3">
                <?php if ($fMode === 'page'): ?>
                <a href="<?= $resForm['adminUrl'] ?>/reservations" class="flex-1 text-center px-4 py-2.5 text-zinc-600 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">취소</a>
                <?php else: ?>
                <button type="button" onclick="rzxCalCloseAdd()" class="flex-1 px-4 py-2.5 text-zinc-600 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">취소</button>
                <?php endif; ?>
                <button type="submit" id="<?= $fId ?>_submitBtn" class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    등록
                </button>
            </div>
        </div>
    </div>
</form>

<?php
// 스태프 데이터 (활성 스태프 목록)
$rfStaff = $pdo->query("SELECT id, name, avatar, designation_fee FROM {$prefix}staff WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<script>
window._rfStaff = window._rfStaff || {};
window._rfStaff['<?= $fId ?>'] = <?= json_encode($rfStaff, JSON_UNESCAPED_UNICODE) ?>;
</script>
