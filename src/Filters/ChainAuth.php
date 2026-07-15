<?php

namespace exAuth\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ChainAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authenticators = ['session', 'tokens', 'jwt', 'hmac'];

        foreach ($authenticators as $auth) {
            $result = null;

            switch ($auth) {
                case 'session':
                    if (session()->has('user_id')) {
                        return null;
                    }
                    break;

                case 'tokens':
                    if ($request->getHeader('Authorization') !== null) {
                        return null;
                    }
                    break;

                case 'jwt':
                    $header = $request->getHeader('Authorization');
                    if ($header !== null && str_starts_with($header->getValue(), 'Bearer ')) {
                        return null;
                    }
                    break;

                case 'hmac':
                    if ($request->getHeader('X-Signature') !== null) {
                        return null;
                    }
                    break;
            }
        }

        return service('response')->setStatusCode(401)->setJSON([
            'error' => 'Authentication required',
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
