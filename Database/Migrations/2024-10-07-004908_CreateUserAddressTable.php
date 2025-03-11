<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserAddressTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
            ],
            'house_no' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
            ],
            'street' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'barangay' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'city' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'zip' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
            ],
        ]);

        // Add foreign key to user_auth_tbl.uuid
        $this->forge->addKey('uuid', true);
        $this->forge->addForeignKey('uuid', 'user_auth_tbl', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_address_tbl');
    }

    public function down()
    {
        $this->forge->dropTable('user_address_tbl');
    }
}
