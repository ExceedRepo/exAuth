<?php

declare(strict_types=1);

namespace exAuth\Entities;

use CodeIgniter\Entity\Entity;

class UserIdentity extends Entity
{
    protected $casts = [
        'id'           => '?integer',
        'user_id'      => '?integer',
        'type'         => 'string',
        'name'         => 'string',
        'secret'       => '?string',
        'secret2'      => '?string',
        'extras'       => '?string',
        'force_reset'  => 'int-bool',
        'last_used_at' => '?string',
        'expires_at'   => '?string',
        'createdAt'    => 'datetime',
        'updatedAt'    => 'datetime',
        'deletedAt'    => 'datetime',
    ];

    protected $dates = [
        'last_used_at',
        'expires_at',
        'createdAt',
        'updatedAt',
        'deletedAt',
    ];
}
