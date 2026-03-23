<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasUuids;
    protected $fillable = [
        'user_id', 'currency', 'balance',
        'ledger_balance', 'is_active'
    ];

    protected $casts = [
        'balance'        => 'decimal:2',
        'ledger_balance' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
