<?php declare(strict_types=1);
namespace exAuth\Config;

class AuthGroups
{
    public array $groups = [
        'superadmin' => [
            'description' => 'Super Administrator',
            'permissions' => ['*'],
        ],
        'admin' => [
            'description' => 'Administrator',
            'permissions' => ['admin.*', 'users.*'],
        ],
        'editor' => [
            'description' => 'Editor',
            'permissions' => ['content.*', 'media.*'],
        ],
        'user' => [
            'description' => 'Default User',
            'permissions' => ['profile.*', 'content.read'],
        ],
        'guest' => [
            'description' => 'Guest',
            'permissions' => ['content.read'],
        ],
    ];

    public array $permissions = [
        'users.*'           => 'Manage all user operations',
        'users.create'      => 'Create users',
        'users.read'        => 'Read user data',
        'users.update'      => 'Update users',
        'users.delete'      => 'Delete users',
        'admin.*'           => 'Admin operations',
        'content.*'         => 'All content operations',
        'content.read'      => 'Read content',
        'content.write'     => 'Write content',
        'content.delete'    => 'Delete content',
        'media.*'           => 'Media management',
        'media.upload'      => 'Upload media',
        'media.delete'      => 'Delete media',
        'profile.*'         => 'Profile management',
        'profile.read'      => 'Read own profile',
        'profile.update'    => 'Update own profile',
    ];

    public string $defaultGroup = 'user';
}
