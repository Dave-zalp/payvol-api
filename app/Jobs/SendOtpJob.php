<?php

namespace App\Jobs;

use App\Mail\LoginOtpMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $identifier;
    public $otp;
    public $type;

    /**
     * Create a new job instance.
     */
    public function __construct($identifier, $otp, $type = 'login')
    {
        $this->identifier = $identifier;
        $this->otp = $otp;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Example: you can switch based on type if needed
        Mail::to($this->identifier)
            ->send(new LoginOtpMail($this->otp));
    }
}
