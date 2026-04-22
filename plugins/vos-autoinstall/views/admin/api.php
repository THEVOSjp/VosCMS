<?php
/**
 * VosCMS Marketplace - Admin AJAX API Handler
 */
header('Content-Type: application/json; charset=utf-8');

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$adminId = $_SESSION['admin_id'] ?? '';

if (!$adminId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    return;
}

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    return;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'purchase':
        $itemId = (int)($_POST['item_id'] ?? 0);
        if (!$itemId) {
            echo json_encode(['success' => false, 'message' => 'Invalid item']);
            return;
        }

        $stmt = $pdo->prepare("SELECT * FROM {$prefix}mp_items WHERE id = ? AND status = 'active'");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
            return;
        }

        // 이미 구매 확인
        $checkStmt = $pdo->prepare(
            "SELECT oi.id FROM {$prefix}mp_order_items oi
             JOIN {$prefix}mp_orders o ON o.id = oi.order_id
             WHERE o.admin_id = ? AND oi.item_id = ? AND o.status = 'paid' LIMIT 1"
        );
        $checkStmt->execute([$adminId, $itemId]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Already purchased']);
            return;
        }

        $price = (float)$item['price'];
        $locale = $_SESSION['locale'] ?? 'ko';
        $name = json_decode($item['name'], true);
        $itemName = $name[$locale] ?? $name['en'] ?? $item['slug'];

        // UUID 생성
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        $orderNum = 'MKT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        $isFree = $price <= 0;

        // 주문 생성
        $orderStmt = $pdo->prepare(
            "INSERT INTO {$prefix}mp_orders (uuid, admin_id, order_number, subtotal, total, currency, status, paid_at, metadata, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $orderStmt->execute([
            $uuid, $adminId, $orderNum, $price, $price, $item['currency'],
            $isFree ? 'paid' : 'pending',
            $isFree ? date('Y-m-d H:i:s') : null,
            json_encode(['type' => 'autoinstall'])
        ]);
        $orderId = (int)$pdo->lastInsertId();

        // 주문 항목 생성
        $oiStmt = $pdo->prepare(
            "INSERT INTO {$prefix}mp_order_items (order_id, item_id, item_name, item_type, item_slug, price, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $oiStmt->execute([$orderId, $itemId, $itemName, $item['type'], $item['slug'], $price]);
        $orderItemId = (int)$pdo->lastInsertId();

        if ($isFree) {
            // 무료: 바로 라이선스 발급
            $licKey = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

            $licStmt = $pdo->prepare(
                "INSERT INTO {$prefix}mp_licenses (license_key, order_item_id, item_id, admin_id, type, max_activations, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 'single', 1, 'active', NOW(), NOW())"
            );
            $licStmt->execute([$licKey, $orderItemId, $itemId, $adminId]);

            // 다운로드 카운트 증가
            $pdo->prepare("UPDATE {$prefix}mp_items SET download_count = download_count + 1 WHERE id = ?")->execute([$itemId]);

            echo json_encode(['success' => true, 'is_free' => true, 'redirect' => null]);
        } else {
            // 유료: 결제 페이지로 리다이렉트 (PaymentService 연동)
            $baseUrl = $config['app_url'] ?? '';
            $adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
            echo json_encode([
                'success' => true,
                'is_free' => false,
                'order_uuid' => $uuid,
                'redirect' => $adminUrl . '/autoinstall/purchases',
                'message' => 'Order created. Payment integration pending.',
            ]);
        }
        break;

    case 'deactivate_domain':
        $licenseId = (int)($_POST['license_id'] ?? 0);
        $domain = trim($_POST['domain'] ?? '');

        if (!$licenseId || !$domain) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            return;
        }

        // 소유자 확인
        $licCheck = $pdo->prepare("SELECT id FROM {$prefix}mp_licenses WHERE id = ? AND admin_id = ?");
        $licCheck->execute([$licenseId, $adminId]);
        if (!$licCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'License not found']);
            return;
        }

        $pdo->prepare(
            "UPDATE {$prefix}mp_license_activations SET is_active = 0, deactivated_at = NOW()
             WHERE license_id = ? AND domain = ?"
        )->execute([$licenseId, $domain]);

        echo json_encode(['success' => true]);
        break;

    case 'sync_catalog':
        $pm = $pluginManager ?? \RzxLib\Core\Plugin\PluginManager::getInstance();
        $apiUrl = $pm ? $pm->getSetting('vos-marketplace', 'marketplace_api_url', 'https://marketplace.voscms.com/api') : '';

        if (!$apiUrl) {
            echo json_encode(['success' => false, 'message' => 'API URL not configured']);
            return;
        }

        // CatalogClient 사용
        require_once __DIR__ . '/../../src/CatalogClient.php';
        $client = new \VosMarketplace\CatalogClient($apiUrl);
        $result = $client->syncCatalog();

        echo json_encode($result);
        break;

    // ─── 아이템 등록/수정 (관리자) ───
    case 'submit_item':
        $itemId = (int)($_POST['item_id'] ?? 0);
        $name = $_POST['name'] ?? '{}';
        $type = $_POST['item_type'] ?? 'plugin';
        $shortDesc = $_POST['short_description'] ?? '{}';
        $description = $_POST['description'] ?? '{}';
        $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
        $tags = $_POST['tags'] ?? '[]';
        $license = $_POST['license'] ?? null;
        $repoUrl = $_POST['repo_url'] ?? null;
        $demoUrl = $_POST['demo_url'] ?? null;
        $reqPlugins = $_POST['requires_plugins'] ?? '[]';
        $version = $_POST['version'] ?? '1.0.0';
        $minVoscms = $_POST['min_voscms'] ?? null;
        $minPhp = $_POST['min_php'] ?? null;
        $price = (float)($_POST['price'] ?? 0);
        $currency = $_POST['currency'] ?? 'USD';
        $salePrice = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
        $saleEnds = $_POST['sale_ends_at'] ?? null;
        $changelog = $_POST['changelog'] ?? '';

        // slug 생성 (영문 이름에서)
        $nameData = json_decode($name, true) ?: [];
        $slugBase = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nameData['en'] ?? 'item-' . time()));
        $slug = trim($slugBase, '-');

        // 파일 업로드 처리
        $uploadDir = BASE_PATH . '/storage/uploads/autoinstall/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);

        $iconPath = null;
        if (!empty($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION) ?: 'png';
            $fn = 'icon_' . $slug . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['icon']['tmp_name'], $uploadDir . $fn)) {
                $iconPath = '/storage/uploads/autoinstall/' . $fn;
            }
        }

        $bannerPath = null;
        if (!empty($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $fn = 'banner_' . $slug . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['banner']['tmp_name'], $uploadDir . $fn)) {
                $bannerPath = '/storage/uploads/autoinstall/' . $fn;
            }
        }

        $screenshots = [];
        if (!empty($_FILES['screenshots'])) {
            foreach ($_FILES['screenshots']['tmp_name'] as $i => $tmp) {
                if ($_FILES['screenshots']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $ext = pathinfo($_FILES['screenshots']['name'][$i], PATHINFO_EXTENSION) ?: 'jpg';
                $fn = 'ss_' . $slug . '_' . time() . '_' . $i . '.' . $ext;
                if (move_uploaded_file($tmp, $uploadDir . $fn)) {
                    $screenshots[] = '/storage/uploads/autoinstall/' . $fn;
                }
            }
        }

        try {
            if ($itemId) {
                // 수정
                $sets = "name=?, type=?, short_description=?, description=?, category_id=?, tags=?, license=?, repo_url=?, demo_url=?, requires_plugins=?, latest_version=?, min_voscms_version=?, min_php_version=?, price=?, currency=?, sale_price=?, sale_ends_at=?, updated_at=NOW()";
                $params = [$name, $type, $shortDesc, $description, $categoryId, $tags, $license, $repoUrl, $demoUrl, $reqPlugins, $version, $minVoscms, $minPhp, $price, $currency, $salePrice, $saleEnds ?: null];

                if ($iconPath) { $sets .= ", icon=?"; $params[] = $iconPath; }
                if ($bannerPath) { $sets .= ", banner_image=?"; $params[] = $bannerPath; }
                if (!empty($screenshots)) { $sets .= ", screenshots=?"; $params[] = json_encode($screenshots); }

                $params[] = $itemId;
                $pdo->prepare("UPDATE {$prefix}mp_items SET {$sets} WHERE id=?")->execute($params);
                echo json_encode(['success' => true, 'message' => '아이템이 수정되었습니다.', 'id' => $itemId]);
            } else {
                // 신규 등록
                $stmt = $pdo->prepare("INSERT INTO {$prefix}mp_items (slug, name, type, short_description, description, author_name, category_id, tags, license, repo_url, demo_url, requires_plugins, icon, banner_image, screenshots, latest_version, min_voscms_version, min_php_version, price, currency, sale_price, sale_ends_at, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$slug, $name, $type, $shortDesc, $description, $_SESSION['admin_name'] ?? 'Admin', $categoryId, $tags, $license, $repoUrl, $demoUrl, $reqPlugins, $iconPath, $bannerPath, json_encode($screenshots), $version, $minVoscms, $minPhp, $price, $currency, $salePrice, $saleEnds ?: null, 'active']);
                $newId = $pdo->lastInsertId();

                // 버전 레코드 생성
                $pdo->prepare("INSERT INTO {$prefix}mp_item_versions (item_id, version, changelog, min_voscms_version, min_php_version) VALUES (?,?,?,?,?)")->execute([$newId, $version, $changelog, $minVoscms, $minPhp]);

                echo json_encode(['success' => true, 'message' => '아이템이 등록되었습니다.', 'id' => $newId]);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
        }
        break;

    case 'register_license':
        $slug = trim($_POST['slug'] ?? '');
        $id   = trim($_POST['id'] ?? '') ?: $slug; // widget:slug, layout:slug 등 네임스페이스
        if (!$slug) { echo json_encode(['success' => false, 'message' => 'slug 필수']); return; }

        // VosCMS 라이선스 키
        $licCache = @json_decode(file_get_contents(BASE_PATH . '/storage/.license_cache'), true);
        $vosKey = $licCache['license_key'] ?? '';
        if (!$vosKey) { echo json_encode(['success' => false, 'message' => 'VosCMS 라이선스 키 없음']); return; }

        // 사이트 도메인
        $domainSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'site_url'");
        $domainSt->execute();
        $siteUrl = $domainSt->fetchColumn() ?: ($_SERVER['HTTP_HOST'] ?? '');
        $domain  = strtolower(preg_replace('#^https?://#', '', rtrim($siteUrl, '/')));

        // market API 호출
        $marketUrl = 'https://market.21ces.com/api/market/item/install';
        $payload   = json_encode(['domain' => $domain, 'vos_key' => $vosKey, 'item_slug' => $slug]);
        $ch = curl_init($marketUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = $resp ? json_decode($resp, true) : null;
        if (!$result || !($result['ok'] ?? false)) {
            $msg = $result['message'] ?? '마켓 API 오류 (HTTP ' . $httpCode . ')';
            echo json_encode(['success' => false, 'message' => $msg]);
            return;
        }

        $licKey     = $result['license_key'] ?? '';
        $productKey = $result['product_key'] ?? '';
        if (!$licKey) { echo json_encode(['success' => false, 'message' => '라이선스 키 수신 실패']); return; }

        // 로컬 저장 (id 기준 — widget:slug, layout:slug 등 포함)
        $saves = [
            'market_license_key'    => $licKey,
            'market_license_status' => 'valid',
        ];
        if ($productKey) $saves['market_product_key'] = $productKey;

        foreach ($saves as $k => $v) {
            $pdo->prepare(
                "INSERT INTO {$prefix}plugin_settings (plugin_id, setting_key, setting_value)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            )->execute([$id, $k, $v]);
        }

        echo json_encode([
            'success'     => true,
            'key_preview' => substr($licKey, 0, 8) . '...' . substr($licKey, -4),
            'product_key' => $productKey,
            'type'        => $result['type'] ?? 'free',
            'is_new'      => $result['is_new'] ?? true,
        ]);
        break;

    case 'validate_license':
        $slug = trim($_POST['slug'] ?? '');
        $id   = trim($_POST['id'] ?? '') ?: $slug;
        if (!$slug) { echo json_encode(['success' => false, 'message' => 'slug 필수']); return; }

        // 저장된 라이선스 키 (id 기준)
        $keySt = $pdo->prepare(
            "SELECT setting_value FROM {$prefix}plugin_settings WHERE plugin_id = ? AND setting_key = 'market_license_key'"
        );
        $keySt->execute([$id]);
        $licKey = $keySt->fetchColumn();
        if (!$licKey) { echo json_encode(['success' => false, 'valid' => false, 'message' => '등록된 라이선스 없음']); return; }

        // 도메인
        $domainSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'site_url'");
        $domainSt->execute();
        $siteUrl = $domainSt->fetchColumn() ?: ($_SERVER['HTTP_HOST'] ?? '');
        $domain  = strtolower(preg_replace('#^https?://#', '', rtrim($siteUrl, '/')));

        // market API validate
        $marketUrl = 'https://market.21ces.com/api/market/license/validate';
        $ch = curl_init($marketUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['license_key' => $licKey, 'slug' => $slug, 'domain' => $domain]),
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);

        $result = $resp ? json_decode($resp, true) : null;
        $isValid = ($result['ok'] ?? false) && ($result['valid'] ?? false);

        // 상태 업데이트
        $pdo->prepare(
            "INSERT INTO {$prefix}plugin_settings (plugin_id, setting_key, setting_value)
             VALUES (?, 'market_license_status', ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        )->execute([$id, $isValid ? 'valid' : 'invalid']);

        echo json_encode([
            'success' => true,
            'valid'   => $isValid,
            'message' => $result['msg'] ?? ($isValid ? '유효한 라이선스' : '유효하지 않은 라이선스'),
        ]);
        break;

    case 'purchase_item':
        // ── 유료 아이템 구매: PAY.JP 토큰 → market API → 라이선스 저장 ──
        $itemSlug   = trim($_POST['item_slug']   ?? '');
        $payjpToken = trim($_POST['payjp_token'] ?? '');
        $buyerEmail = trim($_POST['buyer_email'] ?? '');

        if (!$itemSlug || !$payjpToken) {
            echo json_encode(['success' => false, 'message' => 'item_slug, payjp_token 필수']);
            break;
        }

        // VosCMS 라이선스 키
        $cacheFile = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/storage/.license_cache';
        $vosKey    = '';
        if (file_exists($cacheFile)) {
            $cacheData = json_decode(file_get_contents($cacheFile), true);
            $vosKey    = $cacheData['license_key'] ?? '';
        }
        if (!$vosKey) {
            echo json_encode(['success' => false, 'message' => 'VosCMS 라이선스 키를 찾을 수 없습니다']);
            break;
        }

        // 사이트 도메인
        $siteUrl = '';
        try {
            $stSite  = $pdo->prepare("SELECT value FROM {$prefix}settings WHERE `key` = 'site_url' LIMIT 1");
            $stSite->execute();
            $siteUrl = $stSite->fetchColumn() ?: '';
        } catch (Throwable $e) {}
        $domain = strtolower(preg_replace('#^https?://#', '', rtrim($siteUrl, '/')));
        $domain = preg_replace('#^www\.#', '', $domain);
        $domain = explode('/', $domain)[0];
        $domain = explode('?', $domain)[0];

        if (!$domain) {
            echo json_encode(['success' => false, 'message' => '사이트 URL을 확인할 수 없습니다']);
            break;
        }

        // market API 호출
        $marketUrl = rtrim($_ENV['MARKET_API_URL'] ?? 'https://market.21ces.com/api/market', '/');
        $payload   = json_encode([
            'vos_key'     => $vosKey,
            'domain'      => $domain,
            'item_slug'   => $itemSlug,
            'payjp_token' => $payjpToken,
            'buyer_email' => $buyerEmail,
            'cms_version' => $_ENV['APP_VERSION'] ?? null,
        ]);

        $ch = curl_init($marketUrl . '/item/purchase');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp    = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = $resp ? json_decode($resp, true) : null;
        if (!$result || !($result['ok'] ?? false)) {
            $msg = $result['message'] ?? '결제 처리 중 오류가 발생했습니다';
            echo json_encode(['success' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
            break;
        }

        // 라이선스 정보 로컬 저장
        $nsId = $itemSlug;
        $upsert = "INSERT INTO {$prefix}plugin_settings (plugin_id, setting_key, setting_value)
                        VALUES (?, ?, ?)
                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        $pdo->prepare($upsert)->execute([$nsId, 'market_license_key',    $result['license_key']  ?? '']);
        $pdo->prepare($upsert)->execute([$nsId, 'market_product_key',    $result['product_key']  ?? '']);
        $pdo->prepare($upsert)->execute([$nsId, 'market_serial_key',     $result['serial_key']   ?? '']);
        $pdo->prepare($upsert)->execute([$nsId, 'market_order_number',   $result['order_number'] ?? '']);
        $pdo->prepare($upsert)->execute([$nsId, 'market_license_status', 'valid']);

        echo json_encode([
            'success'      => true,
            'license_key'  => $result['license_key']  ?? '',
            'serial_key'   => $result['serial_key']   ?? '',
            'order_number' => $result['order_number'] ?? '',
            'product_key'  => $result['product_key']  ?? '',
            'is_new'       => $result['is_new'] ?? true,
        ], JSON_UNESCAPED_UNICODE);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
