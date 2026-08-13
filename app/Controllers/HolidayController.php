<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Holiday;
use App\Services\HolidayException;
use App\Services\HolidayService;
use App\Validators\HolidayValidator;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class HolidayController
{
    public function index(): void
    {
        \view('admin.holidays.index', [
            'user' => \auth_user(),
            'holidays' => (new Holiday(\db()))->getAll(),
            'success' => \flash('success'),
            'error' => \flash('error'),
        ]);
    }

    public function create(): void
    {
        $this->renderForm('admin.holidays.create', [
            'holiday_date' => '',
            'name' => '',
            'is_active' => 1,
        ]);
    }

    public function store(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $validation = (new HolidayValidator())->validate($_POST);

        if (!$validation['valid']) {
            http_response_code(422);
            $this->renderForm('admin.holidays.create', $validation['data'], $validation['errors']);
            return;
        }

        try {
            (new HolidayService(\db()))->create(
                $validation['data'],
                $this->today(),
                $this->requestContext()
            );
        } catch (HolidayException $exception) {
            http_response_code(422);
            $this->renderForm('admin.holidays.create', $validation['data'], ['holiday_date' => $exception->getMessage()]);
            return;
        } catch (Throwable $exception) {
            error_log('Pembuatan hari libur gagal: ' . $exception->getMessage());
            \flash('error', 'Hari libur tidak dapat ditambahkan saat ini.');
            \redirect('/admin/holidays');
        }

        \flash('success', 'Hari libur berhasil ditambahkan.');
        \redirect('/admin/holidays');
    }

    public function edit(): void
    {
        $holiday = $this->findHoliday($_GET['id'] ?? null);

        if ($holiday !== null) {
            $this->renderForm('admin.holidays.edit', $holiday, [], (int) $holiday['id']);
        }
    }

    public function update(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $holiday = $this->findHoliday($_POST['id'] ?? null);

        if ($holiday === null) {
            return;
        }

        $validation = (new HolidayValidator())->validate($_POST);

        if (!$validation['valid']) {
            http_response_code(422);
            $this->renderForm('admin.holidays.edit', $validation['data'], $validation['errors'], (int) $holiday['id']);
            return;
        }

        try {
            (new HolidayService(\db()))->update(
                (int) $holiday['id'],
                (string) $holiday['holiday_date'],
                $validation['data'],
                $this->today(),
                $this->requestContext()
            );
        } catch (HolidayException $exception) {
            http_response_code(422);
            $this->renderForm('admin.holidays.edit', $validation['data'], ['holiday_date' => $exception->getMessage()], (int) $holiday['id']);
            return;
        } catch (Throwable $exception) {
            error_log('Pembaruan hari libur gagal: ' . $exception->getMessage());
            \flash('error', 'Hari libur tidak dapat diperbarui saat ini.');
            \redirect('/admin/holidays');
        }

        \flash('success', 'Hari libur berhasil diperbarui.');
        \redirect('/admin/holidays');
    }

    public function toggleStatus(): void
    {
        if (!$this->csrfIsValid()) {
            return;
        }

        $holiday = $this->findHoliday($_POST['id'] ?? null);

        if ($holiday === null) {
            return;
        }

        $active = (int) $holiday['is_active'] !== 1;

        try {
            (new HolidayService(\db()))->setActive(
                (int) $holiday['id'],
                (string) $holiday['holiday_date'],
                (string) $holiday['name'],
                $active,
                $this->today(),
                $this->requestContext()
            );
        } catch (Throwable $exception) {
            error_log('Perubahan status hari libur gagal: ' . $exception->getMessage());
            \flash('error', 'Status hari libur tidak dapat diperbarui saat ini.');
            \redirect('/admin/holidays');
        }

        \flash('success', $active ? 'Hari libur diaktifkan.' : 'Hari libur dinonaktifkan.');
        \redirect('/admin/holidays');
    }

    /** @return array<string,mixed>|null */
    private function findHoliday(mixed $rawId): ?array
    {
        $id = \positive_int($rawId);
        $holiday = $id === null ? null : (new Holiday(\db()))->findById($id);

        if ($holiday === null) {
            \abort(404, 'errors.404');
            return null;
        }

        return $holiday;
    }

    /** @param array<string,mixed> $data @param array<string,string> $errors */
    private function renderForm(string $view, array $data, array $errors = [], ?int $holidayId = null): void
    {
        \view($view, [
            'user' => \auth_user(),
            'formData' => $data,
            'errors' => $errors,
            'holidayId' => $holidayId,
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

    private function today(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
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
