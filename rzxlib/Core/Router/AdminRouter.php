<?php
/**
 * VosCMS Admin Router
 *
 * index.php에서 분리된 관리자 라우팅 로직.
 * 필요 변수: $path, $config, $pdo, $basePath, $siteSettings, $pluginManager, $licenseInfo, $updateInfo
 * 설정 변수: $adminRoute (자동 계산)
 */

$adminRoute = substr($path, strlen($config['admin_path']));
$adminRoute = trim($adminRoute, '/') ?: 'dashboard';

    // AdminAuth 초기화
    require_once BASE_PATH . '/rzxlib/Core/Auth/AdminAuth.php';
    \RzxLib\Core\Auth\AdminAuth::init($pdo);

    // 로그인 페이지는 인증 불필요
    if ($adminRoute === 'login') {
        include BASE_PATH . '/resources/views/admin/login.php';
        exit;
    }

    // 관리자 로그아웃
    if ($adminRoute === 'logout') {
        \RzxLib\Core\Auth\AdminAuth::logout();
        header('Location: ' . $basePath . '/' . $config['admin_path'] . '/login');
        exit;
    }

    // 키오스크 실행 (인증 불필요)
    // 키오스크 실행 (로그인 불필요 — 플러그인 라우트 우선)
    if (str_starts_with($adminRoute, 'kiosk/run') && isset($pluginManager)) {
        foreach ($pluginManager->getRoutes() as $_pr) {
            if ($_pr['type'] === 'admin' && $adminRoute === $_pr['path'] && file_exists($_pr['view_path'])) {
                include $_pr['view_path'];
                exit;
            }
        }
    }

    // 관리자 로그인 확인
    if (!\RzxLib\Core\Auth\AdminAuth::check()) {
        header('Location: ' . $basePath . '/' . $config['admin_path'] . '/login');
        exit;
    }

    // 권한 확인
    $requiredPerm = \RzxLib\Core\Auth\AdminAuth::getRequiredPermission($adminRoute);
    if ($requiredPerm && !\RzxLib\Core\Auth\AdminAuth::can($requiredPerm)) {
        http_response_code(403);
        include BASE_PATH . '/resources/views/admin/403.php';
        exit;
    }

    // ─── 라이선스 체크 ───
    $licenseInfo = null;
    try {
        require_once BASE_PATH . '/rzxlib/Core/License/LicenseClient.php';
        require_once BASE_PATH . '/rzxlib/Core/License/LicenseStatus.php';
        $licenseClient = new \RzxLib\Core\License\LicenseClient();

        // 라이선스 키 미등록 사이트 → 자동 Free 라이선스 등록
        if (empty($_ENV['LICENSE_KEY'])) {
            try {
                $result = $licenseClient->register(
                    $_ENV['APP_URL'] ?? $_SERVER['HTTP_HOST'] ?? 'unknown',
                    $_ENV['APP_VERSION'] ?? '2.1.0',
                    PHP_VERSION
                );
                if (!empty($result['success']) && !empty($result['key'])) {
                    // .env에 라이선스 정보 추가
                    $envFile = BASE_PATH . '/.env';
                    if (file_exists($envFile) && is_writable($envFile)) {
                        $envContent = file_get_contents($envFile);
                        if (!str_contains($envContent, 'LICENSE_KEY=')) {
                            $domain = $licenseClient::normalizeDomain($_ENV['APP_URL'] ?? $_SERVER['HTTP_HOST'] ?? '');
                            $envContent .= "\n\nLICENSE_KEY={$result['key']}\nLICENSE_DOMAIN={$domain}\nLICENSE_REGISTERED_AT=" . date('c') . "\nLICENSE_SERVER=" . ($_ENV['LICENSE_SERVER'] ?? 'https://vos.21ces.com/api') . "\n";
                            file_put_contents($envFile, $envContent);
                            $_ENV['LICENSE_KEY'] = $result['key'];
                        }
                    }
                }
            } catch (\Throwable $e) {
                if ($config['debug']) error_log('Auto license register error: ' . $e->getMessage());
            }
        }

        $licenseStatus = $licenseClient->check();
        $licenseInfo = $licenseStatus->toArray();
    } catch (\Throwable $e) {
        if ($config['debug']) error_log('License check error: ' . $e->getMessage());
    }

    // 업데이트 확인 (캐시 기반, 1시간 TTL)
    $updateInfo = null;
    try {
        require_once BASE_PATH . '/rzxlib/Core/Updater/UpdateChecker.php';
        $updateInfo = \RzxLib\Core\Updater\UpdateChecker::check($pdo, BASE_PATH);
    } catch (\Throwable $e) {
        // 업데이트 확인 실패 시 무시
    }

    // 파일 기반 위젯 DB 동기화 (1시간에 1회)
    $syncFlag = BASE_PATH . '/storage/.widget_sync';
    if (!file_exists($syncFlag) || filemtime($syncFlag) < time() - 3600) {
        try {
            $widgetLoader = new \RzxLib\Core\Modules\WidgetLoader($pdo, BASE_PATH . '/widgets');
            $widgetLoader->syncToDatabase();
            @file_put_contents($syncFlag, date('c'));
        } catch (\Throwable $e) {
            error_log("Widget sync error: " . $e->getMessage());
        }
    }

    // 서비스 주문 상세 (vos-hosting 플러그인 — 정규식 라우트만 코어가 처리)
    //   service-orders 단순 라우트는 PluginManager (plugin.json routes.admin) 가 자동 매칭
    // 신청서 작성 (관리자 대리 등록) — service-orders/{order_number} 보다 먼저 매칭
    if ($adminRoute === 'service-orders/new') {
        $_pf = BASE_PATH . '/plugins/vos-hosting/views/admin/service-orders/new.php';
        if (file_exists($_pf)) { include $_pf; }
        else { http_response_code(404); include BASE_PATH . '/resources/views/admin/dashboard.php'; }
    } elseif (preg_match('#^service-orders/([a-zA-Z0-9_-]+)$#', $adminRoute, $m)) {
        $adminOrderNumber = $m[1];
        $_pf = BASE_PATH . '/plugins/vos-hosting/views/admin/service-orders/detail.php';
        if (file_exists($_pf)) { include $_pf; }
        else { http_response_code(404); include BASE_PATH . '/resources/views/admin/dashboard.php'; }
    // 제작 프로젝트 상세 (vos-hosting 플러그인 — 정규식 라우트)
    } elseif (preg_match('#^custom-projects/(\d+)$#', $adminRoute, $m)) {
        $adminCustomProjectId = (int)$m[1];
        $_pf = BASE_PATH . '/plugins/vos-hosting/views/admin/custom-projects/detail.php';
        if (file_exists($_pf)) { include $_pf; }
        else { http_response_code(404); include BASE_PATH . '/resources/views/admin/dashboard.php'; }
    // 서비스 설정 (정규식 라우트 — 플러그인에서 직접 처리)
    } elseif (preg_match('#^services/settings(?:/(\w+))?$#', $adminRoute, $m)) {
        $settingsTab = $m[1] ?? 'general';
        if (!in_array($settingsTab, ['general', 'categories', 'holidays'])) $settingsTab = 'general';
        $_sf = BASE_PATH . '/plugins/vos-salon/views/services/settings.php';
        if (file_exists($_sf)) { include $_sf; } else { include BASE_PATH . '/resources/views/admin/dashboard.php'; }
    // 근태 개인 리포트 (정규식 라우트)
    } elseif (preg_match('#^staff/attendance/report/personal(?:/(\d+))?$#', $adminRoute, $m)) {
        $reportStaffId = $m[1] ?? null;
        $_prFile = BASE_PATH . '/plugins/vos-attendance/views/attendance-report-personal.php';
        if (file_exists($_prFile)) { include $_prFile; } else { include BASE_PATH . '/resources/views/admin/dashboard.php'; }
    // 관리자 권한 관리 (코어)
    } elseif ($adminRoute === 'staff/admins') {
        include BASE_PATH . '/resources/views/admin/staff/admins.php';
    // 커뮤니티 관리 (vos-community 플러그인 — PluginManager 자동 매칭)
    // 업소 관리 (vos-shop 플러그인 — 존재 시만)
    } elseif (file_exists(BASE_PATH . '/plugins/vos-shop/plugin.json') && $adminRoute === 'shops/consultations') {
        include BASE_PATH . '/plugins/vos-shop/views/admin/consultations.php';
    } elseif (file_exists(BASE_PATH . '/plugins/vos-shop/plugin.json') && ($adminRoute === 'shops' || str_starts_with($adminRoute, 'shops/'))) {
        include BASE_PATH . '/plugins/vos-shop/views/admin/shops.php';
    // 페이지 관리 - 데이터 관리 가이드 편집
    } elseif ($adminRoute === 'site/pages/compliance') {
        include BASE_PATH . '/resources/views/admin/site/pages-compliance.php';
    // 페이지 관리 - 페이지 환경 설정
    } elseif ($adminRoute === 'site/pages/settings') {
        include BASE_PATH . '/resources/views/admin/site/pages-settings.php';
    // 페이지 관리 - 페이지 콘텐츠 편집
    } elseif ($adminRoute === 'site/pages/edit-content') {
        include BASE_PATH . '/resources/views/admin/site/pages-edit-content.php';
    // 페이지 관리 - 범용 문서 페이지 에디터
    } elseif ($adminRoute === 'site/pages/edit') {
        include BASE_PATH . '/resources/views/admin/site/pages-document.php';
    // 페이지 관리 - 위젯 빌더 (홈 페이지)
    } elseif ($adminRoute === 'site/pages/widget-builder') {
        include BASE_PATH . '/resources/views/admin/site/pages-widget-builder.php';
    // 위젯 관리
    // 예약/서비스/스태프 관리: vos-salon 플러그인으로 이전됨
    // 예약 API 라우트 (POST, 정규식 라우트 — 플러그인 라우트 시스템으로 처리 불가하므로 직접 참조)
    } elseif ($adminRoute === 'reservations' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiAction = 'store'; $apiId = null;
        include BASE_PATH . '/plugins/vos-salon/views/reservations/_api.php';
    } elseif (preg_match('#^reservations/(customer-services|search-customers|available-staff)$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $apiAction = $m[1]; $apiId = null;
        include BASE_PATH . '/plugins/vos-salon/views/reservations/_api.php';
    } elseif (preg_match('#^reservations/(add-service|append-service|assign-staff|remove-service|save-memo)$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiAction = $m[1]; $apiId = null;
        include BASE_PATH . '/plugins/vos-salon/views/reservations/_api.php';
    } elseif (preg_match('#^reservations/([\w-]+)/update-contact$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $resId = $m[1];
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($phone) {
            try {
                $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
                $pdo->prepare("UPDATE {$prefix}reservations SET customer_phone = ?, customer_email = ? WHERE id = ?")
                    ->execute([$phone, $email ?: null, $resId]);
                echo json_encode(['success' => true]);
            } catch (\Throwable $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Phone required']);
        }
        exit;
    } elseif (preg_match('#^reservations/([\w-]+)/update-datetime$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $resId = $m[1];
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? '';
        if ($date && $time) {
            try {
                $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
                // 기존 예약의 duration 계산
                $rStmt = $pdo->prepare("SELECT start_time, end_time FROM {$prefix}reservations WHERE id = ?");
                $rStmt->execute([$resId]);
                $rData = $rStmt->fetch(PDO::FETCH_ASSOC);
                if ($rData) {
                    $oldStart = new DateTime('2000-01-01 ' . $rData['start_time']);
                    $oldEnd = new DateTime('2000-01-01 ' . $rData['end_time']);
                    $duration = ($oldEnd->getTimestamp() - $oldStart->getTimestamp()) / 60;
                    $newStart = new DateTime("$date $time");
                    $newEnd = clone $newStart;
                    $newEnd->modify("+{$duration} minutes");
                    $pdo->prepare("UPDATE {$prefix}reservations SET reservation_date = ?, start_time = ?, end_time = ? WHERE id = ?")
                        ->execute([$date, $time . ':00', $newEnd->format('H:i:s'), $resId]);
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Reservation not found']);
                }
            } catch (\Throwable $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Date and time required']);
        }
        exit;
    } elseif (preg_match('#^reservations/([\w-]+)/(confirm|cancel|complete|no-show|start-service|payment)$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiId = $m[1]; $apiAction = $m[2];
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif (preg_match('#^reservations/([\w-]+)$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiId = $m[1]; $apiAction = 'update';
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    // 예약 관리 — GET 페이지
    // 예약/번들: vos-salon 플러그인으로 이전
    } elseif (preg_match('#^reservations/([\w-]+)$#', $adminRoute, $m)) {
        // 플러그인 라우트 우선 (POS 등), 없으면 예약 상세
        $_pluginHit = false;
        if (isset($pluginManager)) {
            foreach ($pluginManager->getRoutes() as $_pr) {
                if ($_pr['type'] === 'admin' && $adminRoute === $_pr['path'] && file_exists($_pr['view_path'])) {
                    include $_pr['view_path'];
                    $_pluginHit = true;
                    break;
                }
            }
        }
        if (!$_pluginHit) {
            $reservationId = $m[1];
            $_sf = BASE_PATH . '/plugins/vos-salon/views/reservations/show.php';
            if (file_exists($_sf)) { include $_sf; } else { include BASE_PATH . '/resources/views/admin/dashboard.php'; }
        }
    } elseif (preg_match('#^bundles/([\w-]+)$#', $adminRoute, $m)) {
        $bundleId = $m[1];
        $_sf = BASE_PATH . '/plugins/vos-salon/views/bundles/edit.php';
        if (file_exists($_sf)) { include $_sf; } else { include BASE_PATH . '/resources/views/admin/dashboard.php'; }
    // 게시판 관리
    } elseif ($adminRoute === 'site/boards') {
        include BASE_PATH . '/resources/views/admin/site/boards.php';
    } elseif ($adminRoute === 'site/boards/create') {
        include BASE_PATH . '/resources/views/admin/site/boards-create.php';
    } elseif ($adminRoute === 'site/boards/edit') {
        include BASE_PATH . '/resources/views/admin/site/boards-edit.php';
    } elseif ($adminRoute === 'site/boards/api') {
        include BASE_PATH . '/resources/views/admin/site/boards-api.php';
    } elseif ($adminRoute === 'site/boards/trash') {
        include BASE_PATH . '/resources/views/admin/site/boards-trash.php';
    // 위젯 관리
    } elseif ($adminRoute === 'site/widgets') {
        include BASE_PATH . '/resources/views/admin/site/widgets.php';
    } elseif ($adminRoute === 'site/widgets/create') {
        include BASE_PATH . '/resources/views/admin/site/widgets-create.php';
    } elseif ($adminRoute === 'site/widgets/marketplace') {
        include BASE_PATH . '/resources/views/admin/site/widgets-marketplace.php';
    } elseif ($adminRoute === 'plugins') {
        include BASE_PATH . '/resources/views/admin/plugins.php';
    } elseif ($adminRoute === 'review-queue') {
        include BASE_PATH . '/resources/views/admin/review-queue.php';
    // contact-messages 라우트는 vos-hosting plugin.json routes.admin 가 PluginManager 통해 자동 매칭
    } elseif ($adminRoute === 'plugins/api') {
        $__noLayout = true;
        include BASE_PATH . '/resources/views/admin/plugins-api.php';
    } else {
        // 플러그인 라우트 매칭
        $_pluginRouteMatch = false;
        if (isset($pluginManager)) {
            foreach ($pluginManager->getRoutes() as $_pr) {
                if ($_pr['type'] === 'admin' && $adminRoute === $_pr['path']) {
                    if (file_exists($_pr['view_path'])) {
                        include $_pr['view_path'];
                        $_pluginRouteMatch = true;
                    }
                    break;
                }
            }
        }
        if (!$_pluginRouteMatch) {
            $adminView = BASE_PATH . '/resources/views/admin/' . $adminRoute . '.php';
            if (file_exists($adminView)) {
                include $adminView;
            } else {
                include BASE_PATH . '/resources/views/admin/dashboard.php';
            }
        }
    }
