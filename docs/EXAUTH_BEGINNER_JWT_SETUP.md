# exAuth JWT Setup — from Zero to a Working Token API

This beginner guide takes you from a normal exAuth install to a working
**JWT (JSON Web Token) API**: issue a token on login, protect API routes, read
the current user, and refresh the token — all verified end-to-end.

> Versions: **exAuth v1.3.0+**, CodeIgniter **4.7**, PHP **8.2**, `firebase/php-jwt` **v7**.
> Do the normal setup first: see **[EXAUTH_BEGINNER_SETUP.md](EXAUTH_BEGINNER_SETUP.md)**.

---

## 0. When should I use JWT?

Use JWT for **stateless APIs** — mobile apps, SPAs (React/Vue), or other servers
calling your API. The client logs in once, receives a token, and sends that token
on every request (`Authorization: Bearer <token>`). No server-side session needed.

If you only build a normal website with login pages, the session flow from the
main setup guide is enough — you don't need JWT.

---

## 1. Prerequisites

- exAuth already installed and working (`composer require exceed/exauth`, `exauth:setup`, `migrate --all`).
- You can register/login via the browser (from the main guide).

---

## 2. Set a JWT secret in `.env` (REQUIRED)

JWT tokens are signed with a secret key. For the default `HS256` algorithm the
key **must be at least 32 characters** (256 bits) — shorter keys are rejected by
the underlying library.

Generate a strong one:

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Put it in `.env`:

```dotenv
JWT_SECRET = 'paste-the-64-character-hex-string-here'
```

> If you skip this, exAuth falls back to your app `encryption.key`, and if that
> is also empty, to an **insecure built-in default** — fine for a quick local
> try, but never for production.

---

## 3. (Optional) Customize JWT settings

Defaults work out of the box. To change the algorithm, token lifetime, or
issuer, create `app/Config/AuthJWT.php`:

```php
<?php

namespace Config;

use exAuth\Config\AuthJWT as BaseAuthJWT;

class AuthJWT extends BaseAuthJWT
{
    public int    $timeToLive = 3600;   // token lifetime in seconds (1 hour)
    public string $algorithm  = 'HS256';
    // public string $issuer   = 'https://your-app.com';
}
```

---

## 4. Register the JWT routes

The JWT API routes are **opt-in**. Add one line to `app/Config/Routes.php`,
right after the existing `service('auth')->routes($routes);`:

```php
service('auth')->routes($routes);      // existing (web auth routes)
service('auth')->jwtRoutes($routes);   // add this (JWT API routes)
```

This registers three endpoints (default prefix `api/auth`):

| Method | Path | Purpose | Protected |
|--------|------|---------|-----------|
| POST | `/api/auth/token`   | Log in with credentials, get a token | no |
| POST | `/api/auth/refresh` | Exchange a valid token for a fresh one | needs token |
| GET  | `/api/auth/me`      | Return the current user | yes (`jwt` filter) |

> Want a different prefix? `service('auth')->jwtRoutes($routes, ['prefix' => 'api/v1']);`

Confirm they exist:

```bash
php spark routes | grep api/auth
```

---

## 5. Get a token (login)

Send email **or** username + password (exAuth honors your `validFields` config).

```bash
curl -X POST https://your-app.com/api/auth/token \
  -H "Content-Type: application/json" \
  -d '{"login":"john@example.com","password":"secret123"}'
```

Response:

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

You can also post form fields (`login`, `password`) instead of JSON, or use
`email` / `username` keys explicitly.

---

## 6. Call a protected endpoint

Send the token in the `Authorization` header:

```bash
curl https://your-app.com/api/auth/me \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

Response:

```json
{ "id": 1, "email": "john@example.com", "username": "johndoe" }
```

Without a valid token you get `401`:

```json
{ "error": "No JWT token provided" }
```

---

## 7. Protect your own API routes

Add the `jwt` filter (auto-registered — no config needed) to any route:

```php
// Single route
$routes->get('api/profile', 'Api\Profile::show', ['filter' => 'jwt']);

