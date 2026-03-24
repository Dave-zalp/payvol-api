<?php

namespace App\Jobs\USD;

use App\Models\User;
use App\Services\StrowalletCardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FundCardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $cardId;
    protected $amount;
    protected $walletId;
    protected $deduction;

    public function __construct(User $user, string $cardId, float $amount, string $walletId, float $deduction)
    {
        $this->user      = $user;
        $this->cardId    = $cardId;
        $this->amount    = $amount;
        $this->walletId  = $walletId;
        $this->deduction = $deduction;
    }

    public function handle(StrowalletCardService $service)
    {
        $service->fundCard($this->user, $this->cardId, $this->amount, $this->walletId, $this->deduction);
    }
}
