<?php

use yii\db\Migration;

/**
 * Modify Task::compileInstructions and Task::runInstructions field type to TEXT.
 */
class m220330_155019_increase_evaluator_instrunctions_length extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%tasks}}', 'compileInstructions', $this->text());
        $this->alterColumn('{{%tasks}}', 'runInstructions', $this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{%tasks}}', 'compileInstructions', $this->string(1000));
        $this->alterColumn('{{%tasks}}', 'runInstructions', $this->string(1000));
    }
}
