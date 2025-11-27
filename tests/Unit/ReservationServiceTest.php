<?php

namespace Tests\Unit;

use App\Services\ReservationService;
use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Unit tests para ReservationService.
 *
 * Estos tests prueban la lógica de negocio del servicio (especialmente
 * la detección de solapamiento) sin tocar la base de datos, mockeando
 * los repositorios que el servicio consume.
 */
class ReservationServiceTest extends PHPUnitTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test que verifica que cuando existe solapamiento el servicio lanza RuntimeException('solapamiento_reserva').
     */
    public function test_store_throws_on_overlap()
    {
        $reservationRepo = Mockery::mock('App\Repositories\ReservationRepositoryInterface');
        $roomRepo = Mockery::mock('App\Repositories\RoomRepositoryInterface');

        $room = (object) ['id' => 11];

        $roomRepo->shouldReceive('findOrFail')->once()->with(11)->andReturn($room);

        // Las fechas ahora se normalizan con Carbon a 'Y-m-d H:i:s'
        $reservationRepo->shouldReceive('existsOverlap')
            ->once()
            ->with(11, '2025-12-10 00:00:00', '2025-12-12 00:00:00')
            ->andReturn(true);

        $service = new ReservationService($reservationRepo, $roomRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('solapamiento_reserva');

        $service->store([
            'room_id' => 11,
            'guest_name' => 'X',
            'check_in' => '2025-12-10',
            'check_out' => '2025-12-12',
        ]);
    }

    /**
     * Test que verifica que cuando no hay solapamiento la reserva se crea correctamente
     * delegando la creación al repositorio.
     */
    public function test_store_creates_when_no_overlap()
    {
        $reservationRepo = Mockery::mock('App\Repositories\ReservationRepositoryInterface');
        $roomRepo = Mockery::mock('App\Repositories\RoomRepositoryInterface');

        $room = (object) ['id' => 11];
        $created = (object) ['id' => 50, 'room_id' => 11, 'guest_name' => 'X'];

        $roomRepo->shouldReceive('findOrFail')->once()->with(11)->andReturn($room);

        // Esperamos que existsOverlap reciba las fechas ya formateadas 'Y-m-d H:i:s'
        $reservationRepo->shouldReceive('existsOverlap')
            ->once()
            ->with(11, '2025-12-10 00:00:00', '2025-12-12 00:00:00')
            ->andReturn(false);

        $reservationRepo->shouldReceive('create')->once()->with(Mockery::subset([
            'room_id' => 11, 'guest_name' => 'X'
        ]))->andReturn($created);

        $service = new ReservationService($reservationRepo, $roomRepo);

        $result = $service->store([
            'room_id' => 11,
            'guest_name' => 'X',
            'check_in' => '2025-12-10',
            'check_out' => '2025-12-12',
        ]);

        $this->assertEquals(50, $result->id);
        $this->assertEquals(11, $result->room_id);
    }
}
