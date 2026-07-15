# exAuth (CodeIgniter 4) Installation Guide — from Zero to Working RBAC

This guide contains the exact, real-world steps used to install the
[`exceed/exauth`](https://packagist.org/packages/exceed/exauth) library on top of
CodeIgniter 4, all the way until **register / login / logout** and
**RBAC (group & permission)** are usable.

> Version used while writing this guide: **exAuth v1.2.0**, CodeIgniter **4.7**, PHP **8.2**.
> Make sure Packagist and GitHub show the same version (check `composer show exceed/exauth --all`).

---

## 1. Prerequisites

- PHP **8.2+** (`php -v`)
- **Composer** (`composer --version`)
- An existing **CodeIgniter 4** project (e.g. from `composer create-project codeigniter4/appstarter`)
- A database prepared. This guide uses **MySQL/MariaDB** (SQLite also works for trying things out).
- The `writable/` folder must be writable by the web server.

---

## 2. Install the package

```bash
composer require exceed/exauth
```

Composer will pull `exceed/exauth` plus the `firebase/php-jwt` dependency.
Use PHP 8.2 explicitly if the server has multiple PHP versions, e.g.:

```bash
/usr/bin/php8.2 /usr/local/bin/composer require exceed/exauth
```

---

## 3. Configure the environment (`.env`)

Open `.env` and enable the required sections:

```dotenv
CI_ENVIRONMENT = development

app.baseURL = 'https://mantan-terindah.com'   # replace with your app's domain/URL

# Database (MySQL example)
database.default.hostname = localhost
database.default.database = db_remember
database.default.username = myex
database.default.password = 'myB34ut1ful3x'
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306
```

> **Tip:** Empty the database tables before migrating, so no leftover old schema
> collides with the new migrations:
> ```sql
> -- example: drop all exauth tables
> DROP TABLE IF EXISTS migrations, users,
>   auth_groups_users, auth_identities, auth_logins,
>   auth_permissions_users, auth_remember_tokens, auth_users_groups;
> ```

---

## 4. Set the Email config (to skip the setup prompt)

`exauth:setup` will ask for `Config\Email::$fromEmail` / `$fromName`.
Fill these in first so you don't have to answer the prompt (important when running non-interactively):

`app/Config/Email.php`

```php
public string $fromEmail = 'noreply@mantan-terindah.com';
public string $fromName  = 'RND IT';
```

---

## 5. Run the one-click setup

```bash
php spark exauth:setup -f
```

This command automatically:

1. Creates `app/Config/exAuth.php` & `app/Config/AuthGroups.php` (extending the built-in config).
2. Adds the `exAuth` & `setting` helpers to `app/Config/Autoload.php`.
3. Adds `service('auth')->routes($routes);` to `app/Config/Routes.php`.
4. Changes `app/Config/Security.php` → `csrfProtection = 'session'` (required for auth forms).
5. Checks the Email config.

> The `-f` option = force-overwrite existing files.
> The final “Run migrate now?” prompt **can be skipped** (answer `n`) because we run
> migrations ourselves in the next step.

### 5b. Manual setup (only if the command fails)

If `exauth:setup` cannot run for some reason, do these 4 things by hand:

1. **Publish config** — copy and edit the namespace so they extend the package config:

   ```bash
   cp vendor/exceed/exauth/src/Config/exAuth.php app/Config/
   cp vendor/exceed/exauth/src/Config/AuthGroups.php app/Config/
   ```

   `app/Config/exAuth.php`:
   ```php
   <?php
   namespace Config;
   use exAuth\Config\exAuth as BaseExAuth;
   class exAuth extends BaseExAuth {}
   ```

   `app/Config/AuthGroups.php`:
   ```php
   <?php
   namespace Config;
   use exAuth\Config\AuthGroups as BaseAuthGroups;
   class AuthGroups extends BaseAuthGroups {}
   ```

2. **Load the helper** in `app/Config/Autoload.php`:
   ```php
   public $helpers = ['exAuth', 'setting'];
   ```

3. **Add the routes** in `app/Config/Routes.php`:
   ```php
   service('auth')->routes($routes);
   ```

4. **Fix CSRF** in `app/Config/Security.php`:
   ```php
   public $csrfProtection = 'session';
   ```

---

## 6. Run the migrations

```bash
php spark migrate --all
```

Check the created tables:

```bash
php spark db:table --show
```

Tables created include: `users`, `auth_identities`, `auth_logins`,
`auth_remember_tokens`, `auth_groups_users`, `auth_permissions_users`, `auth_users_groups`.

---

## 7. Build the Dashboard page (auth usage example)

### 7.1 Controller — `app/Controllers/Dashboard.php`

```php
<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index()
    {
        // ex_current_user() returns the User entity or null
        $user = ex_current_user();

        return view('dashboard', [
            'user' => $user,
        ]);
    }
}
```

> **Important:** do NOT annotate `: string` on a method that may return a redirect.
> The correct example in `app/Controllers/Home.php`:
> ```php
> public function index()
> {
>     if (ex_logged_in()) {
>         return redirect()->to('/dashboard');
>     }
>     return view('welcome_message');
> }
> ```
> (A `: string` return type triggers a *TypeError* because `redirect()` returns a `RedirectResponse`.)

### 7.2 Layout — `app/Views/layout.php`

```php
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->renderSection('title') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main role="main" class="container">
        <?= $this->renderSection('main') ?>
    </main>
</body>
</html>
```

### 7.3 View — `app/Views/dashboard.php`

```php
<?= $this->extend('App\Views\layout') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="container d-flex justify-content-center p-5">
    <div class="card col-12 col-md-6 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">Welcome, <?= esc($user->username) ?>! 🎉</h5>

            <table class="table table-bordered">
                <tr><th>Username</th><td><?= esc($user->username) ?></td></tr>
                <tr><th>Email</th><td><?= esc($user->email) ?></td></tr>
            </table>

            <div class="d-grid gap-2">
                <a href="<?= url_to('logout') ?>" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
```

### 7.4 Routes — `app/Config/Routes.php`

Add a route group protected by the `session` filter:

```php
/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

service('auth')->routes($routes);

$routes->group('dashboard', ['filter' => 'session'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
});
```

Built-in auth routes (from `service('auth')->routes($routes)`):
`/login`, `/register`, `/logout`, `/forgot-password`, `/reset-password`, `/verify`, `/magic-link`.

---

## 8. Permission for the `writable` folder

So the web server can write sessions, cache, and logs:

```bash
chown -R www-data:www-data writable/
chmod -R 775 writable/
```

---

## 9. Test the Flow (register → login → logout)

Open in a browser:

1. **Register** → `https://mantan-terindah.com/register` → fill in email, username, password, confirmation.
   On success it auto-logs-in and redirects to `/` (then to `/dashboard` via `Home`).
2. **Dashboard** → `https://mantan-terindah.com/dashboard` → user data should show (the `session` filter is active).
3. **Logout** → click the logout button → redirected to `/login`.
4. **Login** → `https://mantan-terindah.com/login` → enter email **or** username + password.

> exAuth accepts login with **either email or username** (the `validFields` config in `app/Config/exAuth.php`).

---

## 10. RBAC — Group & Permission

### 10.1 Default group & permission configuration

Edit `app/Config/AuthGroups.php` (created during setup). Example content:

```php
public array $groups = [
    'superadmin' => ['description' => 'Super Administrator', 'permissions' => ['*']],
    'admin'      => ['description' => 'Administrator',     'permissions' => ['admin.*', 'users.*']],
    'editor'     => ['description' => 'Editor',            'permissions' => ['content.*', 'media.*']],
    'user'       => ['description' => 'Default User',      'permissions' => ['profile.*', 'content.read']],
];

public array $permissions = [
    'users.*'      => 'Manage all user operations',
    'users.create' => 'Create users',
    'users.read'   => 'Read user data',
    'admin.*'      => 'Admin operations',
    'content.*'    => 'All content operations',
];

public string $defaultGroup = 'user';
```

Groups & permissions are stored in the **database** (`auth_groups_users`, `auth_permissions_users`)
and can be managed dynamically via the `authorization` service or the CLI.

### 10.2 Check authorization in code

The supported API is on the **User entity** (via the `Authorizable` trait):

```php
$user = ex_current_user();

// Check group membership (string or array)
$user->inGroup('admin');
$user->inGroup(['admin', 'editor']);

// Check permission (supports wildcards: admin.* , *.read , **)
$user->can('users.create');   // true if the user's group grants users.* etc.
$user->hasPermission('admin.settings');

// Introspection
$user->getGroups();       // ['admin', ...]
$user->getPermissions();  // resolved list incl. group + direct permissions
```

### 10.2b Managing groups & permissions (database)

Groups are stored per-user in `auth_groups_users` (the group **name** goes in the
`group_id` column); direct per-user permissions go in `auth_permissions_users`.
Assign them via the CLI or a direct DB insert:

```bash
# CLI
php spark exauth:user addgroup -n johndoe -g admin
php spark exauth:user removegroup -n johndoe -g admin
```

```php
// Direct DB (e.g. inside your own controller/service)
$db = db_connect();

// add user to a group
$db->table('auth_groups_users')->insert(['user_id' => $userId, 'group_id' => 'admin']);

// grant a direct permission to a user
$db->table('auth_permissions_users')->insert(['user_id' => $userId, 'permission' => 'users.delete']);
```

> **Note:** exAuth does not ship a `service('authorization')` facade. Authorization
> checks are done through the User entity (`inGroup()` / `can()`) and the
> `group:` / `permission:` route filters shown below.

### 10.3 Protect routes by group / permission

```php
// Single route
$routes->get('admin/users', 'UserController::index', ['filter' => 'permission:users.manage']);
$routes->get('admin',        'Admin::index',         ['filter' => 'group:admin,superadmin']);

// Route group
$routes->group('admin', ['filter' => 'group:admin,superadmin'], static function ($routes) {
    $routes->get('/', 'Admin::index');
    $routes->get('users', 'Admin::users');
});

// Chain auth (tries session -> tokens -> jwt -> hmac in order)
$routes->get('api/profile', 'Profile::index', ['filter' => 'chain:session,tokens,jwt']);
```

Available filter aliases (auto-registered):
`session`, `tokens`, `hmac`, `jwt`, `chain`, `group`, `permission`.

### 10.4 Commonly used helpers

| Helper | Description |
|--------|-------------|
| `ex_logged_in()` | `true` if logged in |
| `ex_current_user()` | Active User entity / `null` |
| `ex_user_id()` | Active user ID / `null` |
| `ex_logout()` | Log out the active user |
| `ex_auth()` | Auth service instance |

The `exAuth` helper is loaded globally automatically (setup adds it to `Autoload.$helpers`).

---

## 11. User management via CLI

```bash
php spark exauth:user create -n johndoe -e john@example.com -g admin
php spark exauth:user list
php spark exauth:user addgroup -n johndoe -g admin
php spark exauth:user activate -n johndoe
php spark exauth:user password -n johndoe
```

---

## 12. Troubleshooting

| Symptom | Cause & Solution |
|---------|------------------|
| `Call to a member function routes() on null` | `service('auth')` is not registered. Use **v1.1.1+** (Auth facade exists). Re-run `exauth:setup`. |
| `TypeError: Return value must be of type string, RedirectResponse returned` | Controller method declared `: string` but returns `redirect()`. **Remove the return type annotation** (see §7.1). |
| 500 error on register/login (column `password`/`password_hash`) | Old version. Use **v1.1.5+** (the `FixPasswordColumn` migration exists). Drop tables then re-run `migrate --all`. |
| `ErrorException: Type of ...$casts/$dates must not be defined` | Entity bug on PHP 8.2 in old versions. Use **v1.1.6+**. |
| `CSRF token not found` / 400 on login | `app/Config/Security.php` must be `csrfProtection = 'session'` (set by setup). |
| `Table 'users' not found` | Forgot to migrate → `php spark migrate --all`. |
| `Access denied for user ''@'localhost'` | `.env` database not correct/active. Check `database.default.*` in `.env`. |
| Dashboard 404 after login | Make sure `service('auth')->routes($routes);` is present in `Routes.php`. |

---

## 13. Command summary

```bash
# 1. Install
composer require exceed/exauth

# 2. Setup (fill Email in app/Config/Email.php first to avoid the prompt)
php spark exauth:setup -f

# 3. Migrate
php spark migrate --all

# 4. Folder permissions
chown -R www-data:www-data writable/ && chmod -R 775 writable/

# 5. (Build the Dashboard: controller + view + layout + route as in §7)
# 6. Open /register, /login, /logout in the browser
```

Done 🎉 — exAuth with register/login/logout and RBAC (group & permission) is ready to use.
