<?php

namespace App\Repositories;

interface RoomRepositoryInterface
{
    /**
     * Obtiene la habitación por id o lanza NotFound.
     */
    public function findOrFail(int $id);
}
