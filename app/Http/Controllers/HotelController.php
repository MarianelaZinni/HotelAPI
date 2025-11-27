<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    /**
     * Muestra el listado de todos los hoteles.
     */


    public function index()
    {
        return response()->json(Hotel::all());
    }

     /**
     * Almacena un nuevo hotel en el almacenamiento.
     */

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'city' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email'
        ]);

        $hotel = Hotel::create($data);

        return response()->json($hotel, 201);
    }
}