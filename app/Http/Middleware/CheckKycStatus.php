<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\KycVerification;

class CheckKycStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $requiredStatus  Optional: required KYC status
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $requiredStatus = 'verified')
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $kyc = KycVerification::where('user_id', $user->id)->first();

        if (!$kyc) {
            return response()->json([
                'message' => 'KYC not submitted'
            ], 403);
        }

        if ($kyc->status !== $requiredStatus) {
            return response()->json([
                'message' => 'KYC not verified',
                'status' => $kyc->status
            ], 403);
        }

        return $next($request);
    }
}
