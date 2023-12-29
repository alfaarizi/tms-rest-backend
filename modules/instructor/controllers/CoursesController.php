<?php

namespace app\modules\instructor\controllers;

use app\models\Group;
use Yii;
use app\models\InstructorCourse;
use app\resources\CourseResource;
use yii\data\ArrayDataProvider;

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
     *
     *  @OA\Get(
     *     path="/instructor/courses",
     *     operationId="instructor::CoursesController::actionIndex",
     *     tags={"Instructor Courses"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="instuctor",
     *         in="query",
     *         required=false,
     *         description="List courses where the current user is a lecturer",
     *         explode=true,
     *         @OA\Schema(
     *             type="boolean",
     *             default=true
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="forGroups",
     *         in="query",
     *         required=false,
     *         description="List courses where the current user is a group instructor",
     *         explode=true,
     *         @OA\Schema(
     *             type="boolean",
     *             default=false
     *         )
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Common_CourseResource_Read")),
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionIndex(bool $instructor = true, bool $forGroups = false): ArrayDataProvider
    {
        // Collect unique courses
        $courseMap = [];
        // Get courses from InstructorCourses
        if ($instructor) {
            /** @var InstructorCourse[] $instructorCourses */
            $instructorCourses = InstructorCourse::find()->where(['userID' => Yii::$app->user->id])->all();
            foreach ($instructorCourses as $ic) {
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

        return new ArrayDataProvider([
            'allModels' => $courseList,
            'modelClass' => CourseResource::class,
            'pagination' => false
        ]);
    }
}
