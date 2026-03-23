<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RegistrationSession extends Model
{
    use HasUuids;
    protected $fillable = [
        'email',
        'step_data',
        'password',
        'otp',
        'otp_expires_at',
        'pin',
        'current_step'
    ];

    protected $casts = [
        'step_data' => 'array',
        'otp_expires_at' => 'datetime',
    ];
}
