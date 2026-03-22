<?php
namespace RzxLib\Core\Skin;

/**
 * SkinConfigRenderer - skin.json을 읽어 설정 폼을 자동 생성
 *
 * 사용법:
 *   $renderer = new SkinConfigRenderer($skinJsonPath, $savedConfig, $locale);
 *   $meta = $renderer->getMeta();        // 스킨 메타 정보
 *   $renderer->renderForm();             // 설정 폼 HTML 출력
 *   $renderer->getDefaults();            // 기본값 배열
 *
 * 지원 타입: text, textarea, checkbox, select, color, image, number
 */
class SkinConfigRenderer
{
    private array $schema = [];
    private array $vars = [];
    private array $saved = [];
    private string $locale;
    private string $baseUrl = '';

    public function __construct(string $jsonPath, array $savedConfig = [], string $locale = 'ko', string $baseUrl = '')
    {
        $this->locale = $locale;
        $this->saved = $savedConfig;
        $this->baseUrl = $baseUrl;

        if (file_exists($jsonPath)) {
            $this->schema = json_decode(file_get_contents($jsonPath), true) ?: [];
            $this->vars = $this->schema['vars'] ?? [];
        }
    }

    /** 다국어 값 추출 */
    private function t($value, string $fallback = ''): string
    {
        if (is_string($value)) return $value;
        if (is_array($value)) {
            return $value[$this->locale] ?? $value['en'] ?? $value['ko'] ?? reset($value) ?: $fallback;
        }
        return $fallback;
    }

    /** 스킨 메타 정보 */
    public function getMeta(): array
    {
        return [
            'title'       => $this->t($this->schema['title'] ?? ''),
            'description' => $this->t($this->schema['description'] ?? ''),
            'version'     => $this->schema['version'] ?? '',
            'date'        => $this->schema['date'] ?? '',
            'thumbnail'   => $this->schema['thumbnail'] ?? '',
            'author'      => $this->schema['author'] ?? [],
        ];
    }

    /** 기본값 배열 */
    public function getDefaults(): array
    {
        $defaults = [];
        foreach ($this->vars as $var) {
            $defaults[$var['name']] = $var['default'] ?? '';
        }
        return $defaults;
    }

    /** vars 정의가 있는지 */
    public function hasVars(): bool
    {
        return !empty($this->vars);
    }

    /** 저장된 값 또는 기본값 가져오기 */
    private function getValue(array $var)
    {
        $name = $var['name'];
        if (array_key_exists($name, $this->saved)) {
            return $this->saved[$name];
        }
        return $var['default'] ?? '';
    }

    /** 다국어 버튼 HTML */
    private function multilangBtn(string $name, string $inputId, string $modalType = ''): string
    {
        if (!defined('RZX_MULTILANG_SVG')) return '';
        $modalArg = $modalType ? ", '{$modalType}'" : '';
        return '<button type="button" onclick="openMultilangModal(\'skin_config.' . $name . '\', \'' . $inputId . '\'' . $modalArg . ')" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition">' . RZX_MULTILANG_SVG . '</button>';
    }

    private function multilangBtnTextarea(string $name, string $inputId): string
    {
        if (!defined('RZX_MULTILANG_SVG')) return '';
        return '<button type="button" onclick="openMultilangModal(\'skin_config.' . $name . '\', \'' . $inputId . '\', \'editor\')" class="absolute right-2 top-2 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition">' . RZX_MULTILANG_SVG . '</button>';
    }

    /** 설정 폼 HTML 출력 */
    public function renderForm(): void
    {
        // 탭 사용 여부 감지
        $hasTabs = false;
        foreach ($this->vars as $var) {
            if (!empty($var['tab'])) { $hasTabs = true; break; }
        }

        if ($hasTabs) {
            $this->renderFormWithTabs();
            return;
        }

        $this->renderFormFlat();
    }

    /** 탭 없는 기본 폼 */
    private function renderFormFlat(): void
    {
        $inp = 'w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200';
        $currentSection = null;

        foreach ($this->vars as $var) {
            $section = isset($var['section']) ? $this->t($var['section']) : null;
            if ($section && $section !== $currentSection) {
                if ($currentSection !== null) echo '</div>';
                echo '<div class="border-t border-zinc-200 dark:border-zinc-700 pt-5 mt-5">';
                echo '<h4 class="text-base font-semibold text-zinc-800 dark:text-zinc-200 mb-4">' . htmlspecialchars($section) . '</h4>';
                $currentSection = $section;
            } elseif ($section === null && $currentSection !== null) {
                echo '</div>';
                $currentSection = null;
            }

            $this->renderVar($var);
        }
        if ($currentSection !== null) echo '</div>';
        $this->renderDependsOnJs();
    }

