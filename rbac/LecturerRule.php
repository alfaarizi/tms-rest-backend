<?php

namespace app\rbac;

use app\models\Group;
use app\models\InstructorCourse;
use app\models\InstructorGroup;
use yii\rbac\Rule;

/**
 * Checks if the user is a lecturer of course or group.
 */
class LecturerRule extends Rule
{
    /**
     * @inheritDoc
     */
    public $name = 'isLecturer';

    /**
     * @inheritDoc
     */
    public function execute($user, $item, $params)
    {
        if (isset($params['courseID'])) {
            $ic = InstructorCourse::findOne(['userID' => $user, 'courseID' => $params['courseID']]);
            return !is_null($ic);
        }

        if (isset($params['groupID'])) {
            $group = Group::findOne($params['groupID']);
            if (is_null($group)) {
                return false;
            }
            $ic = InstructorCourse::findOne(['userID' => $user, 'courseID' => $group->courseID]);
            return !is_null($ic);
        }

        return false;
    }
}
