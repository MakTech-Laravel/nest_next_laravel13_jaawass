<?php 

namespace App\Services\Payment;

use Stripe\PaymentIntent;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Response as HttpStatus;
class StripePayment extends Payment
{
    public function checkPayment(): array
    {

           Stripe::setApiKey(config('services.stripe.secret'));

           $paymentIntent = PaymentIntent::retrieve($this->paymentData['payment_id']);
           
           return $paymentIntent->toArray();
    }


    public function handleFraudCheck($payment)
    {
       if($payment['status'] === 'succeeded' && ($payment['amount']/100) !== $this->paymentData['paid_amount']) {
          
          return sendResponse(
                status: false,
                message: __('subscription.fraudulent_payment'),
                data: null,
                statusCode: HttpStatus::HTTP_BAD_REQUEST
            );
       }
       return true;
    }

}
