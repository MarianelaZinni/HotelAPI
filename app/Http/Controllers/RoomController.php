<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Habitaciones",
 *     description="Operaciones relacionadas con habitaciones"
 * )
 */
class RoomController extends Controller
{
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
        $hotel = Hotel::findOrFail($hotelId);
        return response()->json($hotel->rooms()->get());
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
        $hotel = Hotel::findOrFail($hotelId);

        $data = $request->validate([
            'name' => 'required|string',
            'capacity' => 'nullable|integer|min:1',
            'room_type' => 'nullable|string',
            'price' => 'nullable|numeric|min:0'
        ]);

        $room = $hotel->rooms()->create($data);

        return response()->json($room, 201);
    }
}
