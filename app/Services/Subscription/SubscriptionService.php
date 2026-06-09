<?php 

namespace App\Services\Subscription;

use App\Models\Subscription;

class SubscriptionService
{
    public function __construct(public Subscription $model)
    {
        
    }
    
    public function createSubscription($data)
    {
       return $this->model->create($data);
    }

    public function updateSubscription($id, $data)
    {
        // Remove payment-related fields from update data
        if (is_array($data)) {
            unset($data['payment_method'], $data['payment_id'], $data['paid_amount']); 
        }

        $this->model->where('id', $id)->update($data);

        return $this->model->where('id', $id)->first();
    }
    
    public function getSubscriptionByManufacturerId($manufacturer_id)
    {
        return $this->model->where('manufacturer_id', $manufacturer_id)->first();
    }

    public function cancelSubscriptionPlan(int $subscriptionId)
    {
        return $this->model->where('id', $subscriptionId)->update(['auto_renew' => false]);
    }

}