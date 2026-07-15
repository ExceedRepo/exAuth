<?php

declare(strict_types=1);

namespace exAuth\Models;

use CodeIgniter\Model;

class GroupPermissionModel extends Model
{
    protected $table         = 'auth_users_groups';
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'group_id',
        'user_id',
    ];

    public function getUserGroups(int $userId): array
    {
        return $this->where('user_id', $userId)->findAll();
    }

    public function removeUserFromAllGroups(int $userId): void
    {
        $this->where('user_id', $userId)->delete();
    }

    public function addUserToGroup(int $userId, int $groupId): void
    {
        $this->insert([
            'user_id'  => $userId,
            'group_id' => $groupId,
        ]);
    }
}
