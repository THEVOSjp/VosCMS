<?php
namespace RzxLib\Core\Modules;

/**
 * 게시판 확장 변수 렌더링 컴포넌트 모듈
 *
 * 사용법 (스킨에서):
 *   ExtraVarRenderer::renderInput($var, $value)   — write 폼용 input
 *   ExtraVarRenderer::renderDisplay($var, $value)  — read 표시용
 *   ExtraVarRenderer::renderAll($extraVars, $values, 'input')  — 전체 렌더링
 *   ExtraVarRenderer::renderAll($extraVars, $values, 'display') — 전체 표시
 */
class ExtraVarRenderer
{
    /**
     * 상태 값을 색상 배지로 렌더 (이슈/Q&A 처리 단계 등)
     * 색상은 원본(주로 ko) 값으로 매핑하므로 다국어 환경에서도 색상이 일관됨.
     * @param string $value 원본 값 (DB에 저장된 값, 색상 매핑 키)
     * @param string $sizeClass Tailwind size 클래스
     * @param string|null $displayLabel 표시 라벨 (null이면 $value 그대로 표시)
     */
    public static function renderStatusBadge(string $value, string $sizeClass = 'px-2 py-0.5 text-xs', ?string $displayLabel = null): string
    {
        if ($value === '') return '';
        $map = [
            '접수'    => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
            '확인 중' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            '진행 중' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
            '해결됨' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
            '닫힘'    => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400',
        ];
        $cls = $map[$value] ?? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300';
        $label = $displayLabel ?? $value;
        return '<span class="inline-flex items-center font-medium rounded-full ' . $sizeClass . ' ' . $cls . '">' . htmlspecialchars($label) . '</span>';
    }

