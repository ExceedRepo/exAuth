# Extending exAuth

exAuth is designed to be extensible. Here's how to override and customize various parts.

## Configuration

Copy the config file to your project's `app/Config/exAuth.php` and change the namespace:

```php
namespace Config;

use exAuth\Config\exAuth as BaseConfig;

class exAuth extends BaseConfig
{
}
```

You can then override any property:

```php
public $defaultGroup = 'subscribers';
public $allowRegistration = false;
public $views = [
    'login'    => 'App\Views\auth\login',
    'register' => 'App\Views\auth\register',
    'forgot'   => 'App\Views\auth\forgot',
    'reset'    => 'App\Views\auth\reset',
];
```

## Views

Place your custom views in `app/Views/` and override the view paths in `Config/exAuth.php`.

See the `$views` property in `exAuth/Config/exAuth.php` for the default view paths.

## Routes

Shield handles route registration automatically via `service('auth')->routes($routes)`.
To customize routes, publish Shield's AuthRoutes config:
```bash
cp vendor/codeigniter4/shield/src/Config/AuthRoutes.php app/Config/
```

## Models

Create a new model in `app/Models/` that extends the exAuth model:

```php
namespace App\Models;

use exAuth\Models\UserModel as BaseUserModel;

class UserModel extends BaseUserModel
{
    protected $allowedFields = [
        'email', 'username', 'password_hash', 'active', 'activate_hash',
        'reset_hash', 'reset_at', 'reset_expires', 'last_login',
        'firstname', 'lastname', 'phone', // custom fields
    ];
}
```

## Entities

Create a new Entity that extends the exAuth entity:

```php
namespace App\Entities;

use exAuth\Entities\User as BaseUser;

class User extends BaseUser
{
    public function getName()
    {
        return trim(trim($this->firstname) . ' ' . trim($this->lastname));
    }
}
```

Add your custom fields to the database using a new migration.

## Filters

You can add or extend the filters in `app/Config/Filters.php`:

Filters are already auto-registered by exAuth's `Registrar`. You don't need to add them manually.
To add a custom filter, you can still add to `app/Config/Filters.php`:

```php
public $aliases = [
    'admin'     => \App\Filters\AdminFilter::class,
];
```

## Services

If you want to provide a custom `authentication` or `authorization` implementation, create a class that implements the appropriate interface:

- `exAuth\Authentication\AuthenticatorInterface`

Then register it in `Config/Services.php` or via the `Registrar` pattern.
