<?php

use yii\db\Migration;

/**
 * Adds 'Corrupted' to the possible values of the 'isAccepted' field
 */
class m230509_100810_studentfile_add_corrupted_status extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            '{{%student_files}}',
            'isAccepted',
            "ENUM('Uploaded','Accepted','Rejected','Updated','Late Submission','Passed','Failed','Corrupted') NOT NULL"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{%student_files}}', 'isAccepted', "ENUM('Uploaded','Accepted','Rejected','Late Submission','Passed','Failed') NOT NULL");
    }
}
