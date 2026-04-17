<?php
/**
 * Contact Form Widget - render.php
 * 비공개 1:1 문의 폼 (비회원 가능, CSRF 보호, 스팸 방지)
 */

$sTitle    = htmlspecialchars($renderer->t($config, 'title', ''));
$sSubtitle = htmlspecialchars($renderer->t($config, 'subtitle', ''));
$showCat   = ($config['show_category'] ?? 1) != 0;
$bgColor   = $config['bg_color'] ?? 'transparent';
$_locale   = $locale ?? (function_exists('current_locale') ? current_locale() : 'ko');

// 카테고리 파싱 (설정에서 다국어 텍스트 로드, 줄당 1개)
$_catRaw = $renderer->t($config, 'categories', '');
$_categories = array_filter(array_map('trim', explode("\n", $_catRaw)));

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$uid = 'contact-' . mt_rand(1000, 9999);

// 로그인 사용자 정보 자동 입력
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
$_isLoggedIn = \RzxLib\Core\Auth\Auth::check();
$_user = $_isLoggedIn ? \RzxLib\Core\Auth\Auth::user() : null;
$_userName = htmlspecialchars($_user['name'] ?? '');
$_userEmail = htmlspecialchars($_user['email'] ?? '');

// 다국어 레이블
$L = [
    'ko' => ['name'=>'이름','email'=>'이메일','category'=>'문의 분류','subject'=>'제목','message'=>'내용','submit'=>'문의하기','sending'=>'전송 중...','success'=>'문의가 접수되었습니다. 빠른 시일 내에 답변드리겠습니다.','error'=>'전송에 실패했습니다. 다시 시도해 주세요.','required'=>'필수 항목을 모두 입력해 주세요.','cat_general'=>'일반 문의','cat_business'=>'사업 제안·제휴','cat_license'=>'라이선스 문의','cat_bug'=>'버그 리포트','cat_security'=>'보안 취약점 신고','cat_other'=>'기타','name_ph'=>'홍길동','email_ph'=>'example@email.com','subject_ph'=>'문의 제목을 입력하세요','message_ph'=>'문의 내용을 자세히 작성해 주세요','privacy'=>'개인정보는 문의 답변 목적으로만 사용되며, 답변 완료 후 삭제됩니다.'],
    'en' => ['name'=>'Name','email'=>'Email','category'=>'Category','subject'=>'Subject','message'=>'Message','submit'=>'Submit','sending'=>'Sending...','success'=>'Your inquiry has been submitted. We will respond as soon as possible.','error'=>'Failed to send. Please try again.','required'=>'Please fill in all required fields.','cat_general'=>'General Inquiry','cat_business'=>'Business / Partnership','cat_license'=>'License Inquiry','cat_bug'=>'Bug Report','cat_security'=>'Security Vulnerability','cat_other'=>'Other','name_ph'=>'John Doe','email_ph'=>'example@email.com','subject_ph'=>'Enter the subject','message_ph'=>'Please describe your inquiry in detail','privacy'=>'Personal information is used only for responding to your inquiry and will be deleted after resolution.'],
    'ja' => ['name'=>'お名前','email'=>'メールアドレス','category'=>'お問い合わせ分類','subject'=>'件名','message'=>'内容','submit'=>'送信する','sending'=>'送信中...','success'=>'お問い合わせを受け付けました。できるだけ早くご返信いたします。','error'=>'送信に失敗しました。もう一度お試しください。','required'=>'必須項目をすべてご入力ください。','cat_general'=>'一般的なお問い合わせ','cat_business'=>'ビジネス提案・提携','cat_license'=>'ライセンスに関するお問い合わせ','cat_bug'=>'バグ報告','cat_security'=>'セキュリティ脆弱性の報告','cat_other'=>'その他','name_ph'=>'山田太郎','email_ph'=>'example@email.com','subject_ph'=>'件名を入力してください','message_ph'=>'お問い合わせ内容を詳しくご記入ください','privacy'=>'個人情報はお問い合わせへの回答目的のみに使用され、回答完了後に削除されます。'],
];
$t = $L[$_locale] ?? $L['en'] ?? $L['ko'];

// 배경 스타일
$sectionStyle = ($bgColor && $bgColor !== 'transparent') ? 'background-color:' . htmlspecialchars($bgColor) . ';' : '';

// === POST 처리 (API) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['_contact_form'])) {
    // ob_start() 내부에서 실행되므로 모든 버퍼를 비워야 JSON만 반환됨
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');

    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? 'general');
    $message = trim($_POST['message'] ?? '');
    $honeypot = trim($_POST['website'] ?? ''); // 스팸 방지

    if ($honeypot) { echo json_encode(['success' => false, 'message' => 'Spam detected']); exit; }
    if (!$name || !$email || !$message) { echo json_encode(['success' => false, 'message' => $t['required']]); exit; }

    try {
        $stmt = $pdo->prepare("INSERT INTO {$prefix}contact_messages (name, email, subject, category, message, ip_address, locale) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$name, $email, $subject, $category, $message, $_SERVER['REMOTE_ADDR'] ?? '', $_locale]);

        // 메일 알림 (공용 헬퍼 사용)
        $receiveEmail = $config['receive_email'] ?? '';
        if ($receiveEmail && function_exists('rzx_send_mail')) {
            $catLabel = $category;
            $mailSubject = "[VosCMS] [{$catLabel}] " . ($subject ?: $name);
            $mailBody = "Name: {$name}\nEmail: {$email}\nCategory: {$catLabel}\nSubject: {$subject}\n\n{$message}\n\n---\nIP: " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\nLocale: {$_locale}";
            rzx_send_mail($pdo, $receiveEmail, $mailSubject, $mailBody, [
                'reply_to' => $email,
                'reply_to_name' => $name,
                'content_type' => 'text/plain',
            ]);
        }

        echo json_encode(['success' => true, 'message' => $t['success']]);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => $t['error']]);
    }
    exit;
}

