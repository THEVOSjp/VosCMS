<?php
/**
 * 사이트 분류 (업종) 선택 공통 컴포넌트
 *
 * 사용법: include BASE_PATH . '/resources/views/components/site-category-select.php';
 * 선택 변수:
 *   $categoryFieldName  — input name (기본: 'site_category')
 *   $categorySelected   — 현재 선택값 (기본: '')
 *   $categoryInputClass — 추가 CSS 클래스 (기본: '')
 */
$_catFieldName = $categoryFieldName ?? 'site_category';
$_catSelected = $categorySelected ?? '';
$_catInputClass = $categoryInputClass ?? '';
$_catKeys = ['beauty_salon', 'nail_salon', 'skincare', 'massage', 'hospital', 'dental', 'studio', 'restaurant', 'accommodation', 'sports', 'education', 'consulting', 'pet', 'car', 'corporate', 'shopping', 'law_firm', 'accounting', 'real_estate', 'it_tech', 'media', 'nonprofit', 'government', 'community', 'portfolio', 'other'];
?>
<select name="<?= $_catFieldName ?>" class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 text-sm <?= $_catInputClass ?>">
    <option value=""><?= __('settings.site.category_placeholder') ?? '-- 업종을 선택하세요 --' ?></option>
    <?php foreach ($_catKeys as $_ck): ?>
    <option value="<?= $_ck ?>" <?= $_catSelected === $_ck ? 'selected' : '' ?>><?= __('settings.site.categories.' . $_ck) ?></option>
    <?php endforeach; ?>
</select>
