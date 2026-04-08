<?php
/**
 * RezlyX - Image Helper
 * 이미지 처리 및 저장을 위한 헬퍼 클래스
 *
 * @package RzxLib\Core\Helpers
 */

namespace RzxLib\Core\Helpers;

class ImageHelper
{
    /** @var string 업로드 기본 경로 */
    private string $uploadPath;

    /** @var array 허용된 MIME 타입 */
    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /** @var int 최대 파일 크기 (바이트) */
    private int $maxFileSize = 5 * 1024 * 1024; // 5MB

    /**
     * 생성자
     *
     * @param string|null $uploadPath 업로드 경로 (기본: uploads/profiles)
     */
    public function __construct(?string $uploadPath = null)
    {
        $this->uploadPath = $uploadPath ?? (defined('BASE_PATH')
            ? BASE_PATH . '/uploads/profiles'
            : dirname(__DIR__, 3) . '/uploads/profiles');

        // 디렉토리가 없으면 생성
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Base64 인코딩된 이미지 저장
     *
     * @param string $base64Data Base64 데이터 (data:image/jpeg;base64,... 형식)
     * @param string|null $filename 저장할 파일명 (확장자 제외, null이면 UUID 생성)
     * @param string $subDir 하위 디렉토리 (기본: '')
     * @return array ['success' => bool, 'path' => string, 'url' => string, 'error' => string|null]
     */
    public function saveBase64Image(string $base64Data, ?string $filename = null, string $subDir = ''): array
    {
        try {
            // Base64 데이터 파싱
            $parsed = $this->parseBase64Data($base64Data);
            if (!$parsed['success']) {
                return $parsed;
            }

            $mimeType = $parsed['mime_type'];
            $imageData = $parsed['data'];

            // MIME 타입 검증
            if (!in_array($mimeType, $this->allowedMimeTypes)) {
                return [
                    'success' => false,
                    'error' => 'Unsupported image type: ' . $mimeType,
                ];
            }

            // 파일 크기 검증
            if (strlen($imageData) > $this->maxFileSize) {
                return [
                    'success' => false,
                    'error' => 'Image size exceeds maximum allowed size',
                ];
            }

            // 확장자 결정
            $extension = $this->getExtensionFromMimeType($mimeType);

            // 파일명 생성
            $filename = $filename ?? $this->generateUniqueFilename();
            $finalFilename = $filename . '.' . $extension;

            // 저장 경로 설정
            $savePath = $this->uploadPath;
            if ($subDir) {
                $savePath .= '/' . trim($subDir, '/');
                if (!is_dir($savePath)) {
                    mkdir($savePath, 0755, true);
                }
            }

            $fullPath = $savePath . '/' . $finalFilename;

            // 파일 저장
            if (file_put_contents($fullPath, $imageData) === false) {
                return [
                    'success' => false,
                    'error' => 'Failed to save image file',
                ];
            }

            // 상대 URL 생성
            $relativePath = str_replace(
                defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3),
                '',
                $fullPath
            );
            $relativePath = str_replace('\\', '/', $relativePath);

            return [
                'success' => true,
                'path' => $fullPath,
                'relative_path' => $relativePath,
                'filename' => $finalFilename,
                'mime_type' => $mimeType,
                'size' => strlen($imageData),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Base64 데이터 파싱
     *
     * @param string $base64Data
     * @return array
     */
    private function parseBase64Data(string $base64Data): array
    {
        // data:image/jpeg;base64,/9j/4AAQ... 형식 파싱
        if (preg_match('/^data:([a-zA-Z0-9\/+]+);base64,(.+)$/', $base64Data, $matches)) {
            $mimeType = $matches[1];
            $data = base64_decode($matches[2], true);

            if ($data === false) {
                return [
                    'success' => false,
                    'error' => 'Invalid Base64 encoding',
                ];
            }

            return [
                'success' => true,
                'mime_type' => $mimeType,
                'data' => $data,
            ];
        }

        // 순수 Base64만 있는 경우 (MIME 타입 없이)
        $data = base64_decode($base64Data, true);
        if ($data === false) {
            return [
                'success' => false,
                'error' => 'Invalid Base64 data',
            ];
        }

        // finfo로 MIME 타입 감지
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($data);

        return [
            'success' => true,
            'mime_type' => $mimeType,
            'data' => $data,
        ];
    }

    /**
     * MIME 타입에서 확장자 추출
     *
     * @param string $mimeType
     * @return string
     */
    private function getExtensionFromMimeType(string $mimeType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $map[$mimeType] ?? 'jpg';
    }

    /**
     * 고유 파일명 생성
     *
     * @return string
     */
    private function generateUniqueFilename(): string
    {
        return date('Ymd_His') . '_' . bin2hex(random_bytes(8));
    }

    /**
     * 프로필 이미지 저장 (회원가입/프로필 수정용)
     *
     * @param string $base64Data Base64 이미지 데이터
     * @param int|string $userId 사용자 ID
     * @return array
     */
    public function saveProfileImage(string $base64Data, int|string $userId): array
    {
        $filename = 'profile_' . $userId . '_' . time();
        return $this->saveBase64Image($base64Data, $filename, 'profiles');
    }

    /**
     * 이미지 파일 삭제
     *
     * @param string $path 파일 경로 (절대 경로 또는 상대 경로)
     * @return bool
     */
    public function deleteImage(string $path): bool
    {
        // 상대 경로인 경우 절대 경로로 변환
        if (!str_starts_with($path, '/') && !preg_match('/^[A-Z]:/i', $path)) {
            $path = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3)) . '/' . ltrim($path, '/');
        }

        if (file_exists($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * 이미지 리사이즈 (GD 라이브러리 사용)
     *
     * @param string $sourcePath 원본 이미지 경로
     * @param int $width 목표 너비
     * @param int $height 목표 높이
     * @param string|null $destPath 저장 경로 (null이면 원본 덮어쓰기)
     * @return bool
     */
    public function resizeImage(string $sourcePath, int $width, int $height, ?string $destPath = null): bool
    {
        if (!file_exists($sourcePath)) {
            return false;
        }

        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        $mimeType = $imageInfo['mime'];
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];

        // 원본 이미지 생성
        $sourceImage = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png' => imagecreatefrompng($sourcePath),
            'image/gif' => imagecreatefromgif($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default => false,
        };

        if (!$sourceImage) {
            return false;
        }

        // 리사이즈된 이미지 생성
        $destImage = imagecreatetruecolor($width, $height);

        // PNG/GIF 투명도 유지
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($destImage, false);
            imagesavealpha($destImage, true);
            $transparent = imagecolorallocatealpha($destImage, 0, 0, 0, 127);
            imagefill($destImage, 0, 0, $transparent);
        }

        // 리사이즈
        imagecopyresampled(
            $destImage,
            $sourceImage,
            0, 0, 0, 0,
            $width, $height,
            $sourceWidth, $sourceHeight
        );

        // 저장
        $destPath = $destPath ?? $sourcePath;
        $result = match ($mimeType) {
            'image/jpeg' => imagejpeg($destImage, $destPath, 90),
            'image/png' => imagepng($destImage, $destPath, 9),
            'image/gif' => imagegif($destImage, $destPath),
            'image/webp' => imagewebp($destImage, $destPath, 90),
            default => false,
        };

        // 메모리 해제
        imagedestroy($sourceImage);
        imagedestroy($destImage);

        return $result;
    }

    /**
     * 업로드 경로 설정
     *
     * @param string $path
     * @return self
     */
    public function setUploadPath(string $path): self
    {
        $this->uploadPath = rtrim($path, '/\\');
        return $this;
    }

    /**
     * 최대 파일 크기 설정
     *
     * @param int $bytes
     * @return self
     */
    public function setMaxFileSize(int $bytes): self
    {
        $this->maxFileSize = $bytes;
        return $this;
    }
}
