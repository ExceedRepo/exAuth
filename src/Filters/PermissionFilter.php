<?php

namespace exAuth\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $permissions = session()->get('permissions') ?? [];

        if (empty($permissions)) {
            return redirect()->to('/login');
        }

        if ($arguments !== null) {
            $hasPermission = false;

            foreach ($arguments as $required) {
                if (in_array($required, $permissions, true)) {
                    $hasPermission = true;
                    break;
                }
            }

            if (! $hasPermission) {
                return service('response')->setStatusCode(403)->setJSON([
                    'error' => 'Insufficient permissions',
                ]);
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