// A whole group
$routes->group('api', ['filter' => 'jwt'], static function ($routes) {
    $routes->get('posts', 'Api\Posts::index');
    $routes->post('posts', 'Api\Posts::create');
});
```

If the token is missing, invalid, or expired, the filter returns `401` with a
JSON error before your controller runs.

---

## 8. Read the current user in your controller

Inside a JWT-protected controller, get the authenticated user:

```php
<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class Profile extends BaseController
{
    public function show()
    {
        $userId = ex_jwt_id();      // int|null — id from the verified token
        $user   = ex_jwt_user();    // User entity|null

        return $this->response->setJSON([
            'id'       => $user->id,
            'email'    => $user->email,
            'username' => $user->username,
            'is_admin' => $user->inGroup('admin'),   // RBAC still works
        ]);
    }
}
```

| Helper | Returns |
|--------|---------|
| `ex_jwt_id()`   | User id from the current verified token, or `null` |
| `ex_jwt_user()` | The `User` entity for the current token, or `null` |

> Under the hood the `jwt` filter verifies the token against the shared
> `service('jwt')` instance; `ex_jwt_id()` reads the verified subject from it.

You can also combine JWT with RBAC filters:

```php
$routes->get('api/admin/stats', 'Api\Admin::stats', ['filter' => 'jwt']);
// then check $user->can('...') / $user->inGroup('...') inside the controller
```

---

## 9. Refresh a token

Before a token expires, exchange it for a new one:

```bash
curl -X POST https://your-app.com/api/auth/refresh \
  -H "Authorization: Bearer <current-valid-token>"
```

Response: a new `token` with a fresh expiry. An expired token cannot be
refreshed — the user must log in again.

---

## 10. How it works (quick tour)

- `exAuth\Config\AuthJWT` — secret, algorithm, TTL, issuer.
- `exAuth\Authentication\Authenticators\JWT` — `generateToken()`, `verify()`,
  `refresh()` (wraps `firebase/php-jwt`).
- `exAuth\Controllers\JWTController` — the `token`, `refresh`, `me` actions.
- `exAuth\Filters\JWTAuth` (alias `jwt`) — verifies the Bearer token, returns 401 otherwise.
- `service('jwt')` — shared authenticator instance used by the filter and helpers.

---

## 11. Troubleshooting

| Symptom | Cause & Fix |
|---------|-------------|
| `RuntimeException: AuthJWT secretKey must be at least 32 characters` | Your `JWT_SECRET` is too short. Use a 32+ character key (see §2). |
| `401 { "error": "No JWT token provided" }` | Missing/blank `Authorization: Bearer <token>` header. |
| `401 { "error": "JWT signature is invalid" }` | Token signed with a different secret, or it was modified. |
| `401 { "error": "JWT token has expired" }` | Token is past `expires_in`. Get a new one via `/token` (or `/refresh` before expiry). |
| `404` on `/api/auth/token` | You forgot `service('auth')->jwtRoutes($routes);` in `Routes.php`. |
| `401` on `/token` with correct password | Check the account is `active = 1` and not banned/suspended. |
| Token works but `ex_jwt_user()` is null | The route must use the `jwt` filter so the token is verified first. |

---

## 12. Quick reference

```bash
# 1. Secret (once)
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'   # put into .env as JWT_SECRET

# 2. Routes (app/Config/Routes.php)
#    service('auth')->jwtRoutes($routes);

# 3. Login -> token
curl -X POST https://your-app.com/api/auth/token \
  -H "Content-Type: application/json" \
  -d '{"login":"john@example.com","password":"secret123"}'

# 4. Use token
curl https://your-app.com/api/auth/me -H "Authorization: Bearer <token>"
```

Done 🎉 — you now have a working, tested JWT API on top of exAuth.
