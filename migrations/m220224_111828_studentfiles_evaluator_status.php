<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Adds a new column (evaluatorStatus) to student_files table and calculate its value for existing solutions
 */
class m220224_111828_studentfiles_evaluator_status extends Migration
{
    private const BATCH_SIZE = 1000;

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add new column with enum values
        $this->addColumn(
            '{{%student_files}}',
            'evaluatorStatus',
            "ENUM('Not Tested','Legacy Failed','Compilation Failed','Execution Failed','Tests Failed','Passed') NOT NULL"
        );


        // Count all StudentFiles
        $count = (new Query())
            ->select(['id'])
            ->from('{{%student_files}}')
            ->count();

        // Build a query to get log messages
        $query = (new Query())
            ->select(['id', 'errorMsg', 'isAccepted'])
            ->from('{{%student_files}}')
            ->orderBy(['id' => SORT_ASC]);

        // Update old files in batches
        for ($i = 0; $i <= $count; $i += self::BATCH_SIZE) {
            // Get the current batch
            $rows = $query
                ->offset($i)
                ->limit(self::BATCH_SIZE)
                ->all();

            foreach ($rows as $row) {
                $id = $row['id'];
                $isAccepted = $row['isAccepted'];
                $errorMsg = $row['errorMsg'];

                if (empty($errorMsg)) {
                    if ($isAccepted === 'Passed') {
                        $evaluatorStatus = 'Passed';
                    } elseif ($isAccepted === 'Failed') {
                        $evaluatorStatus = 'Legacy Failed';
                    } else {
                        $evaluatorStatus = 'Not Tested';
                    }
                } else {
                    if ($errorMsg === 'Your solution passed the tests' || $errorMsg === 'A megoldás átment a teszteken') {
                        $evaluatorStatus = 'Passed';
                    } else {
                        $evaluatorStatus = 'Legacy Failed';
                    }
                }

                $this->update(
                    '{{%student_files}}',
                    ['evaluatorStatus' => $evaluatorStatus],
                    ['id' => $id]
                );
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%student_files}}', 'evaluatorStatus');
        return true;
    }
}
