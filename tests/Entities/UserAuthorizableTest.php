<?php

declare(strict_types=1);

namespace Tests\Entities;

use exAuth\Models\UserModel;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class UserAuthorizableTest extends TestCase
{
    private function makeUserInGroup(string $group): int
    {
        $model = model(UserModel::class);
        $model->save([
            'email'    => 'admin@example.com',
            'username' => 'adminuser',
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
            'active'   => 1,
        ]);
        $userId = $model->getInsertID();

        $this->db->table('auth_groups_users')->insert([
            'user_id'  => $userId,
            'group_id' => $group,
        ]);

        return $userId;
    }

    public function testInGroup(): void
    {
        $userId = $this->makeUserInGroup('admin');
        $user   = model(UserModel::class)->findUserById($userId);

        $this->assertTrue($user->inGroup('admin'));
        $this->assertFalse($user->inGroup('editor'));
        $this->assertTrue($user->inGroup(['editor', 'admin']));
    }

    public function testCanWithGroupWildcardPermissions(): void
    {
        $userId = $this->makeUserInGroup('admin'); // admin => ['admin.*', 'users.*']
        $user   = model(UserModel::class)->findUserById($userId);

        $this->assertTrue($user->can('users.create'));   // via users.*
        $this->assertTrue($user->can('admin.settings')); // via admin.*
        $this->assertFalse($user->can('content.read'));  // admin has no content perms
    }

    public function testCanWithDirectUserPermission(): void
    {
        $userId = $this->makeUserInGroup('user'); // user => ['profile.*', 'content.read']
        $this->db->table('auth_permissions_users')->insert([
            'user_id'    => $userId,
            'permission' => 'reports.view',
        ]);

        $user = model(UserModel::class)->findUserById($userId);

        $this->assertTrue($user->can('reports.view')); // direct
        $this->assertTrue($user->can('content.read')); // via group
        $this->assertFalse($user->can('admin.settings'));
    }

    public function testGuestUserHasNoGroupsOrPermissions(): void
    {
        $model = model(UserModel::class);
        $model->save([
            'email'    => 'nobody@example.com',
            'username' => 'nobody',
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
            'active'   => 1,
        ]);
        $user = $model->findUserById($model->getInsertID());

        $this->assertSame([], $user->getGroups());
        $this->assertFalse($user->can('anything.here'));
    }
}
