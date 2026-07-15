<?php

declare(strict_types=1);

namespace exAuth\Traits;

use exAuth\Models\UserIdentityModel;

trait HasAccessTokens
{
    public function getAccessTokens(): array
    {
        $identityModel = model(UserIdentityModel::class);

        return $identityModel->where('user_id', $this->id)
            ->where('type', 'access_token')
            ->findAll();
    }

    public function createAccessToken(string $name, array $scopes = ['*']): \exAuth\Entities\AccessToken
    {
        $identityModel = model(UserIdentityModel::class);

        $token = $identityModel->generateAccessToken($this, $name, $scopes);

        return $token;
    }

    public function revokeAccessToken(int $id): bool
    {
        $identityModel = model(UserIdentityModel::class);

        $existing = $identityModel->where('user_id', $this->id)
            ->where('id', $id)
            ->where('type', 'access_token')
            ->first();

        if ($existing === null) {
            return false;
        }

        return (bool) $identityModel->delete($id);
    }

    public function revokeAllAccessTokens(): void
    {
        model(UserIdentityModel::class)->deleteIdentitiesByType((int) $this->id, 'access_token');
    }
}
