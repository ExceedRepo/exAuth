# Authorization

> **Note:** Groups and permissions are stored in the database
> (`auth_groups_users` stores the group name per user; `auth_permissions_users`
> stores direct per-user permissions). Route protection works today via the
> `group:` and `permission:` filters (see the [Beginner Setup Guide](EXAUTH_BEGINNER_SETUP.md)). The
> `service('authorization')` API described below is part of the broader design.

exAuth includes a Flat RBAC system with database-backed groups and permissions. Roles and permissions are managed at runtime, not hardcoded in config files.

## Permission Caching (RBAC Cache)

To avoid querying the database on every request, exAuth caches each user's
groups and permissions for 5 minutes (300 seconds) using CodeIgniter's
`cache` service. The cache is keyed per user:

- `exauth_user_groups_{userId}`
- `exauth_user_permissions_{userId}`

The cache is read automatically by:

- The `group` and `permission` route filters (`exAuth\Filters\GroupFilter`,
  `exAuth\Filters\PermissionFilter`)
- The `Authorizable` trait used by the `User` entity (`inGroup()`, `can()`, etc.)

### Cache invalidation

The cache is invalidated automatically whenever group membership changes via
the CLI:

```bash
php spark exauth:user addgroup -n johndoe -g admin
php spark exauth:user removegroup -n johndoe -g admin
php spark exauth:user create -n johndoe -e john@example.com -g admin
```

If you change a user's groups or permissions directly via the database or your
own code, clear the cache manually:

```php
cache()->delete("exauth_user_groups_{$userId}");
cache()->delete("exauth_user_permissions_{$userId}");
```

To change the cache duration, edit the `save(..., 300)` calls in the filters
and the `Authorizable` trait.

## Authorization Service

```php
$authorize = service('authorization');
```

## Groups vs Roles

Groups are simply collections of users that have a set of permissions assigned to them. You can name them however you want: admin, editor, beta-tester, premium, etc.

## Checking Group Membership

The group can be a group ID or group name. You can pass a string or array.

```php
$authorize->inGroup('admin', $userId);
$authorize->inGroup(['admin', 'editor'], $userId);
$authorize->inGroup(3, $userId);
```

Helper shorthand (via the User entity):

```php
ex_current_user()->inGroup('admin');
```

## Managing Group Membership

```php
$authorize->addUserToGroup($userId, 'moderators');
$authorize->addUserToGroup($userId, 2);

$authorize->removeUserFromGroup($userId, 'moderators');
$authorize->removeUserFromGroup($userId, 2);

$users = $authorize->usersInGroup('moderators');
```

## Managing Permissions

```php
$authorize->addPermissionToGroup($permissionId, $groupId);
$authorize->addPermissionToGroup('users.create', 'admin');

$authorize->removePermissionFromGroup($permissionId, $groupId);
$authorize->removePermissionFromGroup('users.create', 'admin');
```

### User-specific permissions

Add a permission directly to a user (in addition to group permissions):

```php
$authorize->addPermissionToUser('users.delete', $userId);
$authorize->removePermissionFromUser('users.delete', $userId);
```

### Checking user-specific permissions

```php
$authorize->doesUserHavePermission($userId, 'users.delete');
```

## Groups CRUD

```php
// Create
$id = $authorize->createGroup('admins', 'Site Administrators');

// Read
$group = $authorize->group('admins');  // by name
$group = $authorize->group(1);          // by id
$groups = $authorize->groups();          // all

// Update
$authorize->updateGroup($id, 'new-name', 'new description');

// Delete
$authorize->deleteGroup($id);
```

## Permissions CRUD

```php
// Create
$id = $authorize->createPermission('blog.posts.manage', 'Allows creating, editing, deleting blog posts');

// Read
$permission = $authorize->permission('blog.posts.manage');
$permission = $authorize->permission(3);
$permissions = $authorize->permissions();

// Update
$authorize->updatePermission($id, 'new.perm.name', 'New description');

// Delete
$authorize->deletePermission($id);
```

## Wildcard Permission Matching

exAuth supports wildcard matching in permission names with the `*` character.

Examples:

- `admin.*` matches `admin.users`, `admin.settings`, `admin.reports`
- `*.read` matches `users.read`, `posts.read`
- `**` matches everything

```php
// In config or DB
'admin.*'
'*.view'
'**'

// matches
ex_current_user()->can('admin.reports')   // true if admin.* allowed
ex_current_user()->can('users.view')       // true if *.view allowed
```
