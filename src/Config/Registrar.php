<?php declare(strict_types=1);
namespace exAuth\Config;

class Registrar
{
    public static function exAuth(): array
    {
        return config('exAuth')->views ?? [];
    }
}
