<?php

namespace App\Http\Controllers;

use App\Jobs\SendRegistrationOtpJob;
use App\Models\RegistrationSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\OtpService;

class RegistrationController extends Controller
{
    //
    public function stepOne(Request $request)
    {
        $request->validate([
            'country' => 'required|string',
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'surname' => 'required|string',
            'gender' => 'required|in:male,female,other',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'referral_code' => 'nullable|string'
        ]);

        $session = RegistrationSession::updateOrCreate(
            ['email' => $request->email],
            [
                'step_data' => $request->only([
                    'country',
                    'first_name',
                    'middle_name',
                    'surname',
                    'gender',
                    'phone',
                    'referral_code'
                ]),
                'current_step' => 2
            ]
        );

        return response()->json(['message' => 'Proceed to Step 2']);
    }

    public function stepTwo(Request $request, OtpService $otpService)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed'
        ]);

        $session = RegistrationSession::where('email', $request->email)->firstOrFail();

        // Generate OTP via reusable service
       $otp = $otpService->generate(
            identifier: $session->email,
            type: 'registration',
            userId: null // user not created yet
        );

        $session->update([
            'password' => Hash::make($request->password),
            'otp' => Hash::make($otp['code']), // Hash OTP!
            'otp_expires_at' => $otp ['expires_at'],
            'current_step' => 3
        ]);

        // Send Otp Job
        // SendRegistrationOtpJob::dispatch($session->email, $otp['code']);


        return response()->json([
            'message' => 'Password saved. OTP sent to email.'
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6'
        ]);

        $session = RegistrationSession::where('email', $request->email)->firstOrFail();

        if (now()->gt($session->otp_expires_at)) {
            return response()->json(['error' => 'OTP expired'], 400);
        }

        if (!Hash::check($request->otp, $session->otp)) {
            return response()->json(['error' => 'Invalid OTP'], 400);
        }

        $session->update([
            'otp' => null,
            'otp_expires_at' => null,
            'current_step' => 4
        ]);

        return response()->json([
            'message' => 'OTP verified successfully'
        ]);
    }

    public function stepFour(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'purpose' => 'required|string'
        ]);

        $session = RegistrationSession::where('email', $request->email)->firstOrFail();

        $data = $session->step_data;
        $data['purpose'] = $request->purpose;

        $session->update([
            'step_data' => $data,
            'current_step' => 5
        ]);

        return response()->json(['message' => 'Proceed to create PIN']);
    }

    public function stepFive(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'pin' => 'required|digits:4|confirmed'
        ]);

        $session = RegistrationSession::where('email', $request->email)->firstOrFail();

        DB::beginTransaction();

        try {

            $data = $session->step_data;

            $user = User::create([
                'country' => $data['country'],
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'surname' => $data['surname'],
                'gender' => $data['gender'],
                'email' => $session->email,
                'phone' => $data['phone'],
                'password' => $session->password,
                'transaction_pin' => Hash::make($request->pin),
                'referral_code' => $data['referral_code'] ?? null,
                'email_verified' => true,
            ]);

            $session->delete();

            DB::commit();

            $token = $user->createToken($request->device_name)->plainTextToken;

            return response()->json([
                'message' => 'Account created successfully',
                'token' => $token
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Registration failed'], 500);
        }
    }
}
