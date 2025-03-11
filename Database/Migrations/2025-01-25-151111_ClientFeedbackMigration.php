<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ClientFeedbackMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true
            ],
            'booker_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
            ],
            'provider_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
            ],
            'car_id' => [
                'type' => 'INT',
            ],
            'comment' => [
                'type' => 'TEXT',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('client_feedback');
    }

    public function down()
    {
        $this->forge->dropTable('client_feedback');
    }
}
