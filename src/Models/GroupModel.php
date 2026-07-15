<?php

declare(strict_types=1);

namespace exAuth\Models;

use CodeIgniter\Model;
use exAuth\Entities\Group;

class GroupModel extends Model
{
    protected $table         = 'auth_groups';
    protected $returnType    = Group::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'name',
        'description',
        'default',
        'permissions',
    ];

    public function findByName(string $name): ?array
    {
        return $this->where('name', $name)->first();
    }

    public function findByUserId(int $userId): array
    {
        return $this->db->table('auth_users_groups')
            ->select('auth_groups.*')
            ->join('auth_groups', 'auth_groups.id = auth_users_groups.group_id')
            ->where('auth_users_groups.user_id', $userId)
            ->get()
            ->getResultArray();
    }
}
