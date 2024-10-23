<?php

use yii\db\Migration;

/**
 * Rename some student submission related entity types and properties.
 *
 * Rename the student_files table to submissions.
 * Rename the isAccepted field in the student_files to status.
 * Rename the instructor_files table to task_files.
 */
class m241022_163226_rename_assignment_entities extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->renameColumn('{{%student_files}}', 'isAccepted', 'status');
        $this->renameColumn('{{%ip_address}}', 'studentFileId', 'submissionId');
        $this->renameColumn('{{%web_app_executions}}', 'studentFileID', 'submissionID');
        $this->renameColumn('{{%test_results}}', 'studentFileID', 'submissionID');
        $this->renameColumn('{{%codecompass_instances}}', 'studentFileId', 'submissionId');
        $this->renameColumn('{{%codechecker_results}}', 'studentFileID', 'submissionID');

        $this->renameTable('{{%student_files}}', '{{%submissions}}');
        $this->renameTable('{{%instructor_files}}', '{{%task_files}}');
    }


    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->renameTable('{{%submissions}}', '{{%student_files}}');
        $this->renameTable('{{%task_files}}', '{{%instructor_files}}');

        $this->renameColumn('{{%student_files}}', 'status', 'isAccepted');
        $this->renameColumn('{{%ip_address}}', 'submissionId', 'studentFileId');
        $this->renameColumn('{{%web_app_executions}}', 'submissionID', 'studentFileID');
        $this->renameColumn('{{%test_results}}', 'submissionID', 'studentFileID');
        $this->renameColumn('{{%codecompass_instances}}', 'submissionId', 'studentFileId');
        $this->renameColumn('{{%codechecker_results}}', 'submissionID', 'studentFileID');
    }
}
