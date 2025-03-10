<?php

use yii\db\Migration;

/**
 * Adds a 'sentCreatedEmail' boolean field to the 'tasks' table.
 *
 * The new field represents whether an email notification regarding the task creation
 * has been sent to students or not.
 */
class m241208_095307_add_sent_created_email_to_tasks extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // The value for existing tasks should be true, but for new tasks, it should be false.
        $this->addColumn('{{%tasks}}', 'sentCreatedEmail', $this->boolean()->notNull()->defaultValue(true));
        $this->alterColumn('{{%tasks}}', 'sentCreatedEmail', $this->boolean()->notNull()->defaultValue(false));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%tasks}}', 'sentCreatedEmail');
    }
}
