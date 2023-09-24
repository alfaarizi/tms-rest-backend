<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Remove duplicated and orphaned no submission records.
 */
class m230923_105017_remove_duplicate_no_submissions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Delete orphaned 'No submission' records
        $query = new Query();
        $query->select(['{{%student_files}}.id'])
            ->from('{{%student_files}}')
            ->innerJoin('{{%tasks}}', '{{%tasks}}.id = {{%student_files}}.taskID')
            ->innerJoin('{{%groups}}', '{{%groups}}.id = {{%tasks}}.groupID')
            ->leftJoin('{{%subscriptions}}', '{{%subscriptions}}.groupID = {{%groups}}.id')
            ->where(
                [
                    '{{%subscriptions}}.groupID' => null,
                    '{{%student_files}}.isAccepted' => 'No Submission',
                    // `isAccepted` must be 'No Submission' if there is no `subscription`, just making this extra sure
                ]
            );

        $studentFileIds = $query->column();
        $this->delete('{{%student_files}}', ['in', 'id', $studentFileIds]);

        // Delete duplicated 'No submission' records
        // Query duplications occurrences
        $query = new Query();
        $query->select(
            [
                'taskID',
                'uploaderID',
                'COUNT(taskID) AS duplication'
            ]
        )
            ->from('{{%student_files}}')
            ->groupBy(['taskID', 'uploaderID'])
            ->having(['>', 'duplication', '1']);

        $duplications = $query->all();
        foreach ($duplications as $duplication) {
            $query = new Query();
            $query->select(['id', 'isAccepted'])
                ->from('{{%student_files}}')
                ->where(
                    [
                        'taskID' => $duplication['taskID'],
                        'uploaderID' => $duplication['uploaderID'],
                    ]
                );

            // Check whether all entries are 'No Submission' or not
            $studentFiles = $query->all();
            $isAllNoSubmission = true;
            foreach ($studentFiles as $studentFile) {
                if ($studentFile['isAccepted'] === 'No Submission') {
                    $isAllNoSubmission = false;
                    break;
                }
            }

            // Not all entries are 'No Submission' => delete all 'No Submission' entries
            if (!$isAllNoSubmission) {
                $this->delete('{{%student_files}}', [
                    'taskID' => $duplication['taskID'],
                    'uploaderID' => $duplication['uploaderID'],
                    'isAccepted' => 'No Submission'
                ]);
            } else {
                // All entries are 'No Submission' => keep only one
                array_shift($studentFiles); // remove first element
                $studentFileIds = array_map(static fn($studentFile) => $studentFile['id'], $studentFiles);
                $this->delete('{{%student_files}}', ['in', 'id', $studentFileIds]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // This migration removes corrupted DB entries, which can't be and shouldn't be reverted.
        return true;
    }
}
