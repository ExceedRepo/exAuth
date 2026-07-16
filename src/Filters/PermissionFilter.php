<?php

namespace exAuth\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $userId = session()->get('auth_user_id');

        if ($userId === null || session()->get('auth_logged_in') !== true) {
            return redirect()->to('/login');
        }

        if ($arguments === null || $arguments === []) {
            return null;
        }

        $userPermissions = $this->getUserPermissions((int) $userId);

        foreach ($arguments as $required) {
            if (in_array($required, $userPermissions, true)) {
                return null;
            }
        }

        return service('response')->setStatusCode(403)->setJSON([
            'error' => 'Insufficient permissions',
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }

    protected function getUserPermissions(int $userId): array
    {
        $cacheKey = "exauth_user_permissions_{$userId}";

        $cached = cache()->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $rows = db_connect()->table('auth_permissions_users')
            ->select('permission')
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();

        $permissions = array_column($rows, 'permission');

        cache()->save($cacheKey, $permissions, 300);

        return $permissions;
    }

    public static function invalidate(int $userId): void
    {
        cache()->delete("exauth_user_permissions_{$userId}");
    }
}
