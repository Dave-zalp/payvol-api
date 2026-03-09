<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class StrowalletService
{
    protected $client;
    protected $baseUrl;
    protected $publicKey;
    protected $webhookUrl;

    public function __construct()
    {
        $this->baseUrl = env('STROWALLET_BASE_URL', 'https://strowallet.com/api');
        $this->publicKey = env('STROWALLET_PUBLIC_KEY'); // put your public key in .env
        $this->webhookUrl = env('STROWALLET_WEBHOOK_URL'); // your webhook
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ]
        ]);
    }

    /**
     * Create a new virtual bank account for the user
     *
     * @param string $email
     * @param string $accountName
     * @param string $phone
     * @param string $mode // sandbox or live
     * @return array
     */
    public function createVirtualAccount(string $email, string $accountName, string $phone, string $mode = 'sandbox')
    {
        try {
            $response = $this->client->post('/virtual-bank/new-customer/', [
                'body' => json_encode([
                    'public_key' => $this->publicKey,
                    'email' => $email,
                    'account_name' => $accountName,
                    'phone' => $phone,
                    'webhook_url' => $this->webhookUrl,
                    'mode' => $mode,
                ])
            ]);

            $data = json_decode($response->getBody(), true);
            return $data;

        } catch (\Exception $e) {
            Log::error('Strowallet createVirtualAccount failed: '.$e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create virtual account'
            ];
        }
    }
}
