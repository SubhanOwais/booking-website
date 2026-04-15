<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\WebPage\BookingController;
use App\Http\Controllers\WebPage\ProfileController;
use App\Http\Controllers\WebPage\GalleryController;
use App\Http\Controllers\WebPage\SpecialBookingController;

use App\Http\Controllers\Admin\AdminGalleryController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\CityMappingController;
use App\Http\Controllers\Admin\TicketingController;
use App\Http\Controllers\Admin\PartnerRequestController;
use App\Http\Controllers\Admin\CompanyAdminController;

use App\Http\Controllers\API\SeatPlaneController;
use App\Http\Controllers\API\RefundController;
use App\Http\Controllers\API\SeatDetailController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

use App\Http\Controllers\Company\CompanyDashboardController;
use App\Http\Controllers\Company\CompanyDiscountController;
use App\Http\Controllers\Company\CompanyCityController;
use App\Http\Controllers\Company\CompanyTicketingController;
use App\Http\Controllers\Company\CompanyUserController;
use App\Http\Controllers\Company\RoleController;
use App\Http\Controllers\Company\PermissionController;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Auth\AuthUserController;


// User data (store/userDate.js)
Route::middleware('auth:sanctum')->get('/auth/me', [AuthUserController::class, 'me']);

// Public routes
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

Route::get('/special-booking', [SpecialBookingController::class, 'index'])->name('special-booking');
Route::post('/special-booking', [SpecialBookingController::class, 'store'])->name('special-booking.store');

Route::post('/api/bookings/generate-ticket', [BookingController::class, 'generateTicket'])->middleware('auth');

Route::get('/galleries', function () {
    return Inertia::render('Landing/GalleryPage/Index');
})->name('gallery');

