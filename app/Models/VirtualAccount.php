<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VirtualAccount extends Model
{
    use HasUuids;
    protected $fillable = [
        'user_id', 'account_name', 'account_number',
        'bank_name', 'provider_reference', 'balance',
        'currency', 'provider_name', 'response'
    ];

    protected $casts = [
        'response' => 'array',
        'balance'  => 'decimal:2',
   ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
