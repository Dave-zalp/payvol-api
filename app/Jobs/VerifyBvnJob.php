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

class VerifyBvnJob implements ShouldQueue
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

        $response = $strowallet->verifyBvn([

            'bvn' => $kyc->bvn_number,
            'first_name' => $user->first_name,
            'last_name' => $user->surname,
            'dob' => $user->dob,
            'phone' => $user->phone

        ]);

        if ($response['status'] === true) {

            $kyc->update(attributes: [
                'bvn_status' => 'verified',
            ]);

        } else {

            $kyc->update([
                'bvn_status' => 'rejected',
            ]);

            throw new Exception('BVN verification failed');

        }

    }
}
