<?php
/**
 * RezlyX Admin - 위젯 빌더 AJAX 핸들러
 * pages-widget-builder.php에서 분리
 *
 * 필요 변수: $pdo, $baseUrl, $currentLocale
 */

// ======= AJAX JSON 요청 처리 =======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    // 레이아웃 저장
    if ($action === 'save_layout') {
        $items = $input['items'] ?? [];
        $pdo->prepare("DELETE FROM rzx_page_widgets WHERE page_slug = ?")->execute([$pageSlug]);
        $stmt = $pdo->prepare("INSERT INTO rzx_page_widgets (page_slug, widget_id, sort_order, config, is_active) VALUES (?, ?, ?, ?, 1)");
        foreach ($items as $i => $item) {
            $stmt->execute([
                $pageSlug,
                (int)$item['widget_id'],
                $i,
                json_encode($item['config'] ?? new \stdClass())
            ]);
        }
        echo json_encode(['success' => true, 'message' => __('site.widget_builder.saved')]);
        exit;
    }

    // 위젯 미리보기 렌더링
    if ($action === 'preview_widget') {
        require_once BASE_PATH . '/rzxlib/Core/Modules/WidgetLoader.php';
        require_once BASE_PATH . '/rzxlib/Core/Modules/WidgetRenderer.php';
        $renderer = new \RzxLib\Core\Modules\WidgetRenderer($pdo, $pageSlug, $currentLocale, $baseUrl);
        $widgetId = (int)($input['widget_id'] ?? 0);
        $widgetConfig = $input['config'] ?? [];

        $stmt = $pdo->prepare("SELECT * FROM rzx_widgets WHERE id = ?");
        $stmt->execute([$widgetId]);
        $widget = $stmt->fetch(PDO::FETCH_ASSOC);

        $html = '';
        if ($widget) {
            $fakeData = [
                'id' => $widget['id'],
                'widget_slug' => $widget['slug'],
                'widget_name' => $widget['name'],
                'template' => $widget['template'] ?? '',
                'css' => $widget['css'] ?? '',
                'js' => $widget['js'] ?? '',
                'widget_type' => $widget['type'],
                'default_config' => $widget['default_config'] ?? '{}',
                'config' => json_encode($widgetConfig),
                'config_schema' => $widget['config_schema'] ?? '{}',
            ];
            $GLOBALS['_rzx_widget_preview'] = true;
            $html = $renderer->render($fakeData);
            unset($GLOBALS['_rzx_widget_preview']);
        }
        echo json_encode(['success' => true, 'html' => $html]);
        exit;
    }

    echo json_encode(['success' => false]);
    exit;
}

// ======= 이미지 업로드 (multipart/form-data) =======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'upload_widget_image') {
    header('Content-Type: application/json');
    $uploadDir = BASE_PATH . '/storage/uploads/widgets/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
        $mime = mime_content_type($_FILES['image']['tmp_name']);
        if (!in_array($mime, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type']);
            exit;
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'widget_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
            $url = $baseUrl . '/storage/uploads/widgets/' . $filename;
            echo json_encode(['success' => true, 'url' => $url]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Upload failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No file']);
    }
    exit;
}

// ======= 비디오 업로드 (multipart/form-data) =======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'upload_widget_video') {
    header('Content-Type: application/json');
    $uploadDir = BASE_PATH . '/storage/uploads/widgets/videos/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (!empty($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['video/mp4', 'video/webm', 'video/ogg'];
        $mime = mime_content_type($_FILES['video']['tmp_name']);
        if (!in_array($mime, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid video type. Allowed: mp4, webm, ogg']);
            exit;
        }
        if ($_FILES['video']['size'] > 50 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File too large (max 50MB)']);
            exit;
        }
        $ext = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION) ?: 'mp4';
        $filename = 'video_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES['video']['tmp_name'], $uploadDir . $filename)) {
            $url = $baseUrl . '/storage/uploads/widgets/videos/' . $filename;
            echo json_encode(['success' => true, 'url' => $url]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Upload failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No video file']);
    }
    exit;
}
