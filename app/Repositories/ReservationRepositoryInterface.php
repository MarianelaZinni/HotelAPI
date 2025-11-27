<?php

namespace App\Repositories;

interface ReservationRepositoryInterface
{
    public function findByIdWithRelations(int $id);

    /**
        * Obtiene reservas filtradas según los criterios proporcionados en $filters     
    */
    public function getFiltered(array $filters);

    /**
        * Comprueba si existe un solapamiento para una habitación en las fechas dadas.
     */
    public function existsOverlap(int $roomId, string $checkIn, string $checkOut): bool;

     /**
        * Crea una nueva reserva con los datos proporcionados en $data
     */
    public function create(array $data);
}
