<?php

declare(strict_types=1);

namespace exAuth\Config;

use CodeIgniter\Config\BaseConfig;

class AuthJWT extends BaseConfig
{
    /**
     * Secret key used to sign/verify JWTs (HMAC algorithms).
     * Set JWT_SECRET in your .env. Falls back to the app encryption key.
     */
    public string $secretKey = '';

    /**
     * Signing algorithm. Default HS256 (HMAC + SHA-256).
     */
    public string $algorithm = 'HS256';

    /**
     * Token lifetime in seconds (default 1 hour).
     */
    public int $timeToLive = 3600;

    /**
     * Optional issuer/audience claims. Leave empty to skip.
     */
    public string $issuer   = '';
    public string $audience = '';

    public function __construct()
    {
        parent::__construct();

        if ($this->secretKey === '') {
            $fromEnv = env('JWT_SECRET', env('encryption.key', ''));

            $this->secretKey = is_string($fromEnv) && $fromEnv !== ''
                ? $fromEnv
                : 'exAuth-INSECURE-default-change-me';
        }

        if ($this->issuer === '') {
            $baseUrl = (string) env('app.baseURL', '');
            if ($baseUrl !== '') {
                $this->issuer = rtrim($baseUrl, '/');
            }
        }
    }
}
