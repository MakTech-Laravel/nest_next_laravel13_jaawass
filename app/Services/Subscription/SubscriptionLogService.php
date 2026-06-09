<?php 

namespace App\Services\Subscription;

use App\Models\SubscriptionLog;

class SubscriptionLogService
{
    public function __construct(public SubscriptionLog $model)
    {
        
    }
    
    public function createSubscriptionLog($data)
    {
       return $this->model->create($data);
    }
}
