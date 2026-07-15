<?php

declare(strict_types=1);

namespace Tests\Database;

use Tests\Support\TestCase;

/**
 * @internal
 */
final class MigrationsTest extends TestCase
{
    public function testCoreTablesExist(): void
    {
        $tables = [
            'users',
            'auth_identities',
            'auth_logins',
            'auth_remember_tokens',
            'auth_groups_users',
            'auth_permissions_users',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                $this->db->tableExists($table),
                "Table '{$table}' should exist after migrations.",
            );
        }
    }

    public function testUsersTableUsesPasswordColumnNotPasswordHash(): void
    {
        $this->assertTrue(
            $this->db->fieldExists('password', 'users'),
            "The users table must have a 'password' column.",
        );
        $this->assertFalse(
            $this->db->fieldExists('password_hash', 'users'),
            "The users table must NOT have a legacy 'password_hash' column.",
        );
    }
}
