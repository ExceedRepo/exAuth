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

### Upgrading

Check the [Changes Docs](docs/changes.md) for upgrade steps between versions.

## Configuration

Once installed, perform the following setup:

1. **app/Config/Email.php** — verify **fromName** and **fromEmail** are set (used for password reset emails, etc.).

2. **app/Config/Validation.php** — add the following to the **ruleSets** array:

   ```php
   \exAuth\Authentication\Passwords\ValidationRules::class,
   ```

3. Ensure your database is configured correctly, then run migrations:

   ```shell
   > php spark migrate -all
   ```

> Note: This library uses your application's cache settings to reduce database lookups. Use a cache engine other than `dummy` for optimal performance. The `GroupModel` and `PermissionModel` handle caching and invalidation automatically.

## Overview

When first installed, exAuth provides all of the basic authentication services: user registration, login/logout, and forgotten password flows.

**"Remember Me"** is disabled by default. Enable it by setting `$allowRemembering` to `true` in **Config/exAuth.php**.

### Routes

Routes are defined in **Config/Routes.php**. This file is automatically located by CodeIgniter. To customize, copy the file to **app/Config**, update the namespace, and make changes there. You may also use the `$reservedRoutes` property of **Config/exAuth** to redirect internal route names.

### Views

Default views are based on Bootstrap 4. Override any view by editing **Config/exAuth.php** and changing the appropriate value in the `$views` variable:

```php
public $views = [
    'login'    => 'exAuth\Views\login',
    'register' => 'exAuth\Views\register',
    'forgot'   => 'exAuth\Views\forgot',
    'reset'    => 'exAuth\Views\reset',
    'emailForgot' => 'exAuth\Views\emails\forgot',
];
```

## Services

**authentication**

Provides access to the authentication library. Default is "session" authenticator.

```php
$authenticate = service('authentication');
```

You can specify the library as the first argument:

```php
$authenticate = service('authentication', 'jwt');
```

**authorization**

Provides access to the authorization library (groups and permissions).

```php
$authorize = service('authorization');
```

**passwords**

Provides direct access to the password validation system. This expandable system supports many of NIST's latest Digital Identity guidelines. Comes with a dictionary of over 620,000 common/leaked passwords.

```php
$passwords = service('passwords');
```

Use the `strong_password` validation rule by adding it to **app/Config/Validation.php**:

```php
public $ruleSets = [
    \CodeIgniter\Validation\Rules::class,
    \CodeIgniter\Validation\FormatRules::class,
    \CodeIgniter\Validation\FileRules::class,
    \CodeIgniter\Validation\CreditCardRules::class,
    \exAuth\Authentication\Passwords\ValidationRules::class,
];
```

Now you can use `strong_password` in validation rules:

```php
$validation->setRules([
    'username' => 'required',
    'password' => 'required|strong_password',
]);
```

## Helper Functions

exAuth comes with its own helper. Load it with `helper('exAuth');`.

> **Hint**: Add `'exAuth'` to the `$helpers` property of **BaseController.php** to have it globally available. The auth filters all pre-load this helper on filtered routes.

**auth()**

Returns the authentication service instance.

```php
auth()
```

**logged_in()**

Checks if any user is logged in. Returns `true` or `false`.

```php
logged_in()
```

**user()**

Returns the User entity for the current logged in user, or `null`.

```php
user()
```

**user_id()**

Returns the current user's integer ID, or `null`.

```php
user_id()
```

**in_groups()**

Ensures the current user is in at least one of the passed groups. Accepts group IDs or names, as a single item or array.

```php
in_groups('admin')
in_groups(['admin', 'editor'])
```

**has_permission()**

Ensures the current user has at least one of the passed permissions. Supports wildcard matching.

```php
has_permission('users.create')
has_permission('admin.*')
```

## Users

exAuth uses CodeIgniter Entities for the User object. This class provides automatic password hashing, ban/unban utility methods, password reset hash generation, and more.

The **UserModel** can automatically assign a role during user creation. Pass the group name to the `withGroup()` method prior to `insert()` or `save()`:

```php
$user = $userModel
    ->withGroup('guests')
    ->insert($data);
```

User registration handles this automatically, looking to the `$defaultGroup` setting in **Config/exAuth.php** for the group name.

## Toolbar

Add the Collector to **app/Config/Toolbar.php**:

```php
public $collectors = [
    \CodeIgniter\Debug\Toolbar\Collectors\Timers::class,
    \CodeIgniter\Debug\Toolbar\Collectors\Database::class,
    ...
    \exAuth\Collectors\Auth::class,
];
```

## Restricting by Route

### Filter Aliases

Add to **app/Config/Filters.php**:

```php
'login'      => \exAuth\Filters\LoginFilter::class,
'role'       => \exAuth\Filters\RoleFilter::class,
'permission' => \exAuth\Filters\PermissionFilter::class,
```

Additional exAuth filters:

```php
'session'    => \exAuth\Filters\SessionAuth::class,
'tokens'     => \exAuth\Filters\TokenAuth::class,
'hmac'       => \exAuth\Filters\HmacAuth::class,
'jwt'        => \exAuth\Filters\JWTAuth::class,
'chain'      => \exAuth\Filters\ChainAuth::class,
'group'      => \exAuth\Filters\GroupFilter::class,
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
$routes->get('admin/users', 'UserController::index', ['filter' => 'role:admin,superadmin']);
```

### Route Groups

```php
$routes->group('admin', ['filter' => 'role:admin,superadmin'], function($routes) {
    ...
});
```

### Chain Authentication Filter

Use the ChainAuth filter to try multiple authenticators in sequence:

```php
$routes->get('api/profile', 'Profile::index', ['filter' => 'chain:session,tokens,jwt']);
```

## Customization

See the [Extending](docs/extending.md) documentation.

## Credits

Built from the best of two worlds: the **Shield Foundation** (CodeIgniter Shield) and the **Myth-Auth** community.
