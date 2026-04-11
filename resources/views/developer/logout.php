<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
unset($_SESSION['developer_id'], $_SESSION['developer_name'], $_SESSION['developer_email'], $_SESSION['developer_type']);
header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/developer/login');
exit;
