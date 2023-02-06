<?php

use yii\db\Migration;

/**
 * Adds 'Initiation Failed' value to evaluatorStatus column in student_files table
 */
class m230206_001311_add_initiation_failed_evaluator_status extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            '{{%student_files}}',
            'evaluatorStatus',
            "ENUM('Not Tested','Legacy Failed','Initiation Failed','Compilation Failed','Execution Failed','Tests Failed','Passed','In Progress') NOT NULL"
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
            "ENUM('Not Tested','Legacy Failed','Compilation Failed','Execution Failed','Tests Failed','Passed','In Progress') NOT NULL"
        );
    }
}
