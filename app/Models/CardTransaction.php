<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CardTransaction extends Model
{
    use HasUuids;
    protected $fillable = [
        'user_id',
        'virtual_card_id',
        'transaction_id',
        'provider_id',
        'card_id',
        'type',
        'method',
        'narrative',
        'amount',
        'cent_amount',
        'currency',
        'status',
        'reference',
        'transacted_at',
        'metadata',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'cent_amount'   => 'integer',
        'transacted_at' => 'datetime',
        'metadata'      => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function virtualCard()
    {
        return $this->belongsTo(VirtualCard::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    public function isDebit(): bool
    {
        return in_array($this->type, ['debit', 'authorization_declined', 'authorization']);
    }
}
