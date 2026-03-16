<?php
/**
 * 키오스크 설정 페이지
 */
include __DIR__ . '/_init.php';

include_once BASE_PATH . '/resources/views/admin/components/multilang-button.php';
require_once BASE_PATH . '/rzxlib/Core/Modules/LanguageModule.php';
use RzxLib\Core\Modules\LanguageModule;

$pageTitle = __('reservations.kiosk_settings') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$langData = LanguageModule::getData($siteSettings ?? [], $currentLocale ?? 'ko');
$allLanguages = $langData['allLanguages'];
$supportedCodes = $langData['supportedCodes'];
$appUrl = $baseUrl;

// 키오스크 설정 로드
$kioskSettingsRaw = [];
$stmt = $pdo->prepare("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` LIKE 'kiosk_%'");
$stmt->execute();
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $kioskSettingsRaw[$row['key']] = $row['value'];
}

$kioskEnabled = ($kioskSettingsRaw['kiosk_enabled'] ?? '0') === '1';
$kioskLanguages = json_decode($kioskSettingsRaw['kiosk_languages'] ?? '[]', true) ?: $supportedCodes;
$kioskTheme = $kioskSettingsRaw['kiosk_theme'] ?? 'dark';
$kioskIdleTimeout = (int)($kioskSettingsRaw['kiosk_idle_timeout'] ?? 60);
$kioskLogoOverride = $kioskSettingsRaw['kiosk_logo_override'] ?? '';
$kioskWelcomeText = $kioskSettingsRaw['kiosk_welcome_text'] ?? '';
$kioskFooterText = $kioskSettingsRaw['kiosk_footer_text'] ?? '';
$kioskBgType = $kioskSettingsRaw['kiosk_bg_type'] ?? 'gradient'; // gradient, image, video
$kioskBgImage = $kioskSettingsRaw['kiosk_bg_image'] ?? '';
$kioskBgVideo = $kioskSettingsRaw['kiosk_bg_video'] ?? '';
$kioskBgOverlay = (int)($kioskSettingsRaw['kiosk_bg_overlay'] ?? 50);

// 저장 처리
$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_token'] ?? '') === $csrfToken) {
    $saveData = [
        'kiosk_enabled' => isset($_POST['kiosk_enabled']) ? '1' : '0',
        'kiosk_languages' => json_encode($_POST['kiosk_languages'] ?? []),
        'kiosk_theme' => $_POST['kiosk_theme'] ?? 'dark',
        'kiosk_idle_timeout' => max(10, (int)($_POST['kiosk_idle_timeout'] ?? 60)),
        'kiosk_logo_override' => trim($_POST['kiosk_logo_override'] ?? ''),
        'kiosk_welcome_text' => trim($_POST['kiosk_welcome_text'] ?? ''),
        'kiosk_footer_text' => trim($_POST['kiosk_footer_text'] ?? ''),
        'kiosk_bg_type' => in_array($_POST['kiosk_bg_type'] ?? '', ['gradient', 'image', 'video']) ? $_POST['kiosk_bg_type'] : 'gradient',
        'kiosk_bg_image' => trim($_POST['kiosk_bg_image'] ?? ''),
        'kiosk_bg_video' => trim($_POST['kiosk_bg_video'] ?? ''),
        'kiosk_bg_overlay' => max(0, min(100, (int)($_POST['kiosk_bg_overlay'] ?? 50))),
    ];

    $stmtUpsert = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
    foreach ($saveData as $k => $v) {
        $stmtUpsert->execute([$k, $v]);
    }
    $saved = true;

    // 값 갱신
    $kioskEnabled = $saveData['kiosk_enabled'] === '1';
    $kioskLanguages = json_decode($saveData['kiosk_languages'], true) ?: [];
    $kioskTheme = $saveData['kiosk_theme'];
    $kioskIdleTimeout = (int)$saveData['kiosk_idle_timeout'];
    $kioskLogoOverride = $saveData['kiosk_logo_override'];
    $kioskWelcomeText = $saveData['kiosk_welcome_text'];
    $kioskFooterText = $saveData['kiosk_footer_text'];
    $kioskBgType = $saveData['kiosk_bg_type'];
    $kioskBgImage = $saveData['kiosk_bg_image'];
    $kioskBgVideo = $saveData['kiosk_bg_video'];
    $kioskBgOverlay = (int)$saveData['kiosk_bg_overlay'];
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLocale ?? 'ko' ?>" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-950 min-h-screen">

