<?php
/**
 * 적립금 - 모듈별 설정 (게시판별 포인트 설정)
 * Rhymix 스크린샷과 동일한 15개 컬럼 구조
 * points.php 에서 include
 */

// 게시판 목록 조회
$boards = [];
try {
    $boards = $pdo->query("SELECT id, title AS board_name, slug FROM {$prefix}boards WHERE is_active = 1 ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

// 모듈별 포인트 항목 (Rhymix와 동일)
$moduleColumns = [
    'insert_document'       => __('points.act_insert_document'),
    'insert_comment'        => __('points.act_insert_comment'),
    'upload_file'           => __('points.col_upload_file'),
    'download_file'         => __('points.col_download_file'),
    'read_document'         => __('points.col_read_document'),
    'voter'                 => __('points.col_voter'),
    'blamer'                => __('points.col_blamer'),
    'voter_comment'         => __('points.col_voter_comment'),
    'blamer_comment'        => __('points.col_blamer_comment'),
    'download_file_author'  => __('points.col_download_file_author'),
    'read_document_author'  => __('points.col_read_document_author'),
    'voted'                 => __('points.col_voted'),
    'blamed'                => __('points.col_blamed'),
    'voted_comment'         => __('points.col_voted_comment'),
    'blamed_comment'        => __('points.col_blamed_comment'),
];
?>

<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700">
    <div class="p-4 border-b dark:border-zinc-700">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('points.module_config') ?></h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __('points.module_config_desc') ?></p>
    </div>
    <div class="p-5">
        <?php if (empty($boards)): ?>
        <p class="text-center text-zinc-400 py-8"><?= __('points.no_boards') ?></p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="border-b-2 border-zinc-300 dark:border-zinc-600">
                        <th class="pb-3 pr-3 text-left font-semibold text-zinc-700 dark:text-zinc-300 sticky left-0 bg-white dark:bg-zinc-800 min-w-[100px] z-10"><?= __('points.col_module') ?></th>
                        <?php foreach ($moduleColumns as $colKey => $colLabel): ?>
                        <th class="pb-3 px-1 text-center font-medium text-zinc-600 dark:text-zinc-400 min-w-[70px] whitespace-pre-line leading-tight"><?= $colLabel ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-zinc-700">
                    <?php foreach ($boards as $b):
                        $bconf = json_decode($settings['point_board_' . $b['id']] ?? '{}', true) ?: [];
                    ?>
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <td class="py-3 pr-3 sticky left-0 bg-white dark:bg-zinc-800 z-10">
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100 text-sm"><?= htmlspecialchars($b['board_name']) ?></div>
                            <div class="text-zinc-400 dark:text-zinc-500" style="font-size:10px">(<?= htmlspecialchars($b['slug']) ?>)</div>
                        </td>
                        <?php foreach ($moduleColumns as $colKey => $colLabel): ?>
                        <td class="py-3 px-1 text-center">
                            <input type="number" class="brd-pt w-14 px-1 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded text-center bg-white dark:bg-zinc-700 dark:text-white text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                   data-board="<?= $b['id'] ?>" data-type="<?= $colKey ?>"
                                   value="<?= isset($bconf[$colKey]) ? (int)$bconf[$colKey] : '' ?>" placeholder="">
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-xs text-zinc-400 mt-3"><?= __('points.module_empty_hint') ?></p>
        <div class="flex justify-end mt-4">
            <button onclick="saveModuleConfig()" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"><?= __('points.save') ?></button>
        </div>
        <?php endif; ?>
    </div>
</div>
