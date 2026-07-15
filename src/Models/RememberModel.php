<?php

declare(strict_types=1);

namespace exAuth\Models;

use CodeIgniter\Model;

class RememberModel extends Model
{
    protected $table         = 'auth_remember_tokens';
    protected $returnType    = 'object';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'user_id',
        'selector',
        'hash',
        'expires',
    ];

    public function findToken(string $selector): ?array
    {
        return $this->where('selector', $selector)->first();
    }

    public function deleteToken(string $selector): void
    {
        $this->where('selector', $selector)->delete();
    }

    public function deleteUserTokens(int $userId): void
    {
        $this->where('user_id', $userId)->delete();
    }
}
