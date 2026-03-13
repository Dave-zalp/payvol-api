<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendOtpJob;
use App\Models\RegistrationSession;
use App\Models\User;
use App\Services\Otpservice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{

    public function login(Request $request, Otpservice $otpService)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'nullable|string',
            'device_name' => 'required|string'
        ]);

        $email = $request->email;

        // /*
        // |--------------------------------------------------------------------------
        // | Check if user is mid-registration
        // |--------------------------------------------------------------------------
        // */

        $registrationSession = RegistrationSession::where('email', $email)->first();

        if ($registrationSession) {

            return response()->json([
                'message' => 'Incomplete Registration',
                // 'next_step' => $registrationSession->current_step
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Normal login
        |--------------------------------------------------------------------------
        */

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'error' => 'Account not found'
            ], 404);
        }

        if (!$request->password || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Invalid credentials'
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Generate login OTP
        |--------------------------------------------------------------------------
        */

        $otp = $otpService->generate(
            identifier: $user->email,
            type: 'login',
            userId: $user->id
        );

        // Dispatch async job
        SendOtpJob::dispatch($user->email, $otp, 'Login');

        return response()->json([
            'message' => 'OTP sent to email'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Verify Login OTP
    |--------------------------------------------------------------------------
    */

    public function verifyOtp(Request $request, Otpservice $otpService)
    {

        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'device_name' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        try {

            $otpService->verify(
                $request->email,
                'login',
                $request->otp
            );

            $token = $user->createToken(
                name: $request->device_name
            )->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'token' => $token
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 400);

        }
    }
}
