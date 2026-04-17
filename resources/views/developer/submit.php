<?php
/**
 * Developer - 아이템 제출/편집 페이지
 * 3탭 구조: 기본 정보 / 릴리즈 정보 / 판매 정보
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['developer_id'])) { header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/developer/login'); exit; }

include __DIR__ . '/partials/_layout_head.php';
$pageTitle = __mp('dev_submit');

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $categories = $pdo->query("SELECT * FROM {$prefix}mp_categories WHERE is_active = 1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $categories = []; }
$locale = $_mpLocale ?? 'ko';

// 편집 모드 확인 (edit?id=xxx)
$editId = (int)($_GET['id'] ?? 0);
$editItem = null;
$editVersions = [];
if ($editId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}mp_items WHERE id = ? AND seller_id = ?");
        $stmt->execute([$editId, $_SESSION['developer_id']]);
        $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($editItem) {
            $vStmt = $pdo->prepare("SELECT * FROM {$prefix}mp_item_versions WHERE item_id = ? ORDER BY released_at DESC");
            $vStmt->execute([$editId]);
            $editVersions = $vStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {}
}
$isEdit = !empty($editItem);

// 편집 시 JSON 필드 파싱
$eName = $isEdit ? (json_decode($editItem['name'], true) ?: []) : [];
$eShortDesc = $isEdit ? (json_decode($editItem['short_description'], true) ?: []) : [];
$eDesc = $isEdit ? (json_decode($editItem['description'], true) ?: []) : [];
$eTags = $isEdit ? (json_decode($editItem['tags'], true) ?: []) : [];
$eReqPlugins = $isEdit ? (json_decode($editItem['requires_plugins'], true) ?: []) : [];

// CSS 클래스
$_inp = 'w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-white bg-white dark:bg-zinc-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition';
$_lbl = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5';
$_hint = 'text-xs text-zinc-400 dark:text-zinc-500 mt-1';
$_card = 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6';
?>

<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= $isEdit ? __mp('submit_edit_title') : __mp('submit_title') ?></h1>
        <?php if ($isEdit): ?>
        <a href="<?= $baseUrl ?>/developer/my-items" class="text-sm text-zinc-500 hover:text-indigo-600 transition">&larr; <?= __mp('submit_back_list') ?></a>
        <?php endif; ?>
    </div>

    <!-- 상태 메시지 -->
    <div id="result" class="hidden mb-4 p-4 rounded-lg text-sm"></div>

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
                        <?php
                        $licenses = ['GPL-2.0'=>'GPL v2','GPL-3.0'=>'GPL v3','LGPL-2.0'=>'LGPL v2','LGPL-3.0'=>'LGPL v3','BSD'=>'BSD','MIT'=>'MIT','CC'=>'Creative Commons','PD'=>'Public Domain','proprietary'=>'상용 (Proprietary)','other'=>'기타'];
                        foreach ($licenses as $lv => $ll):
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
                        <p class="<?= $_hint ?>">마켓플레이스에서 표시되는 기본 이름</p>
                    </div>
                    <div>
                        <label class="<?= $_lbl ?>">이름 (로컬)</label>
                        <input type="text" id="name_local" class="<?= $_inp ?>" value="<?= htmlspecialchars($eName[$locale] ?? '') ?>" placeholder="나의 멋진 플러그인">
                        <p class="<?= $_hint ?>">현재 언어(<?= $locale ?>)로 표시될 이름</p>
                    </div>
                </div>
                <div>
                    <label class="<?= $_lbl ?>">간단한 소개 <span class="text-red-500">*</span></label>
                    <input type="text" id="short_desc" maxlength="200" class="<?= $_inp ?>" value="<?= htmlspecialchars($eShortDesc[$locale] ?? $eShortDesc['en'] ?? '') ?>" placeholder="한 줄로 설명해 주세요 (자료실 목록에 우선 노출됩니다)">
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
                        <label class="<?= $_lbl ?>">
                            <span class="inline-flex items-center gap-1"><svg class="w-3.5 h-3.5 text-zinc-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg> 저장소 URL</span>
                        </label>
                        <input type="url" name="repo_url" class="<?= $_inp ?>" value="<?= htmlspecialchars($editItem['repo_url'] ?? '') ?>" placeholder="https://github.com/username/repo">
                        <p class="<?= $_hint ?>">GitHub 등 소스코드 저장소 주소</p>
                    </div>
                    <div>
                        <label class="<?= $_lbl ?>">데모 URL</label>
                        <input type="url" name="demo_url" class="<?= $_inp ?>" value="<?= htmlspecialchars($editItem['demo_url'] ?? '') ?>" placeholder="https://demo.example.com">
                        <p class="<?= $_hint ?>">체험해 볼 수 있는 데모 사이트 주소</p>
                    </div>
                </div>
                <div>
                    <label class="<?= $_lbl ?>">의존 플러그인</label>
                    <input type="text" name="requires_plugins" class="<?= $_inp ?>" value="<?= htmlspecialchars(implode(', ', $eReqPlugins)) ?>" placeholder="vos-salon, vos-pos (쉼표로 구분)">
                    <p class="<?= $_hint ?>">이 아이템이 동작하기 위해 필요한 다른 플러그인 slug</p>
                </div>
            </div>
        </div>

        <!-- 이미지/파일 -->
        <div class="<?= $_card ?>">
            <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">이미지</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="<?= $_lbl ?>">대표 이미지 (아이콘)</label>
                    <?php if ($isEdit && $editItem['icon']): ?>
                    <div class="mb-2"><img src="<?= htmlspecialchars($editItem['icon']) ?>" class="h-16 rounded-lg border border-zinc-200 dark:border-zinc-600" alt=""></div>
                    <?php endif; ?>
                    <input type="file" name="icon" accept="image/*" class="w-full text-sm text-zinc-700 dark:text-zinc-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-700 dark:file:text-indigo-300 file:font-medium hover:file:bg-indigo-100 cursor-pointer">
                    <p class="<?= $_hint ?>">권장: 256×256px, PNG/JPG/WebP</p>
                </div>
                <div>
                    <label class="<?= $_lbl ?>">배너 이미지</label>
                    <?php if ($isEdit && $editItem['banner_image']): ?>
                    <div class="mb-2"><img src="<?= htmlspecialchars($editItem['banner_image']) ?>" class="h-16 rounded-lg border border-zinc-200 dark:border-zinc-600" alt=""></div>
                    <?php endif; ?>
                    <input type="file" name="banner" accept="image/*" class="w-full text-sm text-zinc-700 dark:text-zinc-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-zinc-50 dark:file:bg-zinc-700 file:text-zinc-700 dark:file:text-zinc-300 file:font-medium hover:file:bg-zinc-100 cursor-pointer">
                    <p class="<?= $_hint ?>">권장: 900×600px 이상, 3:2 비율</p>
                </div>
            </div>
            <div class="mt-4">
                <label class="<?= $_lbl ?>">스크린샷 (복수 선택 가능)</label>
                <div class="border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-xl p-6 text-center hover:border-indigo-400 dark:hover:border-indigo-500 transition cursor-pointer" onclick="document.getElementById('screenshots').click()">
                    <svg class="w-8 h-8 text-zinc-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">여기에 파일을 끌어 놓거나 클릭하세요</p>
                    <p class="text-xs text-zinc-400 mt-1">PNG, JPG, WebP (최대 5MB)</p>
                    <input type="file" id="screenshots" name="screenshots[]" accept="image/*" multiple class="hidden">
                </div>
                <div id="screenshotPreview" class="flex flex-wrap gap-2 mt-3"></div>
            </div>
        </div>
    </div>

    <!-- ==================== 탭2: 릴리즈 정보 ==================== -->
    <div id="tab-release" class="tab-content hidden space-y-6">

        <!-- 현재 버전 -->
        <div class="<?= $_card ?>">
            <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">릴리즈</h2>

            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 mb-4 text-xs text-amber-700 dark:text-amber-300">
                <span class="font-semibold">안내:</span> 공개 릴리즈(.zip) 파일은 본문에 업로드하지 말고 이곳에서 등록해 주세요.
            </div>

            <?php if ($isEdit && !empty($editVersions)): ?>
            <!-- 릴리즈 히스토리 테이블 -->
            <div class="overflow-x-auto mb-4">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-700/50">
                            <th class="px-4 py-2.5 text-left font-semibold text-zinc-700 dark:text-zinc-300 border-b dark:border-zinc-600">버전</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-zinc-700 dark:text-zinc-300 border-b dark:border-zinc-600">파일명</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-zinc-700 dark:text-zinc-300 border-b dark:border-zinc-600">등록 일시</th>
                            <th class="px-4 py-2.5 text-right font-semibold text-zinc-700 dark:text-zinc-300 border-b dark:border-zinc-600">다운로드</th>
                            <th class="px-4 py-2.5 text-center font-semibold text-zinc-700 dark:text-zinc-300 border-b dark:border-zinc-600">상태</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($editVersions as $v): ?>
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                            <td class="px-4 py-2.5 border-b dark:border-zinc-700 font-mono text-indigo-600 dark:text-indigo-400 font-semibold">v<?= htmlspecialchars($v['version']) ?></td>
                            <td class="px-4 py-2.5 border-b dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 text-xs"><?= htmlspecialchars(basename($v['download_url'] ?? '-')) ?></td>
                            <td class="px-4 py-2.5 border-b dark:border-zinc-700 text-zinc-500 dark:text-zinc-400"><?= date('Y-m-d H:i', strtotime($v['released_at'])) ?></td>
                            <td class="px-4 py-2.5 border-b dark:border-zinc-700 text-right text-zinc-500"><?= number_format($v['file_size'] ?? 0) ?>B</td>
                            <td class="px-4 py-2.5 border-b dark:border-zinc-700 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium <?= $v['status'] === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-zinc-100 text-zinc-500' ?>"><?= $v['status'] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php elseif ($isEdit): ?>
            <p class="text-sm text-zinc-400 dark:text-zinc-500 py-4 text-center">등록된 릴리즈가 없습니다.</p>
            <?php endif; ?>

            <!-- 새 릴리즈 등록 폼 -->
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-5 bg-zinc-50/50 dark:bg-zinc-800/50">
                <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-200 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <?= $isEdit ? '새 릴리즈 등록' : '초기 릴리즈' ?>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="<?= $_lbl ?>">버전 <span class="text-red-500">*</span></label>
                        <input type="text" name="version" required value="<?= htmlspecialchars($editItem['latest_version'] ?? '1.0.0') ?>" class="<?= $_inp ?>" placeholder="1.0.0">
                    </div>
                    <div>
                        <label class="<?= $_lbl ?>">최소 VosCMS</label>
                        <input type="text" name="min_voscms" class="<?= $_inp ?>" value="<?= htmlspecialchars($editItem['min_voscms_version'] ?? '') ?>" placeholder="2.0.0">
                    </div>
                    <div>
                        <label class="<?= $_lbl ?>">최소 PHP</label>
                        <input type="text" name="min_php" class="<?= $_inp ?>" value="<?= htmlspecialchars($editItem['min_php_version'] ?? '') ?>" placeholder="8.1">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="<?= $_lbl ?>">패키지 파일 (.zip) <span class="text-red-500">*</span></label>
                    <input type="file" name="package" accept=".zip" <?= $isEdit ? '' : 'required' ?> class="w-full text-sm text-zinc-700 dark:text-zinc-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-700 dark:file:text-indigo-300 file:font-medium hover:file:bg-indigo-100 cursor-pointer">
                    <p class="<?= $_hint ?>">최대 50MB. 설치 경로: /modules/{slug} 또는 /skins/{slug}</p>
                </div>
                <div>
                    <label class="<?= $_lbl ?>">변경 이력 (Changelog)</label>
                    <textarea name="changelog" rows="3" class="<?= $_inp ?>" placeholder="- 새 기능 추가&#10;- 버그 수정&#10;- 성능 개선"><?= htmlspecialchars($editVersions[0]['changelog'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== 탭3: 판매 정보 ==================== -->
    <div id="tab-sales" class="tab-content hidden space-y-6">
        <div class="<?= $_card ?>">
            <h2 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-4">판매 정보</h2>

            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 mb-4 text-xs text-blue-700 dark:text-blue-300">
                <span class="font-semibold">안내:</span> 유료 판매 자료에만 해당되는 기능입니다. 무료 자료는 가격을 0으로 설정하세요.
            </div>

            <!-- 자료 구분 -->
            <div class="mb-6">
                <label class="<?= $_lbl ?>">자료 구분</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer px-4 py-2.5 rounded-lg border transition" id="priceTypeFree">
                        <input type="radio" name="price_type" value="free" <?= (float)($editItem['price'] ?? 0) <= 0 ? 'checked' : '' ?> class="w-4 h-4 text-indigo-600">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">공개 자료 (무료)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer px-4 py-2.5 rounded-lg border transition" id="priceTypePaid">
                        <input type="radio" name="price_type" value="paid" <?= (float)($editItem['price'] ?? 0) > 0 ? 'checked' : '' ?> class="w-4 h-4 text-indigo-600">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">판매 자료 (유료)</span>
                    </label>
                </div>
            </div>

            <!-- 가격 설정 (유료 시 표시) -->
            <div id="paidSettings" class="<?= (float)($editItem['price'] ?? 0) <= 0 ? 'hidden' : '' ?> space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="<?= $_lbl ?>">정가</label>
                        <div class="flex gap-2">
                            <input type="number" name="price" value="<?= $editItem['price'] ?? '0' ?>" min="0" step="0.01" class="flex-1 <?= $_inp ?>">
                            <select name="currency" class="w-24 <?= $_inp ?>">
                                <option value="USD" <?= ($editItem['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD</option>
                                <option value="JPY" <?= ($editItem['currency'] ?? '') === 'JPY' ? 'selected' : '' ?>>JPY</option>
                                <option value="KRW" <?= ($editItem['currency'] ?? '') === 'KRW' ? 'selected' : '' ?>>KRW</option>
                                <option value="EUR" <?= ($editItem['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="<?= $_lbl ?>">할인가</label>
                        <input type="number" name="sale_price" value="<?= $editItem['sale_price'] ?? '' ?>" min="0" step="0.01" class="<?= $_inp ?>" placeholder="할인 시만 입력">
                    </div>
                    <div>
                        <label class="<?= $_lbl ?>">할인 종료일</label>
                        <input type="datetime-local" name="sale_ends_at" value="<?= $editItem['sale_ends_at'] ? date('Y-m-d\TH:i', strtotime($editItem['sale_ends_at'])) : '' ?>" class="<?= $_inp ?>">
                    </div>
                </div>

                <!-- ID 가 상품 구분에 사용되는 고유값 안내 -->
                <div class="bg-zinc-50 dark:bg-zinc-700/30 border border-zinc-200 dark:border-zinc-600 rounded-lg p-3 text-xs text-zinc-500 dark:text-zinc-400">
                    <span class="font-semibold">참고:</span> ID는 상품 구분에 사용되는 고유값으로, 영문/숫자/언더바 문자로만 이루어져야 하며 변경할 수 없습니다. (예: basic, premium, business)
                </div>
            </div>
        </div>
    </div>

    <!-- 하단 버튼 -->
    <div class="flex items-center justify-between mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
        <div class="flex items-center gap-3">
            <button type="button" id="prevTabBtn" class="hidden px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                이전
            </button>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" id="nextTabBtn" class="px-5 py-2.5 bg-zinc-600 hover:bg-zinc-700 text-white text-sm font-medium rounded-lg transition inline-flex items-center gap-1.5">
                다음
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            <button type="submit" id="submitBtn" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg transition inline-flex items-center gap-1.5 shadow-lg shadow-indigo-600/25">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <?= $isEdit ? '수정 완료' : '작성 완료' ?>
            </button>
        </div>
    </div>

    </form>
</div>

<!-- Summernote WYSIWYG -->
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
.dark .note-modal .modal-content { background: #27272a; color: #fff; }
.dark .note-modal .form-control { background: #3f3f46; color: #fff; border-color: #52525b; }
.note-editor .note-editable { min-height: 200px; font-family: 'Pretendard', sans-serif; font-size: 14px; }
.tab-btn.active { border-color: #4f46e5; color: #4f46e5; }
.dark .tab-btn.active { color: #818cf8; border-color: #818cf8; }
</style>

<script>
(function() {
    // ===== 탭 시스템 =====
    var tabs = ['basic', 'release', 'sales'];
    var currentTab = 0;

    function showTab(idx) {
        currentTab = idx;
        tabs.forEach(function(t, i) {
            var el = document.getElementById('tab-' + t);
            var btn = document.querySelector('[data-tab="' + t + '"]');
            if (i === idx) {
                el.classList.remove('hidden');
                btn.classList.add('active');
                btn.classList.remove('border-transparent', 'text-zinc-500', 'dark:text-zinc-400');
                btn.classList.add('border-indigo-600', 'text-indigo-600', 'dark:text-indigo-400');
            } else {
                el.classList.add('hidden');
                btn.classList.remove('active', 'border-indigo-600', 'text-indigo-600', 'dark:text-indigo-400');
                btn.classList.add('border-transparent', 'text-zinc-500', 'dark:text-zinc-400');
            }
        });
        document.getElementById('prevTabBtn').classList.toggle('hidden', idx === 0);
        document.getElementById('nextTabBtn').classList.toggle('hidden', idx === tabs.length - 1);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    document.querySelectorAll('.tab-btn').forEach(function(btn, i) {
        btn.addEventListener('click', function() { showTab(i); });
    });
    document.getElementById('nextTabBtn').addEventListener('click', function() {
        if (currentTab < tabs.length - 1) showTab(currentTab + 1);
    });
    document.getElementById('prevTabBtn').addEventListener('click', function() {
        if (currentTab > 0) showTab(currentTab - 1);
    });

    // ===== 판매 유형 토글 =====
    document.querySelectorAll('input[name="price_type"]').forEach(function(r) {
        r.addEventListener('change', function() {
            document.getElementById('paidSettings').classList.toggle('hidden', this.value === 'free');
            if (this.value === 'free') document.querySelector('input[name="price"]').value = '0';
        });
    });

    // ===== 글자수 카운터 =====
    var sdInput = document.getElementById('short_desc');
    var sdCount = document.getElementById('shortDescCount');
    if (sdInput && sdCount) {
        sdCount.textContent = sdInput.value.length;
        sdInput.addEventListener('input', function() { sdCount.textContent = this.value.length; });
    }

    // ===== 스크린샷 미리보기 =====
    var ssInput = document.getElementById('screenshots');
    var ssPreview = document.getElementById('screenshotPreview');
    if (ssInput) {
        ssInput.addEventListener('change', function() {
            ssPreview.innerHTML = '';
            Array.from(this.files).forEach(function(f) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var div = document.createElement('div');
                    div.className = 'relative';
                    div.innerHTML = '<img src="' + e.target.result + '" class="h-20 rounded-lg border border-zinc-200 dark:border-zinc-600 object-cover">';
                    ssPreview.appendChild(div);
                };
                reader.readAsDataURL(f);
            });
        });
    }

    // ===== Summernote =====
    $(document).ready(function() {
        var existingContent = document.getElementById('description').value;
        $('#description-editor').summernote({
            placeholder: '상세 설명을 입력하세요. 사진과 동영상도 첨부할 수 있습니다.',
            height: 300,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['codeview', 'help']]
            ],
            callbacks: {
                onChange: function(contents) {
                    document.getElementById('description').value = contents;
                }
            }
        });
        if (existingContent) {
            $('#description-editor').summernote('code', existingContent);
        }
    });

    // ===== 폼 제출 =====
    document.getElementById('submitForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> 처리 중...';

        var fd = new FormData(this);
        var nameEn = document.getElementById('name_en').value;
        var nameLocal = document.getElementById('name_local').value;
        var nameObj = { en: nameEn };
        if (nameLocal) nameObj['<?= $locale ?>'] = nameLocal;
        fd.set('name', JSON.stringify(nameObj));

        var sd = document.getElementById('short_desc').value;
        fd.set('short_description', JSON.stringify({ en: sd, '<?= $locale ?>': sd }));

        var descHtml = $('#description-editor').summernote('code') || '';
        fd.set('description', JSON.stringify({ en: descHtml, '<?= $locale ?>': descHtml }));

        var tags = fd.get('tags');
        if (tags) fd.set('tags', JSON.stringify(tags.split(',').map(function(t) { return t.trim(); }).filter(Boolean)));

        var reqPlugins = fd.get('requires_plugins');
        if (reqPlugins) fd.set('requires_plugins', JSON.stringify(reqPlugins.split(',').map(function(t) { return t.trim(); }).filter(Boolean)));
        else fd.set('requires_plugins', '[]');

        if (fd.get('price_type') === 'free') fd.set('price', '0');
        fd.delete('price_type');

        try {
            var res = await fetch('<?= $baseUrl ?>/api/developer/submit', { method: 'POST', body: fd });
            var data = await res.json();
            var el = document.getElementById('result');
            if (data.success) {
                el.className = 'mb-4 p-4 rounded-lg text-sm bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800';
                el.innerHTML = '<strong>제출 완료!</strong> 심사 후 마켓플레이스에 등록됩니다. (ID: ' + (data.queue_id || data.id || '') + ')<br><a href="<?= $baseUrl ?>/developer/my-items" class="underline font-medium">내 아이템 목록</a>';
            } else {
                el.className = 'mb-4 p-4 rounded-lg text-sm bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800';
                el.textContent = data.message || '제출에 실패했습니다.';
            }
            el.classList.remove('hidden');
            showTab(0);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (err) {
            alert('네트워크 오류가 발생했습니다.');
        }
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> <?= $isEdit ? '수정 완료' : '작성 완료' ?>';
    });
})();
</script>

<?php include __DIR__ . '/partials/_layout_foot.php'; ?>
