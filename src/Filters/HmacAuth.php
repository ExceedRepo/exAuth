<?php

namespace exAuth\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class HmacAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $signature = $request->getHeader('X-Signature');

        if ($signature === null) {
            return service('response')->setStatusCode(401)->setJSON([
                'error' => 'Missing HMAC signature',
            ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
