<?php

// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use App\Services\StrowalletService;
// use App\Models\VirtualAccount;

// class VirtualAccountController extends Controller
// {
//     protected $strowallet;

//     public function __construct(StrowalletService $strowallet)
//     {
//         $this->strowallet = $strowallet;
//     }

//     // Create Virtual Naira Account
//     public function create(Request $request)
//     {
//         $user = $request->user();

//         if (VirtualAccount::where('user_id', $user->id)->exists()) {
//             return response()->json(['error'=>'Virtual account already exists'], 400);
//         }

//         $accountName = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name;

//         $response = $this->strowallet->createVirtualAccount(
//             $user->email,
//             $accountName,
//             $user->phone
//         );

//         if (!empty($response['success']) && $response['success'] === false) {
//             return response()->json(['error' => $response['message']], 500);
//         }

//         // Store virtual account locally
//         $virtualAccount = VirtualAccount::create([
//             'user_id' => $user->id,
//             'account_name' => $response['account_name'] ?? $accountName,
//             'account_number' => $response['account_number'] ?? null,
//             'bank_name' => $response['bank_name'] ?? null,
//             'provider_reference' => $response['reference'] ?? null,
//             'balance' => 0,
//         ]);

//         return response()->json([
//             'message' => 'Virtual Naira account created',
//             'account' => $virtualAccount
//         ]);
//     }

//     // Get existing virtual account
//     public function get(Request $request)
//     {
//         $user = $request->user();
//         $account = VirtualAccount::where('user_id', $user->id)->first();

//         if (!$account) {
//             return response()->json(['error'=>'Virtual account not found'], 404);
//         }

//         return response()->json(['account' => $account]);
//     }
// }