<?php include BASE_PATH . '/resources/views/admin/partials/admin-sidebar.php'; ?>
<?php include BASE_PATH . '/resources/views/admin/partials/admin-topbar.php'; ?>

<main class="lg:ml-64 pt-16 p-6">
    <div class="max-w-3xl mx-auto">

        <!-- 헤더 -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('reservations.kiosk_settings') ?></h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __('reservations.kiosk_settings_desc') ?></p>
            </div>
            <a href="<?= $adminUrl ?>/kiosk/run" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <?= __('reservations.kiosk_preview') ?>
            </a>
        </div>

        <?php if ($saved): ?>
        <div class="mb-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-emerald-700 dark:text-emerald-400">
            <?= __('admin.messages.saved') ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= $adminUrl ?>/kiosk/settings" class="space-y-6">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">

            <!-- 기본 설정 -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('reservations.kiosk_basic') ?></h2>

                <!-- 키오스크 활성화 -->
                <div class="flex items-center justify-between py-3 border-b border-zinc-100 dark:border-zinc-800">
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('reservations.kiosk_enable') ?></p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('reservations.kiosk_enable_desc') ?></p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="kiosk_enabled" value="1" class="sr-only peer" <?= $kioskEnabled ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-zinc-300 dark:bg-zinc-700 rounded-full peer peer-checked:bg-blue-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                    </label>
                </div>

                <!-- 테마 -->
                <div class="py-3 border-b border-zinc-100 dark:border-zinc-800">
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2"><?= __('reservations.kiosk_theme') ?></label>
                    <div class="flex gap-3">
                        <label class="flex items-center gap-2 px-4 py-2.5 rounded-lg border cursor-pointer transition <?= $kioskTheme === 'dark' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-zinc-200 dark:border-zinc-700' ?>">
                            <input type="radio" name="kiosk_theme" value="dark" class="text-blue-600" <?= $kioskTheme === 'dark' ? 'checked' : '' ?>>
                            <div class="w-6 h-6 rounded bg-zinc-900 border border-zinc-600"></div>
                            <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('reservations.kiosk_theme_dark') ?></span>
                        </label>
                        <label class="flex items-center gap-2 px-4 py-2.5 rounded-lg border cursor-pointer transition <?= $kioskTheme === 'light' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-zinc-200 dark:border-zinc-700' ?>">
                            <input type="radio" name="kiosk_theme" value="light" class="text-blue-600" <?= $kioskTheme === 'light' ? 'checked' : '' ?>>
                            <div class="w-6 h-6 rounded bg-white border border-zinc-300"></div>
                            <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('reservations.kiosk_theme_light') ?></span>
                        </label>
                    </div>
                </div>

                <!-- 배경 타입 -->
                <div class="py-3 border-b border-zinc-100 dark:border-zinc-800">
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2"><?= __('reservations.kiosk_bg_type') ?></label>
                    <div class="flex gap-3 mb-3">
                        <?php foreach (['gradient' => __('reservations.kiosk_bg_gradient'), 'image' => __('reservations.kiosk_bg_image_label'), 'video' => __('reservations.kiosk_bg_video_label')] as $bgVal => $bgLabel): ?>
                        <label class="flex items-center gap-2 px-4 py-2.5 rounded-lg border cursor-pointer transition <?= $kioskBgType === $bgVal ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-zinc-200 dark:border-zinc-700' ?>">
                            <input type="radio" name="kiosk_bg_type" value="<?= $bgVal ?>" class="text-blue-600 kiosk-bg-radio" <?= $kioskBgType === $bgVal ? 'checked' : '' ?>>
                            <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= $bgLabel ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- 이미지 URL -->
                    <div id="kioskBgImageField" class="<?= $kioskBgType === 'image' ? '' : 'hidden' ?> mt-3">
                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('reservations.kiosk_bg_image_url') ?></label>
                        <div class="flex gap-2">
                            <input type="text" name="kiosk_bg_image" value="<?= htmlspecialchars($kioskBgImage) ?>"
                                   placeholder="https://example.com/bg.jpg"
                                   class="flex-1 px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white text-sm"
                                   id="kioskBgImageInput">
                            <label class="px-3 py-2 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-600 dark:text-zinc-300 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-600 transition flex-shrink-0">
                                <?= __('reservations.kiosk_bg_upload') ?>
                                <input type="file" accept="image/*" class="hidden" onchange="uploadKioskBg(this, 'image')">
                            </label>
                        </div>
                        <?php if ($kioskBgImage): ?>
                        <div class="mt-2 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700 max-h-32">
                            <img src="<?= htmlspecialchars($kioskBgImage) ?>" class="w-full h-32 object-cover" onerror="this.parentElement.classList.add('hidden')">
                        </div>
                        <?php endif; ?>
                        <p class="text-xs text-zinc-400 mt-1"><?= __('reservations.kiosk_bg_image_desc') ?></p>
                    </div>

                    <!-- 동영상 URL -->
                    <div id="kioskBgVideoField" class="<?= $kioskBgType === 'video' ? '' : 'hidden' ?> mt-3">
                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('reservations.kiosk_bg_video_url') ?></label>
                        <div class="flex gap-2">
                            <input type="text" name="kiosk_bg_video" value="<?= htmlspecialchars($kioskBgVideo) ?>"
                                   placeholder="https://example.com/bg.mp4"
                                   class="flex-1 px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white text-sm"
                                   id="kioskBgVideoInput">
                            <label class="px-3 py-2 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-600 dark:text-zinc-300 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-600 transition flex-shrink-0">
                                <?= __('reservations.kiosk_bg_upload') ?>
                                <input type="file" accept="video/mp4,video/webm" class="hidden" onchange="uploadKioskBg(this, 'video')">
                            </label>
                        </div>
                        <p class="text-xs text-zinc-400 mt-1"><?= __('reservations.kiosk_bg_video_desc') ?></p>
                    </div>

                    <!-- 오버레이 투명도 -->
                    <div id="kioskBgOverlayField" class="<?= $kioskBgType === 'gradient' ? 'hidden' : '' ?> mt-3">
                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('reservations.kiosk_bg_overlay') ?> <span id="overlayValue" class="text-blue-500"><?= $kioskBgOverlay ?>%</span></label>
                        <input type="range" name="kiosk_bg_overlay" min="0" max="100" value="<?= $kioskBgOverlay ?>"
                               class="w-full h-2 bg-zinc-200 dark:bg-zinc-700 rounded-lg appearance-none cursor-pointer"
                               oninput="document.getElementById('overlayValue').textContent = this.value + '%'">
                        <p class="text-xs text-zinc-400 mt-1"><?= __('reservations.kiosk_bg_overlay_desc') ?></p>
                    </div>
                </div>

                <!-- 대기 시간 -->
                <div class="py-3">
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2"><?= __('reservations.kiosk_idle_timeout') ?></label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="kiosk_idle_timeout" value="<?= $kioskIdleTimeout ?>" min="10" max="600"
                               class="w-24 px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white text-sm">
                        <span class="text-sm text-zinc-500"><?= __('reservations.kiosk_seconds') ?></span>
                    </div>
                    <p class="text-xs text-zinc-400 mt-1"><?= __('reservations.kiosk_idle_desc') ?></p>
                </div>
            </div>

            <!-- 언어 설정 -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2"><?= __('reservations.kiosk_lang_title') ?></h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4"><?= __('reservations.kiosk_lang_desc') ?></p>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <?php foreach ($supportedCodes as $code):
                        $native = $allLanguages[$code]['native'] ?? strtoupper($code);
                        $checked = in_array($code, $kioskLanguages);
                    ?>
                    <label class="flex items-center gap-2.5 p-2.5 rounded-lg border cursor-pointer transition hover:bg-zinc-50 dark:hover:bg-zinc-800 <?= $checked ? 'border-blue-400 bg-blue-50/50 dark:bg-blue-900/10' : 'border-zinc-200 dark:border-zinc-700' ?>">
                        <input type="checkbox" name="kiosk_languages[]" value="<?= $code ?>" class="rounded text-blue-600" <?= $checked ? 'checked' : '' ?>>
                        <span class="text-sm text-zinc-900 dark:text-white"><?= htmlspecialchars($native) ?></span>
                        <span class="text-xs text-zinc-400">(<?= $code ?>)</span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 커스텀 텍스트 -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('reservations.kiosk_custom_text') ?></h2>

                <div class="space-y-4">
                    <div>
                        <div class="flex items-center gap-1 mb-1">
                            <label class="block text-sm font-medium text-zinc-900 dark:text-white"><?= __('reservations.kiosk_welcome') ?></label>
                            <?= rzx_multilang_btn("openMultilangModal('kiosk.welcome_text','kioskWelcomeInput')") ?>
                        </div>
                        <input type="text" name="kiosk_welcome_text" id="kioskWelcomeInput" value="<?= htmlspecialchars($kioskWelcomeText) ?>"
                               placeholder="Select your language"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white text-sm">
                    </div>
                    <div>
                        <div class="flex items-center gap-1 mb-1">
                            <label class="block text-sm font-medium text-zinc-900 dark:text-white"><?= __('reservations.kiosk_footer') ?></label>
                            <?= rzx_multilang_btn("openMultilangModal('kiosk.footer_text','kioskFooterInput')") ?>
                        </div>
                        <textarea name="kiosk_footer_text" id="kioskFooterInput" rows="3"
                                  placeholder="Powered by RezlyX"
                                  class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white text-sm"><?= htmlspecialchars($kioskFooterText) ?></textarea>
                        <p class="text-xs text-zinc-400 mt-1"><?= __('reservations.kiosk_footer_html_hint') ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('reservations.kiosk_logo_url') ?></label>
                        <input type="text" name="kiosk_logo_override" value="<?= htmlspecialchars($kioskLogoOverride) ?>"
                               placeholder="<?= __('reservations.kiosk_logo_placeholder') ?>"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white text-sm">
                        <p class="text-xs text-zinc-400 mt-1"><?= __('reservations.kiosk_logo_desc') ?></p>
                    </div>
                </div>
            </div>

            <!-- 저장 버튼 -->
            <div class="flex justify-end">
                <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition">
                    <?= __('admin.messages.save') ?>
                </button>
            </div>
        </form>

    </div>
