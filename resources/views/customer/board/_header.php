<?php
/**
 * RezlyX 게시판 - 헤더 (기본 레이아웃 사용)
 */
$pageTitle = $pageTitle ?? (($board['title'] ?? 'Board') . ' - ' . ($config['app_name'] ?? 'RezlyX'));
if (!empty($board['seo_keywords'])) $metaKeywords = $board['seo_keywords'];
if (!empty($board['seo_description'])) $metaDescription = $board['seo_description'];
if (($board['robots_tag'] ?? 'all') === 'noindex') $metaRobots = 'noindex,nofollow';

include BASE_PATH . '/resources/views/layouts/base-header.php';
