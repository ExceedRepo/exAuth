<?php

declare(strict_types=1);

namespace exAuth\Traits;

trait Bannable
{
    public function ban(string $reason = ''): void
    {
        $this->status = 'banned';
        $this->status_message = $reason;

        $users = model(\exAuth\Models\UserModel::class);

        $users->save($this);
    }

    public function unban(): void
    {
        $this->status = null;
        $this->status_message = null;

        $users = model(\exAuth\Models\UserModel::class);

        $users->save($this);
    }

    public function isBanned(): bool
    {
        return ($this->status ?? '') === 'banned';
    }
}
