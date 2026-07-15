<?php

declare(strict_types=1);

namespace exAuth\Commands;

use exAuth\Commands\Exceptions\BadInputException;
use exAuth\Commands\Exceptions\CancelException;
use exAuth\Config\AuthGroups;
use exAuth\Entities\User as UserEntity;
use exAuth\Exceptions\UserNotFoundException;
use exAuth\Models\UserModel;

class User extends BaseCommand
{
    private array $validActions = [
        'create', 'activate', 'deactivate', 'delete', 'password', 'list',
        'addgroup', 'removegroup',
    ];

    protected $name = 'exauth:user';

    protected $description = 'Manage exAuth users.';

    protected $usage = <<<'EOL'
        exauth:user <action> options

            exauth:user create -n username -e user@example.com
            exauth:user create -n username -e user@example.com -g mygroup

            exauth:user activate -n username
            exauth:user activate -e user@example.com

            exauth:user deactivate -n username
            exauth:user deactivate -e user@example.com

            exauth:user delete -i 123
            exauth:user delete -n username
            exauth:user delete -e user@example.com

            exauth:user password -n username
            exauth:user password -e user@example.com

            exauth:user list
            exauth:user list -n username -e user@example.com

            exauth:user addgroup -n username -g mygroup
            exauth:user addgroup -e user@example.com -g mygroup

            exauth:user removegroup -n username -g mygroup
            exauth:user removegroup -e user@example.com -g mygroup
        EOL;

    protected $arguments = [
        'action' => <<<'EOL'
            create:      Create a new user
            activate:    Activate a user
            deactivate:  Deactivate a user
            delete:      Delete a user
            password:    Change a user password
            list:        List users
            addgroup:    Add a user to a group
            removegroup: Remove a user from a group
        EOL,
    ];

    protected $options = [
        '-i'          => 'User id',
        '-n'          => 'User name',
        '-e'          => 'User email',
        '-g'          => 'Group name',
    ];

    public function run(array $params): int
    {
        $action = $params[0] ?? null;

        if ($action === null || ! in_array($action, $this->validActions, true)) {
            $this->write(
                'Specify a valid action: ' . implode(', ', $this->validActions),
                'red',
            );

            return EXIT_ERROR;
        }

        $userid   = (int) ($params['i'] ?? 0);
        $username = $params['n'] ?? null;
        $email    = $params['e'] ?? null;
        $group    = $params['g'] ?? null;

        try {
            match ($action) {
                'create'     => $this->create($username, $email, $group),
                'activate'   => $this->activate($username, $email),
                'deactivate' => $this->deactivate($username, $email),
                'delete'     => $this->delete($userid, $username, $email),
                'password'   => $this->password($username, $email),
                'list'       => $this->list($username, $email),
                'addgroup'   => $this->addgroup($group, $username, $email),
                'removegroup' => $this->removegroup($group, $username, $email),
            };
        } catch (BadInputException|CancelException|UserNotFoundException $e) {
            $this->write($e->getMessage(), 'red');

            return EXIT_ERROR;
        }

        return EXIT_SUCCESS;
    }

    private function create(?string $username = null, ?string $email = null, ?string $group = null): void
    {
        $data = [];

        if ($username === null) {
            $username = $this->prompt('Username', null, 'required|min_length[3]|is_unique[users.username]');
        }
        $data['username'] = $username;

        if ($email === null) {
            $email = $this->prompt('Email', null, 'required|valid_email|is_unique[users.email]');
        }
        $data['email'] = $email;

        $password        = $this->prompt('Password', null, 'required|min_length[8]');
        $passwordConfirm = $this->prompt('Password confirmation', null, 'required');

        if ($password !== $passwordConfirm) {
            throw new BadInputException("The passwords don't match");
        }
        $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        $data['active']   = 1;

        $userModel = model(UserModel::class);
        $user      = new UserEntity($data);

        if ($group !== null && ! $this->validateGroup($group)) {
            throw new CancelException('Invalid group: "' . $group . '"');
        }

        $userModel->save($user);
        $userId = $userModel->getInsertID();
        $user   = $userModel->findUserById($userId);

        if ($group !== null) {
            db_connect()->table('auth_groups_users')->insert([
                'user_id'  => $userId,
                'group_id' => $group,
            ]);
            $this->write('User "' . $username . '" created and added to group "' . $group . '".', 'green');
        } else {
            $this->write('User "' . $username . '" created.', 'green');
        }
    }

    private function validateGroup(string $group): bool
    {
        $groupConfig = config(AuthGroups::class);

        return array_key_exists($group, $groupConfig->groups);
    }

    private function activate(?string $username = null, ?string $email = null): void
    {
        $user = $this->findUser('Activate user', $username, $email);

        $confirm = $this->prompt('Activate the user ' . $user->username . ' ?', ['y', 'n']);

        if ($confirm === 'y') {
            $userModel   = model(UserModel::class);
            $user->active = 1;
            $userModel->save($user);

            $this->write('User "' . $user->username . '" activated', 'green');
        } else {
            $this->write('User "' . $user->username . '" activation cancelled', 'yellow');
        }
    }

