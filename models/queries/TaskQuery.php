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
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return Task|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @param int $userID
     * @return TaskQuery
     */
    public function withStudentFilesForUser($userID)
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
     * @return TaskQuery
     */
    public function findAvailable()
    {
        return $this->andWhere(
            [
                'or',
                ['available' => null],
                ['<', 'available', new Expression('NOW()')]
            ]
        );
    }
}
