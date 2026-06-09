<?php 

namespace App\Services\StripeSubscriptionAutomation;
use Laravel\Cashier\Cashier;
class StripeSubscriptionAutomation
{
    protected $stripe;
    public function __construct()
    {
        //$this->stripe = Cashier::stripe();
    }
}
