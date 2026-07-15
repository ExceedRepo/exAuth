# Setup Guide: From Zero to Login, Register, Logout

> **Target audience:** Complete beginners. If you've just installed CodeIgniter 4
> and your brain is still running on coffee, this guide is for you.

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Installation](#2-installation)
3. [One-Click Setup (Recommended)](#3-one-click-setup-recommended)
4. [Manual Setup](#4-manual-setup)
5. [Run Migrations](#5-run-migrations)
6. [Create a Dashboard Page](#6-create-a-dashboard-page)
7. [Protect Routes with Authentication](#7-protect-routes-with-authentication)
8. [Test the Full Flow](#8-test-the-full-flow)
9. [Helper Functions Reference](#9-helper-functions-reference)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Prerequisites

Before we start, make sure you have:

- **PHP 8.2 or higher** installed. Run `php -v` to check. If it shows something
  older than 8.2, go upgrade. I'll wait.
- **Composer** installed. Run `composer --version`. If you get "command not
  found", Google "install composer" and come back.
- **A CodeIgniter 4 project** already set up. If you don't have one, run:
  ```bash
  composer create-project codeigniter4/appstarter my-project
  cd my-project
  ```
- **A database connection configured** in `.env`. Make sure `database.default.*`
  settings are filled in. SQLite works fine for testing:
  ```env
  database.default.dsn   = SQLite3:///writable/example.db
  ```

> **Pro tip:** If you're using SQLite, make sure `writable/` is writable by your
> web server. Yes, the irony of a folder named "writable" not being writable is
> not lost on me.

---

## 2. Installation

Install exAuth via Composer:

```bash
composer require exceed/exauth
```

This will also install CodeIgniter Shield (exAuth's parent library) and all its
dependencies. Grab a coffee while Composer does its thing.

---

## 3. One-Click Setup (Recommended)

exAuth comes with a spark command that does most of the boring work for you:

```bash
php spark exauth:setup
```

This interactive command will:

1. **Publish config files** to `app/Config/exAuth.php` and `app/Config/AuthGroups.php`
   — these extend the originals, so you can safely customize them later.

2. **Add helpers** to `app/Config/Autoload.php` — it adds `'exAuth'` and
   `'setting'` to the `$helpers` array so you can use helper functions like
   `ex_logged_in()` everywhere without manually loading them.

3. **Add routes** to `app/Config/Routes.php` — adds
   `service('auth')->routes($routes);` so your app knows about `/login`,
   `/register`, `/logout`, etc.

4. **Update security** — changes `$csrfProtection` to `'session'` in
   `app/Config/Security.php` (required by Shield for auth forms).

5. **Check email config** — prompts you to set `$fromEmail` and `$fromName`
   in `app/Config/Email.php` if they're empty.

6. **Ask about migrations** — prompts you to run `spark migrate --all`.

If a file already exists, it will ask before overwriting. Use `-f` to force
overwrite everything.

> **Still reading?** If you used the one-click setup, skip to
> [section 6](#6-create-a-dashboard-page). If you're a control freak who wants
> to do everything by hand, the manual setup is below.

---

## 4. Manual Setup

If you prefer to do things the old-fashioned way (or the setup command didn't
work for some reason), here's what you need to do manually.

### 4.1 Publish Config Files

Copy the config files from exAuth to your app:

```bash
# Create the config files in your app
cp vendor/exceed/exauth/src/Config/exAuth.php app/Config/
cp vendor/exceed/exauth/src/Config/AuthGroups.php app/Config/
```

Then edit both files to change the namespace from `exAuth\Config` to `Config`
and make them extend the original classes:

**`app/Config/exAuth.php`:**
```php
<?php
namespace Config;

use exAuth\Config\exAuth as BaseExAuth;

class exAuth extends BaseExAuth
{
}
```

**`app/Config/AuthGroups.php`:**
```php
<?php
namespace Config;

use exAuth\Config\AuthGroups as BaseAuthGroups;

class AuthGroups extends BaseAuthGroups
{
}
```

### 4.2 Load Helpers

Edit `app/Config/Autoload.php` and add `'exAuth'` and `'setting'` to the
`$helpers` array:

```php
public $helpers = ['exAuth', 'setting'];
```

### 4.3 Add Routes

Open `app/Config/Routes.php` and add this line **after** the `$routes->get('/'...)`
line:

```php
service('auth')->routes($routes);
```

### 4.4 Update Security

Open `app/Config/Security.php` and change:

```php
public $csrfProtection = 'cookie';
```

to:

```php
public $csrfProtection = 'session';
```

This is required because Shield uses session-based CSRF protection for auth
forms. If you skip this, your login form will throw CSRF errors and you will
be sad.

---

## 5. Run Migrations

Now run the database migrations to create the auth tables:

```bash
php spark migrate --all
```

This creates the following tables:
- `users` — where user accounts live
- `auth_identities` — stores tokens, magic links, reset tokens, etc.
- `auth_logins` — keeps track of login attempts
- `auth_remember_tokens` — for the "Remember Me" feature
- `auth_groups_users` — who belongs to which group (group name stored as text)
- `auth_permissions_users` — direct per-user permissions
- `auth_users_groups` — additional user/group mapping

Don't worry, you don't need to memorize all of them. Just know they're there,
working hard so you don't have to.

> **Verify:** Run `php spark db:table --show` to see all tables. If you see
> `users` and `auth_*` tables, you're golden.

---

## 6. Create a Dashboard Page

Now let's create a page where logged-in users can see their info (username,
email) and log out.

### 6.1 Create the Controller

Create `app/Controllers/Dashboard.php`:

```php
<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index(): string
    {
        // Get the currently logged-in user
        $user = ex_current_user();

        // Pass user data to the view
        return view('dashboard', [
            'user' => $user,
        ]);
    }
}
```

> **What's happening here?** `ex_current_user()` returns the logged-in user as
> a `User` entity, or `null` if nobody's logged in. Since we'll protect this
> page with the `session` filter, we know `$user` will always be available.

### 6.2 Create the View

Create `app/Views/dashboard.php`:

```php
<?= $this->extend('App\Views\layout') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="container d-flex justify-content-center p-5">
    <div class="card col-12 col-md-6 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">Welcome, <?= esc($user->username) ?>! 🎉</h5>

            <table class="table table-bordered">
                <tr>
                    <th>Username</th>
                    <td><?= esc($user->username) ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?= esc($user->email) ?></td>
                </tr>
            </table>

            <div class="d-grid gap-2">
                <a href="<?= url_to('logout') ?>" class="btn btn-danger">
                    Logout
                </a>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
```

> **Note:** The view extends `App\Views\layout`. You can use Shield's default
> layout by extending `service('auth')->viewLayout` instead, but creating your
> own layout gives you more control.

### 6.3 Create a Layout (Optional)

If you don't have a layout yet, create `app/Views/layout.php`:

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

### 6.4 Add Routes for Dashboard

In `app/Config/Routes.php`, add:

```php
$routes->group('dashboard', ['filter' => 'session'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
});
```

This protects the entire `/dashboard` route group — only logged-in users can
access it. Unauthenticated users will be redirected to `/login`.

### 6.5 Redirect After Login

By default, exAuth redirects to `/` (your home page) after a successful login
or registration. The simplest approach for a beginner is to put your dashboard
content at the `/` route, or redirect from your home controller:

```php
// app/Controllers/Home.php
public function index()
{
    if (ex_logged_in()) {
        return redirect()->to('/dashboard');
    }
    return redirect()->to('/login');
}
```

> To change the built-in redirect target, publish the controllers and edit the
> `redirect()->to('/')` calls — but the home-controller approach above is easier.

---

## 7. Protect Routes with Authentication

You've already seen the `session` filter, but here are all the ways you can
protect your routes:

### 7.1 Protect a Single Route

```php
$routes->get('profile', 'Profile::index', ['filter' => 'session']);
```

### 7.2 Protect a Route Group

```php
$routes->group('admin', ['filter' => 'session'], static function ($routes) {
    $routes->get('/', 'Admin::index');
    $routes->get('users', 'Admin::users');
});
```

### 7.3 Protect by Group Membership

```php
$routes->get('admin', 'Admin::index', ['filter' => 'group:admin,superadmin']);
```

### 7.4 Protect by Permission

```php
$routes->get('admin/users', 'Admin::users', ['filter' => 'permission:users.manage']);
```

### 7.5 Check Login in Controllers (Without Filters)

Sometimes you need more control. Check login status directly in your controller:

```php
<?php

namespace App\Controllers;

class Profile extends BaseController
{
    public function index()
    {
        if (! ex_logged_in()) {
            return redirect()->to('/login');
        }

        return view('profile', [
            'user' => ex_current_user(),
        ]);
    }
}
```

---

## 8. Test the Full Flow

Time to see if all this actually works. Follow these steps:

### Step 1: Open the Register Page

Go to `http://your-app.test/register` in your browser. You should see a
registration form with fields for email, username, password, and password
confirmation.

> **If you get a 404:** Make sure you added `service('auth')->routes($routes);`
> to your `app/Config/Routes.php`.

> **If you get a CSRF error:** Make sure you changed `$csrfProtection` to
> `'session'` in `app/Config/Security.php`.

### Step 2: Create an Account

Fill in the registration form:
- **Email:** `john@example.com`
- **Username:** `johndoe`
- **Password:** `password123` (must be at least 8 characters)
- **Confirm Password:** `password123`

Click "Register". If registration is successful, you'll be redirected to `/`.

### Step 3: Check the Dashboard

Now go to `http://your-app.test/dashboard`. You should see:
- A welcome message with your username
- A table showing your username and email
- A "Logout" button

> **Note:** You were automatically logged in after registration. Magic, right?
> Actually it's just good UX. No wizards were harmed.

### Step 4: Logout

Click the "Logout" button. You'll be redirected to the login page.

### Step 5: Verify You're Logged Out

Try visiting `http://your-app.test/dashboard` again. You should be redirected
to `/login` because the `session` filter is doing its job.

### Step 6: Login Again

Go to `http://your-app.test/login`, enter your email and password, and you
should be back in the dashboard.

### Step 7: Try the Helper Functions in `spark tinker` (or any view)

```php
// In a view or controller:
ex_logged_in();       // true if logged in
ex_current_user();    // User entity or null
ex_user_id();         // Current user's ID or null
ex_auth();            // Auth service instance
```

---

## 9. Helper Functions Reference

All exAuth helper functions use the `ex_` prefix to avoid conflicts with other
libraries (yes, we learned this the hard way).

| Function | Returns | Description |
|----------|---------|-------------|
| `ex_auth()` | `exAuth\Auth` | The auth facade (has `routes()` method) |
| `ex_logged_in()` | `bool` | Check if any user is logged in |
| `ex_current_user()` | `exAuth\Entities\User\|null` | Get the logged-in user entity |
| `ex_user_id()` | `int\|null` | Get the current user's ID |
| `ex_logout()` | `void` | Log out the current user |

To use them in views, make sure the helper is loaded:

- **Globally:** Add `'exAuth'` to `$helpers` in `app/Config/Autoload.php`
  (the `exauth:setup` command does this automatically).
- **Per controller:** Call `helper('exAuth')` in your controller method.
- **Per view:** Call `helper('exAuth')` at the top of your view file.

---

## 10. Troubleshooting

### "Class 'exAuth\Config\exAuth' not found" or "Config file not found"

Make sure you've installed the package with Composer:
```bash
composer require exceed/exauth
```

Also verify that `vendor/exceed/exauth/` exists. If not, run `composer install`.

### "Route not found" when visiting /login or /register

Did you add `service('auth')->routes($routes);` to `app/Config/Routes.php`?
Double-check. This is the most common mistake.

### "CSRF token not found" or "400 Bad Request" on login/register

You need to change `$csrfProtection` to `'session'` in
`app/Config/Security.php`. Cookie-based CSRF doesn't work well with auth forms.

### "Helper not found" or "Call to undefined function ex_logged_in()"

Make sure `'exAuth'` is in the `$helpers` array in `app/Config/Autoload.php`:
```php
public $helpers = ['exAuth', 'setting'];
```

Or manually load it: `helper('exAuth')`.

### "Table 'users' not found" or "Base table not found"

You forgot to run migrations:
```bash
php spark migrate --all
```

### "Cannot redeclare function ex_auth()" or "Call to undefined function ex_auth()"

You might be loading the wrong helper. exAuth's helper is `exAuth`, not `auth`.
Make sure `'exAuth'` is in the `$helpers` array in `app/Config/Autoload.php`.
All exAuth helpers use the `ex_` prefix.

### Login redirect keeps going to "/" instead of my dashboard

exAuth redirects to `/` after login by default. The easiest fix is to make your
home controller (`/`) redirect logged-in users to your dashboard — see
[section 6.5](#65-redirect-after-login).

### I still can't log in / register and I've tried everything

Take a deep breath. Count to 10. Then:

1. Check your `.env` file — is the database configured correctly?
2. Check `app/Config/Database.php` — does it match your `.env`?
3. Run `php spark migrate --all` again (it's safe to run multiple times).
4. Clear cache: `php spark cache:clear`
5. Check the logs in `writable/logs/` for actual error messages.

If all else fails, open an issue on the
[exAuth GitHub repository](https://github.com/ExceedRepo/exAuth).

---

## Bonus: Minimal Example (For the Impatient)

If you just want the absolute minimum to get auth working, here's your
checklist:

```bash
# 1. Install
composer require exceed/exauth

# 2. Setup
php spark exauth:setup

# 3. Create a simple controller
mkdir -p app/Views
```

**`app/Controllers/Home.php`:**
```php
<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        if (! ex_logged_in()) {
            return redirect()->to('/login');
        }

        return view('welcome_message', [
            'user' => ex_current_user(),
        ]);
    }
}
```

**`app/Views/welcome_message.php`** (modify the existing one):
```php
<?= $this->extend('App\Views\layout') ?>

<?= $this->section('main') ?>
<div class="container mt-5">
    <?php if (ex_logged_in()): ?>
        <h1>Hello, <?= esc(ex_current_user()->username) ?>!</h1>
        <p>Email: <?= esc(ex_current_user()->email) ?></p>
        <a href="<?= url_to('logout') ?>" class="btn btn-primary">Logout</a>
    <?php else: ?>
        <h1>Welcome!</h1>
        <a href="<?= url_to('login') ?>" class="btn btn-primary">Login</a>
        <a href="<?= url_to('register') ?>" class="btn btn-secondary">Register</a>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
```

**`app/Config/Routes.php`:**
```php
// Make sure these are already there:
service('auth')->routes($routes);
$routes->get('/', 'Home::index');
```

That's it. Three files, and you have a working auth system. You're welcome.
