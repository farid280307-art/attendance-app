<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Holiday;
use App\Models\WorkCalendar;
use PDO;
use PDOException;
use Throwable;

final class HolidayService
{
    private Holiday $holidays;
    private WorkCalendar $calendars;

    public function __construct(private PDO $pdo)
    {
        $this->holidays = new Holiday($pdo);
        $this->calendars = new WorkCalendar($pdo);
    }

    /** @param array{holiday_date:string,name:string,is_active:int} $data @param array<string,?string> $context */
    public function create(array $data, string $today, array $context = []): int
    {
        $this->ensureDateAvailable($data['holiday_date']);

        try {
            $id = $this->transaction(function () use ($data, $today): int {
                $id = $this->holidays->create($data);

                if ($data['holiday_date'] >= $today) {
                    $this->calendars->deleteDateForAllUsers($data['holiday_date']);
                }

                return $id;
            });
        } catch (PDOException $exception) {
            throw $this->safeDatabaseException($exception);
        }

        $this->log($context, 'holiday.created', sprintf('Hari libur #%d (%s) ditambahkan.', $id, $data['name']));

        return $id;
    }

    /** @param array{holiday_date:string,name:string,is_active:int} $data @param array<string,?string> $context */
    public function update(int $id, string $previousDate, array $data, string $today, array $context = []): void
    {
        $this->ensureDateAvailable($data['holiday_date'], $id);

        try {
            $this->transaction(function () use ($id, $previousDate, $data, $today): void {
                $this->holidays->update($id, $data);

                foreach (array_unique([$previousDate, $data['holiday_date']]) as $date) {
                    if ($date >= $today) {
                        $this->calendars->deleteDateForAllUsers($date);
                    }
                }
            });
        } catch (PDOException $exception) {
            throw $this->safeDatabaseException($exception);
        }

        $this->log($context, 'holiday.updated', sprintf('Hari libur #%d (%s) diperbarui.', $id, $data['name']));
    }

    /** @param array<string,?string> $context */
    public function setActive(int $id, string $date, string $name, bool $active, string $today, array $context = []): void
    {
        $this->transaction(function () use ($id, $date, $active, $today): void {
            $this->holidays->setActive($id, $active);

            if ($date >= $today) {
                $this->calendars->deleteDateForAllUsers($date);
            }
        });

        $this->log(
            $context,
            $active ? 'holiday.activated' : 'holiday.deactivated',
            sprintf('Hari libur #%d (%s) %s.', $id, $name, $active ? 'diaktifkan' : 'dinonaktifkan')
        );
    }

    private function ensureDateAvailable(string $date, ?int $exceptId = null): void
    {
        if ($this->holidays->dateExists($date, $exceptId)) {
            throw new HolidayException('Sudah terdapat hari libur pada tanggal tersebut.');
        }
    }

    private function safeDatabaseException(PDOException $exception): HolidayException
    {
        if ((string) $exception->getCode() === '23000') {
            return new HolidayException('Sudah terdapat hari libur pada tanggal tersebut.');
        }

        throw $exception;
    }

    private function transaction(callable $operation): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $operation();
            $this->pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    /** @param array<string,?string> $context */
    private function log(array $context, string $action, string $description): void
    {
        try {
            (new ActivityLog($this->pdo))->create(
                \auth_id(),
                $action,
                $description,
                $context['ip_address'] ?? null,
                $context['user_agent'] ?? null
            );
        } catch (Throwable $exception) {
            error_log('Activity log hari libur gagal disimpan: ' . $exception->getMessage());
        }
    }
}
