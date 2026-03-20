<?php

namespace App\Http\Controllers\VirtualCard;

use App\Http\Controllers\Controller;
use App\Jobs\USD\CreateCardJob;
use App\Services\StrowalletCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CardController extends Controller
{

    public function create(Request $request, StrowalletCardService $service)
    {
        try {

            $user = $request->user();

            CreateCardJob::dispatch($user);

            return response()->json([
                'status'  => true,
                'message' => 'Card creation in progress',
            ], 200);

        } catch (\Throwable $e) {

            Log::error('Create Card Error', [
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage() ?: 'Unable to create card'
            ], 500);
        }
    }

}
