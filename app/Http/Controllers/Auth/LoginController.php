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
            'email'       => 'required|email',
            'password'    => 'nullable|string',
            'device_name' => 'required|string',
        ]);

        $email = $request->email;

        $registrationSession = RegistrationSession::where('email', $email)->first();

        if ($registrationSession) {
            return $this->error('Incomplete registration.', 400);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return $this->error('Account not found.', 404);
        }

        if (!$request->password || !Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials.', 401);
        }

        $otp = $otpService->generate(
            identifier: $user->email,
            type: 'login',
            userId: $user->id
        );

        SendOtpJob::dispatch($user->email, $otp, 'Login');

        return $this->success('OTP sent to email.');
    }

    public function verifyOtp(Request $request, Otpservice $otpService)
    {
        $request->validate([
            'email'       => 'required|email',
            'otp'         => 'required|digits:6',
            'device_name' => 'required|string',
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

            return $this->success('Login successful.', ['token' => $token]);

        } catch (\Exception $e) {

            return $this->error($e->getMessage(), 400);
        }
    }
}
