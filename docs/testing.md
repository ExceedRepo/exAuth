# Testing

exAuth ships with an automated test suite built on PHPUnit and CodeIgniter 4's
testing tools. Tests run against an **in-memory SQLite database**, so you don't
need to configure MySQL to run them.

## Running the tests

```bash
# from the exAuth root directory
composer install     # first time only, installs PHPUnit etc.
composer test        # or: vendor/bin/phpunit
```

> Requires PHP 8.2+. If your default `php` is older, run with an explicit
> binary, e.g. `php8.2 vendor/bin/phpunit`.

## How it is wired

- `phpunit.xml.dist` bootstraps the framework from
  `vendor/codeigniter4/framework/system/Test/bootstrap.php` and sets the `tests`
  database group to SQLite `:memory:`.
- `tests/_support/TestCase.php` is the base class. It uses
  `DatabaseTestTrait` and sets `$namespace = 'exAuth'` so **only exAuth's
  migrations** run before each test. The schema is refreshed per test.
- `auth.hashCost` is lowered in `phpunit.xml.dist` to keep password hashing fast.

## What is covered

| Test | What it verifies |
|------|------------------|
| `tests/Database/MigrationsTest.php` | Core tables exist; `users` has a `password` column (not the legacy `password_hash`). |
| `tests/Models/UserModelTest.php` | `getUserByEmail()` / `getUserByUsername()` return arrays; stored password verifies. |
| `tests/Entities/UserAuthorizableTest.php` | `inGroup()`, `can()` incl. wildcard permissions from groups and direct per-user permissions. |
| `tests/Authentication/SessionAuthenticatorTest.php` | `Session::login()` + helpers (`ex_logged_in()`, `ex_user_id()`, `ex_current_user()`), and `ex_logout()` clears state. |
| `tests/Authentication/JWTAuthenticatorTest.php` | JWT generate/verify/refresh, tampered + expired + wrong-secret rejection, short-secret guard. |
| `tests/Controllers/JWTFlowTest.php` | End-to-end token API: issue token (email/username), reject wrong password, protected `/me` with/without/invalid token, refresh. |
| `tests/Config/LoginFieldConfigTest.php` | Login field settings (`validFields`, `useEmailForLogin`, `useUsernameForLogin`) exist and default to both. |

## Writing your own tests

Extend the base test case so migrations run automatically:

```php
<?php

namespace Tests\Feature;

use Tests\Support\TestCase;
use exAuth\Models\UserModel;

final class MyTest extends TestCase
{
    public function testSomething(): void
    {
        $model = model(UserModel::class);
        $model->save([
            'email'    => 'a@b.com',
            'username' => 'ab',
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
            'active'   => 1,
        ]);

        $this->assertIsArray($model->getUserByEmail('a@b.com'));
    }
}
```

The session is automatically mocked by CodeIgniter's `CIUnitTestCase`, so code
that calls `session()` (like the Session authenticator and the `ex_*` helpers)
works inside tests without extra setup.
