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

        $rows = db_connect()->table('auth_permissions_users')
            ->select('permission')
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();

        $userPermissions = array_column($rows, 'permission');

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
}
