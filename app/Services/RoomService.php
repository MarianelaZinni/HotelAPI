<?php

namespace App\Services;

use App\Repositories\HotelRepositoryInterface;
use InvalidArgumentException;

/**
 * Responsable de la lógica relacionada con habitaciones:
 * - Validación de datos
 * - Búsqueda del hotel mediante HotelRepository->findOrFail()
 * - Creación/obtención de habitaciones usando la relación rooms() del modelo Hotel
 */
class RoomService
{
    protected $hotels;

    public function __construct(HotelRepositoryInterface $hotels)
    {
        $this->hotels = $hotels;
    }

    /**
     * Devuelve la colección de habitaciones para un hotel dado.
     */
    public function index(int $hotelId)
    {
        $hotel = $this->hotels->findOrFail($hotelId);
        return $hotel->rooms()->get();
    }

    /**
     * Valida y crea una habitación para el hotel indicado.
     */
    public function store(int $hotelId, array $data)
    {
        $this->validateData($data);

        $hotel = $this->hotels->findOrFail($hotelId);

        return $hotel->rooms()->create($data);
    }

    /**
     * Validación en PHP para evitar reglas que consultan la BD.
     *
     * Campos:
     * - name: required|string
     * - capacity: nullable|integer|min:1
     * - room_type: nullable|string
     * - price: nullable|numeric|min:0
     *
     * Lanza InvalidArgumentException con JSON de errores si falla.
     */
    protected function validateData(array $data): void
    {
        $errors = [];

        if (!isset($data['name']) || $data['name'] === '') {
            $errors['name'][] = 'El campo name es obligatorio.';
        } elseif (!is_string($data['name'])) {
            $errors['name'][] = 'El campo name debe ser una cadena de texto.';
        }

        // capacity nullable integer >=1
        if (array_key_exists('capacity', $data) && $data['capacity'] !== '') {
            if (!is_int($data['capacity']) && !ctype_digit((string)$data['capacity'])) {
                $errors['capacity'][] = 'El campo capacity debe ser un entero.';
            } elseif ((int)$data['capacity'] < 1) {
                $errors['capacity'][] = 'El campo capacity debe ser al menos 1.';
            }
        }

        // room_type nullable string
        if (array_key_exists('room_type', $data) && $data['room_type'] !== '') {
            if (!is_string($data['room_type'])) {
                $errors['room_type'][] = 'El campo room_type debe ser una cadena de texto.';
            }
        }

        // price nullable numeric >= 0
        if (array_key_exists('price', $data) && $data['price'] !== '') {
            if (!is_numeric($data['price'])) {
                $errors['price'][] = 'El campo price debe ser un número.';
            } elseif ((float)$data['price'] < 0) {
                $errors['price'][] = 'El campo price debe ser al menos 0.';
            }
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException(json_encode($errors));
        }
    }
}
