# Nexum Backend

API REST para la plataforma de portfolios profesionales **Nexum**. Construida con Laravel 11 y PostgreSQL, expone endpoints de autenticación, gestión de portfolio y administración de usuarios.

---

## Tabla de contenidos

- [Stack tecnológico](#stack-tecnológico)
- [Requisitos previos](#requisitos-previos)
- [Instalación desde cero](#instalación-desde-cero)
- [Configuración del entorno (.env)](#configuración-del-entorno-env)
- [Endpoints de la API](#endpoints-de-la-api)
- [Usuarios de prueba](#usuarios-de-prueba)
- [Correr los tests](#correr-los-tests)
- [Levantar el servidor local](#levantar-el-servidor-local)

---

## Stack tecnológico

| Componente | Versión | Uso |
|---|---|---|
| PHP | 8.2 | Lenguaje del servidor |
| Laravel | 11.x | Framework principal |
| PostgreSQL | 15.x | Base de datos |
| Laravel Sanctum | 4.x | Autenticación por tokens (API stateless) |
| Spatie Permission | 6.x | Roles y permisos (`admin`, `professional`) |
| Spatie Activitylog | 4.x | Auditoría de cambios en modelos |

**Drivers de infraestructura** (sin dependencias externas):

| Servicio | Driver |
|---|---|
| Sesiones | `database` |
| Cola de trabajos | `database` |
| Caché | `database` |
| Storage de imágenes | `local` (`storage/app/public`) |

---

## Requisitos previos

Antes de instalar el proyecto, asegurate de tener lo siguiente en tu sistema:

- **PHP 8.2** con las extensiones: `pdo_pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`
- **PostgreSQL 15** corriendo localmente (usuario `postgres` con acceso configurado)
- **Composer** (gestor de dependencias de PHP)
- **Git**

### Verificar versiones

```bash
php --version      # debe mostrar PHP 8.2.x
psql --version     # debe mostrar psql 15.x
composer --version # debe mostrar Composer 2.x
```

### Habilitar la extensión PDO para PostgreSQL

En tu `php.ini`, verificá que esta línea esté descomentada (sin el `;` inicial):

```ini
extension=pdo_pgsql
```

Después de editar, reiniciá el servidor PHP (Laragon, XAMPP, etc.).

---

## Instalación desde cero

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio> nexum-backend
cd nexum-backend
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Crear el archivo de entorno

```bash
cp .env.example .env
```

Editá el `.env` con los valores de tu entorno local (ver sección [Configuración del entorno](#configuración-del-entorno-env)).

### 4. Generar la clave de la aplicación

```bash
php artisan key:generate
```

### 5. Crear la base de datos en PostgreSQL

Conectate a PostgreSQL y creá la base de datos:

```sql
CREATE DATABASE nexum_db;
```

O desde la línea de comandos:

```bash
psql -U postgres -c "CREATE DATABASE nexum_db;"
```

### 6. Ejecutar migraciones y seeders

```bash
php artisan migrate --seed
```

Este comando crea todas las tablas y ejecuta los seeders: roles, usuario admin y 3 usuarios profesionales de prueba.

### 7. Crear el enlace simbólico del storage

```bash
php artisan storage:link
```

Necesario para que las imágenes subidas sean accesibles públicamente vía URL.

### 8. (Opcional) Publicar configs de paquetes

Si necesitás personalizar la configuración de Sanctum o Spatie, los archivos ya están publicados en `/config`:

```
config/sanctum.php
config/permission.php
config/activitylog.php
```

---

## Configuración del entorno (.env)

A continuación se listan todas las variables necesarias para el funcionamiento del proyecto:

```env
# ─── Aplicación ────────────────────────────────────────────────
APP_NAME=Nexum
APP_ENV=local
APP_KEY=                        # generado con php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

# ─── Base de datos (PostgreSQL) ────────────────────────────────
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nexum_db
DB_USERNAME=postgres
DB_PASSWORD=                    # tu contraseña de postgres

# ─── Drivers de infraestructura (sin Redis) ────────────────────
SESSION_DRIVER=database
SESSION_LIFETIME=120
QUEUE_CONNECTION=database
CACHE_STORE=database

# ─── Correo electrónico ────────────────────────────────────────
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io   # reemplazá con tu proveedor
MAIL_PORT=2525
MAIL_USERNAME=                       # tu usuario SMTP
MAIL_PASSWORD=                       # tu contraseña SMTP
MAIL_FROM_ADDRESS=noreply@nexum.app
MAIL_FROM_NAME="${APP_NAME}"

# ─── URLs del sistema ──────────────────────────────────────────
FRONTEND_URL=http://localhost:5173   # URL del frontend React
                                     # El link de reset de contraseña apunta a:
                                     # {FRONTEND_URL}/reset-password?token=...&email=...

# ─── Usuario administrador por defecto ─────────────────────────
ADMIN_PASSWORD=Admin1234!            # contraseña del usuario admin@nexun.com
```

### Recomendación para desarrollo

Para probar emails sin un servidor SMTP real, podés usar [Mailtrap](https://mailtrap.io) (plan gratuito) o configurar `MAIL_MAILER=log` para que los emails se escriban en `storage/logs/laravel.log`:

```env
MAIL_MAILER=log
```

---

## Endpoints de la API

**Base URL:** `http://localhost:8000/api/v1`

**Autenticación:** Los endpoints marcados con 🔒 requieren el header:
```
Authorization: Bearer {token}
```

Los endpoints marcados con 👑 requieren además el rol `admin`.

---

### Autenticación (`/auth`)

| Método | Ruta | Auth | Descripción |
|---|---|---|---|
| `POST` | `/auth/register` | — | Registra un nuevo usuario con rol `professional`. Envía email de verificación en cola. Devuelve 201 sin token (debe verificar email primero). |
| `GET` | `/auth/email/verify/{id}/{hash}` | URL firmada | Verifica el email usando el link recibido por correo. Devuelve 200 o 403 si el link es inválido. |
| `POST` | `/auth/email/resend` | — | Reenvía el email de verificación. Body: `{"email":"..."}`. Throttle: 6 req/min. Siempre responde 200. |
| `POST` | `/auth/login` | — | Autentica al usuario. Requiere email verificado e `is_active=true`. Throttle: 5 req/min por IP. Devuelve token Sanctum + datos. |
| `POST` | `/auth/logout` | 🔒 | Revoca el token actual del usuario autenticado. |
| `POST` | `/auth/forgot-password` | — | Envía email con link de reset apuntando al frontend React. Siempre responde 200. Throttle: 6 req/min. |
| `POST` | `/auth/reset-password` | — | Cambia la contraseña con el token del email. Revoca todos los tokens activos. Devuelve 400 si el token es inválido. |

**Ejemplo — Register:**
```json
{
  "first_name": "Juan",
  "last_name": "Pérez",
  "email": "juan@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

**Ejemplo — Login / Respuesta:**
```json
// Request
{ "email": "admin@nexun.com", "password": "Admin1234!" }

// Response 200
{
  "token": "1|abc123xyz...",
  "user": {
    "id": 1,
    "first_name": "Admin",
    "last_name": "Nexun",
    "email": "admin@nexun.com",
    "role": "admin"
  }
}
```

---

### Portfolio del usuario (`/portfolio`)

| Método | Ruta | Auth | Descripción |
|---|---|---|---|
| `GET` | `/portfolio` | 🔒 | Devuelve el portfolio del usuario autenticado. 404 si todavía no tiene portfolio. |
| `PUT` | `/portfolio` | 🔒 | Crea o actualiza el portfolio (idempotente). Solo campos de texto — JSON. Sanitiza con `strip_tags()`. |
| `POST` | `/portfolio/avatar` | 🔒 | Sube o reemplaza la foto de perfil. `multipart/form-data`. Elimina la imagen anterior automáticamente. |

**Ejemplo — PUT /portfolio (JSON):**
```json
{
  "first_name": "Juan",
  "last_name": "García",
  "profession": "Full Stack Developer",
  "biography": "Desarrollador con experiencia en Laravel y React.",
  "phone": "+34 600 000 000",
  "location": "Madrid, España",
  "linkedin_url": "https://www.linkedin.com/in/mi-perfil",
  "github_url": "https://github.com/mi-usuario"
}
```

**Ejemplo — POST /portfolio/avatar (form-data):**
```
avatar: [archivo .jpg/.jpeg/.png/.webp, máx 2MB]
```

> No agregar `Content-Type` manualmente — Postman lo genera automáticamente al usar form-data.

**Respuesta — GET / PUT / POST avatar:**
```json
{
  "data": {
    "id": 1,
    "user": {
      "id": 2,
      "first_name": "Juan",
      "last_name": "García",
      "email": "professional1@nexun.com"
    },
    "profession": "Full Stack Developer",
    "biography": "Desarrollador con experiencia en Laravel y React.",
    "phone": "+34 600 000 000",
    "location": "Madrid, España",
    "avatar_url": "http://localhost:8000/storage/avatars/AbCdEf123.jpg",
    "linkedin_url": "https://www.linkedin.com/in/mi-perfil",
    "github_url": "https://github.com/mi-usuario",
    "design_pattern": null,
    "global_privacy": "public",
    "views_count": 0,
    "created_at": "2026-03-30T17:56:46.000000Z",
    "updated_at": "2026-04-03T20:11:00.000000Z"
  }
}
```

---

### Administración (`/admin`)

> Todos requieren 🔒👑 (token de usuario con rol `admin`).

| Método | Ruta | Auth | Descripción |
|---|---|---|---|
| `PATCH` | `/admin/users/{id}/toggle-status` | 🔒👑 | Activa o desactiva un usuario. Al desactivar: revoca sus tokens y marca `deactivated_by_admin=true`. No aplica sobre la cuenta propia del admin (422). |
| `GET` | `/admin/activity-log` | 🔒👑 | Log de auditoría paginado. Params opcionales: `?user_id={id}&per_page={n}` (máx 100, default 20). |

**Respuesta — GET /admin/activity-log:**
```json
{
  "data": [
    {
      "id": 1,
      "log_name": "user",
      "description": "updated",
      "subject_type": "App\\Models\\User",
      "subject_id": 3,
      "causer": {
        "id": 1,
        "first_name": "Admin",
        "last_name": "Nexun",
        "email": "admin@nexun.com"
      },
      "properties": {
        "attributes": { "is_active": false },
        "old":        { "is_active": true }
      },
      "created_at": "2026-03-30T18:00:00.000000Z"
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "total": 1 }
}
```

---

### Códigos de respuesta

| Código | Significado |
|---|---|
| `200` | OK — operación exitosa |
| `201` | Created — recurso creado |
| `400` | Bad Request — token de reset inválido o expirado |
| `401` | Unauthenticated — token ausente o inválido |
| `403` | Forbidden — sin permiso (rol incorrecto, cuenta desactivada, email no verificado) |
| `404` | Not Found — recurso no encontrado |
| `422` | Unprocessable Entity — error de validación con detalle de campos |
| `429` | Too Many Requests — throttle alcanzado |

---

## Usuarios de prueba

Los siguientes usuarios se crean automáticamente al ejecutar los seeders:

### Administrador

| Campo | Valor |
|---|---|
| Email | `admin@nexun.com` |
| Contraseña | Valor de `ADMIN_PASSWORD` en `.env` (default: `Admin1234!`) |
| Rol | `admin` |
| Email verificado | Sí |

### Profesionales

| Email | Contraseña |
|---|---|
| `professional1@nexun.com` | `Admin1234!` |
| `professional2@nexun.com` | `Admin1234!` |
| `professional3@nexun.com` | `Admin1234!` |

Todos los profesionales tienen email verificado y cuenta activa.

---

## Correr los tests

```bash
# Todos los tests
php artisan test

# Con detalle
php artisan test --verbose

# Un archivo específico
php artisan test tests/Feature/ExampleTest.php
```

**Resultado esperado (Sprint 1):**
```
PASS  Tests\Unit\ExampleTest
✓ that true is true

PASS  Tests\Feature\ExampleTest
✓ the application returns a successful response

Tests:    2 passed (2 assertions)
Duration: ~1s
```

> Para aislar los tests de la BD de desarrollo, creá un archivo `.env.testing` con una base de datos separada (`nexum_db_test`) y ejecutá `php artisan test --env=testing`.

---

## Levantar el servidor local

### Servidor integrado de PHP (recomendado para desarrollo)

```bash
php artisan serve
```

Disponible en: `http://127.0.0.1:8000`

Con host y puerto personalizados:

```bash
php artisan serve --host=0.0.0.0 --port=8080
```

### Procesar la cola de emails

Los emails (verificación y reset de contraseña) se envían de forma asíncrona. Para procesarlos, ejecutá en una terminal separada:

```bash
php artisan queue:work
```

> Sin un worker activo los emails no se enviarán. Para desarrollo inmediato, podés usar `QUEUE_CONNECTION=sync` en `.env`.

---

### Comandos de referencia rápida

```bash
php artisan route:list                  # lista todas las rutas
php artisan route:list --path=api/v1    # filtra por prefijo
php artisan migrate:fresh --seed        # BD limpia con datos de prueba
php artisan storage:link                # crea el symlink public/storage
php artisan db:show                     # estado de la BD
php artisan config:clear                # limpia caché de configuración
php artisan route:clear                 # limpia caché de rutas
php artisan queue:work                  # procesa la cola de trabajos
```

---

## Colección de Postman

El archivo `nexum-api.postman_collection.json` en la raíz del proyecto contiene todos los endpoints listos para importar.

1. Postman → **Import** → seleccioná `nexum-api.postman_collection.json`
2. En **Variables de colección** configurá `base_url = http://127.0.0.1:8000`
3. Ejecutá **Login**, copiá el `token` de la respuesta y pegalo en la variable `{{token}}`

---

## Documentación adicional

- [`ARCHITECTURE.md`](./ARCHITECTURE.md) — Flujo de peticiones HTTP, relaciones entre modelos, autenticación con Sanctum y sistema de roles con Spatie.
