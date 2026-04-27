<?php
ob_start();
include __DIR__ . '/../_head.php';
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['ok'=>false,'msg'=>'method_not_allowed']); exit;
}

$token = $_POST['_token'] ?? '';
if (!$token || $token !== ($_SESSION['_csrf']??'')) {
    http_response_code(419); echo json_encode(['ok'=>false,'msg'=>'CSRF 토큰 오류']); exit;
}

$action = $_POST['action'] ?? '';
$db     = mkt_pdo();
$pfx    = $_mktPrefix;

// ─── submit_item (create / update) ───
if ($action === 'submit_item') {
    $itemId  = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    $isEdit  = $itemId > 0;
    $isDraft = ($_POST['save_draft']??'') === '1';

    $nameJson  = json_decode($_POST['name']  ?? '{}', true) ?: [];
    $shortJson = json_decode($_POST['short_description'] ?? '{}', true) ?: [];
    $descJson  = json_decode($_POST['description'] ?? '{}', true) ?: [];
    $tagsJson  = json_decode($_POST['tags']  ?? '[]', true) ?: [];
    $reqJson   = json_decode($_POST['requires_plugins'] ?? '[]', true) ?: [];

    if (empty($nameJson['en'])) {
        http_response_code(422); echo json_encode(['ok'=>false,'msg'=>'영문 이름(en)은 필수입니다.']); exit;
    }

    $existing = null;
    if ($isEdit) {
        $st = $db->prepare("SELECT * FROM {$pfx}mkt_items WHERE id=?");
        $st->execute([$itemId]); $existing = $st->fetch();
        if (!$existing) { http_response_code(404); echo json_encode(['ok'=>false,'msg'=>'아이템을 찾을 수 없습니다.']); exit; }
    }

    // ─── 이미지 업로드 ───
    if (!function_exists('rzx_upload_image')) {
        require_once BASE_PATH . '/rzxlib/Core/Helpers/image-upload.php';
    }
    $slugHint = preg_replace('/[^a-z0-9\-]+/','-',strtolower($nameJson['en'])) ?: ('item-'.time());
    $iconPath = null; $bannerPath = null; $screenshotPaths = [];

    if (!empty($_FILES['icon']['tmp_name'])) {
        $p = rzx_upload_image('icon','marketplace/icons',$slugHint.'-icon',512,512);
        if ($p) $iconPath = '/'.$p;
    }
    if (!empty($_FILES['banner']['tmp_name'])) {
        $p = rzx_upload_image('banner','marketplace/banners',$slugHint.'-banner',1200,600);
        if ($p) $bannerPath = '/'.$p;
    }
    if (!empty($_FILES['screenshots']['tmp_name']) && is_array($_FILES['screenshots']['tmp_name'])) {
        foreach ($_FILES['screenshots']['tmp_name'] as $i => $tmp) {
            if (($_FILES['screenshots']['error'][$i]??UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            $_FILES['_ss'] = ['tmp_name'=>$tmp,'name'=>$_FILES['screenshots']['name'][$i],'type'=>$_FILES['screenshots']['type'][$i]??'','size'=>$_FILES['screenshots']['size'][$i]??0,'error'=>UPLOAD_ERR_OK];
            $p = rzx_upload_image('_ss','marketplace/screenshots',$slugHint.'-ss-'.$i,1200,800);
            if ($p) $screenshotPaths[] = '/'.$p;
            unset($_FILES['_ss']);
        }
    }

    // ─── ZIP 패키지 ───
    $packageTmp = null; $packageHash = null; $packageSize = null;
    if (!empty($_FILES['package']['tmp_name']) && $_FILES['package']['error']===UPLOAD_ERR_OK) {
        if ($_FILES['package']['size'] > 50*1024*1024) {
            echo json_encode(['ok'=>false,'msg'=>'ZIP 파일은 50MB 이하여야 합니다.']); exit;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['package']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime,['application/zip','application/x-zip-compressed','application/octet-stream'],true)) {
            echo json_encode(['ok'=>false,'msg'=>'ZIP 파일만 허용됩니다.']); exit;
        }
        $packageTmp  = $_FILES['package']['tmp_name'];
        $packageSize = (int)$_FILES['package']['size'];
        $packageHash = hash_file('sha256', $packageTmp);
    }
    // ZIP 없어도 등록 가능: ZIP 없는 신규 아이템은 status='pending' (심사 대기)

    $slug = $isEdit
        ? $existing['slug']
        : (trim(preg_replace('/[^a-z0-9\-]+/', '-', strtolower($nameJson['en'])), '-') ?: ('item-' . time()));

    $priceType = $_POST['price_type'] ?? ((float)($_POST['price']??0)>0?'paid':'free');
    $price     = ($priceType==='paid') ? (float)($_POST['price']??0) : 0.0;
    $salePrice = ($priceType==='paid' && !empty($_POST['sale_price'])) ? (float)$_POST['sale_price'] : null;
    $saleEnds  = !empty($_POST['sale_ends_at']) ? date('Y-m-d H:i:s',strtotime($_POST['sale_ends_at'])) : null;

    $data = [
        'slug'               => $slug,
        'type'               => $_POST['item_type'] ?? ($existing['type']??'plugin'),
        'name'               => json_encode($nameJson, JSON_UNESCAPED_UNICODE),
        'description'        => json_encode(is_array($descJson)?$descJson:[], JSON_UNESCAPED_UNICODE),
        'short_description'  => json_encode(is_array($shortJson)?$shortJson:[], JSON_UNESCAPED_UNICODE),
        'partner_id'         => !empty($_POST['partner_id'])  ? (int)$_POST['partner_id']  : null,
        'license'            => trim($_POST['license']??'') ?: null,
        'tags'               => $tagsJson  ? json_encode($tagsJson, JSON_UNESCAPED_UNICODE) : null,
        'requires_plugins'   => $reqJson   ? json_encode($reqJson,  JSON_UNESCAPED_UNICODE) : null,
        'price'              => $price,
        'currency'           => $_POST['currency'] ?? ($existing['currency']??'JPY'),
        'sale_price'         => $salePrice,
        'sale_ends_at'       => $saleEnds,
        'latest_version'     => trim($_POST['version']??'1.0.0'),
        'min_voscms_version' => trim($_POST['min_voscms']??'') ?: null,
        'min_php_version'    => trim($_POST['min_php']??'') ?: null,
        'repo_url'           => trim($_POST['repo_url']??'') ?: null,
        'demo_url'           => trim($_POST['demo_url']??'') ?: null,
        // ZIP 없이 신규 생성 시 자동 'pending' (심사 대기); 수정 시엔 기존 상태 유지
        'status'             => (!$isEdit && !$packageTmp)
                                ? 'pending'
                                : ($isDraft ? 'draft' : ($_POST['status']??($existing['status']??'active'))),
    ];
    if ($iconPath)   $data['icon']         = $iconPath;
    if ($bannerPath) $data['banner_image'] = $bannerPath;
    if ($screenshotPaths) {
        $eShots = $existing ? (json_decode($existing['screenshots']??'[]',true)?:[]) : [];
        $data['screenshots'] = json_encode(array_values(array_merge($eShots,$screenshotPaths)), JSON_UNESCAPED_UNICODE);
    } elseif (!$isEdit) {
        $data['screenshots'] = json_encode([]);
    }

    try {
        if ($isEdit) {
            $sets = implode(',', array_map(fn($k)=>"`$k`=?", array_keys($data)));
            $db->prepare("UPDATE {$pfx}mkt_items SET $sets, updated_at=NOW() WHERE id=?")->execute([...array_values($data),$itemId]);
            $savedId = $itemId;
        } else {
            $data['product_key'] = sprintf('%08x-%04x-4%03x-%04x-%012x',
                random_int(0, 0xffffffff), random_int(0, 0xffff),
                random_int(0, 0xfff), random_int(0x8000, 0xbfff),
                random_int(0, 0xffffffffffff)
            );
            $cols = implode(',', array_map(fn($k)=>"`$k`", array_keys($data)));
            $phs  = implode(',', array_fill(0, count($data), '?'));
            $db->prepare("INSERT INTO {$pfx}mkt_items ($cols,created_at,updated_at) VALUES ($phs,NOW(),NOW())")->execute(array_values($data));
            $savedId = (int)$db->lastInsertId();
        }

        if ($packageTmp) {
            $storeDir = BASE_PATH.'/storage/uploads/packages/'.$slug;
            if (!is_dir($storeDir)) @mkdir($storeDir,0775,true);
            $saveName = $slug.'-'.$data['latest_version'].'.zip';
            $savePath = $storeDir.'/'.$saveName;
            if (is_uploaded_file($packageTmp)) @move_uploaded_file($packageTmp,$savePath);
            else @copy($packageTmp,$savePath);

            $dup = $db->prepare("SELECT id FROM {$pfx}mkt_item_versions WHERE item_id=? AND version=?");
            $dup->execute([$savedId,$data['latest_version']]); $dupRow=$dup->fetch();
            if (!$dupRow) {
                $db->prepare("INSERT INTO {$pfx}mkt_item_versions (item_id,version,changelog,file_path,file_size,file_hash,min_voscms_version,min_php_version,status,released_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())")
                   ->execute([$savedId,$data['latest_version'],trim($_POST['changelog']??'')?:null,'/storage/uploads/packages/'.$slug.'/'.$saveName,$packageSize,$packageHash,$data['min_voscms_version'],$data['min_php_version'],'active']);
            } else {
                $db->prepare("UPDATE {$pfx}mkt_item_versions SET changelog=?,file_path=?,file_size=?,file_hash=?,status='active',released_at=NOW() WHERE id=?")
                   ->execute([trim($_POST['changelog']??'')?:null,'/storage/uploads/packages/'.$slug.'/'.$saveName,$packageSize,$packageHash,$dupRow['id']]);
            }
        }

        echo json_encode(['ok'=>true,'success'=>true,'is_draft'=>$isDraft,'item_id'=>$savedId,'message'=>$isEdit?'수정되었습니다.':'등록되었습니다.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'msg'=>'DB 오류: '.$e->getMessage()]);
    }
    exit;
}

if ($action === 'status') {
    $id     = (int)($_POST['id']??0);
    $status = $_POST['status']??'';
    if (!$id || !in_array($status,['active','pending','draft','suspended','archived'],true)) {
        echo json_encode(['ok'=>false,'msg'=>'잘못된 요청']); exit;
    }
    $db->prepare("UPDATE {$pfx}mkt_items SET status=?,updated_at=NOW() WHERE id=?")->execute([$status,$id]);
    echo json_encode(['ok'=>true]); exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id']??0);
    if (!$id) { echo json_encode(['ok'=>false,'msg'=>'잘못된 요청']); exit; }
    $db->prepare("DELETE FROM {$pfx}mkt_items WHERE id=?")->execute([$id]);
    echo json_encode(['ok'=>true]); exit;
}

// 리뷰 상태 변경 + 평점 재계산
if ($action === 'review_status') {
    $rid    = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (!$rid || !in_array($status, ['approved','pending','rejected'], true)) {
        echo json_encode(['ok'=>false,'msg'=>'잘못된 요청']); exit;
    }
    $db->prepare("UPDATE {$pfx}mkt_reviews SET status=? WHERE id=?")->execute([$status, $rid]);
    $itemId = (int)$db->query("SELECT item_id FROM {$pfx}mkt_reviews WHERE id={$rid}")->fetchColumn();
    if ($itemId) {
        $db->prepare(
            "UPDATE {$pfx}mkt_items
                SET rating_avg   = COALESCE((SELECT AVG(rating) FROM {$pfx}mkt_reviews WHERE item_id=? AND status='approved'), 0),
                    rating_count = (SELECT COUNT(*) FROM {$pfx}mkt_reviews WHERE item_id=? AND status='approved')
              WHERE id=?"
        )->execute([$itemId, $itemId, $itemId]);
    }
    echo json_encode(['ok'=>true]); exit;
}

// ── 이슈 / Q&A 운영자 액션 ────────────────────────────────
if ($action === 'issue_status') {
    $iid    = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (!$iid || !in_array($status, ['open','closed','resolved'], true)) {
        echo json_encode(['ok'=>false,'msg'=>'잘못된 요청']); exit;
    }
    $db->prepare("UPDATE {$pfx}mkt_issues SET status=?, updated_at=NOW() WHERE id=?")->execute([$status, $iid]);
    echo json_encode(['ok'=>true]); exit;
}

if ($action === 'issue_delete') {
    $iid = (int)($_POST['id'] ?? 0);
    if (!$iid) { echo json_encode(['ok'=>false,'msg'=>'잘못된 요청']); exit; }
    $db->prepare("DELETE FROM {$pfx}mkt_issue_replies WHERE issue_id=?")->execute([$iid]);
    $db->prepare("DELETE FROM {$pfx}mkt_issues WHERE id=?")->execute([$iid]);
    echo json_encode(['ok'=>true]); exit;
}

if ($action === 'issue_reply_add') {
    $iid     = (int)($_POST['issue_id'] ?? 0);
    $bodyTxt = trim($_POST['body'] ?? '');
    if (!$iid || $bodyTxt === '') { echo json_encode(['ok'=>false,'msg'=>'잘못된 요청']); exit; }
    if (mb_strlen($bodyTxt) > 5000) $bodyTxt = mb_substr($bodyTxt, 0, 5000);
    $opName = $_SESSION['admin_name'] ?? '운영자';
    $db->prepare(
        "INSERT INTO {$pfx}mkt_issue_replies (issue_id, body, author_name, author_domain, is_partner_reply, is_verified)
         VALUES (?, ?, ?, NULL, 1, 0)"
    )->execute([$iid, $bodyTxt, $opName]);
    $db->prepare("UPDATE {$pfx}mkt_issues SET reply_count = reply_count + 1, updated_at = NOW() WHERE id=?")->execute([$iid]);
    echo json_encode(['ok'=>true]); exit;
}

if ($action === 'issue_reply_delete') {
    $rid = (int)($_POST['id'] ?? 0);
    if (!$rid) { echo json_encode(['ok'=>false,'msg'=>'잘못된 요청']); exit; }
    $iid = (int)$db->query("SELECT issue_id FROM {$pfx}mkt_issue_replies WHERE id={$rid}")->fetchColumn();
    $db->prepare("DELETE FROM {$pfx}mkt_issue_replies WHERE id=?")->execute([$rid]);
    if ($iid) {
        $db->prepare(
            "UPDATE {$pfx}mkt_issues
                SET reply_count = (SELECT COUNT(*) FROM {$pfx}mkt_issue_replies WHERE issue_id=?)
              WHERE id=?"
        )->execute([$iid, $iid]);
    }
    echo json_encode(['ok'=>true]); exit;
}

if ($action === 'review_delete') {
    $rid = (int)($_POST['id'] ?? 0);
    if (!$rid) { echo json_encode(['ok'=>false,'msg'=>'잘못된 요청']); exit; }
    $itemId = (int)$db->query("SELECT item_id FROM {$pfx}mkt_reviews WHERE id={$rid}")->fetchColumn();
    $db->prepare("DELETE FROM {$pfx}mkt_reviews WHERE id=?")->execute([$rid]);
    if ($itemId) {
        $db->prepare(
            "UPDATE {$pfx}mkt_items
                SET rating_avg   = COALESCE((SELECT AVG(rating) FROM {$pfx}mkt_reviews WHERE item_id=? AND status='approved'), 0),
                    rating_count = (SELECT COUNT(*) FROM {$pfx}mkt_reviews WHERE item_id=? AND status='approved')
              WHERE id=?"
        )->execute([$itemId, $itemId, $itemId]);
    }
    echo json_encode(['ok'=>true]); exit;
}

http_response_code(400);
echo json_encode(['ok'=>false,'msg'=>'unknown action']);
