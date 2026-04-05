<?php
/**
 * RezlyX Admin - POS 설정
 */
$pageTitle = __('reservations.pos_settings') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

$settings = [];
$stmt = $pdo->query("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` LIKE 'pos_%'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $settings[$row['key']] = $row['value'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'pos_auto_refresh' => ($_POST['pos_auto_refresh'] ?? '0') === '1' ? '1' : '0',
        'pos_refresh_interval' => max(10, (int)($_POST['pos_refresh_interval'] ?? 30)),
        'pos_card_size' => $_POST['pos_card_size'] ?? 'medium',
        'pos_show_service_image' => ($_POST['pos_show_service_image'] ?? '0') === '1' ? '1' : '0',
        'pos_image_opacity' => max(10, min(100, (int)($_POST['pos_image_opacity'] ?? 60))),
        'pos_show_modal_image' => ($_POST['pos_show_modal_image'] ?? '0') === '1' ? '1' : '0',
        'pos_modal_image_opacity' => max(10, min(100, (int)($_POST['pos_modal_image_opacity'] ?? 50))),
        'pos_show_price' => ($_POST['pos_show_price'] ?? '0') === '1' ? '1' : '0',
        'pos_show_phone' => ($_POST['pos_show_phone'] ?? '0') === '1' ? '1' : '0',
        'pos_sound_notification' => ($_POST['pos_sound_notification'] ?? '0') === '1' ? '1' : '0',
        'pos_default_tab' => $_POST['pos_default_tab'] ?? 'cards',
        'pos_require_staff' => ($_POST['pos_require_staff'] ?? '0') === '1' ? '1' : '0',
        'pos_auto_assign' => ($_POST['pos_auto_assign'] ?? '0') === '1' ? '1' : '0',
    ];
    $upsertStmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
    foreach ($fields as $k => $v) { $upsertStmt->execute([$k, $v]); $settings[$k] = $v; }
    $saved = true;
}

