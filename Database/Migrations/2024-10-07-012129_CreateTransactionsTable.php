<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'transaction_id' => [
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
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'payment_method' => [
                'type' => 'ENUM',
                'constraint' => ['cash', 'card', 'e-wallet'],
            ],
            'transaction_status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'paid', 'failed', 'refunded'],
                'default' => 'pending',
            ],
            'paid_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ]
        ]);

        $this->forge->addKey('transaction_id', true);
        $this->forge->addForeignKey('owner_uuid', 'user_auth_tbl', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('booker_uuid', 'user_auth_tbl', 'uuid', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('car_id', 'car_listings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('transactions');
    }

    public function down()
    {
        $this->forge->dropTable('transactions');
    }
}
