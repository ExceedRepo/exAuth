<?php

declare(strict_types=1);

namespace exAuth\Config;

use CodeIgniter\Config\BaseService;
use exAuth\Auth;

class Services extends BaseService
{
    /**
     * The base auth class for exAuth.
     */
    public static function auth(bool $getShared = true): Auth
    {
        if ($getShared) {
            return self::getSharedInstance('auth');
        }

        return new Auth();
    }
}
