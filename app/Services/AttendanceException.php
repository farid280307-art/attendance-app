<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class AttendanceException extends RuntimeException
{
    /** @param array<string, string> $errors */
    public function __construct(
        string $message,
        private int $httpStatus = 422,
        private bool $resetWorkflow = false,
        private array $errors = []
    ) {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function shouldResetWorkflow(): bool
    {
        return $this->resetWorkflow;
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
