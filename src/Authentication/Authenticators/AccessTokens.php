<?php

declare(strict_types=1);

namespace exAuth\Authentication\Authenticators;

use exAuth\Authentication\AuthenticatorInterface;
use exAuth\Models\UserIdentityModel;

class AccessTokens implements AuthenticatorInterface
{
    private string $error = '';

    private ?int $userId = null;

    public function authenticate(\CodeIgniter\HTTP\Request $request): bool
    {
        $token = $this->extractBearerToken($request);

        if ($token === null) {
            $this->error = 'No access token provided';

            return false;
        }

        return $this->verify($token);
    }

    public function verify(?string $rawToken = null): bool
    {
        if ($rawToken === null || $rawToken === '') {
            $this->error = 'No access token provided';

            return false;
        }

        $hashed = hash('sha256', $rawToken);

        $identityModel = model(UserIdentityModel::class);

        $row = $identityModel->asObject()
            ->where('type', 'access_token')
            ->where('secret', $hashed)
            ->first();

        if ($row === null) {
            $this->error = 'Invalid access token';

            return false;
        }

        $this->userId = (int) $row->user_id;

        return true;
    }

    public function getUserId(): int|null
    {
        return $this->userId;
    }

    public function supports(): array
    {
        return ['access_token', 'bearer'];
    }

    public function logout(): void
    {
        $this->userId = null;
        $this->error = '';
    }

    public function getError(): string|null
    {
        return $this->error !== '' ? $this->error : null;
    }

    public function getErrorMessage(): string
    {
        return $this->error;
    }

    private function extractBearerToken(\CodeIgniter\HTTP\Request $request): string|null
    {
        $header = $request->getHeaderLine('Authorization');

        if ($header === '' || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return trim(substr($header, 7));
    }
}
