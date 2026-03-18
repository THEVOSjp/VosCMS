<?php
/**
 * RezlyX Admin - 스태프 관리 모달 + JS (index.php에서 include)
 */
?>
<!-- 사진 편집 모달 (Cropper.js) -->
<div id="cropperModal" class="fixed inset-0 z-[60] hidden">
    <div class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <!-- 헤더 -->
            <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white"><?= __('staff.photo_editor.title') ?></h3>
                <button type="button" onclick="closeCropperModal()" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <!-- 크롭 영역 -->
            <div class="relative bg-zinc-900" style="height:340px;">
                <img id="cropperImage" class="block max-w-full" style="display:none;">
            </div>
            <!-- 도구 바 -->
            <div class="px-5 py-3 flex items-center justify-center gap-2 border-t border-zinc-200 dark:border-zinc-700">
                <button type="button" onclick="cropperAction('zoomIn')" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= __('staff.photo_editor.zoom_in') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                </button>
                <button type="button" onclick="cropperAction('zoomOut')" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= __('staff.photo_editor.zoom_out') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/></svg>
                </button>
                <div class="w-px h-6 bg-zinc-300 dark:bg-zinc-600 mx-1"></div>
                <button type="button" onclick="cropperAction('rotateLeft')" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= __('staff.photo_editor.rotate_left') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h1m0 0a8 8 0 1016 0H4z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10V4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10H10"/></svg>
                </button>
                <button type="button" onclick="cropperAction('rotateRight')" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= __('staff.photo_editor.rotate_right') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-1m0 0a8 8 0 10-16 0h17z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 10V4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 10H14"/></svg>
                </button>
                <div class="w-px h-6 bg-zinc-300 dark:bg-zinc-600 mx-1"></div>
                <button type="button" onclick="cropperAction('flipH')" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= __('staff.photo_editor.flip_h') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4l-4 6h8M17 16V4l4 6h-8"/></svg>
                </button>
                <button type="button" onclick="cropperAction('flipV')" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= __('staff.photo_editor.flip_v') ?>">
                    <svg class="w-5 h-5 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4l-4 6h8M17 16V4l4 6h-8"/></svg>
                </button>
                <div class="w-px h-6 bg-zinc-300 dark:bg-zinc-600 mx-1"></div>
                <button type="button" onclick="cropperAction('reset')" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= __('staff.photo_editor.reset') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 9a9 9 0 0115.36-6.36M20 15a9 9 0 01-15.36 6.36"/></svg>
                </button>
            </div>
            <!-- 하단 버튼 -->
            <div class="px-5 py-3 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-2">
                <button type="button" onclick="closeCropperModal()" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition">
                    <?= __('common.buttons.cancel') ?>
                </button>
                <button type="button" onclick="applyCrop()" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                    <?= __('staff.photo_editor.apply') ?>
                </button>
            </div>
        </div>
    </div>
</div>
<style>
    /* 원형 크롭 가이드 */
    .cropper-view-box,
    .cropper-face {
        border-radius: 50%;
    }
    .cropper-view-box {
        outline: 0;
        box-shadow: 0 0 0 1px rgba(59,130,246,.5);
    }
</style>