    /** 탭 기반 폼 */
    private function renderFormWithTabs(): void
    {
        // 탭별로 vars 그룹핑
        $tabs = [];
        $tabOrder = [];
        foreach ($this->vars as $var) {
            $tabKey = !empty($var['tab']) ? $this->t($var['tab']) : '__default__';
            if (!isset($tabs[$tabKey])) {
                $tabs[$tabKey] = [];
                $tabOrder[] = $tabKey;
            }
            $tabs[$tabKey][] = $var;
        }

        // 탭 헤더
        $tabId = 'skinTabs_' . substr(md5(serialize($tabOrder)), 0, 8);
        echo '<div class="border-b border-zinc-200 dark:border-zinc-700 mb-4">';
        echo '<nav class="flex gap-1 overflow-x-auto -mb-px">';
        $first = true;
        foreach ($tabOrder as $i => $tabName) {
            $label = $tabName === '__default__' ? ($this->t($this->schema['title'] ?? '') ?: 'General') : $tabName;
            $active = $first ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400';
            echo '<button type="button" onclick="switchSkinTab(\'' . $tabId . '\',' . $i . ')" class="skinTab-' . $tabId . ' px-3 py-2 text-sm font-medium border-b-2 whitespace-nowrap transition ' . $active . '">' . htmlspecialchars($label) . '</button>';
            $first = false;
        }
        echo '</nav></div>';

        // 탭 콘텐츠
        $first = true;
        foreach ($tabOrder as $i => $tabName) {
            echo '<div class="skinTabPanel-' . $tabId . '" ' . ($first ? '' : 'style="display:none"') . '>';
            $currentSection = null;
            foreach ($tabs[$tabName] as $var) {
                $section = isset($var['section']) ? $this->t($var['section']) : null;
                if ($section && $section !== $currentSection) {
                    if ($currentSection !== null) echo '</div>';
                    echo '<div class="border-t border-zinc-200 dark:border-zinc-700 pt-5 mt-5">';
                    echo '<h4 class="text-base font-semibold text-zinc-800 dark:text-zinc-200 mb-4">' . htmlspecialchars($section) . '</h4>';
                    $currentSection = $section;
                } elseif ($section === null && $currentSection !== null) {
                    echo '</div>';
                    $currentSection = null;
                }
                $this->renderVar($var);
            }
            if ($currentSection !== null) echo '</div>';
            echo '</div>';
            $first = false;
        }

        // 탭 전환 JS
        echo '<script>';
        echo 'function switchSkinTab(id,idx){';
        echo '  document.querySelectorAll(".skinTab-"+id).forEach(function(t,i){';
        echo '    if(i===idx){t.classList.add("border-blue-500","text-blue-600","dark:text-blue-400");t.classList.remove("border-transparent","text-zinc-500");}';
        echo '    else{t.classList.remove("border-blue-500","text-blue-600","dark:text-blue-400");t.classList.add("border-transparent","text-zinc-500");}';
        echo '  });';
        echo '  document.querySelectorAll(".skinTabPanel-"+id).forEach(function(p,i){p.style.display=i===idx?"":"none";});';
        echo '}';
        echo '</script>';

        $this->renderDependsOnJs();
    }

