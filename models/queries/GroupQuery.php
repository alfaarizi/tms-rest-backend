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
        // Collect the instructor/lecturer courses.
        $query = $this
            ->alias('g')
            ->joinWith(['instructorGroups ig', 'course.instructorCourses ic'])
            ->select('g.*')
            ->andWhere(
                [
                    'or',
                    ['ig.userID' => $userID],
                    ['ic.userID' => $userID]
                ]
            );

        if (!is_null($semesterID)) {
            $query->andWhere(['semesterID' => $semesterID]);
        }

        // Filter courses if courseID is not null
        if (!is_null($courseID)) {
            $query->andWhere(['ic.courseID' => $courseID]);
        }

        return $query;
    }
}
