<?php

namespace exAuth\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'username'      => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'first_name'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'last_name'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'active'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
            'status_message' => ['type' => 'TEXT', 'null' => true],
            'last_login'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME'],
            'updated_at'    => ['type' => 'DATETIME'],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('users', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('users', true);
    }
}
