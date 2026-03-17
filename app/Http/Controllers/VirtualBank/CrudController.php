<?php

namespace App\Http\Controllers\VirtualBank;

use App\Http\Controllers\Controller;
use App\Jobs\CreateVirtualAccountJob;
use App\Services\VirtualBankAccountService;
use Illuminate\Http\Request;

class CrudController extends Controller
{
    //
    protected VirtualBankAccountService $service;

    public function __construct(VirtualBankAccountService $service)
    {
        $this->service = $service;
    }

    public function create(Request $request)
    {
        try {
            $user = $request->user();

            $user->name = trim(implode(' ', array_filter([
                $user->first_name,
                $user->middle_name,
                $user->surname,
            ])));


            CreateVirtualAccountJob::dispatch($user);

            return response()->json([
                'status'  => true,
                'message' => 'Virtual account creation is being processed.',
            ], 202);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Throwable $e) {

            return response()->json([
                'status'  => false,
                'message' => 'An unexpected error occurred. Please try again later.c.',
            ], 500);
        }
    }
}
