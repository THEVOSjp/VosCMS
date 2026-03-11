<?php
/**
 * RezlyX My Page
 */

// Auth 클래스 로드
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

// 로그인 확인
if (!Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login');
    exit;
}

$user = Auth::user();
$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - ' . __('auth.mypage.title');
$baseUrl = $config['app_url'] ?? '';

// 헤더에서 사용할 변수
$isLoggedIn = true;
$currentUser = $user;

// 예약 통계 (더미 데이터 - 실제로는 DB에서 가져옴)
$stats = [
    'total' => 0,
    'upcoming' => 0,
    'completed' => 0,
    'cancelled' => 0,
];

// 최근 예약 (더미 데이터)
$recentReservations = [];

// 헤더 포함
include BASE_PATH . '/resources/views/partials/header.php';
?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="lg:flex lg:gap-8">
            <!-- 사이드바 -->
            <?php
            $profileImgUrl = '';
            if (!empty($user['profile_image'])) {
                $profileImgUrl = str_starts_with($user['profile_image'], 'http')
                    ? $user['profile_image']
                    : $baseUrl . $user['profile_image'];
            }
            $sidebarActive = 'dashboard';
            include BASE_PATH . '/resources/views/components/mypage-sidebar.php';
            ?>

            <!-- 메인 콘텐츠 -->
            <div class="flex-1">
                <!-- 환영 메시지 -->
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl shadow-lg p-6 mb-6 text-white">
                    <h1 class="text-2xl font-bold mb-2"><?php echo __('auth.mypage.welcome', ['name' => $user['name'] ?? '']); ?></h1>
                    <p class="text-blue-100"><?php echo __('auth.login.description'); ?></p>
                </div>

                <!-- 통계 카드 -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-zinc-400"><?php echo __('auth.mypage.stats.total_reservations'); ?></p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['total']; ?></p>
                            </div>
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-zinc-400"><?php echo __('auth.mypage.stats.upcoming'); ?></p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['upcoming']; ?></p>
                            </div>
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-zinc-400"><?php echo __('auth.mypage.stats.completed'); ?></p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['completed']; ?></p>
                            </div>
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-zinc-400"><?php echo __('auth.mypage.stats.cancelled'); ?></p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['cancelled']; ?></p>
                            </div>
                            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 빠른 메뉴 -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 mb-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4"><?php echo __('auth.mypage.quick_actions'); ?></h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="<?php echo $baseUrl; ?>/booking" class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white"><?php echo __('auth.mypage.make_reservation'); ?></p>
                                <p class="text-sm text-gray-500 dark:text-zinc-400"><?php echo __('common.nav.booking'); ?></p>
                            </div>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/mypage/reservations" class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-xl hover:bg-green-100 dark:hover:bg-green-900/30 transition">
                            <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white"><?php echo __('auth.mypage.menu.reservations'); ?></p>
                                <p class="text-sm text-gray-500 dark:text-zinc-400"><?php echo __('auth.mypage.view_all'); ?></p>
                            </div>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/mypage/profile" class="flex items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl hover:bg-purple-100 dark:hover:bg-purple-900/30 transition">
                            <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white"><?php echo __('auth.mypage.menu.profile'); ?></p>
                                <p class="text-sm text-gray-500 dark:text-zinc-400"><?php echo __('auth.profile.description'); ?></p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- 최근 예약 -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white"><?php echo __('auth.mypage.recent_reservations'); ?></h2>
                        <a href="<?php echo $baseUrl; ?>/mypage/reservations" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            <?php echo __('auth.mypage.view_all'); ?> &rarr;
                        </a>
                    </div>

                    <?php if (empty($recentReservations)): ?>
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-300 dark:text-zinc-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-gray-500 dark:text-zinc-400"><?php echo __('auth.mypage.no_reservations'); ?></p>
                        <a href="<?php echo $baseUrl; ?>/booking" class="inline-block mt-4 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                            <?php echo __('auth.mypage.make_reservation'); ?>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($recentReservations as $reservation): ?>
                        <div class="flex items-center p-4 bg-gray-50 dark:bg-zinc-700/50 rounded-xl">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($reservation['service_name']); ?></p>
                                <p class="text-sm text-gray-500 dark:text-zinc-400">
                                    <?php echo $reservation['date']; ?> <?php echo $reservation['time']; ?>
                                </p>
                            </div>
                            <span class="px-3 py-1 text-xs font-medium rounded-full
                                <?php echo match($reservation['status']) {
                                    'confirmed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                    'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                    'completed' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                    'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-zinc-700 dark:text-zinc-300'
                                }; ?>">
                                <?php echo __('common.status.' . $reservation['status']); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

<?php
// 푸터 포함
include BASE_PATH . '/resources/views/partials/footer.php';
?>
