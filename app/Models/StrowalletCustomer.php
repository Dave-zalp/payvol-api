<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StrowalletCustomer extends Model
{
    protected $fillable = [
        'user_id',
        'customer_id',
        'customer_email',
        'first_name',
        'last_name',
        'phone_number',
        'city',
        'state',
        'country',
        'line1',
        'zip_code',
        'house_number',
        'id_number',
        'id_type',
        'id_image',
        'user_photo',
        'date_of_birth',
        'response',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

