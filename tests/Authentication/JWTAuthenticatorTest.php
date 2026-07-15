<?php

declare(strict_types=1);

namespace Tests\Authentication;

use CodeIgniter\Config\Factories;
use exAuth\Authentication\Authenticators\JWT;
use exAuth\Config\AuthJWT;
use RuntimeException;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class JWTAuthenticatorTest extends TestCase
{
    protected $migrate = false;

    private function config(string $secret = 'this-is-a-sufficiently-long-secret-key-123'): void
    {
        $config            = new AuthJWT();
        $config->secretKey = $secret;
        $config->issuer    = '';
        $config->audience  = '';
        Factories::injectMock('config', 'AuthJWT', $config);
    }

    public function testGenerateAndVerify(): void
    {
        $this->config();

        $token = (new JWT())->generateToken(42, ['email' => 'a@b.com', 'username' => 'abc']);

        $jwt = new JWT();
        $this->assertTrue($jwt->verify($token));
        $this->assertSame(42, $jwt->getUserId());
        $this->assertSame('a@b.com', $jwt->getPayload()->email);
        $this->assertSame('abc', $jwt->getPayload()->username);
    }

    public function testTamperedTokenFails(): void
    {
        $this->config();

        $token = (new JWT())->generateToken(1);

        $jwt = new JWT();
        $this->assertFalse($jwt->verify($token . 'tampered'));
        $this->assertStringContainsStringIgnoringCase('invalid', $jwt->getErrorMessage());
    }

    public function testExpiredTokenFails(): void
    {
        $this->config();

        $token = (new JWT())->generateToken(1, [], -10);

        $jwt = new JWT();
        $this->assertFalse($jwt->verify($token));
        $this->assertStringContainsStringIgnoringCase('expired', $jwt->getErrorMessage());
    }

    public function testEmptyTokenFails(): void
    {
        $this->config();

        $jwt = new JWT();
        $this->assertFalse($jwt->verify(''));
        $this->assertFalse($jwt->verify(null));
    }

    public function testWrongSecretFailsSignature(): void
    {
        $this->config('secret-number-one-that-is-long-enough-123');
        $token = (new JWT())->generateToken(5);

        $this->config('secret-number-two-that-is-long-enough-456');
        $jwt = new JWT();
        $this->assertFalse($jwt->verify($token));
    }

    public function testRefreshProducesUsableToken(): void
    {
        $this->config();

        $original = (new JWT())->generateToken(7);
        $refreshed = (new JWT())->refresh($original);

        $this->assertIsString($refreshed);

        $jwt = new JWT();
        $this->assertTrue($jwt->verify($refreshed));
        $this->assertSame(7, $jwt->getUserId());
    }

    public function testShortSecretThrows(): void
    {
        $this->config('too-short');

        $this->expectException(RuntimeException::class);
        new JWT();
    }
}
