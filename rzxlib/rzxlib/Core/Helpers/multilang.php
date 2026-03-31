<?php
/**
 * RezlyX 다국어 UI 헬퍼 함수
 *
 * index.php에서 자동 로드되므로 어디서든 사용 가능.
 * 개별 파일에서 include할 필요 없음.
 *
 * 함수 목록:
 *   rzx_multilang_btn($onclick, $title)   - 지구본 버튼 HTML
 *   rzx_multilang_btn_js()                - JS용 RZX_MULTILANG_BTN 함수 출력
 *   rzx_multilang_input($name, $value, $langKey, $opts) - input/textarea + 지구본 조합
 */

// 글로브 SVG — 아이콘 변경 시 이 한 줄만 수정
if (!defined('RZX_MULTILANG_SVG')) {
    define('RZX_MULTILANG_SVG', '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>');
}

if (!function_exists('rzx_multilang_btn')) {
    /**
     * 다국어 지구본 버튼 HTML 반환 (독립 버튼)
     *
     * @param string $onclick  onclick 핸들러 (JS 코드 문자열)
     * @param string $title    툴팁 텍스트 (기본: 다국어)
     * @return string          버튼 HTML
     */
    function rzx_multilang_btn(string $onclick, string $title = ''): string
    {
        if ($title === '') {
            $title = function_exists('__') ? __('admin.common.multilang') : '다국어';
        }
        $titleAttr = htmlspecialchars($title, ENT_QUOTES);
        return '<button type="button" onclick="' . $onclick . '" class="rzx-multilang-btn p-1.5 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/30 rounded-lg transition" title="' . $titleAttr . '">'
             . RZX_MULTILANG_SVG
             . '</button>';
    }
}

if (!function_exists('rzx_multilang_btn_js')) {
    /**
     * JS용 RZX_MULTILANG_BTN 함수 출력 (script 태그 포함)
     * 페이지 HTML 영역에서 한 번만 호출.
     */
    function rzx_multilang_btn_js(): string
    {
        $title = function_exists('__') ? __('admin.common.multilang') : '다국어';
        return '<script>
function RZX_MULTILANG_BTN(onclick, title) {
    title = title || ' . json_encode($title) . ';
    return \'<button type="button" onclick="\' + onclick.replace(/"/g, \'&quot;\') + \'" class="rzx-multilang-btn p-1.5 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/30 rounded-lg transition" title="\' + title + \'">\'
         + ' . json_encode(RZX_MULTILANG_SVG) . '
         + \'</button>\';
}
</script>';
    }
}

if (!function_exists('rzx_multilang_input')) {
    /**
     * 다국어 입력 필드 (input 또는 textarea) + 인라인 지구본 버튼
     *
     * 지구본 아이콘이 필드 내부 오른쪽에 배치됨.
     *
     * @param string $name       input name 및 id
     * @param string $value      현재 값
     * @param string $langKey    다국어 키 (예: 'board.1.title')
     * @param array  $opts       옵션:
     *   'type'        => 'text'|'textarea' (기본: 'text')
     *   'rows'        => textarea 행 수 (기본: 3)
     *   'required'    => bool (기본: false)
     *   'placeholder' => string
     *   'class'       => 추가 CSS 클래스
     *   'modal_type'  => 'text'|'editor' (기본: 없음) - multilang 모달 타입
     *   'attrs'       => 추가 HTML 속성 문자열
     */
    function rzx_multilang_input(string $name, string $value, string $langKey, array $opts = []): void
    {
        $type       = $opts['type'] ?? 'text';
        $rows       = (int)($opts['rows'] ?? 3);
        $required   = !empty($opts['required']) ? 'required' : '';
        $placeholder = isset($opts['placeholder']) ? htmlspecialchars($opts['placeholder'], ENT_QUOTES) : '';
        $extraClass = $opts['class'] ?? '';
        $modalType  = $opts['modal_type'] ?? '';
        $attrs      = $opts['attrs'] ?? '';
        // 현재 로케일 번역값이 있으면 input에 표시 (다국어 뷰)
        $displayValue = $value;
        if ($langKey && isset($GLOBALS['__pdo'])) {
            // $GLOBALS['__pdo']가 없으면 직접 로드 시도
        }
        if ($langKey) {
            try {
                global $config, $siteSettings;
                $_mlLocale = $config['locale'] ?? 'ko';
                $_mlDefLocale = $siteSettings['default_language'] ?? 'ko';
                $_mlChain = array_unique(array_filter([$_mlLocale, 'en', $_mlDefLocale]));
                // Translator 사용
                if (class_exists('\RzxLib\Core\I18n\Translator')) {
                    $trFile = defined('BASE_PATH') ? BASE_PATH . '/resources/lang/' . $_mlLocale . '/translations_cache.php' : '';
                    // DB에서 직접 조회
                    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
                    $dbName = $_ENV['DB_DATABASE'] ?? '';
                    $dbUser = $_ENV['DB_USERNAME'] ?? 'root';
                    $dbPass = $_ENV['DB_PASSWORD'] ?? '';
                    $dbPrefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
                    if ($dbName) {
                        static $_mlPdo = null;
                        if ($_mlPdo === null) {
                            try { $_mlPdo = new \PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass); } catch (\Exception $e) { $_mlPdo = false; }
                        }
                        if ($_mlPdo) {
                            $_mlPH = implode(',', array_fill(0, count($_mlChain), '?'));
                            $_mlStmt = $_mlPdo->prepare("SELECT locale, content FROM {$dbPrefix}translations WHERE lang_key = ? AND locale IN ({$_mlPH})");
                            $_mlStmt->execute(array_merge([$langKey], array_values($_mlChain)));
                            $_mlData = [];
                            while ($_ml = $_mlStmt->fetch(\PDO::FETCH_ASSOC)) { $_mlData[$_ml['locale']] = $_ml['content']; }
                            foreach ($_mlChain as $lc) {
                                if (!empty($_mlData[$lc])) { $displayValue = $_mlData[$lc]; break; }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {}
        }
        $safeValue  = htmlspecialchars($displayValue, ENT_QUOTES);

        // 모달 onclick
        $modalArg = $modalType ? ", '{$modalType}'" : '';
        $onclick  = "openMultilangModal('{$langKey}', '{$name}'{$modalArg})";

        // 공통 input 클래스
        $baseClass = 'w-full text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400';

        $btnTitle = function_exists('__') ? htmlspecialchars(__('admin.common.multilang'), ENT_QUOTES) : '다국어';

        if ($type === 'textarea') {
            echo '<div class="relative">';
            echo "<textarea name=\"{$name}\" id=\"{$name}\" rows=\"{$rows}\" {$required}"
               . ($placeholder ? " placeholder=\"{$placeholder}\"" : '')
               . " class=\"{$baseClass} px-3 py-2 pr-8 {$extraClass}\" {$attrs}>{$safeValue}</textarea>";
            echo '<button type="button" onclick="' . $onclick . '" class="absolute right-2 top-2 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition" title="' . $btnTitle . '">' . RZX_MULTILANG_SVG . '</button>';
            echo '</div>';
        } else {
            echo '<div class="relative">';
            echo "<input type=\"text\" name=\"{$name}\" id=\"{$name}\" value=\"{$safeValue}\" {$required}"
               . ($placeholder ? " placeholder=\"{$placeholder}\"" : '')
               . " class=\"{$baseClass} px-3 py-2 pr-8 {$extraClass}\" {$attrs}>";
            echo '<button type="button" onclick="' . $onclick . '" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition" title="' . $btnTitle . '">' . RZX_MULTILANG_SVG . '</button>';
            echo '</div>';
        }
    }
}
