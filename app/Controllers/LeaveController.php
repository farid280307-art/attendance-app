<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\LeaveRequest;
use App\Services\LeaveAttachmentService;
use App\Services\LeaveException;
use App\Services\LeaveService;
use App\Validators\LeaveValidator;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class LeaveController
{
    public function index(): void
    {
        $user = \auth_user();
        $userId = \auth_id();

        if ($user === null || $userId === null) {
            \redirect('/login');
        }

        \view('leave.index', [
            'user' => $user,
            'requests' => (new LeaveRequest(\db()))->getForUser($userId),
            'success' => \flash('success'),
            'error' => \flash('error'),
        ]);
    }

    public function create(): void
    {
        $this->renderCreate([
            'type' => '',
            'start_date' => '',
            'end_date' => '',
            'reason' => '',
        ]);
    }

    public function store(): void
    {
        if (!\verify_csrf()) {
            \abort(419, 'errors.419');
            return;
        }

        $userId = \auth_id();

        if ($userId === null) {
            \redirect('/login');
        }

        $validation = (new LeaveValidator())->validateCreate($_POST);

        if (!$validation['valid']) {
            http_response_code(422);
            $this->renderCreate($validation['data'], $validation['errors']);
            return;
        }

        try {
            (new LeaveService(\db()))->create(
                $userId,
                $validation['data'],
                is_array($_FILES['attachment'] ?? null) ? $_FILES['attachment'] : [],
                new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')),
                $this->requestContext()
            );
        } catch (LeaveException $exception) {
            http_response_code($exception->httpStatus());
            $this->renderCreate(
                $validation['data'],
                $exception->errors(),
                $exception->getMessage()
            );
            return;
        } catch (Throwable $exception) {
            error_log('Pembuatan pengajuan gagal: ' . $exception->getMessage());
            \flash('error', 'Pengajuan tidak dapat disimpan saat ini. Silakan coba lagi.');
            \redirect('/leave/create');
        }

        \flash('success', 'Pengajuan berhasil dikirim.');
        \redirect('/leave');
    }

    public function show(): void
    {
        $id = \positive_int($_GET['id'] ?? null);
        $userId = \auth_id();
        $request = $id === null ? null : (new LeaveRequest(\db()))->findById($id);

        if ($request === null || $userId === null || (int) $request['user_id'] !== $userId) {
            \abort(404, 'errors.404');
            return;
        }

        \view('leave.show', [
            'user' => \auth_user(),
            'request' => $request,
        ]);
    }

    public function attachment(): void
    {
        $id = \positive_int($_GET['id'] ?? null);
        $userId = \auth_id();
        $request = $id === null ? null : (new LeaveRequest(\db()))->findById($id);

        if (
            $request === null
            || $userId === null
            || ($request['attachment'] ?? null) === null
            || (!\is_admin() && (int) $request['user_id'] !== $userId)
        ) {
            \abort(404, 'errors.404');
            return;
        }

        $file = (new LeaveAttachmentService())->resolve((string) $request['attachment']);

        if ($file === null) {
            \abort(404, 'errors.404');
            return;
        }

        if (headers_sent()) {
            return;
        }

        header('Content-Type: ' . $file['mime']);
        header('Content-Length: ' . $file['size']);
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        header(sprintf(
            'Content-Disposition: inline; filename="lampiran-pengajuan-%d.%s"',
            (int) $request['id'],
            $file['extension']
        ));
        readfile($file['path']);
        exit;
    }

    /** @param array<string, mixed> $formData @param array<string, string> $errors */
    private function renderCreate(array $formData, array $errors = [], ?string $error = null): void
    {
        \view('leave.create', [
            'user' => \auth_user(),
            'formData' => $formData,
            'errors' => $errors,
            'error' => $error ?? \flash('error'),
        ]);
    }

    /** @return array{ip_address:?string,user_agent:?string} */
    private function requestContext(): array
    {
        return [
            'ip_address' => $this->serverValue('REMOTE_ADDR', 45),
            'user_agent' => $this->serverValue('HTTP_USER_AGENT', 2000),
        ];
    }

    private function serverValue(string $key, int $maxLength): ?string
    {
        $value = $_SERVER[$key] ?? null;

        return is_string($value) && $value !== '' ? substr($value, 0, $maxLength) : null;
    }
}
