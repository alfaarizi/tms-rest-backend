<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Repopulates missing 'No submission' records for Canvas tasks.
 */
class m230923_235005_canvas_no_submission extends Migration
{
    private const BATCH_SIZE = 1000;

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Build a query to get all Canvas synchronized groups
        $groups = (new Query())
            ->select(['id'])
            ->from('{{%groups}}')
            ->where(['IS NOT', 'canvasCourseID', null]);

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
        // Delete StudentFile entries with 'No Submission' status belonging to Canvas synchronized groups
        $query = (new Query())
            ->select(['{{%student_files}}.id'])
            ->from('{{%student_files}}')
            ->innerJoin('{{%tasks}}', '{{%tasks}}.id = {{%student_files}}.taskID')
            ->innerJoin('{{%groups}}', '{{%groups}}.id = {{%tasks}}.groupID')
            ->where(['IS NOT', '{{%groups}}.canvasCourseID', null])
            ->andWhere(['{{%student_files}}.isAccepted' => 'No Submission']);
        $studentFileIds = $query->column();

        $this->delete('{{%student_files}}', ['in', 'id', $studentFileIds]);
    }
}
