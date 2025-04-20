<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task_ip_restrictions}}`.
 */
class m240520_150134_create_task_ip_restriction_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task_ip_restrictions}}', [
            'id' => $this->primaryKey(),
            'taskID' => $this->integer(),
            'ipAddress' => $this->string(),
            'ipMask' => $this->string(),
        ]);

        $this->addForeignKey(
            '{{%task_ip_restrictions_ibfk_1}}',
            '{{%task_ip_restrictions}}',
            'taskID',
            '{{%tasks}}',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%task_ip_restrictions}}');
    }
}
