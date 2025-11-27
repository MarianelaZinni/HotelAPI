<?php

use App\Http\Controllers\HotelController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ReservationController;
use App\Http\Middleware\ApiKeyMiddleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Exponemos las rutas API desde web.php usando prefijo 'api' y aplicando
| middleware necesarios.
|
*/

Route::prefix('api')->middleware([SubstituteBindings::class, ApiKeyMiddleware::class])->group(function () {
    // Hotels
    Route::get('/hotels', [HotelController::class, 'index']);
    Route::post('/hotels', [HotelController::class, 'store'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

    // Rooms (per hotel)
    Route::get('/hotels/{hotelId}/rooms', [RoomController::class, 'index']);
    Route::post('/hotels/{hotelId}/rooms', [RoomController::class, 'store'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

    // Reservations
    Route::get('/reservations', [ReservationController::class, 'index']); // filtros: from, to, hotel_id, room_id
    Route::post('/reservations', [ReservationController::class, 'store'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/reservations/{id}', [ReservationController::class, 'show']);
});