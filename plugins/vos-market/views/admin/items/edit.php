<?php
include __DIR__ . '/../_head.php';
$db = mkt_pdo(); $pfx = $_mktPrefix;
$id = (int)($_GET['id'] ?? 0);
$item = null; $versions = [];

if ($id) {
    $st = $db->prepare("SELECT * FROM {$pfx}mkt_items WHERE id=?");
    $st->execute([$id]); $item = $st->fetch();
    if (!$item) { echo '<p class="p-6 text-red-500">아이템을 찾을 수 없습니다.</p>'; include __DIR__.'/../_foot.php'; return; }
    $sv = $db->prepare("SELECT * FROM {$pfx}mkt_item_versions WHERE item_id=? ORDER BY released_at DESC");
    $sv->execute([$id]); $versions = $sv->fetchAll();
}
$isEdit = (bool)$item;
$_editNameEn = $isEdit ? (json_decode($item['name'] ?? '{}', true)['en'] ?? '') : '';
$pageHeaderTitle = $isEdit
    ? ((__('marketplace.admin_item_edit') ?: '아이템 편집') . ($_editNameEn ? ' · ' . htmlspecialchars($_editNameEn) : ''))
    : (__('marketplace.admin_item_create') ?: '새 아이템 등록');
$csrf = $_SESSION['_csrf'] ?? '';
$adminUrl = $_mktAdmin;
$locale   = $_mktLocale;

$partners = $db->query("SELECT id,display_name,email FROM {$pfx}mkt_partners WHERE status='active' ORDER BY display_name")->fetchAll();

// JSON 필드 파싱
$eName    = $item ? (json_decode($item['name']??'{}',true)?:[]) : [];
$eShort   = $item ? (json_decode($item['short_description']??'{}',true)?:[]) : [];
$eDesc    = $item ? (json_decode($item['description']??'{}',true)?:[]) : [];
$eTags    = $item ? (json_decode($item['tags']??'[]',true)?:[]) : [];
$eReq     = $item ? (json_decode($item['requires_plugins']??'[]',true)?:[]) : [];

// tags: 구조 감지 (배열 vs 로케일맵)
$eTagsStrMap = [];
if (!empty($eTags)) {
    $first = reset($eTags);
    if (is_array($first)) {
        foreach ($eTags as $lc => $arr) $eTagsStrMap[$lc] = implode(', ', (array)$arr);
    } else {
        $eTagsStrMap['en'] = implode(', ', array_map('strval', $eTags));
    }
}
$currentTagsDisplay = $eTagsStrMap[$locale] ?? $eTagsStrMap['en'] ?? '';

$_inp  = 'w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white bg-white dark:bg-zinc-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition';
$_lbl  = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5';
$_hint = 'text-xs text-zinc-400 dark:text-zinc-500 mt-1';
$_card = 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6';

$_licenses = ['GPL-2.0'=>'GPL v2','GPL-3.0'=>'GPL v3','LGPL-2.0'=>'LGPL v2','LGPL-3.0'=>'LGPL v3','BSD'=>'BSD','MIT'=>'MIT','CC'=>'Creative Commons','PD'=>'Public Domain',
    'proprietary' => __('marketplace.sf_license_proprietary') ?: '독점',
    'other'       => __('marketplace.sf_license_other')       ?: '기타',
];

// multilang 헬퍼
function _mpLangAttr(array $map): string {
    return htmlspecialchars(json_encode($map ?: new stdClass(), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), ENT_QUOTES);
}

require_once __DIR__ . '/../components/multilang-button.php';
?>
<div class="flex items-center gap-4 mb-6">
    <a href="<?= $adminUrl ?>/market/items" class="text-sm text-zinc-500 hover:text-indigo-600 transition">← <?= __('marketplace.sf_back_list') ?: '목록' ?></a>
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= $pageHeaderTitle ?></h1>
</div>

<div id="result" class="hidden mb-4 p-4 rounded-lg text-sm"></div>

