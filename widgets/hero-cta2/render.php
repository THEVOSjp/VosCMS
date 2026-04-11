<?php
/**
 * Hero CTA Banner Widget - render.php
 * Rhymix.org 스타일 — 타이핑 애니메이션 + 중앙 정렬 + CTA 버튼
 *
 * 사용 가능한 변수:
 *   $config, $widget, $renderer, $baseUrl, $locale
 */

$taglineTop = htmlspecialchars($renderer->t($config, 'tagline_top', ''));
$highlightWord = htmlspecialchars($renderer->t($config, 'highlight_word', 'VosCMS'));
$taglineBottom = htmlspecialchars($renderer->t($config, 'tagline_bottom', ''));
$description = $renderer->t($config, 'description', '');
$primaryText = $config['primary_btn_text'] ?? '';
$primaryUrl = $config['primary_btn_url'] ?? '#';
$primarySub = $config['primary_btn_sub'] ?? '';
$secondaryText = $config['secondary_btn_text'] ?? '';
$secondaryUrl = $config['secondary_btn_url'] ?? '/marketplace';
$secondarySub = $config['secondary_btn_sub'] ?? '';
$requirements = $config['requirements'] ?? '';
$highlightColor = $config['highlight_color'] ?? '#4f46e5';
$primaryBtnColor = $config['primary_btn_color'] ?? '#4f46e5';
$bgStyle = $config['bg_style'] ?? 'light';

// 타이핑 텍스트
$typingWordsRaw = $renderer->t($config, 'typing_words', '');
$typingWords = array_filter(array_map('trim', explode("\n", $typingWordsRaw)));
$typingSpeed = match($config['typing_speed'] ?? 'normal') {
    'fast' => 100,
    'slow' => 220,
    default => 160,
};

// 위젯 고유 ID
$uid = 'hcta_' . substr(md5(json_encode($config) . ($widget['id'] ?? '')), 0, 6);

// 배경 스타일
$bgClasses = match($bgStyle) {
    'dark' => 'bg-zinc-900 text-white',
    'gradient' => 'bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-900 text-white',
    default => 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white',
};
$descColor = match($bgStyle) {
    'dark', 'gradient' => 'color: #a6abaf;',
    default => '',
};
$reqColor = match($bgStyle) {
    'dark', 'gradient' => 'color: #888;',
    default => 'color: #61676c;',
};
$barColor = match($bgStyle) {
    'dark', 'gradient' => 'color: #555;',
    default => 'color: #b7babe;',
};
$secondaryBg = match($bgStyle) {
    'dark', 'gradient' => 'background: #3f3f46;',
    default => 'background: #e9eaec;',
};
$secondaryTextColor = match($bgStyle) {
    'dark', 'gradient' => 'color: #d4d4d8;',
    default => 'color: #52575b;',
};
$borderStyle = match($bgStyle) {
    'dark', 'gradient' => 'border:2px solid rgba(255,255,255,0.2);border-radius:4px;padding:4px 12px;',
    default => 'border-bottom:2px solid ' . $highlightColor . ';padding-bottom:2px;',
};
?>

