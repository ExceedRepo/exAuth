<?php

declare(strict_types=1);

namespace exAuth\Entities;

use CodeIgniter\Entity\Entity;

class AccessToken extends Entity
{
    protected array $casts = [
        'id'           => '?integer',
        'token'        => 'string',
        'name'         => 'string',
        'description'  => '?string',
        'permissions'  => 'array',
        'user_id'      => 'int',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    protected array $dates = [
        'last_used_at',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    public function permissionsExpanded(): array
    {
        $perms = $this->permissions ?? [];

        if (in_array('*', $perms, true)) {
            return ['*'];
        }

        return $perms;
    }
}
