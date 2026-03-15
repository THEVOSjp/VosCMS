<?php
/**
 * RezlyX 이미지 업로드 헬퍼
 * 서비스, 프로필 등 이미지 업로드 공통 처리
 */

/**
 * 파일 업로드 + GD 리사이즈(cover crop)
 *
 * @param string $fieldName   $_FILES 키
 * @param string $subDir      storage/uploads/ 하위 디렉토리
 * @param string $filePrefix  파일명 접두사
 * @param int    $targetW     목표 너비 (px)
 * @param int    $targetH     목표 높이 (px)
 * @return string|null        성공 시 상대 경로 (uploads/...), 실패 시 null
 */
function rzx_upload_image($fieldName, $subDir, $filePrefix, $targetW = 800, $targetH = 600) {
    if (empty($_FILES[$fieldName]['tmp_name']) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmpFile = $_FILES[$fieldName]['tmp_name'];
    $mime = mime_content_type($tmpFile);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime])) return null;

    $ext = $allowed[$mime];
    $uploadDir = BASE_PATH . '/storage/uploads/' . $subDir;
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $filename = $filePrefix . '_' . time() . '.' . $ext;
    $destPath = $uploadDir . '/' . $filename;

    // 이미지 리사이즈 (GD)
    $targetW = max(50, min(2000, $targetW));
    $targetH = max(50, min(2000, $targetH));

    $srcImg = null;
    switch ($mime) {
        case 'image/jpeg': $srcImg = imagecreatefromjpeg($tmpFile); break;
        case 'image/png':  $srcImg = imagecreatefrompng($tmpFile); break;
        case 'image/webp': $srcImg = imagecreatefromwebp($tmpFile); break;
        case 'image/gif':  $srcImg = imagecreatefromgif($tmpFile); break;
    }

    if ($srcImg) {
        $srcW = imagesx($srcImg);
        $srcH = imagesy($srcImg);

        // 비율 유지하면서 target 크기에 맞춤 (cover crop)
        $ratio = max($targetW / $srcW, $targetH / $srcH);
        $newW = (int)($srcW * $ratio);
        $newH = (int)($srcH * $ratio);
        $cropX = (int)(($newW - $targetW) / 2);
        $cropY = (int)(($newH - $targetH) / 2);

        $tmpImg = imagecreatetruecolor($newW, $newH);
        imagealphablending($tmpImg, false);
        imagesavealpha($tmpImg, true);
        imagecopyresampled($tmpImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        $finalImg = imagecreatetruecolor($targetW, $targetH);
        imagealphablending($finalImg, false);
        imagesavealpha($finalImg, true);
        imagecopy($finalImg, $tmpImg, 0, 0, $cropX, $cropY, $targetW, $targetH);

        switch ($mime) {
            case 'image/jpeg': imagejpeg($finalImg, $destPath, 85); break;
            case 'image/png':  imagepng($finalImg, $destPath, 8); break;
            case 'image/webp': imagewebp($finalImg, $destPath, 85); break;
            case 'image/gif':  imagegif($finalImg, $destPath); break;
        }

        imagedestroy($srcImg);
        imagedestroy($tmpImg);
        imagedestroy($finalImg);
    } else {
        move_uploaded_file($tmpFile, $destPath);
    }

    return 'uploads/' . $subDir . '/' . $filename;
}

/**
 * 기존 이미지 파일 삭제
 */
function rzx_delete_image($relativePath) {
    if (empty($relativePath)) return;
    $fullPath = BASE_PATH . '/storage/' . $relativePath;
    if (file_exists($fullPath)) unlink($fullPath);
}
