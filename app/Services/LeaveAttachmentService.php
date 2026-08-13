<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeInterface;
use finfo;
use RuntimeException;

final class LeaveAttachmentService
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;
    private const MIME_EXTENSIONS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    private string $storageRoot;

    public function __construct(?string $storageRoot = null)
    {
        $this->storageRoot = rtrim($storageRoot ?? BASE_PATH . '/storage', '/\\');
    }

    /** @param array<string, mixed> $upload */
    public function storeOptional(array $upload, DateTimeInterface $date): ?string
    {
        $error = $upload['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($upload === [] || $error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (!is_int($error) || $error !== UPLOAD_ERR_OK) {
            $message = in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? 'Ukuran lampiran melebihi batas upload server atau 5 MB.'
                : 'Lampiran gagal diunggah. Silakan pilih file kembali.';
            throw new LeaveException($message, 422, ['attachment' => $message]);
        }

        $temporaryPath = $upload['tmp_name'] ?? null;

        if (!is_string($temporaryPath) || $temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            throw new LeaveException('Lampiran tidak valid.', 422, [
                'attachment' => 'File upload tidak dikenali oleh server.',
            ]);
        }

        $size = filesize($temporaryPath);

        if ($size === false || $size < 1 || $size > self::MAX_FILE_SIZE) {
            throw new LeaveException('Ukuran lampiran harus lebih dari 0 byte dan maksimal 5 MB.', 422, [
                'attachment' => 'Ukuran lampiran tidak valid.',
            ]);
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);

        if (!is_string($mime) || !isset(self::MIME_EXTENSIONS[$mime])) {
            throw new LeaveException('Lampiran harus berupa PDF, JPEG, atau PNG yang valid.', 422, [
                'attachment' => 'Format lampiran tidak diizinkan.',
            ]);
        }

        if (str_starts_with($mime, 'image/') && !$this->isValidImage($temporaryPath, $mime)) {
            throw new LeaveException('Lampiran gambar tidak valid.', 422, [
                'attachment' => 'Isi file tidak sesuai dengan format gambar.',
            ]);
        }

        $relativeDirectory = sprintf('leave/%s/%s', $date->format('Y'), $date->format('m'));
        $targetDirectory = $this->storageRoot . '/' . $relativeDirectory;

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0750, true) && !is_dir($targetDirectory)) {
            throw new RuntimeException('Direktori penyimpanan lampiran tidak dapat dibuat.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . self::MIME_EXTENSIONS[$mime];
        $relativePath = $relativeDirectory . '/' . $filename;
        $targetPath = $this->storageRoot . '/' . $relativePath;

        if (!move_uploaded_file($temporaryPath, $targetPath)) {
            throw new RuntimeException('Lampiran tidak dapat disimpan.');
        }

        @chmod($targetPath, 0640);

        return str_replace('\\', '/', $relativePath);
    }

    public function delete(?string $relativePath): void
    {
        if ($relativePath === null) {
            return;
        }

        $resolved = $this->resolve($relativePath);

        if ($resolved !== null) {
            @unlink($resolved['path']);
        }
    }

    /** @return array{path:string,mime:string,extension:string,size:int}|null */
    public function resolve(string $relativePath): ?array
    {
        $normalized = str_replace('\\', '/', trim($relativePath));

        if (preg_match('#^leave/\d{4}/\d{2}/[a-f0-9]{32}\.(pdf|jpg|png)$#', $normalized, $matches) !== 1) {
            return null;
        }

        $leaveRoot = realpath($this->storageRoot . '/leave');
        $filePath = realpath($this->storageRoot . '/' . $normalized);

        if ($leaveRoot === false || $filePath === false || !is_file($filePath)) {
            return null;
        }

        $rootPrefix = rtrim(str_replace('\\', '/', $leaveRoot), '/') . '/';
        $normalizedFile = str_replace('\\', '/', $filePath);

        if (!str_starts_with($normalizedFile, $rootPrefix)) {
            return null;
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($filePath);
        $expectedExtension = is_string($mime) ? (self::MIME_EXTENSIONS[$mime] ?? null) : null;

        if ($expectedExtension === null || $expectedExtension !== $matches[1]) {
            return null;
        }

        if (str_starts_with($mime, 'image/') && !$this->isValidImage($filePath, $mime)) {
            return null;
        }

        $size = filesize($filePath);

        if ($size === false) {
            return null;
        }

        return [
            'path' => $filePath,
            'mime' => $mime,
            'extension' => $expectedExtension,
            'size' => $size,
        ];
    }

    private function isValidImage(string $path, string $mime): bool
    {
        $imageInfo = @getimagesize($path);
        $expectedType = $mime === 'image/jpeg' ? IMAGETYPE_JPEG : IMAGETYPE_PNG;

        return is_array($imageInfo)
            && ($imageInfo[2] ?? null) === $expectedType
            && (int) ($imageInfo[0] ?? 0) > 0
            && (int) ($imageInfo[1] ?? 0) > 0;
    }
}
