<?php
/**
 * 스타일북 위젯 — 헤어/뷰티 스타일 사진 갤러리
 * rzx_style_posts 테이블에서 데이터 로드
 * 태그 필터, 좋아요, 매장 연결
 */
ob_start();

$currentLocale = $locale ?? ($currentLocale ?? 'ko');
$baseUrl = $baseUrl ?? '';
$_wCfg = $config ?? [];

// 위젯 자체 번역 로드
$_wLang = @include(__DIR__ . '/lang/' . $currentLocale . '.php');
if (!is_array($_wLang)) $_wLang = @include(__DIR__ . '/lang/ko.php');
if (!is_array($_wLang)) $_wLang = [];
$_wt = function($key, $default = '') use ($_wLang) { return $_wLang[$key] ?? $default; };
$_wTitle = $_wCfg['title'] ?? [];
$_title = is_array($_wTitle) ? ($_wTitle[$currentLocale] ?? $_wTitle['ko'] ?? $_wt('title', '스타일북')) : ($_wTitle ?: $_wt('title', '스타일북'));
$_categoryFilter = $_wCfg['category_filter'] ?? '';
$_columns = (int)($_wCfg['columns'] ?? 4);
$_rows = (int)($_wCfg['rows'] ?? 2);
$_showTags = ($_wCfg['show_tags'] ?? true) !== false;
$_showLikes = ($_wCfg['show_likes'] ?? true) !== false;
$_showMore = ($_wCfg['show_more'] ?? true) !== false;
$_layout = $_wCfg['layout'] ?? 'grid';
$_limit = $_columns * $_rows;

