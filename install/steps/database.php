<?php
/**
 * Database Configuration Step
 */

$error = $_SESSION['install_error'] ?? null;
$data = $_SESSION['install_db'] ?? [];
unset($_SESSION['install_error']);
?>

<div class="bg-white rounded-lg shadow-sm p-8">
    <h2 class="text-xl font-bold text-gray-900 mb-6">데이터베이스 설정</h2>

    <?php if ($error): ?>
    <div class="p-4 bg-red-50 border border-red-200 rounded-lg mb-6">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span class="text-red-800"><?php echo htmlspecialchars($error); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" action="?step=database" class="space-y-6">
        <input type="hidden" name="action" value="database">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="db_host" class="block text-sm font-medium text-gray-700 mb-1">
                    데이터베이스 호스트 <span class="text-red-500">*</span>
                </label>
                <input type="text" id="db_host" name="db_host"
                       value="<?php echo htmlspecialchars($data['db_host'] ?? '127.0.0.1'); ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       required>
            </div>

            <div>
                <label for="db_port" class="block text-sm font-medium text-gray-700 mb-1">
                    포트
                </label>
                <input type="text" id="db_port" name="db_port"
                       value="<?php echo htmlspecialchars($data['db_port'] ?? '3306'); ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label for="db_name" class="block text-sm font-medium text-gray-700 mb-1">
                데이터베이스 이름 <span class="text-red-500">*</span>
            </label>
            <input type="text" id="db_name" name="db_name"
                   value="<?php echo htmlspecialchars($data['db_name'] ?? 'rezlyx'); ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   required>
            <p class="mt-1 text-sm text-gray-500">데이터베이스가 없으면 자동으로 생성됩니다.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="db_user" class="block text-sm font-medium text-gray-700 mb-1">
                    사용자명 <span class="text-red-500">*</span>
                </label>
                <input type="text" id="db_user" name="db_user"
                       value="<?php echo htmlspecialchars($data['db_user'] ?? 'root'); ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       required>
            </div>

            <div>
                <label for="db_pass" class="block text-sm font-medium text-gray-700 mb-1">
                    비밀번호
                </label>
                <input type="password" id="db_pass" name="db_pass"
                       value="<?php echo htmlspecialchars($data['db_pass'] ?? ''); ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label for="db_prefix" class="block text-sm font-medium text-gray-700 mb-1">
                테이블 접두사
            </label>
            <input type="text" id="db_prefix" name="db_prefix"
                   value="<?php echo htmlspecialchars($data['db_prefix'] ?? 'rzx_'); ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-sm text-gray-500">여러 RezlyX를 설치할 경우 접두사를 다르게 설정하세요.</p>
        </div>

        <div class="border-t pt-6 flex justify-between">
            <a href="?step=requirements" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                이전
            </a>
            <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                연결 테스트 및 다음
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </form>
</div>
