<?php

use yii\db\Migration;

/**
 * Removes 'Updated' from the possible values of the 'isAccepted' field in the 'student_files' table.
 */
class m220407_133258_student_files_remove_updated_is_accepted_status extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->update(
            '{{%student_files}}',
            ['isAccepted' => 'Uploaded'],
            ['isAccepted' => 'Updated'],
        );

        $this->alterColumn(
            '{{%student_files}}',
            'isAccepted',
            "ENUM('Uploaded','Accepted','Rejected','Late Submission','Passed','Failed') NOT NULL"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn(
            '{{%student_files}}',
            'isAccepted',
            "ENUM('Uploaded','Accepted','Rejected','Updated','Late Submission','Passed','Failed') NOT NULL"
        );


        $this->update(
            '{{%student_files}}',
            ['isAccepted' => 'Updated'],
            [
                'and',
                ['=', 'isAccepted', 'Uploaded'],
                ['>', 'uploadCount', '1']
            ]
        );
    }
}
