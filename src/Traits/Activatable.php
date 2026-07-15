<?php

declare(strict_types=1);

namespace exAuth\Traits;

trait Activatable
{
    public function activate(): void
    {
        $users = auth()->getProvider();

        $users->update($this->id, ['active' => 1]);
    }

    public function deactivate(): void
    {
        $users = auth()->getProvider();

        $users->update($this->id, ['active' => 0]);
    }

    public function isActive(): bool
    {
        return (bool) ($this->active ?? false);
    }
}
