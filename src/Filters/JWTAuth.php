<?php

namespace exAuth\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class JWTAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeader('Authorization');

        if ($authHeader === null || ! str_starts_with($authHeader->getValue(), 'Bearer ')) {
            return service('response')->setStatusCode(401)->setJSON([
                'error' => 'Missing or invalid JWT token',
            ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
