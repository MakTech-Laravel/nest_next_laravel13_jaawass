<?php

namespace App\Services\Account;

final readonly class AccountRestoreResult
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    public function __construct(
        public bool $success,
        public string $message,
        public ?array $data = null,
        public int $statusCode = 200,
    ) {}
}
