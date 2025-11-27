<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Muestra el listado de las habitaciones para un hotel específico.
     */

    public function index($hotelId)
    {
        $hotel = Hotel::findOrFail($hotelId);
        return response()->json($hotel->rooms()->get());
    }

    /**
     * Almacena una nueva habitación para un hotel específico en el almacenamiento.
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
