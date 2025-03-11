<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserAuthTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true
            ],
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'unique' => true,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'unique' => true,
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'role' => [
                'type' => 'ENUM',
                'constraint' => ['provider', 'admin', 'client'],
                'default' => 'client',
            ],
            'verified' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'verification_token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ]
        ]);

        $this->forge->addKey('id', true);  // Primary key
        $this->forge->createTable('user_auth_tbl');
    }

    public function down()
    {
        $this->forge->dropTable('user_auth_tbl');
    }
}
