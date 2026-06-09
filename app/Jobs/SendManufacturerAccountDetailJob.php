<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendManufacturerAccountDetailJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $manufacturer)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO: Implement email sending logic
        // Example: Mail::to($this->manufacturer->email)->send(new ManufacturerAccountDetailsMail($this->manufacturer));
        Log::info('Sending manufacturer account detail email to: ' . $this->manufacturer->email);
    }
}
