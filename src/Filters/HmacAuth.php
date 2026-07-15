<?php

namespace exAuth\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class HmacAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $hmac = service('hmac');

        if (! $hmac->authenticate($request)) {
            return service('response')->setStatusCode(401)->setJSON([
                'error' => $hmac->getErrorMessage() !== '' ? $hmac->getErrorMessage() : 'Unauthorized',
            ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
