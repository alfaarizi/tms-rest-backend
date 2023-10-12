<?php

namespace app\modules\admin\controllers;

use app\models\Group;
use app\models\Semester;
use app\models\Task;
use app\models\StudentFile;
use app\modules\admin\resources\StatisticsResource;
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
     * @param int $semesterID
     * @return StatisticsResource
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/admin/statistics",
     *     operationId="admin::StatisticsController::actionIndex",
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
    public function actionIndex(int $semesterID)
    {
        $semester = Semester::findOne($semesterID);
        if (is_null($semester)) {
            throw new NotFoundHttpException(Yii::t("app", "Semester not found."));
        }

        $statistics = new StatisticsResource();

        $statistics->groupsCount = Group::find()->count();
        $statistics->tasksCount = Task::find()->count();
        $statistics->submissionsCount = StudentFile::find()->where(['not', ['uploadCount' => 0]])->count();
        $statistics->testedSubmissionCount = StudentFile::find()
            ->findTested()
            ->count();
        $statistics->groupsCountPerSemester = Group::find()
            ->findBySemester($semesterID)
            ->count();
        $statistics->tasksCountPerSemester = Task::find()
            ->findBySemester($semesterID)
            ->count();
        $statistics->submissionsCountPerSemester = StudentFile::find()
            ->where(['not', ['uploadCount' => 0]])
            ->findBySemester($semesterID)
            ->count();
        $statistics->testedSubmissionCountPerSemester = StudentFile::find()
            ->findBySemester($semesterID)
            ->findTested()
            ->count();
        $statistics->submissionsUnderTestingCount = StudentFile::find()
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

        $statistics->submissionsToBeTested = StudentFile::find()
            ->notTested($IDs)
            ->count();

        $path = Yii::getAlias("@appdata");
        if(is_dir($path)) {
            $statistics->diskFree = disk_free_space($path);
        } else {
            $statistics->diskFree = null;
        }

        return $statistics;
    }
}
