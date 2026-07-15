<?php

declare(strict_types=1);

namespace Tests\Authentication;

use exAuth\Authentication\Authenticators\HmacSha256;
use exAuth\Models\UserModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class HmacTest extends TestCase
{
    private function makeUser(): object
    {
        $model = model(UserModel::class);
        $model->save([
            'email'    => 'john@example.com',
            'username' => 'johndoe',
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
            'active'   => 1,
        ]);

        return $model->findUserById($model->getInsertID());
    }

    public function testGenerateAndVerify(): void
    {
        $user = $this->makeUser();
        $cred = $user->createHmacKey();

        $this->assertNotEmpty($cred->token);   // public key
        $this->assertNotEmpty($cred->secret);  // shared secret

        $body      = '{"hello":"world"}';
        $signature = HmacSha256::sign($body, $cred->secret);

        $auth = new HmacSha256();
        $this->assertTrue($auth->verify($cred->token, $signature, $body));
        $this->assertSame((int) $user->id, $auth->getUserId());
    }

    public function testWrongSignatureFails(): void
    {
        $user = $this->makeUser();
        $cred = $user->createHmacKey();

        $auth = new HmacSha256();
        $this->assertFalse($auth->verify($cred->token, 'deadbeef', '{"hello":"world"}'));
        $this->assertStringContainsStringIgnoringCase('signature', $auth->getErrorMessage());
    }

    public function testUnknownKeyFails(): void
    {
        $this->makeUser();

        $auth = new HmacSha256();
        $this->assertFalse($auth->verify('unknown-key', 'sig', 'body'));
        $this->assertStringContainsStringIgnoringCase('key', $auth->getErrorMessage());
    }

    public function testTamperedBodyFails(): void
    {
        $user = $this->makeUser();
        $cred = $user->createHmacKey();

        $signature = HmacSha256::sign('original body', $cred->secret);

        $auth = new HmacSha256();
        $this->assertFalse($auth->verify($cred->token, $signature, 'tampered body'));
    }
}
