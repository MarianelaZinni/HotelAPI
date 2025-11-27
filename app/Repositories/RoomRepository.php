<?php

namespace App\Repositories;

use App\Models\Room;

class RoomRepository implements RoomRepositoryInterface
{
    /**
     * Obtiene la habitación por id o lanza NotFound.
     */
    public function findOrFail(int $id)
    {
        return Room::findOrFail($id);
    }
}
