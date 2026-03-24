<?php

namespace App\Jobs\USD;

use App\Integrations\Strowallet\StrowalletService;
use App\Models\VirtualCard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchCardDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;
    public int $backoff = 30; // seconds between retries

    protected string $virtualCardId;

    public function __construct(string $virtualCardId)
    {
        $this->virtualCardId = $virtualCardId;
    }

    public function handle(StrowalletService $integration): void
    {
        $card = VirtualCard::find($this->virtualCardId);

        if (!$card) {
            Log::warning('FetchCardDetailsJob: card not found', ['id' => $this->virtualCardId]);
            return;
        }

        $response = $integration->fetchCardDetail($card->card_id);
        $success  = $response['success'] ?? false;
        $detail   = $response['response']['card_detail'] ?? null;

        if (!$success || !$detail) {
            Log::warning('FetchCardDetailsJob: fetch failed, will retry', [
                'virtual_card_id' => $this->virtualCardId,
                'response'        => $response,
            ]);

            // Release back to queue to retry
            $this->release($this->backoff);
            return;
        }

        $card->update([
            'card_status'    => $detail['card_status'] ?? $card->card_status,
            'card_number'    => $detail['card_number'] ?? null,
            'last4'          => $detail['last4'] ?? null,
            'cvv'            => $detail['cvv'] ?? null,
            'expiry'         => $detail['expiry'] ?? null,
            'balance'        => $detail['balance'] ?? $card->balance,
            'customer_email' => $detail['customer_email'] ?? null,
            'billing_country'  => $detail['billing_country'] ?? null,
            'billing_city'     => $detail['billing_city'] ?? null,
            'billing_street'   => $detail['billing_street'] ?? null,
            'billing_zip_code' => $detail['billing_zip_code'] ?? null,
            'card_details'   => $response,
        ]);

        Log::info('FetchCardDetailsJob: card updated', [
            'virtual_card_id' => $this->virtualCardId,
            'card_status'     => $card->card_status,
        ]);
    }
}
