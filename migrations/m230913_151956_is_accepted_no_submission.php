<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * This class adds a new possible value (No Submission) to the isAccepted column in student_files
 */
class m230913_151956_is_accepted_no_submission extends Migration
{
    private const BATCH_SIZE = 1000;

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            '{{%student_files}}',
            'isAccepted',
            "ENUM('No Submission', 'Uploaded','Accepted','Rejected','Late Submission','Passed','Failed','Corrupted') NOT NULL"
        );

        $this->alterColumn('{{%student_files}}', 'name', $this->string(200));
        $this->alterColumn('{{%student_files}}', 'uploadTime', $this->timestamp()->null());

        // Build a query to get all groups
        $groups = (new Query())
            ->select(['id'])
            ->from('{{%groups}}');

        // Count them
        $count = $groups->count();

        for ($i = 0; $i <= $count; $i += self::BATCH_SIZE) {
            // Get the current batch
            $groupIDs = $groups
                ->offset($i)
                ->limit(self::BATCH_SIZE)
                ->column();

            // Iterate through groups
            foreach ($groupIDs as $groupID) {
                // Get students in the group
                $studentIDs = (new Query())
                    ->select(['userID'])
                    ->from('{{%subscriptions}}')
                    ->where(['groupID' => $groupID])
                    ->column();

                // Get tasks in the group
                $tasks = (new Query())
                    ->select(['id', 'isVersionControlled'])
                    ->from('{{%tasks}}')
                    ->where(['groupID' => $groupID])
                    ->all();

                // Iterate through tasks
                foreach ($tasks as $task) {
                    // Get id of uploaders in task
                    $uploaderIDs = (new Query())
                        ->select(['uploaderID'])
                        ->from('{{%student_files}}')
                        ->where(['taskID' => $task['id']])
                        ->column();

                    // Iterate through students in the group
                    foreach ($studentIDs as $studentID) {
                        // If they have no submission for this task
                        if (!in_array($studentID, $uploaderIDs)) {
                            // Add a 'No Submission' student file to task
                            $this->insert('{{%student_files}}', [
                                'taskID' => $task['id'],
                                'isAccepted' => 'No Submission',
                                'autoTesterStatus' => 'Not Tested',
                                'uploaderID' => $studentID,
                                'name' => null,
                                'grade' => null,
                                'notes' => '',
                                'uploadTime' => null,
                                'isVersionControlled' => $task['isVersionControlled'],
                                'uploadCount' => 0,
                                'verified' => true
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%student_files}}', ['isAccepted' => 'No Submission']);

        $this->alterColumn(
            '{{%student_files}}',
            'isAccepted',
            "ENUM('Uploaded','Accepted','Rejected','Late Submission','Passed','Failed','Corrupted') NOT NULL"
        );

        $this->alterColumn('{{%student_files}}', 'name', $this->string(200)->notNull());
        $this->alterColumn('{{%student_files}}', 'uploadTime', $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'));
    }
}
