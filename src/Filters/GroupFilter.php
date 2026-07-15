<?php

namespace exAuth\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class GroupFilter implements FilterInterface
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

        $rows = db_connect()->table('auth_groups_users')
            ->select('group_id')
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();

        $userGroups = array_column($rows, 'group_id');

        if (array_intersect($arguments, $userGroups) === []) {
            return service('response')->setStatusCode(403)->setJSON([
                'error' => 'Insufficient group permissions',
            ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
