<?php

namespace App\OpenApi;

/**
 * @OA\Info(
 *     title="Hotel API",
 *     version="1.0.0",
 *     description="API para gestionar hoteles, habitaciones y reservas. Protegida por API Key (X-API-KEY)."
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="ApiKeyAuth",
 *     type="apiKey",
 *     in="header",
 *     name="X-API-KEY"
 * )
 */
class SwaggerConfig
{

}
