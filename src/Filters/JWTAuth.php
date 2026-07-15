<?php

namespace exAuth\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class JWTAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $jwt = service('jwt');

        if (! $jwt->authenticate($request)) {
            return service('response')->setStatusCode(401)->setJSON([
                'error' => $jwt->getErrorMessage() !== '' ? $jwt->getErrorMessage() : 'Unauthorized',
            ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
