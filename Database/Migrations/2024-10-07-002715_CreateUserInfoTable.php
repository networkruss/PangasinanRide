<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserInfoTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
            ],
            'first_name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'last_name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'gender' => [
                'type' => 'ENUM',
                'constraint' => ['male', 'female', 'other'],
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => '15',
            ],
            'avatar' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'driver_license' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
            ],
            'driver_license_expiry' => [
                'type' => 'DATE',
                'null' => true
            ],
            'vehicle_plate_number' => [
                'type' => "VARCHAR",
                'constraint' => '255'
            ],
            'ownership' => [
                'type' => "VARCHAR",
                'constraint' => '255',
                'null' => true
            ]
        ]);

        // Add foreign key to user_auth_tbl.uuid
        $this->forge->addKey('uuid', true);
        $this->forge->addForeignKey('uuid', 'user_auth_tbl', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_info_tbl');
    }

    public function down()
    {
        $this->forge->dropTable('user_info_tbl');
    }
}
