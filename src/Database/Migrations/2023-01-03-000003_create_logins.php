<?php

namespace exAuth\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLogins extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'date'       => ['type' => 'DATETIME'],
            'success'    => ['type' => 'INT', 'constraint' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('auth_logins', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('auth_logins', true);
    }
}
