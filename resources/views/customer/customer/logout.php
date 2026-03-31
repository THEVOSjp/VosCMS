<?php
/**
 * RezlyX Customer Logout
 */

// 인증 헬퍼 로드
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

// 로그아웃 처리
Auth::logout();

// 홈으로 리다이렉트
$baseUrl = $config['app_url'] ?? '';
header('Location: ' . $baseUrl . '/');
exit;
