<?php

use App\Http\Controllers\BookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

//Route::prefix('v1')->middleware('auth:api')->group(function() {
Route::prefix('v1')->middleware('client')->group(function() {

    Route::get('/guides/{guide}/bookings', [BookingController::class, 'index']);
    Route::post('/bookings/store', [BookingController::class, 'store']);
    Route::patch('/bookings/{booking}/notes', [BookingController::class, 'updateNotes']);

    // Admin route — bypasses Guide global scope
    Route::withoutMiddleware([])->group(function () {
        Route::delete(
            '/admin/guides/{guide}/bookings/{booking}',
            [BookingController::class, 'destroy']
        )->withoutScopedBindings()->name('admin.guides.bookings.destroy');;
    });

});

