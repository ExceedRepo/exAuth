<?php

declare(strict_types=1);

namespace exAuth\Models;

use CodeIgniter\Model;
use exAuth\Entities\AccessToken;
use exAuth\Entities\User;
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

    public const TYPE_ACCESS_TOKEN = 'access_token';
    public const TYPE_HMAC         = 'hmac_sha256';

    public function getIdentityForUser(int $userId, string $type): ?array
    {
        return $this->asArray()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->first();
    }

    public function deleteIdentitiesByType(int $userId, string $type): void
    {
        $this->where('user_id', $userId)
            ->where('type', $type)
            ->delete();
    }

    /**
     * Creates a personal access token for the user. The raw token is only
     * available on the returned entity (as `token`) — the DB stores its hash.
     *
     * @param array<int, string> $scopes
     */
    public function generateAccessToken(User $user, string $name, array $scopes = ['*']): AccessToken
    {
        $rawToken = bin2hex(random_bytes(32)); // 64-char secret

        $this->insert([
            'user_id' => $user->id,
            'type'    => self::TYPE_ACCESS_TOKEN,
            'name'    => $name,
            'secret'  => hash('sha256', $rawToken),
            'extras'  => json_encode(array_values($scopes)),
        ]);

        return new AccessToken([
            'id'          => $this->getInsertID(),
            'user_id'     => $user->id,
            'name'        => $name,
            'token'       => $rawToken,
            'permissions' => $scopes,
        ]);
    }

    /**
     * Finds an access token identity by its raw token value.
     */
    public function findByAccessToken(string $rawToken): ?object
    {
        return $this->asObject()
            ->where('type', self::TYPE_ACCESS_TOKEN)
            ->where('secret', hash('sha256', $rawToken))
            ->first();
    }

    /**
     * Creates an HMAC key pair for the user. The shared secret is only available
     * on the returned entity (as `secret`); the public `key` is stored and used
     * to look the credential up.
     */
    public function generateHmacToken(User $user, string $name = 'hmac'): AccessToken
    {
        $key    = bin2hex(random_bytes(16));
        $secret = bin2hex(random_bytes(32));

        $this->insert([
            'user_id' => $user->id,
            'type'    => self::TYPE_HMAC,
            'name'    => $name,
            'secret'  => $key,
            'secret2' => $secret,
        ]);

        return new AccessToken([
            'id'      => $this->getInsertID(),
            'user_id' => $user->id,
            'name'    => $name,
            'token'   => $key,
            'secret'  => $secret,
        ]);
    }

    public function findByHmacKey(string $key): ?object
    {
        return $this->asObject()
            ->where('type', self::TYPE_HMAC)
            ->where('secret', $key)
            ->first();
    }

    public function touchLastUsed(int $identityId): void
    {
        $this->update($identityId, ['last_used_at' => date('Y-m-d H:i:s')]);
    }
}
