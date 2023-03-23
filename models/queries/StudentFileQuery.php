<?php

namespace app\models\queries;

use app\models\StudentFile;

class StudentFileQuery extends \yii\db\ActiveQuery
{
    /**
     * Find submissions by semester
     * @param int $semesterID
     * @return StudentFileQuery
     */
    public function findBySemester(int $semesterID): StudentFileQuery
    {
        return $this
            ->joinWith('task t')
            ->andWhere(['semesterID' => $semesterID]);
    }

    /**
     * Find tested submissions
     * @return StudentFileQuery
     */
    public function findTested(): StudentFileQuery
    {
        return $this
            ->andWhere(['not in',
                        'autoTesterStatus',
                        [
                            StudentFile::AUTO_TESTER_STATUS_IN_PROGRESS,
                            StudentFile::AUTO_TESTER_STATUS_NOT_TESTED
                        ]
                    ]);
    }

    /**
     * Find submissions under testing
     * @return StudentFileQuery
     */
    public function findUnderTesting(): StudentFileQuery
    {
        return $this->andWhere(['autoTesterStatus' => StudentFile::AUTO_TESTER_STATUS_IN_PROGRESS]);
    }

    /**
     * Find untested submissions
     * @param $taskIds
     * @return StudentFileQuery
     */
    public function notTested($taskIds): StudentFileQuery
    {
        return $this->andWhere(
            [
                'isAccepted' => StudentFile::IS_ACCEPTED_UPLOADED,
                'autoTesterStatus' => StudentFile::AUTO_TESTER_STATUS_NOT_TESTED,
                'taskID' => $taskIds
            ]
        );
    }
}
