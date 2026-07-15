<?php

namespace exAuth\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIdentities extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'user_id'       => ['type' => 'INT', 'constraint' => 11],
            'type'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'secret'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'secret2'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'extras'        => ['type' => 'TEXT', 'null' => true],
            'force_reset'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'last_used_at'  => ['type' => 'DATETIME', 'null' => true],
            'expires_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME'],
            'updated_at'    => ['type' => 'DATETIME'],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('auth_identities', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('auth_identities', true);
    }
}
