<?php

declare(strict_types=1);

namespace exAuth\Config;

use exAuth\Filters\ChainAuth;
use exAuth\Filters\GroupFilter;
use exAuth\Filters\HmacAuth;
use exAuth\Filters\JWTAuth;
use exAuth\Filters\PermissionFilter;
use exAuth\Filters\SessionAuth;
use exAuth\Filters\TokenAuth;

class Registrar
{
    /**
     * Registers the exAuth filters.
     */
    public static function Filters(): array
    {
        return [
            'aliases' => [
                'session'    => SessionAuth::class,
                'tokens'     => TokenAuth::class,
                'hmac'       => HmacAuth::class,
                'jwt'        => JWTAuth::class,
                'chain'      => ChainAuth::class,
                'group'      => GroupFilter::class,
                'permission' => PermissionFilter::class,
            ],
        ];
    }

    public static function exAuth(): array
    {
        return config('exAuth')->views ?? [];
    }
}
