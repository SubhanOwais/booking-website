<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\AdminGalleryController;

use App\Http\Controllers\API\SeatDetailController;
use App\Http\Controllers\API\SeatPlaneController;
use App\Http\Controllers\API\CityMappingApiController;
use App\Http\Controllers\API\SearchController;
use App\Http\Controllers\API\RefundController;
use App\Http\Controllers\API\TimeScheduleController;

use App\Http\Controllers\WebPage\ProfileController;
use App\Http\Controllers\WebPage\GalleryController;

use App\Http\Controllers\Company\RoleController;
use App\Http\Controllers\Company\PermissionController;

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\EmailVerificationController;

// Frontend will use this
Route::get('/cities', [CityController::class, 'activeCities']);

Route::get('/discount-route/{fromCityId}/{toCityId}', [App\Http\Controllers\API\DiscountController::class, 'getDiscountByRoute']);
Route::get('discounts/all', [ProfileController::class, 'getAllDiscounts']);

// ─────────────────────────────────────────────────────────────
// Search ROUTES
// ─────────────────────────────────────────────────────────────
// Route::post('/search', [SearchController::class, 'search']);
Route::options('/search', [SearchController::class, 'searchOptions']); // preflight
Route::get('/search',     [SearchController::class, 'search']);         // actual data
// Route::get('/search/stream', [SearchController::class, 'stream']);

// ─────────────────────────────────────────────────────────────
// SEAT ROUTES
// ─────────────────────────────────────────────────────────────
Route::get('/seats', [SeatPlaneController::class, 'index']);
Route::post('/seats/hold', [SeatPlaneController::class, 'holdSeats']);
Route::post('/seats/unhold', [SeatPlaneController::class, 'unholdSeats']);
Route::post('/seats/fare', [SeatPlaneController::class, 'verifyFare']);
Route::get('/terminals', [SeatPlaneController::class, 'getSourceTerminals']);

// ─────────────────────────────────────────────────────────────
// BOOKING ROUTES
// ─────────────────────────────────────────────────────────────
Route::post('/bookings/create', [SeatDetailController::class, 'createBooking'])->name('api.bookings.create');
Route::post('/bookings/confirm', [SeatDetailController::class, 'confirmBooking'])->name('api.bookings.confirm');
Route::get('/bookings/{pnr}', [SeatDetailController::class, 'getBookingByPNR'])->name('api.bookings.show');
Route::post('/bookings/{pnr}/payment', [SeatDetailController::class, 'updatePaymentStatus'])->name('api.bookings.payment');

// Cancel ticket
Route::post('/profile/cancel-ticket', [ProfileController::class, 'cancelBooking']);

// Roles and Permissions Api
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/get-roles', [RoleController::class, 'get_roles']);
    Route::post('/save-role', [RoleController::class, 'store']);
    Route::delete('/delete-role/{id}', [RoleController::class, 'delete_role']);
    Route::get('/get-permissions', [PermissionController::class, 'get_permissions']);
});

// City Mapping Routes
Route::prefix('city-mappings')->group(function () {
    Route::get('/main-cities', [CityMappingApiController::class, 'getMainCities']);
    Route::post('/mapped-cities', [CityMappingApiController::class, 'getMappedCities']);
});

Route::post('/seat-details', [SeatDetailController::class, 'getSeatDetails']);
Route::post('/verify-seat-fare', [SeatDetailController::class, 'verifySeatFare']);


// WebPage Gallery
Route::get('/web-bus-gallery/{id}', [GalleryController::class, 'get_bus_gallery_detail'])->name('gallery.detail');
Route::get('/galleries', [GalleryController::class, 'get_all_galleries_with_stats']);
Route::get('/galleries/by-type/{type}', [GalleryController::class, 'get_galleries_by_type']);
Route::get('/bus-gallery/bus-types', [GalleryController::class, 'get_bus_gallery_for_types']);

Route::get('/discount-city/{cityId}', [DiscountController::class, 'getDiscountForCity']);

// Admin Bus Gallery Routes
Route::prefix('bus-gallery')->name('BusGallery.')->group(function () {
    // API routes
    Route::get('/get-galleries', [AdminGalleryController::class, 'get_bus_galleries'])->name('get');
    Route::get('/gallery-detail/{id}', [AdminGalleryController::class, 'get_bus_gallery_detail'])->name('detail');
    Route::post('/add-gallery', [AdminGalleryController::class, 'add_bus_gallery'])->name('add');
    Route::delete('/delete-gallery/{id}', [AdminGalleryController::class, 'delete_bus_gallery'])->name('delete');
    Route::delete('/delete-main-image/{name}/{id}', [AdminGalleryController::class, 'delete_main_image'])->name('deleteMainImage');
    Route::delete('/delete-gallery-image/{name}/{id}', [AdminGalleryController::class, 'delete_gallery_image'])->name('deleteGalleryImage');
});


// Route::get('/route-details', [TripController::class, 'search']);

// Route::get('/timeschedule', [TimeScheduleController::class, 'index']);
// Route::get('/time-schedule', [TimeScheduleController::class, 'index']);
// Route::get('seat-details', [TimeScheduleController::class, 'getSeatDetailsEndpoint']);
// Route::get('verify-fare', [TimeScheduleController::class, 'verifyFareEndpoint']);
