<?php 

namespace App\Services\Payment;

use Illuminate\Http\Response;

abstract class Payment
{
    protected $paymentData;
    
    public function __construct($paymentData)
    {
        $this->paymentData = $paymentData;
    }
    
    abstract public function checkPayment(): array;

    abstract public function handleFraudCheck($payment);

}

