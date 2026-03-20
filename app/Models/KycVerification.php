<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycVerification extends Model
{

    protected $fillable = [

        'user_id',
        'bvn_number',
        'nin_number',
        'selfie_image',
        'nin_front',
        'nin_back',
        'nin_info',
        'bvn_info',
        'bvn_status',
        'nin_status',
        'status',
        'verified_at',
        'rejection_reason',
        'date_of_birth',
        'home_address',
        'state',
        'city',
        'zip_code',
    ];

    protected $casts = [
        'nin_info' => 'array',
        'bvn_info' => 'array',
        'verified_at' => 'datetime',
        'date_of_birth' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
