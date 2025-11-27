<?php

namespace App\Http\Controllers;

use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * @OA\Tag(
 *     name="Reservas",
 *     description="Operaciones relacionadas con reservas"
 * )
 */

class ReservationController extends Controller
{
    protected $service;

    public function __construct(ReservationService $service)
    {
        $this->service = $service;
    }

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
        $reservation = $this->service->show((int)$id);
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
        $filters = $request->only(['from', 'to', 'hotel_id', 'room_id']);
        $bookings = $this->service->index($filters);
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
          $data = $request->all();

        try {
            $reservation = $this->service->store($data);

            Log::info('Reserva creada: id=' . ($reservation->id ?? '') . ' room_id=' . ($reservation->room_id ?? ''));

            return response()->json($reservation, 201);
        } catch (InvalidArgumentException $e) {
            // Errores de validación en el service
            $errors = json_decode($e->getMessage(), true);
            return response()->json([
                'message' => 'La información proporcionada no es válida.',
                'errors' => $errors ?: $e->getMessage()
            ], 422);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'solapamiento_reserva') {
                $message = 'Reserva conflictiva para la habitación seleccionada en las fechas indicadas.';
                Log::warning('Reserva conflictiva: ' . json_encode($data));
                return response()->json(['message' => $message], 409);
            }
            // Re-lanzar otras RuntimeException
            throw $e;
        }
    }
}
