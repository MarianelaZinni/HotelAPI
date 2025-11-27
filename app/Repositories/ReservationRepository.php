<?php

namespace App\Repositories;

use App\Models\Reservation;

class ReservationRepository implements ReservationRepositoryInterface
{
    /**
        * Obtiene una reserva por su ID junto con las relaciones de habitación y hotel.
     */
    public function findByIdWithRelations(int $id)
    {
        return Reservation::with('room.hotel')->findOrFail($id);
    }

    /**
        * Obtiene reservas filtradas según los criterios proporcionados en $filters
    */
    public function getFiltered(array $filters)
    {
        $query = Reservation::query()->with('room.hotel');

        if (!empty($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (!empty($filters['hotel_id'])) {
            $query->whereHas('room', function ($q) use ($filters) {
                $q->where('hotel_id', $filters['hotel_id']);
            });
        }

        if (!empty($filters['from'])) {
            $query->where('check_out', '>', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('check_in', '<', $filters['to']);
        }

        return $query->orderBy('check_in')->get();
    }

    /**
        * Comprueba si existe un solapamiento para una habitación en las fechas dadas.
     */
    public function existsOverlap(int $roomId, string $checkIn, string $checkOut): bool
    {
        return Reservation::where('room_id', $roomId)
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->where('check_in', '<', $checkOut)
                  ->where('check_out', '>', $checkIn);
            })->exists();
    }

    /**
        * Crea una nueva reserva con los datos proporcionados en $data
    */
    public function create(array $data)
    {
        return Reservation::create($data);
    }
}
