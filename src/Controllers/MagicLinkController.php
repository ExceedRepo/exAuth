<?php

declare(strict_types=1);

namespace exAuth\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;
use exAuth\Models\UserIdentityModel;
use exAuth\Models\UserModel;

class MagicLinkController extends Controller
{
    private $userProvider;
    private $identityModel;

    public function __construct()
    {
        $this->userProvider  = model(UserModel::class);
        $this->identityModel = model(UserIdentityModel::class);
    }

    public function showForm()
    {
        if (logged_in()) {
            return redirect()->to('/');
        }

        return view('exAuth\magic_link_form');
    }

    public function sendLink(): RedirectResponse
    {
        $email = $this->request->getPost('email');
        $user  = $this->userProvider->getUserByEmail($email);

        if ($user === null) {
            return redirect()->back()->withInput()
                ->with('error', lang('exAuth.logInInvalid'));
        }

        $this->identityModel->deleteIdentitiesByType($user['id'], 'magic_link');

        helper('text');
        $token = random_string('crypto', 20);

        $this->identityModel->insert([
            'user_id' => $user['id'],
            'type'    => 'magic_link',
            'secret'  => $token,
            'expires' => Time::now()->addMinutes(15)->toDateTimeString(),
        ]);

        return view('exAuth\magic_link_sent');
    }

    public function verifyLink(): RedirectResponse
    {
        $token = $this->request->getGet('token');

        if ($token === null) {
            return redirect()->to('magic-link')->with('error', lang('exAuth.errorTokenInvalid'));
        }

        $identity = $this->identityModel
            ->where('type', 'magic_link')
            ->where('secret', $token)
            ->first();

        if ($identity === null) {
            return redirect()->to('magic-link')->with('error', lang('exAuth.errorTokenInvalid'));
        }

        if (Time::now()->isAfter($identity->expires_at)) {
            return redirect()->to('magic-link')->with('error', lang('exAuth.errorTokenExpired'));
        }

        $this->identityModel->delete($identity->id);

        auth()->login($identity->user_id);

        return redirect()->to('/')->with('message', lang('exAuth.logInSuccess'));
    }

    public function completeLogin(): RedirectResponse
    {
        return redirect()->to('/');
    }
}
