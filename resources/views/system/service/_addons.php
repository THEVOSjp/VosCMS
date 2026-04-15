<section class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden">
    <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/50 border-b border-gray-200 dark:border-zinc-700">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">3</span>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">부가 서비스</h2>
            <span class="text-xs text-gray-400 dark:text-zinc-500 ml-1">선택사항</span>
        </div>
    </div>
    <div class="p-6 space-y-3">
        <?php foreach ($_addons as $addon):
            $isFree = (int)($addon['price'] ?? 0) === 0;
            $isQuote = ($addon['unit'] ?? '') === '별도 견적';
            $isBizmail = stripos($addon['label'] ?? '', '비즈니스 메일') !== false || stripos($addon['label'] ?? '', 'ビジネスメール') !== false;
        ?>
        <?php if ($isBizmail): ?>
        <!-- 비즈니스 메일 (메일 계정 입력 포함) -->
        <div class="p-4 border border-gray-200 dark:border-zinc-600 rounded-xl">
            <label class="flex items-start gap-4 cursor-pointer">
                <input type="checkbox" name="addon_bizmail" class="mt-1 text-blue-600 rounded" onchange="toggleBizMail(this.checked)">
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($addon['label']) ?></p>
                            <span class="text-[10px] px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded-full font-semibold">대용량</span>
                        </div>
                        <p class="text-blue-600 font-bold"><?= displayPrice($addon['price']) ?><span class="text-xs font-normal text-gray-400"><?= htmlspecialchars($addon['unit']) ?></span></p>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1"><?= htmlspecialchars($addon['desc']) ?></p>
                </div>
            </label>
            <div id="bizMailAccountsWrap" class="hidden mt-3 ml-8 space-y-2">
                <p class="text-xs font-medium text-amber-700 dark:text-amber-300 mb-1">비즈니스 메일 계정 설정</p>
                <div class="bizmail-account-row flex items-center gap-2">
                    <div class="flex-1 flex items-center border border-gray-300 dark:border-zinc-600 rounded-lg overflow-hidden">
                        <input type="text" name="bizmail_id[]" placeholder="ceo" class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-700 dark:text-white border-0 focus:ring-0 min-w-0">
                        <span class="px-3 py-2 text-sm font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 border-l border-gray-300 dark:border-zinc-500 whitespace-nowrap bizmail-domain-suffix">@도메인을 선택하세요</span>
                    </div>
                    <input type="password" name="bizmail_pw[]" placeholder="비밀번호" class="w-36 px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-amber-500">
                </div>
                <button type="button" onclick="addBizMailAccount()" class="text-xs text-amber-600 hover:underline">+ 비즈니스 메일 계정 추가</button>
            </div>
        </div>
        <?php else: ?>
        <!-- 일반 부가 서비스 -->
        <label class="flex items-start gap-4 p-4 border border-gray-200 dark:border-zinc-600 rounded-xl cursor-pointer hover:border-blue-300 dark:hover:border-blue-700 hover:bg-blue-50/50 dark:hover:bg-blue-900/20 transition">
            <input type="checkbox" name="addon_<?= htmlspecialchars(preg_replace('/[^a-z0-9]/', '_', strtolower($addon['label'] ?? 'item'))) ?>" class="mt-1 text-blue-600 rounded" <?= !empty($addon['checked']) ? 'checked' : '' ?>>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <p class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($addon['label']) ?></p>
                    <?php if ($isFree): ?>
                    <p class="text-green-600 font-bold"><?= $isQuote ? htmlspecialchars($addon['unit']) : '무료' ?></p>
                    <?php elseif ($isQuote): ?>
                    <p class="text-blue-600 font-bold">별도 견적</p>
                    <?php else: ?>
                    <p class="text-blue-600 font-bold"><?= displayPrice($addon['price']) ?><span class="text-xs font-normal text-gray-400"><?= htmlspecialchars($addon['unit']) ?></span></p>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1"><?= htmlspecialchars($addon['desc']) ?></p>
            </div>
        </label>
        <?php endif; ?>
        <?php endforeach; ?>

        <!-- 정기 유지보수 -->
        <?php if (!empty($_maintenance)): ?>
        <div class="p-4 border border-gray-200 dark:border-zinc-600 rounded-xl">
            <div class="flex items-center justify-between mb-3">
                <p class="font-semibold text-gray-900 dark:text-white">정기 유지보수</p>
                <span class="text-xs text-gray-400 dark:text-zinc-500">택 1</span>
            </div>
            <div class="space-y-2">
                <?php foreach ($_maintenance as $mt): ?>
                <label class="flex items-start gap-3 p-3 border <?= !empty($mt['badge']) ? 'border-blue-100 dark:border-blue-800 bg-blue-50/30 dark:bg-blue-900/20' : 'border-gray-100 dark:border-zinc-600' ?> rounded-lg cursor-pointer hover:border-blue-300 dark:hover:border-blue-700 transition">
                    <input type="radio" name="maintenance" value="<?= (int)$mt['price'] ?>" class="mt-0.5 text-blue-600">
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($mt['label']) ?></p>
                                <?php if (!empty($mt['badge'])): ?>
                                <span class="text-[10px] px-1.5 py-0.5 bg-blue-600 text-white rounded-full font-semibold"><?= htmlspecialchars($mt['badge']) ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm font-bold text-blue-600"><?= displayPrice($mt['price']) ?><span class="text-xs font-normal text-gray-400">/월</span></p>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5"><?= htmlspecialchars($mt['desc']) ?></p>
                    </div>
                </label>
                <?php endforeach; ?>
                <label class="flex items-start gap-3 p-3 border border-gray-100 dark:border-zinc-600 rounded-lg cursor-pointer transition">
                    <input type="radio" name="maintenance" value="0" class="mt-0.5 text-gray-400" checked>
                    <p class="text-sm text-gray-400 dark:text-zinc-500">유지보수 신청 안 함</p>
                </label>
            </div>
        </div>
        <?php endif; ?>

        <!-- 기본 메일 안내 + 메일 계정 입력 -->
        <div class="p-4 border border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/20 rounded-xl">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                <p class="font-semibold text-gray-900 dark:text-white">기본 메일 5개 포함</p>
                <span class="text-xs text-green-600 font-medium">호스팅 기본 제공</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-zinc-400 ml-6 mb-3">도메인 기반 이메일 5개 (예: info@yourdomain.com). 웹메일, IMAP/POP3 지원. 계정당 1GB.</p>
            <div class="ml-6 space-y-2" id="mailAccountsWrap">
                <p class="text-xs font-medium text-gray-600 dark:text-zinc-300 mb-1">메일 계정 설정 (최대 5개)</p>
                <div class="mail-account-row flex items-center gap-2">
                    <div class="flex-1 flex items-center border border-gray-300 dark:border-zinc-600 rounded-lg overflow-hidden">
                        <input type="text" name="mail_id[]" placeholder="info" class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-700 dark:text-white border-0 focus:ring-0 min-w-0">
                        <span class="px-3 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 bg-gray-100 dark:bg-zinc-600 border-l border-gray-300 dark:border-zinc-500 whitespace-nowrap mail-domain-suffix" id="mailDomainSuffix">@도메인을 선택하세요</span>
                    </div>
                    <input type="password" name="mail_pw[]" placeholder="비밀번호" class="w-36 px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <button type="button" onclick="addMailAccount()" class="ml-6 mt-2 text-xs text-blue-600 hover:underline">+ 메일 계정 추가</button>
        </div>
    </div>
</section>
