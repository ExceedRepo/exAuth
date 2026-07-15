<?php

namespace exAuth\Authorization;

class PermissionMatcher
{
    public function match(string $permission, string $check): bool
    {
        if ($permission === '*') {
            return true;
        }

        // Convert wildcard pattern to regex
        $pattern = preg_quote($permission, '/');
        $pattern = str_replace('\*', '.*', $pattern);

        return preg_match('/^' . $pattern . '$/', $check) === 1;
    }

    public function matchAny(array $permissions, array $checks): bool
    {
        foreach ($permissions as $permission) {
            foreach ($checks as $check) {
                if ($this->match($permission, $check)) {
                    return true;
                }
            }
        }

        return false;
    }
}
