<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ActivityLog;
use App\Models\WorkLocation;
use App\Validators\WorkLocationValidator;
use Throwable;

final class WorkLocationController
{
    public function index(): void
    {
        \view('admin.work-locations.index', [
            'user' => \auth_user(),
            'locations' => (new WorkLocation(\db()))->getAll(),
            'success' => \flash('success'),
        ]);
    }

    public function create(): void
    {
        $this->renderForm('admin.work-locations.create', [
            'name' => '',
            'latitude' => '',
            'longitude' => '',
            'radius_meters' => 100,
            'is_active' => 1,
        ]);
    }

    public function store(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $validation = (new WorkLocationValidator())->validate($_POST);
        if (!$validation['valid']) {
            http_response_code(422);
            $this->renderForm('admin.work-locations.create', $validation['data'], $validation['errors']);
            return;
        }

        $model = new WorkLocation(\db());
        $id = $model->create($validation['data']);
        $this->log(
            'work_location.created',
            sprintf('Lokasi kerja #%d (%s) ditambahkan.', $id, $validation['data']['name'])
        );

        \flash('success', 'Lokasi kerja berhasil ditambahkan.');
        \redirect('/admin/work-locations');
    }

    public function edit(): void
    {
        $id = \positive_int($_GET['id'] ?? null);
        $location = $id === null ? null : (new WorkLocation(\db()))->findById($id);

        if ($location === null) {
            \abort(404, 'errors.404');
            return;
        }

        $this->renderForm('admin.work-locations.edit', $location, [], $id);
    }

    public function update(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $id = \positive_int($_POST['id'] ?? null);
        $model = new WorkLocation(\db());
        $location = $id === null ? null : $model->findById($id);

        if ($location === null) {
            \abort(404, 'errors.404');
            return;
        }

        $validation = (new WorkLocationValidator())->validate($_POST);
        if (!$validation['valid']) {
            http_response_code(422);
            $this->renderForm('admin.work-locations.edit', $validation['data'], $validation['errors'], $id);
            return;
        }

        $model->update($id, $validation['data']);
        $this->log(
            'work_location.updated',
            sprintf('Lokasi kerja #%d (%s) diperbarui.', $id, $validation['data']['name'])
        );

        \flash('success', 'Lokasi kerja berhasil diperbarui.');
        \redirect('/admin/work-locations');
    }

    public function toggleStatus(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $id = \positive_int($_POST['id'] ?? null);
        $model = new WorkLocation(\db());
        $location = $id === null ? null : $model->findById($id);

        if ($location === null) {
            \abort(404, 'errors.404');
            return;
        }

        $isActive = (int) $location['is_active'] !== 1;
        $model->setActive($id, $isActive);
        $action = $isActive ? 'activated' : 'deactivated';
        $status = $isActive ? 'diaktifkan' : 'dinonaktifkan';
        $this->log(
            'work_location.' . $action,
            sprintf('Lokasi kerja #%d (%s) %s.', $id, $location['name'], $status)
        );

        \flash('success', 'Lokasi kerja berhasil ' . $status . '.');
        \redirect('/admin/work-locations');
    }

    /** @param array<string, mixed> $formData @param array<string, string> $errors */
    private function renderForm(string $viewName, array $formData, array $errors = [], ?int $id = null): void
    {
        \view($viewName, [
            'user' => \auth_user(),
            'formData' => $formData,
            'errors' => $errors,
            'locationId' => $id,
        ]);
    }

    private function csrfIsValid(): bool
    {
        if (\verify_csrf()) {
            return true;
        }

        \abort(419, 'errors.419');
        return false;
    }

    private function log(string $action, string $description): void
    {
        try {
            (new ActivityLog(\db()))->create(
                \auth_id(),
                $action,
                $description,
                $this->serverValue('REMOTE_ADDR', 45),
                $this->serverValue('HTTP_USER_AGENT', 2000)
            );
        } catch (Throwable $exception) {
            error_log('Activity log gagal disimpan: ' . $exception->getMessage());
        }
    }

    private function serverValue(string $key, int $maxLength): ?string
    {
        $value = $_SERVER[$key] ?? null;

        return is_string($value) && $value !== '' ? substr($value, 0, $maxLength) : null;
    }
}
