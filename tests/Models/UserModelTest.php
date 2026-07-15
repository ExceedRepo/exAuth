<?php

declare(strict_types=1);

namespace Tests\Models;

use exAuth\Models\UserModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class UserModelTest extends TestCase
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

    public function testGetUserByEmailReturnsArray(): void
    {
        $this->createUser();

        $user = model(UserModel::class)->getUserByEmail('john@example.com');

        $this->assertIsArray($user);
        $this->assertSame('johndoe', $user['username']);
        $this->assertArrayHasKey('password', $user);
    }

    public function testGetUserByUsernameReturnsArray(): void
    {
        $this->createUser();

        $user = model(UserModel::class)->getUserByUsername('johndoe');

        $this->assertIsArray($user);
        $this->assertSame('john@example.com', $user['email']);
    }

    public function testUnknownUserReturnsNull(): void
    {
        $this->assertNull(model(UserModel::class)->getUserByEmail('nobody@example.com'));
        $this->assertNull(model(UserModel::class)->getUserByUsername('nobody'));
    }

    public function testStoredPasswordVerifies(): void
    {
        $this->createUser();

        $user = model(UserModel::class)->getUserByEmail('john@example.com');

        $this->assertTrue(password_verify('secret123', $user['password']));
    }
}
