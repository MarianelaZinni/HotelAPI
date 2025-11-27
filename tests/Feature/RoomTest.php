<?php

namespace Tests\Feature;

use Tests\TestCase;
use Mockery;
use App\Services\RoomService;

/**
 * Tests para Room endpoints.
 */
class RoomTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('API_KEY=test_api_key');
        $_ENV['API_KEY'] = 'test_api_key';
        $_SERVER['API_KEY'] = 'test_api_key';

        parent::setUp();

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
     * GET /api/hotels/{hotelId}/rooms debe devolver la colección que retorna RoomService::index.
     */
    public function test_index_returns_rooms_for_hotel()
    {
        $hotelId = 5;
        $rooms = collect([
            ['id' => 1, 'name' => 'Habitación 1', 'capacity' => 2],
            ['id' => 2, 'name' => 'Habitación 2', 'capacity' => 3],
        ]);

        $serviceMock = Mockery::mock(RoomService::class);
        $serviceMock->shouldReceive('index')->once()->with($hotelId)->andReturn($rooms);

        $this->app->instance(RoomService::class, $serviceMock);

        $response = $this->getJson("/api/hotels/{$hotelId}/rooms");

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Habitación 1']);
    }

    /**
     * POST /api/hotels/{hotelId}/rooms debe llamar RoomService::store y devolver 201 con el recurso.
     */
    public function test_store_creates_room_for_hotel()
    {
        $hotelId = 7;
        $payload = [
            'name' => 'Suite 1',
            'capacity' => 2,
            'room_type' => 'suite',
            'price' => 150.00
        ];

        $createdRoom = (object) array_merge(['id' => 20], $payload);

        $serviceMock = Mockery::mock(RoomService::class);

        $serviceMock->shouldReceive('store')->once()->withArgs(function ($receivedHotelId, $receivedData) use ($hotelId, $payload) {
            if ((int)$receivedHotelId !== $hotelId) {
                return false;
            }

            if (!is_array($receivedData)) {
                return false;
            }

            foreach ($payload as $k => $v) {
                if (!array_key_exists($k, $receivedData)) {
                    return false;
                }
                if ((string)$receivedData[$k] !== (string)$v) {
                    return false;
                }
            }

            return true;
        })->andReturn($createdRoom);

        $this->app->instance(RoomService::class, $serviceMock);

        $response = $this->postJson("/api/hotels/{$hotelId}/rooms", $payload);

        $response->assertStatus(201);
        $response->assertJsonFragment(['id' => 20, 'name' => 'Suite 1']);
    }

    /**
     * POST /api/hotels/{hotelId}/rooms con datos inválidos debería devolver 422.
     */
    public function test_store_returns_422_on_validation_error()
    {
        $hotelId = 7;
        $payload = [
            'capacity' => 0,
        ];

        $serviceMock = Mockery::mock(RoomService::class);
        $errors = ['name' => ['El campo name es obligatorio.'], 'capacity' => ['El campo capacity debe ser al menos 1.']];
        $serviceMock->shouldReceive('store')->once()->with($hotelId, Mockery::subset($payload))->andThrow(new \InvalidArgumentException(json_encode($errors)));

        $this->app->instance(RoomService::class, $serviceMock);

        $response = $this->postJson("/api/hotels/{$hotelId}/rooms", $payload);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'La información proporcionada no es válida.']);
        $response->assertJsonFragment(['name' => ['El campo name es obligatorio.']]);
    }
}
