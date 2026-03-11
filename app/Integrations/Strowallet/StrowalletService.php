<?php

namespace App\Integrations\Strowallet;

use Illuminate\Support\Facades\Http;

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
        $response = Http::timeout(30)
            ->retry(3, 2000)
            ->post($this->baseUrl . '/api/kyc_bvn/', [
                'public_key' => $this->publicKey,
                'number' => $data['bvn'],
                'firstName' => strtoupper($data['first_name']),
                'lastName' => strtoupper($data['last_name']),
                'dateOfBirth' => $data['dob'],
                'phoneNumber' => $data['phone'],
                'mode' => $this->mode
            ]);

        return $response->json();
    }

    /**
     * Fetch BVN Details
     */
    public function getBvnDetails(string $bvn)
    {
        $response = Http::timeout(30)
            ->retry(3, 2000)
            ->get($this->baseUrl . '/api/kyc_getbvn/', [
                'public_key' => $this->publicKey,
                'number' => $bvn
            ]);

        return $response->json();
    }

    public function verifyNin(array $data)
    {

        $response = Http::timeout(30)
            ->retry(3, 2000)
            ->post(
                $this->baseUrl.'/api/kyc_verinin/',
                [
                    'public_key' => $this->publicKey,
                    'number_nin' => $data['nin'],
                    'surname' => strtoupper($data['surname']),
                    'firstname' => strtoupper($data['firstname']),
                    'birthdate' => $data['dob'],
                    'telephoneno' => $data['phone'],
                ]
            );

        return $response->json();

    }

    public function getNinDetails($nin)
    {
        $response = Http::timeout(30)
            ->retry(3, 2000)
            ->get(
                $this->baseUrl.'/api/kyc_getnin/',
                [
                    'public_key' => $this->publicKey,
                    'number_nin' => $nin
                ]
            );

        return $response->json();
}
}
