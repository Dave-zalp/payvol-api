<?php

namespace App\Jobs;

use App\Integrations\Strowallet\StrowalletService;
use App\Models\KycVerification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyNinJob implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $kycId;

    public function __construct($kycId)
    {
        $this->kycId = $kycId;
    }

    public function handle(StrowalletService $strowallet)
    {

        $kyc = KycVerification::with('user')->find($this->kycId);

        if (!$kyc) {
            return;
        }

        $user = $kyc->user;

        $response = $strowallet->verifyNin([
            'nin' => $kyc->nin_number,
            'firstname' => $user->first_name,
            'surname' => $user->surname,
            'dob' => '20-10-2003',
            // 'dob' => $user->dob,
            'phone' => $user->phone
        ]);

        if ($response['status'] === true) {

            $kyc->update([
                'nin_status' => 'verified',
            ]);

        } else {

            $kyc->update([
                'nin_status' => 'rejected',
            ]);

            throw new Exception('NIN verification failed');

        }

    }
}
