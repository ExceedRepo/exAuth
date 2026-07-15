<?php

declare(strict_types=1);

namespace exAuth\Entities;

use CodeIgniter\Entity\Entity;
use DateTime;

class User extends Entity
{
    protected array $attributes = [
        'id'             => null,
        'email'          => null,
        'username'       => null,
        'password'       => null,
        'first_name'     => null,
        'last_name'      => null,
        'active'         => false,
        'status'         => null,
        'status_message' => null,
        'last_login'     => null,
        'created_at'     => null,
        'updated_at'     => null,
        'deleted_at'     => null,
    ];

    protected array $casts = [
        'id'             => '?integer',
        'email'          => '?string',
        'username'       => '?string',
        'password'       => '?string',
        'first_name'     => '?string',
        'last_name'      => '?string',
        'active'         => 'bool',
        'status'         => '?string',
        'status_message' => '?string',
        'last_login'     => '?datetime',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        'deleted_at'     => 'datetime',
    ];

    protected array $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'last_login',
    ];

    public function getHasRememberToken(): bool
    {
        return $this->getIdentity('remember_token') !== null;
    }

    public function setHasRememberToken(bool $value): void
    {
        $this->attributes['has_remember_token'] = $value;
    }

    public function getHasAccessToken(): bool
    {
        return $this->getIdentity('access_token') !== null;
    }

    public function setHasAccessToken(bool $value): void
    {
        $this->attributes['has_access_token'] = $value;
    }

    public function getHasHTokens(): bool
    {
        return $this->getIdentity('hmac_token') !== null;
    }

    public function setHasHTokens(bool $value): void
    {
        $this->attributes['has_h_tokens'] = $value;
    }

    public function getHasSshKey(): bool
    {
        return $this->getIdentity('ssh_key') !== null;
    }

    public function setHasSshKey(bool $value): void
    {
        $this->attributes['has_ssh_key'] = $value;
    }

    public function shouldAddEmail(string $email): bool
    {
        if ($this->email === null || $this->email === '') {
            return true;
        }

        if ($this->email !== $email) {
            return true;
        }

        return false;
    }

    private function getIdentity(string $type): ?object
    {
        return null;
    }
}
