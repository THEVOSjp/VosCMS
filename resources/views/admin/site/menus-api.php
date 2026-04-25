<?php
/**
 * RezlyX Admin - 메뉴 관리 API
 * POST /admin/site/menus/api
 *
 * Rhymix 메뉴 시스템 참조
 * DB: rzx_sitemaps, rzx_menu_items
 */

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $input['action'];

try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

/**
 * shortcut(링크) 메뉴: 가리키는 페이지가 실제로 존재해야 한다.
 * - 외부 URL (http://, https://, //) → 통과
 * - 앵커 (#) → 통과
 * - 시스템 페이지 (config/system-pages.php) → 통과
 * - rzx_page_contents 매칭 → 통과
 * - 그 외 → false
 */
function mn_link_target_exists(\PDO $pdo, string $url): bool
{
    $url = trim($url);
    if ($url === '') return false;
    if (preg_match('#^(https?://|//|#)#i', $url)) return true;

    $slug = trim($url, '/');
    // 시스템 페이지
    $sysPages = file_exists(BASE_PATH . '/config/system-pages.php')
        ? include BASE_PATH . '/config/system-pages.php' : [];
    foreach ($sysPages as $sp) {
        if (($sp['slug'] ?? '') === $slug) return true;
    }
    // 동적 페이지
    $st = $pdo->prepare("SELECT 1 FROM rzx_page_contents WHERE page_slug = ? LIMIT 1");
    $st->execute([$slug]);
    return (bool)$st->fetchColumn();
}

