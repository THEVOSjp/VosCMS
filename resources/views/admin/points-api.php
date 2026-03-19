<?php
/**
 * 적립금 관리 - AJAX API
 * points.php에서 include ($pdo, $prefix, $settings, $groups 사용 가능)
 */
try {

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// 설정값 저장 헬퍼
function saveSettings($pdo, $prefix, $data) {
    $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
    foreach ($data as $k => $v) {
        $stmt->execute([$k, (string)$v]);
    }
}

// === 기본 설정 저장 ===
if ($action === 'save_basic') {
    $data = [
        'point_module_enabled' => !empty($input['enabled']) ? 'Y' : 'N',
        'point_name' => trim($input['name'] ?? 'point'),
        'point_max_level' => max(1, min(1000, (int)($input['max_level'] ?? 30))),
        'point_level_icon' => $input['level_icon'] ?? 'default',
        'point_disable_download' => !empty($input['disable_download']) ? 'Y' : 'N',
        'point_disable_read' => !empty($input['disable_read']) ? 'Y' : 'N',
        'point_exchange_enabled' => !empty($input['exchange_enabled']) ? 'Y' : 'N',
        'point_exchange_rate' => (float)($input['exchange_rate'] ?? 1),
        'point_exchange_unit' => max(1, (int)($input['exchange_unit'] ?? 1000)),
        'point_exchange_min' => max(0, (int)($input['exchange_min'] ?? 1000)),
        'point_weight_payment' => max(1, (int)($input['weight_payment'] ?? 3)),
        'point_weight_activity' => max(1, (int)($input['weight_activity'] ?? 1)),
    ];
    saveSettings($pdo, $prefix, $data);

    // max_level 변경 시 레벨 테이블 확장
    $maxLevel = $data['point_max_level'];
    for ($i = 1; $i <= $maxLevel; $i++) {
        $pdo->prepare("INSERT IGNORE INTO {$prefix}point_levels (level, point) VALUES (?, 0)")->execute([$i]);
    }
    // 초과 레벨 삭제
    $pdo->prepare("DELETE FROM {$prefix}point_levels WHERE level > ?")->execute([$maxLevel]);

    echo json_encode(['success' => true, 'message' => __('points.saved')]);
    exit;
}

// === 포인트 부여/차감 저장 ===
if ($action === 'save_actions') {
    $acts = $input['actions'] ?? [];
    $data = [];
    foreach ($acts as $key => $val) {
        $data['point_' . $key] = (int)($val['value'] ?? 0);
        if (isset($val['revert'])) {
            $data['point_' . $key . '_revert'] = $val['revert'] ? 'Y' : 'N';
        }
        if (isset($val['limit'])) {
            $data['point_' . $key . '_limit'] = (int)$val['limit'];
        }
        if (isset($val['except_notice'])) {
            $data['point_' . $key . '_except_notice'] = $val['except_notice'] ? 'Y' : 'N';
        }
    }
    saveSettings($pdo, $prefix, $data);
    echo json_encode(['success' => true, 'message' => __('points.saved')]);
    exit;
}

// === 그룹 연동 저장 ===
if ($action === 'save_group') {
    $data = [
        'point_group_reset' => $input['group_reset'] ?? 'replace',
        'point_group_ratchet' => $input['group_ratchet'] ?? 'demote',
    ];
    $groupLevels = $input['group_levels'] ?? [];
    foreach ($groupLevels as $gid => $lvl) {
        $data['point_group_' . $gid] = (int)$lvl;
    }
    saveSettings($pdo, $prefix, $data);
    echo json_encode(['success' => true, 'message' => __('points.saved')]);
    exit;
}

// === 레벨 포인트 저장 ===
if ($action === 'save_levels') {
    $levelsData = $input['levels'] ?? [];
    $expr = trim($input['expression'] ?? '');
    if ($expr) {
        saveSettings($pdo, $prefix, ['point_expression' => $expr]);
    }

    $stmt = $pdo->prepare("INSERT INTO {$prefix}point_levels (level, point, group_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE point = VALUES(point), group_id = VALUES(group_id)");
    foreach ($levelsData as $lv) {
        $gid = !empty($lv['group_id']) ? $lv['group_id'] : null;
        $stmt->execute([(int)$lv['level'], (int)$lv['point'], $gid]);
    }
    echo json_encode(['success' => true, 'message' => __('points.saved')]);
    exit;
}

// === 포인트 재계산 ===
if ($action === 'recalc') {
    // 누적포인트(total_accumulated) 기준 레벨 재계산
    $maxLvl = (int)($settings['point_max_level'] ?? 30);
    $lvlPoints = [];
    $rows = $pdo->query("SELECT level, point FROM {$prefix}point_levels ORDER BY level")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) $lvlPoints[(int)$r['level']] = (int)$r['point'];

    $members = $pdo->query("SELECT user_id, total_accumulated FROM {$prefix}member_points")->fetchAll(PDO::FETCH_ASSOC);
    $upd = $pdo->prepare("UPDATE {$prefix}member_points SET level = ? WHERE user_id = ?");
    $cnt = 0;
    foreach ($members as $m) {
        $newLevel = 1;
        for ($i = $maxLvl; $i >= 1; $i--) {
            if (isset($lvlPoints[$i]) && $m['total_accumulated'] >= $lvlPoints[$i]) {
                $newLevel = $i;
                break;
            }
        }
        $upd->execute([$newLevel, $m['user_id']]);
        $cnt++;
    }
    echo json_encode(['success' => true, 'message' => __('points.recalc_done', ['count' => $cnt])]);
    exit;
}

