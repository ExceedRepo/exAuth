# CLI Commands

exAuth includes several CLI commands for user and group management.

**Note:** All commands require that the database migrations have been run first.

## Users

### Create User

```bash
php spark exauth:user_create
```

Prompts for username, email, and password. Registers a new user in the system.

```bash
php spark exauth:user_create --username=johndoe --email=j@d.com --password=s3cret
```

### Delete User

```bash
php spark exauth:user_delete <user_id>
```

Deletes the user and their associated identities from the system.

### Activate User

```bash
php spark exauth:user_activate <user_id>
```

Sets the user's active status to true.

### Deactivate User

```bash
php spark exauth:user_deactivate <user_id>
```

Sets the user's active status to false.

### Ban User

```bash
php spark exauth:user_ban <user_id> [reason]
```

Bans a user from the system. Optionally includes a reason.

### Unban User

```bash
php spark exauth:user_unban <user_id>
```

Removes the ban from a user.

### List Users

```bash
php spark exauth:user_list
```

Shows all registered users in a table.

## Groups

### Create Group

```bash
php spark exauth:group_create <name> [description]
```

Creates a new group.

```bash
php spark exauth:group_create admins "Site administrators with full access"
```

### Delete Group

```bash
php spark exauth:group_delete <group_id>
```

Deletes a group (does not delete the users).

### List Groups

```bash
php spark exauth:group_list
```

Shows all groups in a table.

## Permissions

### Create Permission

```bash
php spark exauth:permission_create <name> [description]
```

Creates a new permission.

```bash
php spark exauth:permission_create "users.manage" "Manage all users"
```

### Delete Permission

```bash
php spark exauth:permission_delete <permission_id>
```

Deletes a permission from the system.

### List Permissions

```bash
php spark exauth:permission_list
```

Shows all permissions.

## Utility

### Hash Password

```bash
php spark exauth:hash_password [password]
```

Generates a bcrypt hash of the given password and prints it. You will be prompted for the password if not provided.

### Publish

```bash
php spark exauth:publish
```

Interactively copies the exAuth configuration, views, and other files to your project for customization.
