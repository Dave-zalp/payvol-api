<?php

namespace App\Http\Controllers\Kyc;

use App\Http\Controllers\Controller;
use App\Services\KycService;
use Illuminate\Http\Request;
use App\Models\KycVerification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class KycVerificationController extends Controller
{

    public function submit(Request $request, KycService $kycService)
    {
        $validated = $request->validate([
            'bvn_number'   => 'nullable|digits:11',
            'nin_number'   => 'nullable|digits:11',
            'selfie_image' => 'nullable|image|max:2048',
            'nin_front'    => 'nullable|image|max:2048',
            'nin_back'     => 'nullable|image|max:2048',
            'dob'          => 'nullable',
            'home_address' => 'nullable|string|max:255',
            'state'        => 'nullable|string|max:100',
            'city'         => 'nullable|string|max:100',
            'zip_code'     => 'nullable|string|max:20',
        ]);

        $existing = KycVerification::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return $this->error('KYC already in progress.', 409);
        }

        $kyc = $kycService->submitKyc($request->user(), $validated);

        return $this->success('KYC verification in progress.', ['status' => $kyc->status]);
    }

    public function status()
    {
        $kyc = KycVerification::where('user_id', Auth::id())->first();

        if (!$kyc) {
            return $this->error('KYC not submitted.', 404);
        }

        return $this->success('KYC status retrieved.', ['status' => $kyc->status]);
    }

    public function view($file)
    {
        $path = 'kyc/' . $file;

        if (!Storage::disk('private')->exists($path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('private')->path($path)
        );
    }

}
