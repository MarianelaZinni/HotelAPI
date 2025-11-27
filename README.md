# HotelAPI — README

- Proyecto: API para la gestión de reservas de hoteles 
- Stack utilizado: PHP 8.2.12 - Laravel 12
- Base de datos: MySQL
- Autenticación en las rutas API: API Key enviada en el header `X-API-KEY`.
- Documentación: Swagger (L5‑Swagger) en /api/documentation
- Tests: PHPUnit

Requisitos previos

- Sistema operativo:  Windows
- XAMPP (Apache + MySQL + PHP)
- Composer (gestor de dependencias PHP)
- Git

Para levantar el entorno de forma manual:

1) Clonar el repositorio: git clone https://github.com/MarianelaZinni/HotelAPI.git
2) Acceder a la carpeta donde fue clonado: cd HotelAPI
3) Instalar dependencias PHP: composer install
4) Copiar el contenido del archivo .env.example en .env (o renombrar) y editar los valores de:
	- Conexion de base de datos:
			DB_CONNECTION=mysql
			DB_HOST=127.0.0.1
			DB_PORT=3306
			DB_DATABASE=hotelapi
			DB_USERNAME=root
			DB_PASSWORD=  #en xampp por defecto es vacio, por seguridad podes crear un password via phpMyAdmin o por consola para el usuario root y colocarlo aca
			
	- APP_NAME="HotelAPI"
	- APP_KEY= app_key_de_prueba # para generar una nueva key se debe ejecutar: php artisan key:generate
	- APP_URL=http://127.0.0.1:8000
	- Configurar la API key que usa el middleware para autenticacion: 
		API_KEY=api_key_de_prueba # podes generar una nueva 
5) Iniciar XAMPP: arrancar Apache y MySQL desde el panel de XAMPP.
6) Crear la base de datos en MySQL (via phpMyAdmin o por consola): Nombre sugerido: hotelapi, si se crea con otro nombre actualizar la variable DB_DATABASE en el archivo .env
7) Levantar el servidor de desarrollo de Laravel: php artisan serve --host=127.0.0.1 --port=8000
8) Ejecutar migraciones: php artisan migrate

9) Para ejecutar los tests se debe correr el comando: php artisan test, este comando corre todos los tests, si se quieren ejecutar solo los tests unitarios se debe correr:
php artisan test --testsuite=Unit

10) Para acceder a la documentacion mediante Swagger se debe abrir en el navegador: 
http://127.0.0.1:8000/api/documentation

11) Se usa el header: X-API-KEY y el valor es el que esta indicado en la variable API_KEY del archivo .env (api_key_de_prueba)

12) Para probar los endpoints:
	- Desde la consola se debe correr: curl.exe -i -H "X-API-KEY: api_key_de_prueba" "http://127.0.0.1:8000/api/reservations/1" -> En este ejemplo estamos intentando 
		obtener la reserva con id = 1.

	- Desde Swagger UI:
		* Click en "Authorize" para poder pegar la API_KEY
		* Luego click en "Try it out" de las operaciones para que Swagger incluya la cabecera en las peticiones, completar/modificar los valores de ejemplo y ejecutar para
		obtener la respuesta.
		
Notas y consideraciones de desarrollo: 
- Las rutas API que reciben peticiones desde Swagger han sido exentas del chequeo CSRF mediante withoutMiddleware(VerifyCsrfToken::class) en las rutas POST correspondientes. Aun así, las rutas están protegidas por la API key.
- El servicio de reservas normaliza fechas entrantes a formato MySQL 'Y-m-d H:i:s' usando Carbon antes de persistir.
