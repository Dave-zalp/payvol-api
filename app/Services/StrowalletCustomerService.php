<?php

namespace App\Services;

use App\Integrations\Strowallet\StrowalletService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\StrowalletCustomer;

class StrowalletCustomerService
{
    public function __construct(
        protected StrowalletService $integration
    ) {}

    public function createCustomer(User $user)
    {
        return DB::transaction(function () use ($user) {

            // 🔒 Lock the user row to prevent race conditions
            $user = User::where('id', $user->id)->lockForUpdate()->first();

            // ✅ 1. Check if already linked on user
            if ($user->strowallet_customer_id) {
                return $user->strowallet_customer_id;
            }

            // ✅ 2. Check if already exists in our table
            $existing = StrowalletCustomer::where('user_id', $user->id)->first();
            if ($existing) {
                return $existing->customer_id;
            }

            // ✅ 3. Ensure KYC exists
            $kyc = $user->kyc;
            if (!$kyc) {
                throw new \Exception('User KYC not found');
            }

            // ✅ 4. Build payload
            $payload = [
                'public_key'   => config('services.strowallet.public_key'),
                'houseNumber'  => $user->house_number ?? '1',
                'firstName'    => $user->first_name,
                'lastName'     => $user->surname,
                'idNumber'     => $kyc->nin_number,
                'customerEmail'=> $user->email,
                'phoneNumber'  => '234' . ltrim($user->phone, '0'),
                'dateOfBirth'  => optional($kyc->date_of_birth)?->format('m/d/Y'),
                'idImage'      => $kyc->nin_front_url,
                'userPhoto'    => $kyc->selfie_image_url,
                'line1'        => $kyc->home_address,
                'state'        => $kyc->state,
                'zipCode'      => $kyc->zip_code ?? '100001',
                'city'         => $kyc->city,
                'country'      => $user->country,
                'idType'       => 'NIN',

            ];

            // ✅ 5. Call API OUTSIDE risky duplication zone (still inside lock)
            $response = $this->integration->createCustomer($payload);

            $success     = $response['success'] ?? false;
            $customerId  = $response['response']['customerId'] ?? null;

            if (!$success || !$customerId) {
                Log::warning('Strowallet Customer Creation Failed', [
                    'user_id'  => $user->id,
                    'response' => $response
                ]);

                throw new \Exception(
                    is_string($response['message'] ?? null)
                        ? $response['message']
                        : json_encode($response['message'] ?? 'Unknown error')
                );
            }

            $data = $response['response'];

            // ✅ 6. Save customer
            StrowalletCustomer::create([
                'user_id'        => $user->id,
                'customer_id'    => $data['customerId'],
                'customer_email' => $data['customerEmail'] ?? null,
                'first_name'     => $data['firstName'] ?? null,
                'last_name'      => $data['lastName'] ?? null,
                'phone_number'   => $data['phoneNumber'] ?? null,
                'city'           => $data['city'] ?? null,
                'state'          => $data['state'] ?? null,
                'country'        => $data['country'] ?? null,
                'line1'          => $data['line1'] ?? null,
                'zip_code'       => $data['zipCode'] ?? null,
                'house_number'   => $data['houseNumber'] ?? null,
                'id_number'      => $data['idNumber'] ?? null,
                'id_type'        => $data['idType'] ?? null,
                'id_image'       => $data['idImage'] ?? null,
                'user_photo'     => $data['userPhoto'] ?? null,
                'date_of_birth'  => $data['dateOfBirth'] ?? null,
                'response'       => $response,
            ]);

            $kyc->update([
                'status' => 'verified',
            ]);

            // TODO send mail that KYC has been verified

            return $customerId;
        });
    }
}
