<?php
/**
 * RezlyX Admin - 카드리더 키오스크 모드
 * 전체화면 전용 페이지 - 카드 태그로 출퇴근 자동 기록
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$appName = $config['app_name'] ?? 'RezlyX';
$attendanceUrl = $adminUrl . '/staff/attendance';
?>
<!DOCTYPE html>
<html lang="<?= $config['locale'] ?? 'ko' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('staff.attendance.kiosk_title') ?> - <?= htmlspecialchars($appName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; cursor: none; }
        @keyframes pulse-ring { 0% { transform: scale(0.95); opacity: 1; } 100% { transform: scale(1.3); opacity: 0; } }
        @keyframes success-flash { 0% { background-color: transparent; } 50% { background-color: rgba(34, 197, 94, 0.1); } 100% { background-color: transparent; } }
        @keyframes error-flash { 0% { background-color: transparent; } 50% { background-color: rgba(239, 68, 68, 0.1); } 100% { background-color: transparent; } }
        .success-flash { animation: success-flash 1s ease-out; }
        .error-flash { animation: error-flash 1s ease-out; }
        .pulse-ring { animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite; }
    </style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-900 min-h-screen flex flex-col items-center justify-center select-none" onclick="focusInput()">

    <!-- 상단: 시간 + 날짜 -->
    <div class="absolute top-8 text-center">
        <p id="currentTime" class="text-6xl font-bold text-white font-mono tracking-wider">--:--:--</p>
        <p id="currentDate" class="text-lg text-zinc-400 mt-2"></p>
    </div>

    <!-- 중앙: 카드 태그 안내 -->
    <div id="kioskMain" class="text-center">
        <!-- 대기 상태 -->
        <div id="stateIdle" class="flex flex-col items-center">
            <div class="relative">
                <div class="w-32 h-32 rounded-full bg-purple-600/20 flex items-center justify-center">
                    <svg class="w-16 h-16 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div class="absolute inset-0 w-32 h-32 rounded-full border-2 border-purple-400/50 pulse-ring"></div>
            </div>
            <p class="text-2xl text-white mt-8 font-medium"><?= __('staff.attendance.kiosk_scan') ?></p>
            <p class="text-zinc-500 mt-2 text-sm"><?= __('staff.attendance.kiosk_scan_desc') ?></p>
        </div>

        <!-- 성공 상태 -->
        <div id="stateSuccess" class="hidden flex flex-col items-center">
            <div class="w-32 h-32 rounded-full bg-green-600/20 flex items-center justify-center">
                <svg class="w-16 h-16 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <p id="successName" class="text-3xl text-white mt-6 font-bold"></p>
            <p id="successMsg" class="text-xl text-green-400 mt-2"></p>
            <p id="successTime" class="text-zinc-400 mt-1 font-mono"></p>
        </div>

        <!-- 에러 상태 -->
        <div id="stateError" class="hidden flex flex-col items-center">
            <div class="w-32 h-32 rounded-full bg-red-600/20 flex items-center justify-center">
                <svg class="w-16 h-16 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <p id="errorMsg" class="text-xl text-red-400 mt-6"></p>
        </div>
    </div>

    <!-- 숨겨진 카드 입력 필드 -->
    <input type="text" id="cardInput" class="opacity-0 absolute -top-[9999px]" autocomplete="off" autofocus>

    <!-- 하단: 관리자 링크 -->
    <div class="absolute bottom-6 flex items-center gap-4">
        <a href="<?= $attendanceUrl ?>" class="text-xs text-zinc-600 hover:text-zinc-400 cursor-pointer"><?= __('staff.attendance.kiosk_exit') ?></a>
        <span class="text-zinc-700">|</span>
        <span class="text-xs text-zinc-700"><?= htmlspecialchars($appName) ?></span>
    </div>

    <?php include __DIR__ . '/attendance-kiosk-js.php'; ?>
</body>
</html>
