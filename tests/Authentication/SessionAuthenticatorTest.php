<?php

declare(strict_types=1);

namespace Tests\Authentication;

use exAuth\Authentication\Authenticators\Session;
use exAuth\Entities\User;
use exAuth\Models\UserModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class SessionAuthenticatorTest extends TestCase
{
    private function createUser(): int
    {
        $model = model(UserModel::class);
        $model->save([
            'email'    => 'john@example.com',
            'username' => 'johndoe',
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
            'active'   => 1,
        ]);

        return $model->getInsertID();
    }

    public function testLoginSetsSessionAndHelpersSeeIt(): void
    {
        helper('exAuth');

        $userId = $this->createUser();

        $this->assertFalse(ex_logged_in());

        $user     = new User();
        $user->id = $userId;
        (new Session())->login($user);

        $this->assertTrue(ex_logged_in());
        $this->assertSame($userId, ex_user_id());

        $current = ex_current_user();
        $this->assertNotNull($current);
        $this->assertSame('johndoe', $current->username);
    }

    public function testLogoutClearsSession(): void
    {
        helper('exAuth');

        $userId   = $this->createUser();
        $user     = new User();
        $user->id = $userId;
        (new Session())->login($user);

        $this->assertTrue(ex_logged_in());

        ex_logout();

        $this->assertFalse(ex_logged_in());
        $this->assertNull(ex_user_id());
        $this->assertNull(ex_current_user());
    }
}
