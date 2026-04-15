<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\WebPage\CompanyController;
use App\Http\Controllers\Auth\EmailVerificationController;

// Login-only auth (no register / reset password flows)
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login'); // Show login form
    Route::post('login', [AuthenticatedSessionController::class, 'store']); // Handle login form submission
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// Registration routes
Route::get('register', [RegisterController::class, 'create']); // Show registration form
Route::post('/register', [RegisterController::class, 'register']); // Handle registration form submission


// Email Verification
Route::get('verify-email/{token}', [EmailVerificationController::class, 'verify']);
Route::post('resend-verification', [EmailVerificationController::class, 'resend']);

// Create Partner route
Route::get('/partner-request', [CompanyController::class, 'showPartnerRequest'])->name('partner.request.form'); // Show partner request form
Route::post('/partner-request', [CompanyController::class, 'submitPartnerRequest'])->name('partner.request'); // Handle partner request form submission
