<?php

namespace exAuth\Authorization;

use exAuth\Config\AuthGroups;

class Groups
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
        return $this->getConfig()->groups;
    }

    public function get(string $group): ?array
    {
        return $this->getConfig()->groups[$group] ?? null;
    }

    public function hasGroup(string $group): bool
    {
        return isset($this->getConfig()->groups[$group]);
    }

    public function check(int $userId, ...$permissions): bool
    {
        // Placeholder: actual lookup would query the database
        // For now, return false since no user context is available
        return false;
    }
}
