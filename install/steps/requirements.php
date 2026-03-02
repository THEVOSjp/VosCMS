<?php
/**
 * Requirements Check Step
 */

$requirements = [
    'php' => [
        'label' => 'PHP 버전',
        'required' => '8.0.0',
        'current' => PHP_VERSION,
        'passed' => version_compare(PHP_VERSION, '8.0.0', '>='),
    ],
    'pdo' => [
        'label' => 'PDO 확장',
        'required' => '필수',
        'current' => extension_loaded('pdo') ? '설치됨' : '미설치',
        'passed' => extension_loaded('pdo'),
    ],
    'pdo_mysql' => [
        'label' => 'PDO MySQL',
        'required' => '필수',
        'current' => extension_loaded('pdo_mysql') ? '설치됨' : '미설치',
        'passed' => extension_loaded('pdo_mysql'),
    ],
    'mbstring' => [
        'label' => 'Mbstring 확장',
        'required' => '필수',
        'current' => extension_loaded('mbstring') ? '설치됨' : '미설치',
        'passed' => extension_loaded('mbstring'),
    ],
    'json' => [
        'label' => 'JSON 확장',
        'required' => '필수',
        'current' => extension_loaded('json') ? '설치됨' : '미설치',
        'passed' => extension_loaded('json'),
    ],
    'openssl' => [
        'label' => 'OpenSSL 확장',
        'required' => '필수',
        'current' => extension_loaded('openssl') ? '설치됨' : '미설치',
        'passed' => extension_loaded('openssl'),
    ],
    'curl' => [
        'label' => 'cURL 확장',
        'required' => '권장',
        'current' => extension_loaded('curl') ? '설치됨' : '미설치',
        'passed' => extension_loaded('curl'),
        'optional' => true,
    ],
    'gd' => [
        'label' => 'GD 확장',
        'required' => '권장',
        'current' => extension_loaded('gd') ? '설치됨' : '미설치',
        'passed' => extension_loaded('gd'),
        'optional' => true,
    ],
];

$directories = [
    BASE_PATH . '/storage/cache' => is_writable(BASE_PATH . '/storage/cache'),
    BASE_PATH . '/storage/logs' => is_writable(BASE_PATH . '/storage/logs'),
    BASE_PATH . '/storage/uploads' => is_writable(BASE_PATH . '/storage/uploads'),
    BASE_PATH . '/storage/sessions' => is_writable(BASE_PATH . '/storage/sessions'),
    BASE_PATH . '/public/assets' => is_writable(BASE_PATH . '/public/assets'),
];

$allPassed = true;
foreach ($requirements as $req) {
    if (!($req['optional'] ?? false) && !$req['passed']) {
        $allPassed = false;
        break;
    }
}
foreach ($directories as $writable) {
    if (!$writable) {
        $allPassed = false;
        break;
    }
}
?>

<div class="bg-white rounded-lg shadow-sm p-8">
    <h2 class="text-xl font-bold text-gray-900 mb-6">시스템 요구사항 확인</h2>

    <!-- PHP Extensions -->
    <div class="mb-8">
        <h3 class="font-semibold text-gray-900 mb-4">PHP 확장 모듈</h3>
        <div class="border rounded-lg overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">항목</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">요구사항</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">현재 상태</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-600">결과</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($requirements as $key => $req): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo $req['label']; ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?php echo $req['required']; ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?php echo $req['current']; ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($req['passed']): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    통과
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo ($req['optional'] ?? false) ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'; ?>">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    <?php echo ($req['optional'] ?? false) ? '경고' : '실패'; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Directory Permissions -->
    <div class="mb-8">
        <h3 class="font-semibold text-gray-900 mb-4">디렉토리 권한</h3>
        <div class="border rounded-lg overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">디렉토리</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-600">쓰기 권한</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($directories as $path => $writable): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900 font-mono">
                            <?php echo str_replace(BASE_PATH, '', $path); ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($writable): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    쓰기 가능
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    권한 필요
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!$allPassed): ?>
    <div class="p-4 bg-red-50 border border-red-200 rounded-lg mb-6">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span class="text-red-800 font-medium">일부 요구사항이 충족되지 않았습니다. 문제를 해결한 후 다시 시도해주세요.</span>
        </div>
    </div>
    <?php endif; ?>

    <div class="flex justify-between">
        <a href="?step=welcome" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            이전
        </a>
        <?php if ($allPassed): ?>
        <a href="?step=database" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
            다음
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <?php else: ?>
        <button onclick="location.reload()" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            다시 확인
        </button>
        <?php endif; ?>
    </div>
</div>
