<?php

declare(strict_types=1);

namespace Tests\Controllers;

use CodeIgniter\Test\FeatureTestTrait;
use exAuth\Authentication\Authenticators\HmacSha256;
use exAuth\Models\UserModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class TokenHmacFlowTest extends TestCase
{
    use FeatureTestTrait;

    private object $user;

    protected function setUp(): void
    {
        parent::setUp();

        $routes = service('routes');
        $ctrl   = '\Tests\Support\Controllers\SecureController';
        $routes->get('test/token', $ctrl . '::token', ['filter' => 'tokens']);
        $routes->get('test/token-scoped', $ctrl . '::token', ['filter' => 'tokens:posts.read']);
        $routes->post('test/hmac', $ctrl . '::hmac', ['filter' => 'hmac']);

        $model = model(UserModel::class);
        $model->save([
            'email'    => 'john@example.com',
            'username' => 'johndoe',
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
            'active'   => 1,
        ]);
        $this->user = $model->findUserById($model->getInsertID());
    }

    public function testAccessTokenGrantsAccess(): void
    {
        $token = $this->user->createAccessToken('My App');

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token->token])
            ->get('test/token');

        $result->assertStatus(200);
        $result->assertJSONFragment(['user_id' => (int) $this->user->id]);
    }

    public function testMissingTokenIsRejected(): void
    {
        $this->get('test/token')->assertStatus(401);
    }

    public function testInvalidTokenIsRejected(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer wrong'])
            ->get('test/token');

        $result->assertStatus(401);
    }

    public function testScopeEnforcedAllowed(): void
    {
        $token = $this->user->createAccessToken('Scoped', ['posts.read']);

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token->token])
            ->get('test/token-scoped');

        $result->assertStatus(200);
    }

    public function testScopeEnforcedDenied(): void
    {
        $token = $this->user->createAccessToken('Scoped', ['users.read']);

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token->token])
            ->get('test/token-scoped');

        $result->assertStatus(403);
    }

    public function testHmacGrantsAccess(): void
    {
        $cred = $this->user->createHmacKey();
        $body = '{"ping":"pong"}';
        $sig  = HmacSha256::sign($body, $cred->secret);

        $result = $this->withHeaders(['Authorization' => 'HMAC-SHA256 ' . $cred->token . ':' . $sig])
            ->withBody($body)
            ->post('test/hmac');

        $result->assertStatus(200);
        $result->assertJSONFragment(['user_id' => (int) $this->user->id]);
    }

    public function testHmacWrongSignatureRejected(): void
    {
        $cred = $this->user->createHmacKey();
        $body = '{"ping":"pong"}';

        $result = $this->withHeaders(['Authorization' => 'HMAC-SHA256 ' . $cred->token . ':deadbeef'])
            ->withBody($body)
            ->post('test/hmac');

        $result->assertStatus(401);
    }
}
