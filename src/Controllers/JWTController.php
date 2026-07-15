<?php

declare(strict_types=1);

namespace exAuth\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use exAuth\Models\UserModel;

class JWTController extends Controller
{
    private UserModel $users;

    public function __construct()
    {
        $this->users = model(UserModel::class);
    }

    /**
     * POST — validate credentials and return a signed JWT.
     */
    public function token(): ResponseInterface
    {
        $login    = (string) ($this->input('login') ?? '');
        $email    = (string) ($this->input('email') ?? '');
        $username = (string) ($this->input('username') ?? '');
        $password = (string) ($this->input('password') ?? '');

        $config      = config('exAuth');
        $useEmail    = in_array('email', $config->validFields, true) && $config->useEmailForLogin;
        $useUsername = in_array('username', $config->validFields, true) && $config->useUsernameForLogin;

        if ($login === '') {
            $login = $email !== '' ? $email : $username;
        }

        if ($login === '' || $password === '') {
            return $this->fail('Login and password are required.', 422);
        }

        $user = null;

        if ($useEmail && $useUsername) {
            $user = filter_var($login, FILTER_VALIDATE_EMAIL) !== false
                ? $this->users->getUserByEmail($login)
                : $this->users->getUserByUsername($login);
        } elseif ($useEmail) {
            $user = $this->users->getUserByEmail($login);
        } elseif ($useUsername) {
            $user = $this->users->getUserByUsername($login);
        }

        if ($user === null || ! password_verify($password, (string) $user['password'])) {
            return $this->fail('Invalid credentials.', 401);
        }

        if ((bool) $user['active'] === false) {
            return $this->fail('Account is not active.', 403);
        }

        if (in_array($user['status'] ?? null, ['banned', 'suspended'], true)) {
            return $this->fail('Account is locked.', 403);
        }

        $jwt   = service('jwt');
        $token = $jwt->generateToken((int) $user['id'], [
            'email'    => $user['email'] ?? null,
            'username' => $user['username'] ?? null,
        ]);

        return service('response')->setJSON([
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('AuthJWT')->timeToLive,
        ]);
    }

    /**
     * GET — return the authenticated user (route must use the `jwt` filter).
     */
    public function me(): ResponseInterface
    {
        $userId = service('jwt')->getUserId();

        if ($userId === null) {
            return $this->fail('Unauthorized.', 401);
        }

        $user = $this->users->findUserById($userId);

        if ($user === null) {
            return $this->fail('User not found.', 404);
        }

        return service('response')->setJSON([
            'id'       => $user->id,
            'email'    => $user->email,
            'username' => $user->username,
        ]);
    }

    /**
     * POST — exchange a still-valid token for a fresh one.
     */
    public function refresh(): ResponseInterface
    {
        $header = $this->request->getHeaderLine('Authorization');

        if ($header === '' || ! str_starts_with($header, 'Bearer ')) {
            return $this->fail('Missing bearer token.', 401);
        }

        $current = trim(substr($header, 7));
        $jwt     = service('jwt');

        if (! $jwt->verify($current)) {
            return $this->fail($jwt->getErrorMessage() ?: 'Invalid token.', 401);
        }

        $newToken = $jwt->refresh($current);

        if ($newToken === null) {
            return $this->fail('Could not refresh token.', 400);
        }

        return service('response')->setJSON([
            'token'      => $newToken,
            'token_type' => 'Bearer',
            'expires_in' => config('AuthJWT')->timeToLive,
        ]);
    }

    /**
     * Reads an input value from JSON body or POST.
     */
    private function input(string $key): ?string
    {
        $json = $this->request->getJsonVar($key);
        if ($json !== null) {
            return is_string($json) ? $json : (string) $json;
        }

        $post = $this->request->getPost($key);

        return $post !== null ? (string) $post : null;
    }

    private function fail(string $message, int $status): ResponseInterface
    {
        return service('response')->setStatusCode($status)->setJSON([
            'error' => $message,
        ]);
    }
}
