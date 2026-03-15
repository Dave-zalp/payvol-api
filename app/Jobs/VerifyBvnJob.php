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

        try {

            $response = $strowallet->verifyBvn([
                'bvn' => $kyc->bvn_number,
                'first_name' => $user->first_name,
                'last_name' => $user->surname,
                'dob' => '20-10-2003',
                // 'dob' => $user->dob,
                'phone' => $user->phone
            ]);

            if (($response['status'] ?? false) === true) {

                $kyc->update([
                    'bvn_status' => 'verified',
                ]);

            } else {

                $kyc->update([
                    'bvn_status' => 'rejected',
                ]);

            }

        } catch (\Throwable $e) {

            // API error (400, 500 etc)

            $kyc->update([
                'bvn_status' => 'rejected',
            ]);

            \Log::error('BVN verification job failed', [
                'kyc_id' => $kyc->id,
                'error' => $e->getMessage()
            ]);

        }
    }
}
