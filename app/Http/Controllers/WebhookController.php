<?php

namespace App\Http\Controllers;

use App\Models\CardTransaction;
use App\Models\VirtualCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function virtualbank(Request $request)
    {
        Log::info('Virtual Bank Webhook Received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        return response()->json(['message' => 'OK']);
    }

    public function virtualcard(Request $request)
    {
        $payload = $request->all();

        Log::info('Virtual Card Webhook Received', [
            'headers' => $request->headers->all(),
            'payload' => $payload,
        ]);

        // Strowallet sends card_id at the top level or nested under data/response
        $cardId = $payload['card_id']
            ?? $payload['data']['card_id']
            ?? $payload['response']['card_id']
            ?? null;

        if (!$cardId) {
            Log::warning('Virtual Card Webhook: missing card_id', ['payload' => $payload]);
            return response()->json(['message' => 'OK']);
        }

        $card = VirtualCard::where('card_id', $cardId)->first();

        if (!$card) {
            Log::warning('Virtual Card Webhook: card not found', ['card_id' => $cardId]);
            return response()->json(['message' => 'OK']);
        }

        // --- Card status update (activation, freeze, termination) ---
        $newStatus = $payload['card_status']
            ?? $payload['data']['card_status']
            ?? $payload['status']
            ?? null;

        $validStatuses = ['active', 'frozen', 'terminated', 'pending'];

        if ($newStatus && in_array($newStatus, $validStatuses) && $card->card_status !== $newStatus) {
            $card->update(['card_status' => $newStatus]);

            Log::info('Virtual Card Webhook: status updated', [
                'card_id'    => $cardId,
                'old_status' => $card->card_status,
                'new_status' => $newStatus,
            ]);
        }

        // --- Card spending transaction ---
        $txnData = $payload['transaction']
            ?? $payload['data']
            ?? null;

        if ($txnData && !empty($txnData['id'])) {
            CardTransaction::updateOrCreate(
                ['provider_id' => $txnData['id']],
                [
                    'user_id'         => $card->user_id,
                    'virtual_card_id' => $card->id,
                    'card_id'         => $card->card_id,
                    'type'            => $txnData['type'] ?? 'debit',
                    'method'          => $txnData['method'] ?? 'purchase',
                    'narrative'       => $txnData['narrative'] ?? null,
                    'amount'          => ($txnData['centAmount'] ?? 0) / 100,
                    'cent_amount'     => $txnData['centAmount'] ?? 0,
                    'currency'        => $txnData['currency'] ?? 'usd',
                    'status'          => $txnData['status'] ?? 'success',
                    'reference'       => $txnData['reference'] ?? null,
                    'transacted_at'   => $txnData['createdAt'] ?? now(),
                    'metadata'        => $payload,
                ]
            );

            Log::info('Virtual Card Webhook: transaction recorded', [
                'card_id'     => $cardId,
                'provider_id' => $txnData['id'],
            ]);
        }

        return response()->json(['message' => 'OK']);
    }
}
