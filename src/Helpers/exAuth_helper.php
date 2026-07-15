<?php

declare(strict_types=1);

use exAuth\Authentication\Authentication;
use exAuth\Entities\User;

if (! function_exists('ex_auth')) {
    function ex_auth(): Authentication
    {
        return service('auth');
    }
}

if (! function_exists('ex_user_id')) {
    function ex_user_id(): ?int
    {
        return ex_auth()->getId();
    }
}

if (! function_exists('ex_logged_in')) {
    function ex_logged_in(): bool
    {
        return ex_auth()->isLoggedIn();
    }
}

if (! function_exists('ex_logout')) {
    function ex_logout(): void
    {
        ex_auth()->logout();
    }
}

if (! function_exists('ex_current_user')) {
    function ex_current_user(): ?User
    {
        return ex_auth()->getUser();
    }
}
