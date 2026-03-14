<?php
/**
 * RezlyX Admin Members Settings - Register
 * Registration form settings configuration
 */

require_once __DIR__ . '/_init.php';

$pageTitle = __('members.settings.tabs.register') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentMemberSettingsPage = 'register';

// Available form fields
$availableFields = [
    'name' => __('members.settings.register.fields.name'),
    'email' => __('members.settings.register.fields.email'),
    'password' => __('members.settings.register.fields.password'),
    'phone' => __('members.settings.register.fields.phone'),
    'birth_date' => __('members.settings.register.fields.birth_date'),
    'gender' => __('members.settings.register.fields.gender'),
    'company' => __('members.settings.register.fields.company'),
    'blog' => __('members.settings.register.fields.blog'),
    'profile_photo' => __('members.settings.register.fields.profile_photo'),
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_register') {
        try {
            $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");

            // 회원가입 필드
            $registerFields = implode(',', $_POST['member_register_fields'] ?? ['name', 'email', 'password']);
            $stmt->execute(['member_register_fields', $registerFields]);
            $memberSettings['member_register_fields'] = $registerFields;

            // 캡차 사용
            $captcha = isset($_POST['member_register_captcha']) ? '1' : '0';
            $stmt->execute(['member_register_captcha', $captcha]);
            $memberSettings['member_register_captcha'] = $captcha;

            // 환영 이메일
            $welcomeEmail = isset($_POST['member_welcome_email']) ? '1' : '0';
            $stmt->execute(['member_welcome_email', $welcomeEmail]);
            $memberSettings['member_welcome_email'] = $welcomeEmail;

            // 회원가입 후 이동 URL
            $redirectUrl = trim($_POST['member_register_redirect_url'] ?? '');
            $stmt->execute(['member_register_redirect_url', $redirectUrl]);
            $memberSettings['member_register_redirect_url'] = $redirectUrl;

            // 이메일 제공자 관리
            $emailProviderMode = $_POST['member_email_provider_mode'] ?? 'none';
            $stmt->execute(['member_email_provider_mode', $emailProviderMode]);
            $memberSettings['member_email_provider_mode'] = $emailProviderMode;

            $emailProviderList = trim($_POST['member_email_provider_list'] ?? '');
            $stmt->execute(['member_email_provider_list', $emailProviderList]);
            $memberSettings['member_email_provider_list'] = $emailProviderList;

            $message = __('settings.success');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$currentFields = explode(',', $memberSettings['member_register_fields'] ?? 'name,email,password,phone');

ob_start();
?>

<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors mb-6">
    <?php
    $headerIcon = 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z';
    $headerTitle = __('members.settings.register.title');
    $headerDescription = __('members.settings.register.description');
    $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../../components/settings-header.php';
    ?>

    <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="update_register">

        <!-- 회원가입 필드 선택 -->
        <div class="py-4 border-b dark:border-zinc-700">
            <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('members.settings.register.form_fields') ?></label>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3"><?= __('members.settings.register.form_fields_desc') ?></p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <?php foreach ($availableFields as $key => $label): ?>
                <label class="flex items-center p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 transition">
                    <input type="checkbox" name="member_register_fields[]" value="<?php echo $key; ?>"
                           <?php echo in_array($key, $currentFields) ? 'checked' : ''; ?>
                           <?php echo in_array($key, ['name', 'email', 'password']) ? 'checked disabled' : ''; ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?php echo $label; ?></span>
                    <?php if (in_array($key, ['name', 'email', 'password'])): ?>
                    <span class="ml-1 text-xs text-red-500">*</span>
                    <?php endif; ?>
                </label>
                <?php endforeach; ?>
            </div>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400"><?= __('members.settings.register.required_note') ?></p>
            <!-- Hidden inputs for required fields -->
            <input type="hidden" name="member_register_fields[]" value="name">
            <input type="hidden" name="member_register_fields[]" value="email">
            <input type="hidden" name="member_register_fields[]" value="password">
        </div>

        <!-- 캡차 사용 -->
        <div class="flex items-center justify-between py-4 border-b dark:border-zinc-700">
            <div>
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('members.settings.register.use_captcha') ?></h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('members.settings.register.use_captcha_desc') ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="member_register_captcha" class="sr-only peer" <?php echo ($memberSettings['member_register_captcha'] ?? '0') === '1' ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <!-- 이메일 제공자 관리 -->
        <div class="py-4 border-b dark:border-zinc-700">
            <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('members.settings.register.email_provider') ?></label>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3"><?= __('members.settings.register.email_provider_desc') ?></p>

            <!-- 허가/제한 라디오 -->
            <div class="flex items-center space-x-6 mb-4">
                <?php $currentMode = $memberSettings['member_email_provider_mode'] ?? 'none'; ?>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="radio" name="member_email_provider_mode" value="none"
                           <?= $currentMode === 'none' ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500"
                           onchange="toggleEmailProviderList()">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('members.settings.register.email_provider_none') ?></span>
                </label>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="radio" name="member_email_provider_mode" value="allow"
                           <?= $currentMode === 'allow' ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500"
                           onchange="toggleEmailProviderList()">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('members.settings.register.email_provider_allow') ?></span>
                </label>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="radio" name="member_email_provider_mode" value="block"
                           <?= $currentMode === 'block' ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500"
                           onchange="toggleEmailProviderList()">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('members.settings.register.email_provider_block') ?></span>
                </label>
            </div>

            <!-- 도메인 목록 입력 -->
            <div id="emailProviderListSection" class="<?= $currentMode === 'none' ? 'hidden' : '' ?>">
                <!-- 현재 등록된 도메인 목록 -->
                <div id="emailProviderTags" class="flex flex-wrap gap-2 mb-3">
                    <?php
                    $providerList = $memberSettings['member_email_provider_list'] ?? '';
                    $providers = array_filter(array_map('trim', explode("\n", $providerList)));
                    foreach ($providers as $provider):
                        if (!empty($provider)):
                    ?>
                    <span class="inline-flex items-center px-3 py-1 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-full text-sm">
                        <?= htmlspecialchars($provider) ?>
                        <button type="button" onclick="removeEmailProvider(this, '<?= htmlspecialchars($provider) ?>')" class="ml-2 text-zinc-400 hover:text-red-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </span>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>

                <!-- 새 도메인 추가 -->
                <div class="flex gap-2 mb-2">
                    <input type="text" id="newEmailProvider"
                           placeholder="<?= __('members.settings.register.email_provider_placeholder') ?>"
                           class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <button type="button" onclick="addEmailProvider()"
                            class="px-4 py-2 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-medium rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                        <?= __('admin.buttons.add') ?>
                    </button>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('members.settings.register.email_provider_hint') ?></p>

                <!-- 숨겨진 textarea로 실제 값 저장 -->
                <textarea name="member_email_provider_list" id="emailProviderListInput" class="hidden"><?= htmlspecialchars($providerList) ?></textarea>
            </div>
        </div>

        <!-- 환영 이메일 -->
        <div class="flex items-center justify-between py-4 border-b dark:border-zinc-700">
            <div>
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('members.settings.register.welcome_email') ?></h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('members.settings.register.welcome_email_desc') ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="member_welcome_email" class="sr-only peer" <?php echo ($memberSettings['member_welcome_email'] ?? '1') === '1' ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <!-- 회원가입 후 이동 URL -->
        <div class="py-4">
            <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('members.settings.register.redirect_url') ?></label>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3"><?= __('members.settings.register.redirect_url_desc') ?></p>
            <input type="text" name="member_register_redirect_url"
                   value="<?= htmlspecialchars($memberSettings['member_register_redirect_url'] ?? '') ?>"
                   placeholder="<?= __('members.settings.register.redirect_url_placeholder') ?>"
                   class="w-full md:w-1/2 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400"><?= __('members.settings.register.redirect_url_hint') ?></p>
        </div>

        <div class="flex justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('admin.buttons.save') ?>
            </button>
        </div>
    </form>
</div>

<script>
// 이메일 제공자 관리 JavaScript
function toggleEmailProviderList() {
    const mode = document.querySelector('input[name="member_email_provider_mode"]:checked').value;
    const section = document.getElementById('emailProviderListSection');
    if (mode === 'none') {
        section.classList.add('hidden');
    } else {
        section.classList.remove('hidden');
    }
}

function updateEmailProviderList() {
    const tags = document.querySelectorAll('#emailProviderTags span');
    const providers = [];
    tags.forEach(tag => {
        const text = tag.childNodes[0].textContent.trim();
        if (text) providers.push(text);
    });
    document.getElementById('emailProviderListInput').value = providers.join('\n');
}

function addEmailProvider() {
    const input = document.getElementById('newEmailProvider');
    const value = input.value.trim().toLowerCase();

    if (!value) return;

    // 도메인 형식 검증 (간단한 검증)
    if (!/^[a-z0-9.-]+\.[a-z]{2,}$/.test(value)) {
        alert('<?= __('members.settings.register.email_provider_invalid') ?>');
        return;
    }

    // 중복 체크
    const existing = document.querySelectorAll('#emailProviderTags span');
    for (const tag of existing) {
        if (tag.childNodes[0].textContent.trim() === value) {
            alert('<?= __('members.settings.register.email_provider_duplicate') ?>');
            return;
        }
    }

    // 태그 추가
    const tagsContainer = document.getElementById('emailProviderTags');
    const tag = document.createElement('span');
    tag.className = 'inline-flex items-center px-3 py-1 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-full text-sm';
    tag.innerHTML = `
        ${value}
        <button type="button" onclick="removeEmailProvider(this, '${value}')" class="ml-2 text-zinc-400 hover:text-red-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    `;
    tagsContainer.appendChild(tag);

    // 입력 필드 초기화
    input.value = '';

    // 숨겨진 textarea 업데이트
    updateEmailProviderList();
}

function removeEmailProvider(button, domain) {
    const tag = button.parentElement;
    tag.remove();
    updateEmailProviderList();
}

// Enter 키로 도메인 추가
document.getElementById('newEmailProvider')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addEmailProvider();
    }
});
</script>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_layout.php';
