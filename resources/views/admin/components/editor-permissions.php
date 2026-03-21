<?php
/**
 * 위지윅 에디터 권한 설정 컴포넌트
 * 게시판 설정, 페이지 설정에서 공통 사용
 * 문서/댓글 분리 표시 (세로 배치)
 */
if (!isset($editorConfig)) $editorConfig = [];

$_edGrades = $pdo->query("SELECT id, name, slug FROM {$prefix}member_grades ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);

$_edPerms = [
    'html_perm' => __('site.editor.html_perm') ?? 'HTML 편집 권한',
    'file_perm' => __('site.editor.file_perm') ?? '파일 첨부 권한',
    'component_perm' => __('site.editor.component_perm') ?? '기본 컴포넌트 사용 권한',
    'ext_component_perm' => __('site.editor.ext_component_perm') ?? '확장 컴포넌트 사용 권한',
];

$_cbCls = 'rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500';

// 섹션 렌더 함수
function _renderEditorSection($type, $label, $perms, $grades, $config, $cbCls) {
    $prefix = ($type === 'doc') ? 'editor_' : 'comment_editor_';
    $cbClass = ($type === 'doc') ? 'ed-doc-cb' : 'ed-cmt-cb';
    ?>
    <div class="border dark:border-zinc-700 rounded-xl overflow-hidden">
        <div class="px-4 py-2.5 bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
            <h4 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300"><?= $label ?></h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 dark:border-zinc-700">
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 w-48"></th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-zinc-500"><?= __('site.editor.admin_group') ?? '관리그룹' ?></th>
                        <?php foreach ($grades as $g): ?>
                        <th class="px-3 py-2 text-center text-xs font-medium text-zinc-500"><?= htmlspecialchars($g['name']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-50 dark:divide-zinc-700/50">
                    <?php foreach ($perms as $permKey => $permLabel):
                        $val = $config[$prefix . $permKey] ?? '';
                        $checked = is_string($val) ? explode(',', $val) : (is_array($val) ? $val : []);
                    ?>
                    <tr class="ed-perm-row hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300 font-medium"><?= $permLabel ?></td>
                        <td class="px-3 py-2.5 text-center">
                            <input type="checkbox" class="<?= $cbCls ?> <?= $cbClass ?>" data-perm="<?= $permKey ?>" value="admin" <?= in_array('admin', $checked) ? 'checked' : '' ?>>
                        </td>
                        <?php foreach ($grades as $g): ?>
                        <td class="px-3 py-2.5 text-center">
                            <input type="checkbox" class="<?= $cbCls ?> <?= $cbClass ?>" data-perm="<?= $permKey ?>" value="<?= $g['slug'] ?>" <?= in_array($g['slug'], $checked) ? 'checked' : '' ?>>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
?>

<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden">
    <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('site.editor.title') ?? '위지윅 에디터' ?></h3>
    </div>

    <div class="p-4 space-y-4">
        <!-- 기본 에디터 설정 사용 -->
        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
            <input type="checkbox" id="edUseDefault" class="<?= $_cbCls ?>" <?= ($editorConfig['editor_use_default'] ?? true) ? 'checked' : '' ?> onchange="toggleEditorPerms()">
            <span class="font-medium"><?= __('site.editor.use_default') ?? '기본 에디터 설정 사용' ?></span>
            <span class="text-zinc-400">— <?= __('site.editor.use_default_desc') ?? '에디터 모듈의 기본 설정을 따릅니다.' ?></span>
        </label>

        <div id="edPermSections" class="space-y-4">
            <!-- 문서 에디터 -->
            <?php _renderEditorSection('doc', __('site.editor.document') ?? '문서', $_edPerms, $_edGrades, $editorConfig, $_cbCls); ?>

            <!-- 댓글 에디터 -->
            <?php _renderEditorSection('cmt', __('site.editor.comment') ?? '댓글', $_edPerms, $_edGrades, $editorConfig, $_cbCls); ?>
        </div>
    </div>
</div>

<script>
function toggleEditorPerms() {
    var useDefault = document.getElementById('edUseDefault').checked;
    var sections = document.getElementById('edPermSections');
    if (sections) {
        sections.style.opacity = useDefault ? '0.4' : '1';
        sections.style.pointerEvents = useDefault ? 'none' : '';
    }
}
toggleEditorPerms();

function getEditorConfig() {
    var config = {
        editor_use_default: document.getElementById('edUseDefault').checked
    };
    ['html_perm', 'file_perm', 'component_perm', 'ext_component_perm'].forEach(function(perm) {
        var docVals = [];
        document.querySelectorAll('.ed-doc-cb[data-perm="' + perm + '"]:checked').forEach(function(cb) { docVals.push(cb.value); });
        config['editor_' + perm] = docVals.join(',');

        var cmtVals = [];
        document.querySelectorAll('.ed-cmt-cb[data-perm="' + perm + '"]:checked').forEach(function(cb) { cmtVals.push(cb.value); });
        config['comment_editor_' + perm] = cmtVals.join(',');
    });
    return config;
}
</script>
