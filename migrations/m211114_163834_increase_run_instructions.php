<?php

use yii\db\Migration;

/**
 * Increase Task::runInstructions field length from 255 chars to 1000.
 */
class m211114_163834_increase_run_instructions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%tasks}}', 'runInstructions', $this->string(1000));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{%tasks}}', 'runInstructions', $this->string());
    }
}
