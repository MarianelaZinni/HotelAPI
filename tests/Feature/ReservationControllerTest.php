<?php

namespace Tests\Feature;

use Tests\TestCase;
use Mockery;
use App\Services\ReservationService;

/**
 * Tests para ReservationController.
 *
 * Mockeamos el servicio ReservationService y lo registramos en el contenedor.
 * Seteamos API_KEY antes de parent::setUp() para que el middleware lo tenga disponible,
 * luego registramos las cabeceras a usar por las requests.
 */
class ReservationControllerTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('API_KEY=test_api_key');
        $_ENV['API_KEY'] = 'test_api_key';
        $_SERVER['API_KEY'] = 'test_api_key';

        parent::setUp();

        // 4) Cabeceras que se enviarán en las peticiones de prueba
        $this->withHeaders([
            'X-API-KEY' => 'test_api_key',
            'Accept' => 'application/json',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * POST /api/reservations cuando la creación es exitosa debe devolver 201 y el recurso creado.
     */
    public function test_store_returns_201_when_created()
    {
        $payload = [
            'room_id' => 11,
            'guest_name' => 'Mariana',
            'guest_email' => 'm@example.com',
            'guest_count' => 2,
            'check_in' => '2025-12-10',
            'check_out' => '2025-12-12',
        ];

        $created = (object) array_merge(['id' => 50], $payload);

        $serviceMock = Mockery::mock(ReservationService::class);
        $serviceMock->shouldReceive('store')->once()->with(Mockery::subset($payload))->andReturn($created);

        $this->app->instance(ReservationService::class, $serviceMock);

        $response = $this->postJson('/api/reservations', $payload);

        $response->assertStatus(201);
        $response->assertJsonFragment(['id' => 50, 'guest_name' => 'Mariana']);
    }

    /**
     * POST /api/reservations cuando hay solapamiento -> el servicio lanza RuntimeException('solapamiento_reserva')
     * y el controlador debe devolver 409 con el mensaje adecuado.
     */
    public function test_store_returns_409_on_overlap()
    {
        $payload = [
            'room_id' => 11,
            'guest_name' => 'Mariana',
            'guest_email' => 'm@example.com',
            'guest_count' => 2,
            'check_in' => '2025-12-10',
            'check_out' => '2025-12-12',
        ];

        $serviceMock = Mockery::mock(ReservationService::class);
        $serviceMock->shouldReceive('store')->once()->with(Mockery::subset($payload))->andThrow(new \RuntimeException('solapamiento_reserva'));

        $this->app->instance(ReservationService::class, $serviceMock);

        $response = $this->postJson('/api/reservations', $payload);

        $response->assertStatus(409);
        $response->assertJsonFragment(['message' => 'Reserva conflictiva para la habitación seleccionada en las fechas indicadas.']);
    }

    /**
     * GET /api/reservations/{id} -> debe devolver 200 con la reserva obtenida por el servicio.
     */
    public function test_show_returns_reservation()
    {
        $id = 3;

        $reservationData = (object)[
            'id' => $id,
            'room_id' => 4,
            'guest_name' => 'Ana',
            'guest_email' => 'ana@example.com',
            'guest_count' => 2,
            'check_in' => '2025-12-01 14:00:00',
            'check_out' => '2025-12-05 11:00:00'
        ];

        $serviceMock = Mockery::mock(ReservationService::class);
        $serviceMock->shouldReceive('show')->once()->with($id)->andReturn($reservationData);

        $this->app->instance(ReservationService::class, $serviceMock);

        $response = $this->getJson("/api/reservations/{$id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $id, 'guest_name' => 'Ana']);
    }

    /**
     * GET /api/reservations (index) -> el servicio devuelve una colección y el endpoint debe retornarla.
     */
    public function test_index_returns_filtered_reservations()
    {
        $reservations = collect([
            ['id' => 1, 'room_id' => 2, 'guest_name' => 'P1', 'check_in' => '2025-12-01', 'check_out' => '2025-12-03'],
            ['id' => 2, 'room_id' => 2, 'guest_name' => 'P2', 'check_in' => '2025-12-04', 'check_out' => '2025-12-06'],
        ]);

        $serviceMock = Mockery::mock(ReservationService::class);
        // index() puede recibir cualquier arreglo de filtros; devolvemos la colección
        $serviceMock->shouldReceive('index')->once()->andReturn($reservations);

        $this->app->instance(ReservationService::class, $serviceMock);

        $response = $this->getJson('/api/reservations?from=2025-12-01');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['guest_name' => 'P1']);
    }
}
