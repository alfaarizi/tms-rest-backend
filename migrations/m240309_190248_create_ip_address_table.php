<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%ip_address}}`.
 */
class m240309_190248_create_ip_address_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%ip_address}}', [
            'id' => $this->primaryKey(),
            'studentFileId' => $this->integer(),
            'ipAddress' => $this->string(),
        ]);

        $this->addForeignKey(
            '{{%ipAddress_ibfk_1}}',
            '{{%ip_address}}',
            'studentFileId',
            '{{%student_files}}',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%ip_address}}');
    }
}