<!-- 탭 네비게이션 -->
<div class="border-b border-zinc-200 dark:border-zinc-700 mb-6">
    <nav class="flex gap-0" id="submitTabs">
        <button type="button" data-tab="basic" class="tab-btn px-5 py-3 text-sm font-semibold border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400 transition-colors">
            <span class="inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <?= __('marketplace.sf_tab_basic') ?: '기본 정보' ?>
            </span>
        </button>
        <button type="button" data-tab="release" class="tab-btn px-5 py-3 text-sm font-semibold border-b-2 border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 transition-colors">
            <span class="inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <?= __('marketplace.sf_tab_release') ?: '배포 패키지' ?>
            </span>
        </button>
        <button type="button" data-tab="sales" class="tab-btn px-5 py-3 text-sm font-semibold border-b-2 border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 transition-colors">
            <span class="inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= __('marketplace.sf_tab_sales') ?: '판매 설정' ?>
            </span>
        </button>
    </nav>
</div>

<!-- Product Key 표시 -->
<div class="max-w-4xl mb-5">
<?php if ($isEdit && !empty($item['product_key'])): ?>
    <div class="flex items-center gap-3 px-4 py-3 bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl">
        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 shrink-0">Product Key</span>
        <code id="productKeyDisplay" class="flex-1 font-mono text-sm text-zinc-800 dark:text-zinc-200 truncate">
            <?= htmlspecialchars($item['product_key']) ?>
        </code>
        <button type="button" onclick="copyProductKey()"
                class="shrink-0 px-3 py-1 text-xs font-medium bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:border-indigo-400 hover:text-indigo-600 transition">
            Copy
        </button>
    </div>
<?php else: ?>
    <div class="flex items-center gap-3 px-4 py-3 bg-zinc-50 dark:bg-zinc-800 border border-dashed border-zinc-300 dark:border-zinc-600 rounded-xl">
        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 shrink-0">Product Key</span>
        <span class="flex-1 font-mono text-sm text-zinc-400 dark:text-zinc-500 italic"><?= __('marketplace.sf_product_key_pending') ?: '저장 시 자동 생성됩니다' ?></span>
    </div>
<?php endif; ?>
</div>

<form id="submitForm" enctype="multipart/form-data" class="max-w-4xl">
<input type="hidden" name="action" value="submit_item">
<input type="hidden" name="_token" value="<?= htmlspecialchars($csrf) ?>">
<?php if ($isEdit): ?><input type="hidden" name="item_id" value="<?= $item['id'] ?>"><?php endif; ?>

