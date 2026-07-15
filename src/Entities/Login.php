<?php

declare(strict_types=1);

namespace exAuth\Entities;

use CodeIgniter\Entity\Entity;

class Login extends Entity
{
    protected array $casts = [
        'id'         => 'int',
        'user_id'    => 'int',
        'ip_address' => 'string',
        'user_agent' => 'string',
        'email'      => 'string',
        'date'       => 'datetime',
        'success'    => 'int-bool',
    ];

    protected array $dates = [
        'date',
    ];
}
