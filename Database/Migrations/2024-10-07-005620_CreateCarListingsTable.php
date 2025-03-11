<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCarListingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true
            ],
            'owner_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
            ],
            'brand' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'model' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'year' => [
                'type' => 'YEAR',
            ],
            'rental' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'max_speed' => [
                'type' => 'INT',
            ],
            'fuel_type' => [
                'type' => 'ENUM',
                'constraint' => ['petrol', 'diesel', 'hybrid', 'electric'],
            ],
            'seats' => [
                'type' => 'INT',
            ],
            'car_image' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'description' => [
                'type' => 'TEXT',
            ],
            'availability' => [
                'type' => 'ENUM',
                'constraint' => ['booked', 'available'],
                'default' => 'available',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['approved', 'declined', 'pending'],
                'default' => 'pending',
            ],
            'location' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'listed_on datetime default current_timestamp on update current_timestamp'
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('owner_uuid', 'user_auth_tbl', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->createTable('car_listings');
    }

    public function down()
    {
        $this->forge->dropTable('car_listings');
    }
}
