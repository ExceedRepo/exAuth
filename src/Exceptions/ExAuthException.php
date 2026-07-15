<?php

declare(strict_types=1);

namespace exAuth\Exceptions;

use RuntimeException;

class ExAuthException extends RuntimeException
{
    public static function forNotFound(string $message = null): self
    {
        return new static($message ?? 'Resource not found.');
    }

    public static function forAccessDenied(string $message = null): self
    {
        return new static($message ?? 'Access denied.');
    }

    public static function forExpired(string $message = null): self
    {
        return new static($message ?? 'Resource has expired.');
    }
}
