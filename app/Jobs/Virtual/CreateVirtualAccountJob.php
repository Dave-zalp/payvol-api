<?php

namespace App\Jobs;

use App\Services\VirtualBankAccountService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateVirtualAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public User $user) {}

    public function handle(VirtualBankAccountService $service): void
    {
        $service->createVirtualAccount($this->user);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('CreateVirtualAccountJob Failed', [
            'user_id' => $this->user->id,
            'message' => $e->getMessage(),
        ]);
    }
}
