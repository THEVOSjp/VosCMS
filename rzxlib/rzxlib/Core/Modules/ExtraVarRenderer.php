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
     * 확장 변수 목록 전체 렌더링
     * @param array $extraVars 확장 변수 정의 배열
     * @param array $values 저장된 값 (var_name => value)
     * @param string $mode 'input' 또는 'display'
     */
    public static function renderAll(array $extraVars, array $values = [], string $mode = 'input'): void
    {
        if (empty($extraVars)) return;
        echo '<div class="rzx-extra-vars space-y-4">';
        foreach ($extraVars as $var) {
            if (!($var['is_active'] ?? 1)) continue;
            $val = $values[$var['var_name']] ?? ($var['default_value'] ?? '');
            if ($mode === 'input') {
                self::renderInput($var, $val);
            } else {
                self::renderDisplay($var, $val);
            }
        }
        echo '</div>';
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
                $options = array_filter(array_map('trim', explode("\n", $var['options'] ?? '')));
                echo '<select name="' . $name . '" id="' . $id . '" class="' . $cls . '" ' . $reqAttr . '>';
                echo '<option value="">-- ' . $title . ' --</option>';
                foreach ($options as $opt) {
                    $sel = ($value === $opt) ? ' selected' : '';
                    echo '<option value="' . htmlspecialchars($opt) . '"' . $sel . '>' . htmlspecialchars($opt) . '</option>';
                }
                echo '</select>';
                break;

            case 'checkbox':
                $options = array_filter(array_map('trim', explode("\n", $var['options'] ?? '')));
                $checked = is_array($value) ? $value : ($value ? explode(',', $value) : []);
                echo '<div class="flex flex-wrap gap-3">';
                foreach ($options as $opt) {
                    $chk = in_array($opt, $checked) ? ' checked' : '';
                    echo '<label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">';
                    echo '<input type="checkbox" name="' . $name . '[]" value="' . htmlspecialchars($opt) . '"' . $chk . ' class="rounded">';
                    echo htmlspecialchars($opt) . '</label>';
                }
                echo '</div>';
                break;

            case 'radio':
                $options = array_filter(array_map('trim', explode("\n", $var['options'] ?? '')));
                echo '<div class="flex flex-wrap gap-3">';
                foreach ($options as $opt) {
                    $chk = ($value === $opt) ? ' checked' : '';
                    echo '<label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">';
                    echo '<input type="radio" name="' . $name . '" value="' . htmlspecialchars($opt) . '"' . $chk . ' class="rounded-full">';
                    echo htmlspecialchars($opt) . '</label>';
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
                echo '<div class="flex flex-wrap gap-1">';
                foreach ($items as $item) {
                    if (trim($item)) echo '<span class="px-2 py-0.5 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs rounded">' . htmlspecialchars(trim($item)) . '</span>';
                }
                echo '</div>';
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
