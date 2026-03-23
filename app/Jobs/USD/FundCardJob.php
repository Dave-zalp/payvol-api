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
    protected $id;
    protected $amount;

    public function __construct(User $user, string $id, float $amount)
    {
        $this->user   = $user;
        $this->cardId = $id;
        $this->amount = $amount;
    }

    public function handle(StrowalletCardService $service)
    {
        $service->fundCard($this->user, $this->cardId, $this->amount);
    }
}
