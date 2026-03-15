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
    <?php if (!empty($resForm['source'])): ?>
    <input type="hidden" name="source" value="<?= htmlspecialchars($resForm['source']) ?>">
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <!-- 좌측: 서비스 선택 (3/5) -->
        <div class="lg:col-span-3">
            <div class="<?= $fMode === 'page' ? 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 h-full' : '' ?>">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">서비스 선택</h3>
                    <div class="flex items-center gap-2">
                        <input type="text" id="<?= $fId ?>_svcSearch" placeholder="서비스 검색..."
                               class="px-3 py-1.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100 w-48">
                        <span id="<?= $fId ?>_selectedCount" class="text-sm text-zinc-500 dark:text-zinc-400">0개 선택</span>
                    </div>
                </div>

                <div id="<?= $fId ?>_svcList" class="grid grid-cols-1 md:grid-cols-2 gap-3 <?= $fMode === 'page' ? 'max-h-[calc(100vh-320px)]' : 'max-h-[50vh]' ?> overflow-y-auto pr-1">
                    <?php foreach ($fServices as $svc):
                        $oldIds = $fOld['service_ids'] ?? [];
                        $isSelected = in_array($svc['id'], $oldIds);
                        $price = (float)($svc['price'] ?? 0);
                        $duration = (int)($svc['duration'] ?? 30);
                    ?>
                    <div class="rf-card group relative flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all
                        <?= $isSelected ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' ?>"
                        data-name="<?= htmlspecialchars(strtolower($svc['name'])) ?>"
                        data-price="<?= $price ?>" data-duration="<?= $duration ?>">
                        <input type="checkbox" name="service_ids[]" value="<?= $svc['id'] ?>"
                               class="sr-only rf-check" <?= $isSelected ? 'checked' : '' ?>
                               data-price="<?= $price ?>" data-duration="<?= $duration ?>">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center mt-0.5 transition-all
                            <?= $isSelected ? 'border-blue-500 bg-blue-500' : 'border-zinc-300 dark:border-zinc-600 group-hover:border-zinc-400' ?>">
                            <svg class="w-3.5 h-3.5 text-white <?= $isSelected ? '' : 'hidden' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($svc['name']) ?></p>
                            <?php if (!empty($svc['description'])): ?>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 line-clamp-2"><?= htmlspecialchars(mb_substr($svc['description'], 0, 60)) ?></p>
                            <?php endif; ?>
                            <div class="flex items-center gap-3 mt-1.5">
                                <span class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <?= $duration ?>분
                                </span>
                                <span class="text-sm font-bold text-blue-600 dark:text-blue-400"><?= resFormFormatPrice($price, $fSymbol, $fPosition) ?></span>
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
                    <div>
                        <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">이름 <span class="text-red-500">*</span></label>
                        <input type="text" name="customer_name" id="<?= $fId ?>_name" value="<?= htmlspecialchars($fOld['customer_name'] ?? '') ?>" required
                               class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
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