$tgl = 'w-9 h-5 bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-blue-600 rounded-full peer after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full';
$inp = 'px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200';
$hint = 'text-xs text-zinc-500 dark:text-zinc-400 mt-0.5';
?>
<!DOCTYPE html>
<html lang="<?= $config['locale'] ?? 'ko' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }</style>
    <script>if(localStorage.getItem('darkMode')==='true'||(!localStorage.getItem('darkMode')&&window.matchMedia('(prefers-color-scheme:dark)').matches)){document.documentElement.classList.add('dark')}</script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
    <div class="flex">
        <?php include BASE_PATH . '/resources/views/admin/partials/admin-sidebar.php'; ?>
        <main class="flex-1 ml-64">
            <?php $pageHeaderTitle = __('reservations.pos_settings'); include BASE_PATH . '/resources/views/admin/partials/admin-topbar.php'; ?>
            <div class="p-6">
                <div class="w-full">
                    <!-- 헤더 -->
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100"><?= __('reservations.pos_settings') ?></h1>
                            <p class="text-sm text-zinc-500 mt-1"><?= __('reservations.pos_settings_desc') ?></p>
                        </div>
                        <a href="<?= $adminUrl ?>/reservations/pos" class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('reservations.pos') ?></a>
                    </div>

                    <?php if (!empty($saved)): ?>
                    <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-300"><?= __('admin.common.saved') ?></div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">

                        <!-- ═══ 화면 설정 ═══ -->
                        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('reservations.pos_settings_display') ?></h3>
                            <div class="space-y-5">

                                <!-- 카드 크기 -->
                                <div class="flex items-start justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('reservations.pos_settings_card_size') ?></label>
                                        <p class="<?= $hint ?>"><?= __('reservations.pos_settings_card_size_help') ?></p>
                                    </div>
                                    <select name="pos_card_size" class="<?= $inp ?> ml-4">
                                        <option value="small" <?= ($settings['pos_card_size'] ?? 'medium') === 'small' ? 'selected' : '' ?>><?= __('reservations.pos_settings_small') ?></option>
                                        <option value="medium" <?= ($settings['pos_card_size'] ?? 'medium') === 'medium' ? 'selected' : '' ?>><?= __('reservations.pos_settings_medium') ?></option>
                                        <option value="large" <?= ($settings['pos_card_size'] ?? '') === 'large' ? 'selected' : '' ?>><?= __('reservations.pos_settings_large') ?></option>
                                    </select>
                                </div>

                                <!-- 서비스 이미지 표시 -->
                                <div>
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('reservations.pos_settings_show_image') ?></label>
                                            <p class="<?= $hint ?>"><?= __('reservations.pos_settings_show_image_help') ?></p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer ml-4">
                                            <input type="hidden" name="pos_show_service_image" value="0">
                                            <input type="checkbox" name="pos_show_service_image" value="1" <?= ($settings['pos_show_service_image'] ?? '1') === '1' ? 'checked' : '' ?> class="sr-only peer">
                                            <div class="<?= $tgl ?>"></div>
                                        </label>
                                    </div>
                                    <!-- 이미지 투명도 -->
                                    <div class="mt-3 ml-2 flex items-center gap-3">
                                        <label class="text-xs text-zinc-500 shrink-0"><?= __('reservations.pos_settings_image_opacity') ?></label>
                                        <input type="range" name="pos_image_opacity" min="10" max="100" step="5" value="<?= (int)($settings['pos_image_opacity'] ?? 60) ?>" class="flex-1 h-1.5 accent-blue-600" oninput="document.getElementById('opacityVal').textContent=this.value+'%'">
                                        <span id="opacityVal" class="text-xs font-mono text-zinc-600 dark:text-zinc-400 w-10 text-right"><?= (int)($settings['pos_image_opacity'] ?? 60) ?>%</span>
                                    </div>
                                </div>

                                <!-- 서비스 내역 모달 헤더 이미지 -->
                                <div>
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('reservations.pos_settings_show_modal_image') ?></label>
                                            <p class="<?= $hint ?>"><?= __('reservations.pos_settings_show_modal_image_help') ?></p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer ml-4">
                                            <input type="hidden" name="pos_show_modal_image" value="0">
                                            <input type="checkbox" name="pos_show_modal_image" value="1" <?= ($settings['pos_show_modal_image'] ?? '1') === '1' ? 'checked' : '' ?> class="sr-only peer">
                                            <div class="<?= $tgl ?>"></div>
                                        </label>
                                    </div>
                                    <div class="mt-3 ml-2 flex items-center gap-3">
                                        <label class="text-xs text-zinc-500 shrink-0"><?= __('reservations.pos_settings_image_opacity') ?></label>
                                        <input type="range" name="pos_modal_image_opacity" min="10" max="100" step="5" value="<?= (int)($settings['pos_modal_image_opacity'] ?? 50) ?>" class="flex-1 h-1.5 accent-blue-600" oninput="document.getElementById('modalOpacityVal').textContent=this.value+'%'">
                                        <span id="modalOpacityVal" class="text-xs font-mono text-zinc-600 dark:text-zinc-400 w-10 text-right"><?= (int)($settings['pos_modal_image_opacity'] ?? 50) ?>%</span>
                                    </div>
                                </div>

                                <!-- 가격 표시 -->
                                <div class="flex items-start justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('reservations.pos_settings_show_price') ?></label>
                                        <p class="<?= $hint ?>"><?= __('reservations.pos_settings_show_price_help') ?></p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                                        <input type="hidden" name="pos_show_price" value="0">
                                        <input type="checkbox" name="pos_show_price" value="1" <?= ($settings['pos_show_price'] ?? '1') === '1' ? 'checked' : '' ?> class="sr-only peer">
                                        <div class="<?= $tgl ?>"></div>
                                    </label>
                                </div>

                                <!-- 전화번호 표시 -->
                                <div class="flex items-start justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('reservations.pos_settings_show_phone') ?></label>
                                        <p class="<?= $hint ?>"><?= __('reservations.pos_settings_show_phone_help') ?></p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                                        <input type="hidden" name="pos_show_phone" value="0">
                                        <input type="checkbox" name="pos_show_phone" value="1" <?= ($settings['pos_show_phone'] ?? '1') === '1' ? 'checked' : '' ?> class="sr-only peer">
                                        <div class="<?= $tgl ?>"></div>
                                    </label>
                                </div>

                                <!-- 기본 탭 -->
                                <div class="flex items-start justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('reservations.pos_settings_default_tab') ?></label>
                                        <p class="<?= $hint ?>"><?= __('reservations.pos_settings_default_tab_help') ?></p>
                                    </div>
                                    <select name="pos_default_tab" class="<?= $inp ?> ml-4">
                                        <option value="cards" <?= ($settings['pos_default_tab'] ?? 'cards') === 'cards' ? 'selected' : '' ?>><?= __('reservations.pos_settings_tab_cards') ?></option>
                                        <option value="waiting" <?= ($settings['pos_default_tab'] ?? '') === 'waiting' ? 'selected' : '' ?>><?= __('reservations.pos_settings_tab_waiting') ?></option>
                                        <option value="reservations" <?= ($settings['pos_default_tab'] ?? '') === 'reservations' ? 'selected' : '' ?>><?= __('reservations.pos_settings_tab_reservations') ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- ═══ 자동화 설정 ═══ -->
                        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('reservations.pos_settings_automation') ?></h3>
                            <div class="space-y-5">

                                <!-- 자동 새로고침 -->
                                <div class="flex items-start justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('reservations.pos_settings_auto_refresh') ?></label>
                                        <p class="<?= $hint ?>"><?= __('reservations.pos_settings_auto_refresh_help') ?></p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                                        <input type="hidden" name="pos_auto_refresh" value="0">
                                        <input type="checkbox" name="pos_auto_refresh" value="1" <?= ($settings['pos_auto_refresh'] ?? '0') === '1' ? 'checked' : '' ?> class="sr-only peer">
                                        <div class="<?= $tgl ?>"></div>
                                    </label>
                                </div>

                                <!-- 새로고침 간격 -->
                                <div class="flex items-start justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('reservations.pos_settings_refresh_interval') ?></label>
                                        <p class="<?= $hint ?>"><?= __('reservations.pos_settings_refresh_interval_help') ?></p>
                                    </div>
                                    <div class="flex items-center gap-2 ml-4">
                                        <input type="number" name="pos_refresh_interval" value="<?= (int)($settings['pos_refresh_interval'] ?? 30) ?>" min="10" max="300" class="w-20 <?= $inp ?>">
                                        <span class="text-sm text-zinc-500"><?= __('reservations.pos_settings_seconds') ?></span>
                                    </div>
                                </div>

                                <!-- 알림음 -->
                                <div class="flex items-start justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('reservations.pos_settings_sound') ?></label>
                                        <p class="<?= $hint ?>"><?= __('reservations.pos_settings_sound_help') ?></p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                                        <input type="hidden" name="pos_sound_notification" value="0">
                                        <input type="checkbox" name="pos_sound_notification" value="1" <?= ($settings['pos_sound_notification'] ?? '0') === '1' ? 'checked' : '' ?> class="sr-only peer">
                                        <div class="<?= $tgl ?>"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- ═══ 운영 설정 ═══ -->
                        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('reservations.pos_settings_operation') ?></h3>
                            <div class="space-y-5">

                                <!-- 스태프 배정 필수 -->
                                <div class="flex items-start justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('reservations.pos_settings_require_staff') ?></label>
                                        <p class="<?= $hint ?>"><?= __('reservations.pos_settings_require_staff_help') ?></p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                                        <input type="hidden" name="pos_require_staff" value="0">
                                        <input type="checkbox" name="pos_require_staff" value="1" <?= ($settings['pos_require_staff'] ?? '1') === '1' ? 'checked' : '' ?> class="sr-only peer">
                                        <div class="<?= $tgl ?>"></div>
                                    </label>
                                </div>

                                <!-- 스태프 자동 배정 -->
                                <div class="flex items-start justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('reservations.pos_settings_auto_assign') ?></label>
                                        <p class="<?= $hint ?>"><?= __('reservations.pos_settings_auto_assign_help') ?></p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                                        <input type="hidden" name="pos_auto_assign" value="0">
                                        <input type="checkbox" name="pos_auto_assign" value="1" <?= ($settings['pos_auto_assign'] ?? '0') === '1' ? 'checked' : '' ?> class="sr-only peer">
                                        <div class="<?= $tgl ?>"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- 저장 -->
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.buttons.save') ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