<section class="hero-cta-widget <?= $bgClasses ?>">
    <div style="max-width:900px;margin:0 auto;padding:64px 20px;text-align:center;">

        <!-- Tagline -->
        <h1 class="hero-cta-title" style="margin:0 0 10px 0;padding:0;font-size:38px;font-weight:bold;line-height:160%;letter-spacing:-0.02em;">
            <?php if ($taglineTop): ?>
            <span><?= $taglineTop ?></span><br>
            <?php endif; ?>
            <span id="<?= $uid ?>" class="hero-cta-highlight" style="display:inline-block;min-width:200px;color:<?= $highlightColor ?>;font-size:42px;<?= $borderStyle ?>">
                <?= $highlightWord ?>
            </span>
            <?php if ($taglineBottom): ?>
            <span> <?= $taglineBottom ?></span>
            <?php endif; ?>
        </h1>

        <!-- Description -->
        <?php if ($description): ?>
        <div class="hero-cta-desc" style="margin-top:40px;font-size:16px;line-height:200%;word-break:keep-all;color:#61676c;<?= $descColor ?>">
            <?= nl2br(htmlspecialchars($description)) ?>
        </div>
        <?php endif; ?>

        <!-- CTA Buttons -->
        <?php if ($primaryText || $secondaryText): ?>
        <div style="margin-top:48px;margin-bottom:16px;">
            <div class="hero-cta-buttons" style="display:inline-flex;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                <?php if ($primaryText): ?>
                <a href="<?= htmlspecialchars($primaryUrl) ?>"
                   style="display:block;padding:16px 32px;background:<?= $primaryBtnColor ?>;color:#fff;text-decoration:none;font-size:16px;line-height:150%;text-align:center;min-width:200px;transition:opacity 0.2s;"
                   onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <span style="font-weight:bold;"><?= htmlspecialchars($primaryText) ?></span>
                    <?php if ($primarySub): ?><br><span style="font-size:12px;opacity:0.8;"><?= htmlspecialchars($primarySub) ?></span><?php endif; ?>
                </a>
                <?php endif; ?>
                <?php if ($secondaryText): ?>
                <a href="<?= htmlspecialchars($secondaryUrl) ?>"
                   style="display:block;padding:16px 32px;<?= $secondaryBg ?><?= $secondaryTextColor ?>text-decoration:none;font-size:16px;line-height:150%;text-align:center;min-width:180px;transition:opacity 0.2s;"
                   onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                    <span style="font-weight:bold;"><?= htmlspecialchars($secondaryText) ?></span>
                    <?php if ($secondarySub): ?><br><span style="font-size:12px;opacity:0.7;"><?= htmlspecialchars($secondarySub) ?></span><?php endif; ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Requirements -->
        <?php if ($requirements): ?>
        <div style="margin-top:12px;font-size:12px;<?= $reqColor ?>line-height:150%;">
            <?php
            $parts = explode('|', $requirements);
            echo implode('<span style="display:inline-block;margin:0 6px;' . $barColor . '">|</span>', array_map(fn($p) => htmlspecialchars(trim($p)), $parts));
            ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($typingWords)): ?>
<script>
(function() {
    var words = <?= json_encode(array_values($typingWords), JSON_UNESCAPED_UNICODE) ?>;
    var el = document.getElementById('<?= $uid ?>');
    if (!el || words.length === 0) return;

    var speed = <?= $typingSpeed ?>;
    var idx = 0;

    function typeWord() {
        var chars = words[idx].split('');
        var i = 0;

        function typeChar() {
            var display = chars.slice(0, i + 1).join('');
            el.textContent = display;
            i++;
            if (i < chars.length) {
                setTimeout(typeChar, speed);
            } else {
                setTimeout(eraseWord, speed * 8);
            }
        }

        function eraseWord() {
            var text = words[idx];
            var len = text.length;

            function eraseChar() {
                len--;
                if (len > 0) {
                    el.textContent = text.substring(0, len);
                    setTimeout(eraseChar, speed / 2);
                } else {
                    el.textContent = '\u00A0';
                    idx = (idx + 1) % words.length;
                    setTimeout(typeWord, speed * 2);
                }
            }
            eraseChar();
        }

        typeChar();
    }

    setTimeout(function() {
        idx = 0;
        typeWord();
    }, speed * 12);
})();
</script>
<?php endif; ?>

<style>
.dark .hero-cta-desc { color: #a6abaf !important; }
.hero-cta-highlight { transition: none; font-family: inherit; }
@media (max-width: 768px) {
    .hero-cta-title { font-size: 26px !important; }
    .hero-cta-highlight { font-size: 32px !important; }
    .hero-cta-buttons { flex-direction: column !important; width: 100% !important; }
    .hero-cta-buttons a { min-width: auto !important; width: 100% !important; }
}
</style>
