<?php

namespace exAuth\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGroups extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'       => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'user_id'  => ['type' => 'INT', 'constraint' => 11],
            'group_id' => ['type' => 'VARCHAR', 'constraint' => 255],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('auth_groups_users', true);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11],
            'permission' => ['type' => 'VARCHAR', 'constraint' => 255],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('auth_permissions_users', true);

        $this->forge->addField([
            'id'       => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'user_id'  => ['type' => 'INT', 'constraint' => 11],
            'group_id' => ['type' => 'VARCHAR', 'constraint' => 255],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('auth_users_groups', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('auth_users_groups', true);
        $this->forge->dropTable('auth_permissions_users', true);
        $this->forge->dropTable('auth_groups_users', true);
    }
}
