<?php

declare(strict_types=1);

use exAuth\Auth;
use exAuth\Authentication\Authentication;
use exAuth\Authentication\Authenticators\Session;
use exAuth\Entities\User;

if (! function_exists('ex_auth')) {
    function ex_auth(): Auth
    {
        return service('auth');
    }
}

if (! function_exists('ex_session')) {
    function ex_session(): ?Session
    {
        try {
            $instance = Authentication::factory('session');
            return $instance instanceof Session ? $instance : null;
        } catch (\RuntimeException) {
            return null;
        }
    }
}

if (! function_exists('ex_user_id')) {
    function ex_user_id(): ?int
    {
        $session = ex_session();

        return $session?->getId();
    }
}

if (! function_exists('ex_logged_in')) {
    function ex_logged_in(): bool
    {
        $session = ex_session();

        return $session?->isLoggedIn() ?? false;
    }
}

if (! function_exists('ex_logout')) {
    function ex_logout(): void
    {
        $session = ex_session();
        $session?->logout();
    }
}

if (! function_exists('ex_current_user')) {
    function ex_current_user(): ?User
    {
        $userId = ex_user_id();

        if ($userId === null) {
            return null;
        }

        $userModel = model(\exAuth\Models\UserModel::class);

        return $userModel->find($userId);
    }
}
