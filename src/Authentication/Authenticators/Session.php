<?php

declare(strict_types=1);

namespace exAuth\Authentication\Authenticators;

use exAuth\Authentication\AuthenticatorInterface;
use exAuth\Models\UserIdentityModel;

class Session implements AuthenticatorInterface
{
    private ?\exAuth\Entities\User $user = null;

    private string $error = '';

    public function authenticate(\CodeIgniter\HTTP\Request $request): bool
    {
        $credentials = $request->getPost();

        $email = $credentials['email'] ?? '';
        $password = $credentials['password'] ?? '';

        if ($email === '' || $password === '') {
            $this->error = 'Email and password are required';

            return false;
        }

        $identityModel = model(UserIdentityModel::class);

        $identity = $identityModel->getIdentityBySecret('email_password', $email);

        if ($identity === null) {
            $this->error = 'Invalid email or password';

            return false;
        }

        if (! password_verify($password, $identity->secret2)) {
            $this->error = 'Invalid email or password';

            return false;
        }

        $user = $identity->user();

        if ($user === null) {
            $this->error = 'User not found';

            return false;
        }

        $this->user = $user;

        session()->set('auth_user_id', $user->id);
        session()->set('auth_logged_in', true);

        return true;
    }

    public function login(\exAuth\Entities\User $user, bool $remember = false): bool
    {
        $this->user = $user;

        session()->set('auth_user_id', $user->id);
        session()->set('auth_logged_in', true);

        return true;
    }

    public function logout(): void
    {
        session()->destroy();

        $this->user = null;
    }

    public function isLoggedIn(): bool
    {
        return session()->get('auth_logged_in') === true;
    }

    public function getId(): int|string|null
    {
        return session()->get('auth_user_id');
    }

    public function supports(): array
    {
        return ['session', 'password'];
    }

    public function getError(): string|null
    {
        return $this->error !== '' ? $this->error : null;
    }

    public function getErrorMessage(): string
    {
        return $this->error;
    }
}
