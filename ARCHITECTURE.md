# Nexum Backend — Arquitectura Sprint 1

## Índice

1. [Flujo completo de una petición HTTP](#1-flujo-completo-de-una-petición-http)
2. [Ejemplos concretos por Historia de Usuario](#2-ejemplos-concretos-por-historia-de-usuario)
3. [Relaciones entre modelos](#3-relaciones-entre-modelos)
4. [Autenticación con Sanctum](#4-autenticación-con-sanctum)
5. [Sistema de roles con Spatie Permission](#5-sistema-de-roles-con-spatie-permission)

---

## 1. Flujo completo de una petición HTTP

Cada request que llega al servidor recorre exactamente estas capas, en este orden:

```
Cliente HTTP
    │
    ▼
bootstrap/app.php         ← configura la aplicación, registra middleware globales y aliases
    │
    ▼
routes/api.php            ← empareja la URL y el método HTTP con un controlador
    │
    ▼
Middleware Stack          ← se ejecuta antes de llegar al controlador
    │  ├── auth:sanctum   → verifica el Bearer token, inyecta $request->user()
    │  ├── role:admin     → CheckRole::class, verifica que el usuario tenga el rol requerido
    │  ├── throttle:login → RateLimiter, bloquea si supera el límite de intentos
    │  └── signed         → verifica que la URL no fue manipulada (links de verificación)
    │
    ▼
FormRequest               ← valida y sanitiza el body ANTES de llegar al método del controlador
    │  ├── authorize()    → verifica permisos adicionales (retorna true en todos los casos del sprint)
    │  ├── prepareForValidation() → sanitización XSS con strip_tags()
    │  └── rules()        → reglas de validación; si falla devuelve 422 automáticamente
    │
    ▼
Controller                ← lógica de negocio; nunca retorna vistas Blade
    │  ├── Lee datos del FormRequest validado
    │  ├── Interactúa con uno o más Modelos
    │  └── Construye la respuesta
    │
    ▼
Model / Eloquent          ← representa una tabla; encapsula la lógica de datos
    │  ├── Casts          → convierte tipos automáticamente (boolean, datetime, hashed)
    │  ├── Relaciones     → hasOne, belongsTo, etc.
    │  ├── LogsActivity   → trait de Spatie que registra cambios en activity_log
    │  └── HasRoles       → trait de Spatie que conecta con la tabla model_has_roles
    │
    ▼
API Resource              ← transforma el modelo en el JSON exacto que ve el cliente
    │  └── toArray()      → define qué campos exponer y con qué nombres
    │
    ▼
JsonResponse              ← response()->json([...], $statusCode)
    │
    ▼
Cliente HTTP
```

### Ejemplo visual: `PUT /api/v1/profile`

```
PUT /api/v1/profile
  Authorization: Bearer abc123token
  Body: { "profession": "Dev", "bio": "...", "github_url": "..." }

1. routes/api.php
   └── Route::put('/', [ProfileController::class, 'update'])
       └── middleware: auth:sanctum

2. auth:sanctum middleware
   └── Busca el token "abc123token" en personal_access_tokens
   └── Carga el User correspondiente → disponible en $request->user()

3. ProfileRequest
   └── prepareForValidation() → strip_tags en todos los campos de texto
   └── rules() → valida bio máx 1000 chars, github_url debe contener "github.com"
   └── Si falla → 422 Unprocessable Entity con detalle de errores

4. ProfileController::update()
   └── Profile::updateOrCreate(['user_id' => $request->user()->id], $request->validated())
   └── Si es nuevo → Spatie Activitylog registra evento "created" en activity_log
   └── Si existe → Spatie Activitylog registra solo los campos que cambiaron ("updated")

5. ProfileResource::toArray()
   └── Retorna: id, user{id, first_name, last_name, email}, profession, bio,
               avatar_path, linkedin_url, github_url, created_at, updated_at

6. return new ProfileResource($profile)
   └── HTTP 200 con Content-Type: application/json
```

---

## 2. Ejemplos concretos por Historia de Usuario

### HU-4 — Roles y control de acceso

| Archivo | Responsabilidad |
|---|---|
| `database/seeders/RoleSeeder.php` | Crea los roles `admin` y `professional` en la tabla `roles` |
| `database/seeders/AdminUserSeeder.php` | Crea `admin@portfolio.test` y le asigna el rol `admin` |
| `app/Http/Middleware/CheckRole.php` | Recibe el rol como parámetro (`role:admin`), consulta Spatie, devuelve 403 si no coincide |
| `bootstrap/app.php` | Registra el alias `'role' => CheckRole::class` y carga `routes/api.php` |
| `app/Models/User.php` | Trait `HasRoles` habilita `$user->assignRole()`, `$user->hasRole()`, `$user->getRoleNames()` |

**Flujo de autorización por rol:**
```
Request con Bearer token
    → auth:sanctum carga $user
    → role:admin invoca CheckRole::handle()
        → $request->user()->hasAnyRole(['admin'])
        → true  → continúa al controlador
        → false → return response()->json(['message' => 'Forbidden.'], 403)
```

---

### HU-1 — Registro y verificación de email

| Archivo | Responsabilidad |
|---|---|
| `app/Http/Requests/RegisterRequest.php` | Valida first_name, last_name, email único, password con confirmación |
| `app/Http/Controllers/Api/V1/AuthController.php` → `register()` | Crea el User, asigna rol `professional`, dispara notificación de verificación |
| `app/Notifications/VerifyEmailQueued.php` | Extiende `VerifyEmail` de Laravel + implementa `ShouldQueue` para envío asíncrono |
| `app/Models/User.php` → `sendEmailVerificationNotification()` | Override que usa `VerifyEmailQueued` en lugar de la notificación síncrona default |
| `routes/api.php` → `verification.verify` | Ruta nombrada que Laravel usa para construir el link firmado en el email |
| `AuthController::verifyEmail()` | Verifica el hash SHA1 del email, llama `markEmailAsVerified()`, dispara evento `Verified` |

**Por qué la ruta se llama `verification.verify`:**
Laravel construye el link del email buscando esa ruta por nombre. Al nombrar nuestra ruta API con ese nombre, el link del email apunta directamente a nuestro endpoint en lugar de a una vista Blade.

---

### HU-2 y HU-3 — Login, logout y recuperación de contraseña

| Archivo | Responsabilidad |
|---|---|
| `app/Providers/AppServiceProvider.php` | Define el `RateLimiter` con nombre `login` (5 req/min por IP) y personaliza la URL de reset |
| `AuthController::login()` | Valida credenciales con `Hash::check()`, verifica `is_active` y `hasVerifiedEmail()`, crea token Sanctum |
| `AuthController::logout()` | `$request->user()->currentAccessToken()->delete()` — revoca solo el token en uso |
| `app/Notifications/ResetPasswordQueued.php` | Extiende `ResetPassword` + `ShouldQueue` |
| `app/Models/User.php` → `sendPasswordResetNotification()` | Override que usa `ResetPasswordQueued` |
| `AppServiceProvider` → `ResetPassword::createUrlUsing()` | Cambia el destino del link a `{FRONTEND_URL}/reset-password?token=...&email=...` |
| `AuthController::resetPassword()` | Usa `Password::reset()` (Password Broker de Laravel), revoca todos los tokens con `$user->tokens()->delete()` |

---

### HU-5 — Desactivación de cuentas (dos escenarios)

El campo `deactivated_by_admin` en la tabla `users` es la clave que distingue ambos casos:

| Escenario | `is_active` | `deactivated_by_admin` | Puede reactivarse |
|---|---|---|---|
| Admin desactiva | `false` | `true` | Solo el admin vía `toggle-status` |
| Usuario se autodesactiva | `false` | `false` | El propio usuario vía `/profile/reactivate` |
| Cuenta activa | `true` | `false` | N/A |

| Archivo | Responsabilidad |
|---|---|
| `AdminUserController::toggleStatus()` | Si activo → desactiva y pone `deactivated_by_admin=true`. Si inactivo → reactiva. Siempre revoca tokens al desactivar |
| `ProfileController::deactivate()` | Pone `is_active=false` (sin tocar `deactivated_by_admin`). Pone portfolio en `private`. Revoca tokens |
| `ProfileController::reactivate()` | Sin auth. Verifica credenciales + que `deactivated_by_admin=false`. Reactiva y restaura `global_privacy=public` |
| `AuthController::login()` | Lee `deactivated_by_admin` para elegir el mensaje 403 apropiado |

---

### HU-6 — Auditoría con Spatie Activitylog

El trait `LogsActivity` intercepta automáticamente los eventos Eloquent `created` y `updated`.

| Archivo | Responsabilidad |
|---|---|
| `app/Models/User.php` → `getActivitylogOptions()` | Define log name `user`, audita: first_name, last_name, email, is_active, deactivated_by_admin |
| `app/Models/Portfolio.php` → `getActivitylogOptions()` | Define log name `portfolio`, audita: profession, bio, linkedin_url, github_url |
| `ActivityLogController::index()` | Consulta `Activity::with('causer')`, filtra por `user_id` si se proporciona, pagina resultados |

**Qué guarda Spatie en cada registro:**
```json
{
  "log_name": "user",
  "description": "updated",
  "subject_type": "App\\Models\\User",
  "subject_id": 3,
  "causer_type": "App\\Models\\User",
  "causer_id": 1,
  "properties": {
    "attributes": { "is_active": false, "deactivated_by_admin": true },
    "old":        { "is_active": true,  "deactivated_by_admin": false }
  }
}
```

`logOnlyDirty()` garantiza que solo se registran los campos que realmente cambiaron.
`dontSubmitEmptyLogs()` evita registros vacíos si un `save()` no modifica campos auditados.

---

### HU-7 y HU-8 — Perfil e integración de redes profesionales

| Archivo | Responsabilidad |
|---|---|
| `app/Http/Requests/ProfileRequest.php` → `prepareForValidation()` | Aplica `strip_tags()` sobre profession, bio, linkedin_url, github_url antes de validar |
| `ProfileRequest::rules()` | bio máx 1000 chars; linkedin_url y github_url validan dominio con `parse_url()` + closure |
| `ProfileController::show()` | Carga `$request->user()->profile` con eager loading del `user`, retorna `ProfileResource` |
| `ProfileController::update()` | `Profile::updateOrCreate(['user_id' => ...], $validated)` — idempotente, no duplica perfiles |
| `app/Http/Resources/ProfileResource.php` | Expone: id, user{}, profession, bio, avatar_path, linkedin_url, github_url, timestamps |

---

## 3. Relaciones entre modelos

```
┌─────────────────────────────────────────────────────────┐
│                        User                              │
│  id, first_name, last_name, email, password              │
│  is_active, deactivated_by_admin, storage_used           │
└──────────┬────────────────┬────────────────┬────────────┘
           │ hasOne         │ hasOne         │ (Spatie)
           ▼                ▼                ▼
    ┌──────────────┐  ┌──────────────┐  model_has_roles
    │   Profile    │  │  Portfolio   │       │
    │  user_id FK  │  │  user_id FK  │       ▼
    │  profession  │  │  profession  │    ┌──────┐
    │  bio         │  │  biography   │    │ Role │
    │  avatar_path │  │  phone       │    │admin │
    │  linkedin_url│  │  location    │    │prof. │
    │  github_url  │  │  global_priv │    └──────┘
    └──────────────┘  │  views_count │
                      └──────┬───────┘
                             │ hasMany
                             ▼
                      ┌─────────────┐
                      │ SocialLink  │
                      │portfolio_id │
                      │platform_name│
                      │url          │
                      └─────────────┘
```

### Notas sobre el diseño

- **User ↔ Profile (1:1):** `Profile` es la identidad pública del profesional en Sprint 1. Tiene `user_id` con índice `unique`, garantizando la relación 1:1 a nivel de base de datos.

- **User ↔ Portfolio (1:1):** `Portfolio` es la estructura de presentación completa (diseño, privacidad, vistas). Sus features más avanzadas (design_pattern, global_privacy, views_count) se activan en sprints futuros.

- **Portfolio ↔ SocialLink (1:N):** Un portfolio puede tener múltiples links sociales. En Sprint 1 usamos `linkedin_url` y `github_url` directamente en `Profile` para simplicidad; `SocialLink` está preparada para sprints futuros con plataformas adicionales.

- **User ↔ Role (N:M vía Spatie):** La tabla pivote `model_has_roles` conecta users con roles. Spatie soporta múltiples roles por usuario, pero en este sistema cada usuario tiene exactamente uno: `admin` o `professional`.

### Acceso a relaciones en código

```php
// Desde un User
$user->profile;           // Profile|null — datos públicos del profesional
$user->portfolio;         // Portfolio|null — estructura del portfolio
$user->getRoleNames();    // Collection ['professional']
$user->hasRole('admin');  // bool

// Desde un Portfolio
$portfolio->user;         // User
$portfolio->socialLinks;  // Collection de SocialLink

// Desde un Profile
$profile->user;           // User
```

---

## 4. Autenticación con Sanctum

Sanctum implementa autenticación stateless mediante tokens opacos almacenados en la tabla `personal_access_tokens`.

### Flujo completo de autenticación

```
┌─────────────────────────────────────────────────────────────────┐
│  PASO 1 — REGISTRO                                               │
│                                                                  │
│  POST /api/v1/auth/register                                      │
│  → User::create([...])                                           │
│  → $user->assignRole('professional')                             │
│  → $user->notify(new VerifyEmailQueued)  ← encolado             │
│  ← 201 { user: {...} }  // sin token todavía                    │
└─────────────────────────────────────────────────────────────────┘
                          │
                          ▼ (el worker de cola procesa el email)
┌─────────────────────────────────────────────────────────────────┐
│  PASO 2 — VERIFICACIÓN DE EMAIL                                  │
│                                                                  │
│  GET /api/v1/auth/email/verify/{id}/{hash}?expires=...&signature=│
│  → Middleware `signed` valida que la URL no fue alterada         │
│  → hash_equals(sha1($user->email), $hash) verifica identidad     │
│  → $user->markEmailAsVerified()  → email_verified_at = now()    │
│  → event(new Verified($user))                                    │
│  ← 200 { message: "Email verified successfully." }              │
└─────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│  PASO 3 — LOGIN                                                  │
│                                                                  │
│  POST /api/v1/auth/login                                         │
│  → Throttle: 5 intentos/min por IP (cache en tabla cache)        │
│  → Hash::check($password, $user->password)                       │
│  → Verifica $user->is_active === true                            │
│  → Verifica $user->hasVerifiedEmail()                            │
│  → $user->createToken('api-token')->plainTextToken               │
│     └── Inserta en personal_access_tokens:                       │
│         { tokenable_id: 5, name: 'api-token',                   │
│           token: hash_sha256(plain_token), ... }                 │
│  ← 200 { token: "1|abc...xyz", user: { id, name, role } }       │
└─────────────────────────────────────────────────────────────────┘
                          │
                          ▼ (el cliente guarda el token)
┌─────────────────────────────────────────────────────────────────┐
│  PASO 4 — PETICIÓN AUTENTICADA                                   │
│                                                                  │
│  GET /api/v1/profile                                             │
│  Authorization: Bearer 1|abc...xyz                               │
│                                                                  │
│  → Middleware auth:sanctum:                                      │
│     1. Extrae "1|abc...xyz" del header                           │
│     2. Separa el ID (1) del token en texto plano (abc...xyz)     │
│     3. Busca el registro con id=1 en personal_access_tokens      │
│     4. Compara hash_sha256(abc...xyz) con la columna `token`     │
│     5. Carga el User con tokenable_id                            │
│     6. Inyecta el usuario en $request->user()                    │
│  → Controlador recibe $request->user() ya hidratado              │
│  ← 200 ProfileResource                                           │
└─────────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│  PASO 5 — LOGOUT                                                 │
│                                                                  │
│  POST /api/v1/auth/logout                                        │
│  Authorization: Bearer 1|abc...xyz                               │
│                                                                  │
│  → $request->user()->currentAccessToken()->delete()              │
│     └── DELETE FROM personal_access_tokens WHERE id = 1          │
│  ← 200 { message: "Logged out successfully." }                   │
│                                                                  │
│  El token queda inválido de inmediato. Próximas peticiones        │
│  con ese token reciben 401 Unauthenticated.                      │
└─────────────────────────────────────────────────────────────────┘
```

### Estructura del token Sanctum

El token que recibe el cliente tiene el formato `{id}|{plaintext}`:

```
1|V8Kz2mX9qRpTnLwJcHdYsAbFuGeOiPvN...
│  └─ texto plano (nunca guardado en BD)
└─ ID del registro en personal_access_tokens
```

En la base de datos se guarda solo el hash SHA-256 del texto plano. Si la BD es comprometida, los tokens en texto plano no se pueden recuperar.

### Por qué no usamos cookies de sesión

El frontend es React (SPA desacoplada). Las cookies de sesión requieren mismo dominio o configuración CORS especial con `withCredentials`. Los Bearer tokens son más simples para SPAs: el cliente los guarda en memoria o localStorage y los envía explícitamente en cada request.

---

## 5. Sistema de roles con Spatie Permission

### Tablas involucradas

```
roles                          permissions
─────────────────              ──────────────────────
id | name  | guard_name        id | name | guard_name
───┼───────┼───────────        ───┼──────┼───────────
1  | admin | web               (vacía en Sprint 1)
2  | prof. | web

model_has_roles
───────────────────────────────────────
role_id | model_type           | model_id
────────┼─────────────────────┼─────────
1       | App\Models\User      | 1        ← admin
2       | App\Models\User      | 2        ← ana.garcia
2       | App\Models\User      | 3        ← carlos.mendez
2       | App\Models\User      | 4        ← sofia.romero
```

### Flujo de verificación de rol en una petición

```
PATCH /api/v1/admin/users/3/toggle-status
Authorization: Bearer {admin_token}

1. auth:sanctum
   └── Carga User con id=1 (admin)

2. role:admin  →  CheckRole::handle($request, $next, 'admin')
   └── $request->user()->hasAnyRole(['admin'])
       └── Spatie consulta model_has_roles:
           SELECT * FROM model_has_roles
           WHERE model_type = 'App\Models\User'
             AND model_id   = 1
             AND role_id IN (SELECT id FROM roles WHERE name = 'admin')
       └── Encuentra registro → retorna true
   └── Llama $next($request) → continúa al controlador

3. AdminUserController::toggleStatus($user)
   └── Route Model Binding carga User con id=3 automáticamente
   └── Lógica de negocio
```

### Cómo se asigna el rol al registrarse

```php
// AuthController::register()
$user = User::create([...]);
$user->assignRole('professional');
// Inserta en model_has_roles: { role_id: 2, model_type: 'App\Models\User', model_id: $user->id }
```

### Guard `web` vs `api`

Spatie usa el concepto de "guard" para separar contextos de autenticación. Por defecto usa el guard `web`. Sanctum autentica por el guard `sanctum` internamente pero cuando consultamos roles usamos el guard `web` porque así están creados los roles en el seeder.

Si en el futuro se necesitan permisos granulares (ej: `edit-own-profile`, `view-analytics`), Spatie permite crearlos con `Permission::create(['name' => '...'])` y asignarlos a roles o directamente a usuarios, sin cambiar la arquitectura existente.

### Métodos disponibles en el modelo User (via HasRoles)

```php
$user->assignRole('professional');          // asigna rol
$user->syncRoles(['admin']);                // reemplaza todos los roles
$user->hasRole('admin');                    // bool
$user->hasAnyRole(['admin', 'professional']); // bool
$user->getRoleNames();                      // Collection<string>
```

---

*Documento generado para Sprint 1. Actualizar en cada sprint con los nuevos módulos.*
