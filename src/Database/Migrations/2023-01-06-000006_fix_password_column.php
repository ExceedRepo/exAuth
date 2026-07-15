<?php

namespace exAuth\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Converges installs migrated with older exAuth versions (which created a
 * `password_hash` column) to the current schema, which uses a `password`
 * column. No-op for fresh installs that already have `password`.
 */
class FixPasswordColumn extends Migration
{
    public function up(): void
    {
        if (
            $this->db->fieldExists('password_hash', 'users')
            && ! $this->db->fieldExists('password', 'users')
        ) {
            $this->forge->modifyColumn('users', [
                'password_hash' => [
                    'name'       => 'password',
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if (
            $this->db->fieldExists('password', 'users')
            && ! $this->db->fieldExists('password_hash', 'users')
        ) {
            $this->forge->modifyColumn('users', [
                'password' => [
                    'name'       => 'password_hash',
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
            ]);
        }
    }
}
