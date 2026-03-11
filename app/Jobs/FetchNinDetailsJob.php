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

class FetchNinDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $kycId;

    public function __construct($kycId)
    {
        $this->kycId = $kycId;
    }

    public function handle(StrowalletService $strowallet)
    {

        $kyc = KycVerification::find($this->kycId);

        if (!$kyc) {
            return;
        }

        // Call Strowallet API to fetch BVN details
        $response = $strowallet->getNinDetails($kyc->nin_number);

        // If API returned valid data
        if (isset($response['firstname'])) {

            $kyc->update([
                'nin_info' => $response,
                'status' => 'verified',
            ]);

        } else {

            $kyc->update([
                'status' => 'rejected',
                'rejection_reason' => 'Unable to fetch NIN details'
            ]);

            throw new Exception('Failed to get NIN details');

        }

    }
}
