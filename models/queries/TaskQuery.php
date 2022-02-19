<?php

namespace app\models\queries;

use app\models\Task;
use yii\db\ActiveQuery;
use yii\db\Expression;

class TaskQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     * @return Task[]|array
     */
    public function all($db = null): array
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return array|\yii\db\ActiveRecord|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * Include student files for the tasks
     * @param int $userID
     * @return TaskQuery
     */
    public function withStudentFilesForUser(int $userID): TaskQuery
    {
        return $this->joinWith(
            [
                'studentFiles' => function (ActiveQuery $query) use ($userID) {
                    $query->andOnCondition(['uploaderID' => $userID]);
                }
            ],
            true,
            'LEFT JOIN'
        );
    }

    /**
     * Find available tasks
     * @return TaskQuery
     */
    public function findAvailable(): TaskQuery
    {
        return $this->andWhere(
            [
                'or',
                ['available' => null],
                ['<', 'available', new Expression('NOW()')]
            ]
        );
    }

    /**
     * Filter tasks by semesters
     * @param int $semesterFromID
     * @param int $semesterToID
     * @return TaskQuery
     */
    public function semesterInterval(int $semesterFromID, int $semesterToID): TaskQuery
    {
        if ($semesterToID === $semesterFromID) {
            $condition = ['semesterID' => $semesterFromID];
        } else {
            $condition = [
                'between',
                'semesterID',
                $semesterFromID,
                $semesterToID
            ];
        }

        return $this->andWhere($condition);
    }
}
