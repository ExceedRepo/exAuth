# CLI Commands

exAuth includes several CLI commands for setup and user management.

**Note:** All commands require that the database migrations have been run first.

## Setup

### One-Click Setup

```bash
php spark exauth:setup
```

This interactive command does the following automatically:

1. **Publishes config files** — `app/Config/exAuth.php` and `app/Config/AuthGroups.php`
2. **Adds helpers** — registers `exAuth` and `setting` helpers in `Config/Autoload.php`
3. **Adds routes** — adds `service('auth')->routes($routes);` to `Config/Routes.php`
4. **Updates security** — changes `$csrfProtection` to `'session'` for CSRF compatibility
5. **Checks email config** — prompts for `$fromEmail` and `$fromName` if empty
6. **Runs migrations** — optionally runs `spark migrate --all`

Use `-f` to force overwrite existing files:

```bash
php spark exauth:setup -f
```

## User Management

### Create User

```bash
php spark exauth:user create
```

Prompts for username, email, and password. Registers a new user in the system.

```bash
php spark exauth:user create -n johndoe -e john@example.com
```

You can also specify a group:

```bash
php spark exauth:user create -n johndoe -e john@example.com -g admin
```

### List Users

```bash
php spark exauth:user list
```

Shows all registered users in a table. Filter by username or email:

```bash
php spark exauth:user list -n john
php spark exauth:user list -e @example.com
```

### Activate User

```bash
php spark exauth:user activate -n johndoe
php spark exauth:user activate -e john@example.com
```

### Deactivate User

```bash
php spark exauth:user deactivate -n johndoe
php spark exauth:user deactivate -e john@example.com
```

### Delete User

```bash
php spark exauth:user delete -i 5
php spark exauth:user delete -n johndoe
php spark exauth:user delete -e john@example.com
```

### Change Password

```bash
php spark exauth:user password -n johndoe
php spark exauth:user password -e john@example.com
```

### Add User to Group

```bash
php spark exauth:user addgroup -n johndoe -g admin
```

### Remove User from Group

```bash
php spark exauth:user removegroup -n johndoe -g admin
```
