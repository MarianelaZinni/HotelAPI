<?php

namespace App\Providers;

use App\Repositories\HotelRepository;
use App\Repositories\HotelRepositoryInterface;
use App\Repositories\ReservationRepository;
use App\Repositories\ReservationRepositoryInterface;
use App\Repositories\RoomRepository;
use App\Repositories\RoomRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Bindings para repositorios (inyección de dependencias)
        $this->app->bind(ReservationRepositoryInterface::class, ReservationRepository::class);
        $this->app->bind(RoomRepositoryInterface::class, RoomRepository::class);
        $this->app->bind(HotelRepositoryInterface::class, HotelRepository::class);
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
