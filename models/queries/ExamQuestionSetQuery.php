<?php

namespace app\models\queries;

use app\models\ExamQuestionSet;
use yii\db\ActiveQuery;

class ExamQuestionSetQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     * @return ExamQuestionSet[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return ExamQuestionSet|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @paramint  $userID
     * @return ExamQuestionSetQuery
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
     * @return ExamQuestionSetQuery
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