<!-- 스태프 추가/수정 모달 -->
<div id="staffModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-start justify-center min-h-screen px-4 pt-16 pb-20">
        <div class="fixed inset-0 bg-zinc-900/75 transition-opacity" onclick="closeStaffModal()"></div>
        <div class="relative z-50 w-full max-w-xl bg-white dark:bg-zinc-800 rounded-xl shadow-xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h3 id="modalTitle" class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('staff.create') ?></h3>
                <button type="button" onclick="closeStaffModal()" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="staffForm" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="formId">
                <input type="hidden" name="user_id" id="formUserId" value="">
                <input type="hidden" name="name_i18n" id="formNameI18n">
                <input type="hidden" name="bio_i18n" id="formBioI18n">
                <input type="hidden" name="remove_avatar" id="formRemoveAvatar" value="0">
                <input type="hidden" name="member_avatar_url" id="formMemberAvatarUrl" value="">
                <input type="hidden" name="service_ids" id="formServiceIds">
                <input type="hidden" name="bundle_ids" id="formBundleIds">

                <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                    <!-- 회원 연동 -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.fields.linked_member') ?></label>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('staff.link_member_desc') ?></p>
                        <div class="relative">
                            <div id="linkedMemberDisplay" class="hidden items-center gap-2 px-3 py-2 mb-2 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                <span id="linkedMemberName" class="text-sm text-purple-800 dark:text-purple-300 flex-1"></span>
                                <button type="button" onclick="unlinkMember()" class="p-0.5 text-purple-400 hover:text-purple-600 dark:hover:text-purple-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div id="memberSearchWrap">
                                <input type="text" id="memberSearch" autocomplete="off"
                                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                                       placeholder="<?= __('staff.search_member_placeholder') ?>">
                                <div id="memberSearchResults" class="hidden absolute z-20 w-full mt-1 bg-white dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
                            </div>
                        </div>
                    </div>

                    <!-- 사진 -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('staff.fields.avatar') ?></label>
                        <div class="flex items-center gap-4">
                            <div id="avatarPreview" class="w-20 h-20 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-2xl font-bold text-zinc-500 overflow-hidden shrink-0">
                                <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="px-3 py-1.5 text-xs font-medium text-blue-600 border border-blue-300 hover:bg-blue-50 dark:border-blue-700 dark:hover:bg-blue-900/20 rounded-lg cursor-pointer transition inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <?= __('staff.fields.upload_photo') ?>
                                    <input type="file" name="avatar" id="avatarInput" accept="image/*" class="hidden" onchange="previewAvatar(this)">
                                </label>
                                <button type="button" id="removeAvatarBtn" onclick="removeAvatar()" class="hidden px-3 py-1.5 text-xs font-medium text-red-600 border border-red-300 hover:bg-red-50 dark:border-red-700 dark:hover:bg-red-900/20 rounded-lg transition">
                                    <?= __('staff.fields.remove_photo') ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 배너 이미지 -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('staff.fields.banner') ?? '배너 이미지' ?></label>
                        <div id="bannerPreview" class="w-full h-24 rounded-lg bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center overflow-hidden mb-2">
                            <span class="text-xs text-zinc-400"><?= __('staff.fields.no_banner') ?? 'No banner' ?></span>
                        </div>
                        <div class="flex gap-2">
                            <label class="px-3 py-1.5 text-xs font-medium text-blue-600 border border-blue-300 hover:bg-blue-50 dark:border-blue-700 dark:hover:bg-blue-900/20 rounded-lg cursor-pointer transition inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <?= __('staff.fields.upload_banner') ?? '배너 업로드' ?>
                                <input type="file" name="banner" id="bannerInput" accept="image/*" class="hidden" onchange="previewBanner(this)">
                            </label>
                            <button type="button" id="removeBannerBtn" onclick="removeBanner()" class="hidden px-3 py-1.5 text-xs font-medium text-red-600 border border-red-300 hover:bg-red-50 dark:border-red-700 dark:hover:bg-red-900/20 rounded-lg transition">
                                <?= __('staff.fields.remove') ?? '삭제' ?>
                            </button>
                        </div>
                        <input type="hidden" name="remove_banner" id="formRemoveBanner" value="0">
                    </div>

                    <!-- 이름 + 다국어 -->
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('staff.fields.name') ?></label>
                            <?= rzx_multilang_btn("toggleI18n('name')") ?>
                        </div>
                        <input type="text" name="name" id="formName" required placeholder="<?= __('staff.placeholder.name') ?>"
                               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                        <div id="nameI18nFields" class="hidden mt-2 space-y-1.5 pl-3 border-l-2 border-purple-300 dark:border-purple-700">
                            <?php foreach ($supportedLangs as $code): ?>
                            <div class="flex items-center gap-2">
                                <span class="w-14 text-[11px] font-medium text-zinc-500 dark:text-zinc-400 shrink-0"><?= $langNativeNames[$code] ?? $code ?></span>
                                <input type="text" data-i18n="name" data-lang="<?= $code ?>" placeholder="<?= $langNativeNames[$code] ?? $code ?>"
                                       class="flex-1 px-2 py-1 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-xs">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 직책 -->
                    <?php if (!empty($positions)): ?>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.fields.position') ?></label>
                        <select name="position_id" id="formPosition"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                            <option value="">-</option>
                            <?php foreach ($positions as $pos): ?>
                            <option value="<?= $pos['id'] ?>"><?= htmlspecialchars($pos['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- 이메일 / 전화번호 -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.fields.email') ?></label>
                            <input type="email" name="email" id="formEmail" placeholder="<?= __('staff.placeholder.email') ?>"
                                   class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.fields.phone') ?></label>
                            <input type="text" name="phone" id="formPhone" placeholder="<?= __('staff.placeholder.phone') ?>"
                                   class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                        </div>
                    </div>

                    <!-- 소개 + 다국어 -->
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('staff.fields.bio') ?></label>
                            <?= rzx_multilang_btn("toggleI18n('bio')") ?>
                        </div>
                        <textarea name="bio" id="formBio" rows="3" placeholder="<?= __('staff.placeholder.bio') ?>"
                                  class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm resize-none"></textarea>
                        <div id="bioI18nFields" class="hidden mt-2 space-y-1.5 pl-3 border-l-2 border-purple-300 dark:border-purple-700">
                            <?php foreach ($supportedLangs as $code): ?>
                            <div class="flex items-center gap-2">
                                <span class="w-14 text-[11px] font-medium text-zinc-500 dark:text-zinc-400 shrink-0"><?= $langNativeNames[$code] ?? $code ?></span>
                                <input type="text" data-i18n="bio" data-lang="<?= $code ?>" placeholder="<?= $langNativeNames[$code] ?? $code ?>"
                                       class="flex-1 px-2 py-1 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-xs">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 시술 전 인사말 -->
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('staff.fields.greeting_before') ?></label>
                            <?= rzx_multilang_btn("toggleI18n('greeting_before')") ?>
                        </div>
                        <input type="hidden" name="greeting_before_i18n" id="formGreetingBeforeI18n">
                        <textarea name="greeting_before" id="formGreetingBefore" rows="2" placeholder="<?= __('staff.placeholder.greeting_before') ?>"
                                  class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm resize-none"></textarea>
                        <div id="greeting_beforeI18nFields" class="hidden mt-2 space-y-1.5 pl-3 border-l-2 border-purple-300 dark:border-purple-700">
                            <?php foreach ($supportedLangs as $code): ?>
                            <div class="flex items-center gap-2">
                                <span class="w-14 text-[11px] font-medium text-zinc-500 dark:text-zinc-400 shrink-0"><?= $langNativeNames[$code] ?? $code ?></span>
                                <input type="text" data-i18n="greeting_before" data-lang="<?= $code ?>" placeholder="<?= $langNativeNames[$code] ?? $code ?>"
                                       class="flex-1 px-2 py-1 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-xs">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 시술 후 인사말 -->
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('staff.fields.greeting_after') ?></label>
                            <?= rzx_multilang_btn("toggleI18n('greeting_after')") ?>
                        </div>
                        <input type="hidden" name="greeting_after_i18n" id="formGreetingAfterI18n">
                        <textarea name="greeting_after" id="formGreetingAfter" rows="2" placeholder="<?= __('staff.placeholder.greeting_after') ?>"
                                  class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm resize-none"></textarea>
                        <div id="greeting_afterI18nFields" class="hidden mt-2 space-y-1.5 pl-3 border-l-2 border-purple-300 dark:border-purple-700">
                            <?php foreach ($supportedLangs as $code): ?>
                            <div class="flex items-center gap-2">
                                <span class="w-14 text-[11px] font-medium text-zinc-500 dark:text-zinc-400 shrink-0"><?= $langNativeNames[$code] ?? $code ?></span>
                                <input type="text" data-i18n="greeting_after" data-lang="<?= $code ?>" placeholder="<?= $langNativeNames[$code] ?? $code ?>"
                                       class="flex-1 px-2 py-1 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-xs">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 담당 서비스 -->
                    <?php if (!empty($services)): ?>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('staff.fields.services') ?></label>
                        <div id="serviceSelector" class="flex flex-wrap gap-1.5">
                            <?php foreach ($services as $svc): ?>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer svc-check" value="<?= htmlspecialchars($svc['id']) ?>">
                                <span class="px-2.5 py-1 text-xs font-medium rounded-full border transition-colors
                                    peer-checked:bg-emerald-100 peer-checked:text-emerald-700 peer-checked:border-emerald-300
                                    dark:peer-checked:bg-emerald-900/30 dark:peer-checked:text-emerald-400 dark:peer-checked:border-emerald-700
                                    bg-zinc-100 text-zinc-500 border-zinc-200
                                    dark:bg-zinc-700 dark:text-zinc-400 dark:border-zinc-600
                                    hover:bg-zinc-200 dark:hover:bg-zinc-600"><?= htmlspecialchars(getServiceTranslated($svc['id'], $svc['name'])) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 담당 번들 -->
                    <?php if (!empty($allBundles)): ?>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('bundles.nav') ?></label>
                        <div id="bundleSelector" class="flex flex-wrap gap-1.5">
                            <?php foreach ($allBundles as $bdl): ?>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer bdl-check" value="<?= htmlspecialchars($bdl['id']) ?>">
                                <span class="px-2.5 py-1 text-xs font-medium rounded-full border transition-colors
                                    peer-checked:bg-blue-100 peer-checked:text-blue-700 peer-checked:border-blue-300
                                    dark:peer-checked:bg-blue-900/30 dark:peer-checked:text-blue-400 dark:peer-checked:border-blue-700
                                    bg-zinc-100 text-zinc-500 border-zinc-200
                                    dark:bg-zinc-700 dark:text-zinc-400 dark:border-zinc-600
                                    hover:bg-zinc-200 dark:hover:bg-zinc-600">
                                    <svg class="w-3 h-3 inline mr-0.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    <?= htmlspecialchars($bdl['name']) ?>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 지명비 (설정 활성 시만 표시) -->
                    <?php if (($settings['staff_designation_fee_enabled'] ?? '0') === '1'): ?>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.fields.designation_fee') ?></label>
                        <div class="relative">
                            <input type="number" name="designation_fee" id="formDesignationFee" min="0" step="1" value="0"
                                   class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500"
                                   placeholder="0">
                            <p class="text-xs text-zinc-400 mt-1"><?= __('staff.fields.designation_fee_desc') ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 활성 상태 (수정 시만) -->
                    <div id="activeField" class="hidden flex items-center justify-between">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('staff.fields.is_active') ?></label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" id="formActive" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-zinc-200 peer-focus:ring-2 peer-focus:ring-blue-500 dark:bg-zinc-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                        </label>
                    </div>
                </div>

                <!-- 버튼 -->
                <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-700 rounded-b-xl flex justify-end gap-2">
                    <button type="button" onclick="closeStaffModal()" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition"><?= __('settings.multilang.cancel') ?></button>
                    <button type="submit" id="submitBtn" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.common.save') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    var STAFF_URL = '<?= $adminUrl ?>/staff';

    // ─── 회원 검색 ───
    var memberSearchTimer = null;
    var memberSearchBound = false;
    var allMembersCache = null; // 전체 회원 캐시

    // 전체 회원 로드 (모달 열릴 때 1회)
    function loadAllMembers(callback) {
        if (allMembersCache) {
            callback(allMembersCache);
            return;
        }
        console.log('[StaffManage] Loading all members...');
        var fd = new FormData();
        fd.append('action', 'search_members');
        fd.append('q', '*');
        fetch(STAFF_URL, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.members) {
                    allMembersCache = data.members;
                    console.log('[StaffManage] Loaded', data.members.length, 'members');
                    callback(data.members);
                }
            })
            .catch(function(err) {
                console.error('[StaffManage] Load members error:', err);
            });
    }

    // 클라이언트 사이드 필터링
    function filterMembers(q) {
        if (!allMembersCache) return [];
        if (!q) return allMembersCache.slice(0, 20);
        q = q.toLowerCase();
        return allMembersCache.filter(function(m) {
            return (m.name && m.name.toLowerCase().indexOf(q) !== -1) ||
                   (m.email && m.email.toLowerCase().indexOf(q) !== -1) ||
                   (m.phone && m.phone.toLowerCase().indexOf(q) !== -1);
        }).slice(0, 20);
    }

    function initMemberSearch() {
        if (memberSearchBound) return;
        var input = document.getElementById('memberSearch');
        var results = document.getElementById('memberSearchResults');
        if (!input || !results) return;
        memberSearchBound = true;

        // 입력 시 클라이언트 필터링
        input.addEventListener('input', function() {
            var q = this.value.trim();
            clearTimeout(memberSearchTimer);
            memberSearchTimer = setTimeout(function() {
                var filtered = filterMembers(q);
                renderMemberResults(filtered, q);
                console.log('[StaffManage] Filtered members:', q, filtered.length);
            }, 100);
        });

        // 포커스 시 전체 목록 표시
        input.addEventListener('focus', function() {
            var self = this;
            loadAllMembers(function() {
                var q = self.value.trim();
                var filtered = filterMembers(q);
                renderMemberResults(filtered, q);
            });
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#memberSearchWrap')) {
                results.classList.add('hidden');
            }
        });
    }

    function renderMemberResults(members, query) {
        var results = document.getElementById('memberSearchResults');
        results.innerHTML = '';
        if (members.length === 0) {
            results.innerHTML = '<div class="px-3 py-2 text-sm text-zinc-400"><?= __('staff.no_member_found') ?></div>';
            results.classList.remove('hidden');
            return;
        }
        members.forEach(function(m) {
            var div = document.createElement('div');
            div.className = 'px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-600 cursor-pointer flex items-center gap-2';
            var initial = m.name ? m.name.charAt(0) : '?';
            var nameHtml = query ? highlightMatch(m.name || '', query) : escapeHtml(m.name || '');
            var emailHtml = query ? highlightMatch(m.email || '', query) : escapeHtml(m.email || '');
            var avatarHtml = m.avatar
                ? '<img src="' + m.avatar.replace(/"/g, '&quot;') + '" class="w-7 h-7 rounded-full object-cover shrink-0" onerror="this.style.display=\'none\'">'
                : '<div class="w-7 h-7 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 text-xs font-semibold shrink-0">' + escapeHtml(initial) + '</div>';
            div.innerHTML = avatarHtml +
                '<div class="flex-1 min-w-0"><div class="text-sm font-medium text-zinc-900 dark:text-white truncate">' +
                nameHtml + '</div><div class="text-xs text-zinc-500 dark:text-zinc-400 truncate">' +
                emailHtml + (m.phone ? ' · ' + escapeHtml(m.phone) : '') + '</div></div>';
            div.addEventListener('click', function() {
                selectMember(m);
            });
            results.appendChild(div);
        });
        results.classList.remove('hidden');
    }

    function highlightMatch(text, query) {
        if (!query || !text) return escapeHtml(text);
        var escaped = escapeHtml(text);
        var q = escapeHtml(query);
        var regex = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return escaped.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-700 text-inherit rounded px-0.5">$1</mark>');
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function selectMember(m) {
        document.getElementById('formUserId').value = m.id;
        document.getElementById('formName').value = m.name || '';
        document.getElementById('formEmail').value = m.email || '';
        document.getElementById('formPhone').value = m.phone || '';

        // 회원 프로필 사진을 스태프 사진 미리보기에 반영 + hidden 필드에 URL 저장
        document.getElementById('formMemberAvatarUrl').value = '';
        if (m.avatar) {
            document.getElementById('avatarPreview').innerHTML = '<img src="' + m.avatar.replace(/"/g, '&quot;') + '" class="w-full h-full object-cover">';
            document.getElementById('removeAvatarBtn').classList.remove('hidden');
            document.getElementById('formMemberAvatarUrl').value = m.avatar;
            console.log('[StaffManage] Member avatar URL set:', m.avatar);
        }

        var display = document.getElementById('linkedMemberDisplay');
        document.getElementById('linkedMemberName').textContent = m.name + ' (' + (m.email || m.id) + ')';
        display.classList.remove('hidden');
        display.classList.add('flex');
        document.getElementById('memberSearchWrap').classList.add('hidden');
        document.getElementById('memberSearchResults').classList.add('hidden');
        document.getElementById('memberSearch').value = '';
        console.log('[StaffManage] Member selected:', m.id, m.name);
    }

    window.unlinkMember = function() {
        document.getElementById('formUserId').value = '';
        document.getElementById('formMemberAvatarUrl').value = '';
        document.getElementById('linkedMemberDisplay').classList.add('hidden');
        document.getElementById('linkedMemberDisplay').classList.remove('flex');
        document.getElementById('memberSearchWrap').classList.remove('hidden');
        console.log('[StaffManage] Member unlinked');
    };

    // 모달 열기
    window.openStaffModal = function(data) {
        var isEdit = !!data;
        document.getElementById('modalTitle').textContent = isEdit ? '<?= __('staff.edit') ?>' : '<?= __('staff.create') ?>';
        document.getElementById('formAction').value = isEdit ? 'update' : 'create';
        document.getElementById('formId').value = isEdit ? data.id : '';
        document.getElementById('formUserId').value = isEdit ? (data.user_id || '') : '';
        document.getElementById('formName').value = isEdit ? data.name : '';
        document.getElementById('formEmail').value = isEdit ? (data.email || '') : '';
        document.getElementById('formPhone').value = isEdit ? (data.phone || '') : '';
        document.getElementById('formBio').value = isEdit ? (data.bio || '') : '';
        document.getElementById('formGreetingBefore').value = isEdit ? (data.greeting_before || '') : '';
        document.getElementById('formGreetingAfter').value = isEdit ? (data.greeting_after || '') : '';
        document.getElementById('formRemoveAvatar').value = '0';
        document.getElementById('formMemberAvatarUrl').value = '';
        croppedBlob = null; // 크롭 초기화

        var posEl = document.getElementById('formPosition');
        if (posEl) posEl.value = isEdit && data.position_id ? data.position_id : '';

        // 활성 상태
        var activeField = document.getElementById('activeField');
        var activeInput = document.getElementById('formActive');
        if (isEdit) {
            activeField.classList.remove('hidden');
            activeInput.checked = !!data.is_active;
        } else {
            activeField.classList.add('hidden');
            activeInput.checked = true;
        }

        // 아바타 미리보기
        var preview = document.getElementById('avatarPreview');
        var removeBtn = document.getElementById('removeAvatarBtn');
        if (isEdit && data.avatar) {
            preview.innerHTML = '<img src="' + data.avatar + '" class="w-full h-full object-cover">';
            removeBtn.classList.remove('hidden');
        } else {
            preview.innerHTML = '<svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
            removeBtn.classList.add('hidden');
        }
        document.getElementById('avatarInput').value = '';

        // 배너 미리보기
        var bannerPreview = document.getElementById('bannerPreview');
        var removeBannerBtn = document.getElementById('removeBannerBtn');
        if (isEdit && data.banner) {
            bannerPreview.innerHTML = '<img src="' + data.banner + '" class="w-full h-full object-cover">';
            removeBannerBtn.classList.remove('hidden');
        } else {
            bannerPreview.innerHTML = '<span class="text-xs text-zinc-400"><?= __('staff.fields.no_banner') ?? 'No banner' ?></span>';
            removeBannerBtn.classList.add('hidden');
        }
        document.getElementById('bannerInput').value = '';
        document.getElementById('formRemoveBanner').value = '0';

        // 회원 연동 표시
        if (isEdit && data.user_id) {
            document.getElementById('linkedMemberName').textContent = data.name + ' (' + (data.email || data.user_id) + ')';
            document.getElementById('linkedMemberDisplay').classList.remove('hidden');
            document.getElementById('linkedMemberDisplay').classList.add('flex');
            document.getElementById('memberSearchWrap').classList.add('hidden');
        } else {
            document.getElementById('linkedMemberDisplay').classList.add('hidden');
            document.getElementById('linkedMemberDisplay').classList.remove('flex');
            document.getElementById('memberSearchWrap').classList.remove('hidden');
        }
        document.getElementById('memberSearch').value = '';

        // 지명비
        var feeEl = document.getElementById('formDesignationFee');
        if (feeEl) feeEl.value = isEdit ? (data.designation_fee || 0) : 0;

        // 담당 서비스 초기화
        var selectedSvcs = isEdit ? (data.service_ids || []) : [];
        document.querySelectorAll('.svc-check').forEach(function(cb) {
            cb.checked = selectedSvcs.indexOf(cb.value) !== -1;
        });

        // 담당 번들 초기화
        var selectedBdls = isEdit ? (data.bundle_ids || []) : [];
        document.querySelectorAll('.bdl-check').forEach(function(cb) {
            cb.checked = selectedBdls.indexOf(cb.value) !== -1;
        });

        // 다국어 필드 초기화
        fillI18nFields('name', isEdit ? (data.name_i18n || {}) : {});
        fillI18nFields('bio', isEdit ? (data.bio_i18n || {}) : {});
        fillI18nFields('greeting_before', isEdit ? (data.greeting_before_i18n || {}) : {});
        fillI18nFields('greeting_after', isEdit ? (data.greeting_after_i18n || {}) : {});
        document.getElementById('nameI18nFields').classList.add('hidden');
        document.getElementById('bioI18nFields').classList.add('hidden');
        document.getElementById('greeting_beforeI18nFields').classList.add('hidden');
        document.getElementById('greeting_afterI18nFields').classList.add('hidden');

        document.getElementById('staffModal').classList.remove('hidden');
        document.getElementById('formName').focus();
        console.log('[StaffManage] Modal opened:', isEdit ? 'edit #' + data.id : 'create');
    };

    window.closeStaffModal = function() {
        document.getElementById('staffModal').classList.add('hidden');
        console.log('[StaffManage] Modal closed');
    };

    // 다국어 토글
    window.toggleI18n = function(field) {
        var el = document.getElementById(field + 'I18nFields');
        el.classList.toggle('hidden');
        console.log('[StaffManage] Toggle i18n:', field, !el.classList.contains('hidden'));
    };

    // 다국어 필드 채우기
    function fillI18nFields(field, data) {
        document.querySelectorAll('input[data-i18n="' + field + '"]').forEach(function(input) {
            input.value = data[input.dataset.lang] || '';
        });
    }

    // 다국어 필드 → JSON
    function collectI18n(field) {
        var result = {};
        document.querySelectorAll('input[data-i18n="' + field + '"]').forEach(function(input) {
            var val = input.value.trim();
            if (val) result[input.dataset.lang] = val;
        });
        return Object.keys(result).length > 0 ? JSON.stringify(result) : '';
    }

    // 크롭퍼 인스턴스
    var cropper = null;
    var croppedBlob = null; // 크롭된 이미지 Blob

    // 사진 선택 → 편집 모달 열기
    window.previewAvatar = function(input) {
        if (input.files && input.files[0]) {
            var file = input.files[0];
            // 파일 유효성 검사
            if (!file.type.startsWith('image/')) {
                console.warn('[StaffManage] Invalid file type:', file.type);
                return;
            }
            var reader = new FileReader();
            reader.onload = function(e) {
                openCropperModal(e.target.result);
            };
            reader.readAsDataURL(file);
            console.log('[StaffManage] Avatar selected for editing:', file.name);
        }
    };

    // 크롭퍼 모달 열기
    function openCropperModal(imageSrc) {
        var modal = document.getElementById('cropperModal');
        var img = document.getElementById('cropperImage');
        modal.classList.remove('hidden');
        img.src = imageSrc;
        img.style.display = 'block';

        // 기존 인스턴스 제거
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }

        // Cropper 초기화
        cropper = new Cropper(img, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.85,
            cropBoxResizable: true,
            cropBoxMovable: true,
            guides: false,
            center: true,
            highlight: false,
            background: true,
            responsive: true,
            restore: false,
            ready: function() {
                console.log('[StaffManage] Cropper ready');
            }
        });
        console.log('[StaffManage] Cropper modal opened');
    }

    // 크롭퍼 모달 닫기
    window.closeCropperModal = function() {
        document.getElementById('cropperModal').classList.add('hidden');
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        // 파일 인풋 초기화 (취소 시)
        document.getElementById('avatarInput').value = '';
        console.log('[StaffManage] Cropper modal closed');
    };

    // 도구 버튼 액션
    window.cropperAction = function(action) {
        if (!cropper) return;
        switch (action) {
            case 'zoomIn':    cropper.zoom(0.1); break;
            case 'zoomOut':   cropper.zoom(-0.1); break;
            case 'rotateLeft':  cropper.rotate(-90); break;
            case 'rotateRight': cropper.rotate(90); break;
            case 'flipH':
                var imgData = cropper.getImageData();
                cropper.scaleX(imgData.scaleX === -1 ? 1 : -1);
                break;
            case 'flipV':
                var imgDataV = cropper.getImageData();
                cropper.scaleY(imgDataV.scaleY === -1 ? 1 : -1);
                break;
            case 'reset':     cropper.reset(); break;
        }
        console.log('[StaffManage] Cropper action:', action);
    };

    // 크롭 적용
    window.applyCrop = function() {
        if (!cropper) return;
        var canvas = cropper.getCroppedCanvas({
            width: 400,
            height: 400,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });

        // 미리보기 업데이트
        var dataUrl = canvas.toDataURL('image/jpeg', 0.9);
        document.getElementById('avatarPreview').innerHTML = '<img src="' + dataUrl + '" class="w-full h-full object-cover">';
        document.getElementById('removeAvatarBtn').classList.remove('hidden');
        document.getElementById('formRemoveAvatar').value = '0';

        // Blob 저장 (폼 제출 시 사용)
        canvas.toBlob(function(blob) {
            croppedBlob = blob;
            console.log('[StaffManage] Crop applied, blob size:', blob.size);
        }, 'image/jpeg', 0.9);

        // 모달 닫기
        document.getElementById('cropperModal').classList.add('hidden');
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        console.log('[StaffManage] Crop applied');
    };

    window.removeAvatar = function() {
        document.getElementById('avatarPreview').innerHTML = '<svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
        document.getElementById('avatarInput').value = '';
        document.getElementById('removeAvatarBtn').classList.add('hidden');
        document.getElementById('formRemoveAvatar').value = '1';
        croppedBlob = null;
        console.log('[StaffManage] Avatar removed');
    };

    // === 배너 이미지 ===
    window.previewBanner = function(input) {
        if (input.files && input.files[0]) {
            var file = input.files[0];
            if (!file.type.startsWith('image/')) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('bannerPreview').innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover">';
                document.getElementById('removeBannerBtn').classList.remove('hidden');
                document.getElementById('formRemoveBanner').value = '0';
            };
            reader.readAsDataURL(file);
            console.log('[StaffManage] Banner selected:', file.name);
        }
    };

    window.removeBanner = function() {
        document.getElementById('bannerPreview').innerHTML = '<span class="text-xs text-zinc-400"><?= __('staff.fields.no_banner') ?? 'No banner' ?></span>';
        document.getElementById('bannerInput').value = '';
        document.getElementById('removeBannerBtn').classList.add('hidden');
        document.getElementById('formRemoveBanner').value = '1';
        console.log('[StaffManage] Banner removed');
    };

    // 폼 제출
    document.getElementById('staffForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.textContent = '...';

        // i18n JSON 세팅
        document.getElementById('formNameI18n').value = collectI18n('name');
        document.getElementById('formBioI18n').value = collectI18n('bio');
        document.getElementById('formGreetingBeforeI18n').value = collectI18n('greeting_before');
        document.getElementById('formGreetingAfterI18n').value = collectI18n('greeting_after');

        // 담당 서비스 수집
        var selectedServices = [];
        document.querySelectorAll('.svc-check:checked').forEach(function(cb) {
            selectedServices.push(cb.value);
        });
        document.getElementById('formServiceIds').value = JSON.stringify(selectedServices);
        console.log('[StaffManage] Selected services:', selectedServices.length);

        // 담당 번들 수집
        var selectedBundles = [];
        document.querySelectorAll('.bdl-check:checked').forEach(function(cb) {
            selectedBundles.push(cb.value);
        });
        document.getElementById('formBundleIds').value = JSON.stringify(selectedBundles);
        console.log('[StaffManage] Selected bundles:', selectedBundles.length);

        var formData = new FormData(this);

        // 크롭된 이미지가 있으면 원본 파일 대신 사용
        if (croppedBlob) {
            formData.delete('avatar');
            formData.append('avatar', croppedBlob, 'avatar.jpg');
            console.log('[StaffManage] Using cropped image, size:', croppedBlob.size);
        }

        try {
            var res = await fetch(STAFF_URL, { method: 'POST', body: formData });
            var result = await res.json();
            console.log('[StaffManage] Response:', result);
            if (result.success) {
                showAlert(result.message, 'success');
                closeStaffModal();
                setTimeout(function() { location.reload(); }, 500);
            } else {
                showAlert(result.message, 'error');
            }
        } catch (err) {
            console.error('[StaffManage] Error:', err);
            showAlert('<?= __('staff.error.server') ?>', 'error');
        }

        btn.disabled = false;
        btn.textContent = '<?= __('admin.common.save') ?>';
    });

    // 삭제
    window.deleteStaff = async function(id, name) {
        if (!confirm('<?= __('staff.confirm_delete') ?>')) return;
        try {
            var formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            var res = await fetch(STAFF_URL, { method: 'POST', body: formData });
            var result = await res.json();
            console.log('[StaffManage] Delete result:', result);
            if (result.success) {
                var card = document.getElementById('staff-card-' + id);
                if (card) card.remove();
                showAlert(result.message, 'success');
            } else {
                showAlert(result.message, 'error');
            }
        } catch (err) {
            console.error('[StaffManage] Delete error:', err);
            showAlert('<?= __('staff.error.server') ?>', 'error');
        }
    };

    // 알림
    function showAlert(msg, type) {
        var box = document.getElementById('alertBox');
        box.className = 'mb-6 p-4 rounded-lg border ' + (type === 'success'
            ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800'
            : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800');
        box.textContent = msg;
        box.classList.remove('hidden');
        setTimeout(function() { box.classList.add('hidden'); }, 4000);
    }

    // ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeStaffModal();
    });

    // 초기화
    initMemberSearch();
    console.log('[StaffManage] Module initialized');
})();
</script>
