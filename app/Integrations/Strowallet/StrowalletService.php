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
        $this->naira_virtual_bank_webhook_url = config('services.strowallet.naira_virtual_bank_webhook_url');
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
            'webhook_url'       => $this->naira_virtual_bank_webhook_url ?? null,
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

    public function createCustomer(array $payload): array
    {
        $endpoint = $this->baseUrl . '/bitvcard/create-user/';

        Log::info('Strowallet Create Customer Request', [
            'endpoint' => $endpoint,
            'payload'  => $payload
        ]);

        $response = Http::timeout(30)->post($endpoint, $payload);

        $body = $response->json();

        Log::info('Strowallet Create Customer Response', [
            'status' => $response->status(),
            'body'   => $body
        ]);

        return is_array($body) ? $body : [
            'success' => false,
            'message' => 'Invalid response from provider'
        ];
    }

    public function createCard(array $payload): array
    {
        $endpoint = $this->baseUrl . '/bitvcard/create-card/';

        Log::info('Strowallet Create Card Request', [
            'endpoint' => $endpoint,
            'payload'  => $payload
        ]);

        $response = Http::timeout(30)->post($endpoint, $payload);

        $body = $response->json();

        Log::info('Strowallet Create Card Response', [
            'status' => $response->status(),
            'body'   => $body
        ]);

        return is_array($body) ? $body : [
            'success' => false,
            'message' => 'Invalid response from provider'
        ];
    }

    public function fetchCardDetail(string $cardId): array
    {
        $endpoint = $this->baseUrl . '/bitvcard/fetch-card-detail/';

        $payload = [
            'card_id'    => $cardId,
            'public_key' => $this->publicKey,
            'mode'       => $this->mode,
        ];

        Log::info('Strowallet Fetch Card Detail Request', [
            'endpoint' => $endpoint,
            'payload'  => $payload
        ]);

        $response = Http::timeout(30)->post($endpoint, $payload);

        $body = $response->json();

        Log::info('Strowallet Fetch Card Detail Response', [
            'status' => $response->status(),
            'body'   => $body
        ]);

        return is_array($body) ? $body : [
            'success' => false,
            'message' => 'Invalid response from provider'
        ];
    }

    public function updateCardStatus(string $cardId, string $action): array
    {
        $endpoint = $this->baseUrl . '/bitvcard/action/status/';

        $params = [
            'action'     => $action,
            'card_id'    => $cardId,
            'public_key' => $this->publicKey,
        ];

        Log::info('Strowallet Update Card Status Request', [
            'endpoint' => $endpoint,
            'params'   => $params,
        ]);

        $response = Http::timeout(30)->withQueryParameters($params)->post($endpoint);

        $body = $response->json();

        Log::info('Strowallet Update Card Status Response', [
            'status' => $response->status(),
            'body'   => $body,
        ]);

        return is_array($body) ? $body : [
            'success' => false,
            'message' => 'Invalid response from provider'
        ];
    }

    public function fetchCardTransactions(string $cardId): array
    {
        $endpoint = $this->baseUrl . '/bitvcard/card-transactions/';

        $payload = [
            'card_id'    => $cardId,
            'public_key' => $this->publicKey,
            'mode'       => $this->mode,
        ];

        Log::info('Strowallet Fetch Card Transactions Request', [
            'endpoint' => $endpoint,
            'payload'  => $payload
        ]);

        $response = Http::timeout(30)->post($endpoint, $payload);

        $body = $response->json();

        Log::info('Strowallet Fetch Card Transactions Response', [
            'status' => $response->status(),
            'body'   => $body
        ]);

        return is_array($body) ? $body : [
            'success' => false,
            'message' => 'Invalid response from provider'
        ];
    }

    public function fundCard(array $payload): array
    {
        $endpoint = $this->baseUrl . '/bitvcard/fund-card/';

        Log::info('Strowallet Fund Card Request', [
            'endpoint' => $endpoint,
            'payload'  => $payload
        ]);

        $response = Http::timeout(30)->post($endpoint, $payload);

        $body = $response->json();

        Log::info('Strowallet Fund Card Response', [
            'status' => $response->status(),
            'body'   => $body
        ]);

        return is_array($body) ? $body : [
            'success' => false,
            'message' => 'Invalid response from provider'
        ];
    }
}
