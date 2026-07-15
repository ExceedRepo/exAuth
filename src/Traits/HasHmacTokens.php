<?php

declare(strict_types=1);

namespace exAuth\Traits;

use exAuth\Models\UserIdentityModel;

trait HasHmacTokens
{
    public function getHmacKeys(): array
    {
        $identityModel = model(UserIdentityModel::class);

        return $identityModel->where('user_id', $this->id)
            ->where('type', 'hmac_sha256')
            ->findAll();
    }

    public function createHmacKey(): \exAuth\Entities\AccessToken
    {
        $identityModel = model(UserIdentityModel::class);

        return $identityModel->generateHmacToken($this);
    }

    public function deleteHmacKey(int $id): bool
    {
        $identityModel = model(UserIdentityModel::class);

        $existing = $identityModel->where('user_id', $this->id)
            ->where('id', $id)
            ->where('type', 'hmac_sha256')
            ->first();

        if ($existing === null) {
            return false;
        }

        return (bool) $identityModel->delete($id);
    }
}
