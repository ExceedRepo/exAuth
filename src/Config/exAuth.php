<?php declare(strict_types=1);
namespace exAuth\Config;

class exAuth
{
    public array $actions = ['login' => null, 'register' => null];
    public string $viewPrefix = 'exAuth\\Views\\';
    public array $views = [
        'login'      => 'login',
        'register'   => 'register',
        'forgot'     => 'forgot',
        'reset'      => 'reset',
        'emailMagic' => 'emailMagic',
    ];
    public bool $allowRegistration = true;
    public string $activeAuthenticator = 'session';
    public bool $enableJWT = false;
    public bool $enableTokens = false;
    public bool $enableHmac = false;
    public bool $enableRateLimit = true;
    public array $authenticators = [
        'session' => 'exAuth\\Authentication\\Authenticators\\Session',
        'tokens'  => 'exAuth\\Authentication\\Authenticators\\AccessTokens',
        'hmac'    => 'exAuth\\Authentication\\Authenticators\\HmacSha256',
        'jwt'     => 'exAuth\\Authentication\\Authenticators\\JWT',
    ];
    public array $authenticationChain = ['session', 'tokens', 'jwt', 'hmac'];
    public string $hashAlgorithm = 'sha256';
    public int $hashCost = 12;
    public int $minPasswordLength = 8;
    public string $userProvider = 'exAuth\\Models\\UserModel';
    public int $maxLoginAttempts = 5;
    public int $loginAttemptHours = 1;
    public array $validFields = ['email', 'username'];
    public bool $useEmailForLogin = true;
    public bool $useUsernameForLogin = true;
}
