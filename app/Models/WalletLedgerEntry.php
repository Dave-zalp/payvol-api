<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WalletLedgerEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'wallet_id',
        'user_id',
        'transaction_id',
        'type',
        'amount',
        'currency',
        'description',
        'running_balance',
        'reference',
        'metadata',
    ];

    protected $casts = [
        'amount'          => 'decimal:8',
        'running_balance' => 'decimal:8',
        'metadata'        => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Reference generation
    |--------------------------------------------------------------------------
    */

    public static function generateReference(): string
    {
        do {
            $ref = 'LDG-' . strtoupper(Str::random(12));
        } while (self::where('reference', $ref)->exists());

        return $ref;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
        return $this->type === 'debit';
    }
}
