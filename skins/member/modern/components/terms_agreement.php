<?php
/**
 * RezlyX Member Skin - Modern
 * 약관 동의 컴포넌트 (모던 스타일)
 *
 * 사용 가능한 변수:
 * - $termsSettings: 약관 설정 배열 (두 가지 형식 지원)
 *   형식 1 (원시): member_term_1_title, member_term_1_content, member_term_1_consent 등
 *   형식 2 (파싱됨): [['id' => 1, 'title' => '...', 'content' => '...', 'required' => true], ...]
 * - $translations: 번역 데이터
 * - $errors: 폼 에러 메시지 배열 (약관 관련)
 */

// 약관 데이터 파싱 (두 가지 형식 지원)
$terms = [];

// 이미 파싱된 배열 형식인지 확인 (AuthController에서 전달된 경우)
if (is_array($termsSettings) && !empty($termsSettings) && isset($termsSettings[0]['id'])) {
    // 이미 파싱된 형식
    $terms = $termsSettings;
} else {
    // 원시 설정 형식 파싱 (다국어 지원)
    $currentLocale = function_exists('current_locale') ? current_locale() : 'ko';
    for ($i = 1; $i <= 5; $i++) {
        $consent = $termsSettings["member_term_{$i}_consent"] ?? 'disabled';

        // 비활성화된 약관은 건너뛰기
        if ($consent === 'disabled') {
            continue;
        }

        // db_trans()를 사용하여 번역 조회, 없으면 기본 설정값 사용
        $defaultTitle = $termsSettings["member_term_{$i}_title"] ?? '';
        $defaultContent = $termsSettings["member_term_{$i}_content"] ?? '';

        if (function_exists('db_trans')) {
            $title = db_trans("term.{$i}.title", $currentLocale, $defaultTitle);
            $content = db_trans("term.{$i}.content", $currentLocale, $defaultContent);
        } else {
            $title = $defaultTitle;
            $content = $defaultContent;
        }

        if (empty($title)) {
            continue;
        }

        $terms[] = [
            'id' => $i,
            'title' => $title,
            'content' => $content,
            'consent' => $consent,
            'required' => ($consent === 'required'),
        ];
    }
}

// 약관이 없으면 표시하지 않음
if (empty($terms)) {
    return;
}

$hasRequiredTerms = false;
foreach ($terms as $term) {
    if ($term['required']) {
        $hasRequiredTerms = true;
        break;
    }
}
?>

