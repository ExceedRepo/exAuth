<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

abstract class TestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;

    /**
     * Run only exAuth's migrations against the test database.
     */
    protected $namespace = 'exAuth';

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
}
