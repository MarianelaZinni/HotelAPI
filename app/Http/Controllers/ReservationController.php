<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    /**
     * Muestra los detalles de una reserva específica, dado su id
     */
    public function show($id)
    {
        $reservation = Reservation::with('room.hotel')->findOrFail($id);
        return response()->json($reservation);
    }

    /**
     * Muestra el listado de las reservas con filtros opcionales (desde, hasta, id del hotel, id de la habitación)
     */
    public function index(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'hotel_id' => 'nullable|integer|exists:hotels,id',
            'room_id' => 'nullable|integer|exists:rooms,id',
        ]);

        $query = Reservation::query()->with('room.hotel');

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('hotel_id')) {
            $query->whereHas('room', function ($q) use ($request) {
                $q->where('hotel_id', $request->hotel_id);
            });
        }

        if ($request->filled('from')) {
            $query->where('check_out', '>', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('check_in', '<', $request->to);
        }

        $bookings = $query->orderBy('check_in')->get();

        return response()->json($bookings);
    }

    /**
     * Almacena una nueva reserva en el almacenamiento.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => 'required|integer|exists:rooms,id',
            'guest_name' => 'required|string',
            'guest_email' => 'nullable|email',
            'guest_count' => 'nullable|integer|min:1',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);

        $room = Room::findOrFail($data['room_id']);

        // Convertir a cadenas DateTime (la base de datos espera datetimes)
        $checkIn = $data['check_in'];
        $checkOut = $data['check_out'];

        // Comprobación de solapamiento:
        // El solapamiento existe si: existing.check_in < new.check_out AND existing.check_out > new.check_in
        $overlap = Reservation::where('room_id', $room->id)
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->where('check_in', '<', $checkOut)
                  ->where('check_out', '>', $checkIn);
            })->exists();

        if ($overlap) {
            $message = 'Reserva conflictiva para la habitación seleccionada en las fechas indicadas.';
            Log::warning('Conflicto de reserva para room_id=' . $room->id . ' entre ' . $checkIn . ' y ' . $checkOut);
            return response()->json(['message' => $message], 409);
        }

        $reservation = Reservation::create($data);

        Log::info('Reserva creada: id=' . $reservation->id . ' room_id=' . $reservation->room_id);

        return response()->json($reservation, 201);
    }
}
