<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class LeaveException extends RuntimeException
{
    /** @param array<string, string> $errors */
    public function __construct(
        string $message,
        private int $httpStatus = 422,
        private array $errors = []
    ) {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