    /** 단일 var 렌더링 */
    private function renderVar(array $var): void
    {
        $inp = 'w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200';
        $name  = $var['name'];
        $type  = $var['type'] ?? 'text';
        $title = $this->t($var['title'] ?? $name);
        $desc  = $this->t($var['description'] ?? '');
        $value = $this->getValue($var);
        $multilang = !empty($var['multilang']);
        $inputId = 'skin_cfg_' . $name;
        $dependsOn = $var['depends_on'] ?? null;
        $depAttr = $dependsOn ? ' data-depends-on="' . htmlspecialchars($dependsOn) . '"' : '';

        echo '<div class="mb-4 skin-var-row"' . $depAttr . '>';

        switch ($type) {
            case 'checkbox':
                $checked = $value ? 'checked' : '';
                echo '<label class="flex items-center justify-between">';
                echo '<span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">' . htmlspecialchars($title) . '</span>';
                echo '<label class="relative inline-flex items-center cursor-pointer">';
                echo '<input type="checkbox" name="skin_config[' . $name . ']" value="1" ' . $checked . ' class="sr-only peer skin-checkbox" data-name="' . $name . '">';
                echo '<div class="w-9 h-5 bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-blue-600 rounded-full peer after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"></div>';
                echo '</label></label>';
                break;
            case 'select':
                echo '<label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">' . htmlspecialchars($title) . '</label>';
                echo '<select name="skin_config[' . $name . ']" class="' . $inp . '">';
                foreach ($var['options'] ?? [] as $opt) {
                    $optVal = $opt['value'] ?? '';
                    $optLabel = $this->t($opt['label'] ?? $optVal);
                    $sel = ($value == $optVal) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($optVal) . '" ' . $sel . '>' . htmlspecialchars($optLabel) . '</option>';
                }
                echo '</select>';
                break;
            case 'radio':
                echo '<label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">' . htmlspecialchars($title) . '</label>';
                echo '<div class="flex flex-wrap gap-4">';
                foreach ($var['options'] ?? [] as $opt) {
                    $optVal = $opt['value'] ?? '';
                    $optLabel = $this->t($opt['label'] ?? $optVal);
                    $chk = ($value == $optVal) ? 'checked' : '';
                    echo '<label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">';
                    echo '<input type="radio" name="skin_config[' . $name . ']" value="' . htmlspecialchars($optVal) . '" ' . $chk . ' class="text-blue-600">';
                    echo htmlspecialchars($optLabel) . '</label>';
                }
                echo '</div>';
                break;
            case 'color':
                echo '<label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">' . htmlspecialchars($title) . '</label>';
                echo '<div class="flex items-center gap-3">';
                echo '<input type="color" name="skin_config[' . $name . ']" value="' . htmlspecialchars($value ?: '#000000') . '" class="w-10 h-9 rounded border border-zinc-300 dark:border-zinc-600 cursor-pointer">';
                echo '<span class="text-sm text-zinc-500">' . htmlspecialchars($value ?: '') . '</span>';
                echo '</div>';
                break;
            case 'image':
                echo '<label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">' . htmlspecialchars($title) . '</label>';
                if ($value) {
                    $imgSrc = (str_starts_with($value, 'http') ? $value : $this->baseUrl . $value);
                    echo '<div class="mb-2 flex items-start gap-3">';
                    echo '<img src="' . htmlspecialchars($imgSrc) . '" class="max-h-20 rounded border border-zinc-300 dark:border-zinc-600">';
                    echo '<button type="button" onclick="this.closest(\'.mb-2\').remove();document.querySelector(\'[name=\\\'skin_config[' . $name . ']\\\']]\').value=\'\';document.querySelector(\'[name=\\\'skin_delete[' . $name . ']\\\']]\').value=\'1\'" class="text-xs text-red-500 hover:text-red-700 shrink-0 mt-1">삭제</button>';
                    echo '</div>';
                }
                echo '<input type="file" name="skin_file_' . $name . '" accept="image/*" class="text-sm text-zinc-600 dark:text-zinc-400">';
                echo '<input type="hidden" name="skin_config[' . $name . ']" value="' . htmlspecialchars($value) . '">';
                echo '<input type="hidden" name="skin_delete[' . $name . ']" value="0">';
                break;
            case 'video':
                echo '<label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">' . htmlspecialchars($title) . '</label>';
                if ($value) {
                    $vidSrc = (str_starts_with($value, 'http') ? $value : $this->baseUrl . $value);
                    echo '<div class="mb-2 flex items-start gap-3">';
                    echo '<video src="' . htmlspecialchars($vidSrc) . '" class="max-h-24 rounded border border-zinc-300 dark:border-zinc-600" muted playsinline></video>';
                    echo '<div class="flex-1 min-w-0"><p class="text-xs text-zinc-400 break-all">' . htmlspecialchars($value) . '</p>';
                    echo '<button type="button" onclick="this.closest(\'.mb-2\').remove();document.querySelector(\'[name=\\\'skin_config[' . $name . ']\\\']]\').value=\'\';document.querySelector(\'[name=\\\'skin_delete[' . $name . ']\\\']]\').value=\'1\'" class="text-xs text-red-500 hover:text-red-700 mt-1">삭제</button>';
                    echo '</div></div>';
                }
                echo '<input type="file" name="skin_file_' . $name . '" accept="video/mp4,video/webm" class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">';
                echo '<input type="text" name="skin_config[' . $name . ']" value="' . htmlspecialchars($value) . '" placeholder="https://... (URL 직접 입력)" class="' . $inp . ' mt-1">';
                echo '<input type="hidden" name="skin_delete[' . $name . ']" value="0">';
                break;
            case 'number':
                echo '<label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">' . htmlspecialchars($title) . '</label>';
                $min = isset($var['min']) ? ' min="' . (int)$var['min'] . '"' : '';
                $max = isset($var['max']) ? ' max="' . (int)$var['max'] . '"' : '';
                echo '<input type="number" name="skin_config[' . $name . ']" value="' . htmlspecialchars($value) . '"' . $min . $max . ' class="w-32 ' . $inp . '">';
                break;
            case 'textarea':
                echo '<label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">' . htmlspecialchars($title) . '</label>';
                if ($multilang) {
                    echo '<div class="relative">';
                    echo '<textarea name="skin_config[' . $name . ']" id="' . $inputId . '" rows="4" class="' . $inp . ' pr-8 font-mono">' . htmlspecialchars($value) . '</textarea>';
                    echo $this->multilangBtnTextarea($name, $inputId);
                    echo '</div>';
                } else {
                    echo '<textarea name="skin_config[' . $name . ']" rows="4" class="' . $inp . ' font-mono">' . htmlspecialchars($value) . '</textarea>';
                }
                break;
            default: // text
                echo '<label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">' . htmlspecialchars($title) . '</label>';
                if ($multilang) {
                    echo '<div class="relative">';
                    echo '<input type="text" name="skin_config[' . $name . ']" id="' . $inputId . '" value="' . htmlspecialchars($value) . '" class="' . $inp . ' pr-8">';
                    echo $this->multilangBtn($name, $inputId);
                    echo '</div>';
                } else {
                    echo '<input type="text" name="skin_config[' . $name . ']" value="' . htmlspecialchars($value) . '" class="' . $inp . '">';
                }
                break;
        }

        if ($desc) {
            echo '<p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">' . htmlspecialchars($desc) . '</p>';
        }
        echo '</div>';
    }

