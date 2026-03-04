<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/Otp.php

class Otp extends Model
{
    protected $fillable = [
        'user_id',
        'identifier',
        'code',
        'type',
        'attempts',
        'max_attempts',
        'expires_at',
        'is_used'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isExpired()
    {
        return now()->gt($this->expires_at);
    }

    public function isMaxAttemptsReached()
    {
        return $this->attempts >= $this->max_attempts;
    }
}
