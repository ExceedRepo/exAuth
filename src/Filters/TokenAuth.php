<?php

namespace exAuth\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class TokenAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $tokens = service('tokens');

        if (! $tokens->authenticate($request)) {
            return service('response')->setStatusCode(401)->setJSON([
                'error' => $tokens->getErrorMessage() !== '' ? $tokens->getErrorMessage() : 'Unauthorized',
            ]);
        }

        // Optional scope enforcement: ['filter' => 'tokens:posts.read']
        if ($arguments !== null && $arguments !== []) {
            foreach ($arguments as $scope) {
                if ($tokens->tokenCan($scope)) {
                    return null;
                }
            }

            return service('response')->setStatusCode(403)->setJSON([
                'error' => 'Token is missing the required scope',
            ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
