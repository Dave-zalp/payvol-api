<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasUuids;
    protected $fillable = [
        'user_id',
        'reference',
        'type',
        'channel',
        'amount',
        'fee',
        'currency',
        'balance_before',
        'balance_after',
        'status',
        'description',
        'transactable_type',
        'transactable_id',
        'metadata',
    ];

    protected $casts = [
        'amount'         => 'decimal:8',
        'fee'            => 'decimal:8',
        'balance_before' => 'decimal:8',
        'balance_after'  => 'decimal:8',
        'metadata'       => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Reference generation
    |--------------------------------------------------------------------------
    */

    public static function generateReference(): string
    {
        do {
            $ref = 'TXN-' . strtoupper(Str::random(12));
        } while (self::where('reference', $ref)->exists());

        return $ref;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactable()
    {
        return $this->morphTo();
    }

    public function cardTransactions()
    {
        return $this->hasMany(CardTransaction::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(WalletLedgerEntry::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markSuccess(): void
    {
        $this->update(['status' => 'success']);
    }

    public function markFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
