<?php
/**
 * 마켓플레이스 아이템 등록/편집 공통 폼
 *
 * 필요 변수:
 *   $pdo          - PDO 인스턴스
 *   $locale       - 현재 로케일
 *   $baseUrl      - 사이트 기본 URL
 *   $categories   - 카테고리 목록 (rzx_mp_categories)
 *   $editItem     - 편집 시 기존 아이템 데이터 (null이면 신규)
 *   $editVersions - 편집 시 버전 목록
 *   $formAction   - 폼 제출 API URL
 *   $backUrl      - 뒤로가기 URL
 *   $context      - 'admin' | 'developer'
 */

$isEdit = !empty($editItem);
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 편집 시 JSON 필드 파싱
$eName = $isEdit ? (json_decode($editItem['name'] ?? '{}', true) ?: []) : [];
$eShortDesc = $isEdit ? (json_decode($editItem['short_description'] ?? '{}', true) ?: []) : [];
$eDesc = $isEdit ? (json_decode($editItem['description'] ?? '{}', true) ?: []) : [];
$eTags = $isEdit ? (json_decode($editItem['tags'] ?? '[]', true) ?: []) : [];
$eReqPlugins = $isEdit ? (json_decode($editItem['requires_plugins'] ?? '[]', true) ?: []) : [];

// CSS 클래스
$_inp = 'w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white bg-white dark:bg-zinc-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition';
$_lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5';
$_hint = 'text-xs text-zinc-400 dark:text-zinc-500 mt-1';
$_card = 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6';

// 라이선스 옵션
$_licenses = ['GPL-2.0'=>'GPL v2','GPL-3.0'=>'GPL v3','LGPL-2.0'=>'LGPL v2','LGPL-3.0'=>'LGPL v3','BSD'=>'BSD','MIT'=>'MIT','CC'=>'Creative Commons','PD'=>'Public Domain','proprietary'=>'상용 (Proprietary)','other'=>'기타'];
?>

<!-- 탭 네비게이션 -->
<div class="border-b border-zinc-200 dark:border-zinc-700 mb-6">
    <nav class="flex gap-0" id="submitTabs">
        <button type="button" data-tab="basic" class="tab-btn px-5 py-3 text-sm font-semibold border-b-2 transition-colors border-indigo-600 text-indigo-600 dark:text-indigo-400">
            <span class="inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                기본 정보
            </span>
        </button>
        <button type="button" data-tab="release" class="tab-btn px-5 py-3 text-sm font-semibold border-b-2 transition-colors border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300">
            <span class="inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                릴리즈 정보
            </span>
        </button>
        <button type="button" data-tab="sales" class="tab-btn px-5 py-3 text-sm font-semibold border-b-2 transition-colors border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300">
            <span class="inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                판매 정보
            </span>
        </button>
    </nav>
</div>

<form id="submitForm" enctype="multipart/form-data">
<input type="hidden" name="action" value="submit_item">
<?php if ($isEdit): ?><input type="hidden" name="item_id" value="<?= $editItem['id'] ?>"><?php endif; ?>

