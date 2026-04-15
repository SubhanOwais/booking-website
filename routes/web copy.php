<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\API\SeatPlaneController;
use App\Http\Controllers\API\SeatDetailController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;
use App\Models\TicketingSeat;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing/Home', [
        'canLogin' => Route::has('login'),
    ]);
})->name('home');

Route::get('/booking', function () {
    return Inertia::render('Landing/BookingPage/Booking', [
        'canLogin' => Route::has('login'),
    ]);
})->name('booking');

Route::get('/seat-detail', [SeatDetailController::class, 'index'])
    ->middleware('auth')
    ->name('seat-detail');

Route::get('/seat-plane', [SeatPlaneController::class, 'index'])
    ->middleware('auth')
    ->name('seat-plane');

Route::post('/api/verify-seat-fare', [SeatPlaneController::class, 'verifyFare'])
    ->middleware('auth');


Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Admin routes
    Route::middleware(['ensure.super.admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    });

    // Profile routes (for WebCustomer)
    Route::middleware(['ensure.web.customer'])->prefix('profile')->name('profile.')->group(function () {
        Route::get('/page', [ProfileController::class, 'page'])->name('index');
    });
});

// Route::middleware(['auth', 'verified', 'superadmin'])->group(function () {
//     Route::get('/admin', function () {
//         return Inertia::render('Admin/Dashboard');
//     })->name('admin.dashboard');
// });

// Route::get('/customer-profile', function () {
//     return Inertia::render('Profile.Dashboard');
// })->middleware(['auth', 'verified'])->name('customer_profile');

/*
|--------------------------------------------------------------------------
| Web Customer Protected Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    // Single profile route
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/ticket/{id}', [ProfileController::class, 'getTicket']);
    Route::delete('/profile/trips/{id}/cancel', [ProfileController::class, 'cancelTrip']);

    // Remove or comment out the old dashboard route
    // Route::get('/customer-profile', [ProfileController::class, 'dashboard'])->name('profile.dashboard');
});



require __DIR__.'/auth.php';
