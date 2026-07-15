<?php

declare(strict_types=1);

namespace exAuth\Traits;

trait Authorizable
{
    protected array $groupCache = [];

    protected array $permissionCache = [];

    public function can(string $permission): bool
    {
        $this->populatePermissions();

        if (in_array('*', $this->permissionCache, true)) {
            return true;
        }

        if (in_array($permission, $this->permissionCache, true)) {
            return true;
        }

        $this->populateGroups();

        $matrix = setting('AuthGroups.matrix');

        if ($matrix === null) {
            return false;
        }

        foreach ($this->groupCache as $group) {
            $perms = $matrix[$group] ?? [];

            if (in_array('*', $perms, true)) {
                return true;
            }

            if (in_array($permission, $perms, true)) {
                return true;
            }
        }

        return false;
    }

    public function canAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public function hasRole(string $role): bool
    {
        $this->populateGroups();

        return in_array($role, $this->groupCache, true);
    }

    public function getGroups(): array
    {
        $this->populateGroups();

        return $this->groupCache;
    }

    public function getPermissions(): array
    {
        $this->populatePermissions();

        return $this->permissionCache;
    }

    protected function populateGroups(): void
    {
        if ($this->id === null) {
            $this->groupCache = [];

            return;
        }

        $groupModel = model(\exAuth\Models\GroupModel::class);

        $this->groupCache = $groupModel->getForUser($this);
    }

    protected function populatePermissions(): void
    {
        if ($this->id === null) {
            $this->permissionCache = [];

            return;
        }

        $permissionModel = model(\exAuth\Models\PermissionModel::class);

        $this->permissionCache = $permissionModel->getForUser($this);
    }
}
