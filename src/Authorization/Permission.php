<?php

namespace exAuth\Authorization;

use exAuth\Config\AuthGroups;

class Permission
{
    private ?AuthGroups $config = null;

    private function getConfig(): AuthGroups
    {
        if ($this->config === null) {
            $this->config = new AuthGroups();
        }
        return $this->config;
    }

    public function all(): array
    {
        return $this->getConfig()->permissions;
    }

    public function get(string $permission): ?string
    {
        return $this->getConfig()->permissions[$permission] ?? null;
    }

    public function has(string $permission): bool
    {
        return isset($this->getConfig()->permissions[$permission]);
    }
}