// Cache clear route (for development/testing only)
Route::get('/cache-clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');

    return 'Cache cleared successfully!';
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Seat routes
    Route::get('/seat-detail', [SeatDetailController::class, 'index'])->name('seat-detail');
    Route::get('/seat-plane', [SeatPlaneController::class, 'index'])->name('seat-plane');
    Route::post('/api/verify-seat-fare', [SeatPlaneController::class, 'verifyFare']);

    // Admin dashboard (only for SuperAdmin) - FIXED: Use 'admin' middleware alias
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        // Route::get('/cities', function () { return Inertia::render('Admin/Cities/Index'); })->name('cities');

        // City Routes
        Route::get('/cities', [CityController::class, 'index'])->name('cities');
        Route::post('/cities', [CityController::class, 'store'])->name('cities.store');
        Route::put('/cities/{city}', [CityController::class, 'update'])->name('cities.update');
        Route::delete('/cities/{city}', [CityController::class, 'destroy'])->name('cities.destroy');
        Route::post('/cities/{city}/toggle-status', [CityController::class, 'toggleStatus'])->name('cities.toggle-status');

        // Discount Cities Routes
        Route::resource('discount', DiscountController::class)->except(['show', 'create', 'edit']);
        Route::post('discount/{id}/restore', [DiscountController::class, 'restore'])->name('discount.restore');
        Route::delete('discount/{id}/force-delete', [DiscountController::class, 'forceDelete'])->name('discount.force-delete');
        Route::post('discount/{id}/toggle-active', [DiscountController::class, 'toggleActive'])->name('discount.toggle-active');
        Route::get('discount/mapped-cities/{mainCityId}', [DiscountController::class, 'getMappedCities'])->name('discount.mapped-cities');

        // Bus Gallery Routes
        Route::prefix('gallery')->name('Gallery.')->group(function () {
            Route::get('/', [AdminGalleryController::class, 'index'])->name('index');
        });

        // partner request routes
        Route::get('/partner-requests', [PartnerRequestController::class, 'index'])->name('partner-requests.index');
        Route::patch('/partner-requests/{partnerRequest}/accept', [PartnerRequestController::class, 'accept'])->name('partner-requests.accept');
        Route::patch('/partner-requests/{partnerRequest}/reject', [PartnerRequestController::class, 'reject'])->name('partner-requests.reject');
        Route::patch('/partner-requests/{partnerRequest}/reviewing', [PartnerRequestController::class, 'reviewing'])->name('partner-requests.reviewing');

        // Companies
        Route::get('/companies', [CompanyAdminController::class, 'index'])->name('companies.index');
        Route::get('/companies/create/{partnerRequest}', [CompanyAdminController::class, 'create'])->name('companies.create');
        Route::post('/companies', [CompanyAdminController::class, 'store'])->name('companies.store');
        Route::patch('/companies/{company}/toggle', [CompanyAdminController::class, 'toggle'])->name('companies.toggle');

        // City Mapping
        Route::get('/city-mapping', [CityMappingController::class, 'index'])->name('city-mapping.index');
        Route::post('/city-mapping', [CityMappingController::class, 'store'])->name('city-mapping.store');
        Route::get('/city-mapping/{mapping}/edit', [CityMappingController::class, 'edit'])->name('city-mapping.edit');
        Route::put('/city-mapping/{mapping}', [CityMappingController::class, 'update'])->name('city-mapping.update');
        Route::delete('/city-mapping/{mapping}', [CityMappingController::class, 'destroy'])->name('city-mapping.destroy');

        // Ticketing routes
        Route::get('/ticketing', [TicketingController::class, 'index'])->name('ticketing.index');
        Route::get('/ticketing/export', [TicketingController::class, 'export'])->name('ticketing.export');
        Route::get('/ticketing/{id}', [TicketingController::class, 'show'])->name('ticketing.show');

        // Refund routes
        Route::get('/refund', [RefundController::class, 'index'])->name('refund.index');
        Route::post('/refund/process', [RefundController::class, 'processRefund'])->name('refund.process');

        // WebSite Detail
        Route::get('websites', [\App\Http\Controllers\Admin\WebsiteController::class, 'index'])->name('websites.index');
        Route::post('websites', [\App\Http\Controllers\Admin\WebsiteController::class, 'store'])->name('websites.store');
        Route::put('websites/{website}', [\App\Http\Controllers\Admin\WebsiteController::class, 'update'])->name('websites.update');
        Route::delete('websites/{website}', [\App\Http\Controllers\Admin\WebsiteController::class, 'destroy'])->name('websites.destroy');
    });

    // Profile routes (only for WebCustomer) - FIXED: Use 'customer' middleware alias
    Route::middleware(['customer'])->prefix('profile')->name('profile.')->group(function () {
        // WebCustomer profile routes
        Route::get('/', [ProfileController::class, 'show'])->name('index');
        Route::post('/', [ProfileController::class, 'update'])->name('update');
    });

    // Company Panel Routes
    Route::middleware(['company'])->prefix('company/')->name('company.')->group(function () {
        Route::get('company-data', [CompanyDashboardController::class, 'companyData']);

        Route::get('dashboard', [CompanyDashboardController::class, 'index'])->name('dashboard');

        // Discount Cities Routes
        Route::resource('discount', CompanyDiscountController::class)->except(['show', 'create', 'edit']);
        Route::post('discount/{id}/restore', [CompanyDiscountController::class, 'restore'])->name('discount.restore');
        Route::delete('discount/{id}/force-delete', [CompanyDiscountController::class, 'forceDelete'])->name('discount.force-delete');
        Route::post('discount/{id}/toggle-active', [CompanyDiscountController::class, 'toggleActive'])->name('discount.toggle-active');
        Route::get('discount/mapped-cities/{mainCityId}', [CompanyDiscountController::class, 'getMappedCities'])->name('discount.mapped-cities');

        // Company Cities Routes
        Route::get('cities', [CompanyCityController::class, 'index'])->name('cities.index');
        Route::post('cities', [CompanyCityController::class, 'store'])->name('cities.store');
        Route::put('cities/{companyCity}', [CompanyCityController::class, 'update'])->name('cities.update');
        Route::delete('cities/{companyCity}', [CompanyCityController::class, 'destroy'])->name('cities.destroy');
        Route::post('cities/{companyCity}/toggle-active', [CompanyCityController::class, 'toggleActive'])->name('cities.toggle-active');

        // Ticketing routes
        Route::get('/ticketing', [CompanyTicketingController::class, 'index'])->name('ticketing.index');
        Route::get('/ticketing/export', [CompanyTicketingController::class, 'exportToExcel'])->name('ticketing.export'); // ✅ BEFORE {id}
        Route::get('/ticketing/{id}', [CompanyTicketingController::class, 'show'])->name('ticketing.show');

        // ── Company Users Management (Employees & Partners) ───────────────────
        Route::get('/users', [CompanyUserController::class, 'index'])->name('users.index');
        Route::post('users/create', [CompanyUserController::class, 'store'])->name('users.store');
        Route::post('users/update', [CompanyUserController::class, 'update'])->name('users.update');
        Route::delete('users/delete/{id}', [CompanyUserController::class, 'destroy'])->name('users.destroy');
        Route::get('users/roles', [CompanyUserController::class, 'getRoles'])->name('users.roles');

        // Role & Permission Management (Company)
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
             Route::get('/list', [RoleController::class, 'get_roles'])->name('list');
            Route::post('/save', [RoleController::class, 'store'])->name('save');
            Route::delete('/delete/{id}', [RoleController::class, 'delete_role'])->name('delete');
        });

        Route::get('/permissions', [PermissionController::class, 'get_permissions'])->name('permissions');
    });
});

// Login/Logout routes
// Route::middleware('guest')->group(function () {
//     Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
//     Route::post('login', [AuthenticatedSessionController::class, 'store']);
// });

// Run this ONLY ONE TIME
Route::get('/import-cities', [CityController::class, 'importCities']);

require __DIR__.'/auth.php';
