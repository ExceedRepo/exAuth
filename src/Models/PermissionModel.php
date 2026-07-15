<?php

declare(strict_types=1);

namespace exAuth\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table         = 'auth_permissions';
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    public function getPermissionsForUser(int $userId): array
    {
        return $this->db->table('auth_users_groups')
            ->select('auth_permissions.*')
            ->join('auth_group_permissions', 'auth_group_permissions.group_id = auth_users_groups.group_id')
            ->join('auth_permissions', 'auth_permissions.id = auth_group_permissions.permission_id')
            ->where('auth_users_groups.user_id', $userId)
            ->get()
            ->getResultArray();
    }

    public function setUserPermissions(int $userId, array $groupIds): void
    {
        $this->db->table('auth_users_groups')
            ->where('user_id', $userId)
            ->delete();

        $data = [];
        foreach ($groupIds as $groupId) {
            $data[] = [
                'user_id'  => $userId,
                'group_id' => $groupId,
            ];
        }

        if ($data !== []) {
            $this->db->table('auth_users_groups')->insertBatch($data);
        }
    }

    public function getPermissionsForGroup(int $groupId): array
    {
        return $this->db->table('auth_group_permissions')
            ->select('auth_permissions.*')
            ->join('auth_permissions', 'auth_permissions.id = auth_group_permissions.permission_id')
            ->where('auth_group_permissions.group_id', $groupId)
            ->get()
            ->getResultArray();
    }
}
