<?php

use yii\db\Migration;

/**
 * Adds 'password' field to 'tasks' table and 'verified' field to 'student_files' table
 */
class m220411_093626_password_protected_tasks extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%tasks}}', 'password', $this->string(255)->after('available'));
        $this->addColumn('{{%student_files}}', 'verified', $this->boolean()->notNull()->defaultValue(true)->after('isAccepted'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%tasks}}', 'password');
        $this->dropColumn('{{student_files}}', 'verified');
    }
}
