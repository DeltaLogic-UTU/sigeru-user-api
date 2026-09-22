# SiGeRU User API

API REST para gestionar usuarios, autenticación, solicitudes de acceso y recuperación de contraseñas del sistema SiGeRU.

## Tecnologías

- PHP 8.2
- Laravel 12
- MySQL
- JWT con `tymon/jwt-auth`
- Composer
- Pest/PHPUnit para pruebas

## Requisitos

- PHP 8.2 o superior
- Composer
- MySQL 8 o compatible
- Extensiones PHP necesarias para Laravel y MySQL

## Instalación

Desde la raíz del proyecto:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Configura `.env`:

```env
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigeru
DB_USERNAME=root
DB_PASSWORD=tu_contraseña

CACHE_STORE=file
MAIL_MAILER=log
```

`CACHE_STORE=file` evita depender de una tabla `cache` durante el desarrollo. Si se cambia a `database`, esa tabla debe existir.

Importa la estructura y los datos iniciales de la base de datos. Después limpia la configuración:

```powershell
php artisan config:clear
php artisan route:clear
```

Inicia la API:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

URL base:

```text
http://127.0.0.1:8000
```

## Estructura principal

```text
app/Http/Controllers/Api/
├── AuthController.php
├── PasswordRecoveryController.php
└── SolicitudAccesoController.php

app/Models/
├── User.php
├── Usuario.php
└── SolicitudAcceso.php

routes/api.php
```

Las rutas de `routes/api.php` reciben automáticamente el prefijo `/api`.

## Modelo de datos

### `usuarios`

Datos personales:

- `id_usuario`
- `nombre`
- `apellido`
- `email`
- `telefono`
- `estado`
- `tipo`
- `fecha_registro`

### `credenciales`

Datos de autenticación:

- `id_credenciales`
- `id_usuario`
- `cedula`
- `password`
- `ultimo_login`
- `token`

La contraseña se almacena con hash. La relación es:

```text
usuarios.id_usuario -> credenciales.id_usuario
```

### `solicitudes_acceso`

Solicitudes de nuevos usuarios:

- `id_solicitud`
- `nombre`
- `apellido`
- `cedula`
- `email`
- `telefono`
- `rol_solicitado`
- `estado`
- `fecha_solicitud`
- `fecha_resolucion`
- `id_admin_resuelve`

`estado` acepta `pendiente`, `aprobado` o `rechazado`.

`rol_solicitado` acepta `administrador`, `chofer`, `barrendero`, `operario` o `vecino`.

### `rec_password`

Solicitudes de recuperación:

- `id_solicitud`
- `id_usuario`
- `codigo`
- `estado`
- `fecha_solicitud`
- `fecha_expiracion`

El token se guarda con hash y solo puede usarse una vez.

## Autenticación JWT

El login utiliza la cédula, no el email:

```json
{
    "cedula": "99999999-9",
    "password": "Sigeru123"
}
```

Para rutas protegidas:

```text
Authorization: Bearer TU_ACCESS_TOKEN
Accept: application/json
```

En Postman se configura en **Authorization > Bearer Token**.

## Endpoints

### Iniciar sesión

```http
POST /api/auth/login
```

Body:

```json
{
    "cedula": "99999999-9",
    "password": "Sigeru123"
}
```

Respuesta:

