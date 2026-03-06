<?php
/**
 * RezlyX - 이미지 크롭 컴포넌트
 *
 * Cropper.js를 사용하여 이미지 크롭 기능을 제공합니다.
 * 프로필 사진 등 정사각형 이미지에 최적화되어 있습니다.
 *
 * 사용 변수:
 * - $cropperConfig: 설정 배열
 *   - id: 컴포넌트 ID (기본: 'image_cropper')
 *   - inputName: 폼 필드 이름 (기본: 'cropped_image')
 *   - aspectRatio: 가로세로 비율 (기본: 1 = 정사각형)
 *   - outputWidth: 출력 이미지 너비 (기본: 400)
 *   - outputHeight: 출력 이미지 높이 (기본: 400)
 *   - outputFormat: 출력 포맷 (기본: 'image/jpeg')
 *   - outputQuality: JPEG 품질 0-1 (기본: 0.9)
 *   - cropBoxResizable: 크롭 박스 크기 조절 가능 (기본: true)
 *   - translations: 번역 데이터
 */

// 기본 설정
$cropperId = $cropperConfig['id'] ?? 'image_cropper';
$inputName = $cropperConfig['inputName'] ?? 'cropped_image';
$aspectRatio = $cropperConfig['aspectRatio'] ?? 1;
$outputWidth = $cropperConfig['outputWidth'] ?? 400;
$outputHeight = $cropperConfig['outputHeight'] ?? 400;
$outputFormat = $cropperConfig['outputFormat'] ?? 'image/jpeg';
$outputQuality = $cropperConfig['outputQuality'] ?? 0.9;
$cropBoxResizable = $cropperConfig['cropBoxResizable'] ?? true;
$_cropperTranslations = $cropperConfig['translations'] ?? [];

// 번역 기본값 (원래 $translations 변수를 덮어쓰지 않도록 별도 변수 사용)
$t = array_merge([
    'title' => '이미지 편집',
    'select_image' => '이미지 선택',
    'drag_drop' => '또는 이미지를 여기에 드래그하세요',
    'zoom_in' => '확대',
    'zoom_out' => '축소',
    'rotate_left' => '왼쪽 회전',
    'rotate_right' => '오른쪽 회전',
    'reset' => '초기화',
    'cancel' => '취소',
    'apply' => '적용',
    'file_too_large' => '파일 크기가 너무 큽니다. 최대 5MB까지 업로드 가능합니다.',
    'invalid_file_type' => '지원하지 않는 파일 형식입니다. JPG, PNG, GIF, WebP만 가능합니다.',
], $_cropperTranslations);
?>

<!-- Cropper.js CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">

<!-- 이미지 크롭 모달 -->
<div id="<?= $cropperId ?>_modal" class="fixed inset-0 z-[100] hidden">
    <!-- 배경 오버레이 -->
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="ImageCropper.close('<?= $cropperId ?>')"></div>

    <!-- 모달 콘텐츠 -->
    <div class="fixed inset-4 md:inset-10 lg:inset-20 bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl flex flex-col overflow-hidden">
        <!-- 헤더 -->
        <div class="flex items-center justify-between p-4 border-b dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($t['title']) ?></h2>
            <button type="button" onclick="ImageCropper.close('<?= $cropperId ?>')"
                    class="p-2 text-gray-500 hover:text-gray-700 dark:text-zinc-400 dark:hover:text-zinc-200 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- 크롭 영역 -->
        <div class="flex-1 flex items-center justify-center p-4 bg-gray-100 dark:bg-zinc-900 overflow-hidden">
            <div id="<?= $cropperId ?>_container" class="relative w-full h-full flex items-center justify-center">
                <!-- 이미지 선택 전 표시 -->
                <div id="<?= $cropperId ?>_placeholder" class="text-center">
                    <div class="w-24 h-24 mx-auto mb-4 rounded-full bg-gray-200 dark:bg-zinc-700 flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <label for="<?= $cropperId ?>_file_input"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg cursor-pointer hover:bg-blue-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <?= htmlspecialchars($t['select_image']) ?>
                    </label>
                    <p class="mt-2 text-sm text-gray-500 dark:text-zinc-400"><?= htmlspecialchars($t['drag_drop']) ?></p>
                </div>

                <!-- 크롭 이미지 (선택 후 표시) -->
                <img id="<?= $cropperId ?>_image" class="hidden max-w-full max-h-full">
            </div>
        </div>

        <!-- 툴바 -->
        <div id="<?= $cropperId ?>_toolbar" class="hidden p-4 border-t dark:border-zinc-700 bg-white dark:bg-zinc-800">
            <div class="flex items-center justify-center gap-2">
                <!-- 확대 -->
                <button type="button" onclick="ImageCropper.zoom('<?= $cropperId ?>', 0.1)"
                        class="p-2 text-gray-600 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= htmlspecialchars($t['zoom_in']) ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                    </svg>
                </button>
                <!-- 축소 -->
                <button type="button" onclick="ImageCropper.zoom('<?= $cropperId ?>', -0.1)"
                        class="p-2 text-gray-600 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= htmlspecialchars($t['zoom_out']) ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/>
                    </svg>
                </button>

                <div class="w-px h-6 bg-gray-300 dark:bg-zinc-600 mx-2"></div>

                <!-- 왼쪽 회전 -->
                <button type="button" onclick="ImageCropper.rotate('<?= $cropperId ?>', -90)"
                        class="p-2 text-gray-600 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= htmlspecialchars($t['rotate_left']) ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                </button>
                <!-- 오른쪽 회전 -->
                <button type="button" onclick="ImageCropper.rotate('<?= $cropperId ?>', 90)"
                        class="p-2 text-gray-600 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= htmlspecialchars($t['rotate_right']) ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6"/>
                    </svg>
                </button>

                <div class="w-px h-6 bg-gray-300 dark:bg-zinc-600 mx-2"></div>

                <!-- 초기화 -->
                <button type="button" onclick="ImageCropper.reset('<?= $cropperId ?>')"
                        class="p-2 text-gray-600 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= htmlspecialchars($t['reset']) ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- 푸터 -->
        <div class="flex items-center justify-between p-4 border-t dark:border-zinc-700">
            <button type="button" onclick="ImageCropper.close('<?= $cropperId ?>')"
                    class="px-4 py-2 text-gray-700 dark:text-zinc-300 font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700 transition">
                <?= htmlspecialchars($t['cancel']) ?>
            </button>
            <button type="button" id="<?= $cropperId ?>_apply_btn" onclick="ImageCropper.apply('<?= $cropperId ?>')"
                    class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                <?= htmlspecialchars($t['apply']) ?>
            </button>
        </div>
    </div>
