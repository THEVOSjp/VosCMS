<?php
/**
 * Installation Complete Step
 */

$adminPath = $_SESSION['admin_path'] ?? 'admin';
$siteUrl = $_SESSION['site_url'] ?? '/';
?>

<div class="bg-white rounded-lg shadow-sm p-8 text-center">
    <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-6">
        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <h2 class="text-2xl font-bold text-gray-900 mb-2">설치가 완료되었습니다!</h2>
    <p class="text-gray-600 mb-8">RezlyX가 성공적으로 설치되었습니다.</p>

    <div class="bg-gray-50 rounded-lg p-6 mb-8 text-left max-w-md mx-auto">
        <h3 class="font-semibold text-gray-900 mb-4">설치 정보</h3>

        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">사이트 URL:</span>
                <a href="<?php echo htmlspecialchars($siteUrl); ?>" class="text-blue-600 hover:underline" target="_blank">
                    <?php echo htmlspecialchars($siteUrl); ?>
                </a>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">관리자 페이지:</span>
                <a href="<?php echo htmlspecialchars($siteUrl . '/' . $adminPath); ?>" class="text-blue-600 hover:underline" target="_blank">
                    <?php echo htmlspecialchars($siteUrl . '/' . $adminPath); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-8 text-left">
        <div class="flex">
            <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div>
                <h4 class="font-semibold text-yellow-800 mb-1">보안 권장사항</h4>
                <ul class="text-sm text-yellow-700 space-y-1">
                    <li>• <code class="bg-yellow-100 px-1 rounded">/install</code> 폴더를 삭제하거나 접근을 차단하세요.</li>
                    <li>• <code class="bg-yellow-100 px-1 rounded">.env</code> 파일의 권한을 제한하세요.</li>
                    <li>• 정기적으로 백업을 수행하세요.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="flex justify-center space-x-4">
        <a href="<?php echo htmlspecialchars($siteUrl); ?>"
           class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            사이트 방문
        </a>
        <a href="<?php echo htmlspecialchars($siteUrl . '/' . $adminPath); ?>"
           class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            관리자 페이지로
        </a>
    </div>
</div>
