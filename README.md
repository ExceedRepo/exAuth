# exAuth — Extended Authentication for CodeIgniter 4

[![](https://github.com/exauth/exauth/workflows/PHPUnit/badge.svg)](https://github.com/exauth/exauth/actions/workflows/phpunit.yml)
[![](https://github.com/exauth/exauth/workflows/PHPStan/badge.svg)](https://github.com/exauth/exauth/actions/workflows/phpstan.yml)
[![](https://github.com/exauth/exauth/workflows/StructArmed/badge.svg)](https://github.com/exauth/exauth/actions/workflows/structarmed.yml)
[![Coverage Status](https://coveralls.io/repos/github/exauth/exauth/badge.svg?branch=main)](https://coveralls.io/github/exauth/exauth?branch=main)

exAuth is an authentication and authorization library for CodeIgniter 4, created for developers who want the modern multi-authentication power of **CodeIgniter Shield** (Session, Tokens, HMAC, JWT, ChainAuth) but prefer the simplicity of **Myth/Auth**'s approach — where groups and permissions are managed **in the database**, not hardcoded in config files.

Instead of editing a PHP config file every time you need a new role or permission, exAuth stores everything in database tables (`auth_groups_users`, `auth_permissions_users`) so you can manage them dynamically at runtime, just like Myth/Auth.

## Project Notice

exAuth was built as a learning/analysis project bringing together the best of CodeIgniter Shield and Myth/Auth.

**Shield** is the official, maintained authentication library for CodeIgniter 4.
**Myth/Auth** is the predecessor of Shield, now archived.

exAuth is not an official package. For production, the recommended library is [CodeIgniter Shield](https://github.com/codeigniter4/shield).

## Requirements

- PHP 8.2+
- CodeIgniter 4.3+

## Features

- **4 Authentication Methods**: Session, AccessTokens, HmacSha256, JWT
- **Chain Authentication** — tries multiple authenticators in sequence until one succeeds
- **Database-backed Groups & Permissions** (simple, Myth-Auth style)
- **Flat RBAC** per NIST standards
- **Wildcard Permission Matching** (e.g. `admin.*`)
- **Remember-me** persistent login
- **Magic Link** passwordless login via email
- **2FA-ready** — action-based post-authentication system
- **Email-based account verification**
- **User Activation/Banning** (Activatable, Bannable traits)
- **All views** for login, registration, forgot password flows
- **CLI commands** for easy setup and management
- **Debug Toolbar** integration
- **22-language support** structure (English + Indonesian built-in)

## Installation

### Composer

```shell
> composer require exauth/exauth
```

### Manual

Clone or download the repo and add the namespace to **app/Config/Autoload.php**:

```php
$psr4 = [
    'exAuth' => APPPATH . 'ThirdParty/exauth/src',
];
```

## Configuration

> **New to exAuth?** Follow the step-by-step [Beginner Setup Guide](docs/EXAUTH_BEGINNER_SETUP.md) — it
> takes you from zero to a working login/register/logout flow (and RBAC).
> Building an API? See the [Beginner JWT Setup guide](docs/EXAUTH_BEGINNER_JWT_SETUP.md).

The fastest way to configure everything is the setup command:

```shell
php spark exauth:setup
```

This publishes config, registers the `exAuth` helper, adds the auth routes,
adjusts CSRF settings, and runs migrations. To do it manually, see the
[Beginner Setup Guide](docs/EXAUTH_BEGINNER_SETUP.md#5b-manual-setup-only-if-the-command-fails).

## Overview

When installed, exAuth provides basic authentication: user registration,
login/logout, forgotten password, magic-link login, and route protection
via filters.

Routes are registered by adding this line to **app/Config/Routes.php**:

```php
service('auth')->routes($routes);
```

### Views

Default views live in `src/Views` and are based on Bootstrap 5. To customize
them, copy the files into your app's `Views` directory and adjust the
`view(...)` calls, or override the paths in `Config/exAuth.php`.

### Login field (email / username / both)

Like Shield and Myth-Auth, exAuth lets you choose which field users log in with.
Configure it in **Config/exAuth.php**:

```php
public array $validFields         = ['email', 'username'];
public bool  $useEmailForLogin    = true;
public bool  $useUsernameForLogin = true;
```

- Both `true` (default): the login form accepts **either** an email or a
  username in a single field. The controller auto-detects which one was entered.
- Only email: set `$useUsernameForLogin = false`.
- Only username: set `$useEmailForLogin = false`.

The login view submits a single `login` input; `LoginController::loginPost()`
reads the config and resolves the user accordingly.

## Services

**auth**

Provides access to the exAuth facade. Its main job is registering routes:

```php
// app/Config/Routes.php
service('auth')->routes($routes);
```

For login state and the current user, use the helper functions below
(`ex_logged_in()`, `ex_current_user()`, etc.).

## Helper Functions

exAuth comes with its own helper. Load it with `helper('exAuth');`.

> **Hint**: Add `'exAuth'` to the `$helpers` property of **BaseController.php** to have it globally available. The auth filters all pre-load this helper on filtered routes.

**ex_auth()**

Returns the authentication service instance (factory).

```php
ex_auth()
```

**ex_logged_in()**

Checks if any user is logged in. Returns `true` or `false`.

```php
ex_logged_in()
```

**ex_current_user()**

Returns the User entity for the current logged in user, or `null`.

```php
ex_current_user()
```

**ex_user_id()**

Returns the current user's integer ID, or `null`.

```php
ex_user_id()
```

**ex_logout()**

Logs out the current user.

```php
ex_logout()
```

## Users

exAuth uses CodeIgniter Entities for the User object. User accounts live in the
`users` table (email, username, password, active, status, etc.).

Assign a user to a group by inserting into the `auth_groups_users` table (the
group name is stored as text), or use the CLI:

```bash
php spark exauth:user addgroup -n johndoe -g admin
```

## Restricting by Route

### Filter Aliases

Filters are auto-registered via the `Registrar` pattern. You do **not** need to add them to `Config/Filters.php` manually.

Available filter aliases:

```php
'session'    => Session-based auth (redirect to /login if not logged in)
'tokens'     => Bearer token auth (returns 401 JSON on failure)
'hmac'       => HMAC signature auth (returns 401 JSON on failure)
'jwt'        => JWT Bearer token auth (returns 401 JSON on failure)
'chain'      => Tries session, tokens, jwt, hmac in sequence
'group'      => Checks group membership
'permission' => Checks permissions
```

### Global Restrictions

Restrict by URI pattern in **app/Config/Filters.php**:

```php
public $filters = [
    'login' => ['before' => ['account/*']],
];
```

Or globally:

```php
public $globals = [
    'before' => [
        'honeypot',
        'login',
        ...
    ],
];
```

### Single Route

```php
$routes->get('admin/users', 'UserController::index', ['filter' => 'permission:users.manage']);
$routes->get('admin/users', 'UserController::index', ['filter' => 'group:admin,superadmin']);
```

### Route Groups

```php
$routes->group('admin', ['filter' => 'group:admin,superadmin'], function($routes) {
    ...
});
```

### Chain Authentication Filter

Use the chain filter to try multiple authenticators in sequence:

```php
$routes->get('api/profile', 'Profile::index', ['filter' => 'chain:session,tokens,jwt']);
```

## Customization

See the [Extending](docs/extending.md) documentation.

## Credits

Built from the best of two worlds: the **Shield Foundation** (CodeIgniter Shield) and the **Myth-Auth** community.
