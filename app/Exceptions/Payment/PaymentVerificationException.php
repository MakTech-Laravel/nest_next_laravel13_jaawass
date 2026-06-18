<?php

namespace App\Exceptions\Payment;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class PaymentVerificationException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode = Response::HTTP_BAD_REQUEST,
    ) {
        parent::__construct($message);
    }
}
