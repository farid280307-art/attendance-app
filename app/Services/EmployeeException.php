<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class EmployeeException extends RuntimeException
{
    /** @param array<string, string> $errors */
    public function __construct(string $message, private array $errors = [])
    {
        parent::__construct($message);
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
