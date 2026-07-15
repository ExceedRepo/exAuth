# Authentication

> **Note:** This document describes the broader authenticator design. For the
> practical, supported day-to-day flow (login, register, logout, current user),
> use the helper functions shown in the [Beginner Setup Guide](EXAUTH_BEGINNER_SETUP.md) —
> `ex_logged_in()`, `ex_current_user()`, `ex_user_id()`, `ex_logout()`. The
> session-based login used by the web controllers is the fully wired path.

exAuth provides four authenticators — Session, AccessTokens, HmacSha256, and JWT — and a Chain mechanism that tries them sequentially. The `Authentication` class acts as a factory and multiplexer.

## Getting an Instance

The preferred way to get the authentication service:

```php
$auth = service('authentication');
$auth = service('authentication', 'session'); // default
$auth = service('authentication', 'jwt');
```

You can also use the helper:

```php
$auth = ex_auth();
```

## Authenticator Interface

Every authenticator implements `exAuth\Authentication\AuthenticatorInterface`:

```php
authenticate(array $credentials): bool
attempt(array $credentials): bool
check(): bool
logout(): void
getUser(): ?object
getError(): ?string
setUser(object $user): void
```

## Login Field Configuration

exAuth (like Shield and Myth-Auth) lets you decide whether users log in with
their email, their username, or either one. This is controlled in
`Config/exAuth.php`:

```php
public array $validFields         = ['email', 'username'];
public bool  $useEmailForLogin    = true;
public bool  $useUsernameForLogin = true;
```

`LoginController::loginPost()` reads these settings:

- If **both** are enabled, the submitted value is inspected with
  `FILTER_VALIDATE_EMAIL`; if it looks like an email the user is looked up by
  email, otherwise by username.
- If only one is enabled, only that field is used.

The default login view posts a single `login` field that accepts either value.
If you build a custom form, post the value as `login` (or `email` / `username`
directly — both are honored based on the config).

## Session Authenticator

The primary authenticator for web-based logins.

### Logging In

```php
$credentials = [
    'email'    => $this->request->getPost('email'),
    'password' => $this->request->getPost('password'),
];

if ($auth->attempt($credentials)) {
    // logged in
} else {
    $error = $auth->getError();
}
```

To enable "Remember Me":

```php
$credentials['remember'] = true;
$auth->attempt($credentials);
```

### Logging Out

```php
$auth->logout();
```

## AccessTokens Authenticator

Personal access tokens (API keys). **Fully wired and tested** — see the
**[Access Tokens & HMAC guide](EXAUTH_BEGINNER_TOKENS_HMAC_SETUP.md)**.

```php
// Create a token (raw value shown once; DB stores only its hash)
$token = $user->createAccessToken('My App', ['posts.read']);
echo $token->token;

// Protect a route
$routes->get('api/posts', 'Api\Posts::index', ['filter' => 'tokens']);
$routes->get('api/posts', 'Api\Posts::create', ['filter' => 'tokens:posts.write']);

// In the controller
$userId = ex_token_id();
$user   = ex_token_user();
```

## HmacSha256 Authenticator

Signed server-to-server requests — the secret never travels on the wire.
**Fully wired and tested** — see the
**[Access Tokens & HMAC guide](EXAUTH_BEGINNER_TOKENS_HMAC_SETUP.md)**.

```php
// Create a key pair (secret shown once)
$cred = $user->createHmacKey();
echo $cred->token;   // public key
echo $cred->secret;  // shared secret

// Client sends: Authorization: HMAC-SHA256 <key>:<hmac_sha256(body, secret)>
$routes->post('api/ingest', 'Api\Ingest::store', ['filter' => 'hmac']);

// In the controller
$userId = ex_hmac_id();
$user   = ex_hmac_user();
```

## JWT Authenticator

For stateless authentication using JSON Web Tokens. This path is **fully wired
and tested** — see the dedicated **[Beginner JWT Setup guide](EXAUTH_BEGINNER_JWT_SETUP.md)**
for the complete, working flow (issue token, protect routes, refresh).

Quick summary:

```php
// Issue a token (usually done for you by POST /api/auth/token)
$token = service('jwt')->generateToken($userId, ['email' => $email]);

// Verify a raw token
$jwt = service('jwt');
if ($jwt->verify($token)) {
    $userId = $jwt->getUserId();
}
```

Register the API endpoints (`/api/auth/token`, `/api/auth/refresh`,
`/api/auth/me`) in `app/Config/Routes.php`:

```php
service('auth')->jwtRoutes($routes);
```

Protect routes with the `jwt` filter and read the current user with
`ex_jwt_id()` / `ex_jwt_user()`.

## Chain Authentication

Tries multiple authenticators in order until one succeeds.

```php
$auth = service('authentication', 'chain');
$auth->setChain(['session', 'tokens', 'jwt']);
$auth->authenticate($request); // uses request context
```

Alternatively, use the ChainAuth filter on a route:

```php
$routes->get('api/profile', 'Profile::index', ['filter' => 'chain:session,tokens,jwt']);
```

## Determining Login Status

```php
if ($auth->check()) {
    $user = $auth->getUser();
}
```

The helper `ex_logged_in()` is shorthand:

```php
if (ex_logged_in()) { ... }
```

## Current User

```php
$user = ex_current_user();
$id   = ex_user_id();
```


