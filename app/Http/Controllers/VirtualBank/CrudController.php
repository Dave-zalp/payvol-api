<?php

namespace App\Http\Controllers\VirtualBank;

use App\Http\Controllers\Controller;
use App\Services\VirtualBank\VirtualBankAccountService;
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


            // TODO: Make this a dispatchable Job
            $virtualAccount = $this->service->createVirtualAccount($user);

            return response()->json([
                'status'  => true,
                'message' => 'Virtual account created successfully.',
                'data'    => $virtualAccount
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Throwable $e) {

            return response()->json([
                'status'  => false,
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }
}
