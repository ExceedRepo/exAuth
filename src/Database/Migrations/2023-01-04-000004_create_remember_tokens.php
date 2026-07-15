<?php

namespace exAuth\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRememberTokens extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11],
            'selector'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'hash'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'expires'    => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('auth_remember_tokens', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('auth_remember_tokens', true);
    }
}
