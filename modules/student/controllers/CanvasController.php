<?php

namespace app\modules\student\controllers;

use app\components\CanvasIntegration;
use app\exceptions\CanvasRequestException;
use app\models\Task;
use app\models\User;
use app\modules\student\helpers\PermissionHelpers;
use yii\base\Action;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use Yii;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * This class controls the canvas integration related actions for students.
 */
class CanvasController extends BaseStudentRestController
{
    private CanvasIntegration $canvas;

    /**
     * @param Action $action
     * @return bool
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (!Yii::$app->params['canvas']['enabled']) {
            throw new BadRequestHttpException(Yii::t('app', 'Canvas synchronization is disabled! Contact the administrator for more information.'));
        }

        $this->canvas = new CanvasIntegration();

        return true;
    }

    /**
     * @return array
     */
    protected function verbs(): array
    {
        return ArrayHelper::merge(
            parent::verbs(),
            [
                'sync-submission' => ['POST'],
            ]
        );
    }

    /**
     * Synchronize the selected user submission with Canvas
     * @param int $taskID the id of the selected task
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws CanvasRequestException
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     *
     * @OA\Post(
     *     path="/student/canvas/sync-submission",
     *     operationId="student::CanvasController::actionSyncSubmission",
     *     tags={"Student Canvas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *          name="taskID",
     *          in="query",
     *          required=true,
     *          description="ID of the task",
     *          explode=true,
     *          @OA\Schema(ref="#/components/schemas/int_id")
     *      ),
     *     @OA\Response(
     *         response=204,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized: missing, invalid or expired access or Canvas token.",
     *        @OA\JsonContent(ref="#/components/schemas/Yii2Error"),
     *    ),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=409, ref="#/components/responses/409"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionSyncSubmission(int $taskID)
    {
        $task = Task::findOne($taskID);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        PermissionHelpers::isItMyTask($taskID);
        $group = $task->group;

        // Setup is required before synchronization
        if (!$group->isCanvasCourse) {
            throw new ConflictHttpException(Yii::t('app', 'Synchronization is not configured for this group.'));
        }

        // Only Canvas tasks can be synchronized
        if ($task->category != Task::CATEGORY_TYPE_CANVAS_TASKS) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This operation can only be performed on a canvas synchronized task!')
            );
        }

        // Use original synchronizer when syncing group manually, else error
        if (empty($group->synchronizerID)) {
            throw new ConflictHttpException(Yii::t('app', 'Synchronization is not configured for this group.'));
        } else {
            $user = User::findIdentity($group->synchronizerID);
        }

        if (!$this->canvas->refreshCanvasToken($user)) {
            throw new ServerErrorHttpException(Yii::t('app', 'Failed to refresh Canvas Token.'));
        }

        $this->canvas->synchronizeSubmission($task);
        $this->response->statusCode = 204;
    }
}
