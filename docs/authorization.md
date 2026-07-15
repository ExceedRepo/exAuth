# Authorization

exAuth includes a Flat RBAC system with database-backed groups and permissions. Roles and permissions are managed at runtime, not hardcoded in config files.

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
