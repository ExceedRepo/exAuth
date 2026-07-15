<?php

declare(strict_types=1);

namespace exAuth\Models;

use CodeIgniter\Model;
use exAuth\Entities\UserIdentity;

class UserIdentityModel extends Model
{
    protected $table         = 'auth_identities';
    protected $returnType    = UserIdentity::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'user_id',
        'type',
        'name',
        'secret',
        'secret2',
        'extras',
        'force_reset',
        'last_used_at',
        'expires_at',
    ];

    public function getIdentityForUser(int $userId, string $type): ?array
    {
        return $this->where('user_id', $userId)
            ->where('type', $type)
            ->first();
    }

    public function deleteIdentitiesByType(int $userId, string $type): void
    {
        $this->where('user_id', $userId)
            ->where('type', $type)
            ->delete();
    }
}