</main>

<?php include BASE_PATH . '/resources/views/admin/components/multilang-modal.php'; ?>

<script>
console.log('[Kiosk Settings] Page loaded');

// 테마 라디오 선택 시 시각적 피드백
document.querySelectorAll('input[name="kiosk_theme"]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('input[name="kiosk_theme"]').forEach(r => {
            const label = r.closest('label');
            if (r.checked) {
                label.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                label.classList.remove('border-zinc-200', 'dark:border-zinc-700');
            } else {
                label.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                label.classList.add('border-zinc-200', 'dark:border-zinc-700');
            }
        });
        console.log('[Kiosk Settings] Theme changed:', radio.value);
    });
});

// 배경 타입 라디오 전환
document.querySelectorAll('.kiosk-bg-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.kiosk-bg-radio').forEach(r => {
            const label = r.closest('label');
            if (r.checked) {
                label.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                label.classList.remove('border-zinc-200', 'dark:border-zinc-700');
            } else {
                label.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                label.classList.add('border-zinc-200', 'dark:border-zinc-700');
            }
        });
        const v = radio.value;
        document.getElementById('kioskBgImageField').classList.toggle('hidden', v !== 'image');
        document.getElementById('kioskBgVideoField').classList.toggle('hidden', v !== 'video');
        document.getElementById('kioskBgOverlayField').classList.toggle('hidden', v === 'gradient');
        console.log('[Kiosk Settings] Background type changed:', v);
    });
});