try {
    switch ($action) {

        // ─── 사이트맵 CRUD ───
        case 'add_sitemap':
            $title = trim($input['title'] ?? '');
            if (!$title) { jsonError('Title is required'); }
            $slug = makeSlug($title) ?: 'sitemap-' . time();
            $maxOrder = $pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM rzx_sitemaps")->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO rzx_sitemaps (title, slug, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$title, $slug, $maxOrder + 1]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'rename_sitemap':
            $id = intval($input['id'] ?? 0);
            $title = trim($input['title'] ?? '');
            if (!$id || !$title) { jsonError('ID and title are required'); }
            $slug = makeSlug($title) ?: 'sitemap-' . $id;
            $pdo->prepare("UPDATE rzx_sitemaps SET title=?, slug=?, updated_at=NOW() WHERE id=?")->execute([$title, $slug, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_sitemap':
            $id = intval($input['id'] ?? 0);
            if (!$id) { jsonError('ID is required'); }
            $pdo->prepare("DELETE FROM rzx_menu_items WHERE sitemap_id=?")->execute([$id]);
            $pdo->prepare("DELETE FROM rzx_sitemaps WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        // ─── 메뉴 항목 CRUD ───
        case 'add_menu_item':
            $sitemapId = intval($input['sitemap_id'] ?? 0);
            $title = trim($input['title'] ?? '');
            if (!$sitemapId || !$title) { jsonError('Sitemap ID and title are required'); }

            $parentId   = !empty($input['parent_id']) ? intval($input['parent_id']) : null;
            $url        = trim(trim($input['url'] ?? ''), '/');
            $target     = in_array($input['target'] ?? '', ['_self', '_blank']) ? $input['target'] : '_self';
            $icon       = trim($input['icon'] ?? '');
            $cssClass   = trim($input['css_class'] ?? '');
            $desc       = trim($input['description'] ?? '');
            $menuType   = trim($input['menu_type'] ?? 'page');
            $openWindow = intval($input['open_window'] ?? (($target === '_blank') ? 1 : 0));
            $isShortcut = ($menuType === 'shortcut') ? 1 : 0;
            $expand     = intval($input['expand'] ?? 0);
            $groupSrls  = trim($input['group_srls'] ?? '');

            // shortcut(링크) 메뉴는 기존 페이지 연결만 — url 필수, 페이지 존재 검증
            if ($menuType === 'shortcut') {
                if (!$url) {
                    jsonError('링크 메뉴는 연결할 페이지(URL)를 반드시 지정해야 합니다.');
                }
                if (!mn_link_target_exists($pdo, $url)) {
                    jsonError("연결할 페이지를 찾을 수 없습니다: '/{$url}'. 페이지를 먼저 만든 후 다시 시도해주세요.");
                }
            }

            // URL 미입력 시 자동 생성
            if (!$url) {
                $url = makeSlug($title) ?: 'menu-' . time();
            }

            // 정렬 순서
            $orderSql = "SELECT COALESCE(MAX(sort_order),0) FROM rzx_menu_items WHERE sitemap_id=? AND parent_id " . ($parentId ? "=?" : "IS NULL");
            $orderStmt = $pdo->prepare($orderSql);
            $orderParams = [$sitemapId];
            if ($parentId) $orderParams[] = $parentId;
            $orderStmt->execute($orderParams);
            $maxOrder = $orderStmt->fetchColumn();

            $stmt = $pdo->prepare(
                "INSERT INTO rzx_menu_items (sitemap_id, parent_id, title, url, target, icon, css_class, description, menu_type, sort_order, is_shortcut, open_window, `expand`, group_srls, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
            );
            $stmt->execute([$sitemapId, $parentId, $title, $url, $target, $icon, $cssClass, $desc, $menuType, $maxOrder + 1, $isShortcut, $openWindow, $expand, $groupSrls]);
            $newMenuId = $pdo->lastInsertId();

            // 시스템 페이지 번역 자동 등록
            // system-pages.php에 정의된 페이지면 site.pages.{key} 번역을 메뉴 번역으로 복사
            $_sysPages = file_exists(BASE_PATH . '/config/system-pages.php') ? include BASE_PATH . '/config/system-pages.php' : [];
            $_sysPageMatch = null;
            foreach ($_sysPages as $_sp) {
                if (($_sp['slug'] ?? '') === $url) { $_sysPageMatch = $_sp; break; }
            }
            if ($_sysPageMatch && !empty($_sysPageMatch['title'])) {
                // 번역 키에서 다국어 텍스트 가져와서 rzx_translations에 등록
                $_transKey = $_sysPageMatch['title']; // 예: site.pages.downloads
                $_locales = ['ko','en','ja','zh_CN','zh_TW','de','es','fr','id','mn','ru','tr','vi'];
                $_insStmt = $pdo->prepare("INSERT IGNORE INTO rzx_translations (lang_key, locale, content) VALUES (?, ?, ?)");
                foreach ($_locales as $_loc) {
                    $_translated = function_exists('__') ? __($_transKey, [], $_loc) : '';
                    // 번역이 키 자체를 반환하면 스킵
                    if ($_translated && $_translated !== $_transKey) {
                        $_insStmt->execute(["menu_item.{$newMenuId}.title", $_loc, $_translated]);
                    }
                }
            }

            // 페이지 자동 생성
            $defaultLocale = $config['locale'] ?? 'ko';
            if ($menuType === 'page') {
                // 문서 페이지
                $chk = $pdo->prepare("SELECT COUNT(*) FROM rzx_page_contents WHERE page_slug = ? AND locale = ?");
                $chk->execute([$url, $defaultLocale]);
                if ((int)$chk->fetchColumn() === 0) {
                    $pdo->prepare("INSERT INTO rzx_page_contents (page_slug, page_type, locale, title, content, is_system, is_active) VALUES (?, 'document', ?, ?, '', 0, 1)")
                        ->execute([$url, $defaultLocale, $title]);
                }
            } elseif ($menuType === 'widget') {
                // 위젯 페이지
                $chk = $pdo->prepare("SELECT COUNT(*) FROM rzx_page_contents WHERE page_slug = ? AND locale = ?");
                $chk->execute([$url, $defaultLocale]);
                if ((int)$chk->fetchColumn() === 0) {
                    $pdo->prepare("INSERT INTO rzx_page_contents (page_slug, page_type, locale, title, content, is_system, is_active) VALUES (?, 'widget', ?, ?, '', 0, 1)")
                        ->execute([$url, $defaultLocale, $title]);
                }
                // 위젯 목록 테이블에도 빈 항목
                $chk2 = $pdo->prepare("SELECT COUNT(*) FROM rzx_page_widgets WHERE page_slug = ?");
                $chk2->execute([$url]);
                if ((int)$chk2->fetchColumn() === 0) {
                    $pdo->prepare("INSERT INTO rzx_page_widgets (page_slug, widget_id, sort_order, config, is_active) VALUES (?, 0, 0, '{}', 1)")
                        ->execute([$url]);
                }
            } elseif ($menuType === 'external') {
                // 외부 페이지: URL 또는 파일 경로를 content에 저장
                $externalUrl = $url;
                $slug = makeSlug($title) ?: 'ext-' . time();
                $pdo->prepare("UPDATE rzx_menu_items SET url = ? WHERE id = ?")->execute([$slug, $newMenuId]);
                $pdo->prepare("INSERT INTO rzx_page_contents (page_slug, page_type, locale, title, content, is_system, is_active) VALUES (?, 'external', ?, ?, ?, 0, 1)")
                    ->execute([$slug, $defaultLocale, $title, $externalUrl]);
            } elseif ($menuType === 'board') {
                // 게시판 자동 생성
                $boardSlug = preg_replace('/[^a-z0-9_-]/', '', strtolower($url)) ?: makeSlug($title) ?: 'board-' . time();
                // slug 중복 체크
                $chk = $pdo->prepare("SELECT id FROM rzx_boards WHERE slug = ?");
                $chk->execute([$boardSlug]);
                if (!$chk->fetch()) {
                    // 게시판 생성
                    $pdo->prepare(
                        "INSERT INTO rzx_boards (slug, title, category, skin, per_page, list_columns, perm_list, perm_read, perm_write, perm_comment, perm_manage, is_active)
                         VALUES (?, ?, 'board', 'default', 20, ?, 'all', 'all', 'member', 'member', 'admin', 1)"
                    )->execute([$boardSlug, $title, json_encode(['no', 'title', 'nick_name', 'created_at', 'view_count'])]);
                }
                // 메뉴 URL을 게시판 slug로 설정
                $pdo->prepare("UPDATE rzx_menu_items SET url = ? WHERE id = ?")->execute([$boardSlug, $newMenuId]);
            }

            $boardCreated = ($menuType === 'board');
            echo json_encode(['success' => true, 'id' => $newMenuId, 'page_created' => in_array($menuType, ['page', 'widget']), 'board_created' => $boardCreated]);
            break;

        case 'update_menu_item':
            $id    = intval($input['id'] ?? 0);
            $title = trim($input['title'] ?? '');
            if (!$id || !$title) { jsonError('ID and title are required'); }

            $url        = trim(trim($input['url'] ?? ''), '/');
            $target     = in_array($input['target'] ?? '', ['_self', '_blank']) ? $input['target'] : '_self';
            $icon       = trim($input['icon'] ?? '');
            $cssClass   = trim($input['css_class'] ?? '');
            $desc       = trim($input['description'] ?? '');
            $openWindow = intval($input['open_window'] ?? 0);
            $expand     = intval($input['expand'] ?? 0);
            $groupSrls  = trim($input['group_srls'] ?? '');

            // shortcut 메뉴는 페이지 존재 검증
            $curStmt = $pdo->prepare("SELECT menu_type FROM rzx_menu_items WHERE id = ? LIMIT 1");
            $curStmt->execute([$id]);
            $curType = (string)($curStmt->fetchColumn() ?: '');
            if ($curType === 'shortcut') {
                if (!$url) {
                    jsonError('링크 메뉴는 연결할 페이지(URL)를 반드시 지정해야 합니다.');
                }
                if (!mn_link_target_exists($pdo, $url)) {
                    jsonError("연결할 페이지를 찾을 수 없습니다: '/{$url}'. 페이지를 먼저 만든 후 다시 시도해주세요.");
                }
            }

            $stmt = $pdo->prepare(
                "UPDATE rzx_menu_items SET title=?, url=?, target=?, icon=?, css_class=?, description=?, open_window=?, `expand`=?, group_srls=?, updated_at=NOW() WHERE id=?"
            );
            $stmt->execute([$title, $url, $target, $icon, $cssClass, $desc, $openWindow, $expand, $groupSrls, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'rename_menu_item':
            $id    = intval($input['id'] ?? 0);
            $title = trim($input['title'] ?? '');
            if (!$id || !$title) { jsonError('ID and title are required'); }
            $pdo->prepare("UPDATE rzx_menu_items SET title=?, updated_at=NOW() WHERE id=?")->execute([$title, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_menu_item':
            $id = intval($input['id'] ?? 0);
            if (!$id) { jsonError('ID is required'); }

            // 시스템 페이지 보호 — 메뉴 항목만 삭제, 페이지 콘텐츠는 보존
            $menuCheck = $pdo->prepare("SELECT url, menu_type FROM rzx_menu_items WHERE id = ?");
            $menuCheck->execute([$id]);
            $menuData = $menuCheck->fetch(PDO::FETCH_ASSOC);
            if ($menuData) {
                $menuSlug = $menuData['url'] ?? '';
                $isSystemPage = false;

                // DB is_system 체크
                if ($menuSlug) {
                    $sysChk = $pdo->prepare("SELECT is_system FROM rzx_page_contents WHERE page_slug = ? AND is_system = 1 LIMIT 1");
                    $sysChk->execute([$menuSlug]);
                    $isSystemPage = (bool)$sysChk->fetchColumn();
                }
                // config/system-pages.php 체크
                if (!$isSystemPage && $menuSlug) {
                    $sysPagesConf = file_exists(BASE_PATH . '/config/system-pages.php') ? include BASE_PATH . '/config/system-pages.php' : [];
                    foreach ($sysPagesConf as $spConf) {
                        if (($spConf['slug'] ?? '') === $menuSlug) { $isSystemPage = true; break; }
                    }
                }

                if ($isSystemPage) {
                    // 시스템 페이지: 메뉴 항목 + 번역만 삭제, 페이지 콘텐츠/위젯은 보존
                    $pdo->prepare("DELETE FROM rzx_translations WHERE lang_key LIKE ?")->execute(["menu_item.{$id}.%"]);
                    $pdo->prepare("DELETE FROM rzx_menu_items WHERE id = ?")->execute([$id]);
                    echo json_encode(['success' => true, 'message' => '메뉴에서 제거되었습니다. 시스템 페이지는 보존됩니다.']);
                    break;
                }
            }

            deleteMenuItemRecursive($pdo, $id);
            echo json_encode(['success' => true]);
            break;

        case 'toggle_home':
            $id = intval($input['id'] ?? 0);
            $sitemapId = intval($input['sitemap_id'] ?? 0);
            if (!$id || !$sitemapId) { jsonError('ID and sitemap_id are required'); }

            $stmt = $pdo->prepare("SELECT is_home FROM rzx_menu_items WHERE id=?");
            $stmt->execute([$id]);
            $isHome = (int)$stmt->fetchColumn();

            if ($isHome) {
                $pdo->prepare("UPDATE rzx_menu_items SET is_home=0, updated_at=NOW() WHERE id=?")->execute([$id]);
            } else {
                $pdo->prepare("UPDATE rzx_menu_items SET is_home=0, updated_at=NOW() WHERE sitemap_id=? AND is_home=1")->execute([$sitemapId]);
                $pdo->prepare("UPDATE rzx_menu_items SET is_home=1, updated_at=NOW() WHERE id=?")->execute([$id]);
            }
            echo json_encode(['success' => true, 'is_home' => !$isHome]);
            break;

        case 'move_menu_item':
            $id = intval($input['id'] ?? 0);
            $newParentId = isset($input['parent_id']) ? (intval($input['parent_id']) ?: null) : null;
            $newSitemapId = intval($input['sitemap_id'] ?? 0);
            $newOrder = intval($input['sort_order'] ?? 0);
            if (!$id) { jsonError('ID is required'); }

            $pdo->prepare("UPDATE rzx_menu_items SET parent_id=?, sitemap_id=?, sort_order=?, updated_at=NOW() WHERE id=?")
                ->execute([$newParentId, $newSitemapId ?: null, $newOrder, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'copy_menu_item':
            $id = intval($input['id'] ?? 0);
            $targetSitemapId = intval($input['target_sitemap_id'] ?? 0);
            $targetParentId = isset($input['target_parent_id']) ? (intval($input['target_parent_id']) ?: null) : null;
            if (!$id || !$targetSitemapId) { jsonError('ID and target_sitemap_id are required'); }

            $stmt = $pdo->prepare("SELECT * FROM rzx_menu_items WHERE id=?");
            $stmt->execute([$id]);
            $orig = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orig) { jsonError('Menu item not found'); }

            $orderSql = "SELECT COALESCE(MAX(sort_order),0) FROM rzx_menu_items WHERE sitemap_id=? AND parent_id " . ($targetParentId ? "=?" : "IS NULL");
            $oStmt = $pdo->prepare($orderSql);
            $oParams = [$targetSitemapId];
            if ($targetParentId) $oParams[] = $targetParentId;
            $oStmt->execute($oParams);
            $maxOrd = $oStmt->fetchColumn();

            $pdo->prepare(
                "INSERT INTO rzx_menu_items (sitemap_id, parent_id, title, url, target, icon, css_class, description, menu_type, sort_order, is_shortcut, open_window, `expand`, group_srls, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                $targetSitemapId, $targetParentId,
                $orig['title'] . ' (Copy)', $orig['url'], $orig['target'],
                $orig['icon'], $orig['css_class'], $orig['description'] ?? '',
                $orig['menu_type'] ?? 'page', $maxOrd + 1, $orig['is_shortcut'] ?? 0,
                $orig['open_window'] ?? 0, $orig['expand'] ?? 0, $orig['group_srls'] ?? '',
                $orig['is_active'] ?? 1
            ]);
            $newId = $pdo->lastInsertId();
            copyChildrenRecursive($pdo, $id, $newId, $targetSitemapId);
            echo json_encode(['success' => true, 'id' => $newId]);
            break;

        case 'reorder_menu_items':
            $sitemapId = intval($input['sitemap_id'] ?? 0);
            $parentId = isset($input['parent_id']) ? (intval($input['parent_id']) ?: null) : null;
            $order = $input['order'] ?? [];
            if (!$sitemapId || !is_array($order) || empty($order)) {
                jsonError('sitemap_id and order are required');
            }

            $stmt = $pdo->prepare(
                "UPDATE rzx_menu_items SET sort_order=?, parent_id=?, sitemap_id=?, updated_at=NOW() WHERE id=?"
            );
            foreach ($order as $idx => $itemId) {
                $itemId = intval($itemId);
                if ($itemId > 0) {
                    $stmt->execute([$idx + 1, $parentId, $sitemapId, $itemId]);
                }
            }
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
    }
} catch (PDOException $e) {
    error_log('[MenuAPI] DB Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

// ─── Helper ───
function jsonError(string $msg): void {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

function makeSlug(string $text): string {
    $slug = preg_replace('/[^a-z0-9\x{AC00}-\x{D7A3}]+/u', '-', mb_strtolower($text));
    return trim($slug, '-');
}

function copyChildrenRecursive(PDO $pdo, int $origParentId, int $newParentId, int $sitemapId): void {
    $stmt = $pdo->prepare("SELECT * FROM rzx_menu_items WHERE parent_id=? ORDER BY sort_order ASC");
    $stmt->execute([$origParentId]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($children as $idx => $child) {
        $pdo->prepare(
            "INSERT INTO rzx_menu_items (sitemap_id, parent_id, title, url, target, icon, css_class, description, menu_type, sort_order, is_shortcut, open_window, `expand`, group_srls, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $sitemapId, $newParentId,
            $child['title'], $child['url'], $child['target'],
            $child['icon'], $child['css_class'], $child['description'] ?? '',
            $child['menu_type'] ?? 'page', $idx + 1, $child['is_shortcut'] ?? 0,
            $child['open_window'] ?? 0, $child['expand'] ?? 0, $child['group_srls'] ?? '',
            $child['is_active'] ?? 1
        ]);
        $childNewId = (int)$pdo->lastInsertId();
        copyChildrenRecursive($pdo, (int)$child['id'], $childNewId, $sitemapId);
    }
}

function deleteMenuItemRecursive(PDO $pdo, int $id): void {
    // 하위 메뉴 재귀 삭제
    $stmt = $pdo->prepare("SELECT id FROM rzx_menu_items WHERE parent_id=?");
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $childId) {
        deleteMenuItemRecursive($pdo, (int)$childId);
    }
    // 연결된 콘텐츠 삭제
    $item = $pdo->prepare("SELECT url, menu_type FROM rzx_menu_items WHERE id=?");
    $item->execute([$id]);
    $menuItem = $item->fetch(PDO::FETCH_ASSOC);
    if ($menuItem) {
        $slug = $menuItem['url'] ?? '';
        $type = $menuItem['menu_type'] ?? '';

        // 시스템 페이지 보호 (삭제 안 함)
        $isSystem = false;
        if ($slug) {
            // DB is_system 플래그 체크
            $sysCheck = $pdo->prepare("SELECT is_system FROM rzx_page_contents WHERE page_slug = ? AND is_system = 1 LIMIT 1");
            $sysCheck->execute([$slug]);
            $isSystem = (bool)$sysCheck->fetchColumn();
            // config/system-pages.php에 정의된 slug도 보호
            if (!$isSystem) {
                $systemPages = file_exists(BASE_PATH . '/config/system-pages.php') ? include BASE_PATH . '/config/system-pages.php' : [];
                foreach ($systemPages as $sp) {
                    if (($sp['slug'] ?? '') === $slug) { $isSystem = true; break; }
                }
            }
        }

        if ($slug && !$isSystem) {
            if ($type === 'page' || $type === 'widget') {
                // 페이지 콘텐츠 + 위젯 모두 삭제
                $pdo->prepare("DELETE FROM rzx_page_contents WHERE page_slug = ?")->execute([$slug]);
                $pdo->prepare("DELETE FROM rzx_page_widgets WHERE page_slug = ?")->execute([$slug]);
            } elseif ($type === 'board') {
                // 게시판 삭제 (게시글, 댓글, 파일, 카테고리, 투표, 번역 포함)
                $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
                $boardStmt = $pdo->prepare("SELECT id FROM {$prefix}boards WHERE slug = ?");
                $boardStmt->execute([$slug]);
                $boardId = $boardStmt->fetchColumn();
                if ($boardId) {
                    $pdo->prepare("DELETE FROM {$prefix}board_votes WHERE target_type = 'post' AND target_id IN (SELECT id FROM {$prefix}board_posts WHERE board_id = ?)")->execute([$boardId]);
                    $pdo->prepare("DELETE FROM {$prefix}board_files WHERE board_id = ?")->execute([$boardId]);
                    $pdo->prepare("DELETE FROM {$prefix}board_comments WHERE board_id = ?")->execute([$boardId]);
                    $pdo->prepare("DELETE FROM {$prefix}board_posts WHERE board_id = ?")->execute([$boardId]);
                    $pdo->prepare("DELETE FROM {$prefix}board_categories WHERE board_id = ?")->execute([$boardId]);
                    $pdo->prepare("DELETE FROM {$prefix}board_extra_vars WHERE board_id = ?")->execute([$boardId]);
                    $pdo->prepare("DELETE FROM {$prefix}board_admins WHERE board_id = ?")->execute([$boardId]);
                    $pdo->prepare("DELETE FROM {$prefix}boards WHERE id = ?")->execute([$boardId]);
                }
            }
        }

        // 번역 데이터 삭제
        $pdo->prepare("DELETE FROM rzx_translations WHERE lang_key LIKE ?")->execute(["menu_item.{$id}.%"]);
    }
    $pdo->prepare("DELETE FROM rzx_menu_items WHERE id=?")->execute([$id]);
}
