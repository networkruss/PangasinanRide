<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
            ],
            'owner_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
            ],
            'booker_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
            ],
            'car_id' => [
                'type' => 'INT',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['approved', 'declined', 'pending', 'completed', 'cancelled'],
                'default' => 'pending',
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'pickup_date' => [
                'type' => 'DATE',
            ],
            'dropoff_date' => [
                'type' => 'DATE',
            ],
            'pickup_location' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'dropoff_location' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'booked_at datetime default current_timestamp on update current_timestamp',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('owner_uuid', 'user_auth_tbl', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('booker_uuid', 'user_auth_tbl', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('car_id', 'car_listings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('booking');
    }

    public function down()
    {
        $this->forge->dropTable('booking');
    }
}
