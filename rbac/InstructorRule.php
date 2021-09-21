<?php

namespace app\rbac;

use app\models\InstructorGroup;
use yii\rbac\Rule;

/**
 * Checks if the user is an instructor of a group or a course.
 * For courses optionally a semester can be passed.
 */
class InstructorRule extends Rule
{
    /**
     * @inheritDoc
     */
    public $name = 'isInstructor';

    /**
     * @inheritDoc
     */
    public function execute($user, $item, $params)
    {
        if (isset($params['groupID'])) {
            $ig = InstructorGroup::findOne(['userID' => $user, 'groupID' => $params['groupID']]);
            return !is_null($ig);
        }

        if (isset($params['courseID'])) {
            $igs = InstructorGroup::find()
                ->joinWith('group')
                ->where(['userID' => $user, 'courseID' => $params['courseID']]);

            if (isset($params['semesterID'])) {
                $igs = $igs->andWhere(['semesterID' => $params['semesterID']]);
            }

            return $igs->count() > 0;
        }

        return false;
    }
}
