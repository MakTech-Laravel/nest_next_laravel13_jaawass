<?php

namespace App\Exceptions\Auth;

use Exception;

class EmailVerificationException extends Exception
{
    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>  $replace
     */
    public function __construct(
        public readonly string $messageKey,
        public readonly int $httpStatus,
        public readonly ?array $data = null,
        array $replace = [],
    ) {
        parent::__construct(__($messageKey, $replace));
    }
}
