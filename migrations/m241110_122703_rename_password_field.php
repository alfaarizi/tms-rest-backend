<?php

use yii\db\Migration;

/**
 * Rename 'password' field in 'tasks' table to 'exitPassword'
 */
class m241110_122703_rename_password_field extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->renameColumn('{{%tasks}}', 'password', 'exitPassword');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->renameColumn('{{%tasks}}', 'exitPassword', 'password');
    }
}
