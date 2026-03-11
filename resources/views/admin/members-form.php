<?php
/**
 * RezlyX Admin - 회원 추가/수정 모달 폼
 * 회원가입 설정(member_register_fields)에 따라 동적 필드 표시
 * 프로필 사진 편집(Cropper.js), 국제전화번호 컴포넌트 포함
 */
$registerFields = $registerFields ?? ['name', 'email', 'password', 'phone'];
$inputClass = 'w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm';
$labelClass = 'block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1';
?>

<div id="memberModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeMemberModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto relative">
            <div class="sticky top-0 bg-white dark:bg-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between rounded-t-2xl z-10">
                <h2 id="memberModalTitle" class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('admin.members.list.edit') ?></h2>
                <button onclick="closeMemberModal()" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="memberForm" class="p-6 space-y-4" enctype="multipart/form-data">
                <input type="hidden" id="memberId" name="id" value="">
                <input type="hidden" id="memberAction" name="action" value="update_member">

                <!-- 프로필 사진 -->
                <?php if (in_array('profile_photo', $registerFields)): ?>
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <div id="memberAvatarPreview" class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-600 flex items-center justify-center overflow-hidden">
                            <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="<?= $labelClass ?>"><?= __('admin.members.settings.register.fields.profile_photo') ?></label>
                        <input type="file" id="memberProfileImage" name="profile_image" accept="image/jpeg,image/png,image/webp"
                               class="text-sm text-zinc-600 dark:text-zinc-400 file:mr-2 file:px-3 file:py-1 file:rounded-lg file:border-0 file:text-sm file:bg-blue-50 dark:file:bg-blue-900/30 file:text-blue-600 dark:file:text-blue-400 file:cursor-pointer"
                               onchange="openMemberCropper(this)">
                        <p class="text-xs text-zinc-400 mt-1">JPG, PNG, WebP (400x400)</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 이름 (필수, 항상 표시) -->
                <div>
                    <label class="<?= $labelClass ?>"><?= __('admin.members.settings.register.fields.name') ?> <span class="text-red-500">*</span></label>
                    <input type="text" id="memberName" name="name" required class="<?= $inputClass ?>">
                </div>

                <!-- 이메일 (필수, 항상 표시) -->
                <div>
                    <label class="<?= $labelClass ?>"><?= __('admin.members.settings.register.fields.email') ?> <span class="text-red-500">*</span></label>
                    <input type="email" id="memberEmail" name="email" required class="<?= $inputClass ?>">
                </div>

                <!-- 비밀번호 (추가 시 필수, 수정 시 선택) -->
                <div id="memberPasswordField">
                    <label class="<?= $labelClass ?>">
                        <?= __('admin.members.settings.register.fields.password') ?>
                        <span id="memberPasswordRequired" class="text-red-500 hidden">*</span>
                    </label>
                    <input type="password" id="memberPassword" name="password" class="<?= $inputClass ?>" autocomplete="new-password">
                    <p id="memberPasswordHint" class="hidden text-xs text-zinc-400 mt-1"><?= __('admin.members.list.password_hint') ?></p>
                </div>

                <!-- 전화번호 (국제전화번호 컴포넌트) -->
                <?php if (in_array('phone', $registerFields)): ?>
                <div id="memberPhoneField">
                    <?php
                    $phoneInputConfig = [
                        'name' => 'phone',
                        'id' => 'memberPhone',
                        'label' => __('admin.members.settings.register.fields.phone'),
                        'value' => '',
                        'country_code' => '+82',
                        'phone_number' => '',
                        'required' => false,
                        'show_label' => true,
                        'placeholder' => '010-1234-5678',
                    ];
                    include BASE_PATH . '/resources/views/components/phone-input.php';
                    ?>
                </div>
                <?php endif; ?>

                <!-- 생년월일 -->
                <?php if (in_array('birth_date', $registerFields)): ?>
                <div id="memberBirthField">
                    <label class="<?= $labelClass ?>"><?= __('admin.members.settings.register.fields.birth_date') ?></label>
                    <input type="date" id="memberBirthDate" name="birth_date" class="<?= $inputClass ?>">
                </div>
                <?php endif; ?>

                <!-- 성별 + 회사 (2열) -->
                <?php if (in_array('gender', $registerFields) || in_array('company', $registerFields)): ?>
                <div class="grid grid-cols-2 gap-3">
                    <?php if (in_array('gender', $registerFields)): ?>
                    <div id="memberGenderField">
                        <label class="<?= $labelClass ?>"><?= __('admin.members.settings.register.fields.gender') ?></label>
                        <select id="memberGender" name="gender" class="<?= $inputClass ?>">
                            <option value="">-</option>
                            <option value="male"><?= __('admin.members.list.gender_male') ?></option>
                            <option value="female"><?= __('admin.members.list.gender_female') ?></option>
                            <option value="other"><?= __('admin.members.list.gender_other') ?></option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if (in_array('company', $registerFields)): ?>
                    <div id="memberCompanyField">
                        <label class="<?= $labelClass ?>"><?= __('admin.members.settings.register.fields.company') ?></label>
                        <input type="text" id="memberCompany" name="company" class="<?= $inputClass ?>">
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- 블로그 -->
                <?php if (in_array('blog', $registerFields)): ?>
                <div id="memberBlogField">
                    <label class="<?= $labelClass ?>"><?= __('admin.members.settings.register.fields.blog') ?></label>
                    <input type="url" id="memberBlog" name="blog" class="<?= $inputClass ?>" placeholder="https://">
                </div>
                <?php endif; ?>

                <!-- 등급 + 상태 (2열) -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="<?= $labelClass ?>"><?= __('admin.members.list.col_grade') ?></label>
                        <select id="memberGrade" name="grade_id" class="<?= $inputClass ?>">
                            <option value=""><?= __('admin.members.list.no_grade') ?></option>
                            <?php foreach ($grades as $g): ?>
                            <option value="<?= htmlspecialchars($g['id']) ?>"><?= htmlspecialchars($g['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="<?= $labelClass ?>"><?= __('admin.members.list.col_status') ?></label>
                        <select id="memberStatus" name="status" class="<?= $inputClass ?>">
                            <option value="active"><?= __('admin.members.list.status_active') ?></option>
                            <option value="inactive"><?= __('admin.members.list.status_inactive') ?></option>
                            <option value="withdrawn"><?= __('admin.members.list.status_withdrawn') ?></option>
                        </select>
                    </div>
                </div>

                <!-- 회원 정보 (수정 시만 표시) -->
                <div id="memberInfoBox" class="hidden p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg text-xs text-zinc-500 dark:text-zinc-400 space-y-1">
                    <div class="flex justify-between"><span>ID</span><span id="memberInfoId" class="font-mono"></span></div>
                    <div class="flex justify-between"><span><?= __('admin.members.list.col_joined') ?></span><span id="memberInfoJoined"></span></div>
                    <div class="flex justify-between"><span><?= __('admin.members.list.last_login') ?></span><span id="memberInfoLogin"></span></div>
                </div>
            </form>

            <div class="sticky bottom-0 bg-white dark:bg-zinc-800 px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end gap-3 rounded-b-2xl">
                <button onclick="closeMemberModal()" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                    <?= __('common.buttons.cancel') ?>
                </button>
                <button onclick="saveMember()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                    <?= __('common.buttons.save') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 프로필 사진 Cropper 모달 -->
<?php if (in_array('profile_photo', $registerFields)): ?>
<div id="memberCropperModal" class="fixed inset-0 z-[60] hidden">
    <div class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white"><?= __('admin.staff.photo_editor.title') ?></h3>
                <button onclick="closeMemberCropper()" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-4">
                <div style="height:340px;" class="bg-zinc-100 dark:bg-zinc-900 rounded-lg overflow-hidden">
                    <img id="memberCropperImage" src="" class="max-w-full">
                </div>
                <!-- 툴바 -->
                <div class="flex items-center justify-center gap-2 mt-3">
                    <button type="button" onclick="memberCropperAction('zoom', 0.1)" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg" title="<?= __('admin.staff.photo_editor.zoom_in') ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                    </button>
                    <button type="button" onclick="memberCropperAction('zoom', -0.1)" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg" title="<?= __('admin.staff.photo_editor.zoom_out') ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/></svg>
                    </button>
                    <button type="button" onclick="memberCropperAction('rotate', -90)" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg" title="<?= __('admin.staff.photo_editor.rotate_left') ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a4 4 0 014 4v2M3 10l4-4M3 10l4 4"/></svg>
                    </button>
                    <button type="button" onclick="memberCropperAction('rotate', 90)" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg" title="<?= __('admin.staff.photo_editor.rotate_right') ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a4 4 0 00-4 4v2M21 10l-4-4M21 10l-4 4"/></svg>
                    </button>
                    <button type="button" onclick="memberCropperAction('reset')" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg" title="<?= __('admin.staff.photo_editor.reset') ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M4 9a9 9 0 0114.32-4.32M20 15a9 9 0 01-14.32 4.32"/></svg>
                    </button>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-3">
                <button onclick="closeMemberCropper()" class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg"><?= __('common.buttons.cancel') ?></button>
                <button onclick="applyMemberCrop()" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg"><?= __('admin.staff.photo_editor.apply') ?></button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
