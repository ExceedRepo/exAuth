<?php

declare(strict_types=1);

namespace exAuth\Config;

use CodeIgniter\Config\BaseService;
use exAuth\Auth;
use exAuth\Authentication\Authenticators\AccessTokens;
use exAuth\Authentication\Authenticators\HmacSha256;
use exAuth\Authentication\Authenticators\JWT;

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

    /**
     * Shared JWT authenticator. The JWTAuth filter verifies the request token
     * against this instance so controllers can read the current user via
     * service('jwt')->getUserId() or the ex_jwt_* helpers.
     */
    public static function jwt(bool $getShared = true): JWT
    {
        if ($getShared) {
            return self::getSharedInstance('jwt');
        }

        return new JWT();
    }

    /**
     * Shared Personal Access Token authenticator (used by the `tokens` filter
     * and the ex_token_* helpers).
     */
    public static function tokens(bool $getShared = true): AccessTokens
    {
        if ($getShared) {
            return self::getSharedInstance('tokens');
        }

        return new AccessTokens();
    }

    /**
     * Shared HMAC-SHA256 authenticator (used by the `hmac` filter and the
     * ex_hmac_* helpers).
     */
    public static function hmac(bool $getShared = true): HmacSha256
    {
        if ($getShared) {
            return self::getSharedInstance('hmac');
        }

        return new HmacSha256();
    }
}
