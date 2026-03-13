<?php
/**
 * RezlyX Admin - 403 Forbidden
 */
$siteName = $siteSettings['site_name'] ?? 'RezlyX';
$adminPath = $config['admin_path'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="ko" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - <?= htmlspecialchars($siteName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full bg-zinc-50 dark:bg-zinc-900 flex items-center justify-center">
    <div class="text-center px-6">
        <p class="text-6xl font-bold text-red-500 mb-4">403</p>
        <h1 class="text-2xl font-semibold text-zinc-800 dark:text-white mb-2">접근 권한이 없습니다</h1>
        <p class="text-zinc-500 dark:text-zinc-400 mb-6">이 페이지에 접근할 수 있는 권한이 부여되지 않았습니다.</p>
        <a href="<?= htmlspecialchars($basePath . '/' . $adminPath) ?>"
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/>
            </svg>
            대시보드로 이동
        </a>
    </div>
</body>
</html>
