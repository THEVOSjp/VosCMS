<section class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden">
    <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/50 border-b border-gray-200 dark:border-zinc-700">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">3</span>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white"><?= htmlspecialchars(__('services.order.addons.title')) ?></h2>
            <span class="text-xs text-gray-400 dark:text-zinc-500 ml-1"><?= htmlspecialchars(__('services.order.addons.optional')) ?></span>
        </div>
    </div>
    <div class="p-6 space-y-3">
        <?php foreach ($_addons as $addon):
            $sid = $addon['_id'] ?? '';
            // 다국어 표시값 (현재 locale → en → 기본 locale → 폴백 한국어 값)
            $_label = $sid ? db_trans("service.addon.{$sid}.label", null, $addon['label'] ?? '') : ($addon['label'] ?? '');
            $_desc  = $sid ? db_trans("service.addon.{$sid}.desc",  null, $addon['desc']  ?? '') : ($addon['desc']  ?? '');
            $_unit  = $sid ? db_trans("service.addon.{$sid}.unit",  null, $addon['unit']  ?? '') : ($addon['unit']  ?? '');
            $isFree = (int)($addon['price'] ?? 0) === 0;
            $isQuote = ($addon['unit'] ?? '') === '별도 견적';   // 로직 분기는 원본 한국어 unit 으로 (안정)
            $isBizmail = ($sid === 'bizmail');                    // _id 기반 식별 (다국어 안전)
            $isInstall = ($sid === 'install');                    // 설치 지원: 관리자 정보 입력 폼 표시
        ?>
        <?php if ($isBizmail): ?>
        <!-- 비즈니스 메일 (메일 계정 입력 포함) -->
        <div class="p-4 border border-gray-200 dark:border-zinc-600 rounded-xl">
            <label class="flex items-start gap-4 cursor-pointer">
                <input type="checkbox" name="addon_bizmail" class="mt-1 text-blue-600 rounded" onchange="toggleBizMail(this.checked)">
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($_label) ?></p>
                            <span class="text-[10px] px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded-full font-semibold"><?= htmlspecialchars(__('services.order.addons.bizmail_badge')) ?></span>
                        </div>
                        <p class="text-blue-600 font-bold"><?= displayPrice($addon['price']) ?><span class="text-xs font-normal text-gray-400"><?= htmlspecialchars($_unit) ?></span></p>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1"><?= htmlspecialchars($_desc) ?></p>
                </div>
            </label>
            <div id="bizMailAccountsWrap" class="hidden mt-3 ml-8 space-y-2">
                <p class="text-xs font-medium text-amber-700 dark:text-amber-300 mb-1"><?= htmlspecialchars(__('services.order.addons.bizmail_setup')) ?></p>
                <div class="bizmail-account-row flex items-center gap-2">
                    <div class="flex-1 flex items-center border border-gray-300 dark:border-zinc-600 rounded-lg overflow-hidden">
                        <input type="text" name="bizmail_id[]" placeholder="ceo" class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-700 dark:text-white border-0 focus:ring-0 min-w-0">
                        <span class="px-3 py-2 text-sm font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 border-l border-gray-300 dark:border-zinc-500 whitespace-nowrap bizmail-domain-suffix">@<?= htmlspecialchars(__('services.order.domain.select_domain')) ?></span>
                    </div>
                    <div class="relative w-36">
                        <input type="password" name="bizmail_pw[]" placeholder="<?= htmlspecialchars(__('services.order.addons.pw_placeholder')) ?>" class="w-full px-3 py-2 pr-9 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-amber-500">
                        <button type="button" onclick="togglePw(this)" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" title="show/hide">👁</button>
                    </div>
                </div>
                <button type="button" onclick="addBizMailAccount()" class="text-xs text-amber-600 hover:underline"><?= htmlspecialchars(__('services.order.addons.bizmail_add')) ?></button>
            </div>
        </div>
        <?php elseif ($isInstall): ?>
        <!-- 설치 지원 (관리자 정보 입력 포함) -->
        <div class="p-4 border border-gray-200 dark:border-zinc-600 rounded-xl">
            <label class="flex items-start gap-4 cursor-pointer">
                <input type="checkbox" name="addon_install" class="mt-1 text-blue-600 rounded" onchange="toggleInstallForm(this.checked)" <?= !empty($addon['checked']) ? 'checked' : '' ?>>
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <p class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($_label) ?></p>
                        <?php if ($isFree): ?>
                        <p class="text-green-600 font-bold"><?= htmlspecialchars(__('services.order.addons.price_free')) ?></p>
                        <?php else: ?>
                        <p class="text-blue-600 font-bold"><?= displayPrice($addon['price']) ?><span class="text-xs font-normal text-gray-400"><?= htmlspecialchars($_unit) ?></span></p>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1"><?= htmlspecialchars($_desc) ?></p>
                </div>
            </label>
            <div id="installAdminFormWrap" class="<?= !empty($addon['checked']) ? '' : 'hidden' ?> mt-3 ml-8 space-y-2">
                <p class="text-xs font-medium text-blue-700 dark:text-blue-300"><?= htmlspecialchars(__('services.order.addons.install_admin_label')) ?></p>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mb-2"><?= htmlspecialchars(__('services.order.addons.install_admin_desc')) ?></p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-zinc-400 mb-0.5"><?= htmlspecialchars(__('services.order.addons.install_admin_id')) ?> <span class="text-red-500">*</span></label>
                        <input type="text" name="install_admin_id" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="admin">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-zinc-400 mb-0.5"><?= htmlspecialchars(__('services.order.addons.install_admin_email')) ?> <span class="text-red-500">*</span></label>
                        <input type="email" name="install_admin_email" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="admin@example.com">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-zinc-400 mb-0.5"><?= htmlspecialchars(__('services.order.addons.install_admin_pw')) ?> <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="install_admin_pw" class="w-full px-3 py-2 pr-9 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                            <button type="button" onclick="togglePw(this)" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" title="show/hide">👁</button>
                        </div>
                        <p class="text-[10px] text-amber-600 dark:text-amber-400 mt-0.5"><?= htmlspecialchars(__('services.order.addons.install_admin_pw_hint')) ?></p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-zinc-400 mb-0.5"><?= htmlspecialchars(__('services.order.addons.install_site_title')) ?></label>
                        <input type="text" name="install_site_title" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- 일반 부가 서비스 -->
        <label class="flex items-start gap-4 p-4 border border-gray-200 dark:border-zinc-600 rounded-xl cursor-pointer hover:border-blue-300 dark:hover:border-blue-700 hover:bg-blue-50/50 dark:hover:bg-blue-900/20 transition">
            <input type="checkbox" name="addon_<?= htmlspecialchars($sid ?: preg_replace('/[^a-z0-9]/', '_', strtolower($addon['label'] ?? 'item'))) ?>" class="mt-1 text-blue-600 rounded" <?= !empty($addon['checked']) ? 'checked' : '' ?>>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <p class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($_label) ?></p>
                    <?php if ($isFree): ?>
                    <p class="text-green-600 font-bold"><?= $isQuote ? htmlspecialchars($_unit) : htmlspecialchars(__('services.order.addons.price_free')) ?></p>
                    <?php elseif ($isQuote): ?>
                    <p class="text-blue-600 font-bold"><?= htmlspecialchars(__('services.order.addons.price_quote')) ?></p>
                    <?php else: ?>
                    <p class="text-blue-600 font-bold"><?= displayPrice($addon['price']) ?><span class="text-xs font-normal text-gray-400"><?= htmlspecialchars($_unit) ?></span></p>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1"><?= htmlspecialchars($_desc) ?></p>
            </div>
        </label>
        <?php endif; ?>
        <?php endforeach; ?>

        <!-- 정기 유지보수 -->
        <?php if (!empty($_maintenance)): ?>
        <div class="p-4 border border-gray-200 dark:border-zinc-600 rounded-xl">
            <div class="flex items-center justify-between mb-3">
                <p class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars(__('services.order.addons.maint_title')) ?></p>
                <span class="text-xs text-gray-400 dark:text-zinc-500"><?= htmlspecialchars(__('services.order.addons.maint_pick_one')) ?></span>
            </div>
            <div class="space-y-2">
                <?php foreach ($_maintenance as $mt):
                    $msid = $mt['_id'] ?? '';
                    // 다국어 표시값
                    $_mLabel = $msid ? db_trans("service.maintenance.{$msid}.label", null, $mt['label'] ?? '') : ($mt['label'] ?? '');
                    $_mDesc  = $msid ? db_trans("service.maintenance.{$msid}.desc",  null, $mt['desc']  ?? '') : ($mt['desc']  ?? '');
                    $_mBadge = $msid ? db_trans("service.maintenance.{$msid}.badge", null, $mt['badge'] ?? '') : ($mt['badge'] ?? '');
                ?>
                <label class="flex items-start gap-3 p-3 border <?= $_mBadge !== '' ? 'border-blue-100 dark:border-blue-800 bg-blue-50/30 dark:bg-blue-900/20' : 'border-gray-100 dark:border-zinc-600' ?> rounded-lg cursor-pointer hover:border-blue-300 dark:hover:border-blue-700 transition">
                    <input type="radio" name="maintenance" value="<?= (int)$mt['price'] ?>" class="mt-0.5 text-blue-600">
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($_mLabel) ?></p>
                                <?php if ($_mBadge !== ''): ?>
                                <span class="text-[10px] px-1.5 py-0.5 bg-blue-600 text-white rounded-full font-semibold"><?= htmlspecialchars($_mBadge) ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm font-bold text-blue-600"><?= displayPrice($mt['price']) ?><span class="text-xs font-normal text-gray-400"><?= htmlspecialchars(__('services.order.hosting.price_per_month')) ?></span></p>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5"><?= htmlspecialchars($_mDesc) ?></p>
                    </div>
                </label>
                <?php endforeach; ?>
                <label class="flex items-start gap-3 p-3 border border-gray-100 dark:border-zinc-600 rounded-lg cursor-pointer transition">
                    <input type="radio" name="maintenance" value="0" class="mt-0.5 text-gray-400" checked>
                    <p class="text-sm text-gray-400 dark:text-zinc-500"><?= htmlspecialchars(__('services.order.addons.maint_none')) ?></p>
                </label>
            </div>
        </div>
        <?php endif; ?>

        <!-- 기본 메일 안내 + 메일 계정 입력 -->
        <div class="p-4 border border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/20 rounded-xl">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                <p class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars(__('services.order.addons.mail_default_title')) ?></p>
                <span class="text-xs text-green-600 font-medium"><?= htmlspecialchars(__('services.order.addons.mail_default_badge')) ?></span>
            </div>
            <p class="text-xs text-gray-500 dark:text-zinc-400 ml-6 mb-3"><?= htmlspecialchars(__('services.order.addons.mail_default_desc')) ?></p>
            <div class="ml-6 space-y-2" id="mailAccountsWrap">
                <p class="text-xs font-medium text-gray-600 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.order.addons.mail_setup_label')) ?></p>
                <div class="mail-account-row flex items-center gap-2">
                    <div class="flex-1 flex items-center border border-gray-300 dark:border-zinc-600 rounded-lg overflow-hidden">
                        <input type="text" name="mail_id[]" placeholder="info" class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-700 dark:text-white border-0 focus:ring-0 min-w-0">
                        <span class="px-3 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 bg-gray-100 dark:bg-zinc-600 border-l border-gray-300 dark:border-zinc-500 whitespace-nowrap mail-domain-suffix" id="mailDomainSuffix">@<?= htmlspecialchars(__('services.order.domain.select_domain')) ?></span>
                    </div>
                    <div class="relative w-36">
                        <input type="password" name="mail_pw[]" placeholder="<?= htmlspecialchars(__('services.order.addons.pw_placeholder')) ?>" class="w-full px-3 py-2 pr-9 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                        <button type="button" onclick="togglePw(this)" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" title="show/hide">👁</button>
                    </div>
                </div>
            </div>
            <button type="button" onclick="addMailAccount()" class="ml-6 mt-2 text-xs text-blue-600 hover:underline"><?= htmlspecialchars(__('services.order.addons.mail_add')) ?></button>
        </div>
    </div>
</section>
