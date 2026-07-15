<?php

declare(strict_types=1);

namespace Tests\Authentication;

use exAuth\Authentication\Authenticators\AccessTokens;
use exAuth\Models\UserIdentityModel;
use exAuth\Models\UserModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class AccessTokenTest extends TestCase
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
        $user  = $this->makeUser();
        $token = $user->createAccessToken('My App');

        $this->assertNotEmpty($token->token);
        $this->assertSame(64, strlen($token->token));

        $auth = new AccessTokens();
        $this->assertTrue($auth->verify($token->token));
        $this->assertSame((int) $user->id, $auth->getUserId());
    }

    public function testRawTokenIsHashedInDatabase(): void
    {
        $user  = $this->makeUser();
        $token = $user->createAccessToken('My App');

        $row = model(UserIdentityModel::class)
            ->where('type', 'access_token')
            ->first();

        $this->assertNotSame($token->token, $row->secret);
        $this->assertSame(hash('sha256', $token->token), $row->secret);
    }

    public function testInvalidTokenFails(): void
    {
        $this->makeUser();

        $auth = new AccessTokens();
        $this->assertFalse($auth->verify('not-a-real-token'));
        $this->assertFalse($auth->verify(''));
    }

    public function testScopes(): void
    {
        $user  = $this->makeUser();
        $token = $user->createAccessToken('Limited', ['posts.read', 'posts.write']);

        $auth = new AccessTokens();
        $auth->verify($token->token);

        $this->assertTrue($auth->tokenCan('posts.read'));
        $this->assertTrue($auth->tokenCan('posts.write'));
        $this->assertFalse($auth->tokenCan('users.delete'));
    }

    public function testWildcardScope(): void
    {
        $user  = $this->makeUser();
        $token = $user->createAccessToken('Full'); // defaults to ['*']

        $auth = new AccessTokens();
        $auth->verify($token->token);

        $this->assertTrue($auth->tokenCan('anything.at.all'));
    }

    public function testRevoke(): void
    {
        $user  = $this->makeUser();
        $token = $user->createAccessToken('Temp');

        $tokens = $user->getAccessTokens();
        $this->assertCount(1, $tokens);

        $this->assertTrue($user->revokeAccessToken((int) $tokens[0]->id));

        $auth = new AccessTokens();
        $this->assertFalse($auth->verify($token->token));
    }
}
