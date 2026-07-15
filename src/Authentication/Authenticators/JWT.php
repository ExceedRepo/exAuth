<?php

declare(strict_types=1);

namespace exAuth\Authentication\Authenticators;

use exAuth\Authentication\AuthenticatorInterface;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use UnexpectedValueException;

class JWT implements AuthenticatorInterface
{
    private string $error = '';

    private ?int $userId = null;

    private ?object $payload = null;

    protected string $secretKey;

    protected string $algorithm;

    protected int $ttl;

    protected string $issuer;

    protected string $audience;

    public function __construct()
    {
        $config = config('AuthJWT');

        $this->secretKey = $config->secretKey;
        $this->algorithm = $config->algorithm;
        $this->ttl       = $config->timeToLive;
        $this->issuer    = $config->issuer;
        $this->audience  = $config->audience;

        if (str_starts_with($this->algorithm, 'HS') && strlen($this->secretKey) < 32) {
            throw new \RuntimeException(
                'AuthJWT secretKey must be at least 32 characters for the ' . $this->algorithm
                . ' algorithm. Set a longer JWT_SECRET in your .env file.',
            );
        }
    }

    /**
     * Issues a signed JWT for the given user id.
     *
     * @param array<string, mixed> $claims Extra claims to embed (e.g. email, username).
     */
    public function generateToken(int $userId, array $claims = [], ?int $ttl = null): string
    {
        $now = time();
        $ttl ??= $this->ttl;

        $payload = array_merge($claims, [
            'sub' => $userId,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
        ]);

        if ($this->issuer !== '') {
            $payload['iss'] = $this->issuer;
        }
        if ($this->audience !== '') {
            $payload['aud'] = $this->audience;
        }

        return FirebaseJWT::encode($payload, $this->secretKey, $this->algorithm);
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
            $decoded = FirebaseJWT::decode($token, new Key($this->secretKey, $this->algorithm));

            $userId = (int) ($decoded->sub ?? 0);

            if ($userId === 0) {
                $this->error = 'Invalid JWT payload: missing subject';

                return false;
            }

            if ($this->issuer !== '' && ($decoded->iss ?? null) !== $this->issuer) {
                $this->error = 'Invalid JWT issuer';

                return false;
            }

            $this->userId  = $userId;
            $this->payload = $decoded;

            return true;
        } catch (BeforeValidException $e) {
            $this->error = 'JWT token is not yet valid';

            return false;
        } catch (ExpiredException $e) {
            $this->error = 'JWT token has expired';

            return false;
        } catch (SignatureInvalidException $e) {
            $this->error = 'JWT signature is invalid';

            return false;
        } catch (UnexpectedValueException $e) {
            $this->error = 'Invalid JWT token';

            return false;
        }
    }

    public function refresh(string $token, ?int $ttl = null): string|null
    {
        try {
            $decoded = FirebaseJWT::decode($token, new Key($this->secretKey, $this->algorithm));

            $payload = (array) $decoded;

            $now = time();
            $payload['iat'] = $now;
            $payload['nbf'] = $now;
            $payload['exp'] = $now + ($ttl ?? $this->ttl);

            return FirebaseJWT::encode($payload, $this->secretKey, $this->algorithm);
        } catch (\Exception $e) {
            $this->error = 'Cannot refresh JWT: ' . $e->getMessage();

            return null;
        }
    }

    public function getUserId(): int|null
    {
        return $this->userId;
    }

    public function getPayload(): ?object
    {
        return $this->payload;
    }

    public function supports(): array
    {
        return ['jwt', 'bearer'];
    }

    public function logout(): void
    {
        $this->userId  = null;
        $this->payload = null;
        $this->error   = '';
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
