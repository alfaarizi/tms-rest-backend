<?php

namespace app\modules\instructor\controllers;

use app\models\Group;
use Yii;
use app\models\InstructorCourse;
use app\resources\CourseResource;

/**
 * This class provides access to courses for instructors
 */
class CoursesController extends BaseInstructorRestController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(parent::verbs(), [
            'index' => ['GET'],
        ]);
    }

    /**
     * Lists available courses for the current user
     * @param bool $instructor
     * @param bool $forGroups
     * @return array
     */
    public function actionIndex($instructor = true, $forGroups = false)
    {
        $instructor = filter_var($instructor, FILTER_VALIDATE_BOOLEAN);
        $forGroups = filter_var($forGroups, FILTER_VALIDATE_BOOLEAN);

        // Collect unique courses
        $courseMap = [];
        // Get courses from InstructorCourses
        if ($instructor) {
            foreach (InstructorCourse::find()->where(['userID' => Yii::$app->user->id])->all() as $ic) {
                $course = $ic->course;
                $courseMap[$course->id] = new CourseResource($course);
            }
        }

        // Get courses from InstructorGroups
        if ($forGroups) {
            foreach (Group::find()->joinWith('instructorGroups')->where(['userID' => Yii::$app->user->id])->all() as $ig) {
                $course = $ig->course;
                $courseMap[$course->id] = new CourseResource($course);
            }
        }

        // Convert key-value pairs into a regular array
        $courseList = [];
        foreach ($courseMap as $course) {
            $courseList[] = $course;
        }

        return $courseList;
    }
}
