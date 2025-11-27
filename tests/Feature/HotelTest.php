<?php

namespace Tests\Feature;

use Tests\TestCase;
use Mockery;
use App\Services\HotelService;

/**
 * Ttests para endpoints de Hotel.
 *
 * Mockean HotelService y lo registran en el contenedor para que el controller lo reciba.
 *
 * En setUp():
 * - putenv / $_ENV / $_SERVER se asignan ANTES de parent::setUp() para que env('API_KEY')
 *   esté disponible.
 */
class HotelTest extends TestCase
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
     * GET /api/hotels debe devolver la lista que retorna HotelService::index.
     *
     * Verificamos:
     * - HotelService::index es invocado una vez y su retorno se exporta como JSON.
     */
    public function test_index_returns_hotels()
    {
        $hotels = collect([
            ['id' => 1, 'name' => 'Hotel A', 'city' => 'Buenos Aires'],
            ['id' => 2, 'name' => 'Hotel B', 'city' => 'Córdoba'],
        ]);

        $serviceMock = Mockery::mock(HotelService::class);
        $serviceMock->shouldReceive('index')->once()->andReturn($hotels);

        $this->app->instance(HotelService::class, $serviceMock);

        $response = $this->getJson('/api/hotels');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Hotel A']);
        $response->assertJsonFragment(['city' => 'Córdoba']);
    }

    /**
     * POST /api/hotels debe llamar HotelService::store y devolver 201 con el recurso creado.
     *
     * Usamos withArgs() para comparar hotel payload de forma flexible y evitar
     * falsos negativos por diferencias de tipos (string vs int etc.).
     */
    public function test_store_creates_hotel()
    {
        $payload = [
            'name' => 'Hotel Nuevo',
            'city' => 'Rosario',
            'address' => 'Calle X 123',
            'phone' => '+54 11 0000',
            'email' => 'nuevo@hotel.com'
        ];

        $created = (object) array_merge(['id' => 10], $payload);

        $serviceMock = Mockery::mock(HotelService::class);
        $serviceMock->shouldReceive('store')->once()->withArgs(function ($receivedData) use ($payload) {
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
        })->andReturn($created);

        $this->app->instance(HotelService::class, $serviceMock);

        $response = $this->postJson('/api/hotels', $payload);

        $response->assertStatus(201);
        $response->assertJsonFragment(['id' => 10, 'name' => 'Hotel Nuevo']);
    }

    /**
     * POST /api/hotels con datos inválidos -> HotelService lanza InvalidArgumentException con JSON de errores.
     * El controller debe convertirlo a 422 con estructura {message, errors}.
     */
    public function test_store_returns_422_on_validation_error()
    {
        $payload = [
            // omitimos 'name' para provocar validación
            'city' => 'X'
        ];

        $errors = ['name' => ['El campo nombre es obligatorio.']];

        $serviceMock = Mockery::mock(HotelService::class);
        $serviceMock->shouldReceive('store')->once()->withArgs(function ($receivedData) use ($payload) {
            // recibimos el payload (puede contener otros campos), comprobamos sólo que tenga 'city'
            return is_array($receivedData) && array_key_exists('city', $receivedData);
        })->andThrow(new \InvalidArgumentException(json_encode($errors)));

        $this->app->instance(HotelService::class, $serviceMock);

        $response = $this->postJson('/api/hotels', $payload);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'La información proporcionada no es válida.']);
        $response->assertJsonFragment(['name' => ['El campo nombre es obligatorio.']]);
    }
}