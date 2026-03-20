<?php

namespace App\Services;

use App\Integrations\Strowallet\StrowalletService;
use App\Models\StrowalletCustomer;
use App\Models\VirtualCard;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StrowalletCardService
{
    public function __construct(
        protected StrowalletService $integration
    ) {}

    public function createCard(User $user)
    {
        // Ensure user has strowallet customer
        $customer = $user->strowalletCustomer;

        if (!$customer) {
            throw new \Exception('User does not have a Strowallet customer profile.');
        }

        // Prevent duplicate pending cards
        if (VirtualCard::where('user_id', $user->id)
            ->where('card_status', 'pending')
            ->exists()) {
            throw new \Exception('Card creation already in progress.');
        }

        $payload = [
            'name_on_card'  => $customer->first_name . ' ' . $customer->last_name,
            'card_type'     => 'visa',
            'public_key'    => config('services.strowallet.public_key'),
            'amount'        => (string) 0,
            'customerEmail' => $customer->customer_email,
            'mode'          => 'sandbox', // remove in production
        ];

        $response = $this->integration->createCard($payload);

        $success = $response['success'] ?? false;
        $data    = $response['response'] ?? null;

        if (!$success || !$data || empty($data['card_id'])) {
            Log::warning('Strowallet Card Creation Failed', [
                'user_id'  => $user->id,
                'response' => $response
            ]);

            throw new \Exception(
                is_string($response['message'])
                    ? $response['message']
                    : json_encode($response['message'])
            );
        }

        DB::transaction(function () use ($user, $data, $response) {

            VirtualCard::create([
                'user_id'          => $user->id,
                'card_id'          => $data['card_id'],
                'card_user_id'     => $data['card_user_id'] ?? null,
                'reference'        => $data['reference'] ?? null,
                'name_on_card'     => $data['name_on_card'],
                'card_brand'       => $data['card_brand'] ?? null,
                'card_type'        => $data['card_type'] ?? null,
                'card_status'      => $data['card_status'] ?? 'pending',
                'customer_id'      => $data['customer_id'] ?? null,
                'card_created_at'  => $data['card_created_date'] ?? null,
                'response'         => $response,
            ]);

        });

        return $data;
    }
}
