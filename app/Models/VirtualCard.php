<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VirtualCard extends Model
{
    use HasUuids;
    protected $fillable = [
        'user_id',
        'card_id',
        'card_user_id',
        'reference',
        'name_on_card',
        'card_brand',
        'card_type',
        'card_status',
        'customer_id',
        'card_created_at',
        'balance',
        'response',
    ];

    protected $casts = [
        'response' => 'array',
        'card_created_at' => 'date',
        'balance' => 'decimal:2',
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

    public function cardTransactions()
    {
        return $this->hasMany(CardTransaction::class);
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactable');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers (Very Useful)
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->card_status === 'active';
    }

    public function isPending(): bool
    {
        return $this->card_status === 'pending';
    }

    public function isFrozen(): bool
    {
        return $this->card_status === 'frozen';
    }
}
