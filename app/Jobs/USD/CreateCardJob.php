<?php

namespace App\Jobs\USD;

use App\Models\User;
use App\Services\StrowalletCardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateCardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $walletId;
    protected $prefundAmount;
    protected $deduction;

    public function __construct(User $user, string $walletId, float $prefundAmount, float $deduction)
    {
        $this->user          = $user;
        $this->walletId      = $walletId;
        $this->prefundAmount = $prefundAmount;
        $this->deduction     = $deduction;
    }

    public function handle(StrowalletCardService $service)
    {
        $service->createCard($this->user, $this->walletId, $this->prefundAmount, $this->deduction);
    }
}
