<?php

namespace App\Http\Controllers;

use App\Services\HotelService;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * @OA\Tag(
 *     name="Hotels",
 *     description="Operaciones relacionadas con hoteles"
 * )
 */
class HotelController extends Controller
{
    protected $service;

    public function __construct(HotelService $service)
    {
        $this->service = $service;
    }


    /**
     * Muestra el listado de todos los hoteles.
     *
     * @OA\Get(
     *     path="/api/hotels",
     *     tags={"Hoteles"},
     *     summary="Listar todos los hoteles",
     *     description="Devuelve el listado completo de los hoteles",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de hoteles",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Hotel Central"),
     *                 @OA\Property(property="city", type="string", example="Buenos Aires"),
     *                 @OA\Property(property="address", type="string", example="Calle Falsa 123"),
     *                 @OA\Property(property="phone", type="string", example="+54 11 1234 5678"),
     *                 @OA\Property(property="email", type="string", format="email", example="contacto@hotel.com")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $hotels = $this->service->index();
        return response()->json($hotels);
    }

    /**
     * Almacena un nuevo hotel en el almacenamiento.
     *
     * @OA\Post(
     *     path="/api/hotels",
     *     tags={"Hoteles"},
     *     summary="Crear un nuevo hotel",
     *     description="Crea un nuevo hotel con los datos dados",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Hotel Central"),
     *             @OA\Property(property="city", type="string", example="Buenos Aires"),
     *             @OA\Property(property="address", type="string", example="Calle Falsa 123"),
     *             @OA\Property(property="phone", type="string", example="+54 11 1234 5678"),
     *             @OA\Property(property="email", type="string", format="email", example="contacto@hotel.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Hotel creado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Hotel Central"),
     *             @OA\Property(property="city", type="string", example="Buenos Aires"),
     *             @OA\Property(property="address", type="string", example="Calle Falsa 123"),
     *             @OA\Property(property="phone", type="string", example="+54 11 1234 5678"),
     *             @OA\Property(property="email", type="string", format="email", example="contacto@hotel.com"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
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
    public function store(Request $request)
    {
         $data = $request->all();

        try {
            $hotel = $this->service->store($data);
            return response()->json($hotel, 201);
        } catch (InvalidArgumentException $e) {
            $errors = json_decode($e->getMessage(), true);
            return response()->json([
                'message' => 'La información proporcionada no es válida.',
                'errors' => $errors ?: $e->getMessage()
            ], 422);
        }
    }
}