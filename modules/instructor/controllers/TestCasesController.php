<?php

namespace app\modules\instructor\controllers;

use app\modules\instructor\resources\TaskResource;
use app\modules\instructor\resources\TestCaseResource;
use app\resources\SemesterResource;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class TestCasesController
 */
class TestCasesController extends BaseInstructorRestController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(parent::verbs(), [
            'index' => ['GET'],
            'create' => ['POST'],
            'update' => ['PUT', 'PATCH'],
            'delete' => ['DELETE']
        ]);
    }

    public function actionIndex($taskID)
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(Yii::t('app', 'Auto tester is disabled. Contact the administrator for more information.'));
        }

        $task = TaskResource::findOne($taskID);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        $query = TestCaseResource::find()->where(['taskID' => $taskID]);

        return new ActiveDataProvider(
            [
                'query' => $query,
                'pagination' => false
            ]
        );
    }

    /**
     * @return TestCaseResource|array
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public function actionCreate()
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(Yii::t('app', 'Auto tester is disabled. Contact the administrator for more information.'));
        }

        $model = new TestCaseResource();
        $model->scenario = TestCaseResource::SCENARIO_CREATE;

        $model->load(Yii::$app->request->post(), '');

        if (!$model->validate()) {
            $this->response->statusCode = 422;
            return $model->errors;
        }

        $task = TaskResource::findOne($model->taskID);

        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        // Access check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        if (!$model->save(false)) {
            throw new ServerErrorHttpException(Yii::t("app", "A database error occurred"));
        }

        $this->response->statusCode = 201;
        return $model;
    }

    /**
     * @param int $id
     * @return TestCaseResource|array
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(Yii::t('app', 'Auto tester is disabled. Contact the administrator for more information.'));
        }

        $model = TestCaseResource::findOne($id);

        if (is_null($model)) {
            throw new NotFoundHttpException(Yii::t('app', 'Test case not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $model->task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        // Check semester
        if ($model->task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        $model->scenario = TestCaseResource::SCENARIO_UPDATE;
        $model->load(Yii::$app->request->post(), '');

        if (!$model->validate()) {
            $this->response->statusCode = 422;
            return $model->errors;
        }

        if (!$model->save(false)) {
            throw new ServerErrorHttpException(Yii::t("app", "A database error occurred"));
        }
        return $model;
    }

    /**
     * @param int $id
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws NotFoundHttpException
     */
    public function actionDelete($id)
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(Yii::t('app', 'Auto tester is disabled. Contact the administrator for more information.'));
        }

        $model = TestCaseResource::findOne($id);

        if (is_null($model)) {
            throw new NotFoundHttpException(Yii::t('app', 'Test case not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $model->task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        // Check semester
        if ($model->task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        try {
            $model->delete();
            $this->response->statusCode = 204;
        } catch (yii\base\ErrorException $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }
}