<!-- Terms Agreement Section (Modern Style) -->
<div class="terms-agreement-section mt-6">
    <!-- 섹션 헤더 -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                <?= __('auth.terms.title') ?>
            </h3>
            <p class="text-sm text-gray-500 dark:text-zinc-400 mt-0.5">
                <?= __('auth.terms.subtitle') ?>
            </p>
        </div>
        <?php if ($hasRequiredTerms): ?>
        <span class="text-xs text-red-500 dark:text-red-400 bg-red-50 dark:bg-red-900/30 px-2 py-1 rounded-full">
            * <?= __('auth.terms.required_mark') ?>
        </span>
        <?php endif; ?>
    </div>

    <!-- 에러 메시지 -->
    <?php if (!empty($errors['terms'])): ?>
    <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl flex items-center">
        <svg class="w-5 h-5 text-red-500 dark:text-red-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm text-red-700 dark:text-red-300"><?= htmlspecialchars($errors['terms']) ?></p>
    </div>
    <?php endif; ?>

    <!-- 전체 동의 체크박스 (2개 이상일 때만) -->
    <?php if (count($terms) > 1): ?>
    <div class="mb-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-100 dark:border-blue-800/50">
        <label class="flex items-center cursor-pointer group">
            <div class="relative">
                <input type="checkbox" id="agreeAllTerms" name="agree_all"
                       class="sr-only peer"
                       onchange="toggleAllTermsModern(this.checked)">
                <div class="w-6 h-6 border-2 border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700
                            peer-checked:bg-blue-600 peer-checked:border-blue-600 transition-all duration-200
                            peer-focus:ring-2 peer-focus:ring-blue-500 peer-focus:ring-offset-2">
                    <svg class="w-4 h-4 text-white absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <span class="ml-3 text-base font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                <?= __('auth.terms.agree_all') ?>
            </span>
        </label>
    </div>
    <?php endif; ?>

    <!-- 개별 약관 목록 -->
    <div class="space-y-3">
        <?php foreach ($terms as $index => $term): ?>
        <div class="group border border-gray-200 dark:border-zinc-700 rounded-xl overflow-hidden bg-white dark:bg-zinc-800/50 hover:border-blue-300 dark:hover:border-blue-700 transition-colors duration-200">
            <!-- 약관 헤더 -->
            <div class="p-4">
                <div class="flex items-start">
                    <!-- 커스텀 체크박스 -->
                    <div class="relative flex-shrink-0 mt-0.5">
                        <input type="checkbox"
                               id="term_<?= $term['id'] ?>"
                               name="terms[<?= $term['id'] ?>]"
                               value="1"
                               class="term-checkbox-modern sr-only peer"
                               <?= $term['required'] ? 'required' : '' ?>
                               data-required="<?= $term['required'] ? 'true' : 'false' ?>"
                               onchange="checkAllTermsStatusModern()">
                        <label for="term_<?= $term['id'] ?>"
                               class="flex items-center justify-center w-5 h-5 border-2 border-gray-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 cursor-pointer
                                      peer-checked:bg-blue-600 peer-checked:border-blue-600 transition-all duration-200
                                      peer-focus:ring-2 peer-focus:ring-blue-500 peer-focus:ring-offset-2">
                            <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </label>
                    </div>

                    <!-- 약관 정보 -->
                    <div class="ml-3 flex-1 min-w-0">
                        <label for="term_<?= $term['id'] ?>" class="flex items-center flex-wrap gap-2 cursor-pointer">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                <?= htmlspecialchars($term['title']) ?>
                            </span>
                            <?php if ($term['required']): ?>
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 rounded-full">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <?= __('auth.terms.required') ?>
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-zinc-400 rounded-full">
                                <?= __('auth.terms.optional') ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($term['isFallback'])): ?>
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 rounded-full animate-pulse">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <?= __('auth.terms.translation_pending') ?>
                            </span>
                            <?php endif; ?>
                        </label>
                    </div>

                    <!-- 펼치기/접기 버튼 -->
                    <?php if (!empty($term['content'])): ?>
                    <button type="button"
                            onclick="toggleTermContentModern(<?= $term['id'] ?>)"
                            class="ml-2 p-1.5 text-gray-400 hover:text-blue-600 dark:text-zinc-500 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700 transition-all duration-200">
                        <svg id="termIcon_<?= $term['id'] ?>" class="w-5 h-5 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 약관 내용 (접힌 상태로 시작) -->
            <?php if (!empty($term['content'])): ?>
            <div id="termContent_<?= $term['id'] ?>" class="hidden">
                <div class="px-4 pb-4">
                    <div class="terms-content p-4 bg-gray-50 dark:bg-zinc-900/50 rounded-lg max-h-48 overflow-y-auto text-sm
                                scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-zinc-600 scrollbar-track-transparent">
                        <?= $term['content'] ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 약관 안내 문구 -->
    <p class="mt-4 text-xs text-gray-500 dark:text-zinc-500 text-center">
        <?= __('auth.terms.notice') ?>
    </p>
</div>

<script>
// Modern 스킨용 전체 동의 토글
function toggleAllTermsModern(checked) {
    var checkboxes = document.querySelectorAll('.term-checkbox-modern');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = checked;
        // 시각적 업데이트를 위한 이벤트 트리거
        checkbox.dispatchEvent(new Event('change'));
    });
    console.log('Modern: All terms toggled:', checked);
}

