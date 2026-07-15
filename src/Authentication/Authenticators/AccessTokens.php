<?php

declare(strict_types=1);

namespace exAuth\Authentication\Authenticators;

use CodeIgniter\I18n\Time;
use exAuth\Authentication\AuthenticatorInterface;
use exAuth\Models\UserIdentityModel;

class AccessTokens implements AuthenticatorInterface
{
    private string $error = '';

    private ?int $userId = null;

    private array $scopes = [];

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

        $identityModel = model(UserIdentityModel::class);
        $row           = $identityModel->findByAccessToken($rawToken);

        if ($row === null) {
            $this->error = 'Invalid access token';

            return false;
        }

        if (! empty($row->expires_at) && Time::parse($row->expires_at)->isBefore(Time::now())) {
            $this->error = 'Access token has expired';

            return false;
        }

        $this->userId = (int) $row->user_id;
        $this->scopes = $this->decodeScopes($row->extras ?? null);

        $identityModel->touchLastUsed((int) $row->id);

        return true;
    }

    /**
     * Whether the current token grants a given scope (supports the '*' wildcard).
     */
    public function tokenCan(string $scope): bool
    {
        if (in_array('*', $this->scopes, true)) {
            return true;
        }

        return in_array($scope, $this->scopes, true);
    }

    public function getScopes(): array
    {
        return $this->scopes;
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
        $this->scopes = [];
        $this->error  = '';
    }

    public function getError(): string|null
    {
        return $this->error !== '' ? $this->error : null;
    }

    public function getErrorMessage(): string
    {
        return $this->error;
    }

    private function decodeScopes(?string $extras): array
    {
        if ($extras === null || $extras === '') {
            return ['*'];
        }

        $decoded = json_decode($extras, true);

        return is_array($decoded) ? $decoded : ['*'];
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
