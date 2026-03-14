<?php
/**
 * RezlyX Admin - 스태프 설정 페이지
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}
include_once __DIR__ . '/../components/multilang-button.php';

$pageTitle = __('staff.settings.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

// DB 연결
$settings = [];
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $stmt = $pdo->query("SELECT `key`, `value` FROM {$prefix}settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key']] = $row['value'];
    }

    // POST 처리
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        // 직책 추가
        if ($action === 'add_position') {
            $posName = trim($_POST['position_name'] ?? '');
            if ($posName !== '') {
                $stmt = $pdo->prepare("SELECT MAX(sort_order) FROM {$prefix}staff_positions");
                $stmt->execute();
                $maxSort = (int)$stmt->fetchColumn();
                $stmt = $pdo->prepare("INSERT INTO {$prefix}staff_positions (name, is_active, sort_order) VALUES (?, 1, ?)");
                $stmt->execute([$posName, $maxSort + 1]);
                $message = __('staff.settings.position_added');
                $messageType = 'success';
            }
        }

        // 직책 수정
        if ($action === 'edit_position') {
            $posId = (int)($_POST['position_id'] ?? 0);
            $posName = trim($_POST['position_name'] ?? '');
            $nameI18n = $_POST['name_i18n'] ?? null;
            if ($posId > 0 && $posName !== '') {
                if ($nameI18n) {
                    $i18nJson = json_encode(json_decode($nameI18n, true), JSON_UNESCAPED_UNICODE);
                    $stmt = $pdo->prepare("UPDATE {$prefix}staff_positions SET name = ?, name_i18n = ? WHERE id = ?");
                    $stmt->execute([$posName, $i18nJson, $posId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE {$prefix}staff_positions SET name = ? WHERE id = ?");
                    $stmt->execute([$posName, $posId]);
                }
                $message = __('staff.settings.position_updated');
                $messageType = 'success';
            }
        }

        // 직책 삭제
        if ($action === 'delete_position') {
            $posId = (int)($_POST['position_id'] ?? 0);
            if ($posId > 0) {
                // 해당 직책을 사용 중인 스태프의 position_id를 NULL로
                $stmt = $pdo->prepare("UPDATE {$prefix}staff SET position_id = NULL WHERE position_id = ?");
                $stmt->execute([$posId]);
                $stmt = $pdo->prepare("DELETE FROM {$prefix}staff_positions WHERE id = ?");
                $stmt->execute([$posId]);
                $message = __('staff.settings.position_deleted');
                $messageType = 'success';
            }
        }

        // 직책 활성화/비활성화 토글 저장
        if ($action === 'save_positions') {
            $activeIds = $_POST['active_positions'] ?? [];
            // 모두 비활성 후 선택된 것만 활성
            $pdo->exec("UPDATE {$prefix}staff_positions SET is_active = 0");
            if (!empty($activeIds)) {
                $placeholders = implode(',', array_fill(0, count($activeIds), '?'));
                $stmt = $pdo->prepare("UPDATE {$prefix}staff_positions SET is_active = 1 WHERE id IN ({$placeholders})");
                $stmt->execute(array_map('intval', $activeIds));
            }
            $message = __('staff.settings.position_saved');
            $messageType = 'success';
        }

        // 설정 저장
        if ($action === 'save_staff_settings') {
            try {
                $fields = [
                    'staff_selection_required' => isset($_POST['staff_selection_required']) ? '1' : '0',
                    'staff_show_bio' => isset($_POST['staff_show_bio']) ? '1' : '0',
                    'staff_show_photo' => isset($_POST['staff_show_photo']) ? '1' : '0',
                    'staff_auto_assign' => trim($_POST['staff_auto_assign'] ?? 'none'),
                    'staff_linked_grade' => trim($_POST['staff_linked_grade'] ?? ''),
                    'staff_schedule_enabled' => isset($_POST['staff_schedule_enabled']) ? '1' : '0',
                    'staff_designation_fee_enabled' => isset($_POST['staff_designation_fee_enabled']) ? '1' : '0',
                    'booking_slot_interval' => trim($_POST['booking_slot_interval'] ?? '30'),
                ];

                $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                foreach ($fields as $key => $value) {
                    $stmt->execute([$key, $value]);
                }

                // 연동 등급 변경 시 일괄 동기화
                $oldLinkedGrade = $settings['staff_linked_grade'] ?? '';
                $newLinkedGrade = $fields['staff_linked_grade'];
                $syncMsg = '';
                if ($newLinkedGrade && $newLinkedGrade !== $oldLinkedGrade) {
                    require_once BASE_PATH . '/rzxlib/Core/Helpers/StaffSync.php';
                    $syncResult = StaffSync::syncAllByGrade($pdo, $prefix, $newLinkedGrade);
                    if ($syncResult['created'] > 0 || $syncResult['activated'] > 0) {
                        $syncMsg = ' (' . __('staff.settings.sync_result', [
                            'created' => $syncResult['created'],
                            'activated' => $syncResult['activated']
                        ]) . ')';
                    }
                }

                $message = __('staff.settings.saved') . $syncMsg;
                $messageType = 'success';

                foreach ($fields as $key => $value) {
                    $settings[$key] = $value;
                }
            } catch (PDOException $e) {
                $message = $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    // 직책 목록 로드
    $positions = $pdo->query("SELECT * FROM {$prefix}staff_positions ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 회원 등급 목록 로드
    $memberGrades = $pdo->query("SELECT id, name, slug FROM {$prefix}member_grades ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
    $memberGrades = [];
}
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
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
    <div class="flex">
        <?php include dirname(__DIR__) . '/partials/admin-sidebar.php'; ?>

        <main class="flex-1 ml-64">
            <?php include dirname(__DIR__) . '/partials/admin-topbar.php'; ?>

            <div class="p-8">
                <!-- 알림 -->
                <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-lg border <?= $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
                <?php endif; ?>

                <!-- 헤더 -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('staff.settings.title') ?></h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __('staff.settings.description') ?></p>
                </div>

                <!-- 설정 폼 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm">
                    <form method="POST" action="<?= $adminUrl ?>/staff/settings">
                        <input type="hidden" name="action" value="save_staff_settings">

                        <div class="p-6 space-y-6">
                            <!-- 예약 시 스태프 선택 필수 -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('staff.settings.selection_required') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5"><?= __('staff.settings.selection_required_desc') ?></p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="staff_selection_required" class="sr-only peer" <?= ($settings['staff_selection_required'] ?? '0') === '1' ? 'checked' : '' ?>>
                                    <div class="w-11 h-6 bg-zinc-200 peer-focus:ring-2 peer-focus:ring-blue-500 dark:bg-zinc-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            </div>

                            <!-- 소개 표시 -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('staff.settings.show_bio') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5"><?= __('staff.settings.show_bio_desc') ?></p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="staff_show_bio" class="sr-only peer" <?= ($settings['staff_show_bio'] ?? '1') === '1' ? 'checked' : '' ?>>
                                    <div class="w-11 h-6 bg-zinc-200 peer-focus:ring-2 peer-focus:ring-blue-500 dark:bg-zinc-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            </div>

                            <!-- 사진 표시 -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('staff.settings.show_photo') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5"><?= __('staff.settings.show_photo_desc') ?></p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="staff_show_photo" class="sr-only peer" <?= ($settings['staff_show_photo'] ?? '1') === '1' ? 'checked' : '' ?>>
                                    <div class="w-11 h-6 bg-zinc-200 peer-focus:ring-2 peer-focus:ring-blue-500 dark:bg-zinc-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            </div>

                            <!-- 자동 배정 -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    <?= __('staff.settings.auto_assign') ?>
                                </label>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('staff.settings.auto_assign_desc') ?></p>
                                <?php $autoAssign = $settings['staff_auto_assign'] ?? 'none'; ?>
                                <select name="staff_auto_assign"
                                        class="w-48 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm">
                                    <option value="none" <?= $autoAssign === 'none' ? 'selected' : '' ?>><?= __('staff.settings.assign_none') ?></option>
                                    <option value="round_robin" <?= $autoAssign === 'round_robin' ? 'selected' : '' ?>><?= __('staff.settings.assign_round_robin') ?></option>
                                    <option value="least_busy" <?= $autoAssign === 'least_busy' ? 'selected' : '' ?>><?= __('staff.settings.assign_least_busy') ?></option>
                                </select>
                            </div>

                            <!-- 구분선 -->
                            <div class="border-t border-zinc-200 dark:border-zinc-700"></div>

                            <!-- 스태프 스케줄 관리 -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('staff.settings.schedule_enabled') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5"><?= __('staff.settings.schedule_enabled_desc') ?></p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="staff_schedule_enabled" class="sr-only peer" <?= ($settings['staff_schedule_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                                    <div class="w-11 h-6 bg-zinc-200 peer-focus:ring-2 peer-focus:ring-blue-500 dark:bg-zinc-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            </div>

                            <!-- 지명비 기능 -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('staff.settings.designation_fee_enabled') ?></label>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5"><?= __('staff.settings.designation_fee_enabled_desc') ?></p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="staff_designation_fee_enabled" class="sr-only peer" <?= ($settings['staff_designation_fee_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                                    <div class="w-11 h-6 bg-zinc-200 peer-focus:ring-2 peer-focus:ring-blue-500 dark:bg-zinc-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            </div>

                            <!-- 타임슬롯 간격 -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('staff.settings.booking_slot_interval') ?></label>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('staff.settings.booking_slot_interval_desc') ?></p>
                                <?php $slotInterval = $settings['booking_slot_interval'] ?? '30'; ?>
                                <select name="booking_slot_interval"
                                        class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm">
                                    <option value="15" <?= $slotInterval === '15' ? 'selected' : '' ?>>15<?= __('common.minutes') ?></option>
                                    <option value="30" <?= $slotInterval === '30' ? 'selected' : '' ?>>30<?= __('common.minutes') ?></option>
                                    <option value="60" <?= $slotInterval === '60' ? 'selected' : '' ?>>60<?= __('common.minutes') ?></option>
                                </select>
                            </div>

                            <!-- 구분선 -->
                            <div class="border-t border-zinc-200 dark:border-zinc-700"></div>

                            <!-- 스태프 연동 등급 -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    <?= __('staff.settings.linked_grade') ?>
                                </label>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('staff.settings.linked_grade_desc') ?></p>
                                <?php $linkedGrade = $settings['staff_linked_grade'] ?? ''; ?>
                                <select name="staff_linked_grade"
                                        class="w-64 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-purple-500 text-sm">
                                    <option value="" <?= $linkedGrade === '' ? 'selected' : '' ?>><?= __('staff.settings.grade_none') ?></option>
                                    <?php foreach ($memberGrades as $grade): ?>
                                    <option value="<?= htmlspecialchars($grade['id']) ?>" <?= $linkedGrade === $grade['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($grade['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($linkedGrade): ?>
                                <div class="mt-2 p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                                    <p class="text-xs text-purple-700 dark:text-purple-300">
                                        <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <?= __('staff.settings.linked_grade_info') ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 저장 버튼 -->
                        <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-700 rounded-b-xl flex justify-end">
                            <button type="submit"
                                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                                <?= __('admin.common.save') ?>
                            </button>
                        </div>
                    </form>
                </div>
                <!-- 스태프 직책 관리 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm mt-6">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('staff.settings.position_title') ?></h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('staff.settings.position_desc') ?></p>
                    </div>

                    <!-- 직책 체크박스 목록 + 활성화 저장 -->
                    <form method="POST" action="<?= $adminUrl ?>/staff/settings" id="positionForm">
                        <input type="hidden" name="action" value="save_positions">
                        <div class="p-6">
                            <?php if (!empty($positions)): ?>
                            <div class="space-y-2" id="positionList">
                                <?php foreach ($positions as $pos):
                                    $i18n = $pos['name_i18n'] ? json_decode($pos['name_i18n'], true) : [];
                                ?>
                                <div class="flex items-center justify-between group py-2 px-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors" id="pos-row-<?= $pos['id'] ?>">
                                    <!-- 보기 모드 -->
                                    <div class="flex items-center gap-3 flex-1 pos-view" data-id="<?= $pos['id'] ?>">
                                        <input type="checkbox" name="active_positions[]" value="<?= $pos['id'] ?>" <?= $pos['is_active'] ? 'checked' : '' ?>
                                               class="w-4 h-4 rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500 dark:bg-zinc-700">
                                        <span class="text-sm text-zinc-900 dark:text-white font-medium"><?= htmlspecialchars($pos['name']) ?></span>
                                        <?php if ($pos['is_active']): ?>
                                        <span class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"><?= __('staff.settings.position_active') ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($i18n)): ?>
                                        <span class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"><?= count($i18n) ?> <?= __('staff.settings.position_langs') ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- 액션 버튼들 -->
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity pos-actions" data-id="<?= $pos['id'] ?>">
                                        <!-- 수정 버튼 -->
                                        <button type="button" onclick="openEditPosition(<?= $pos['id'] ?>, <?= htmlspecialchars(json_encode($pos['name']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($i18n), ENT_QUOTES) ?>)"
                                                class="p-1.5 text-zinc-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-all" title="<?= __('staff.settings.position_edit') ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <!-- 다국어 버튼 -->
                                        <?= rzx_multilang_btn("openEditPosition({$pos['id']}, " . htmlspecialchars(json_encode($pos['name']), ENT_QUOTES) . ", " . htmlspecialchars(json_encode($i18n), ENT_QUOTES) . ", true)") ?>
                                        <!-- 삭제 버튼 -->
                                        <button type="button" onclick="deletePosition(<?= $pos['id'] ?>)"
                                                class="p-1.5 text-zinc-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-all" title="<?= __('admin.common.delete') ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <p class="text-sm text-zinc-400"><?= __('staff.settings.position_empty') ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="px-6 py-3 border-t border-zinc-200 dark:border-zinc-700 flex justify-end">
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                                <?= __('staff.settings.position_save_active') ?>
                            </button>
                        </div>
                    </form>

                    <!-- 직책 추가 -->
                    <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-700 rounded-b-xl">
                        <form method="POST" action="<?= $adminUrl ?>/staff/settings" class="flex gap-2 items-center">
                            <input type="hidden" name="action" value="add_position">
                            <input type="text" name="position_name" placeholder="<?= __('staff.settings.position_placeholder') ?>"
                                   class="flex-1 max-w-xs px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500" required>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <?= __('staff.settings.position_add') ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 직책 수정 모달 -->
            <div id="editPositionModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-zinc-900/75 transition-opacity" onclick="closeEditPosition()"></div>
                    <div class="relative z-50 w-full max-w-lg bg-white dark:bg-zinc-800 rounded-xl shadow-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('staff.settings.position_edit') ?></h3>
                            <button type="button" onclick="closeEditPosition()" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <form method="POST" action="<?= $adminUrl ?>/staff/settings" id="editPositionForm">
                            <input type="hidden" name="action" value="edit_position">
                            <input type="hidden" name="position_id" id="editPosId">
                            <input type="hidden" name="name_i18n" id="editPosI18n">

                            <!-- 기본 이름 -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.settings.position_name_label') ?></label>
                                <input type="text" name="position_name" id="editPosName" required
                                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- 다국어 입력 영역 (토글) -->
                            <div id="editPosI18nSection" class="hidden mb-4">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                    <?= __('staff.settings.position_multilang') ?>
                                </label>
                                <div class="space-y-2 max-h-60 overflow-y-auto" id="editPosLangFields"></div>
                            </div>

                            <div class="flex justify-end gap-2">
                                <button type="button" onclick="closeEditPosition()" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition"><?= __('settings.multilang.cancel') ?></button>
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.common.save') ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 삭제용 hidden form -->
            <form method="POST" action="<?= $adminUrl ?>/staff/settings" id="deletePositionForm" class="hidden">
                <input type="hidden" name="action" value="delete_position">
                <input type="hidden" name="position_id" id="deletePosId">
            </form>
        </main>
    </div>

    <script>
    (function() {
        'use strict';

        // 지원 언어 목록
        var langNativeNames = {ko:'한국어',en:'English',ja:'日本語',zh_CN:'简体中文',zh_TW:'繁體中文',de:'Deutsch',es:'Español',fr:'Français',vi:'Tiếng Việt',id:'Bahasa Indonesia',mn:'Монгол',ru:'Русский',tr:'Türkçe'};
        var activeCodes = <?= json_encode(json_decode($settings['supported_languages'] ?? '["ko","en","ja"]', true) ?: ['ko','en','ja']) ?>;
        var supportedLangs = {};
        activeCodes.forEach(function(c) { supportedLangs[c] = langNativeNames[c] || c; });
        console.log('[StaffPositions] Supported langs:', Object.keys(supportedLangs));

        var currentI18n = {};

        // 수정 모달 열기
        window.openEditPosition = function(id, name, i18n, showI18n) {
            document.getElementById('editPosId').value = id;
            document.getElementById('editPosName').value = name;
            currentI18n = i18n || {};

            // 다국어 필드 생성
            var langFields = document.getElementById('editPosLangFields');
            langFields.innerHTML = '';
            Object.keys(supportedLangs).forEach(function(code) {
                var nativeName = supportedLangs[code];
                var div = document.createElement('div');
                div.className = 'flex items-center gap-2';
                div.innerHTML = '<span class="w-16 text-xs font-medium text-zinc-500 dark:text-zinc-400 shrink-0">' + nativeName + '</span>' +
                    '<input type="text" data-lang="' + code + '" value="' + (currentI18n[code] || '').replace(/"/g, '&quot;') + '"' +
                    ' class="flex-1 px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm" placeholder="' + nativeName + '">';
                langFields.appendChild(div);
            });

            var i18nSection = document.getElementById('editPosI18nSection');
            if (showI18n) {
                i18nSection.classList.remove('hidden');
            } else {
                i18nSection.classList.add('hidden');
            }

            document.getElementById('editPositionModal').classList.remove('hidden');
            document.getElementById('editPosName').focus();
            console.log('[StaffPositions] Edit modal opened for id:', id);
        };

        // 수정 모달 닫기
        window.closeEditPosition = function() {
            document.getElementById('editPositionModal').classList.add('hidden');
            console.log('[StaffPositions] Edit modal closed');
        };

        // 수정 폼 제출 시 i18n JSON 생성
        document.getElementById('editPositionForm').addEventListener('submit', function() {
            var i18n = {};
            document.querySelectorAll('#editPosLangFields input[data-lang]').forEach(function(input) {
                var val = input.value.trim();
                if (val) i18n[input.dataset.lang] = val;
            });
            document.getElementById('editPosI18n').value = Object.keys(i18n).length > 0 ? JSON.stringify(i18n) : '';
            console.log('[StaffPositions] Saving position with i18n:', i18n);
        });

        // 삭제
        window.deletePosition = function(id) {
            if (!confirm('<?= __('staff.settings.position_delete_confirm') ?>')) return;
            document.getElementById('deletePosId').value = id;
            document.getElementById('deletePositionForm').submit();
            console.log('[StaffPositions] Deleting position:', id);
        };

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeEditPosition();
        });

        console.log('[StaffPositions] Module initialized');
    })();
    </script>
</body>
</html>
