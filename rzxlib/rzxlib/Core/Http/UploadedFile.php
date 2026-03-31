<?php

declare(strict_types=1);

namespace RzxLib\Core\Http;

/**
 * UploadedFile - 업로드된 파일
 *
 * HTTP 파일 업로드 처리
 *
 * @package RzxLib\Core\Http
 */
class UploadedFile
{
    /**
     * 임시 파일 경로
     */
    protected string $tempPath;

    /**
     * 원본 파일명
     */
    protected string $originalName;

    /**
     * MIME 타입
     */
    protected string $mimeType;

    /**
     * 파일 크기 (바이트)
     */
    protected int $size;

    /**
     * 업로드 에러 코드
     */
    protected int $error;

    /**
     * UploadedFile 생성자
     */
    public function __construct(
        string $tempPath,
        string $originalName,
        string $mimeType,
        int $size,
        int $error
    ) {
        $this->tempPath = $tempPath;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->error = $error;
    }

    /**
     * 임시 파일 경로
     */
    public function path(): string
    {
        return $this->tempPath;
    }

    /**
     * 원본 파일명
     */
    public function getClientOriginalName(): string
    {
        return $this->originalName;
    }

    /**
     * 원본 확장자
     */
    public function getClientOriginalExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    /**
     * MIME 타입
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * 실제 MIME 타입 (finfo 사용)
     */
    public function getClientMimeType(): string
    {
        if (!file_exists($this->tempPath)) {
            return $this->mimeType;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($this->tempPath) ?: $this->mimeType;
    }

    /**
     * 파일 크기 (바이트)
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * 에러 코드
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * 에러 메시지
     */
    public function getErrorMessage(): ?string
    {
        return match ($this->error) {
            UPLOAD_ERR_OK => null,
            UPLOAD_ERR_INI_SIZE => '파일 크기가 php.ini의 upload_max_filesize를 초과했습니다.',
            UPLOAD_ERR_FORM_SIZE => '파일 크기가 폼의 MAX_FILE_SIZE를 초과했습니다.',
            UPLOAD_ERR_PARTIAL => '파일이 일부만 업로드되었습니다.',
            UPLOAD_ERR_NO_FILE => '파일이 업로드되지 않았습니다.',
            UPLOAD_ERR_NO_TMP_DIR => '임시 폴더가 없습니다.',
            UPLOAD_ERR_CANT_WRITE => '디스크에 파일을 쓸 수 없습니다.',
            UPLOAD_ERR_EXTENSION => 'PHP 확장이 업로드를 중단했습니다.',
            default => '알 수 없는 업로드 오류가 발생했습니다.',
        };
    }

    /**
     * 업로드 성공 여부
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->tempPath);
    }

    /**
     * 파일 이동
     */
    public function move(string $directory, ?string $name = null): string
    {
        if (!$this->isValid()) {
            throw new \RuntimeException($this->getErrorMessage() ?? '유효하지 않은 업로드 파일입니다.');
        }

        // 디렉토리 생성
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $name = $name ?? $this->hashName();
        $target = rtrim($directory, '/') . '/' . $name;

        if (!move_uploaded_file($this->tempPath, $target)) {
            throw new \RuntimeException('파일을 이동할 수 없습니다.');
        }

        return $target;
    }

    /**
     * 지정된 경로에 저장
     */
    public function store(string $path, ?string $name = null): string
    {
        return $this->move($path, $name);
    }

    /**
     * 해시 기반 파일명 생성
     */
    public function hashName(?string $path = null): string
    {
        $hash = bin2hex(random_bytes(20));
        $extension = $this->getClientOriginalExtension();

        $name = $hash . ($extension ? '.' . $extension : '');

        return $path ? rtrim($path, '/') . '/' . $name : $name;
    }

    /**
     * 이미지 파일 확인
     */
    public function isImage(): bool
    {
        return str_starts_with($this->getClientMimeType(), 'image/');
    }

    /**
     * 이미지 크기 가져오기
     */
    public function getImageDimensions(): ?array
    {
        if (!$this->isImage()) {
            return null;
        }

        $size = @getimagesize($this->tempPath);

        if ($size === false) {
            return null;
        }

        return [
            'width' => $size[0],
            'height' => $size[1],
        ];
    }

    /**
     * 파일 내용 읽기
     */
    public function getContent(): string
    {
        return file_get_contents($this->tempPath) ?: '';
    }

    /**
     * 스트림으로 열기
     */
    public function openStream(string $mode = 'rb')
    {
        return fopen($this->tempPath, $mode);
    }
}
