# Events

The following events are thrown by the default configuration of exAuth.

## Authentication

**login ($user)**

Fired after a user successfully logs in.

```php
\CodeIgniter\Events\Events::on('login', function ($user) {
    $this->logger->info('User logged in: ' . $user->email);
});
```

**logout ($user)**

Fired after a user logs out.

```php
\CodeIgniter\Events\Events::on('logout', function ($user) {
    $this->logger->info('User logged out: ' . $user->email);
});
```

## User Registration

**register ($user)**

Fired after a user is successfully registered.

```php
\CodeIgniter\Events\Events::on('register', function ($user) {
    // send welcome email, etc.
});
```

## Password Reset

**passwordReset ($user)**

Fired after a user successfully resets their password.

```php
\CodeIgniter\Events\Events::on('passwordReset', function ($user) {
    $this->notifier->sendEmail($user, 'password_reset');
});
```

## User Activation

**activate ($user)**

Fired after a user activates their account.

**deactivate ($user)**

Fired after a user has been deactivated.

## User Banning

**ban ($user)**

Fired after a user has been banned.

**unban ($user)**

Fired after a user has been unbanned.

### Examples:

```php
\CodeIgniter\Events\Events::on('ban', function ($user) {
    $user->invalidateTokens();
});

\CodeIgniter\Events\Events::on('unban', function ($user) {
    $user->clearCache();
});
```

## Authorization

There are no authorization-specific events at this time.
