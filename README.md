# SpecHub API REST en PHP

API RESTful en **PHP 8** que expone de forma remota la información de la base de datos `spechub`.
Implementa el **CRUD completo** del recurso principal
**dispositivos** (`GET`, `POST`, `PUT`, `DELETE`) y de **marcas**, con autenticación JWT para las
operaciones de escritura.

Sigue el diseño previo del proyecto (`docs/ARQUITECTURA-BACKEND.md`): **mismas rutas** y **mismo JSON**
que el backend Java, de modo que el front-end React funciona con cualquiera de los dos cambiando solo
`VITE_API_BASE_URL`.

## Cumplimiento de los requisitos de la actividad

| Requisito | Dónde se cumple |
|---|---|
| API sobre una tabla principal de la BD | `dispositivo` (+ `especificacion_dispositivo`, `imagen_dispositivo`, `comentario`) y `marca` |
| Servicios GET, POST (set), PUT y DELETE | Ver [tabla de endpoints](#endpoints) |
| Diseño de API REST | Recursos en plural, verbos HTTP, códigos de estado correctos (200/201/204/400/401/403/404/405/409), `Location` en altas, JSON, API sin estado (JWT) |
| Buenas prácticas de arquitectura | Capas Controller → Service → DAO, DTO/Mapper, validación, inyección de dependencias, excepciones de dominio, transacciones |
| Basada en el diseño previo | Rutas y forma del JSON idénticas a `DispositivoController.java` / `DispositivoDTO.java` |
| Prueba en Postman con evidencias | `postman/SpecHub-PHP-API.postman_collection.json` + [`docs/GUIA-EVIDENCIAS-POSTMAN.md`](docs/GUIA-EVIDENCIAS-POSTMAN.md) |

## Arquitectura

```
Cliente (Postman / React)
        │  HTTP + JSON
┌───────▼──────────────────────────────────────────────┐
│ public/index.php  (front controller)                  │
│   └─ Kernel: CORS · Router · manejo global de errores │
├───────────────────────────────────────────────────────┤
│ Middleware      AuthMiddleware (JWT, rol ADMIN)       │
├───────────────────────────────────────────────────────┤
│ Controller      HTTP ⇄ objetos: valida, delega, elige │
│                 el código de estado                   │
├───────────────────────────────────────────────────────┤
│ Service         reglas de negocio, transacciones,     │
│                 llaves foráneas, conflictos (409)     │
├───────────────────────────────────────────────────────┤
│ DAO             SQL con sentencias preparadas (PDO)   │
├───────────────────────────────────────────────────────┤
│ MySQL / MariaDB   base `spechub`  (docs/schema.sql)   │
└───────────────────────────────────────────────────────┘
   Transversales: Validation · Mapper · Exception · Core (Router, Request, Response, Container)
```

Cada capa solo conoce a la inmediatamente inferior: un controlador nunca toca un DAO ni SQL.
Las dependencias se resuelven por **inyección en el constructor** (`Core/Container.php`, autowiring).

```
backend-php/
├── public/
│   ├── index.php                 Front controller (único punto de entrada)
│   └── .htaccess                 Reescritura para Apache/XAMPP
├── routes/api.php                Tabla de rutas = contrato REST
├── src/
│   ├── bootstrap.php             Autoload PSR-4, .env, manejo de errores PHP
│   ├── Kernel.php                Composición: contenedor + rutas + errores + CORS
│   ├── Core/                     Router, Request, Response, Container, Database, Env, ErrorResponder
│   ├── Middleware/               AuthMiddleware, CorsMiddleware
│   ├── Controller/               Dispositivo, Marca, TipoDispositivo, Auth, Info
│   ├── Service/                  DispositivoService, MarcaService, AuthService, JwtService, ...
│   ├── DAO/                      DispositivoDAO, MarcaDAO, TipoDispositivoDAO, UsuarioAdminDAO
│   ├── Mapper/DispositivoMapper  Fila de BD → JSON "enriquecido" del front
│   ├── Validation/               Reglas de entrada (equivalentes a los DTO con Bean Validation)
│   └── Exception/                NotFound, Conflict, Validation, Unauthorized, ...
└── .env.
```

## Requisitos

- **PHP 8.1 o superior** con las extensiones `pdo_mysql` y `mbstring` (vienen activas en XAMPP).
- **MySQL 8 / MariaDB 10.5+** con la base `spechub` ya cargada (ver más abajo).
- No requiere Composer ni librerías externas.

## Instalación

**1. Base de datos.** Si aún no la tienes, carga el esquema y los datos de ejemplo del proyecto:

```bash
mysql -u root -p --default-character-set=utf8mb4 -e "CREATE DATABASE IF NOT EXISTS spechub CHARACTER SET utf8mb4"
mysql -u root -p --default-character-set=utf8mb4 spechub < ../docs/schema.sql
mysql -u root -p --default-character-set=utf8mb4 spechub < ../docs/seed_devices.sql
```

> Conserva `--default-character-set=utf8mb4`: sin él, las tildes se guardan mal ("cÃ¡mara") desde la consola.
> Si el backend Java ya creó las tablas, salta este paso: **comparten la misma base**.

**2. Configuración.**  ajusta .env usuario/clave de MySQL:



| Variable | Descripción |
|---|---|
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Conexión a MySQL (por defecto `127.0.0.1:3306`, `spechub`, `root`, sin clave) |
| `JWT_SECRET` | Mínimo 32 caracteres. Con el **mismo valor** que `spechub.jwt.secret` de Java, los tokens de ambos backends son intercambiables |
| `JWT_EXPIRATION_SECONDS` | Vigencia del token (86400 = 24 h) |
| `CORS_ALLOWED_ORIGINS` | Orígenes extra separados por coma (`localhost`/`127.0.0.1` ya están permitidos) |
| `APP_DEBUG` | `true` agrega detalle técnico a los errores 500 (solo en desarrollo) |

**3. Ejecutar.**

```bash
cd backend-php
php -S localhost:8000 -t public public/index.php
```

Verifica en el navegador: <http://localhost:8000/api> → `{"status":"ok","database":"ok",...}`

> **Con XAMPP / Apache:** copia `backend-php` dentro de `htdocs` y usa
> `http://localhost/backend-php/public/api/...` (`mod_rewrite` activo; el `.htaccess` ya está incluido).
> La API detecta sola la subcarpeta. En Postman, cambia la variable `baseUrl` de la colección.

> **Puertos:** Java usa el 8080 y PHP el 8000, así que pueden correr a la vez.

## Endpoints

Lectura **pública**. Escritura (`POST`/`PUT`/`DELETE`) exige `Authorization: Bearer <token>` con rol ADMIN.

| Método | Ruta | Descripción | Éxito | Auth |
|---|---|---|---|---|
| POST | `/api/auth/login` | Devuelve el JWT `{token, username, role}` | 200 | — |
| **GET** | `/api/devices` | Lista dispositivos. Filtros: `type`, `brandId`, `maxPrice`, `q` | 200 | — |
| **GET** | `/api/devices/{id}` | Detalle (marca, tipo, specs, imagen, comentarios, promedio) | 200 | — |
| **POST** | `/api/devices` | Crea un dispositivo con su ficha técnica | **201** + `Location` | ADMIN |
| **PUT** | `/api/devices/{id}` | Reemplaza el dispositivo y su ficha técnica | 200 | ADMIN |
| **DELETE** | `/api/devices/{id}` | Elimina (cascada a specs, imágenes y comentarios) | **204** | ADMIN |
| GET | `/api/brands` · `/api/brands/{id}` | Lista / detalle de marcas | 200 | — |
| POST | `/api/brands` | Crea marca (nombre único) | 201 | ADMIN |
| PUT | `/api/brands/{id}` | Actualiza marca | 200 | ADMIN |
| DELETE | `/api/brands/{id}` | Elimina marca (409 si tiene dispositivos) | 204 | ADMIN |
| GET | `/api/device-types` | Lista tipos (celular, portátil, tablet, smartwatch) | 200 | — |
| GET | `/api/` | Estado de la API y de la conexión a la BD | 200 | — |

### Ejemplo: crear un dispositivo

```bash
# 1) obtener token
curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'

# 2) crear
curl -i -X POST http://localhost:8000/api/devices \
  -H "Authorization: Bearer <TOKEN>" -H "Content-Type: application/json" \
  -d '{
    "name": "Galaxy S25 Ultra", "brandId": 1, "typeId": 1,
    "releaseDate": "2025-01-22", "price": 5299000,
    "shortDescription": "Buque insignia 2025", "review": "Reseña...", "imageTone": "phone-blue",
    "specs": { "RAM": "12 GB", "Almacenamiento": "256 GB" }
  }'
```

Respuesta `201 Created` con `Location: /api/devices/13`:

```json
{
  "id": 13, "name": "Galaxy S25 Ultra", "brandId": 1, "typeId": 1,
  "releaseDate": "2025-01-22", "price": 5299000.0,
  "shortDescription": "Buque insignia 2025", "review": "Reseña...", "imageTone": "phone-blue",
  "imageUrl": null,
  "specs": { "RAM": "12 GB", "Almacenamiento": "256 GB" },
  "brand": { "id": 1, "name": "Samsung", "country": "Corea del Sur" },
  "type":  { "id": 1, "name": "Celular", "slug": "celular" },
  "comments": [], "averageRating": null, "commentCount": 0
}
```

Campos del cuerpo (`POST` y `PUT`): `name` (obligatorio, ≤120), `brandId` y `typeId` (obligatorios, deben existir),
`releaseDate` (obligatorio, `AAAA-MM-DD` válida), `price` (obligatorio, ≥ 0), y opcionales `shortDescription` (≤200),
`review`, `imageTone` (≤40) y `specs` (objeto `{"Clave":"Valor"}`, máx. 50).
`PUT` es un **reemplazo completo**: se envían todos los campos y la ficha técnica se sustituye entera.

## Errores

Todos los errores usan la misma forma (la que ya lee el front-end en `apiFetch`, campo `mensaje`):

```json
{ "timestamp": "2026-09-20T20:37:55.301Z", "status": 400, "error": "Error de validación",
  "mensaje": "La petición contiene datos inválidos",
  "errores": { "name": "El nombre del dispositivo es obligatorio", "price": "El precio no puede ser negativo" } }
```

| Código | Cuándo |
|---|---|
| 400 | JSON mal formado o vacío, id/filtro con formato incorrecto, campos inválidos (`errores` por campo), `brandId`/`typeId` inexistentes |
| 401 | Falta el token, es inválido, fue manipulado o expiró |
| 403 | Token válido pero sin rol ADMIN |
| 404 | El dispositivo/marca no existe, o la ruta no existe |
| 405 | Método no admitido en esa ruta (incluye cabecera `Allow`) |
| 409 | Marca duplicada, o marca con dispositivos asociados |
| 503 / 500 | BD inaccesible / error interno (el detalle solo se muestra con `APP_DEBUG=true`) |

## Seguridad implementada

- **Sentencias preparadas reales** (`PDO::ATTR_EMULATE_PREPARES = false`): sin inyección SQL. Además se escapan `%` y `_` en la búsqueda `q`.
- **JWT HS256** con firma verificada en tiempo constante (`hash_equals`), algoritmo fijado (rechaza `alg: none`), expiración y rol.
- Contraseñas con **bcrypt** (`password_verify`) y verificación de relleno para no revelar qué usuarios existen.
- **CORS** restringido a `localhost` y a los orígenes configurados.
- Los errores inesperados se registran en el log del servidor y **no filtran** rutas ni SQL al cliente.
- Usuario de arranque: `admin` / `admin123` (viene en `docs/schema.sql`). **Cámbialo antes de producción.**


