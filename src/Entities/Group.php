<?php

declare(strict_types=1);

namespace exAuth\Entities;

use CodeIgniter\Entity\Entity;

class Group extends Entity
{
    protected array $permissions = [];

    protected array $casts = [
        'id'          => 'int',
        'name'        => 'string',
        'description' => 'string',
        'default'     => 'bool',
    ];
}
