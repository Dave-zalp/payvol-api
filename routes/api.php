<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Kyc\KycVerificationController;
use App\Http\Controllers\VirtualAccountController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function () {
    Route::post('/register/step-1', [RegistrationController::class, 'stepOne']);
    Route::post('/register/step-2', [RegistrationController::class, 'stepTwo']);
    Route::post('/register/verify-otp', [RegistrationController::class, 'verifyOtp']);
    Route::post('/register/step-4', [RegistrationController::class, 'stepFour']);
    Route::post('/register/step-5', [RegistrationController::class, 'stepFive']);

    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/login/verify-otp', [LoginController::class, 'verifyOtp']);

});
    # KYC
    Route::post('/kyc/submit', [KycVerificationController::class, 'submit']);

    Route::middleware(['auth:sanctum', 'kyc.check'])->group(function () {
    Route::get('/kyc/status', [KycVerificationController::class, 'status']);


   #naira
   Route::prefix('naira')->group(function () {

    Route::post('/wallets/virtual/create', [VirtualAccountController::class, 'create']);
    Route::get('/wallets/virtual', [VirtualAccountController::class, 'get']);

   });

  });
