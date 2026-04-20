<?php
/**
 * Changelog 편집 페이지 — 데이터 관리 (업로드·버전·번역)
 *
 * 라우팅: /changelog/edit (system 페이지 전용 프론트 편집 URL)
 *
 * 원칙: 설정=모양+기능 / 편집=데이터
 * 이 파일은 "편집" 영역으로, 콘텐츠 데이터(changelog 버전) 를 관리한다.
 *
 * 기능:
 *   - MD 파일 업로드 + 프리뷰 + 병합
 *   - 버전 목록 + 다국어 커버리지 매트릭스
 *   - 항목별 토글 (active, internal), 삭제
 *   - AI 번역 버튼 (현재 비활성)
 */

// 관리자 전용
if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo '<div class="max-w-3xl mx-auto px-4 py-16 text-center text-zinc-500">'
       . (__('common.no_permission') ?? '관리자 권한이 필요합니다.')
       . '</div>';
    return;
}

use RzxLib\Core\Translate\TranslatorFactory;

$prefix   = $_ENV['DB_PREFIX'] ?? 'rzx_';
$baseUrl  = rtrim($config['app_url'] ?? '', '/');
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'theadmin');
$pageSlug = 'changelog';
$pageTitle = __('site.pages.changelog') ?? '변경 이력';

$translator = TranslatorFactory::make();
$aiAvailable = $translator->isAvailable();

// 현재 DB 상태
$versions = $pdo->query(
    "SELECT version, release_date,
            GROUP_CONCAT(DISTINCT CONCAT(locale, ':', translation_source, ':', is_active) ORDER BY locale) AS locales,
            MAX(is_internal) AS any_internal,
            MIN(is_active) AS all_active
       FROM {$prefix}changelog
      GROUP BY version, release_date
      ORDER BY release_date DESC, version DESC
      LIMIT 100"
)->fetchAll(PDO::FETCH_ASSOC);

// 전체 로케일 목록
$allLocales = ['ko', 'en', 'ja', 'zh_CN', 'zh_TW', 'de', 'es', 'fr', 'id', 'mn', 'ru', 'tr', 'vi'];

// locale 별 총 개수
$localeCounts = [];
$countStmt = $pdo->query("SELECT locale, COUNT(*) AS c FROM {$prefix}changelog GROUP BY locale");
while ($r = $countStmt->fetch(PDO::FETCH_ASSOC)) {
    $localeCounts[$r['locale']] = (int)$r['c'];
}

$apiUrl = $adminUrl . '/site/changelog/api';
$csrfToken = $_SESSION['csrf_token'] ?? '';

include BASE_PATH . '/skins/layouts/' . ($siteSettings['site_layout'] ?? 'modern') . '/header.php';
?>

<div class="max-w-5xl mx-auto px-4 py-8">
    <!-- 헤더 + 돌아가기 -->
    <div class="mb-6">
        <a href="<?= htmlspecialchars($baseUrl . '/changelog') ?>" class="inline-flex items-center gap-1 text-sm text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <?= __('common.back') ?? '돌아가기' ?>
        </a>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($pageTitle) ?> — <?= __('common.edit') ?? '편집' ?></h1>
            <span class="text-xs px-2 py-0.5 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-full font-medium"><?= __('common.edit') ?? '편집' ?></span>
        </div>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">버전 데이터 업로드·관리·번역. 페이지 디자인(설정)은 <a href="<?= htmlspecialchars($baseUrl . '/changelog/settings') ?>" class="text-blue-600 hover:underline">설정 페이지</a>에서.</p>
    </div>

