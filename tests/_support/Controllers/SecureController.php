<?php

declare(strict_types=1);

namespace Tests\Support\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Minimal controller used by feature tests to exercise the token/hmac filters.
 */
class SecureController extends Controller
{
    public function token(): ResponseInterface
    {
        helper('exAuth');

        return service('response')->setJSON([
            'user_id' => ex_token_id(),
        ]);
    }

    public function hmac(): ResponseInterface
    {
        helper('exAuth');

        return service('response')->setJSON([
            'user_id' => ex_hmac_id(),
        ]);
    }
}
