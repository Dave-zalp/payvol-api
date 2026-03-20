<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\StrowalletCustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateStrowalletCustomerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 15;

    public function __construct(public User $user) {}

    public function handle(StrowalletCustomerService $service): void
    {
        $service->createCustomer($this->user);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('CreateStrowalletCustomerJob Failed', [
            'user_id' => $this->user->id,
            'message' => $e->getMessage(),
        ]);
    }
}
