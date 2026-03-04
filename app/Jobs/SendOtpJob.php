<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOtpJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
   public function __construct(
        public $identifier,
        public $otp,
        public $type
    ) {}

    public function handle()
    {
        // You can switch based on type
        Mail::to($this->identifier)
            ->send(new GenericOtpMail($this->otp, $this->type));
    }

}
