<?php

declare(strict_types=1);

namespace exAuth\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;
use CodeIgniter\Throttle\Throttler;
use exAuth\Authentication\Authenticators\Session;
use exAuth\Entities\User;
use exAuth\Models\UserIdentityModel;
use exAuth\Models\UserModel;
use exAuth\Traits\RendersViews;

class LoginController extends Controller
{
    use RendersViews;

    private $userProvider;
    private $identityProvider;

    public function __construct()
    {
        $this->userProvider = model(UserModel::class);
        $this->identityProvider = model(UserIdentityModel::class);
    }

    public function login()
    {
        helper('exAuth');

        if (ex_logged_in()) {
            return redirect()->to('/');
        }

        if ($this->request->getMethod() === 'POST') {
            return $this->loginPost();
        }

        return $this->renderView('login');
    }

    private function loginPost(): RedirectResponse
    {
        $password = (string) ($this->request->getPost('password') ?? '');

        $authConfig = config('exAuth');

        if ($authConfig->enableRateLimit) {
            $throttler = service('throttler');
            $ip        = $this->request->getIPAddress();
            $key       = 'login_' . md5($ip);

            if ($throttler->check($key, $authConfig->maxLoginAttempts, $authConfig->loginAttemptHours * 3600) === false) {
                $seconds = $throttler->getTokenTime();
                return redirect()->back()->withInput()
                    ->with('error', lang('exAuth.logInTooManyAttempts', [$seconds]));
            }
        }

        // Determine which field(s) are allowed for login, driven by Config/exAuth.
        $authConfig       = config('exAuth');
        $validFields      = $authConfig->validFields;
        $useEmail         = in_array('email', $validFields, true) && $authConfig->useEmailForLogin;
        $useUsername      = in_array('username', $validFields, true) && $authConfig->useUsernameForLogin;
        $allowBoth        = $useEmail && $useUsername;

        $loginValue = (string) ($this->request->getPost('login') ?? '');

        if ($loginValue === '' && $useEmail) {
            $loginValue = (string) ($this->request->getPost('email') ?? '');
        }
        if ($loginValue === '' && $useUsername) {
            $loginValue = (string) ($this->request->getPost('username') ?? '');
        }

        $user       = null;

        if ($loginValue !== '') {
            $isEmail = filter_var($loginValue, FILTER_VALIDATE_EMAIL) !== false;

            if ($allowBoth) {
                // Detect the field type from the input.
                if ($isEmail) {
                    $user = $this->userProvider->getUserByEmail($loginValue);
                } else {
                    $user = $this->userProvider->getUserByUsername($loginValue);
                }
            } elseif ($useEmail) {
                $user = $this->userProvider->getUserByEmail($loginValue);
            } elseif ($useUsername) {
                $user = $this->userProvider->getUserByUsername($loginValue);
            }
        }

        if ($user === null) {
            return redirect()->back()->withInput()
                ->with('error', lang('exAuth.logInInvalid'));
        }

        if (! password_verify($password, (string) $user['password'])) {
            return redirect()->back()->withInput()
                ->with('error', lang('exAuth.logInInvalid'));
        }

        if ((bool) $user['active'] === false) {
            return redirect()->back()->withInput()
                ->with('error', lang('exAuth.logInNotActive'));
        }

        if ($user['status'] === 'banned' || $user['status'] === 'suspended') {
            return redirect()->back()->withInput()
                ->with('error', lang('exAuth.logInLocked'));
        }

        $session = new Session();
        $userEntity = new User();
        $userEntity->id = $user['id'];
        $session->login($userEntity);

        $this->userProvider->update($user['id'], ['last_login' => Time::now()->toDateTimeString()]);

        return redirect()->to('/')->with('message', lang('exAuth.logInSuccess'));
    }

    public function logout(): RedirectResponse
    {
        helper('exAuth');
        ex_logout();
        return redirect()->to('/')->with('message', lang('exAuth.logOutSuccess'));
    }

    public function forgotPassword()
    {
        if ($this->request->getMethod() === 'POST') {
            return $this->forgotPasswordPost();
        }

        return $this->renderView('forgot_password');
    }

    private function forgotPasswordPost(): RedirectResponse
    {
        $email = (string) ($this->request->getPost('email') ?? '');
        $user  = $this->userProvider->getUserByEmail($email);

        if ($user === null) {
            return redirect()->back()->withInput()
                ->with('error', lang('exAuth.logInInvalid'));
        }

        $this->identityProvider->deleteIdentitiesByType($user['id'], 'reset_token');

        helper('text');
        $token = random_string('crypto', 32);

        $this->identityProvider->insert([
            'user_id'    => $user['id'],
            'type'       => 'reset_token',
            'secret'     => $token,
            'expires_at' => Time::now()->addHours(1)->toDateTimeString(),
        ]);

        return redirect()->to('login')->with('message', lang('exAuth.logInInvalid'));
    }

    public function resetPassword()
    {
        $token = $this->request->getGet('token');

        if ($this->request->getMethod() === 'POST') {
            return $this->resetPasswordPost();
        }

        return $this->renderView('reset_password', ['token' => $token]);
    }

    private function resetPasswordPost(): RedirectResponse
    {
        $token    = $this->request->getPost('token');
        $password = $this->request->getPost('password');

        $identity = $this->identityProvider
            ->where('type', 'reset_token')
            ->where('secret', $token)
            ->first();

        if ($identity === null) {
            return redirect()->back()->withInput()
                ->with('error', lang('exAuth.errorTokenInvalid'));
        }

        if (Time::now()->isAfter($identity->expires_at)) {
            return redirect()->back()->withInput()
                ->with('error', lang('exAuth.errorTokenExpired'));
        }

        $this->userProvider->update(
            $identity->user_id,
            ['password' => password_hash($password, PASSWORD_DEFAULT)]
        );

        $this->identityProvider->delete($identity->id);

        return redirect()->to('login')->with('message', lang('exAuth.logInSuccess'));
    }

    public function verify()
    {
        $token = $this->request->getGet('token');

        if ($this->request->getMethod() === 'POST') {
            return $this->verifyPost();
        }

        return $this->renderView('verify', ['token' => $token]);
    }

    private function verifyPost(): RedirectResponse
    {
        $token = $this->request->getPost('token');

        $identity = $this->identityProvider
            ->where('type', 'verify_email')
            ->where('secret', $token)
            ->first();

        if ($identity === null) {
            return redirect()->back()->withInput()
                ->with('error', lang('exAuth.errorTokenInvalid'));
        }

        if (Time::now()->isAfter($identity->expires_at)) {
            return redirect()->back()->withInput()
                ->with('error', lang('exAuth.errorTokenExpired'));
        }

        $this->userProvider->update($identity->user_id, ['active' => 1]);
        $this->identityProvider->delete($identity->id);

        return redirect()->to('login')->with('message', lang('exAuth.logInSuccess'));
    }
}
