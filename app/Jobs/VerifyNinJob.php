<?php

namespace App\Jobs;

use App\Integrations\Strowallet\StrowalletService;
use App\Models\KycVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

        // Prevent duplicate API billing
        if ($kyc->nin_status === 'verified') {
            return;
        }

        $user = $kyc->user;

        try {

            $response = $strowallet->verifyNin([
                'nin' => $kyc->nin_number,
                'firstname' => $user->first_name,
                'surname' => $user->surname,
                'dob' => '20-10-2003', // ideally $user->dob
                'phone' => $user->phone
            ]);

            Log::info('NIN Verification API Response', [
                'kyc_id' => $kyc->id,
                'response' => $response
            ]);

            if (($response['status'] ?? false) === true) {

                $kyc->update([
                    'nin_status' => 'verified',
                ]);

            } else {

                $kyc->update([
                    'nin_status' => 'rejected',
                ]);

                Log::warning('NIN verification rejected', [
                    'kyc_id' => $kyc->id,
                    'message' => $response['message'] ?? null
                ]);

            }

        } catch (\Throwable $e) {

            // API error (400, 500, network failure etc)

            $kyc->update([
                'nin_status' => 'rejected',
            ]);

            Log::error('NIN verification job failed', [
                'kyc_id' => $kyc->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