<!-- ==================== 탭1: 기본 정보 ==================== -->
<div id="tab-basic" class="tab-content space-y-6">
    <!-- 유형 + 분류 -->
    <div class="<?= $_card ?>">
        <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">유형 및 분류</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="<?= $_lbl ?>">아이템 유형 <span class="text-red-500">*</span></label>
                <select name="item_type" required class="<?= $_inp ?>">
                    <option value="plugin" <?= ($editItem['type'] ?? '') === 'plugin' ? 'selected' : '' ?>>Plugin (모듈)</option>
                    <option value="widget" <?= ($editItem['type'] ?? '') === 'widget' ? 'selected' : '' ?>>Widget (위젯)</option>
                    <option value="theme" <?= ($editItem['type'] ?? '') === 'theme' ? 'selected' : '' ?>>Theme (테마)</option>
                    <option value="skin" <?= ($editItem['type'] ?? '') === 'skin' ? 'selected' : '' ?>>Skin (스킨)</option>
                </select>
            </div>
            <div>
                <label class="<?= $_lbl ?>">카테고리</label>
                <select name="category_id" class="<?= $_inp ?>">
                    <option value="">선택 안 함</option>
                    <?php foreach ($categories as $cat):
                        $cn = json_decode($cat['name'], true);
                        $sel = ($editItem['category_id'] ?? '') == $cat['id'] ? 'selected' : '';
                    ?>
                    <option value="<?= $cat['id'] ?>" <?= $sel ?>><?= htmlspecialchars($cn[$locale] ?? $cn['en'] ?? $cat['slug']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="<?= $_lbl ?>">라이선스</label>
                <select name="license" class="<?= $_inp ?>">
                    <option value="">선택</option>
                    <?php foreach ($_licenses as $lv => $ll):
                        $sel = ($editItem['license'] ?? '') === $lv ? 'selected' : '';
                    ?>
                    <option value="<?= $lv ?>" <?= $sel ?>><?= $ll ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="<?= $_hint ?>">공개 자료의 경우 반드시 선택해 주세요.</p>
            </div>
        </div>
    </div>

    <!-- 기본 정보 -->
    <div class="<?= $_card ?>">
        <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">기본 정보</h2>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="<?= $_lbl ?>">이름 (영문) <span class="text-red-500">*</span></label>
                    <input type="text" id="name_en" required class="<?= $_inp ?>" value="<?= htmlspecialchars($eName['en'] ?? '') ?>" placeholder="My Awesome Plugin">
                </div>
                <div>
                    <label class="<?= $_lbl ?>">이름 (로컬)</label>
                    <input type="text" id="name_local" class="<?= $_inp ?>" value="<?= htmlspecialchars($eName[$locale] ?? '') ?>" placeholder="나의 멋진 플러그인">
                </div>
            </div>
            <div>
                <label class="<?= $_lbl ?>">간단한 소개 <span class="text-red-500">*</span></label>
                <input type="text" id="short_desc" maxlength="200" class="<?= $_inp ?>" value="<?= htmlspecialchars($eShortDesc[$locale] ?? $eShortDesc['en'] ?? '') ?>" placeholder="한 줄로 설명해 주세요 (목록에 우선 노출)">
                <p class="<?= $_hint ?>"><span id="shortDescCount">0</span>/200</p>
            </div>
            <div>
                <label class="<?= $_lbl ?>">상세 설명</label>
                <div id="description-editor"></div>
                <textarea id="description" class="hidden"><?= htmlspecialchars($eDesc[$locale] ?? $eDesc['en'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="<?= $_lbl ?>">태그</label>
                <input type="text" name="tags" class="<?= $_inp ?>" value="<?= htmlspecialchars(implode(', ', $eTags)) ?>" placeholder="쉼표(,)로 구분하여 복수 등록">
            </div>
        </div>
    </div>

    <!-- URL 정보 -->
    <div class="<?= $_card ?>">
        <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">URL 정보</h2>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="<?= $_lbl ?>">저장소 URL</label>
                    <input type="url" name="repo_url" class="<?= $_inp ?>" value="<?= htmlspecialchars($editItem['repo_url'] ?? '') ?>" placeholder="https://github.com/username/repo">
                </div>
                <div>
                    <label class="<?= $_lbl ?>">데모 URL</label>
                    <input type="url" name="demo_url" class="<?= $_inp ?>" value="<?= htmlspecialchars($editItem['demo_url'] ?? '') ?>" placeholder="https://demo.example.com">
                </div>
            </div>
            <div>
                <label class="<?= $_lbl ?>">의존 플러그인</label>
                <input type="text" name="requires_plugins" class="<?= $_inp ?>" value="<?= htmlspecialchars(implode(', ', $eReqPlugins)) ?>" placeholder="vos-salon, vos-pos (쉼표로 구분)">
            </div>
        </div>
    </div>

    <!-- 이미지 -->
    <div class="<?= $_card ?>">
        <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">이미지</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="<?= $_lbl ?>">대표 이미지 (아이콘)</label>
                <?php if ($isEdit && $editItem['icon']): ?>
                <div class="mb-2"><img src="<?= htmlspecialchars($editItem['icon']) ?>" class="h-16 rounded-lg border border-zinc-200 dark:border-zinc-600" alt=""></div>
                <?php endif; ?>
                <input type="file" name="icon" accept="image/*" class="w-full text-sm text-zinc-700 dark:text-zinc-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-700 dark:file:text-indigo-300 file:font-medium cursor-pointer">
                <p class="<?= $_hint ?>">권장: 256×256px</p>
            </div>
            <div>
                <label class="<?= $_lbl ?>">배너 이미지</label>
                <?php if ($isEdit && $editItem['banner_image']): ?>
                <div class="mb-2"><img src="<?= htmlspecialchars($editItem['banner_image']) ?>" class="h-16 rounded-lg border border-zinc-200 dark:border-zinc-600" alt=""></div>
                <?php endif; ?>
                <input type="file" name="banner" accept="image/*" class="w-full text-sm text-zinc-700 dark:text-zinc-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-zinc-50 dark:file:bg-zinc-700 file:text-zinc-700 dark:file:text-zinc-300 file:font-medium cursor-pointer">
                <p class="<?= $_hint ?>">권장: 900×600px, 3:2 비율</p>
            </div>
        </div>
        <div class="mt-4">
            <label class="<?= $_lbl ?>">스크린샷 (복수 선택)</label>
            <div class="border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-xl p-6 text-center hover:border-indigo-400 transition cursor-pointer" onclick="document.getElementById('screenshots').click()">
                <svg class="w-8 h-8 text-zinc-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p class="text-sm text-zinc-500">파일을 끌어 놓거나 클릭</p>
                <input type="file" id="screenshots" name="screenshots[]" accept="image/*" multiple class="hidden">
            </div>
            <div id="screenshotPreview" class="flex flex-wrap gap-2 mt-3"></div>
        </div>
    </div>
</div>

<!-- ==================== 탭2: 릴리즈 정보 ==================== -->
<div id="tab-release" class="tab-content hidden space-y-6">
    <div class="<?= $_card ?>">
        <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">릴리즈</h2>
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 mb-4 text-xs text-amber-700 dark:text-amber-300">
            <span class="font-semibold">안내:</span> 공개 릴리즈(.zip) 파일은 본문에 업로드하지 말고 이곳에서 등록해 주세요.
        </div>

        <?php if ($isEdit && !empty($editVersions)): ?>
        <div class="overflow-x-auto mb-4">
            <table class="w-full text-sm border-collapse">
                <thead><tr class="bg-zinc-50 dark:bg-zinc-700/50">
                    <th class="px-4 py-2.5 text-left font-semibold text-zinc-700 dark:text-zinc-300 border-b dark:border-zinc-600">버전</th>
                    <th class="px-4 py-2.5 text-left font-semibold text-zinc-700 dark:text-zinc-300 border-b dark:border-zinc-600">파일명</th>
                    <th class="px-4 py-2.5 text-left font-semibold text-zinc-700 dark:text-zinc-300 border-b dark:border-zinc-600">등록일</th>
                    <th class="px-4 py-2.5 text-center font-semibold text-zinc-700 dark:text-zinc-300 border-b dark:border-zinc-600">상태</th>
                </tr></thead>
                <tbody>
                <?php foreach ($editVersions as $v): ?>
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                    <td class="px-4 py-2.5 border-b dark:border-zinc-700 font-mono text-indigo-600 dark:text-indigo-400 font-semibold">v<?= htmlspecialchars($v['version']) ?></td>
                    <td class="px-4 py-2.5 border-b dark:border-zinc-700 text-xs text-zinc-500"><?= htmlspecialchars(basename($v['download_url'] ?? '-')) ?></td>
                    <td class="px-4 py-2.5 border-b dark:border-zinc-700 text-zinc-500"><?= date('Y-m-d H:i', strtotime($v['released_at'])) ?></td>
                    <td class="px-4 py-2.5 border-b dark:border-zinc-700 text-center">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium <?= $v['status'] === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-zinc-100 text-zinc-500' ?>"><?= $v['status'] ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($isEdit): ?>
        <p class="text-sm text-zinc-400 py-4 text-center">등록된 릴리즈가 없습니다.</p>
        <?php endif; ?>

        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-5 bg-zinc-50/50 dark:bg-zinc-800/50">
            <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= $isEdit ? '새 릴리즈 등록' : '초기 릴리즈' ?>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div><label class="<?= $_lbl ?>">버전 <span class="text-red-500">*</span></label>
                    <input type="text" name="version" required value="<?= htmlspecialchars($editItem['latest_version'] ?? '1.0.0') ?>" class="<?= $_inp ?>"></div>
                <div><label class="<?= $_lbl ?>">최소 VosCMS</label>
                    <input type="text" name="min_voscms" class="<?= $_inp ?>" value="<?= htmlspecialchars($editItem['min_voscms_version'] ?? '') ?>" placeholder="2.0.0"></div>
                <div><label class="<?= $_lbl ?>">최소 PHP</label>
                    <input type="text" name="min_php" class="<?= $_inp ?>" value="<?= htmlspecialchars($editItem['min_php_version'] ?? '') ?>" placeholder="8.1"></div>
            </div>
            <div class="mb-4">
                <label class="<?= $_lbl ?>">패키지 파일 (.zip) <?= $isEdit ? '' : '<span class="text-red-500">*</span>' ?></label>
                <input type="file" name="package" accept=".zip" <?= $isEdit ? '' : 'required' ?> class="w-full text-sm text-zinc-700 dark:text-zinc-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-700 dark:file:text-indigo-300 file:font-medium cursor-pointer">
                <p class="<?= $_hint ?>">최대 50MB</p>
            </div>
            <div><label class="<?= $_lbl ?>">변경 이력</label>
                <textarea name="changelog" rows="3" class="<?= $_inp ?>" placeholder="- 새 기능 추가&#10;- 버그 수정"><?= htmlspecialchars($editVersions[0]['changelog'] ?? '') ?></textarea></div>
        </div>
    </div>
</div>

<!-- ==================== 탭3: 판매 정보 ==================== -->
<div id="tab-sales" class="tab-content hidden space-y-6">
    <div class="<?= $_card ?>">
        <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">판매 정보</h2>
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 mb-4 text-xs text-blue-700 dark:text-blue-300">
            유료 판매 자료에만 해당되는 기능입니다. 무료 자료는 가격을 0으로 설정하세요.
        </div>
        <div class="mb-6">
            <label class="<?= $_lbl ?>">자료 구분</label>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer px-4 py-2.5 rounded-lg border transition">
                    <input type="radio" name="price_type" value="free" <?= (float)($editItem['price'] ?? 0) <= 0 ? 'checked' : '' ?> class="w-4 h-4 text-indigo-600">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">공개 자료 (무료)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer px-4 py-2.5 rounded-lg border transition">
                    <input type="radio" name="price_type" value="paid" <?= (float)($editItem['price'] ?? 0) > 0 ? 'checked' : '' ?> class="w-4 h-4 text-indigo-600">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">판매 자료 (유료)</span>
                </label>
            </div>
        </div>
        <div id="paidSettings" class="<?= (float)($editItem['price'] ?? 0) <= 0 ? 'hidden' : '' ?> space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="<?= $_lbl ?>">정가</label>
                    <div class="flex gap-2">
                        <input type="number" name="price" value="<?= $editItem['price'] ?? '0' ?>" min="0" step="0.01" class="flex-1 <?= $_inp ?>">
                        <select name="currency" class="w-24 <?= $_inp ?>">
                            <?php foreach (['USD','JPY','KRW','EUR'] as $cur): ?>
                            <option value="<?= $cur ?>" <?= ($editItem['currency'] ?? '') === $cur ? 'selected' : '' ?>><?= $cur ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div><label class="<?= $_lbl ?>">할인가</label>
                    <input type="number" name="sale_price" value="<?= $editItem['sale_price'] ?? '' ?>" min="0" step="0.01" class="<?= $_inp ?>" placeholder="할인 시만"></div>
                <div><label class="<?= $_lbl ?>">할인 종료일</label>
                    <input type="datetime-local" name="sale_ends_at" value="<?= !empty($editItem['sale_ends_at']) ? date('Y-m-d\TH:i', strtotime($editItem['sale_ends_at'])) : '' ?>" class="<?= $_inp ?>"></div>
            </div>
        </div>
    </div>
</div>

<!-- 하단 버튼 -->
<div class="flex items-center justify-between mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
    <div>
        <button type="button" id="prevTabBtn" class="hidden px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> 이전
        </button>
    </div>
    <div class="flex items-center gap-3">
        <button type="button" id="nextTabBtn" class="px-5 py-2.5 bg-zinc-600 hover:bg-zinc-700 text-white text-sm font-medium rounded-lg transition inline-flex items-center gap-1.5">
            다음 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <?php if ($context === 'developer' && !$isEdit): ?>
        <button type="button" id="draftBtn" class="px-5 py-2.5 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
            임시 저장
        </button>
        <?php endif; ?>
        <button type="submit" id="submitBtn" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg transition inline-flex items-center gap-1.5 shadow-lg shadow-indigo-600/25">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <?= $isEdit ? '수정 완료' : '작성 완료' ?>
        </button>
    </div>
</div>
</form>

<!-- Summernote -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<style>
.note-editor.note-frame { border-radius: 0.5rem; overflow: hidden; border-color: #d4d4d8; }
.dark .note-editor.note-frame { border-color: #52525b; }
.dark .note-editing-area .note-editable { background: #3f3f46; color: #fff; }
.dark .note-toolbar { background: #27272a; border-color: #52525b; }
.dark .note-toolbar .note-btn { color: #d4d4d8; }
.dark .note-toolbar .note-btn:hover { background: #52525b; }
.note-editor .note-editable { min-height: 200px; font-size: 14px; }
.tab-btn.active { border-color: #4f46e5; color: #4f46e5; }
.dark .tab-btn.active { color: #818cf8; border-color: #818cf8; }
</style>

<script>
(function() {
    var tabs = ['basic', 'release', 'sales'];
    var currentTab = 0;
    function showTab(idx) {
        currentTab = idx;
        tabs.forEach(function(t, i) {
            var el = document.getElementById('tab-' + t);
            var btn = document.querySelector('[data-tab="' + t + '"]');
            el.classList.toggle('hidden', i !== idx);
            btn.classList.toggle('active', i === idx);
            if (i === idx) { btn.classList.remove('border-transparent','text-zinc-500','dark:text-zinc-400'); btn.classList.add('border-indigo-600','text-indigo-600','dark:text-indigo-400'); }
            else { btn.classList.remove('border-indigo-600','text-indigo-600','dark:text-indigo-400'); btn.classList.add('border-transparent','text-zinc-500','dark:text-zinc-400'); }
        });
        document.getElementById('prevTabBtn').classList.toggle('hidden', idx === 0);
        document.getElementById('nextTabBtn').classList.toggle('hidden', idx === tabs.length - 1);
    }
    document.querySelectorAll('.tab-btn').forEach(function(b,i) { b.addEventListener('click', function() { showTab(i); }); });
    document.getElementById('nextTabBtn').addEventListener('click', function() { if (currentTab < tabs.length-1) showTab(currentTab+1); });
    document.getElementById('prevTabBtn').addEventListener('click', function() { if (currentTab > 0) showTab(currentTab-1); });

    // 판매 토글
    document.querySelectorAll('input[name="price_type"]').forEach(function(r) {
        r.addEventListener('change', function() {
            document.getElementById('paidSettings').classList.toggle('hidden', this.value === 'free');
            if (this.value === 'free') document.querySelector('input[name="price"]').value = '0';
        });
    });

    // 글자수
    var sd = document.getElementById('short_desc'), sc = document.getElementById('shortDescCount');
    if (sd && sc) { sc.textContent = sd.value.length; sd.addEventListener('input', function() { sc.textContent = this.value.length; }); }

    // 스크린샷 프리뷰
    var ss = document.getElementById('screenshots'), sp = document.getElementById('screenshotPreview');
    if (ss) ss.addEventListener('change', function() {
        sp.innerHTML = '';
        Array.from(this.files).forEach(function(f) {
            var r = new FileReader(); r.onload = function(e) {
                var d = document.createElement('div'); d.innerHTML = '<img src="'+e.target.result+'" class="h-20 rounded-lg border border-zinc-200 dark:border-zinc-600 object-cover">';
                sp.appendChild(d);
            }; r.readAsDataURL(f);
        });
    });

    // Summernote
    $(function() {
        var ex = document.getElementById('description').value;
        $('#description-editor').summernote({ placeholder: '상세 설명을 입력하세요.', height: 300,
            toolbar: [['style',['style']],['font',['bold','italic','underline','strikethrough']],['color',['color']],['para',['ul','ol','paragraph']],['table',['table']],['insert',['link','picture','video']],['view',['codeview','help']]],
            callbacks: { onChange: function(c) { document.getElementById('description').value = c; } }
        });
        if (ex) $('#description-editor').summernote('code', ex);
    });

    // 임시 저장
    var draftBtn = document.getElementById('draftBtn');
    if (draftBtn) {
        draftBtn.addEventListener('click', function() {
            var fd = new FormData(document.getElementById('submitForm'));
            fd.set('save_draft', '1');
            submitFormData(fd);
        });
    }

    // 공통 제출 함수
    async function submitFormData(fd) {
        var btn = document.getElementById('submitBtn'); btn.disabled = true;
        var origHtml = btn.innerHTML;
        btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> 처리 중...';

        // JSON 필드 가공
        var ne = document.getElementById('name_en').value, nl = document.getElementById('name_local').value;
        var no = {en:ne}; if(nl) no['<?= $locale ?>'] = nl;
        fd.set('name', JSON.stringify(no));
        fd.set('short_description', JSON.stringify({en:document.getElementById('short_desc').value,'<?=$locale?>':document.getElementById('short_desc').value}));
        fd.set('description', JSON.stringify({en:$('#description-editor').summernote('code')||'','<?=$locale?>':$('#description-editor').summernote('code')||''}));
        var tg = fd.get('tags'); if(tg) fd.set('tags', JSON.stringify(tg.split(',').map(function(t){return t.trim()}).filter(Boolean)));
        var rp = fd.get('requires_plugins'); fd.set('requires_plugins', rp ? JSON.stringify(rp.split(',').map(function(t){return t.trim()}).filter(Boolean)) : '[]');
        if(fd.get('price_type')==='free') fd.set('price','0');
        fd.delete('price_type');
        fd.set('context', '<?= $context ?>');

        try {
            var res = await fetch('<?= $formAction ?>', {method:'POST', body:fd});
            var data = await res.json();
            var el = document.getElementById('result');
            if(data.success) {
                el.className = 'mb-4 p-4 rounded-lg text-sm bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800';
                el.innerHTML = '<strong>' + (data.is_draft ? '임시 저장 완료' : '완료') + '!</strong> ' + (data.message||'') + '<br><a href="<?= $backUrl ?>" class="underline font-medium">목록으로</a>';
            } else {
                el.className = 'mb-4 p-4 rounded-lg text-sm bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800';
                el.textContent = data.message || '실패';
            }
            el.classList.remove('hidden'); showTab(0); window.scrollTo({top:0,behavior:'smooth'});
        } catch(err) { alert('네트워크 오류'); }
        btn.disabled = false; btn.innerHTML = origHtml;
    }

    // 폼 제출
    document.getElementById('submitForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        submitFormData(new FormData(this));
    });
})();
</script>
