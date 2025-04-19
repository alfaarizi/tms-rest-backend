<?php

namespace app\models\queries;

use app\models\Group;
use yii\db\ActiveQuery;

class GroupQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     * @return Group[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return Group|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @param $semesterID
     * @return GroupQuery
     */
    public function findBySemester($semesterID)
    {
        return $this->andWhere(['semesterID' => $semesterID]);
    }

    /**
     * @param $studentID
     * @param $semesterID
     * @return GroupQuery
     */
    public function findForStudent($studentID, $semesterID)
    {
        return $this->joinWith('subscriptions s')
            ->where(
                [
                    's.userID' => $studentID,
                    's.semesterID' => $semesterID
                ]
            );
    }

    public function instructorAccessibleGroups(int $userID, ?int $semesterID = null, ?int $courseID = null): self
    {
        // Select all the groups where either:
        // ic.userID === $userID -> my course, I can view every group
        // ig.userID === $userID -> my group
        $query = $this->alias('g')
            ->joinWith(['instructorGroups ig', 'course.instructorCourses ic'])
            ->select('g.*')
            ->andWhere(['or', ['ig.userID' => $userID], ['ic.userID' => $userID]]);

        if (!is_null($semesterID)) {
            $query->andWhere(['g.semesterID' => $semesterID]);
        }

        // Important to restrict g.courseID instead of ic.courseID
        if (!is_null($courseID)) {
            $query->andWhere(['g.courseID' => $courseID]);
        }

        return $query;
    }
}
