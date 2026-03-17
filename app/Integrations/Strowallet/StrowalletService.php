<?php

namespace App\Integrations\Strowallet;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StrowalletService
{
    protected $baseUrl;
    protected $publicKey;
    protected $mode;
    protected $webhook_url;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.strowallet.url'), '/');
        $this->publicKey = config('services.strowallet.public_key');
        $this->mode = config('services.strowallet.mode', 'sandbox');
        $this->webhook_url = config('services.strowallet.webhook_url');
    }

    /**
     * Verify BVN
     */
    public function verifyBvn(array $data)
    {
        $endpoint = $this->baseUrl . '/kyc_bvn/';

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
                ->withQueryParameters($payload)
                ->post($endpoint);

            Log::info('Strowallet BVN Verification Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->json();

        } catch (\Throwable $e) {

            Log::error('Strowallet BVN Verification Failed', [
                'message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Fetch BVN Details
     */
    public function getBvnDetails(string $bvn)
    {
        $endpoint = $this->baseUrl . '/kyc_getbvn/';

        $payload = [
            'public_key' => $this->publicKey,
            'number' => $bvn
        ];

        Log::info('Strowallet Get BVN Details Request', [
            'endpoint' => $endpoint,
            'payload' => $payload
        ]);

        try {

            $response = Http::timeout(30)
                ->retry(3, 2000)
                ->withQueryParameters($payload)
                ->get($endpoint);

            Log::info('Strowallet Get BVN Details Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->json();

        } catch (\Throwable $e) {

            Log::error('Strowallet Get BVN Details Failed', [
                'message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Verify NIN
     */
    public function verifyNin(array $data)
    {
        $endpoint = $this->baseUrl . '/kyc_verinin/';

        $payload = [
            'public_key' => $this->publicKey,
            'number_nin' => $data['nin'],
            'surname' => strtoupper($data['surname']),
            'firstname' => strtoupper($data['firstname']),
            'birthdate' => $data['dob'],
            'telephoneno' => $data['phone']
        ];

        Log::info('Strowallet NIN Verification Request', [
            'endpoint' => $endpoint,
            'payload' => $payload
        ]);

        try {

            $response = Http::timeout(30)
                ->retry(3, 2000)
                ->withQueryParameters($payload)
                ->post($endpoint);

            Log::info('Strowallet NIN Verification Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->json();

        } catch (\Throwable $e) {

            Log::error('Strowallet NIN Verification Failed', [
                'message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get NIN Details
     */
    public function getNinDetails(string $nin)
    {
        $endpoint = $this->baseUrl . '/kyc_getnin/';

        $payload = [
            'public_key' => $this->publicKey,
            'number_nin' => $nin
        ];

        Log::info('Strowallet Get NIN Details Request', [
            'endpoint' => $endpoint,
            'payload' => $payload
        ]);

        try {

            $response = Http::timeout(30)
                ->retry(3, 2000)
                ->withQueryParameters($payload)
                ->get($endpoint);

            Log::info('Strowallet Get NIN Details Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->json();

        } catch (\Throwable $e) {

            Log::error('Strowallet Get NIN Details Failed', [
                'message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Create Virtual Bank Account
     */
    public function createVirtualAccount(array $data)
    {
        $endpoint = $this->baseUrl . '/virtual-bank/new-customer/';

        $payload = [
            'public_key'   => $this->publicKey,
            'account_name' => $data['account_name'],
            'email' => $data['email'],
            'phone'        => $data['phone'],
            'webhook_url'       => $this->webhook_url ?? null,
            'mode'         => $this->mode,
        ];

        // Remove null values from payload
        $payload = array_filter($payload, fn($value) => !is_null($value));

        Log::info('Strowallet Create Virtual Account Request', [
            'endpoint' => $endpoint,
            'payload'  => $payload
        ]);

        try {
            $response = Http::timeout(30)
                ->retry(3, 2000)
                ->post($endpoint, $payload);

            Log::info('Strowallet Create Virtual Account Response', [
                'status' => $response->status(),
                'body'   => $response->body()
            ]);

            return $response->json();

        } catch (\Throwable $e) {

            Log::error('Strowallet Create Virtual Account Failed', [
                'message' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
