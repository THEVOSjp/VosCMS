<?php
/**
 * RezlyX Admin - 서비스 관리 모달 폼
 * services.php에서 include
 */
?>

<!-- ═══ 서비스 추가/수정 모달 ═══ -->
<div id="serviceModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeServiceModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto relative">
            <!-- 헤더 -->
            <div class="sticky top-0 bg-white dark:bg-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between rounded-t-2xl z-10">
                <h2 id="serviceModalTitle" class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('services.create') ?></h2>
                <button onclick="closeServiceModal()" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- 폼 -->
            <form id="serviceForm" class="p-6 space-y-4">
                <input type="hidden" id="svcId" name="id" value="">
                <input type="hidden" id="svcAction" name="action" value="create_service">

                <!-- 서비스명 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('services.fields.name') ?> <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="text" id="svcName" name="name" required
                               class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                               placeholder="<?= __('services.placeholder_name') ?>">
                        <?= rzx_multilang_btn("openServiceMultilang('name')") ?>
                    </div>
                </div>

                <!-- 슬러그 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('services.fields.slug') ?></label>
                    <input type="text" id="svcSlug" name="slug"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                           placeholder="<?= __('services.placeholder_slug') ?>">
                </div>

                <!-- 카테고리 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('services.fields.category') ?></label>
                    <select id="svcCategory" name="category_id"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <option value=""><?= __('services.select_none') ?></option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars(getCategoryTranslated($cat['id'], 'name', $cat['name'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 가격 + 소요시간 -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('services.fields.price') ?> (<?= $currency ?>)</label>
                        <input type="number" id="svcPrice" name="price" min="0" step="100" value="0"
                               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('services.fields.duration') ?></label>
                        <select id="svcDuration" name="duration"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <?php foreach ([10, 15, 20, 30, 40, 45, 60, 90, 120, 150, 180] as $min): ?>
                            <option value="<?= $min ?>" <?= $min === 30 ? 'selected' : '' ?>><?= $min ?><?= __('services.minute') ?><?= $min >= 60 ? ' (' . floor($min/60) . 'h' . ($min%60 ? $min%60 . 'm' : '') . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- 버퍼 시간 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('services.fields.buffer_time') ?></label>
                    <select id="svcBuffer" name="buffer_time"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <option value="0"><?= __('services.no_buffer') ?></option>
                        <option value="5">5<?= __('services.minute') ?></option>
                        <option value="10">10<?= __('services.minute') ?></option>
                        <option value="15">15<?= __('services.minute') ?></option>
                        <option value="30">30<?= __('services.minute') ?></option>
                    </select>
                </div>

                <!-- 설명 -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('services.fields.description') ?></label>
                        <?= rzx_multilang_btn("openServiceMultilang('description')") ?>
                    </div>
                    <textarea id="svcDescription" name="description" rows="3"
                              class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm resize-none"
                              placeholder="<?= __('services.placeholder_description') ?>"></textarea>
                </div>

                <!-- 서비스 이미지 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('services.fields.image') ?></label>
                    <div id="svcImageArea" class="relative border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg hover:border-blue-400 dark:hover:border-blue-500 transition cursor-pointer"
                         onclick="document.getElementById('svcImageInput').click()"
                         ondragover="event.preventDefault();this.classList.add('border-blue-500','bg-blue-50','dark:bg-blue-900/20')"
                         ondragleave="this.classList.remove('border-blue-500','bg-blue-50','dark:bg-blue-900/20')"
                         ondrop="event.preventDefault();this.classList.remove('border-blue-500','bg-blue-50','dark:bg-blue-900/20');handleImageDrop(event)">
                        <!-- 업로드 안내 (이미지 없을 때) -->
                        <div id="svcImagePlaceholder" class="flex flex-col items-center justify-center py-6 text-zinc-400 dark:text-zinc-500">
                            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <p class="text-xs"><?= __('services.image_upload_hint') ?></p>
                            <p class="text-[10px] mt-1 text-zinc-300 dark:text-zinc-600"><?= __('services.image_formats') ?></p>
                        </div>
                        <!-- 이미지 미리보기 (이미지 있을 때) -->
                        <div id="svcImagePreview" class="hidden relative">
                            <img id="svcImagePreviewImg" src="" alt="" class="w-full rounded-lg object-cover" style="max-height: 200px;">
                            <button type="button" onclick="event.stopPropagation();removeServiceImage()"
                                    class="absolute top-2 right-2 p-1 bg-red-600 hover:bg-red-700 text-white rounded-full shadow-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <input type="file" id="svcImageInput" name="image" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" onchange="previewServiceImage(this)">
                    <input type="hidden" id="svcImageExisting" name="existing_image" value="">
                    <input type="hidden" id="svcImageRemove" name="remove_image" value="0">
                    <!-- 이미지 크기 설정 -->
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('services.image_size') ?>:</span>
                        <input type="number" id="svcImageWidth" name="image_width" min="50" max="2000" value="800" placeholder="W"
                               class="w-20 px-2 py-1 text-xs border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                        <span class="text-xs text-zinc-400">×</span>
                        <input type="number" id="svcImageHeight" name="image_height" min="50" max="2000" value="600" placeholder="H"
                               class="w-20 px-2 py-1 text-xs border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                        <span class="text-xs text-zinc-400">px</span>
                    </div>
                </div>

                <!-- 활성 상태 -->
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="svcActive" name="is_active" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 dark:bg-zinc-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                    </label>
                    <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('services.fields.is_active') ?></span>
                </div>
            </form>

            <!-- 하단 버튼 -->
            <div class="sticky bottom-0 bg-white dark:bg-zinc-800 px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end gap-3 rounded-b-2xl">
                <button onclick="closeServiceModal()" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                    <?= __('common.buttons.cancel') ?>
                </button>
                <button onclick="saveService()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                    <?= __('common.buttons.save') ?>
                </button>
            </div>
        </div>
    </div>
</div>
