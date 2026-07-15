<?php

declare(strict_types=1);

namespace exAuth\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;
use exAuth\Models\UserModel;

class RegisterController extends Controller
{
    private $userProvider;

    public function __construct()
    {
        $this->userProvider = model(UserModel::class);
    }

    public function register()
    {
        if (logged_in()) {
            return redirect()->to('/');
        }

        if ($this->request->getMethod() === 'POST') {
            return $this->registerPost();
        }

        return view('exAuth\register');
    }

    private function registerPost(): RedirectResponse
    {
        $email           = $this->request->getPost('email');
        $username        = $this->request->getPost('username');
        $password        = $this->request->getPost('password');
        $passwordConfirm = $this->request->getPost('password_confirm');

        if ($password !== $passwordConfirm) {
            return redirect()->back()->withInput()
                ->with('errors', ['password_confirm' => 'Passwords do not match.']);
        }

        $existingEmail    = $this->userProvider->getUserByEmail($email);
        $existingUsername = $this->userProvider->getUserByUsername($username);

        if ($existingEmail !== null || $existingUsername !== null) {
            return redirect()->back()->withInput()
                ->with('error', 'A user with that email or username already exists.');
        }

        $this->userProvider->save([
            'email'    => $email,
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'active'   => 1,
        ]);

        $user = $this->userProvider->getUserByEmail($email);

        auth()->login($user['id']);

        return redirect()->to('/')->with('message', lang('exAuth.registerSuccess'));
    }
}