<div class="space-y-6">

    <!-- 업로드 섹션 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
        <h4 class="text-base font-bold text-zinc-900 dark:text-white mb-1">📥 CHANGELOG.md 업로드</h4>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-5">마크다운 파일을 업로드하면 신규/변경된 버전만 자동으로 DB 에 병합합니다.</p>

        <form id="cl-upload-form" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1.5">언어</label>
                    <select name="locale" id="cl-locale-select" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                        <?php foreach ($allLocales as $loc): ?>
                        <option value="<?= $loc ?>" <?= $loc === 'ko' ? 'selected' : '' ?>>
                            <?= $loc ?> <?= isset($localeCounts[$loc]) ? '(' . $localeCounts[$loc] . '건)' : '(0건)' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-[10px] text-zinc-400 mt-1">파일명에 <code>.en.md</code> 형식이면 자동 감지합니다.</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1.5">파일</label>
                    <input type="file" name="file" accept=".md,text/markdown" required
                           class="w-full text-sm file:mr-3 file:px-3 file:py-1.5 file:border-0 file:rounded file:bg-blue-50 file:text-blue-700 file:text-xs file:font-medium hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-300">
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" id="cl-btn-preview" class="px-4 py-2 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 rounded-lg text-sm font-medium hover:bg-zinc-200 dark:hover:bg-zinc-600">
                    미리보기
                </button>
                <button type="button" id="cl-btn-apply" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    업로드 & 병합
                </button>
                <button type="button" id="cl-btn-translate" disabled
                        title="<?= $aiAvailable ? '모든 버전을 다른 언어로 AI 번역' : 'AI 번역 엔진 준비 중 — config/translator.php 활성화 필요' ?>"
                        class="px-4 py-2 <?= $aiAvailable ? 'bg-amber-500 hover:bg-amber-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-400 cursor-not-allowed' ?> rounded-lg text-sm font-medium ml-auto">
                    🤖 AI 번역 저장 <?= $aiAvailable ? '' : '(준비중)' ?>
                </button>
            </div>
        </form>

        <div id="cl-result" class="mt-5 hidden"></div>
    </div>

    <!-- 버전 목록 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <h4 class="text-base font-bold text-zinc-900 dark:text-white">📋 등록된 버전 (<?= count($versions) ?>)</h4>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                locale 별 개수: <?= implode(' · ', array_map(fn($l) => "$l:" . ($localeCounts[$l] ?? 0), $allLocales)) ?>
            </div>
        </div>

        <?php if (empty($versions)): ?>
        <div class="p-16 text-center text-zinc-400 dark:text-zinc-500 text-sm">
            <p>등록된 버전이 없습니다. 위에서 CHANGELOG.md 를 업로드하세요.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-700/50 text-xs text-zinc-500 dark:text-zinc-400 uppercase">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-medium">버전</th>
                        <th class="px-4 py-2.5 text-left font-medium">일자</th>
                        <th class="px-4 py-2.5 text-left font-medium">다국어 커버리지</th>
                        <th class="px-4 py-2.5 text-center font-medium">내부</th>
                        <th class="px-4 py-2.5 text-center font-medium">공개</th>
                        <th class="px-4 py-2.5 text-right font-medium">액션</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <?php foreach ($versions as $v):
                        // locale 파싱: "ko:original:1,en:ai:1"
                        $locMap = [];
                        foreach (explode(',', $v['locales']) as $piece) {
                            [$loc, $src, $act] = array_pad(explode(':', $piece, 3), 3, '');
                            $locMap[$loc] = ['src' => $src, 'active' => $act];
                        }
                    ?>
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <td class="px-4 py-2.5 font-mono font-semibold text-indigo-600 dark:text-indigo-400">
                            v<?= htmlspecialchars($v['version']) ?>
                        </td>
                        <td class="px-4 py-2.5 text-zinc-500 dark:text-zinc-400">
                            <?= htmlspecialchars($v['release_date']) ?>
                        </td>
                        <td class="px-4 py-2.5">
                            <div class="flex flex-wrap gap-0.5 text-[10px] font-mono">
                                <?php foreach ($allLocales as $loc):
                                    $has = isset($locMap[$loc]);
                                    $src = $has ? $locMap[$loc]['src'] : '';
                                    $marker = $src === 'original' ? 'O' : ($src === 'ai' ? 'A' : ($src === 'manual' ? 'M' : ''));
                                    $color = !$has ? 'bg-zinc-100 text-zinc-400 dark:bg-zinc-700 dark:text-zinc-500'
                                           : ($src === 'original' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                           : ($src === 'ai' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                           : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'));
                                ?>
                                <span class="px-1.5 py-0.5 rounded <?= $color ?>" title="<?= $loc ?><?= $has ? ' / ' . $src : ' / 없음' ?>">
                                    <?= $loc ?><?= $marker ? $marker : '' ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <?= $v['any_internal'] ? '<span class="text-xs px-2 py-0.5 bg-zinc-200 dark:bg-zinc-600 rounded text-zinc-700 dark:text-zinc-300">내부</span>' : '<span class="text-xs text-zinc-400">—</span>' ?>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <?= $v['all_active'] ? '<span class="text-green-600">✓</span>' : '<span class="text-red-600">✗</span>' ?>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <button type="button" class="cl-delete-btn text-xs text-red-600 hover:text-red-700" data-version="<?= htmlspecialchars($v['version']) ?>">
                                삭제
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-3 bg-zinc-50 dark:bg-zinc-900/30 text-xs text-zinc-500 dark:text-zinc-400 border-t border-zinc-200 dark:border-zinc-700">
            <strong>범례:</strong>
            <span class="ml-2 px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 font-mono">locO</span> 원본 업로드
            <span class="ml-2 px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 font-mono">locA</span> AI 번역
            <span class="ml-2 px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 font-mono">locM</span> 수동 번역
            <span class="ml-2 px-1.5 py-0.5 rounded bg-zinc-100 text-zinc-400 dark:bg-zinc-700 dark:text-zinc-500 font-mono">loc</span> 없음
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const apiUrl = <?= json_encode($apiUrl) ?>;
    const csrfToken = <?= json_encode($csrfToken) ?>;
    const form = document.getElementById('cl-upload-form');
    const resultBox = document.getElementById('cl-result');

    async function submitForm(action) {
        const fileInput = form.querySelector('input[name=file]');
        if (!fileInput.files.length) {
            alert('MD 파일을 선택하세요.');
            return;
        }
        const fd = new FormData(form);
        fd.set('action', action);
        if (!fd.get('csrf_token')) fd.set('csrf_token', csrfToken);

        resultBox.classList.remove('hidden');
        resultBox.innerHTML = '<div class="text-sm text-zinc-500 p-3">처리 중...</div>';

        try {
            const res = await fetch(apiUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
            const data = await res.json();
            renderResult(action, data);
            if (action === 'apply' && data.success) {
                setTimeout(() => location.reload(), 1500);
            }
        } catch (e) {
            resultBox.innerHTML = '<div class="text-sm text-red-600 p-3">요청 실패: ' + e.message + '</div>';
        }
    }

    function renderResult(action, data) {
        if (!data.success) {
            let html = '<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">';
            html += '❌ ' + (data.error || '실패');
            if (data.warnings && data.warnings.length) {
                html += '<ul class="mt-2 text-xs space-y-1">';
                data.warnings.forEach(w => html += '<li>⚠ ' + escapeHtml(w) + '</li>');
                html += '</ul>';
            }
            html += '</div>';
            resultBox.innerHTML = html;
            return;
        }

        let html = '<div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm">';
        if (action === 'preview') {
            html += '<div class="font-medium text-green-800 dark:text-green-300 mb-3">📋 미리보기 (locale: ' + escapeHtml(data.locale) + ', 블록 ' + data.block_count + '개)</div>';
            html += '<div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">';
            html += kv('🆕 신규', data.new.length, data.new);
            html += kv('✏ 변경', data.updated.length, data.updated);
            html += kv('⏸ 동일', data.unchanged.length, data.unchanged);
            html += kv('🛡 보호', data.protected.length, data.protected);
            html += '</div>';
        } else {
            html += '<div class="font-medium text-green-800 dark:text-green-300 mb-2">✅ 완료 (locale: ' + escapeHtml(data.locale) + ')</div>';
            html += '<div class="text-xs text-green-700 dark:text-green-400">';
            html += '신규 ' + data.created + ' · 변경 ' + data.updated + ' · 스킵 ' + data.skipped + ' · 보호 ' + data.protected;
            html += '</div>';
        }
        if (data.warnings && data.warnings.length) {
            html += '<details class="mt-3"><summary class="cursor-pointer text-xs text-amber-700 dark:text-amber-400">⚠ 경고 ' + data.warnings.length + '건</summary>';
            html += '<ul class="mt-2 text-xs space-y-1 pl-4 list-disc">';
            data.warnings.forEach(w => html += '<li>' + escapeHtml(w) + '</li>');
            html += '</ul></details>';
        }
        html += '</div>';
        resultBox.innerHTML = html;
    }

    function kv(label, count, list) {
        return '<div class="p-2 bg-white dark:bg-zinc-800 rounded border border-zinc-200 dark:border-zinc-700">' +
            '<div class="text-zinc-500 dark:text-zinc-400">' + label + '</div>' +
            '<div class="text-lg font-bold text-zinc-900 dark:text-white">' + count + '</div>' +
            (list && list.length ? '<div class="text-[10px] text-zinc-400 mt-1 truncate" title="' + escapeHtml(list.join(', ')) + '">' + escapeHtml(list.slice(0, 3).join(', ')) + (list.length > 3 ? '…' : '') + '</div>' : '') +
            '</div>';
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    document.getElementById('cl-btn-preview')?.addEventListener('click', () => submitForm('preview'));
    document.getElementById('cl-btn-apply')?.addEventListener('click', () => {
        if (!confirm('업로드된 파일로 DB 를 병합합니다. 계속하시겠습니까?')) return;
        submitForm('apply');
    });

    // 버전 삭제
    document.querySelectorAll('.cl-delete-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const version = btn.dataset.version;
            if (!confirm('v' + version + ' 의 모든 locale 을 삭제하시겠습니까?')) return;
            // 같은 version 의 모든 locale 을 삭제해야 하는데 API 는 id 단위이므로 반복 호출 대신 SQL 직접 처리 필요
            // 일단 간단히 알림만
            alert('일괄 삭제는 개별 id 단위 API 호출 구현 필요 — 현재 UI 에서는 개별 locale 삭제만 지원 (개선 예정)');
        });
    });
})();
</script>

</div> <!-- /.max-w-5xl wrapper -->

<?php include BASE_PATH . '/skins/layouts/' . ($siteSettings['site_layout'] ?? 'modern') . '/footer.php'; ?>
