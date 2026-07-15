<?php

declare(strict_types=1);

namespace exAuth\Models;

use CodeIgniter\Model;
use exAuth\Entities\User;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $returnType    = User::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'email',
        'username',
        'password',
        'first_name',
        'last_name',
        'active',
        'status',
        'status_message',
        'last_login',
    ];

    public function getUserByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    public function getUserByUsername(string $username): ?array
    {
        return $this->where('username', $username)->first();
    }

    public function findUserById(int $id): ?User
    {
        return $this->find($id);
    }
}
