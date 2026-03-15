<?php

namespace App\Integrations\Strowallet;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StrowalletService
{
    protected $baseUrl;
    protected $publicKey;
    protected $mode;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.strowallet.url'), '/');
        $this->publicKey = config('services.strowallet.public_key');
        $this->mode = config('services.strowallet.mode', 'sandbox');
    }

    /**
     * Verify BVN
     */
    public function verifyBvn(array $data)
    {
        $endpoint = $this->baseUrl . '/api/kyc_bvn/';

        $payload = [
            'public_key' => $this->publicKey,
            'number' => $data['bvn'],
            'firstName' => strtoupper($data['first_name']),
            'lastName' => strtoupper($data['last_name']),
            'dateOfBirth' => $data['dob'],
            'phoneNumber' => $data['phone'],
            'mode' => $this->mode
        ];

        Log::info('Strowallet BVN Verification Request', [
            'endpoint' => $endpoint,
            'payload' => $payload
        ]);

        try {
            $response = Http::timeout(30)
                ->retry(3, 2000)
                ->post($endpoint, $payload);

            Log::info('Strowallet BVN Verification Response', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return $response->json();

        } catch (\Exception $e) {

            Log::error('Strowallet BVN Verification Failed', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Fetch BVN Details
     */
    public function getBvnDetails(string $bvn)
    {
        $endpoint = $this->baseUrl . '/api/kyc_getbvn/';

        Log::info('Strowallet Get BVN Details Request', [
            'endpoint' => $endpoint,
            'bvn' => $bvn
        ]);

        try {

            $response = Http::timeout(30)
                ->retry(3, 2000)
                ->get($endpoint, [
                    'public_key' => $this->publicKey,
                    'number' => $bvn
                ]);

            Log::info('Strowallet Get BVN Details Response', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return $response->json();

        } catch (\Exception $e) {

            Log::error('Strowallet Get BVN Details Failed', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Verify NIN
     */
    public function verifyNin(array $data)
    {
        $endpoint = $this->baseUrl.'/api/kyc_verinin/';

        $payload = [
            'public_key' => $this->publicKey,
            'number_nin' => $data['nin'],
            'surname' => strtoupper($data['surname']),
            'firstname' => strtoupper($data['firstname']),
            'birthdate' => $data['dob'],
            'telephoneno' => $data['phone'],
        ];

        Log::info('Strowallet NIN Verification Request', [
            'endpoint' => $endpoint,
            'payload' => $payload
        ]);

        try {

            $response = Http::timeout(30)
                ->retry(3, 2000)
                ->post($endpoint, $payload);

            Log::info('Strowallet NIN Verification Response', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return $response->json();

        } catch (\Exception $e) {

            Log::error('Strowallet NIN Verification Failed', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get NIN Details
     */
    public function getNinDetails($nin)
    {
        $endpoint = $this->baseUrl.'/api/kyc_getnin/';

        Log::info('Strowallet Get NIN Details Request', [
            'endpoint' => $endpoint,
            'nin' => $nin
        ]);

        try {

            $response = Http::timeout(30)
                ->retry(3, 2000)
                ->get($endpoint, [
                    'public_key' => $this->publicKey,
                    'number_nin' => $nin
                ]);

            Log::info('Strowallet Get NIN Details Response', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return $response->json();

        } catch (\Exception $e) {

            Log::error('Strowallet Get NIN Details Failed', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