// 배경 파일 업로드
async function uploadKioskBg(input, type) {
    const file = input.files[0];
    if (!file) return;
    console.log('[Kiosk Settings] Uploading', type, file.name);

    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', '<?= $csrfToken ?>');
    formData.append('type', 'kiosk_bg');

    try {
        const resp = await fetch('<?= $adminUrl ?>/kiosk/upload', { method: 'POST', body: formData });
        const data = await resp.json();
        console.log('[Kiosk Settings] Upload result:', data);
        if (data.success && data.url) {
            if (type === 'image') {
                document.getElementById('kioskBgImageInput').value = data.url;
            } else {
                document.getElementById('kioskBgVideoInput').value = data.url;
            }
        } else {
            alert(data.message || '업로드 실패');
        }
    } catch (err) {
        console.error('[Kiosk Settings] Upload error:', err);
        alert('업로드 실패');
    }
    input.value = '';
}

// 언어 체크박스 시각적 피드백
document.querySelectorAll('input[name="kiosk_languages[]"]').forEach(cb => {
    cb.addEventListener('change', () => {
        const label = cb.closest('label');
        if (cb.checked) {
            label.classList.add('border-blue-400', 'bg-blue-50/50', 'dark:bg-blue-900/10');
            label.classList.remove('border-zinc-200', 'dark:border-zinc-700');
        } else {
            label.classList.remove('border-blue-400', 'bg-blue-50/50', 'dark:bg-blue-900/10');
            label.classList.add('border-zinc-200', 'dark:border-zinc-700');
        }
        console.log('[Kiosk Settings] Language toggled:', cb.value, cb.checked);
    });
});
</script>

</body>
</html>
