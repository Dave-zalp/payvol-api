<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Kyc\KycVerificationController;
use App\Http\Controllers\VirtualBank\CrudController;
use App\Http\Controllers\VirtualCard\CardController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/virtualbank/strollwallet/webhook', [WebhookController::class, 'virtualbank']);
Route::post('/virtualcard/strollwallet/webhook', [WebhookController::class, 'virtualcard']);

Route::prefix('auth')->group(function () {
    Route::post('/register/step-1', [RegistrationController::class, 'stepOne']);
    Route::post('/register/step-2', [RegistrationController::class, 'stepTwo']);
    Route::post('/register/verify-otp', [RegistrationController::class, 'verifyOtp']);
    Route::post('/register/step-4', [RegistrationController::class, 'stepFour']);
    Route::post('/register/step-5', [RegistrationController::class, 'stepFive']);

    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/login/verify-otp', [LoginController::class, 'verifyOtp']);

});

    Route::middleware(['auth:sanctum'])->group(function () {

        # KYC
        Route::post('/kyc/submit', [KycVerificationController::class, 'submit']);
        Route::get('/wallets', [WalletController::class, 'index']);
        Route::get('/wallets/transactions', [WalletController::class, 'transactions']);
        Route::get('/wallets/{currency}/balance', [WalletController::class, 'balance']);

        Route::middleware(['kyc.check'])->group(function () {
            Route::get('/kyc/status', [KycVerificationController::class, 'status']);

            # naira
            Route::prefix('naira')->group(function () {
                Route::post('/virtualbank/create', [CrudController::class, 'create']);
                Route::get('/virtualbank/get', [CrudController::class, 'show']);
            });

            # USD
            Route::prefix('USD')->group(function () {
                Route::post('/virtualcard/create',[CardController::class, 'create']);
                Route::get('/virtualcard',[CardController::class, 'index']);
                Route::get('/virtualcard/{id}',[CardController::class, 'show']);
                Route::post('/virtualcard/{id}/fund',[CardController::class, 'fund']);
                Route::get('/virtualcard/{id}/transactions',[CardController::class, 'transactions']);
                Route::get('/virtualcard/{id}/balance',[CardController::class, 'cardBalance']);
                Route::post('/virtualcard/{id}/{action}',[CardController::class, 'toggleStatus'])->whereIn('action', ['freeze', 'unfreeze']);
            });

    });
  });