// === 설정 초기화 (기본값 복원) ===
if ($action === 'reset_settings') {
    $pdo->beginTransaction();
    try {
        // 기본 설정값
        $defaults = [
            'point_module_enabled' => 'N',
            'point_name' => 'point',
            'point_max_level' => '50',
            'point_level_icon' => 'default',
            'point_disable_download' => 'N',
            'point_disable_read' => 'N',
            'point_exchange_enabled' => 'N',
            'point_exchange_rate' => '1',
            'point_exchange_unit' => '1000',
            'point_exchange_min' => '1000',
            'point_weight_payment' => '3',
            'point_weight_activity' => '1',
            'point_expression' => 'Math.pow(l, 2) * 250',
            'point_group_reset' => 'replace',
            'point_group_ratchet' => 'demote',
            // 포인트 부여/차감
            'point_signup' => '10',
            'point_login' => '5',
            'point_insert_document' => '10',
            'point_insert_comment' => '5',
            'point_upload_file' => '5',
            'point_download_file' => '-5',
            'point_read_document' => '0',
            'point_voter' => '0',
            'point_blamer' => '0',
            'point_voter_comment' => '0',
            'point_blamer_comment' => '0',
            'point_download_file_author' => '0',
            'point_read_document_author' => '0',
            'point_voted' => '0',
            'point_blamed' => '0',
            'point_voted_comment' => '0',
            'point_blamed_comment' => '0',
            // 그룹 연동 (기본 그룹 ID 기준)
            'point_group_c62bc9ff-13dc-11f1-8b4f-8447092025d9' => '1',
            'point_group_c998e8e0b3805ed51bfb11c7bf68c145a987' => '11',
            'point_group_0f4ed0fb2b6c6087c2a73c92ffc8c11e05ef' => '21',
            'point_group_5b9ac4fb8281987c5994b3ec40ba1f912b31' => '31',
            'point_group_b53dc7275b348c3526bc1921c84e0b2e9f88' => '41',
            'point_group_6e3582138e58938eac9cb3d3e7845710bb1e' => '48',
            'point_group_c3df1c9720a647c7d91c0c804e66ac044af9' => '0',
        ];

        $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        foreach ($defaults as $k => $v) {
            $stmt->execute([$k, $v]);
        }

        // 레벨 포인트 테이블 재생성
        $pdo->exec("DELETE FROM {$prefix}point_levels");
        $maxLevel = 50;
        $lvlIns = $pdo->prepare("INSERT INTO {$prefix}point_levels (level, point, group_id) VALUES (?, ?, ?)");

        // 그룹 매핑: 레벨 범위 → 그룹 ID
        $groupMap = [
            [1, 9, 'c62bc9ff-13dc-11f1-8b4f-8447092025d9'],    // 일반
            [10, 20, 'c998e8e0b3805ed51bfb11c7bf68c145a987'],   // 실버
            [21, 30, '0f4ed0fb2b6c6087c2a73c92ffc8c11e05ef'],   // 골드
            [31, 40, '5b9ac4fb8281987c5994b3ec40ba1f912b31'],   // 플래티넘
            [41, 47, 'b53dc7275b348c3526bc1921c84e0b2e9f88'],   // VIP
            [48, 50, '6e3582138e58938eac9cb3d3e7845710bb1e'],   // VVIP
        ];

        for ($i = 1; $i <= $maxLevel; $i++) {
            $pt = (int)(pow($i, 2) * 250);
            $gid = null;
            foreach ($groupMap as $gm) {
                if ($i >= $gm[0] && $i <= $gm[1]) { $gid = $gm[2]; break; }
            }
            $lvlIns->execute([$i, $pt, $gid]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => __('points.reset_settings_done')]);
    } catch (\Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// === 전체 초기화 ===
if ($action === 'reset_all') {
    $pdo->exec("UPDATE {$prefix}member_points SET point = 0, balance = 0, total_accumulated = 0, level = 1");
    echo json_encode(['success' => true, 'message' => __('points.reset_done')]);
    exit;
}

// === 회원 포인트 목록 ===
if ($action === 'member_list') {
    $page = max(1, (int)($input['page'] ?? 1));
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    $search = trim($input['search'] ?? '');

    // users를 기준으로 LEFT JOIN하여 member_points에 없는 회원도 표시
    $where = "WHERE u.status != 'withdrawn'";
    $params = [];
    if ($search) {
        $where .= " AND (u.name LIKE ? OR u.email LIKE ?)";
        $params = ["%$search%", "%$search%"];
    }

    $countSql = "SELECT COUNT(*) FROM {$prefix}users u $where";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT u.id AS user_id, u.name, u.email, u.created_at AS joined_at,
                   COALESCE(mp.point, 0) AS point,
                   COALESCE(mp.balance, 0) AS balance,
                   COALESCE(mp.total_accumulated, 0) AS total_accumulated,
                   COALESCE(mp.level, 1) AS level
            FROM {$prefix}users u
            LEFT JOIN {$prefix}member_points mp ON u.id = mp.user_id
            $where
            ORDER BY COALESCE(mp.total_accumulated, 0) DESC, u.created_at DESC
            LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 이름 복호화
    foreach ($list as &$row) {
        if (class_exists('\RzxLib\Core\Helpers\Encryption')) {
            $row['name'] = \RzxLib\Core\Helpers\Encryption::decrypt($row['name']);
        }
    }

    echo json_encode([
        'success' => true,
        'list' => $list,
        'total' => $total,
        'page' => $page,
        'total_pages' => ceil($total / $perPage)
    ]);
    exit;
}

// === 개별 포인트 수정 ===
if ($action === 'update_member_point') {
    $userId = $input['user_id'] ?? '';
    $point = (int)($input['point'] ?? 0);
    $mode = $input['mode'] ?? 'set'; // set, add, minus

    // 레코드가 없으면 먼저 생성
    $pdo->prepare("INSERT IGNORE INTO {$prefix}member_points (user_id, point, level) VALUES (?, 0, 1)")->execute([$userId]);

    if ($mode === 'add') {
        $pdo->prepare("UPDATE {$prefix}member_points SET point = point + ? WHERE user_id = ?")->execute([$point, $userId]);
    } elseif ($mode === 'minus') {
        $pdo->prepare("UPDATE {$prefix}member_points SET point = point - ? WHERE user_id = ?")->execute([$point, $userId]);
    } else {
        $pdo->prepare("UPDATE {$prefix}member_points SET point = ? WHERE user_id = ?")->execute([$point, $userId]);
    }
    echo json_encode(['success' => true, 'message' => __('points.saved')]);
    exit;
}

// === 모듈별 설정 저장 ===
if ($action === 'save_module_config') {
    $boards = $input['boards'] ?? [];
    $data = [];
    foreach ($boards as $bid => $conf) {
        $data['point_board_' . $bid] = json_encode($conf);
    }
    if ($data) saveSettings($pdo, $prefix, $data);
    echo json_encode(['success' => true, 'message' => __('points.saved')]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);

} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'API Error: ' . $e->getMessage()]);
}
