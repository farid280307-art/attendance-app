<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\LeaveRequest;
use App\Services\LeaveService;
use App\Validators\LeaveValidator;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class AdminLeaveController
{
    public function index(): void
    {
        \view('admin.leave-requests.index', [
            'user' => \auth_user(),
            'requests' => (new LeaveRequest(\db()))->getForAdmin(),
            'success' => \flash('success'),
            'error' => \flash('error'),
        ]);
    }

    public function show(): void
    {
        $request = $this->findRequest($_GET['id'] ?? null);

        if ($request === null) {
            return;
        }

        $this->renderShow($request);
    }

    public function approve(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $request = $this->findRequest($_POST['id'] ?? null);

        if ($request === null) {
            return;
        }

        if ($request['status'] !== 'pending') {
            $this->redirectConflict((int) $request['id']);
        }

        $reviewerId = \auth_id();

        if ($reviewerId === null) {
            \redirect('/login');
        }

        try {
            $updated = (new LeaveService(\db()))->approve(
                (int) $request['id'],
                $reviewerId,
                new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')),
                $this->requestContext()
            );
        } catch (Throwable $exception) {
            error_log('Approval pengajuan gagal: ' . $exception->getMessage());
            \flash('error', 'Pengajuan tidak dapat diproses saat ini.');
            \redirect('/admin/leave-requests/show?id=' . $request['id']);
        }

        if (!$updated) {
            $this->redirectConflict((int) $request['id']);
        }

        \flash('success', 'Pengajuan berhasil disetujui.');
        \redirect('/admin/leave-requests/show?id=' . $request['id']);
    }

    public function reject(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $request = $this->findRequest($_POST['id'] ?? null);

        if ($request === null) {
            return;
        }

        if ($request['status'] !== 'pending') {
            $this->redirectConflict((int) $request['id']);
        }

        $validation = (new LeaveValidator())->validateRejection($_POST);

        if (!$validation['valid']) {
            http_response_code(422);
            $this->renderShow($request, $validation['errors'], $validation['data']['rejection_reason']);
            return;
        }

        $reviewerId = \auth_id();

        if ($reviewerId === null) {
            \redirect('/login');
        }

        try {
            $updated = (new LeaveService(\db()))->reject(
                (int) $request['id'],
                $reviewerId,
                $validation['data']['rejection_reason'],
                new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')),
                $this->requestContext()
            );
        } catch (Throwable $exception) {
            error_log('Penolakan pengajuan gagal: ' . $exception->getMessage());
            \flash('error', 'Pengajuan tidak dapat diproses saat ini.');
            \redirect('/admin/leave-requests/show?id=' . $request['id']);
        }

        if (!$updated) {
            $this->redirectConflict((int) $request['id']);
        }

        \flash('success', 'Pengajuan berhasil ditolak.');
        \redirect('/admin/leave-requests/show?id=' . $request['id']);
    }

    /** @return array<string, mixed>|null */
    private function findRequest(mixed $rawId): ?array
    {
        $id = \positive_int($rawId);
        $request = $id === null ? null : (new LeaveRequest(\db()))->findByIdWithUser($id);

        if ($request === null) {
            \abort(404, 'errors.404');
            return null;
        }

        return $request;
    }

    /** @param array<string, mixed> $request @param array<string, string> $errors */
    private function renderShow(array $request, array $errors = [], string $rejectionReason = ''): void
    {
        \view('admin.leave-requests.show', [
            'user' => \auth_user(),
            'request' => $request,
            'errors' => $errors,
            'rejectionReason' => $rejectionReason,
            'success' => \flash('success'),
            'error' => \flash('error'),
        ]);
    }

    private function redirectConflict(int $requestId): never
    {
        \flash('error', 'Pengajuan sudah diproses sebelumnya.');
        \redirect('/admin/leave-requests/show?id=' . $requestId);
    }

    private function csrfIsValid(): bool
    {
        if (\verify_csrf()) {
            return true;
        }

        \abort(419, 'errors.419');
        return false;
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
