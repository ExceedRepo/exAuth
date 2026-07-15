# Testing

Testing support for exAuth is in an early stage. This document will be updated as the test suite matures.

## Running the existing tests

```bash
# from the exAuth root directory
composer test

# or directly with phpunit
vendor/bin/phpunit
```

## What is covered

- **Authentication service**: instantiation and service registration
- **Basic entity instantiation**: entity creation and default values

## Fakers (coming soon)

Fakers will be added to provide random data generation for test objects:

- `UserFaker`
- `GroupFaker`
- `PermissionFaker`
- `AccessTokenFaker`

## Writing your own tests

The tests directory is structured as:

```
tests/
    Authentication/
    Authorization/
    Controllers/
    Entities/
    Models/
    Support/
```

Refer to the `tests/` directory for examples and to the [CodeIgniter 4 testing documentation](https://codeigniter.com/user_guide/testing/) for how to set up unit and feature tests.

If you want to run tests with the framework integration:

```php
use CodeIgniter\Test\CIUnitTestCase;

class UserTest extends CIUnitTestCase
{
    public function testUserCanBeCreated()
    {
        $user = new \exAuth\Entities\User();
        $this->assertInstanceOf(\exAuth\Entities\User::class, $user);
    }
}
```

For integration tests, use the `DatabaseTestTrait` to set up migrations automatically.
