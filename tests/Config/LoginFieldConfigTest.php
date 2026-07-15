<?php

declare(strict_types=1);

namespace Tests\Config;

use exAuth\Config\exAuth as ExAuthConfig;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class LoginFieldConfigTest extends TestCase
{
    protected $migrate = false;

    public function testLoginFieldSettingsExist(): void
    {
        $config = new ExAuthConfig();

        $this->assertContains('email', $config->validFields);
        $this->assertContains('username', $config->validFields);
        $this->assertIsBool($config->useEmailForLogin);
        $this->assertIsBool($config->useUsernameForLogin);
    }

    public function testDefaultAllowsBothEmailAndUsername(): void
    {
        $config = new ExAuthConfig();

        $this->assertTrue($config->useEmailForLogin);
        $this->assertTrue($config->useUsernameForLogin);
    }
}
