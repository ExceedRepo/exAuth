<?php

declare(strict_types=1);

namespace Tests\Controllers;

use CodeIgniter\Test\FeatureTestTrait;
use exAuth\Models\UserModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class JWTFlowTest extends TestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $routes = service('routes');
        service('auth')->jwtRoutes($routes);

        model(UserModel::class)->save([
            'email'    => 'john@example.com',
            'username' => 'johndoe',
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
            'active'   => 1,
        ]);
    }

    private function issueToken(string $login = 'john@example.com', string $password = 'secret123'): string
    {
        $result = $this->post('api/auth/token', [
            'login'    => $login,
            'password' => $password,
        ]);

        $result->assertStatus(200);
        $json = json_decode($result->getJSON(), true);

        return $json['token'];
    }

    public function testTokenEndpointIssuesToken(): void
    {
        $result = $this->post('api/auth/token', [
            'login'    => 'john@example.com',
            'password' => 'secret123',
        ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['token_type' => 'Bearer']);
        $json = json_decode($result->getJSON(), true);
        $this->assertNotEmpty($json['token']);
    }

    public function testTokenEndpointRejectsWrongPassword(): void
    {
        $result = $this->post('api/auth/token', [
            'login'    => 'john@example.com',
            'password' => 'wrongpass',
        ]);

        $result->assertStatus(401);
    }

    public function testLoginWithUsername(): void
    {
        $token = $this->issueToken('johndoe');
        $this->assertNotEmpty($token);
    }

    public function testProtectedRouteWithValidToken(): void
    {
        $token = $this->issueToken();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/auth/me');

        $result->assertStatus(200);
        $result->assertJSONFragment([
            'email'    => 'john@example.com',
            'username' => 'johndoe',
        ]);
    }

    public function testProtectedRouteWithoutTokenIsRejected(): void
    {
        $result = $this->get('api/auth/me');

        $result->assertStatus(401);
    }

    public function testProtectedRouteWithInvalidTokenIsRejected(): void
    {
        $result = $this->withHeaders(['Authorization' => 'Bearer not.a.valid.token'])
            ->get('api/auth/me');

        $result->assertStatus(401);
    }

    public function testRefreshReturnsNewToken(): void
    {
        $token = $this->issueToken();

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('api/auth/refresh');

        $result->assertStatus(200);
        $json = json_decode($result->getJSON(), true);
        $this->assertNotEmpty($json['token']);
    }
}
