<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeInterface;
use finfo;
use RuntimeException;

final class ImageService
{
    private const MAX_FILE_SIZE = 3 * 1024 * 1024;
    private const MIN_DIMENSION = 240;
    private const MAX_DIMENSION = 4000;

    private string $storageRoot;

    public function __construct(?string $storageRoot = null)
    {
        $this->storageRoot = rtrim($storageRoot ?? BASE_PATH . '/storage', '/\\');
    }

    /** @param array<string, mixed> $upload */
    public function storeAttendanceSelfie(array $upload, DateTimeInterface $date): string
    {
        $temporaryPath = $this->validateAttendanceSelfie($upload);
        $relativeDirectory = sprintf(
            'attendance/%s/%s/%s',
            $date->format('Y'),
            $date->format('m'),
            $date->format('d')
        );
        $targetDirectory = $this->storageRoot . '/' . $relativeDirectory;

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0750, true) && !is_dir($targetDirectory)) {
            throw new RuntimeException('Direktori penyimpanan selfie tidak dapat dibuat.');
        }

        $filename = bin2hex(random_bytes(16)) . '.jpg';
        $relativePath = $relativeDirectory . '/' . $filename;
        $targetPath = $this->storageRoot . '/' . $relativePath;

        if (!move_uploaded_file($temporaryPath, $targetPath)) {
            throw new RuntimeException('Selfie tidak dapat disimpan.');
        }

        @chmod($targetPath, 0640);

        return str_replace('\\', '/', $relativePath);
    }

    public function delete(string $relativePath): void
    {
        $normalized = str_replace('\\', '/', ltrim($relativePath, '/\\'));

        if ($normalized === '' || str_contains($normalized, '../')) {
            return;
        }

        $path = $this->storageRoot . '/' . $normalized;
        $root = realpath($this->storageRoot);
        $file = realpath($path);

        if ($root === false || $file === false || !is_file($file)) {
            return;
        }

        $rootPrefix = rtrim(str_replace('\\', '/', $root), '/') . '/';
        $normalizedFile = str_replace('\\', '/', $file);

        if (str_starts_with($normalizedFile, $rootPrefix)) {
            unlink($file);
        }
    }

    /** @param array<string, mixed> $upload */
    private function validateAttendanceSelfie(array $upload): string
    {
        $error = $upload['error'] ?? UPLOAD_ERR_NO_FILE;

        if (!is_int($error) || $error !== UPLOAD_ERR_OK) {
            throw new AttendanceException('Selfie wajib diambil ulang dan dikirim.', 422, true, [
                'selfie' => 'Upload selfie tidak valid.',
            ]);
        }

        $temporaryPath = $upload['tmp_name'] ?? null;

        if (!is_string($temporaryPath) || $temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            throw new AttendanceException('File selfie tidak valid.', 422, true, [
                'selfie' => 'File upload tidak dikenali.',
            ]);
        }

        $actualSize = filesize($temporaryPath);

        if ($actualSize === false || $actualSize < 1 || $actualSize > self::MAX_FILE_SIZE) {
            throw new AttendanceException('Ukuran selfie harus lebih dari 0 byte dan maksimal 3 MB.', 422, true, [
                'selfie' => 'Ukuran selfie tidak valid.',
            ]);
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
        $imageInfo = @getimagesize($temporaryPath);

        if ($mime !== 'image/jpeg' || !is_array($imageInfo) || ($imageInfo[2] ?? null) !== IMAGETYPE_JPEG) {
            throw new AttendanceException('Selfie harus berupa gambar JPEG yang valid.', 422, true, [
                'selfie' => 'Format selfie harus JPEG.',
            ]);
        }

        $width = (int) ($imageInfo[0] ?? 0);
        $height = (int) ($imageInfo[1] ?? 0);

        if (
            $width < self::MIN_DIMENSION
            || $height < self::MIN_DIMENSION
            || $width > self::MAX_DIMENSION
            || $height > self::MAX_DIMENSION
        ) {
            throw new AttendanceException('Dimensi selfie harus antara 240x240 dan 4000x4000 piksel.', 422, true, [
                'selfie' => 'Dimensi selfie tidak valid.',
            ]);
        }

        return $temporaryPath;
    }
}
