<?php

namespace App\Repositories;

use App\Models\Hotel;

class HotelRepository implements HotelRepositoryInterface
{
    /**
     * Obtiene todos los hoteles.
     */
    public function all()
    {
        return Hotel::all();
    }

    /**
     * Crea un nuevo hotel con los datos proporcionados en $data
     */
    public function create(array $data)
    {
        return Hotel::create($data);
    }

    /**
     * Obtiene un hotel por id o lanza NotFound.
     */
    public function findOrFail(int $id)
    {
        return Hotel::findOrFail($id);
    }
}
