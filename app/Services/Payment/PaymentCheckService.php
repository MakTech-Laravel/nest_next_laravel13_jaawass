<?php 

namespace App\Services\Payment;

use App\Enums\Api\V1\Payment\RegisterPaymentManager;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentCheckService
{

    protected $paymentClass;
 
    public function checkPayment(string $paymentMethod, array $paymentData)
    {

  
       $paymentClass = $this->supportPaymentMethod();

       $paymentMethod = strtolower($paymentMethod);

       if(!array_key_exists($paymentMethod, $paymentClass)) {
          
         sendResponse(
            status: false,
            message: __('commmon.payment_method_not_supported'),
            data: null
         );
       }
       
       $this->paymentClass = new $paymentClass[$paymentMethod]($paymentData);

         
     $payment = $this->paymentClass->checkPayment();
    
        if(!$payment) {
            return sendResponse(
                status: false,
                message: __('commmon.payment_not_found'),
                data: null
            );
        }


        $this->paymentClass->handleFraudCheck($payment);

    }


    public function supportPaymentMethod(): array|null
    {
      return [
        RegisterPaymentManager::STRIPE->value => StripePayment::class,  
        RegisterPaymentManager::PAYPAL->value => PaypalPayment::class,
      ];
    }
}
