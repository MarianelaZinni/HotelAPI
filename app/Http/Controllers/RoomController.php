<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RoomService;
use InvalidArgumentException;

/**
 * @OA\Tag(
 *     name="Habitaciones",
 *     description="Operaciones relacionadas con habitaciones"
 * )
 */
class RoomController extends Controller
{
    protected $service;

    public function __construct(RoomService $service)
    {
        $this->service = $service;
    }

    /**
     * Muestra el listado de las habitaciones para un hotel específico.
     *
     * @OA\Get(
     *     path="/api/hotels/{hotelId}/rooms",
     *     tags={"Habitaciones"},
     *     summary="Listar las habitaciones de un determinado hotel",
     *     description="Devuelve el listado de habitaciones pertenecientes al hotel indicado",
     *     @OA\Parameter(
     *         name="hotelId",
     *         in="path",
     *         description="ID del hotel",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listado de habitaciones",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Habitación Matrimonial"),
     *                 @OA\Property(property="capacity", type="integer", nullable=true, example=2),
     *                 @OA\Property(property="room_type", type="string", nullable=true, example="standard"),
     *                 @OA\Property(property="price", type="number", format="float", nullable=true, example=120.50),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Hotel no encontrado")
     * )
     */
    public function index($hotelId)
    {
        $rooms = $this->service->index((int) $hotelId);
        return response()->json($rooms);
    }

    /**
     * Almacena una nueva habitación para un hotel específico en la base de datos.
     *
     * @OA\Post(
     *     path="/api/hotels/{hotelId}/rooms",
     *     tags={"Habitaciones"},
     *     summary="Crear una habitación de un hotel",
     *     description="Crea una nueva habitación asociada al hotel indicado",
     *     @OA\Parameter(
     *         name="hotelId",
     *         in="path",
     *         description="ID del hotel",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Habitación Matrimonial"),
     *             @OA\Property(property="capacity", type="integer", nullable=true, example=2),
     *             @OA\Property(property="room_type", type="string", nullable=true, example="suite"),
     *             @OA\Property(property="price", type="number", format="float", nullable=true, example=150.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Habitación creada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=10),
     *             @OA\Property(property="name", type="string", example="Habitación Matrimonial"),
     *             @OA\Property(property="capacity", type="integer", nullable=true, example=2),
     *             @OA\Property(property="room_type", type="string", nullable=true, example="suite"),
     *             @OA\Property(property="price", type="number", format="float", nullable=true, example=150.00),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Hotel no encontrado"),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 additionalProperties=@OA\Property(type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request, $hotelId)
    {
         $data = $request->all();

        try {
            $room = $this->service->store((int)$hotelId, $data);
            return response()->json($room, 201);
        } catch (InvalidArgumentException $e) {
            $errors = json_decode($e->getMessage(), true);
            return response()->json([
                'message' => 'La información proporcionada no es válida.',
                'errors' => $errors ?: $e->getMessage()
            ], 422);
        }
    }
}
