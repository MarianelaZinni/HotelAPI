<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Reservas",
 *     description="Operaciones relacionadas con reservas"
 * )
 */

class ReservationController extends Controller
{
   /**
     * Muestra los detalles de una reserva específica, dado su id
     *
     * @OA\Get(
     *     path="/api/reservations/{id}",
     *     tags={"Reservas"},
     *     summary="Obtener una reserva en concreto dado su ID",
     *     description="Devuelve los detalles de una reserva, incluyendo relación con la habitación y el hotel",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la reserva",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reserva encontrada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="room_id", type="integer"),
     *             @OA\Property(property="guest_name", type="string"),
     *             @OA\Property(property="guest_email", type="string", nullable=true),
     *             @OA\Property(property="guest_count", type="integer", nullable=true),
     *             @OA\Property(property="check_in", type="string", format="date-time"),
     *             @OA\Property(property="check_out", type="string", format="date-time"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="room",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(
     *                     property="hotel",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Reserva no encontrada")
     * )
     */
    public function show($id)
    {
        $reservation = Reservation::with('room.hotel')->findOrFail($id);
        return response()->json($reservation);
    }

    /**
     * Muestra el listado de las reservas con filtros opcionales (desde, hasta, id del hotel, id de la habitación)
     *
     * @OA\Get(
     *     path="/api/reservations",
     *     tags={"Reservas"},
     *     summary="Listar todas las reservas",
     *     description="Devuelve un listado de reservas. Se pueden aplicar filtros: from, to, hotel_id, room_id",
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         description="Fecha desde (exclusive) formato YYYY-MM-DD",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         description="Fecha hasta (exclusive) formato YYYY-MM-DD",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="hotel_id",
     *         in="query",
     *         description="Filtrar por ID de hotel",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="room_id",
     *         in="query",
     *         description="Filtrar por ID de habitación",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listado de reservas",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="room_id", type="integer"),
     *                 @OA\Property(property="guest_name", type="string"),
     *                 @OA\Property(property="guest_email", type="string", nullable=true),
     *                 @OA\Property(property="guest_count", type="integer", nullable=true),
     *                 @OA\Property(property="check_in", type="string", format="date-time"),
     *                 @OA\Property(property="check_out", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
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
     * Almacena una nueva reserva en la base de datos.
     *
     * @OA\Post(
     *     path="/api/reservations",
     *     tags={"Reservas"},
     *     summary="Crear una nueva reserva",
     *     description="Crea una nueva reserva si no hay solapamiento con reservas existentes",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"room_id","guest_name","check_in","check_out"},
     *             @OA\Property(property="room_id", type="integer", example=1),
     *             @OA\Property(property="guest_name", type="string", example="Juan Pérez"),
     *             @OA\Property(property="guest_email", type="string", format="email", example="marianela@mail.com"),
     *             @OA\Property(property="guest_count", type="integer", example=2),
     *             @OA\Property(property="check_in", type="string", format="date-time", example="2025-12-01T14:00:00Z"),
     *             @OA\Property(property="check_out", type="string", format="date-time", example="2025-12-05T11:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reserva creada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="room_id", type="integer"),
     *             @OA\Property(property="guest_name", type="string"),
     *             @OA\Property(property="guest_email", type="string", nullable=true),
     *             @OA\Property(property="guest_count", type="integer", nullable=true),
     *             @OA\Property(property="check_in", type="string", format="date-time"),
     *             @OA\Property(property="check_out", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=409, description="Conflicto por solapamiento de reservas"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
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

        // Convertir DateTime (la base de datos espera datetimes)
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
