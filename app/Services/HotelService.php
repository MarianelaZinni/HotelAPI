<?php

namespace App\Services;

use App\Repositories\HotelRepositoryInterface;
use InvalidArgumentException;

/**
 * HotelService
 *
 * Responsable de la lógica relacionada con hoteles:
 * - Validación de datos
 * - Delegar persistencia/recuperación a HotelRepositoryInterface
 */
class HotelService
{
    protected $hotels;

    public function __construct(HotelRepositoryInterface $hotels)
    {
        $this->hotels = $hotels;
    }

    /**
     * Devuelve todos los hoteles.
     */
    public function index()
    {
        return $this->hotels->all();
    }

    /**
     * Valida y crea un nuevo hotel.
     */
    public function store(array $data)
    {
        $this->validateData($data);

        return $this->hotels->create($data);
    }

    /**
     * Validación en PHP para hotel:
     * - name: required|string
     * - city, address, phone: nullable|string
     * - email: nullable|email
     *
     * Lanza InvalidArgumentException con JSON de errores si falla.
     */
    protected function validateData(array $data): void
    {
        $errors = [];

        if (!isset($data['name']) || $data['name'] === '') {
            $errors['name'][] = 'El campo nombre es obligatorio.';
        } elseif (!is_string($data['name'])) {
            $errors['name'][] = 'El nombre debe ser una cadena de texto.';
        }

        if (array_key_exists('city', $data) && $data['city'] !== '') {
            if (!is_string($data['city'])) {
                $errors['city'][] = 'La ciudad debe ser una cadena de texto.';
            }
        }

        if (array_key_exists('address', $data) && $data['address'] !== '') {
            if (!is_string($data['address'])) {
                $errors['address'][] = 'La dirección debe ser una cadena de texto.';
            }
        }

        if (array_key_exists('phone', $data) && $data['phone'] !== '') {
            if (!is_string($data['phone'])) {
                $errors['phone'][] = 'El teléfono debe ser una cadena de texto.';
            }
        }

        if (array_key_exists('email', $data) && $data['email'] !== '') {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'][] = 'El email debe ser una dirección de correo válida.';
            }
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException(json_encode($errors));
        }
    }
}
