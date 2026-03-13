<?php

namespace App\Services;

use App\Models\Otp;
use Illuminate\Support\Facades\Hash;
use Exception;


class Otpservice
{
    public function generate($identifier, $type, $userId = null)
    {
        $otp = random_int(100000, 999999);

        // Delete previous unused OTPs for this type
        Otp::where('identifier', $identifier)
            ->where('type', $type)
            ->where('is_used', false)
            ->delete();

        $record = Otp::create([
            'user_id' => $userId,
            'identifier' => $identifier,
            'code' => Hash::make($otp),
            'type' => $type,
            'expires_at' => now()->addMinutes(30),
        ]);

        return $otp;
    }

    public function verify($identifier, $type, $inputOtp)
    {
        $otp = Otp::where('identifier', $identifier)
            ->where('type', $type)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (!$otp) {
            throw new Exception("OTP not found.");
        }

        if ($otp->isExpired()) {
            throw new Exception("OTP expired.");
        }

        if ($otp->isMaxAttemptsReached()) {
            throw new Exception("Maximum attempts reached.");
        }

        if (!Hash::check($inputOtp, $otp->code)) {
            $otp->increment('attempts');
            throw new Exception("Invalid OTP.");
        }

        $otp->update([
            'is_used' => true
        ]);

        return true;
    }
}
