<?php

declare(strict_types=1);

namespace exAuth\Models;

use CodeIgniter\Model;
use exAuth\Entities\Login;

class LoginModel extends Model
{
    protected $table         = 'auth_logins';
    protected $returnType    = Login::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'user_id',
        'ip_address',
        'user_agent',
        'email',
        'date',
        'success',
    ];

    public function recentLoginCount(int $userId, int $hours): int
    {
        $timeAgo = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        return $this->where('user_id', $userId)
            ->where('date >=', $timeAgo)
            ->countAllResults();
    }
}