```json
{
    "message": "Autenticación existosa",
    "data": {
        "access_token": "TOKEN_JWT",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

Las credenciales incorrectas devuelven `401`.

### Obtener el usuario autenticado

```http
GET /api/auth/me
```

Requiere JWT. Devuelve las credenciales y el registro relacionado de `usuarios`.

### Cerrar sesión

```http
POST /api/auth/logout
```

Requiere JWT e invalida el token actual.

### Renovar token

```http
POST /api/auth/refresh
```

Requiere JWT y devuelve un nuevo token.

## Solicitudes de acceso

### Crear solicitud

Ruta pública:

```http
POST /api/solicitudes
```

Body:

```json
{
    "nombre": "Juan",
    "apellido": "Peres",
    "cedula": "1234567-8",
    "email": "juan.perez@sigeru.test",
    "telefono": "099000123",
    "rol_solicitado": "chofer"
}
```

Devuelve `201 Created` y crea la solicitud con estado `pendiente`.

### Listar solicitudes pendientes

```http
GET /api/solicitudes
```

Requiere JWT y solo devuelve solicitudes pendientes.

### Aprobar o rechazar

```http
PUT /api/solicitudes/{id}/resolver
```

Requiere JWT.

Para aprobar:

```json
{
    "estado": "aprobado"
}
```

Para rechazar:

```json
{
    "estado": "rechazado"
}
```

Al aprobar, la API:

1. Verifica que la solicitud siga pendiente.
2. Verifica que tenga cédula.
3. Evita duplicados por cédula o email.
4. Crea el registro en `usuarios`.
5. Usa `rol_solicitado` como `tipo`.
6. Crea sus credenciales relacionadas.
7. Asigna inicialmente `Sigeru123`.
8. Guarda la contraseña con hash.
9. Marca la solicitud como aprobada.

Todo se ejecuta dentro de una transacción. Si se rechaza, no se crea ningún usuario. Una solicitud resuelta no puede procesarse nuevamente.

## Recuperación de contraseña

El flujo es personalizado porque el email está en `usuarios` y la contraseña en `credenciales`.

### Solicitar recuperación

```http
POST /api/auth/forgot-password
```

Body:

```json
{
    "email": "juan.perez@sigeru.test"
}
```

El sistema busca el email, cancela solicitudes pendientes anteriores, genera un token aleatorio, guarda su hash y establece una expiración de 60 minutos.

Durante el desarrollo, `.env` usa:

```env
MAIL_MAILER=log
```

Por eso el enlace se escribe en:

```text
storage/logs/laravel.log
```

### Restablecer contraseña

```http
POST /api/auth/reset-password
```

Body:

```json
{
    "email": "juan.perez@sigeru.test",
    "token": "TOKEN_DEL_LOG",
    "password": "Nueva123",
    "password_confirmation": "Nueva123"
}
```

El token debe existir, estar pendiente, pertenecer al email y no estar vencido. Al utilizarse cambia a `utilizado`.

## Ejemplo completo en Postman

1. Ejecuta `POST /api/auth/login`.
2. Copia `data.access_token`.
3. Configura ese token como Bearer Token.
4. Ejecuta `GET /api/auth/me`.
5. Crea una solicitud con `POST /api/solicitudes`.
6. Lista solicitudes con `GET /api/solicitudes`.
7. Aprueba o rechaza con `PUT /api/solicitudes/{id}/resolver`.
8. Si fue aprobada, inicia sesión con su cédula y `Sigeru123`.
9. Para recuperar una contraseña, ejecuta `forgot-password`, copia el token del log y llama a `reset-password`.

## Códigos de respuesta

| Código | Significado |
|---|---|
| `200` | Operación exitosa |
| `201` | Recurso creado |
| `400` | Token inválido o expirado |
| `401` | Falta autenticación o las credenciales son inválidas |
| `404` | Recurso o correo no encontrado |
| `422` | Error de validación o solicitud ya resuelta |
| `500` | Error interno de configuración o aplicación |

## Comandos útiles

```powershell
php artisan route:list --path=api
php artisan config:clear
php artisan cache:clear
php artisan migrate:status
php artisan test
vendor/bin/pint --dirty --format agent
```

## Seguridad

- No subas `.env` al repositorio.
- No compartas `APP_KEY` ni `JWT_SECRET`.
- Cambia `Sigeru123` en producción.
- Configura SMTP antes de enviar correos reales.
- No uses `APP_DEBUG=true` en producción.
- Las rutas de solicitudes usan JWT, pero todavía no tienen autorización específica por rol administrador.
- La respuesta de aprobación incluye la contraseña inicial para facilitar pruebas; en producción debería entregarse por un canal seguro o forzar un cambio de contraseña.
