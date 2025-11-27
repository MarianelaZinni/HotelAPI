# AGENTS.md — Guía para futuros agentes (humanos o IA) que trabajen en este repositorio

- Este documento es una guía práctica para trabajar en HotelAPI. Contiene contexto, prompts recomendados, áreas candidatas para que la IA proponga cambios, y advertencias sobre las partes que requieren revisión humana cuidadosa.

1) Contexto del proyecto
- Qué hace la API
  - HotelAPI es una API REST para gestionar hoteles, habitaciones y reservas. Soporta creación/consulta de hoteles, habitaciones y reservas controlando posibles solapamientos; la API está protegida por una API key (header: `X-API-KEY`).
  - Documentación disponible vía Swagger (L5‑Swagger) en `/api/documentation`.

- Stack técnico
  - Framework: Laravel 12
  - Lenguaje: PHP 8.2.12
  - Base de datos: MySQL (desarrollo orientado a XAMPP en Windows)
  - Documentación OpenAPI: l5-swagger (swagger-php / swagger-ui)
  - Testing: PHPUnit
  - Organización típica: 
		Los controladores se encuentran en `app/Http/Controllers`, los servicios en `app/Services`, los repositorios en `app/Repositories`, 
		los modelos en `app/Models` y las rutas en `routes/web.php` 

2) Información operativa crítica
- Se utiliza autenticacion via API_KEY; el header utilizado es: `X-API-KEY`, su valor se puede encontrar en la variable API_KEY del archivo .env y se hace uso de un 
  middleware (`App\Http\Middleware\ApiKeyMiddleware`) para autenticar

- Archivos importantes:
  - `routes/web.php` — ahi se encuentra la definición de endpoints, se utiliza el prefijo `api`
  - `app/Http/Middleware/ApiKeyMiddleware.php` — donde indicamos autenticación por API key
  - `app/OpenApi/SwaggerConfig.php` — anotaciones globales OpenAPI (por ejemplo, se le indica a swagger que envie el header X-API-KEY con el valor de la key en cada request)
  - `config/l5-swagger.php` — configuración de swagger
  - `app/Services/ReservationService.php` — aca se encuentra la lógica para gestionar reservas: se controla el solapamiento, se validan los datos recibidos y se hace la conversión de fechas
  - `app/Repositories/*` — abstracciones sobre la persistencia. El fin de estos archivos es poder realizar tests usando mocks sin tener que acceder a la base de datos
  - `app/Models/Reservation.php` — se encuentra el casts para las fechas
  - `public/vendor/l5-swagger/...` — assets/initializer (si fue publicado)
  - `storage/logs/laravel.log` — logs para depuración

3) Sugerencias para usar IA sobre este código
- Prompts iniciales recomendados para entender el proyecto
  - "Dame un resumen de las responsabilidades principales del proyecto a partir de la estructura del repositorio."
  - "Lista las rutas API disponibles y qué controladores/servicios usan."
  - "Encuentra áreas con deuda técnica o riesgos (p. ej. manejo de fechas, validaciones, concurrencia)."
  - "Genera casos de prueba unitarios para ReservationService enfocándote en validaciones y solapamientos."

- Prompts útiles cuando quieras que la IA proponga código
  - "Propone un refactor seguro para normalizar fechas en ReservationService usando Carbon y ajusta los tests unitarios correspondientes."
  - "Genera un diff que añada casting datetime en el modelo Reservation y que reformatee las fechas antes de persistir."
  - "Crea tests unitarios que mockeen el repositorio y comprueben la detección de solapamiento y la creación exitosa."

- Qué partes son buenas candidatas para que la IA genere o mejore
  - Nuevos endpoints CRUD (controladores + rutas + tests)
  - Validaciones y mensajes de error estandarizados (servicios / request objects)
  - Tests unitarios y de integración (mocks de repositorio)
  - Generación/actualización de documentación OpenAPI (anotaciones @OA) y config/l5-swagger.php
  - Añadir comentarios en el codigo para explicar funcionamietos.

- Qué partes requieren más cuidado humano y no delegar 100% a la IA
  - Lógica de solapamiento de reservas
  - Migraciones y cambios en esquema de BD: revisar impacto y plan de rollback
  - Consultas SQL y optimizaciones
  - Manejo de zonas horarias y conversiones de fechas


4) Convenciones del repositorio y recomendaciones
- Idioma de los comentarios
  - Preferencia actual: español en comentarios y mensajes de commit.
- Estructura estándar (resumen)
  - app/Http/Controllers — controladores
  - app/Services — lógica de negocio
  - app/Repositories — acceso a datos (interfaces + implementations)
  - app/Models — Eloquent models
  - app/OpenApi — anotaciones swagger
  - config — configuraciones (incluye l5-swagger.php)
  - routes — definiciones (aquí `routes/web.php` expone `api/*`)
  - public/vendor/l5-swagger — assets swagger (si fueron publicados)
  - storage/logs — logs
  - tests — PHPUnit tests (Unit / Feature)

- Buenas practicas de desarrollo recomendadas:
  - Mantener inmutabilidad de inputs en los servicios (o copiar antes de mutar)
  - Usar casts de Eloquent (`$casts`) para fechas
  - No hardcodear valores usar el archivo `.env`
  - Mensajes de commit claros/descriptivos.

5) Flujo de trabajo recomendado cuando uses IA para generar código
- Flujo general
  1. Pedirle a la IA un resumen del cambio propuesto y un diff.
  2. Revisá manualmente el diff: búsqueda datos hardcodeados, queries que puedan afectar datos.
  3. Aplicá el diff en una rama.
  4. Ejecutá tests locales: `php artisan test` (unit + feature)  para comprobar el correcto funcionamieto
  5. Ejecutá migraciones en una DB de desarrollo
  6. Manual QA: probar endpoints relevantes
  7. Pedir code review si es posible
  8. Mergear

6) Plantillas y prompts recomendados para distintos trabajos
- Prompt para "entender el proyecto" 
  - "Eres un desarrollador de software que tiene acceso al repositorio HotelAPI. Resumime: endpoints expuestos, archivos que contienen reglas de negocio críticas 
  y las dependencias externas relevantes (DB, swagger)."

- Prompt para "generar tests unitarios" 
  - "Generá tests unitarios con PHPUnit para `ReservationService::store`. Crea los mocks necesarios para `ReservationRepositoryInterface` y `RoomRepositoryInterface`. 
  Deben cubrir: validación fallida, solapamiento y creación exitosa."

7) Notas sobre debugging y herramientas
- Logs: `storage/logs/laravel.log` (revisar trazas)
- DB local con XAMPP: phpMyAdmin o MySQL CLI para revisar tablas (reservations, rooms)
- Swagger UI: `/api/documentation` (usar Authorize para inyectar `X-API-KEY`)

8) Checklist rápido para agentes IA antes de proponer un cambio
- ¿El cambio introduce secretos en el repo? -> NO
- ¿Los tests pasan localmente? -> SÍ
- ¿Hay migraciones? -> incluir rollback y documentar
- ¿Impacta reglas de negocio críticas (ej. solapamiento)? -> señalalo claramente y pide revisión humana
- ¿Se actualizó documentación (OpenAPI) si corresponde? -> SÍ