<?php

use yii\db\Migration;

/**
 * Increase the QuizQuestionSet::name field length to 100  chars from 45.
 */
class m250522_212406_increase_questionset_name_length extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            '{{%quiz_questionsets}}',
            'name',
            $this->string(100)->notNull()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn(
            '{{%quiz_questionsets}}',
            'name',
            $this->string(45)->notNull()
        );
    }
}
