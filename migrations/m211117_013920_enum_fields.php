<?php

use yii\db\Migration;

/**
 * Use ENUM data type for fields with restricted value set.
 */
class m211117_013920_enum_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            '{{%tasks}}',
            'category',
            "ENUM('Smaller tasks','Larger tasks','Classwork tasks','Exams','Canvas tasks') NOT NULL"
        );
        $this->alterColumn(
            '{{%tasks}}',
            'testOS',
            "ENUM('linux','windows') NOT NULL DEFAULT 'linux'"
        );
        $this->alterColumn(
            '{{%student_files}}',
            'isAccepted',
            "ENUM('Uploaded','Accepted','Rejected','Updated','Late Submission','Passed','Failed') NOT NULL"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{%tasks}}', 'category', $this->string());
        $this->alterColumn('{{%tasks}}', 'testOS', $this->string());
        $this->alterColumn('{{%student_files}}', 'isAccepted', $this->string());
    }
}
