<?php

namespace app\modules\admin\controllers;

use app\models\Group;
use app\models\Semester;
use app\models\Task;
use app\models\Submission;
use app\modules\admin\resources\StatisticsResource;
use app\modules\admin\resources\StatisticsSemesterResource;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * This class provides various statistics for admins
 */
class StatisticsController extends BaseAdminRestController
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
     * List statistics for administrators
     *
     * @OA\Get(
     *     path="/admin/statistics",
     *     operationId="admin::StatisticsController::actionIndex",
     *     tags={"Admin Statistics"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionIndex(): StatisticsResource
    {
        $statistics = new StatisticsResource();

        $statistics->groupsCount = Group::find()->count();
        $statistics->tasksCount = Task::find()->count();
        $statistics->submissionsCount = Submission::find()->where(['not', ['uploadCount' => 0]])->count();
        $statistics->testedSubmissionCount = Submission::find()
            ->findTested()
            ->count();
        $statistics->submissionsUnderTestingCount = Submission::find()
            ->findUnderTesting()
            ->count();

        // Get the IDs of tasks with autoTest enabled.
        $IDs = array_keys(
            Task::find()
                ->select('id')
                ->autoTestEnabled()
                ->asArray()
                ->indexBy('id')
                ->all()
        );

        $statistics->submissionsToBeTested = Submission::find()
            ->notTested($IDs)
            ->count();

        $path = Yii::getAlias("@appdata");
        if (is_dir($path)) {
            $statistics->diskFree = disk_free_space($path);
        } else {
            $statistics->diskFree = null;
        }

        return $statistics;
    }

    /**
     * List statistics for administrators
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/admin/statistics/view",
     *     operationId="admin::StatisticsController::actionView",
     *     tags={"Admin Statistics"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="semesterID",
     *         in="query",
     *         required=true,
     *         description="ID of the semester",
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionView(int $semesterID): StatisticsSemesterResource
    {
        $semester = Semester::findOne($semesterID);
        if (is_null($semester)) {
            throw new NotFoundHttpException(Yii::t("app", "Semester not found."));
        }

        $statistics = new StatisticsSemesterResource();

        $statistics->groupsCount = Group::find()
            ->findBySemester($semesterID)
            ->count();
        $statistics->tasksCount = Task::find()
            ->findBySemester($semesterID)
            ->count();
        $statistics->submissionsCount = Submission::find()
            ->where(['not', ['uploadCount' => 0]])
            ->findBySemester($semesterID)
            ->count();
        $statistics->testedSubmissionCount = Submission::find()
            ->findBySemester($semesterID)
            ->findTested()
            ->count();

        return $statistics;
    }
}
