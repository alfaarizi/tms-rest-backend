<?php

use yii\db\Migration;

/**
 * Class m230215_204314_rename_evaluator_status
 */
class m230215_204314_rename_evaluator_status extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->renameColumn('{{%student_files}}', 'evaluatorStatus', 'autoTesterStatus');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->renameColumn('{{%student_files}}', 'autoTesterStatus', 'evaluatorStatus');
    }
}