    /** depends_on 자동 연동 JS */
    private function renderDependsOnJs(): void
    {
        if (!$this->hasDependencies()) return;
        echo '<script>';
        echo 'document.addEventListener("DOMContentLoaded",function(){';
        echo 'function skinDepToggle(parentName){';
        echo '  var cb=document.querySelector("[data-name=\""+parentName+"\"]");';
        echo '  var radios=document.querySelectorAll("[name=\"skin_config["+parentName+"]\"]");';
        echo '  var enabled=false;';
        echo '  if(cb&&cb.type==="checkbox"){enabled=cb.checked;}';
        echo '  else if(radios.length>0){var v="";radios.forEach(function(r){if(r.checked)v=r.value;});enabled=(v!==""&&v!=="none"&&v!=="0"&&v!=="disabled");}';
        echo '  document.querySelectorAll("[data-depends-on=\""+parentName+"\"]").forEach(function(row){';
        echo '    row.style.opacity=enabled?"1":"0.4";';
        echo '    row.style.pointerEvents=enabled?"auto":"none";';
        echo '  });';
        echo '}';
        foreach ($this->getDependencyParents() as $parent) {
            echo 'skinDepToggle("' . $parent . '");';
            echo 'var _cb=document.querySelector("[data-name=\"' . $parent . '\"]");';
            echo 'if(_cb)_cb.addEventListener("change",function(){skinDepToggle("' . $parent . '")});';
            echo 'document.querySelectorAll("[name=\"skin_config[' . $parent . ']\"]").forEach(function(r){r.addEventListener("change",function(){skinDepToggle("' . $parent . '")})});';
        }
        echo '});';
        echo '</script>';
    }

    /** depends_on이 있는 var가 존재하는지 */
    private function hasDependencies(): bool
    {
        foreach ($this->vars as $var) {
            if (!empty($var['depends_on'])) return true;
        }
        return false;
    }

    /** depends_on 부모 필드명 목록 (중복 제거) */
    private function getDependencyParents(): array
    {
        $parents = [];
        foreach ($this->vars as $var) {
            if (!empty($var['depends_on'])) {
                $parents[$var['depends_on']] = true;
            }
        }
        return array_keys($parents);
    }
}