// Modern 스킨용 개별 체크박스 상태 확인
function checkAllTermsStatusModern() {
    var checkboxes = document.querySelectorAll('.term-checkbox-modern');
    var allChecked = true;
    checkboxes.forEach(function(checkbox) {
        if (!checkbox.checked) {
            allChecked = false;
        }
    });

    var agreeAllCheckbox = document.getElementById('agreeAllTerms');
    if (agreeAllCheckbox) {
        agreeAllCheckbox.checked = allChecked;
    }
    console.log('Modern: Terms status checked, all agreed:', allChecked);
}

// Modern 스킨용 약관 내용 토글
function toggleTermContentModern(termId) {
    var content = document.getElementById('termContent_' + termId);
    var icon = document.getElementById('termIcon_' + termId);

    if (content && icon) {
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.classList.add('rotate-180');
        } else {
            content.classList.add('hidden');
            icon.classList.remove('rotate-180');
        }
        console.log('Modern: Term content toggled:', termId);
    }
}

// Modern 스킨용 폼 제출 전 필수 약관 확인
function validateTermsAgreement() {
    var requiredTerms = document.querySelectorAll('.term-checkbox-modern[data-required="true"]');
    var allRequiredChecked = true;
    var firstUnchecked = null;

    requiredTerms.forEach(function(checkbox) {
        var container = checkbox.closest('.group');
        if (!checkbox.checked) {
            allRequiredChecked = false;
            if (container) {
                container.classList.add('border-red-500', 'dark:border-red-500', 'bg-red-50/50', 'dark:bg-red-900/10');
            }
            if (!firstUnchecked) {
                firstUnchecked = checkbox;
            }
        } else {
            if (container) {
                container.classList.remove('border-red-500', 'dark:border-red-500', 'bg-red-50/50', 'dark:bg-red-900/10');
            }
        }
    });

    if (!allRequiredChecked) {
        console.log('Modern: Required terms not agreed');
        if (firstUnchecked) {
            firstUnchecked.closest('.group').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        alert('<?= __('auth.terms.required_alert') ?>');
        return false;
    }

    return true;
}
</script>

<style>
/* 약관 내용 Typography 스타일 */
.terms-content {
    font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    line-height: 1.7;
}
.terms-content h1 {
    font-size: 1.5em;
    font-weight: 700;
    margin: 1em 0 0.5em 0;
    color: #1f2937;
}
.terms-content h2 {
    font-size: 1.25em;
    font-weight: 600;
    margin: 1em 0 0.5em 0;
    color: #374151;
}
.terms-content h3 {
    font-size: 1.1em;
    font-weight: 600;
    margin: 0.8em 0 0.4em 0;
    color: #4b5563;
}
.terms-content p {
    margin: 0.5em 0;
    color: #4b5563;
}
.terms-content ul, .terms-content ol {
    margin: 0.5em 0;
    padding-left: 1.5em;
}
.terms-content li {
    margin: 0.25em 0;
    color: #4b5563;
}
.terms-content blockquote {
    margin: 0.5em 0;
    padding-left: 1em;
    border-left: 3px solid #d1d5db;
    color: #6b7280;
}
.terms-content span[style*="font-weight: bolder"],
.terms-content span[style*="font-weight: bold"],
.terms-content strong, .terms-content b {
    font-weight: 600;
    color: #1f2937;
}

/* 다크모드 약관 내용 스타일 */
.dark .terms-content h1 {
    color: #f3f4f6;
}
.dark .terms-content h2 {
    color: #e5e7eb;
}
.dark .terms-content h3 {
    color: #d1d5db;
}
.dark .terms-content p,
.dark .terms-content li {
    color: #9ca3af;
}
.dark .terms-content blockquote {
    border-left-color: #4b5563;
    color: #9ca3af;
}
.dark .terms-content span[style*="font-weight: bolder"],
.dark .terms-content span[style*="font-weight: bold"],
.dark .terms-content strong, .dark .terms-content b {
    color: #e5e7eb;
}

/* 커스텀 스크롤바 스타일 */
.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: #d1d5db;
    border-radius: 3px;
}
.dark .scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: #52525b;
}

/* 체크박스 체크 아이콘 표시 */
.peer:checked ~ label svg {
    opacity: 1;
}
</style>
