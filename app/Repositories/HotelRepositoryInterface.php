<?php

namespace App\Repositories;

interface HotelRepositoryInterface
{
    /**
     * Obtiene todos los hoteles.
     */
    public function all();

    /**
     * Crea un nuevo hotel con los datos proporcionados en $data
     */
    public function create(array $data);

    /**
     * Obtiene un hotel por id o lanza NotFound.
     */ 
    public function findOrFail(int $id);
}