</div>

<!-- 숨겨진 파일 인풋 -->
<input type="file" id="<?= $cropperId ?>_file_input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">

<!-- 크롭된 이미지 데이터를 저장할 숨겨진 필드 -->
<input type="hidden" id="<?= $cropperId ?>_data" name="<?= htmlspecialchars($inputName) ?>">

<!-- Cropper.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<script>
/**
 * ImageCropper - 이미지 크롭 관리자
 */
const ImageCropper = (function() {
    'use strict';

    // 크로퍼 인스턴스 저장
    const croppers = {};
    const configs = {};

    /**
     * 크로퍼 초기화
     */
    function init(id, config) {
        configs[id] = {
            aspectRatio: config.aspectRatio || 1,
            outputWidth: config.outputWidth || 400,
            outputHeight: config.outputHeight || 400,
            outputFormat: config.outputFormat || 'image/jpeg',
            outputQuality: config.outputQuality || 0.9,
            cropBoxResizable: config.cropBoxResizable !== false,
            onApply: config.onApply || null,
            translations: config.translations || {}
        };

        const fileInput = document.getElementById(id + '_file_input');
        const modal = document.getElementById(id + '_modal');
        const container = document.getElementById(id + '_container');

        if (!fileInput || !modal) {
            console.error('[ImageCropper] 필수 요소를 찾을 수 없습니다:', id);
            return;
        }

        // 파일 선택 이벤트
        fileInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                loadImage(id, e.target.files[0]);
            }
        });

        // 드래그 앤 드롭
        container.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('ring-2', 'ring-blue-500');
        });

        container.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('ring-2', 'ring-blue-500');
        });

        container.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('ring-2', 'ring-blue-500');

            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                loadImage(id, e.dataTransfer.files[0]);
            }
        });

        // ESC 키로 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                close(id);
            }
        });

        console.log('[ImageCropper] 초기화 완료:', id);
    }

    /**
     * 이미지 로드
     */
    function loadImage(id, file) {
        const config = configs[id];
        const t = config.translations;

        // 파일 크기 검증 (최대 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert(t.file_too_large || '파일 크기가 너무 큽니다. 최대 5MB까지 업로드 가능합니다.');
            return;
        }

        // 파일 형식 검증
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert(t.invalid_file_type || '지원하지 않는 파일 형식입니다.');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const image = document.getElementById(id + '_image');
            const placeholder = document.getElementById(id + '_placeholder');
            const toolbar = document.getElementById(id + '_toolbar');
            const applyBtn = document.getElementById(id + '_apply_btn');

            // 기존 크로퍼 제거
            if (croppers[id]) {
                croppers[id].destroy();
                croppers[id] = null;
            }

            // 이미지 표시
            image.src = e.target.result;
            image.classList.remove('hidden');
            placeholder.classList.add('hidden');
            toolbar.classList.remove('hidden');
            applyBtn.disabled = false;

            // 크로퍼 생성
            image.onload = function() {
                croppers[id] = new Cropper(image, {
                    aspectRatio: config.aspectRatio,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.8,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: config.cropBoxResizable,
                    toggleDragModeOnDblclick: false,
                    background: true,
                    responsive: true,
                });
                console.log('[ImageCropper] 크로퍼 생성됨:', id);
            };
        };
        reader.readAsDataURL(file);
    }

    /**
     * 모달 열기
     */
    function open(id) {
        const modal = document.getElementById(id + '_modal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            console.log('[ImageCropper] 모달 열림:', id);
        }
    }

    /**
     * 모달 닫기
     */
    function close(id) {
        const modal = document.getElementById(id + '_modal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';

            // 상태 초기화
            resetState(id);
            console.log('[ImageCropper] 모달 닫힘:', id);
        }
    }

    /**
     * 상태 초기화
     */
    function resetState(id) {
        const image = document.getElementById(id + '_image');
        const placeholder = document.getElementById(id + '_placeholder');
        const toolbar = document.getElementById(id + '_toolbar');
        const applyBtn = document.getElementById(id + '_apply_btn');
        const fileInput = document.getElementById(id + '_file_input');

        // 크로퍼 제거
        if (croppers[id]) {
            croppers[id].destroy();
            croppers[id] = null;
        }

        // UI 초기화
        if (image) {
            image.classList.add('hidden');
            image.src = '';
        }
        if (placeholder) placeholder.classList.remove('hidden');
        if (toolbar) toolbar.classList.add('hidden');
        if (applyBtn) applyBtn.disabled = true;
        if (fileInput) fileInput.value = '';
    }

    /**
     * 확대/축소
     */
    function zoom(id, ratio) {
        if (croppers[id]) {
            croppers[id].zoom(ratio);
        }
    }

    /**
     * 회전
     */
    function rotate(id, degree) {
        if (croppers[id]) {
            croppers[id].rotate(degree);
        }
    }

    /**
     * 초기화
     */
    function reset(id) {
        if (croppers[id]) {
            croppers[id].reset();
        }
    }

    /**
     * 크롭 적용
     */
    function apply(id) {
        const config = configs[id];
        const cropper = croppers[id];

        if (!cropper) {
            console.error('[ImageCropper] 크로퍼가 없습니다:', id);
            return;
        }

        // 크롭된 이미지 가져오기
        const canvas = cropper.getCroppedCanvas({
            width: config.outputWidth,
            height: config.outputHeight,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        if (!canvas) {
            console.error('[ImageCropper] 캔버스 생성 실패');
            return;
        }

        // Base64로 변환
        const dataUrl = canvas.toDataURL(config.outputFormat, config.outputQuality);

        // 숨겨진 필드에 저장
        const dataInput = document.getElementById(id + '_data');
        if (dataInput) {
            dataInput.value = dataUrl;
        }

        // 콜백 실행
        if (typeof config.onApply === 'function') {
            config.onApply(dataUrl, canvas, id);
        }

        // 커스텀 이벤트 발생 (재사용성 향상)
        const event = new CustomEvent('imageCropped', {
            detail: { id: id, dataUrl: dataUrl, canvas: canvas }
        });
        document.dispatchEvent(event);

        // 모달 닫기
        close(id);

        console.log('[ImageCropper] 크롭 적용됨:', id);
    }

    /**
     * onApply 콜백 설정
     */
    function setOnApply(id, callback) {
        if (configs[id]) {
            configs[id].onApply = callback;
        }
    }

    /**
     * 트리거 버튼 클릭 시 모달 열기
     */
    function trigger(id) {
        open(id);
        // 파일 선택 다이얼로그 열기
        const fileInput = document.getElementById(id + '_file_input');
        if (fileInput) {
            fileInput.click();
        }
    }

    // Public API
    return {
        init: init,
        open: open,
        close: close,
        zoom: zoom,
        rotate: rotate,
        reset: reset,
        apply: apply,
        trigger: trigger,
        loadImage: loadImage,
        setOnApply: setOnApply
    };
})();

// 자동 초기화
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($cropperId)): ?>
    ImageCropper.init('<?= $cropperId ?>', {
        aspectRatio: <?= $aspectRatio ?>,
        outputWidth: <?= $outputWidth ?>,
        outputHeight: <?= $outputHeight ?>,
        outputFormat: '<?= $outputFormat ?>',
        outputQuality: <?= $outputQuality ?>,
        cropBoxResizable: <?= $cropBoxResizable ? 'true' : 'false' ?>,
        translations: <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>
    });
    <?php endif; ?>
});
</script>