<!-- ======== 탭1: 기본 정보 ======== -->
<div id="tab-basic" class="tab-content space-y-6">

    <!-- 유형·라이선스 -->
    <div class="<?= $_card ?>">
        <h2 class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4"><?= __('marketplace.sf_type_license') ?: '유형 및 라이선스' ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="<?= $_lbl ?>"><?= __('marketplace.sf_item_type') ?: '아이템 유형' ?> <span class="text-red-500">*</span></label>
                <select name="item_type" required class="<?= $_inp ?>">
                    <option value="plugin" <?= ($item['type']??'')==='plugin'?'selected':'' ?>><?= __('marketplace.sf_type_plugin') ?: '플러그인' ?></option>
                    <option value="widget" <?= ($item['type']??'')==='widget'?'selected':'' ?>><?= __('marketplace.sf_type_widget') ?: '위젯' ?></option>
                    <option value="theme"  <?= ($item['type']??'')==='theme' ?'selected':'' ?>><?= __('marketplace.sf_type_theme')  ?: '테마' ?></option>
                    <option value="skin"   <?= ($item['type']??'')==='skin'  ?'selected':'' ?>><?= __('marketplace.sf_type_skin')   ?: '스킨' ?></option>
                </select>
            </div>
            <div>
                <label class="<?= $_lbl ?>"><?= __('marketplace.sf_license') ?: '라이선스' ?></label>
                <select name="license" class="<?= $_inp ?>">
                    <option value=""><?= __('marketplace.sf_license_select') ?: '선택' ?></option>
                    <?php foreach ($_licenses as $lv => $ll): ?>
                    <option value="<?= $lv ?>" <?= ($item['license']??'')===$lv?'selected':'' ?>><?= $ll ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="<?= $_lbl ?>"><?= __('marketplace.sf_partner') ?: '파트너' ?></label>
                <select name="partner_id" class="<?= $_inp ?>">
                    <option value=""><?= __('marketplace.sf_partner_none') ?: '없음 (직접 관리)' ?></option>
                    <?php foreach ($partners as $pt): ?>
                    <option value="<?= $pt['id'] ?>" <?= ($item['partner_id']??'')==$pt['id']?'selected':'' ?>><?= htmlspecialchars($pt['display_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="<?= $_lbl ?>"><?= __('marketplace.sf_item_status') ?: '상태' ?></label>
                <select name="status" class="<?= $_inp ?>">
                    <option value="active"    <?= ($item['status']??'')==='active'   ?'selected':'' ?>><?= __('marketplace.sf_status_active')    ?: '활성' ?></option>
                    <option value="pending"   <?= ($item['status']??'')==='pending'  ?'selected':'' ?>><?= __('marketplace.sf_status_pending')   ?: '대기' ?></option>
                    <option value="draft"     <?= ($item['status']??'')==='draft'    ?'selected':'' ?>><?= __('marketplace.sf_status_draft')     ?: '임시저장' ?></option>
                    <option value="suspended" <?= ($item['status']??'')==='suspended'?'selected':'' ?>><?= __('marketplace.sf_status_suspended') ?: '정지' ?></option>
                    <option value="archived"  <?= ($item['status']??'')==='archived' ?'selected':'' ?>><?= __('marketplace.sf_status_archived')  ?: '보관' ?></option>
                </select>
            </div>
        </div>
    </div>

    <!-- 아이템명·설명 -->
    <div class="<?= $_card ?>">
        <h2 class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4"><?= __('marketplace.sf_basic_info') ?: '기본 정보' ?></h2>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="<?= $_lbl ?>"><?= __('marketplace.sf_name_en') ?: '영문 이름 (en)' ?> <span class="text-red-500">*</span></label>
                    <input type="text" id="name_en" required class="<?= $_inp ?>" value="<?= htmlspecialchars($eName['en']??'') ?>" placeholder="my-awesome-plugin">
                </div>
                <div>
                    <label class="<?= $_lbl ?>"><?= __('marketplace.sf_name_local') ?: '현재 언어 이름' ?> (<?= $locale ?>)</label>
                    <input type="hidden" id="name_json" value='<?= _mpLangAttr($eName) ?>'>
                    <div class="flex gap-2">
                        <input type="text" id="name_local" class="<?= $_inp ?> flex-1" value="<?= htmlspecialchars($eName[$locale]??'') ?>" placeholder="<?= htmlspecialchars(__('marketplace.sf_name_local_hint') ?: '아이템 이름') ?>">
                        <?= rzx_multilang_btn("openMultilangModal('name_json','name_local','text')") ?>
                    </div>
                </div>
            </div>
            <div>
                <label class="<?= $_lbl ?>"><?= __('marketplace.sf_short_desc') ?: '짧은 설명' ?> <span class="text-red-500">*</span></label>
                <input type="hidden" id="short_description_json" value='<?= _mpLangAttr($eShort) ?>'>
                <div class="flex gap-2">
                    <input type="text" id="short_desc" maxlength="200" class="<?= $_inp ?> flex-1" value="<?= htmlspecialchars($eShort[$locale]??$eShort['en']??'') ?>" placeholder="<?= htmlspecialchars(__('marketplace.sf_short_desc_hint') ?: '한 줄 설명 (최대 200자)') ?>">
                    <?= rzx_multilang_btn("openMultilangModal('short_description_json','short_desc','text')") ?>
                </div>
                <p class="<?= $_hint ?>"><span id="shortDescCount"><?= mb_strlen($eShort[$locale]??$eShort['en']??'') ?></span>/200</p>
            </div>
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('marketplace.sf_description') ?: '상세 설명' ?></label>
                    <?= rzx_multilang_btn("openMultilangModal('description_json','description-editor','editor')") ?>
                </div>
                <input type="hidden" id="description_json" value='<?= _mpLangAttr($eDesc) ?>'>
                <div id="description-editor"></div>
                <textarea id="description" class="hidden"><?= htmlspecialchars($eDesc[$locale]??$eDesc['en']??'') ?></textarea>
            </div>
            <div>
                <label class="<?= $_lbl ?>"><?= __('marketplace.sf_tags') ?: '태그' ?></label>
                <input type="hidden" id="tags_json" value='<?= _mpLangAttr($eTagsStrMap) ?>'>
                <div class="flex gap-2">
                    <input type="text" id="tags_display" class="<?= $_inp ?> flex-1" value="<?= htmlspecialchars($currentTagsDisplay) ?>" placeholder="<?= htmlspecialchars(__('marketplace.sf_tags_hint') ?: 'tag1, tag2, tag3') ?>">
                    <?= rzx_multilang_btn("openMultilangModal('tags_json','tags_display','text')") ?>
                </div>
            </div>
        </div>
    </div>

    <!-- URL 정보 -->
    <div class="<?= $_card ?>">
        <h2 class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4"><?= __('marketplace.sf_urls') ?: 'URL 및 의존성' ?></h2>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="<?= $_lbl ?>"><?= __('marketplace.sf_repo_url') ?: '저장소 URL' ?></label>
                    <input type="url" name="repo_url" class="<?= $_inp ?>" value="<?= htmlspecialchars($item['repo_url']??'') ?>" placeholder="<?= htmlspecialchars(__('marketplace.sf_repo_hint') ?: 'https://github.com/username/repo') ?>">
                </div>
                <div>
                    <label class="<?= $_lbl ?>"><?= __('marketplace.sf_demo_url') ?: '데모 URL' ?></label>
                    <input type="url" name="demo_url" class="<?= $_inp ?>" value="<?= htmlspecialchars($item['demo_url']??'') ?>" placeholder="<?= htmlspecialchars(__('marketplace.sf_demo_hint') ?: 'https://demo.example.com') ?>">
                </div>
            </div>
            <div>
                <label class="<?= $_lbl ?>"><?= __('marketplace.sf_requires') ?: '의존 플러그인' ?></label>
                <input type="text" name="requires_plugins" class="<?= $_inp ?>" value="<?= htmlspecialchars(implode(', ', $eReq)) ?>" placeholder="<?= htmlspecialchars(__('marketplace.sf_requires_hint') ?: 'vos-calendar, vos-forms') ?>">
            </div>
        </div>
    </div>

    <!-- 이미지 -->
    <div class="<?= $_card ?>">
        <h2 class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4"><?= __('marketplace.sf_images') ?: '이미지' ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="<?= $_lbl ?>"><?= __('marketplace.sf_icon') ?: '아이콘' ?> (512×512)</label>
                <?php if ($isEdit && !empty($item['icon'])): ?>
                <div class="mb-2"><img src="<?= htmlspecialchars($item['icon']) ?>" class="h-16 rounded-lg border border-zinc-200 dark:border-zinc-600" alt=""></div>
                <?php endif; ?>
                <input type="file" name="icon" accept="image/*" class="w-full text-sm text-zinc-700 dark:text-zinc-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-700 dark:file:text-indigo-300 file:font-medium cursor-pointer">
                <p class="<?= $_hint ?>"><?= __('marketplace.sf_icon_hint') ?: 'PNG/WebP · 512×512px' ?></p>
            </div>
            <div>
                <label class="<?= $_lbl ?>"><?= __('marketplace.sf_banner') ?: '배너' ?> (1200×600)</label>
                <?php if ($isEdit && !empty($item['banner_image'])): ?>
                <div class="mb-2"><img src="<?= htmlspecialchars($item['banner_image']) ?>" class="h-16 rounded-lg border border-zinc-200 dark:border-zinc-600" alt=""></div>
                <?php endif; ?>
                <input type="file" name="banner" accept="image/*" class="w-full text-sm text-zinc-700 dark:text-zinc-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-zinc-50 dark:file:bg-zinc-700 file:text-zinc-700 dark:file:text-zinc-300 file:font-medium cursor-pointer">
                <p class="<?= $_hint ?>"><?= __('marketplace.sf_banner_hint') ?: 'PNG/WebP · 1200×600px' ?></p>
            </div>
        </div>
        <div class="mt-4">
            <label class="<?= $_lbl ?>"><?= __('marketplace.sf_screenshots') ?: '스크린샷' ?></label>
            <?php if ($isEdit && !empty($item['screenshots'])):
                $shots = json_decode($item['screenshots'],true)?:[];
                if ($shots): ?>
            <div class="flex flex-wrap gap-2 mb-2">
                <?php foreach ($shots as $shot): ?>
                <img src="<?= htmlspecialchars($shot) ?>" class="h-20 rounded-lg border border-zinc-200 dark:border-zinc-600 object-cover" alt="">
                <?php endforeach; ?>
            </div>
            <?php endif; endif; ?>
            <div class="border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-xl p-6 text-center hover:border-indigo-400 transition cursor-pointer" onclick="document.getElementById('screenshots').click()">
                <svg class="w-8 h-8 text-zinc-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p class="text-sm text-zinc-500"><?= __('marketplace.sf_drop_files') ?: '클릭하여 스크린샷 추가' ?></p>
                <input type="file" id="screenshots" name="screenshots[]" accept="image/*" multiple class="hidden">
            </div>
            <div id="screenshotPreview" class="flex flex-wrap gap-2 mt-3"></div>
        </div>
    </div>
</div>

<!-- ======== 탭2: 배포 패키지 ======== -->
<div id="tab-release" class="tab-content hidden space-y-6">
    <div class="<?= $_card ?>">
        <h2 class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4"><?= __('marketplace.sf_release') ?: '배포' ?></h2>
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 mb-4 text-xs text-amber-700 dark:text-amber-300">
            <?= __('marketplace.sf_release_notice') ?: '새 버전을 등록하면 기존 버전 목록에 추가됩니다.' ?>
        </div>

        <?php if ($isEdit && !empty($versions)): ?>
        <div class="overflow-x-auto mb-4">
            <table class="w-full text-sm border-collapse">
                <thead><tr class="bg-zinc-50 dark:bg-zinc-700/50">
                    <th class="px-4 py-2.5 text-left font-semibold text-zinc-700 dark:text-zinc-300 border-b dark:border-zinc-600"><?= __('marketplace.sf_version') ?: '버전' ?></th>
                    <th class="px-4 py-2.5 text-left font-semibold text-zinc-700 dark:text-zinc-300 border-b dark:border-zinc-600"><?= __('marketplace.sf_filename') ?: '파일' ?></th>
                    <th class="px-4 py-2.5 text-left font-semibold text-zinc-700 dark:text-zinc-300 border-b dark:border-zinc-600"><?= __('marketplace.sf_date') ?: '등록일' ?></th>
                    <th class="px-4 py-2.5 text-center font-semibold text-zinc-700 dark:text-zinc-300 border-b dark:border-zinc-600"><?= __('marketplace.sf_status') ?: '상태' ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($versions as $v): ?>
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                    <td class="px-4 py-2.5 border-b dark:border-zinc-700 font-mono text-indigo-600 dark:text-indigo-400 font-semibold">v<?= htmlspecialchars($v['version']) ?></td>
                    <td class="px-4 py-2.5 border-b dark:border-zinc-700 text-xs text-zinc-500"><?= htmlspecialchars(basename($v['file_path']??'-')) ?></td>
                    <td class="px-4 py-2.5 border-b dark:border-zinc-700 text-zinc-500"><?= date('Y-m-d H:i', strtotime($v['released_at'])) ?></td>
                    <td class="px-4 py-2.5 border-b dark:border-zinc-700 text-center">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium <?= $v['status']==='active'?'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400':'bg-zinc-100 text-zinc-500' ?>"><?= $v['status'] ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($isEdit): ?>
        <p class="text-sm text-zinc-400 py-4 text-center"><?= __('marketplace.sf_no_releases') ?: '등록된 버전이 없습니다.' ?></p>
        <?php endif; ?>

        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-5 bg-zinc-50/50 dark:bg-zinc-800/50">
            <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= $isEdit ? (__('marketplace.sf_new_release') ?: '새 버전 등록') : (__('marketplace.sf_initial_release') ?: '초기 릴리즈') ?>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="<?= $_lbl ?>"><?= __('marketplace.sf_version') ?: '버전' ?> <span class="text-red-500">*</span></label>
                    <input type="text" name="version" required value="<?= htmlspecialchars($item['latest_version']??'1.0.0') ?>" class="<?= $_inp ?>">
                </div>
                <div>
                    <label class="<?= $_lbl ?>"><?= __('marketplace.sf_min_voscms') ?: '최소 VosCMS' ?></label>
                    <input type="text" name="min_voscms" class="<?= $_inp ?>" value="<?= htmlspecialchars($item['min_voscms_version']??'') ?>" placeholder="2.0.0">
                </div>
                <div>
                    <label class="<?= $_lbl ?>"><?= __('marketplace.sf_min_php') ?: '최소 PHP' ?></label>
                    <input type="text" name="min_php" class="<?= $_inp ?>" value="<?= htmlspecialchars($item['min_php_version']??'') ?>" placeholder="8.1">
                </div>
            </div>
            <div class="mb-4">
                <label class="<?= $_lbl ?>"><?= __('marketplace.sf_package') ?: 'ZIP 패키지' ?> <?= $isEdit ? '' : '<span class="text-red-500">*</span>' ?></label>
                <input type="file" name="package" accept=".zip" <?= $isEdit ? '' : 'required' ?> class="w-full text-sm text-zinc-700 dark:text-zinc-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-700 dark:file:text-indigo-300 file:font-medium cursor-pointer">
                <p class="<?= $_hint ?>"><?= __('marketplace.sf_package_hint') ?: '최대 50MB' ?></p>
            </div>
            <div>
                <label class="<?= $_lbl ?>"><?= __('marketplace.sf_changelog') ?: '변경 내역' ?></label>
                <textarea name="changelog" rows="3" class="<?= $_inp ?>" placeholder="<?= htmlspecialchars(__('marketplace.sf_changelog_hint') ?: '이번 버전의 변경사항을 입력하세요') ?>"><?= htmlspecialchars(!empty($versions)?$versions[0]['changelog']??'':'') ?></textarea>
            </div>
        </div>
    </div>
</div>

<!-- ======== 탭3: 판매 설정 ======== -->
<div id="tab-sales" class="tab-content hidden space-y-6">
    <div class="<?= $_card ?>">
        <h2 class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4"><?= __('marketplace.sf_sales_info') ?: '판매 설정' ?></h2>
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 mb-4 text-xs text-blue-700 dark:text-blue-300">
            <?= __('marketplace.sf_sales_notice') ?: '유료 판매 자료에만 해당되는 기능입니다.' ?>
        </div>
        <div class="mb-6">
            <label class="<?= $_lbl ?>"><?= __('marketplace.sf_price_type') ?: '가격 유형' ?></label>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer px-4 py-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 transition hover:border-indigo-400">
                    <input type="radio" name="price_type" value="free" <?= (float)($item['price']??0)<=0?'checked':'' ?> class="w-4 h-4 text-indigo-600">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('marketplace.sf_price_free') ?: '무료' ?></span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer px-4 py-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 transition hover:border-indigo-400">
                    <input type="radio" name="price_type" value="paid" <?= (float)($item['price']??0)>0?'checked':'' ?> class="w-4 h-4 text-indigo-600">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('marketplace.sf_price_paid') ?: '유료' ?></span>
                </label>
            </div>
        </div>
        <div id="paidSettings" class="<?= (float)($item['price']??0)<=0?'hidden':'' ?> space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="<?= $_lbl ?>"><?= __('marketplace.sf_price') ?: '가격' ?></label>
                    <div class="flex gap-2">
                        <input type="number" name="price" value="<?= $item['price']??'0' ?>" min="0" step="1" class="w-28 <?= $_inp ?>">
                        <select name="currency" class="flex-1 <?= $_inp ?>">
                            <?php foreach (['JPY','KRW','USD','EUR'] as $cur): ?>
                            <option value="<?= $cur ?>" <?= ($item['currency']??'JPY')===$cur?'selected':'' ?>><?= $cur ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="<?= $_lbl ?>"><?= __('marketplace.sf_sale_price') ?: '할인가' ?></label>
                    <input type="number" name="sale_price" value="<?= $item['sale_price']??'' ?>" min="0" step="1" class="<?= $_inp ?>">
                </div>
                <div>
                    <label class="<?= $_lbl ?>"><?= __('marketplace.sf_sale_ends') ?: '할인 종료일' ?></label>
                    <input type="datetime-local" name="sale_ends_at" value="<?= !empty($item['sale_ends_at'])?date('Y-m-d\TH:i',strtotime($item['sale_ends_at'])):'' ?>" class="<?= $_inp ?>">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 하단 버튼 -->
<div class="flex items-center justify-between mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
    <div>
        <button type="button" id="prevTabBtn" class="hidden px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> <?= __('marketplace.sf_prev') ?: '이전' ?>
        </button>
    </div>
    <div class="flex items-center gap-3">
        <button type="button" id="nextTabBtn" class="px-5 py-2.5 bg-zinc-600 hover:bg-zinc-700 text-white text-sm font-medium rounded-lg transition inline-flex items-center gap-1.5">
            <?= __('marketplace.sf_next') ?: '다음' ?> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <button type="button" id="draftBtn" class="px-5 py-2.5 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
            <?= __('marketplace.sf_draft') ?: '임시저장' ?>
        </button>
        <button type="submit" id="submitBtn" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg transition inline-flex items-center gap-1.5 shadow-lg shadow-indigo-600/25">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <?= $isEdit ? (__('marketplace.sf_edit') ?: '수정 완료') : (__('marketplace.sf_submit') ?: '등록 완료') ?>
        </button>
    </div>
</div>
</form>

<?php
// 다국어 모달 — adminUrl 필요
require __DIR__ . '/../components/multilang-modal.php';
?>

<!-- Summernote -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<style>
.note-editor.note-frame{border-radius:.5rem;overflow:hidden;border-color:#d4d4d8}
.dark .note-editor.note-frame{border-color:#52525b}
.dark .note-editing-area .note-editable{background:#3f3f46;color:#fff}
.dark .note-toolbar{background:#27272a;border-color:#52525b}
.dark .note-toolbar .note-btn{color:#d4d4d8}
.dark .note-toolbar .note-btn:hover{background:#52525b}
.note-editor .note-editable{min-height:200px;font-size:14px}
.tab-btn.active{border-color:#4f46e5 !important;color:#4f46e5 !important}
.dark .tab-btn.active{color:#818cf8 !important;border-color:#818cf8 !important}
</style>

<script>
function copyProductKey() {
    var el = document.getElementById('productKeyDisplay');
    if (!el) return;
    navigator.clipboard.writeText(el.textContent.trim()).then(function() {
        var btn = el.nextElementSibling;
        var orig = btn.textContent;
        btn.textContent = '✅ Copied';
        setTimeout(function() { btn.textContent = orig; }, 1500);
    });
}

(function(){
var FORM_ACTION = <?= json_encode($adminUrl.'/market/items/api') ?>;
var BACK_URL    = <?= json_encode($adminUrl.'/market/items') ?>;
var LOC         = <?= json_encode($locale) ?>;

// 탭
var tabs = ['basic','release','sales'], cur = 0;
function showTab(i) {
    cur = i;
    tabs.forEach(function(t,j){
        document.getElementById('tab-'+t).classList.toggle('hidden', j!==i);
        var b = document.querySelector('[data-tab="'+t+'"]');
        if(j===i){ b.classList.add('active'); b.classList.remove('border-transparent','text-zinc-500','dark:text-zinc-400'); }
        else     { b.classList.remove('active'); b.classList.add('border-transparent','text-zinc-500','dark:text-zinc-400'); }
    });
    document.getElementById('prevTabBtn').classList.toggle('hidden', i===0);
    document.getElementById('nextTabBtn').classList.toggle('hidden', i===tabs.length-1);
}
document.querySelectorAll('.tab-btn').forEach(function(b,i){ b.addEventListener('click',function(){ showTab(i); }); });
document.getElementById('nextTabBtn').addEventListener('click',function(){ if(cur<tabs.length-1) showTab(cur+1); });
document.getElementById('prevTabBtn').addEventListener('click',function(){ if(cur>0) showTab(cur-1); });

// 판매 토글
document.querySelectorAll('input[name="price_type"]').forEach(function(r){
    r.addEventListener('change',function(){
        document.getElementById('paidSettings').classList.toggle('hidden', this.value==='free');
        if(this.value==='free') document.querySelector('input[name="price"]').value='0';
    });
});

// 글자수
var sd=document.getElementById('short_desc'), sc=document.getElementById('shortDescCount');
if(sd&&sc){ sd.addEventListener('input',function(){ sc.textContent=this.value.length; }); }

// 스크린샷 프리뷰
var ss=document.getElementById('screenshots'), sp=document.getElementById('screenshotPreview');
if(ss) ss.addEventListener('change',function(){
    sp.innerHTML='';
    Array.from(this.files).forEach(function(f){
        var r=new FileReader(); r.onload=function(e){
            var d=document.createElement('div');
            d.innerHTML='<img src="'+e.target.result+'" class="h-20 rounded-lg border border-zinc-200 dark:border-zinc-600 object-cover">';
            sp.appendChild(d);
        }; r.readAsDataURL(f);
    });
});

// Summernote
$(function(){
    var ex = document.getElementById('description').value;
    $('#description-editor').summernote({placeholder:<?= json_encode(__('marketplace.sf_desc_placeholder') ?: '상세 설명을 입력하세요') ?>, height:300,
        toolbar:[['style',['style']],['font',['bold','italic','underline','strikethrough']],['color',['color']],['para',['ul','ol','paragraph']],['table',['table']],['insert',['link','picture','video']],['view',['codeview','help']]],
        callbacks:{onChange:function(c){ document.getElementById('description').value=c; }}
    });
    if(ex) $('#description-editor').summernote('code',ex);
});

function parseMap(id){
    var el=document.getElementById(id); if(!el||!el.value) return {};
    try{ var v=JSON.parse(el.value); return (v&&typeof v==='object')?v:{}; }catch(_){ return {}; }
}

async function submitFormData(fd){
    var btn=document.getElementById('submitBtn'); btn.disabled=true;
    var orig=btn.innerHTML;
    btn.innerHTML='<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> '+<?= json_encode(__('marketplace.sf_processing') ?: '처리 중…') ?>;

    // name
    var nameMap=parseMap('name_json');
    var ne=(document.getElementById('name_en').value||'').trim();
    var nl=(document.getElementById('name_local').value||'').trim();
    if(ne) nameMap.en=ne; else delete nameMap.en;
    if(nl) nameMap[LOC]=nl; else delete nameMap[LOC];
    fd.set('name',JSON.stringify(nameMap));

    // short_description
    var shortMap=parseMap('short_description_json');
    var sv2=(document.getElementById('short_desc').value||'').trim();
    if(sv2){ shortMap[LOC]=sv2; if(!shortMap.en) shortMap.en=sv2; }
    else delete shortMap[LOC];
    fd.set('short_description',JSON.stringify(shortMap));

    // description
    var descMap=parseMap('description_json');
    var dv=($('#description-editor').summernote('code')||'').trim();
    if(dv&&dv!=='<p><br></p>'){ descMap[LOC]=dv; if(!descMap.en) descMap.en=dv; }
    else delete descMap[LOC];
    fd.set('description',JSON.stringify(descMap));

    // tags → {locale: [array]}
    var tagsStrMap=parseMap('tags_json');
    var td=(document.getElementById('tags_display').value||'').trim();
    if(td){ tagsStrMap[LOC]=td; if(!tagsStrMap.en) tagsStrMap.en=td; } else delete tagsStrMap[LOC];
    var tagsOut={};
    Object.keys(tagsStrMap).forEach(function(lc){
        var arr=String(tagsStrMap[lc]||'').split(',').map(function(t){ return t.trim(); }).filter(Boolean);
        if(arr.length) tagsOut[lc]=arr;
    });
    fd.set('tags',JSON.stringify(tagsOut));

    fd.delete('name_json'); fd.delete('short_description_json'); fd.delete('description_json'); fd.delete('tags_json');
    var rp=fd.get('requires_plugins'); fd.set('requires_plugins', rp?JSON.stringify(rp.split(',').map(function(t){return t.trim();}).filter(Boolean)):'[]');
    if(fd.get('price_type')==='free') fd.set('price','0');
    fd.delete('price_type');

    try{
        var res=await fetch(FORM_ACTION,{method:'POST',body:fd});
        var data=await res.json();
        var el=document.getElementById('result');
        if(data.ok||data.success){
            el.className='mb-4 p-4 rounded-lg text-sm bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800';
            el.innerHTML='<strong>'+(data.is_draft?<?= json_encode(__('marketplace.sf_draft_saved') ?: '임시저장') ?>:<?= json_encode(__('marketplace.sf_complete') ?: '저장 완료') ?>)+'!</strong> '+(data.message||'')+'<br><a href="'+BACK_URL+'" class="underline font-medium">'+<?= json_encode(__('marketplace.sf_back_list') ?: '목록으로') ?>+'</a>';
            if(!data.is_draft && data.item_id) {
                setTimeout(function(){ window.location.href=BACK_URL+'/edit?id='+data.item_id; },1200);
            }
        }else{
            el.className='mb-4 p-4 rounded-lg text-sm bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800';
            el.textContent=data.msg||data.message||<?= json_encode(__('marketplace.sf_fail') ?: '저장에 실패했습니다.') ?>;
        }
        el.classList.remove('hidden'); showTab(0); window.scrollTo({top:0,behavior:'smooth'});
    }catch(err){ alert('Network error: '+err.message); }
    btn.disabled=false; btn.innerHTML=orig;
}

document.getElementById('submitBtn').closest('form').addEventListener('submit',async function(e){
    e.preventDefault(); submitFormData(new FormData(this));
});
document.getElementById('draftBtn').addEventListener('click',function(){
    var fd=new FormData(document.getElementById('submitForm'));
    fd.set('save_draft','1'); fd.set('status','draft');
    submitFormData(fd);
});
})();
</script>
<?php include __DIR__ . '/../_foot.php'; ?>
