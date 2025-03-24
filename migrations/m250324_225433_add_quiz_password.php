<?php

use yii\db\Migration;

/**
 * Add entry level password for quizzes.
 */
class m250324_225433_add_quiz_password extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%quiz_tests}}', 'password', $this->string(255)->after('availableuntil'));
        $this->addColumn('{{%quiz_testinstances}}', 'token', $this->string()->after('userID'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%quiz_tests}}', 'password');
        $this->dropColumn('{{%quiz_testinstances}}', 'token');
    }
}
