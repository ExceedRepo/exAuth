<?php

declare(strict_types=1);

namespace exAuth\Authentication;

class Authentication
{
    private static array $authenticators = [];

    private static array $instances = [];

    private string $lastError = '';

    public static function factory(string $name): AuthenticatorInterface
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        if (! isset(self::$authenticators[$name])) {
            throw new \RuntimeException("Unknown authenticator: {$name}");
        }

        $class = self::$authenticators[$name];

        if (! is_subclass_of($class, AuthenticatorInterface::class)) {
            throw new \RuntimeException("Authenticator {$name} does not implement AuthenticatorInterface");
        }

        self::$instances[$name] = new $class();

        return self::$instances[$name];
    }

    public function addAuthenticator(string $name, string $class): void
    {
        self::$authenticators[$name] = $class;
    }

    public function authenticate(string $name, \CodeIgniter\HTTP\Request $request): bool
    {
        $authenticator = self::factory($name);

        $result = $authenticator->authenticate($request);

        if (! $result) {
            $this->lastError = $authenticator->getErrorMessage();
        }

        return $result;
    }

    public function getError(): string
    {
        return $this->lastError;
    }
}
