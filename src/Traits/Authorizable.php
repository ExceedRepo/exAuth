<?php

declare(strict_types=1);

namespace exAuth\Traits;

trait Authorizable
{
    protected ?array $groupCache = null;

    protected ?array $permissionCache = null;

    /**
     * @param string|list<string> $group
     */
    public function inGroup($group): bool
    {
        $this->populateGroups();

        $groups = is_array($group) ? $group : [$group];

        return array_intersect($groups, $this->groupCache) !== [];
    }

    public function can(string $permission): bool
    {
        $this->populatePermissions();

        foreach ($this->permissionCache as $owned) {
            if ($this->permissionMatches($owned, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(string $permission): bool
    {
        return $this->can($permission);
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
        if ($this->groupCache !== null) {
            return;
        }

        if (empty($this->id)) {
            $this->groupCache = [];

            return;
        }

        $cacheKey = "exauth_user_groups_{$this->id}";
        $cached   = cache()->get($cacheKey);

        if ($cached !== null) {
            $this->groupCache = $cached;

            return;
        }

        $rows = db_connect()->table('auth_groups_users')
            ->select('group_id')
            ->where('user_id', $this->id)
            ->get()
            ->getResultArray();

        $this->groupCache = array_column($rows, 'group_id');

        cache()->save($cacheKey, $this->groupCache, 300);
    }

    protected function populatePermissions(): void
    {
        if ($this->permissionCache !== null) {
            return;
        }

        if (empty($this->id)) {
            $this->permissionCache = [];

            return;
        }

        $cacheKey = "exauth_user_permissions_{$this->id}";
        $cached   = cache()->get($cacheKey);

        if ($cached !== null) {
            // Direct permissions from cache; group-inherited permissions are
            // computed below so wildcard group grants stay live.
            $permissions = $cached;
        } else {
            // Direct per-user permissions.
            $rows = db_connect()->table('auth_permissions_users')
                ->select('permission')
                ->where('user_id', $this->id)
                ->get()
                ->getResultArray();

            $permissions = array_column($rows, 'permission');

            cache()->save($cacheKey, $permissions, 300);
        }

        // Permissions inherited from the user's groups (from AuthGroups config).
        $this->populateGroups();

        $config = config('AuthGroups');

        foreach ($this->groupCache as $groupName) {
            if (isset($config->groups[$groupName]['permissions']) && is_array($config->groups[$groupName]['permissions'])) {
                $permissions = array_merge($permissions, $config->groups[$groupName]['permissions']);
            }
        }

        $this->permissionCache = array_values(array_unique($permissions));
    }

    protected function permissionMatches(string $owned, string $wanted): bool
    {
        if ($owned === $wanted || $owned === '*' || $owned === '**') {
            return true;
        }

        if (str_ends_with($owned, '*')) {
            $prefix = rtrim(substr($owned, 0, -1), '.');

            return $prefix === '' || $wanted === $prefix || str_starts_with($wanted, $prefix . '.');
        }

        return false;
    }
}
