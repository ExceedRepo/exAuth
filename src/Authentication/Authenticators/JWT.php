<?php

declare(strict_types=1);

namespace exAuth\Authentication\Authenticators;

use exAuth\Authentication\AuthenticatorInterface;

class JWT implements AuthenticatorInterface
{
    private string $error = '';

    private ?int $userId = null;

    protected string $secretKey = '';

    protected string $algorithm = 'HS256';

    public function __construct()
    {
        $this->secretKey = setting('AuthJWT.secretKey') ?? 'exAuth-default-secret';

        $algo = setting('AuthJWT.algorithm');

        if ($algo !== null) {
            $this->algorithm = $algo;
        }
    }

    public function authenticate(\CodeIgniter\HTTP\Request $request): bool
    {
        $header = $request->getHeaderLine('Authorization');

        if ($header === '' || ! str_starts_with($header, 'Bearer ')) {
            $this->error = 'No JWT token provided';

            return false;
        }

        $token = trim(substr($header, 7));

        return $this->verify($token);
    }

    public function verify(?string $token = null): bool
    {
        if ($token === null || $token === '') {
            $this->error = 'No JWT token provided';

            return false;
        }

        try {
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($this->secretKey, $this->algorithm));

            $this->userId = (int) ($decoded->sub ?? 0);

            if ($this->userId === 0) {
                $this->error = 'Invalid JWT payload: missing subject';

                return false;
            }

            return true;
        } catch (\Firebase\JWT\BeforeValidException $e) {
            $this->error = 'JWT token is not yet valid';

            return false;
        } catch (\Firebase\JWT\ExpiredException $e) {
            $this->error = 'JWT token has expired';

            return false;
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            $this->error = 'JWT signature is invalid';

            return false;
        } catch (\UnexpectedValueException $e) {
            $this->error = 'Invalid JWT token';

            return false;
        }
    }

    public function refresh(string $token, int $ttl = 3600): string|null
    {
        try {
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($this->secretKey, $this->algorithm));

            $payload = (array) $decoded;

            $payload['iat'] = time();
            $payload['exp'] = time() + $ttl;

            return \Firebase\JWT\JWT::encode($payload, $this->secretKey, $this->algorithm);
        } catch (\Exception $e) {
            $this->error = 'Cannot refresh JWT: ' . $e->getMessage();

            return null;
        }
    }

    public function getUserId(): int|null
    {
        return $this->userId;
    }

    public function supports(): array
    {
        return ['jwt', 'bearer'];
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
