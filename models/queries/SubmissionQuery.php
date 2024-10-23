<?php

namespace app\models\queries;

use app\models\Submission;

class SubmissionQuery extends \yii\db\ActiveQuery
{
    /**
     * Find submissions by semester
     * @param int $semesterID
     * @return SubmissionQuery
     */
    public function findBySemester(int $semesterID): SubmissionQuery
    {
        return $this
            ->joinWith('task t')
            ->andWhere(['semesterID' => $semesterID]);
    }

    /**
     * Find tested submissions
     * @return SubmissionQuery
     */
    public function findTested(): SubmissionQuery
    {
        return $this
            ->andWhere(['not in',
                        'autoTesterStatus',
                        [
                            Submission::AUTO_TESTER_STATUS_IN_PROGRESS,
                            Submission::AUTO_TESTER_STATUS_NOT_TESTED
                        ]
                    ]);
    }

    /**
     * Find submissions under testing
     * @return SubmissionQuery
     */
    public function findUnderTesting(): SubmissionQuery
    {
        return $this->andWhere(['autoTesterStatus' => Submission::AUTO_TESTER_STATUS_IN_PROGRESS]);
    }

    /**
     * Find untested submissions
     * @param $taskIds
     * @return SubmissionQuery
     */
    public function notTested($taskIds): SubmissionQuery
    {
        return $this->andWhere(
            [
                'status' => Submission::STATUS_UPLOADED,
                'autoTesterStatus' => Submission::AUTO_TESTER_STATUS_NOT_TESTED,
                'taskID' => $taskIds
            ]
        );
    }
}
