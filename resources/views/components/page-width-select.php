<?php
/**
 * 페이지 너비 선택 컴포넌트
 *
 * 사용법:
 *   $__pageWidth = ['id' => 'cfgPageWidth', 'value' => '5xl', 'name' => 'page_width'];
 *   include BASE_PATH . '/resources/views/components/page-width-select.php';
 *
 * 파라미터:
 *   id    - input hidden ID (기본: 'cfgPageWidth')
 *   value - 현재 선택 값 (기본: '5xl')
 *   name  - form name 속성 (선택, hidden input에 사용)
 */
$_pw_id    = $__pageWidth['id'] ?? 'cfgPageWidth';
$_pw_value = $__pageWidth['value'] ?? '5xl';
$_pw_name  = $__pageWidth['name'] ?? '';

$_pw_options = [
    '2xl' => ['label' => '2xl',  'desc' => '672px',  'bar' => '25%'],
    '3xl' => ['label' => '3xl',  'desc' => '768px',  'bar' => '35%'],
    '4xl' => ['label' => '4xl',  'desc' => '896px',  'bar' => '45%'],
    '5xl' => ['label' => '5xl',  'desc' => '1024px', 'bar' => '55%'],
    '6xl' => ['label' => '6xl',  'desc' => '1152px', 'bar' => '70%'],
    '7xl' => ['label' => '7xl',  'desc' => '1280px', 'bar' => '85%'],
    'full' => ['label' => 'Full', 'desc' => '100%',   'bar' => '100%'],
];
?>
<input type="hidden" id="<?= $_pw_id ?>" <?= $_pw_name ? 'name="' . htmlspecialchars($_pw_name) . '"' : '' ?> value="<?= htmlspecialchars($_pw_value) ?>">
<div class="grid grid-cols-7 gap-2" id="<?= $_pw_id ?>_wrap">
    <?php foreach ($_pw_options as $wk => $wo):
        $isSelected = $_pw_value === $wk;
    ?>
    <button type="button"
            onclick="document.getElementById('<?= $_pw_id ?>').value='<?= $wk ?>';document.querySelectorAll('#<?= $_pw_id ?>_wrap button').forEach(b=>{b.classList.remove('border-blue-500','bg-blue-50','dark:bg-blue-900/30');b.classList.add('border-zinc-200','dark:border-zinc-600')});this.classList.remove('border-zinc-200','dark:border-zinc-600');this.classList.add('border-blue-500','bg-blue-50','dark:bg-blue-900/30')"
            class="flex flex-col items-center gap-1.5 p-2.5 rounded-xl border-2 transition-all cursor-pointer <?= $isSelected ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'border-zinc-200 dark:border-zinc-600 hover:border-zinc-400' ?>">
        <div class="w-full h-5 bg-zinc-100 dark:bg-zinc-700 rounded-md overflow-hidden flex items-center justify-center">
            <div class="h-3 bg-blue-400 dark:bg-blue-500 rounded-sm" style="width:<?= $wo['bar'] ?>"></div>
        </div>
        <span class="text-xs font-bold <?= $isSelected ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-700 dark:text-zinc-300' ?>"><?= $wo['label'] ?></span>
        <span class="text-[10px] <?= $isSelected ? 'text-blue-500' : 'text-zinc-400' ?>"><?= $wo['desc'] ?></span>
    </button>
    <?php endforeach; ?>
</div>
<p class="text-xs text-zinc-400 mt-2"><?= __('site.pages.cfg.page_width_desc') ?? '페이지 콘텐츠 영역의 최대 너비를 설정합니다.' ?></p>
