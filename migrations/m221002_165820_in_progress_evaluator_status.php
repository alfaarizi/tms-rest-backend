<?php

use yii\db\Migration;

/**
 * Adds 'In Progress' value to evaluatorStatus column
 */
class m221002_165820_in_progress_evaluator_status extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            '{{%student_files}}',
            'evaluatorStatus',
            "ENUM('Not Tested','Legacy Failed','Compilation Failed','Execution Failed','Tests Failed','Passed','In Progress') NOT NULL"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn(
            '{{%student_files}}',
            'evaluatorStatus',
            "ENUM('Not Tested','Legacy Failed','Compilation Failed','Execution Failed','Tests Failed','Passed') NOT NULL"
        );
    }
}
