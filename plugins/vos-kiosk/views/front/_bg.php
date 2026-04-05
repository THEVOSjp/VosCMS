<?php if ($kioskBgType === 'image' && $kioskBgImage): ?>
    <div class="bg-media-container"><img src="<?= htmlspecialchars($kioskBgImage) ?>" alt="background"></div>
    <div class="bg-overlay"></div>
<?php elseif ($kioskBgType === 'video' && $kioskBgVideo): ?>
    <div class="bg-media-container"><video autoplay muted loop playsinline><source src="<?= htmlspecialchars($kioskBgVideo) ?>" type="video/<?= pathinfo($kioskBgVideo, PATHINFO_EXTENSION) ?: 'mp4' ?>"></video></div>
    <div class="bg-overlay"></div>
<?php endif; ?>