    private function deactivate(?string $username = null, ?string $email = null): void
    {
        $user = $this->findUser('Deactivate user', $username, $email);

        $confirm = $this->prompt('Deactivate the user "' . $user->username . '" ?', ['y', 'n']);

        if ($confirm === 'y') {
            $userModel   = model(UserModel::class);
            $user->active = 0;
            $userModel->save($user);

            $this->write('User "' . $user->username . '" deactivated', 'green');
        } else {
            $this->write('User "' . $user->username . '" deactivation cancelled', 'yellow');
        }
    }

    private function delete(int $userid = 0, ?string $username = null, ?string $email = null): void
    {
        $userModel = model(UserModel::class);

        if ($userid !== 0) {
            $user = $userModel->findUserById($userid);
            $this->checkUserExists($user);
        } else {
            $user = $this->findUser('Delete user', $username, $email);
        }

        $confirm = $this->prompt(
            'Delete the user "' . $user->username . '" (' . $user->email . ') ?',
            ['y', 'n'],
        );

        if ($confirm === 'y') {
            $userModel->delete($user->id, true);
            $this->write('User "' . $user->username . '" deleted', 'green');
        } else {
            $this->write('User "' . $user->username . '" deletion cancelled', 'yellow');
        }
    }

    private function checkUserExists($user): void
    {
        if ($user === null) {
            throw new UserNotFoundException("User doesn't exist");
        }
    }

    private function password($username = null, $email = null): void
    {
        $user = $this->findUser('Change user password', $username, $email);

        $confirm = $this->prompt('Set the password for "' . $user->username . '" ?', ['y', 'n']);

        if ($confirm === 'y') {
            $password        = $this->prompt('Password', null, 'required|min_length[8]');
            $passwordConfirm = $this->prompt('Password confirmation', null, 'required');

            if ($password !== $passwordConfirm) {
                throw new BadInputException("The passwords don't match");
            }

            $userModel     = model(UserModel::class);
            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $userModel->save($user);

            $this->write('Password for "' . $user->username . '" set', 'green');
        } else {
            $this->write('Password setting for "' . $user->username . '" cancelled', 'yellow');
        }
    }

    private function list(?string $username = null, ?string $email = null): void
    {
        $userModel = model(UserModel::class)->asArray();

        if ($username !== null) {
            $userModel->like('username', $username);
        }
        if ($email !== null) {
            $userModel->like('email', $email);
        }

        $this->write("Id\tUser");

        foreach ($userModel->findAll() as $user) {
            $this->write($user['id'] . "\t" . $user['username'] . ' (' . $user['email'] . ')');
        }
    }

    private function addgroup($group = null, $username = null, $email = null): void
    {
        if ($group === null) {
            $group = $this->prompt('Group', null, 'required');
        }

        if (! $this->validateGroup($group)) {
            throw new CancelException('Invalid group: "' . $group . '"');
        }

        $user = $this->findUser('Add user to group', $username, $email);

        $confirm = $this->prompt(
            'Add the user "' . $user->username . '" to the group "' . $group . '" ?',
            ['y', 'n'],
        );

        if ($confirm === 'y') {
            $db = db_connect();
            $exists = $db->table('auth_groups_users')
                ->where('user_id', $user->id)
                ->where('group_id', $group)
                ->countAllResults() > 0;

            if (! $exists) {
                $db->table('auth_groups_users')->insert([
                    'user_id'  => $user->id,
                    'group_id' => $group,
                ]);
            }

            $this->write('User "' . $user->username . '" added to group "' . $group . '"', 'green');
        } else {
            $this->write(
                'Addition of the user "' . $user->username . '" to the group "' . $group . '" cancelled',
                'yellow',
            );
        }
    }

    private function removegroup($group = null, $username = null, $email = null): void
    {
        if ($group === null) {
            $group = $this->prompt('Group', null, 'required');
        }

        if (! $this->validateGroup($group)) {
            throw new CancelException('Invalid group: "' . $group . '"');
        }

        $user = $this->findUser('Remove user from group', $username, $email);

        $confirm = $this->prompt(
            'Remove the user "' . $user->username . '" from the group "' . $group . '" ?',
            ['y', 'n'],
        );

        if ($confirm === 'y') {
            db_connect()->table('auth_groups_users')
                ->where('user_id', $user->id)
                ->where('group_id', $group)
                ->delete();

            $this->write('User "' . $user->username . '" removed from group "' . $group . '"', 'green');
        } else {
            $this->write('Removal of the user "' . $user->username . '" from the group "' . $group . '" cancelled', 'yellow');
        }
    }

    private function findUser($question = '', $username = null, $email = null): UserEntity
    {
        if ($username === null && $email === null) {
            $choice = $this->prompt($question . ' by username or email ?', ['u', 'e']);

            if ($choice === 'u') {
                $username = $this->prompt('Username', null, 'required');
            } elseif ($choice === 'e') {
                $email = $this->prompt('Email', null, 'required');
            }
        }

        $userModel = model(UserModel::class);

        $user = null;
        if ($username !== null) {
            $user = $userModel->asArray()->where('username', $username)->first();
        } elseif ($email !== null) {
            $user = $userModel->asArray()->where('email', $email)->first();
        }

        $this->checkUserExists($user);

        return $userModel->findUserById($user['id']);
    }
}