// DB에서 스타일 포스트 로드
$_stylePosts = [];
$_allTags = [];
try {
    if (!isset($pdo)) {
        $pdo = new PDO(
            'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
            $_ENV['DB_USERNAME'] ?? 'root',
            $_ENV['DB_PASSWORD'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $sql = "SELECT sp.*, s.name as shop_name, s.slug as shop_slug, s.cover_image as shop_image
            FROM {$prefix}style_posts sp
            LEFT JOIN {$prefix}shops s ON sp.shop_id = s.id
            WHERE sp.status = 'active'";
    $params = [];

    if ($_categoryFilter) {
        $sql .= " AND sp.category = ?";
        $params[] = $_categoryFilter;
    }

    $sql .= " ORDER BY sp.like_count DESC, sp.created_at DESC LIMIT " . ($_limit + 20);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $_stylePosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 태그 수집
    foreach ($_stylePosts as $p) {
        $tags = json_decode($p['tags'] ?? '[]', true) ?: [];
        foreach ($tags as $t) {
            $_allTags[$t] = ($_allTags[$t] ?? 0) + 1;
        }
    }
    arsort($_allTags);
    $_allTags = array_slice($_allTags, 0, 15, true);

} catch (\Throwable $e) {
    // 에러 시 빈 상태
}

// DB 데이터가 없으면 샘플 이미지로 미리보기
if (empty($_stylePosts)) {
    $_sampleDir = __DIR__ . '/samples';
    $_sampleFiles = [
        ['file' => 'short-cut.jpg', 'tags' => ['숏컷','short'], 'name' => 'Short Cut', 'likes' => 234],
        ['file' => 'layered.jpg', 'tags' => ['레이어드','layered'], 'name' => 'Layered Cut', 'likes' => 189],
        ['file' => 'perm-curl.jpg', 'tags' => ['펌','컬','perm'], 'name' => 'Volume Perm', 'likes' => 312],
        ['file' => 'long-straight.jpg', 'tags' => ['롱','스트레이트','long'], 'name' => 'Long Straight', 'likes' => 156],
        ['file' => 'color-blonde.jpg', 'tags' => ['컬러','블론드','color'], 'name' => 'Blonde Color', 'likes' => 278],
        ['file' => 'balayage.jpg', 'tags' => ['발레아쥬','컬러','balayage'], 'name' => 'Balayage', 'likes' => 198],
        ['file' => 'men-cut.jpg', 'tags' => ['남성','숏','men'], 'name' => 'Men\'s Cut', 'likes' => 143],
        ['file' => 'bob-cut.jpg', 'tags' => ['보브','미디엄','bob'], 'name' => 'Bob Cut', 'likes' => 167],
    ];
    foreach (array_slice($_sampleFiles, 0, $_limit) as $i => $s) {
        $_stylePosts[] = [
            'id' => $i + 1,
            'images' => json_encode([['url' => 'widgets/stylebook/samples/' . $s['file'], 'type' => 'result']]),
            'tags' => json_encode($s['tags']),
            'content' => $s['name'],
            'like_count' => $s['likes'],
            'shop_name' => 'Sample Salon',
            'shop_slug' => '',
            'staff_name' => 'Designer',
            'category' => 'hair',
        ];
    }
    $_allTags = ['숏컷' => 2, '레이어드' => 1, '펌' => 1, '컬러' => 2, '롱' => 1, '남성' => 1, '보브' => 1, '발레아쥬' => 1];
}

$_colClass = match($_columns) {
    2 => 'grid-cols-2',
    3 => 'grid-cols-2 sm:grid-cols-3',
    5 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-5',
    6 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-6',
    default => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4',
};
?>

<div class="stylebook-widget">
    <!-- 헤더 -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($_title) ?></h2>
        <div class="flex items-center gap-3">
            <a href="<?= $baseUrl ?>/styles/create" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= $_wt('create', '등록') ?>
            </a>
            <?php if ($_showMore): ?>
            <a href="<?= $baseUrl ?>/styles" class="text-sm text-blue-600 hover:underline"><?= $_wt('view_more', '더보기') ?> &rarr;</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 태그 필터 -->
    <?php if ($_showTags && !empty($_allTags)): ?>
    <div class="flex flex-wrap gap-2 mb-4" id="styleTagFilter">
        <button class="style-tag-btn active px-3 py-1.5 text-sm rounded-full border border-blue-500 bg-blue-500 text-white transition" data-tag="all">
            <?= $_wt('all', '전체') ?>
        </button>
        <?php foreach ($_allTags as $tag => $cnt): ?>
        <button class="style-tag-btn px-3 py-1.5 text-sm rounded-full border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-400 hover:border-blue-400 hover:text-blue-600 transition" data-tag="<?= htmlspecialchars($tag) ?>">
            <?= htmlspecialchars($tag) ?>
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- 사진 그리드 -->
    <div class="grid <?= $_colClass ?> gap-3" id="styleGrid">
        <?php foreach (array_slice($_stylePosts, 0, $_limit) as $post):
            $images = json_decode($post['images'] ?? '[]', true) ?: [];
            $firstImg = $images[0]['url'] ?? '';
            $tags = json_decode($post['tags'] ?? '[]', true) ?: [];
            $tagStr = implode(',', $tags);
        ?>
        <div class="style-card group cursor-pointer relative rounded-xl overflow-hidden bg-zinc-100 dark:bg-zinc-800 aspect-[3/4]" data-tags="<?= htmlspecialchars($tagStr) ?>" data-id="<?= $post['id'] ?>">
            <?php if ($firstImg): ?>
            <img src="<?= $baseUrl . '/' . ltrim(htmlspecialchars($firstImg), '/') ?>" alt="" class="w-full h-full object-cover" loading="lazy">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center text-zinc-300 dark:text-zinc-600">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <?php endif; ?>

            <!-- 오버레이 -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                <div class="absolute bottom-0 left-0 right-0 p-3">
                    <!-- 태그 -->
                    <?php if (!empty($tags)): ?>
                    <div class="flex flex-wrap gap-1 mb-2">
                        <?php foreach (array_slice($tags, 0, 3) as $t): ?>
                        <span class="text-[10px] px-2 py-0.5 bg-white/20 backdrop-blur-sm text-white rounded-full">#<?= htmlspecialchars($t) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- 매장 + 좋아요 -->
                    <div class="flex items-center justify-between">
                        <div class="text-white text-xs truncate">
                            <?php if ($post['shop_name']): ?>
                            <span class="font-medium"><?= htmlspecialchars($post['shop_name']) ?></span>
                            <?php endif; ?>
                            <?php if ($post['staff_name']): ?>
                            <span class="opacity-70"> · <?= htmlspecialchars($post['staff_name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($_showLikes): ?>
                        <span class="text-white text-xs flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                            <?= number_format($post['like_count'] ?? 0) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($_stylePosts) && !$_isPreview): ?>
    <div class="text-center py-12 text-zinc-400 dark:text-zinc-600">
        <svg class="w-16 h-16 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <p class="text-sm"><?= $_wt('empty', '아직 등록된 스타일이 없습니다.') ?></p>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    // 태그 필터
    var tagBtns = document.querySelectorAll('.style-tag-btn');
    tagBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            tagBtns.forEach(function(b) { b.classList.remove('active', 'bg-blue-500', 'text-white', 'border-blue-500'); b.classList.add('border-zinc-300', 'text-zinc-600'); });
            this.classList.add('active', 'bg-blue-500', 'text-white', 'border-blue-500');
            this.classList.remove('border-zinc-300', 'text-zinc-600');
            var tag = this.dataset.tag;
            document.querySelectorAll('.style-card').forEach(function(card) {
                if (tag === 'all') { card.style.display = ''; return; }
                var cardTags = (card.dataset.tags || '').split(',');
                card.style.display = cardTags.indexOf(tag) !== -1 ? '' : 'none';
            });
        });
    });

    // 카드 클릭 → 상세 페이지
    document.querySelectorAll('.style-card').forEach(function(card) {
        card.addEventListener('click', function() {
            var id = this.dataset.id;
            if (id) location.href = '<?= $baseUrl ?>/styles/' + id;
        });
    });
})();
</script>
<?php return ob_get_clean(); ?>
