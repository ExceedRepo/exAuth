<?php

declare(strict_types=1);

namespace exAuth\Authentication\Authenticators;

use exAuth\Authentication\AuthenticatorInterface;
use exAuth\Models\UserIdentityModel;

class HmacSha256 implements AuthenticatorInterface
{
    private string $error = '';

    private ?int $userId = null;

    public function authenticate(\CodeIgniter\HTTP\Request $request): bool
    {
        $header = $request->getHeaderLine('Authorization');

        if ($header === '' || ! str_starts_with($header, 'HMAC-SHA256 ')) {
            $this->error = 'No HMAC authorization header';

            return false;
        }

        $token = trim(substr($header, 11));

        $parts = explode(':', $token);

        if (count($parts) !== 2) {
            $this->error = 'Invalid HMAC token format';

            return false;
        }

        [$key, $signature] = $parts;

        $body = (string) $request->getBody();

        return $this->verify($key, $signature, $body);
    }

    public function verify(string $key, string $signature, string $body): bool
    {
        $identityModel = model(UserIdentityModel::class);

        $row = $identityModel->asObject()
            ->where('type', 'hmac_sha256')
            ->where('secret', $key)
            ->first();

        if ($row === null) {
            $this->error = 'Invalid HMAC key';

            return false;
        }

        $expected = hash_hmac('sha256', $body, $row->secret2);

        if (! hash_equals($expected, $signature)) {
            $this->error = 'Invalid HMAC signature';

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
        return ['hmac', 'hmac_sha256'];
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
}
