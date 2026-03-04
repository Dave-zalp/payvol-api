<?php

use App\Http\Controllers\RegistrationController;
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
});