// === HTML ===
$html = '<section class="py-12"' . ($sectionStyle ? ' style="' . $sectionStyle . '"' : '') . '>';
$html .= '<div id="' . $uid . '" class="max-w-2xl mx-auto px-4 sm:px-6">';

// 헤더
if ($sTitle || $sSubtitle) {
    $html .= '<div class="text-center mb-8">';
    if ($sTitle) $html .= '<h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">' . $sTitle . '</h2>';
    if ($sSubtitle) $html .= '<div class="text-sm text-zinc-500 dark:text-zinc-400">' . $sSubtitle . '</div>';
    $html .= '</div>';
}

// 성공 메시지 (기본 숨김)
$html .= '<div id="' . $uid . '-success" class="hidden mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl text-center">';
$html .= '<svg class="w-8 h-8 text-green-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
$html .= '<p id="' . $uid . '-success-msg" class="text-sm font-medium text-green-700 dark:text-green-300"></p>';
$html .= '</div>';

// 폼
$html .= '<form id="' . $uid . '-form" class="space-y-5 bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 p-6 sm:p-8 shadow-sm">';
$html .= '<input type="hidden" name="_contact_form" value="' . $uid . '">';
// 허니팟 (스팸 방지)
$html .= '<div class="hidden"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>';

$inputCls = 'w-full px-4 py-3 border border-zinc-200 dark:border-zinc-600 rounded-xl bg-zinc-50 dark:bg-zinc-700/50 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition';
$labelCls = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5';

// 이름 + 이메일 (2열)
$html .= '<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">';
$html .= '<div><label class="' . $labelCls . '">' . $t['name'] . ' <span class="text-red-500">*</span></label>';
$html .= '<input type="text" name="name" value="' . $_userName . '" class="' . $inputCls . '" placeholder="' . $t['name_ph'] . '" required></div>';
$html .= '<div><label class="' . $labelCls . '">' . $t['email'] . ' <span class="text-red-500">*</span></label>';
$html .= '<input type="email" name="email" value="' . $_userEmail . '" class="' . $inputCls . '" placeholder="' . $t['email_ph'] . '" required></div>';
$html .= '</div>';

// 분류 (위젯 설정에서 편집 가능)
if ($showCat && !empty($_categories)) {
    $html .= '<div><label class="' . $labelCls . '">' . $t['category'] . '</label>';
    $html .= '<select name="category" class="' . $inputCls . '">';
    foreach ($_categories as $cat) {
        $html .= '<option value="' . htmlspecialchars($cat) . '">' . htmlspecialchars($cat) . '</option>';
    }
    $html .= '</select></div>';
}

// 제목
$html .= '<div><label class="' . $labelCls . '">' . $t['subject'] . '</label>';
$html .= '<input type="text" name="subject" class="' . $inputCls . '" placeholder="' . $t['subject_ph'] . '"></div>';

// 내용
$html .= '<div><label class="' . $labelCls . '">' . $t['message'] . ' <span class="text-red-500">*</span></label>';
$html .= '<textarea name="message" rows="6" class="' . $inputCls . ' resize-y" placeholder="' . $t['message_ph'] . '" required></textarea></div>';

// 개인정보 안내
$html .= '<div class="text-[11px] text-zinc-400 dark:text-zinc-500 flex items-start gap-1.5">';
$html .= '<svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>';
$html .= '<span>' . $t['privacy'] . '</span></div>';

// 에러 메시지
$html .= '<div id="' . $uid . '-error" class="hidden text-sm text-red-600 dark:text-red-400"></div>';

// 전송 버튼
$html .= '<button type="submit" id="' . $uid . '-btn" class="w-full py-3 bg-blue-600 text-white font-bold text-sm rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-600/25 transition flex items-center justify-center gap-2">';
$html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
$html .= $t['submit'] . '</button>';

$html .= '</form>';
$html .= '</div></section>';

// === JS ===
$html .= '<script>
(function() {
    var root = document.getElementById("' . $uid . '");
    if (!root) return;
    var form = document.getElementById("' . $uid . '-form");
    var btn = document.getElementById("' . $uid . '-btn");
    var errEl = document.getElementById("' . $uid . '-error");
    var successEl = document.getElementById("' . $uid . '-success");
    var successMsg = document.getElementById("' . $uid . '-success-msg");
    var btnText = btn.innerHTML;

    form.addEventListener("submit", function(e) {
        e.preventDefault();
        errEl.classList.add("hidden");
        btn.disabled = true;
        btn.innerHTML = \'<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>' . $t['sending'] . '\';

        fetch(window.location.href, {
            method: "POST",
            body: new FormData(form)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                form.classList.add("hidden");
                successMsg.textContent = data.message;
                successEl.classList.remove("hidden");
                successEl.scrollIntoView({ behavior: "smooth", block: "center" });
            } else {
                errEl.textContent = data.message;
                errEl.classList.remove("hidden");
                btn.disabled = false;
                btn.innerHTML = btnText;
            }
        })
        .catch(function() {
            errEl.textContent = "' . $t['error'] . '";
            errEl.classList.remove("hidden");
            btn.disabled = false;
            btn.innerHTML = btnText;
        });
    });
})();
</script>';

return $html;
