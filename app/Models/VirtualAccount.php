<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualAccount extends Model
{
    protected $fillable = [
        'user_id', 'account_name', 'account_number',
        'bank_name', 'provider_reference', 'balance',
        'currency', 'provider_name'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
