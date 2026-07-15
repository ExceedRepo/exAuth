<?php

namespace exAuth\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class GroupFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $groupId = session()->get('group_id');

        if ($groupId === null) {
            return redirect()->to('/login');
        }

        if ($arguments !== null && ! in_array($groupId, $arguments, true)) {
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
