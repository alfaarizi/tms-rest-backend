<?php

namespace app\models\queries;

use app\models\QuizQuestionSet;
use yii\db\ActiveQuery;

class QuizQuestionSetQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     * @return QuizQuestionSet[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return QuizQuestionSet|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @paramint  $userID
     * @return QuizQuestionSetQuery
     */
    public function listForOwnedCourses($userID)
    {
        return $this
            ->alias('qs')
            ->select('qs.*')
            ->joinWith('course.instructorCourses')
            ->andWhere(['userID' => $userID])
            ->orderBy('courseID');
    }

    /**
     * @param int $userID
     * @param int $semesterID
     * @return QuizQuestionSetQuery
     */
    public function listForOwnedGroups($userID, $semesterID)
    {
        return $this
            ->alias('qs')
            ->joinWith('course c')
            ->innerJoin('{{%groups}} g', 'c.id = g.courseID')
            ->innerJoin('{{%instructor_groups}} ig', 'g.id = ig.groupID')
            ->select('qs.*')
            ->andWhere(['userID' => $userID, 'semesterID' => $semesterID]);
    }
}
