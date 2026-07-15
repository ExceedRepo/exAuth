<?php

declare(strict_types=1);

namespace exAuth\Traits;

trait Resettable
{
    public function sendResetEmail(string $email): bool
    {
        $identityModel = model(\exAuth\Models\UserIdentityModel::class);

        $identity = $identityModel->where('type', 'email_password')
            ->where('secret', $email)
            ->first();

        if ($identity === null) {
            return false;
        }

        $token = bin2hex(random_bytes(32));

        $identityModel->where('id', $identity->id)
            ->set(['secret2' => password_hash($token, PASSWORD_DEFAULT)])
            ->update();

        $emailService = \Config\Services::email();

        $emailService->setTo($email);
        $emailService->setSubject('Password Reset Request');
        $emailService->setMessage("Your password reset token is: {$token}");

        return $emailService->send();
    }

    public function resetPassword(string $token, string $password): bool
    {
        $identityModel = model(\exAuth\Models\UserIdentityModel::class);

        $identities = $identityModel->where('type', 'email_password')->findAll();

        foreach ($identities as $identity) {
            if (password_verify($token, $identity->secret2)) {
                $identityModel->update($identity->id, [
                    'secret2' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                return true;
            }
        }

        return false;
    }
}