    /**
     * 확장 변수 정의의 다국어 번역 적용 (title, description, options, default_value)
     * 현재 로케일 → en 폴백 → 원본 순. 옵션 라벨 매핑(원본→번역)을 $var['_option_labels']에 저장.
     */
    private static function applyDefTranslations(array $vars, int $boardId): array
    {
        if ($boardId <= 0 || empty($vars)) return $vars;
        if (!isset($GLOBALS['pdo'])) return $vars;

        $pdo = $GLOBALS['pdo'];
        $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
        $locale = class_exists('\\RzxLib\\Core\\I18n\\Translator')
            ? \RzxLib\Core\I18n\Translator::getLocale()
            : ($GLOBALS['currentLocale'] ?? 'ko');

        $keys = [];
        foreach ($vars as $v) {
            $vn = $v['var_name'] ?? '';
            if ($vn === '') continue;
            foreach (['title','description','options','default_value'] as $f) {
                $keys[] = "board_ev.{$boardId}.{$vn}.{$f}";
            }
        }
        if (empty($keys)) return $vars;

        try {
            $ph = implode(',', array_fill(0, count($keys), '?'));
            $sql = "SELECT lang_key, locale, content FROM {$prefix}translations
                    WHERE lang_key IN ({$ph}) AND locale IN (?, 'en')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge($keys, [$locale]));
            $tr = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                $tr[$r['lang_key']][$r['locale']] = $r['content'];
            }
        } catch (\PDOException $e) {
            return $vars;
        }

        foreach ($vars as &$v) {
            $vn = $v['var_name'] ?? '';
            if ($vn === '') continue;

            // 단순 텍스트 필드
            foreach (['title','description','default_value'] as $f) {
                $key = "board_ev.{$boardId}.{$vn}.{$f}";
                $val = $tr[$key][$locale] ?? $tr[$key]['en'] ?? null;
                if ($val !== null && $val !== '') $v[$f] = $val;
            }

            // 옵션 라벨 매핑 (원본 → 번역)
            $origOpts = self::parseOptions($v['options'] ?? null);
            if (!empty($origOpts)) {
                $optKey = "board_ev.{$boardId}.{$vn}.options";
                $trOptRaw = $tr[$optKey][$locale] ?? $tr[$optKey]['en'] ?? null;
                if ($trOptRaw !== null && $trOptRaw !== '') {
                    $trOpts = self::parseOptions($trOptRaw);
                    if (count($trOpts) === count($origOpts)) {
                        $v['_option_labels'] = array_combine($origOpts, $trOpts);
                    }
                }
            }
        }
        unset($v);

        return $vars;
    }

    /**
     * 단일 옵션 값의 번역 라벨 조회 (목록뷰 등에서 사용)
     * 캐시는 boardId+varName 기준. 없으면 원본 그대로 반환.
     */
    public static function getOptionLabel(int $boardId, string $varName, string $value): string
    {
        if ($value === '' || $boardId <= 0 || $varName === '') return $value;
        if (!isset($GLOBALS['pdo'])) return $value;

        static $cache = [];
        $cacheKey = $boardId . '.' . $varName;
        if (!isset($cache[$cacheKey])) {
            $pdo = $GLOBALS['pdo'];
            $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
            $locale = class_exists('\\RzxLib\\Core\\I18n\\Translator')
                ? \RzxLib\Core\I18n\Translator::getLocale()
                : ($GLOBALS['currentLocale'] ?? 'ko');

            try {
                // 원본 옵션 조회
                $orig = $pdo->prepare("SELECT options FROM {$prefix}board_extra_vars WHERE board_id = ? AND var_name = ?");
                $orig->execute([$boardId, $varName]);
                $origRaw = $orig->fetchColumn();
                if ($origRaw === false) { $cache[$cacheKey] = []; return $value; }
                $origOpts = self::parseOptions($origRaw);

                // 번역 옵션 조회
                $tr = $pdo->prepare("SELECT content FROM {$prefix}translations WHERE lang_key = ? AND locale = ?");
                $tr->execute(["board_ev.{$boardId}.{$varName}.options", $locale]);
                $trRaw = $tr->fetchColumn();
                if ($trRaw === false && $locale !== 'en') {
                    $tr->execute(["board_ev.{$boardId}.{$varName}.options", 'en']);
                    $trRaw = $tr->fetchColumn();
                }

                $map = [];
                if ($trRaw !== false && $trRaw !== '') {
                    $trOpts = self::parseOptions($trRaw);
                    if (count($trOpts) === count($origOpts)) {
                        $map = array_combine($origOpts, $trOpts);
                    }
                }
                $cache[$cacheKey] = $map;
            } catch (\PDOException $e) {
                $cache[$cacheKey] = [];
            }
        }

        return $cache[$cacheKey][$value] ?? $value;
    }

    /**
     * options 파싱 — JSON 배열이면 그대로, 아니면 개행 분리(레거시)
     */
    private static function parseOptions(?string $raw): array
    {
        if ($raw === null || $raw === '') return [];
        $trim = ltrim($raw);
        if ($trim !== '' && $trim[0] === '[') {
            $arr = json_decode($raw, true);
            if (is_array($arr)) return array_values(array_filter(array_map('strval', $arr), fn($x) => $x !== ''));
        }
        return array_filter(array_map('trim', explode("\n", $raw)));
    }

    /**
     * 확장 변수 목록 전체 렌더링
     * @param array $extraVars 확장 변수 정의 배열
     * @param array $values 저장된 값 (var_name => value)
     * @param string $mode 'input' 또는 'display'
     */
    public static function renderAll(array $extraVars, array $values = [], string $mode = 'input', int $boardId = 0): void
    {
        if (empty($extraVars)) return;

        // 다국어 번역 적용 (boardId가 주어진 경우)
        if ($boardId > 0) {
            $extraVars = self::applyDefTranslations($extraVars, $boardId);
        }

        echo '<div class="rzx-extra-vars space-y-4">';
        foreach ($extraVars as $var) {
            if (!($var['is_active'] ?? 1)) continue;
            $val = $values[$var['var_name']] ?? ($var['default_value'] ?? '');

            // permission 체크: 입력 모드에서 권한 없으면 hidden input으로 기존값 유지만 (UI 노출 X)
            if ($mode === 'input' && !self::canEdit($var)) {
                $name = 'extra_' . htmlspecialchars($var['var_name']);
                if ($val !== '' && $val !== null) {
                    echo '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars((string)$val) . '">';
                }
                continue;
            }

            if ($mode === 'input') {
                self::renderInput($var, $val);
            } else {
                self::renderDisplay($var, $val);
            }
        }
        echo '</div>';
    }

    /**
     * 현재 사용자가 이 확장변수를 편집할 수 있는지
     * permission: 'all'=누구나, 'member'=로그인 사용자, 'admin'=관리자
     */
    public static function canEdit(array $var): bool
    {
        $perm = $var['permission'] ?? 'all';
        if ($perm === 'admin')  return !empty($_SESSION['admin_id']);
        if ($perm === 'member') return !empty($_SESSION['user_id']) || !empty($_SESSION['admin_id']);
        return true; // 'all'
    }

    /**
     * 개별 확장 변수 — 입력 폼 (write용)
     */
    public static function renderInput(array $var, $value = ''): void
    {
        $name = 'extra_' . htmlspecialchars($var['var_name']);
        $id = 'ev_' . htmlspecialchars($var['var_name']);
        $title = htmlspecialchars($var['title']);
        $desc = htmlspecialchars($var['description'] ?? '');
        $required = !empty($var['is_required']);
        $reqAttr = $required ? 'required' : '';
        $reqMark = $required ? ' <span class="text-red-500">*</span>' : '';
        $cls = 'w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent';

        echo '<div class="rzx-ev-field">';
        echo '<label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1" for="' . $id . '">' . $title . $reqMark . '</label>';
        if ($desc) echo '<p class="text-xs text-zinc-400 mb-1">' . nl2br($desc) . '</p>';

        $safeVal = htmlspecialchars($value);

        switch ($var['var_type']) {
            case 'text':
            case 'text_multilang':
                echo '<input type="text" name="' . $name . '" id="' . $id . '" value="' . $safeVal . '" class="' . $cls . '" ' . $reqAttr . '>';
                break;

            case 'textarea':
            case 'textarea_multilang':
                echo '<textarea name="' . $name . '" id="' . $id . '" rows="3" class="' . $cls . ' resize-y" ' . $reqAttr . '>' . $safeVal . '</textarea>';
                break;

            case 'textarea_editor':
                echo '<textarea name="' . $name . '" id="' . $id . '" rows="5" class="' . $cls . ' resize-y" ' . $reqAttr . '>' . $safeVal . '</textarea>';
                break;

            case 'number':
                echo '<input type="number" name="' . $name . '" id="' . $id . '" value="' . $safeVal . '" class="' . $cls . '" ' . $reqAttr . '>';
                break;

            case 'select':
                $options = self::parseOptions($var['options'] ?? null);
                $labels = $var['_option_labels'] ?? [];
                echo '<select name="' . $name . '" id="' . $id . '" class="' . $cls . '" ' . $reqAttr . '>';
                echo '<option value="">-- ' . $title . ' --</option>';
                foreach ($options as $opt) {
                    $sel = ($value === $opt) ? ' selected' : '';
                    $label = $labels[$opt] ?? $opt;
                    echo '<option value="' . htmlspecialchars($opt) . '"' . $sel . '>' . htmlspecialchars($label) . '</option>';
                }
                echo '</select>';
                break;

            case 'checkbox':
                $options = self::parseOptions($var['options'] ?? null);
                $labels = $var['_option_labels'] ?? [];
                $checked = is_array($value) ? $value : ($value ? explode(',', $value) : []);
                echo '<div class="flex flex-wrap gap-3">';
                foreach ($options as $opt) {
                    $chk = in_array($opt, $checked) ? ' checked' : '';
                    $label = $labels[$opt] ?? $opt;
                    echo '<label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">';
                    echo '<input type="checkbox" name="' . $name . '[]" value="' . htmlspecialchars($opt) . '"' . $chk . ' class="rounded">';
                    echo htmlspecialchars($label) . '</label>';
                }
                echo '</div>';
                break;

            case 'radio':
                $options = self::parseOptions($var['options'] ?? null);
                $labels = $var['_option_labels'] ?? [];
                echo '<div class="flex flex-wrap gap-3">';
                foreach ($options as $opt) {
                    $chk = ($value === $opt) ? ' checked' : '';
                    $label = $labels[$opt] ?? $opt;
                    echo '<label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">';
                    echo '<input type="radio" name="' . $name . '" value="' . htmlspecialchars($opt) . '"' . $chk . ' class="rounded-full">';
                    echo htmlspecialchars($label) . '</label>';
                }
                echo '</div>';
                break;

            case 'date':
                echo '<input type="date" name="' . $name . '" id="' . $id . '" value="' . $safeVal . '" class="' . $cls . '" ' . $reqAttr . '>';
                break;

            case 'email':
                echo '<input type="email" name="' . $name . '" id="' . $id . '" value="' . $safeVal . '" class="' . $cls . '" ' . $reqAttr . '>';
                break;

            case 'url':
                echo '<input type="url" name="' . $name . '" id="' . $id . '" value="' . $safeVal . '" class="' . $cls . '" placeholder="https://" ' . $reqAttr . '>';
                break;

            case 'tel':
                echo '<input type="tel" name="' . $name . '" id="' . $id . '" value="' . $safeVal . '" class="' . $cls . '" ' . $reqAttr . '>';
                break;

            case 'color':
                echo '<input type="color" name="' . $name . '" id="' . $id . '" value="' . ($safeVal ?: '#000000') . '" class="w-12 h-10 border rounded-lg cursor-pointer">';
                break;

            case 'file':
                echo '<input type="file" name="' . $name . '" id="' . $id . '" class="text-sm text-zinc-700 dark:text-zinc-300">';
                if ($value) echo '<p class="text-xs text-zinc-400 mt-1">현재: ' . $safeVal . '</p>';
                break;

            default:
                echo '<input type="text" name="' . $name . '" id="' . $id . '" value="' . $safeVal . '" class="' . $cls . '">';
        }

        echo '</div>';
    }

    /**
     * 개별 확장 변수 — 표시용 (read용)
     */
    public static function renderDisplay(array $var, $value = ''): void
    {
        if ($value === '' || $value === null) return;

        $title = htmlspecialchars($var['title']);

        echo '<div class="rzx-ev-display flex items-baseline gap-3">';
        echo '<span class="text-sm font-medium text-zinc-500 dark:text-zinc-400 shrink-0">' . $title . '</span>';

        switch ($var['var_type']) {
            case 'url':
                $url = htmlspecialchars($value);
                echo '<a href="' . $url . '" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline break-all">' . $url . '</a>';
                break;

            case 'email':
                $email = htmlspecialchars($value);
                echo '<a href="mailto:' . $email . '" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">' . $email . '</a>';
                break;

            case 'color':
                echo '<span class="inline-flex items-center gap-1.5 text-sm text-zinc-700 dark:text-zinc-300">';
                echo '<span class="w-4 h-4 rounded border" style="background:' . htmlspecialchars($value) . '"></span>';
                echo htmlspecialchars($value) . '</span>';
                break;

            case 'checkbox':
                $items = is_array($value) ? $value : explode(',', $value);
                $labels = $var['_option_labels'] ?? [];
                echo '<div class="flex flex-wrap gap-1">';
                foreach ($items as $item) {
                    $item = trim($item);
                    if ($item === '') continue;
                    $lbl = $labels[$item] ?? $item;
                    echo '<span class="px-2 py-0.5 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs rounded">' . htmlspecialchars($lbl) . '</span>';
                }
                echo '</div>';
                break;

            case 'select':
            case 'radio':
                $labels = $var['_option_labels'] ?? [];
                $displayLabel = $labels[$value] ?? null;
                echo self::renderStatusBadge($value, 'px-2 py-0.5 text-xs', $displayLabel);
                break;

            case 'textarea':
            case 'textarea_multilang':
            case 'textarea_editor':
                echo '<div class="text-sm text-zinc-700 dark:text-zinc-300 break-all">' . nl2br(htmlspecialchars($value)) . '</div>';
                break;

            default:
                echo '<span class="text-sm text-zinc-700 dark:text-zinc-300">' . htmlspecialchars($value) . '</span>';
        }

        echo '</div>';
    }
}
