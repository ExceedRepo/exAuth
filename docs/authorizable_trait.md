# Authorizable Trait

The `Authorizable` trait should be attached to any Entity that needs authorization checks. For exAuth, it is already attached to the `User` entity.

## Methods

### can(string $permission)

Checks if the entity has a specific permission.

```php
$user->can('users.create');
$user->can('admin.*');
```

### inGroup(string $group)

Checks if the entity belongs to a group.

```php
$user->inGroup('admin');
$user->inGroup('moderators');
```

### hasPermission(string $permission)

Alias for `can()`.

```php
$user->hasPermission('users.create');
```
