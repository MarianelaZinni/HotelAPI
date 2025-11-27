<?php

namespace App\Services;

use App\Repositories\ReservationRepositoryInterface;
use App\Repositories\RoomRepositoryInterface;
use Carbon\Carbon;
use RuntimeException;
use InvalidArgumentException;

/**
 * ReservationService
 *
 * Contiene la lógica de negocio relacionada con reservas:
 * - Validación de los datos de entrada
 * - Comprobación de existencia de la habitación usando RoomRepository->findOrFail()
 * - Comprobación de solapamiento usando ReservationRepository->existsOverlap()
 * - Creación de la reserva usando ReservationRepository->create()
 */
class ReservationService
{
    protected $reservations;
    protected $rooms;

    public function __construct(
        ReservationRepositoryInterface $reservations,
        RoomRepositoryInterface $rooms
    ) {
        $this->reservations = $reservations;
        $this->rooms = $rooms;
    }

    /**
     * Recupera una reserva por id incluyendo relaciones necesarias.
     */
    public function show(int $id)
    {
        return $this->reservations->findByIdWithRelations($id);
    }

    /**
     * Devuelve la lista filtrada de reservas según $filters.
     */
    public function index(array $filters)
    {
        return $this->reservations->getFiltered($filters);
    }

    /**
     * Crea una reserva si no hay solapamiento.
     *
     * - Valida los datos de entrada
     * - Comprueba existencia de la habitación usando el RoomRepository (findOrFail)
     * - Comprueba solapamiento usando ReservationRepository->existsOverlap
     * - Si no hay conflicto crea la reserva y la devuelve
     */
    public function store(array $data)
    {
        $this->validateData($data);

        $room = $this->rooms->findOrFail((int)$data['room_id']);

        // Normalizar/parsear fechas con Carbon y convertir al formato MySQL DATETIME
        try {
            $checkInDt = Carbon::parse($data['check_in']);
            $checkOutDt = Carbon::parse($data['check_out']);
        } catch (\Exception $e) {
            // aunque validateData ya chequea, defensivamente lanzamos InvalidArgumentException
            throw new InvalidArgumentException(json_encode([
                'check_in' => ['El campo check_in no es una fecha válida.'],
                'check_out' => ['El campo check_out no es una fecha válida.'],
            ]));
        }

        // Usamos strings en formato 'Y-m-d H:i:s' para persistir y para el chequeo de solapamiento
        $checkInStr = $checkInDt->format('Y-m-d H:i:s');
        $checkOutStr = $checkOutDt->format('Y-m-d H:i:s');

        $overlap = $this->reservations->existsOverlap($room->id, $checkInStr, $checkOutStr);

        if ($overlap) {
            throw new RuntimeException('solapamiento_reserva');
        }

        // Reescribimos en $data las fechas normalizadas para que el repositorio las inserte correctamente
        $data['check_in'] = $checkInStr;
        $data['check_out'] = $checkOutStr;

        return $this->reservations->create($data);
    }

    /**
     * Validación en PHP puro:
     * - required: room_id, guest_name, check_in, check_out
     * - tipos básicos: room_id integer, guest_count integer >=1 si está
     * - guest_email formato email si está
     * - check_in/check_out formatos de fecha válidos y check_out > check_in
     *
     * Lanza InvalidArgumentException con mensaje en caso de error.
     */
    protected function validateData(array $data): void
    {
        $errors = [];

        // Required
        foreach (['room_id', 'guest_name', 'check_in', 'check_out'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $errors[$field][] = 'El campo ' . $field . ' es obligatorio.';
            }
        }

        // room_id must be integer
        if (isset($data['room_id']) && !is_int($data['room_id']) && !ctype_digit((string)$data['room_id'])) {
            $errors['room_id'][] = 'El campo room_id debe ser un entero.';
        }

        // guest_name must be string
        if (isset($data['guest_name']) && !is_string($data['guest_name'])) {
            $errors['guest_name'][] = 'El campo guest_name debe ser una cadena de texto.';
        }

        // guest_email if present must be valid
        if (!empty($data['guest_email'])) {
            if (!filter_var($data['guest_email'], FILTER_VALIDATE_EMAIL)) {
                $errors['guest_email'][] = 'El campo guest_email debe ser un correo electrónico válido.';
            }
        }

        // guest_count if present must be integer >= 1
        if (isset($data['guest_count']) && $data['guest_count'] !== '') {
            if (!is_int($data['guest_count']) && !ctype_digit((string)$data['guest_count'])) {
                $errors['guest_count'][] = 'El campo guest_count debe ser un entero.';
            } elseif ((int)$data['guest_count'] < 1) {
                $errors['guest_count'][] = 'El campo guest_count debe ser al menos 1.';
            }
        }

        // Validar check_in / check_out con Carbon (acepta ISO8601 con 'Z')
        $checkInDt = null;
        $checkOutDt = null;

        if (isset($data['check_in'])) {
            try {
                $checkInDt = Carbon::parse((string)$data['check_in']);
            } catch (\Exception $e) {
                $errors['check_in'][] = 'El campo check_in no es una fecha válida.';
            }
        }

        if (isset($data['check_out'])) {
            try {
                $checkOutDt = Carbon::parse((string)$data['check_out']);
            } catch (\Exception $e) {
                $errors['check_out'][] = 'El campo check_out no es una fecha válida.';
            }
        }

        if ($checkInDt instanceof Carbon && $checkOutDt instanceof Carbon) {
            if ($checkOutDt->lte($checkInDt)) {
                $errors['check_out'][] = 'El campo check_out debe ser una fecha posterior a check_in.';
            }
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException(json_encode($errors));
        }
    }
}
