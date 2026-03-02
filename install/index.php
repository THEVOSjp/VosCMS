<?php
/**
 * RezlyX Installation Wizard
 *
 * @package RezlyX\Install
 */

define('BASE_PATH', dirname(__DIR__));
define('INSTALL_PATH', __DIR__);

// Check if already installed
if (file_exists(BASE_PATH . '/install/installed.lock')) {
    header('Location: /');
    exit;
}

// Current step
$step = $_GET['step'] ?? 'welcome';
$validSteps = ['welcome', 'requirements', 'database', 'admin', 'complete'];

if (!in_array($step, $validSteps)) {
    $step = 'welcome';
}

// Session for installation data
session_start();

// Process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once INSTALL_PATH . '/steps/process.php';
    exit;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RezlyX 설치</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css');
        body { font-family: 'Pretendard', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-4xl mx-auto px-4 py-6">
                <h1 class="text-2xl font-bold text-blue-600">RezlyX</h1>
                <p class="text-gray-500 text-sm">설치 마법사</p>
            </div>
        </header>

        <!-- Progress Steps -->
        <div class="bg-white border-b">
            <div class="max-w-4xl mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <?php
                    $stepLabels = [
                        'welcome' => '시작',
                        'requirements' => '환경 확인',
                        'database' => '데이터베이스',
                        'admin' => '관리자 설정',
                        'complete' => '완료'
                    ];
                    $currentIndex = array_search($step, $validSteps);
                    foreach ($stepLabels as $key => $label):
                        $index = array_search($key, $validSteps);
                        $isActive = $key === $step;
                        $isComplete = $index < $currentIndex;
                    ?>
                    <div class="flex items-center <?php echo $index < count($stepLabels) - 1 ? 'flex-1' : ''; ?>">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full <?php
                            echo $isActive ? 'bg-blue-600 text-white' :
                                ($isComplete ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600');
                        ?>">
                            <?php if ($isComplete): ?>
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            <?php else: ?>
                                <?php echo $index + 1; ?>
                            <?php endif; ?>
                        </div>
                        <span class="ml-2 text-sm <?php echo $isActive ? 'font-semibold text-gray-900' : 'text-gray-500'; ?>">
                            <?php echo $label; ?>
                        </span>
                        <?php if ($index < count($stepLabels) - 1): ?>
                        <div class="flex-1 h-0.5 mx-4 <?php echo $isComplete ? 'bg-green-500' : 'bg-gray-200'; ?>"></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Content -->
        <main class="flex-1 py-8">
            <div class="max-w-4xl mx-auto px-4">
                <?php include INSTALL_PATH . "/steps/{$step}.php"; ?>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t py-4">
            <div class="max-w-4xl mx-auto px-4 text-center text-sm text-gray-500">
                &copy; <?php echo date('Y'); ?> RezlyX. All rights reserved.
            </div>
        </footer>
    </div>
</body>
</html>
