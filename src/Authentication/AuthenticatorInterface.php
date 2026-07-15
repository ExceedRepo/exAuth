<?php

declare(strict_types=1);

namespace exAuth\Authentication;

interface AuthenticatorInterface
{
    public function authenticate(\CodeIgniter\HTTP\Request $request): bool;

    public function supports(): array;

    public function getError(): string|null;

    public function getErrorMessage(): string;

    public function logout(): void;
}
