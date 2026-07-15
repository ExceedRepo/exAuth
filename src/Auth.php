<?php

declare(strict_types=1);

namespace exAuth;

use CodeIgniter\Router\RouteCollection;

class Auth
{
    /**
     * Registers the exAuth routes.
     *
     * Usage in app/Config/Routes.php:
     *   service('auth')->routes($routes);
     */
    public function routes(RouteCollection &$routes, array $config = []): void
    {
        $namespace = $config['namespace'] ?? 'exAuth\Controllers';

        $routes->group('/', ['namespace' => $namespace], static function (RouteCollection $routes): void {
            // Registration
            $routes->get('register', 'RegisterController::register', ['as' => 'register']);
            $routes->post('register', 'RegisterController::register');

            // Login / Logout
            $routes->get('login', 'LoginController::login', ['as' => 'login']);
            $routes->post('login', 'LoginController::login');
            $routes->get('logout', 'LoginController::logout', ['as' => 'logout']);

            // Forgot / Reset password
            $routes->get('forgot-password', 'LoginController::forgotPassword', ['as' => 'forgot-password']);
            $routes->post('forgot-password', 'LoginController::forgotPassword');
            $routes->get('reset-password', 'LoginController::resetPassword', ['as' => 'reset-password']);
            $routes->post('reset-password', 'LoginController::resetPassword');

            // Email verification
            $routes->get('verify', 'LoginController::verify', ['as' => 'verify']);
            $routes->post('verify', 'LoginController::verify');

            // Magic link
            $routes->get('magic-link', 'MagicLinkController::showForm', ['as' => 'magic-link']);
            $routes->post('magic-link', 'MagicLinkController::sendLink');
            $routes->get('verify-magic-link', 'MagicLinkController::verifyLink', ['as' => 'verify-magic-link']);
        });
    }

    /**
     * Registers the JWT API routes (opt-in).
     *
     * Usage in app/Config/Routes.php:
     *   service('auth')->jwtRoutes($routes);
     *
     * Produces:
     *   POST   {prefix}/token    -> issue a JWT from credentials
     *   POST   {prefix}/refresh  -> exchange a valid token for a fresh one
     *   GET    {prefix}/me       -> current user (protected by the `jwt` filter)
     */
    public function jwtRoutes(RouteCollection &$routes, array $config = []): void
    {
        $namespace = $config['namespace'] ?? 'exAuth\Controllers';
        $prefix    = trim($config['prefix'] ?? 'api/auth', '/');

        $routes->group($prefix, ['namespace' => $namespace], static function (RouteCollection $routes): void {
            $routes->post('token', 'JWTController::token', ['as' => 'jwt-token']);
            $routes->post('refresh', 'JWTController::refresh', ['as' => 'jwt-refresh']);
            $routes->get('me', 'JWTController::me', ['as' => 'jwt-me', 'filter' => 'jwt']);
        });
    }
}
