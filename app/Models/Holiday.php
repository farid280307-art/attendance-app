<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Holiday
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function getAll(): array
    {
        return $this->pdo->query(
            'SELECT `id`, `holiday_date`, `name`, `is_active`, `created_at`, `updated_at`
             FROM `holidays`
             ORDER BY `holiday_date` DESC, `id` DESC'
        )->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function getActiveForRange(string $startDate, string $endDate): array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `holiday_date`, `name`
             FROM `holidays`
             WHERE `is_active` = :is_active
               AND `holiday_date` BETWEEN :start_date AND :end_date
             ORDER BY `holiday_date` ASC, `id` ASC'
        );
        $statement->execute([
            'is_active' => 1,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `holiday_date`, `name`, `is_active`, `created_at`, `updated_at`
             FROM `holidays`
             WHERE `id` = :id
             LIMIT 1'
        );
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->execute();
        $holiday = $statement->fetch();

        return is_array($holiday) ? $holiday : null;
    }

    /** @return array<string, mixed>|null */
    public function findActiveByDate(string $date): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `holiday_date`, `name`
             FROM `holidays`
             WHERE `holiday_date` = :holiday_date AND `is_active` = :is_active
             LIMIT 1'
        );
        $statement->execute(['holiday_date' => $date, 'is_active' => 1]);
        $holiday = $statement->fetch();

        return is_array($holiday) ? $holiday : null;
    }

    public function dateExists(string $date, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM `holidays` WHERE `holiday_date` = :holiday_date';

        if ($exceptId !== null) {
            $sql .= ' AND `id` <> :except_id';
        }

        $sql .= ' LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->bindValue('holiday_date', $date);

        if ($exceptId !== null) {
            $statement->bindValue('except_id', $exceptId, PDO::PARAM_INT);
        }

        $statement->execute();

        return $statement->fetchColumn() !== false;
    }

    /** @param array{holiday_date:string,name:string,is_active:int} $data */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO `holidays` (`holiday_date`, `name`, `is_active`)
             VALUES (:holiday_date, :name, :is_active)'
        );
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array{holiday_date:string,name:string,is_active:int} $data */
    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE `holidays`
             SET `holiday_date` = :holiday_date,
                 `name` = :name,
                 `is_active` = :is_active
             WHERE `id` = :id'
        );
        $statement->execute($data + ['id' => $id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE `holidays` SET `is_active` = :is_active WHERE `id` = :id'
        );
        $statement->bindValue('is_active', $active ? 1 : 0, PDO::PARAM_INT);
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->execute();
    }
}
