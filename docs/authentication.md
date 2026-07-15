# Authentication

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

For API clients using bearer tokens.

```php
$auth = service('authentication', 'tokens');
$token  = $request->getHeaderLine('Authorization'); // Bearer <token>

$auth->authenticate(['token' => $token]);
```

Tokens are generated via the user entity:

```php
$token = $user->generateAccessToken('My App');
```

## HmacSha256 Authenticator

For server-to-server API communication using HMAC signatures.

```php
$auth = service('authentication', 'hmac');
$signature = $request->getHeaderLine('X-Signature');
$timestamp = $request->getHeaderLine('X-Timestamp');

$auth->authenticate([
    'signature' => $signature,
    'timestamp' => $timestamp,
    'content'   => file_get_contents('php://input'),
]);
```

## JWT Authenticator

For stateless authentication using JSON Web Tokens.

```php
$auth = service('authentication', 'jwt');
$token = $request->getHeaderLine('Authorization'); // Bearer <token>

$auth->authenticate(['token' => $token]);
```

JWT token generation:

```php
$token = $auth->generateToken($user);
```

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

## Password Validation

Password validators are configured in `Config/exAuth.php`. The `strong_password` validation rule can be used:

```php
$validation->setRules([
    'password' => 'required|strong_password',
]);
```

To be able to use this rule, add the following to `app/Config/Validation.php`:

```php
public $ruleSets = [
    \CodeIgniter\Validation\Rules::class,
    \CodeIgniter\Validation\FormatRules::class,
    \CodeIgniter\Validation\FileRules::class,
    \CodeIgniter\Validation\CreditCardRules::class,
    \exAuth\Authentication\Passwords\ValidationRules::class,
];
```
