<?php

declare(strict_types=1);

use exAuth\Authentication\Authentication;
use exAuth\Entities\User;

if (! function_exists('auth')) {
    function auth(): Authentication
    {
        return service('auth');
    }
}

if (! function_exists('user_id')) {
    function user_id(): ?int
    {
        return auth()->getId();
    }
}

if (! function_exists('logged_in')) {
    function logged_in(): bool
    {
        return auth()->isLoggedIn();
    }
}

if (! function_exists('logout')) {
    function logout(): void
    {
        auth()->logout();
    }
}

if (! function_exists('current_user')) {
    function current_user(): ?User
    {
        return auth()->getUser();
    }
}
