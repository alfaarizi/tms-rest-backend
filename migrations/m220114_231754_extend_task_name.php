<?php

use yii\db\Migration;

/**
 * Extend the name of tasks to 40 characters.
 */
class m220114_231754_extend_task_name extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%tasks}}', 'name', $this->string(40)->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{%tasks}}', 'name', $this->string(25)->notNull());
    }
}
