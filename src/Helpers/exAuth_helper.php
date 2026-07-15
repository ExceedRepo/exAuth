<?php

declare(strict_types=1);

use exAuth\Auth;
use exAuth\Authentication\Authenticators\Session;
use exAuth\Entities\User;

if (! function_exists('ex_auth')) {
    function ex_auth(): Auth
    {
        return service('auth');
    }
}

if (! function_exists('ex_session')) {
    function ex_session(): Session
    {
        return new Session();
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

if (! function_exists('ex_jwt_id')) {
    /**
     * Returns the user id from the currently verified JWT (set by the `jwt` filter),
     * or null if there is none.
     */
    function ex_jwt_id(): ?int
    {
        return service('jwt')->getUserId();
    }
}

if (! function_exists('ex_jwt_user')) {
    /**
     * Returns the User entity for the currently verified JWT, or null.
     */
    function ex_jwt_user(): ?User
    {
        $userId = ex_jwt_id();

        if ($userId === null) {
            return null;
        }

        return model(\exAuth\Models\UserModel::class)->find($userId);
    }
}

if (! function_exists('ex_token_id')) {
    /**
     * User id from the currently verified access token (set by the `tokens` filter).
     */
    function ex_token_id(): ?int
    {
        return service('tokens')->getUserId();
    }
}

if (! function_exists('ex_token_user')) {
    function ex_token_user(): ?User
    {
        $userId = ex_token_id();

        if ($userId === null) {
            return null;
        }

        return model(\exAuth\Models\UserModel::class)->find($userId);
    }
}

if (! function_exists('ex_hmac_id')) {
    /**
     * User id from the currently verified HMAC request (set by the `hmac` filter).
     */
    function ex_hmac_id(): ?int
    {
        return service('hmac')->getUserId();
    }
}

if (! function_exists('ex_hmac_user')) {
    function ex_hmac_user(): ?User
    {
        $userId = ex_hmac_id();

        if ($userId === null) {
            return null;
        }

        return model(\exAuth\Models\UserModel::class)->find($userId);
    }
}
