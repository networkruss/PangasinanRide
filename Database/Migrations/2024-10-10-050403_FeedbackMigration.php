<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FeedbackMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'feedback' => [
                'type' => 'TEXT'
            ],
            'rating' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'car_id' => [
                'type' => 'INT',
                'constraint' => 11
            ],
            'booker_uuid' => [
                'type' => 'VARCHAR',
                'constraint' => 36
            ],
            'created_at timestamp default current_timestamp',
            'updated_at timestamp default current_timestamp on update current_timestamp',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('feedbacks');
    }

    public function down()
    {
        $this->forge->dropTable('feedbacks');
    }
}
