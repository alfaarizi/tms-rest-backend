<?php

use yii\db\Migration;

/**
 * Add 'entry_password' field to 'tasks' table and create 'task_access_tokens' table for creating a relation between unlocked tasks and access tokens
 */
class m241104_153317_add_task_password_and_task_token extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%tasks}}', 'entryPassword', $this->string(255));

        $this->createTable('{{%task_access_tokens}}', [
            'id' => $this->primaryKey(),
            'accessToken' => $this->string()->notNull(),
            'taskId' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            '{{%task_access_tokens_ibfk_1}}',
            '{{%task_access_tokens}}',
            'accessToken',
            '{{%access_tokens}}',
            'token',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%tasks}}', 'entryPassword');

        $this->dropTable('{{%task_access_tokens}}');
    }
}
