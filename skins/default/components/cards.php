<?php
/**
 * RezlyX Default Theme - Card Components
 *
 * 재사용 가능한 카드 컴포넌트 모음
 */
?>

<?php
/**
 * 기본 카드
 */
function renderCard($content, $options = []) {
    $class = $options['class'] ?? '';
    $padding = $options['padding'] ?? 'p-6';
    ?>
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg dark:shadow-zinc-900/50 <?php echo $padding; ?> transition-colors <?php echo $class; ?>">
        <?php echo $content; ?>
    </div>
    <?php
}

/**
 * 서비스 카드
 */
function renderServiceCard($service, $baseUrl = '') {
    $name = htmlspecialchars($service['name'] ?? '');
    $description = htmlspecialchars($service['description'] ?? '');
    $price = number_format($service['price'] ?? 0);
    $duration = $service['duration'] ?? 60;
    $id = $service['id'] ?? 0;
    $image = $service['image'] ?? null;
    ?>
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg dark:shadow-zinc-900/50 overflow-hidden
                hover:shadow-xl transition-all duration-300 group">
        <?php if ($image): ?>
            <div class="aspect-video bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo $name; ?>"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            </div>
        <?php else: ?>
            <div class="aspect-video bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                <svg class="w-16 h-16 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        <?php endif; ?>

        <div class="p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2"><?php echo $name; ?></h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4 line-clamp-2"><?php echo $description; ?></p>

            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400">₩<?php echo $price; ?></span>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400 ml-1">/ <?php echo $duration; ?>분</span>
                </div>
                <a href="<?php echo $baseUrl; ?>/booking?service=<?php echo $id; ?>"
                   class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    예약하기
                </a>
            </div>
        </div>
    </div>
    <?php
}

/**
 * 특징 카드
 */
function renderFeatureCard($icon, $title, $description) {
    ?>
    <div class="text-center p-6">
        <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center">
            <?php echo $icon; ?>
        </div>
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2"><?php echo htmlspecialchars($title); ?></h3>
        <p class="text-sm text-zinc-600 dark:text-zinc-400"><?php echo htmlspecialchars($description); ?></p>
    </div>
    <?php
}

/**
 * 통계 카드
 */
function renderStatCard($value, $label, $icon = null) {
    ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-lg dark:shadow-zinc-900/50">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-3xl font-bold text-zinc-900 dark:text-white"><?php echo htmlspecialchars($value); ?></p>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1"><?php echo htmlspecialchars($label); ?></p>
            </div>
            <?php if ($icon): ?>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                    <?php echo $icon; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * 후기 카드
 */
function renderTestimonialCard($name, $content, $rating = 5, $avatar = null) {
    ?>
    <div class="bg-white dark:bg-zinc-800 rounded-2xl p-6 shadow-lg dark:shadow-zinc-900/50">
        <div class="flex items-center space-x-1 mb-4">
            <?php for ($i = 0; $i < 5; $i++): ?>
                <svg class="w-5 h-5 <?php echo $i < $rating ? 'text-amber-400' : 'text-zinc-300 dark:text-zinc-600'; ?>"
                     fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            <?php endfor; ?>
        </div>
        <p class="text-zinc-600 dark:text-zinc-300 mb-4 italic">"<?php echo htmlspecialchars($content); ?>"</p>
        <div class="flex items-center">
            <?php if ($avatar): ?>
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($name); ?>"
                     class="w-10 h-10 rounded-full mr-3">
            <?php else: ?>
                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mr-3">
                    <span class="text-blue-600 dark:text-blue-400 font-semibold"><?php echo mb_substr($name, 0, 1); ?></span>
                </div>
            <?php endif; ?>
            <span class="font-medium text-zinc-900 dark:text-white"><?php echo htmlspecialchars($name); ?></span>
        </div>
    </div>
    <?php
}
?>
