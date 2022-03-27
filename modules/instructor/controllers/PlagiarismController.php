<?php

namespace app\modules\instructor\controllers;

use app\components\plagiarism\MossPlagiarismFinder;
use app\models\Semester;
use app\modules\instructor\resources\CreatePlagiarismResource;
use app\modules\instructor\resources\PlagiarismResource;
use Throwable;
use Yii;
use yii\base\ErrorException;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use ZIPARCHIVE;

/**
 * @OA\PathItem(
 *   path="/instructor/plagiarism/{id}",
 *   @OA\Parameter(
 *      name="id",
 *      in="path",
 *      required=true,
 *      description="ID of the plagiarism check",
 *      @OA\Schema(ref="#/components/schemas/int_id"),
 *   ),
 * ),
 */

/**
 * This class provides access to plagiarism checks for instructors
 */
class PlagiarismController extends BaseInstructorRestController
{
    protected function verbs()
    {
        return ArrayHelper::merge(
            parent::verbs(),
            [
                'index' => ['GET'],
                'view' => ['GET'],
                'create' => ['POST'],
                'delete' => ['DELETE'],
                'update' => ['PATCH', 'PUT'],
                'run-moss' => ['POST'],
            ]
        );
    }

    /**
     * List plagiarism checks from the given semester
     * @param $semesterID
     * @return ActiveDataProvider
     *
     * @OA\Get(
     *     path="/instructor/plagiarism",
     *     operationId="instructor::PlagiarismController::actionIndex",
     *     tags={"Instructor Plagiarism"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_sort"),
     *     @OA\Parameter(
     *         name="semesterID",
     *         in="query",
     *         required=true,
     *         description="ID of the semester",
     *         explode=true,
     *         @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_PlagiarismResource_Read")),
     *    ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionIndex($semesterID)
    {
        // Collect the instructor's plagiarism validations.
        return new ActiveDataProvider(
            [
                'query' => PlagiarismResource::find()->where(
                    [
                        'requesterID' => Yii::$app->user->id,
                        'semesterID' => $semesterID
                    ]
                ),
                'sort' => [
                    'defaultOrder' => [
                        'id' => SORT_DESC,
                    ]
                ],
                'pagination' => false
            ]
        );
    }

    /**
     * View a plagiarism check
     * @param $id
     * @return PlagiarismResource
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/plagiarism/{id}",
     *     operationId="instructor::PlagiarismController::actionView",
     *     tags={"Instructor Plagiarism"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_PlagiarismResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionView($id)
    {
        $model = PlagiarismResource::findOne($id);

        if (is_null($model)) {
            throw new NotFoundHttpException(Yii::t("app", "Request not found"));
        }

        if ($model->requesterID != Yii::$app->user->id) {
            throw new ForbiddenHttpException(Yii::t("app", "You don't have permission to view this request"));
        }

        return $model;
    }

    /**
     * Create a new plagiarism check or return an existing one with the same parameters if it exists
     * @return PlagiarismResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Post(
     *     path="/instructor/plagiarism",
     *     operationId="instructor::PlagiarismController::actionCreate",
     *     tags={"Instructor Plagiarism"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="new plagiarism check",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_CreatePlagiarismResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="existing plagiarism check returned",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_PlagiarismResource_Read"),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="new plagiarism check created",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_PlagiarismResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionCreate()
    {
        $requestModel = new CreatePlagiarismResource();
        $requestModel->load(Yii::$app->request->post(), '');
        if (!$requestModel->validate()) {
            $this->response->statusCode = 422;
            return $requestModel->errors;
        }

        // Get the parameters.
        $taskIDs = implode(',', $requestModel->selectedTasks);
        $userIDs = implode(',', $requestModel->selectedStudents);
        $basefileIDs = implode(',', $requestModel->selectedBasefiles);
        $ignoreThreshold = $requestModel->ignoreThreshold;

        // Check if the request already presents in the db.
        $requesterID = Yii::$app->user->id;
        $semesterID = Semester::getActualID();
        $plagiarism = PlagiarismResource::findOne(
            [
                'requesterID' => $requesterID,
                'taskIDs' => $taskIDs,
                'userIDs' => $userIDs,
                'baseFileIDs' => $basefileIDs,
                'semesterID' => $semesterID,
                'ignoreThreshold' => $ignoreThreshold
            ]
        );

        // If the request is a new one create it.
        if (is_null($plagiarism)) {
            // Get the name and description
            $name = $requestModel->name;
            $description = $requestModel->description;

            $plagiarism = new PlagiarismResource(
                [
                    'requesterID' => $requesterID,
                    'taskIDs' => $taskIDs,
                    'userIDs' => $userIDs,
                    'baseFileIDs' => $basefileIDs,
                    'semesterID' => $semesterID,
                    'name' => $name,
                    'description' => $description,
                    'ignoreThreshold' => $ignoreThreshold
                ]
            );

            if ($plagiarism->save()) {
                $this->response->statusCode = 201;
                return $plagiarism;
            } elseif ($plagiarism->hasErrors()) {
                $this->response->statusCode = 422;
                return $plagiarism->errors;
            } else {
                throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
            }
        } else {
            if ($plagiarism->requesterID != Yii::$app->user->id) {
                throw new ForbiddenHttpException(Yii::t("app", "You don't have permission to view this request"));
            }

            $this->response->statusCode = 200;
            return $plagiarism;
        }
    }

    /**
     * Update a plagiarism check
     * @param int $id
     * @return PlagiarismResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Put(
     *     path="/instructor/plagiarism/{id}",
     *     operationId="instructor::PlagiarismController::actionUpdate",
     *     tags={"Instructor Plagiarism"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="updated plagiarism check",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Instructor_PlagiarismResource_ScenarioUpdate"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="plagiarism check updated",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_PlagiarismResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionUpdate($id)
    {
        $model = PlagiarismResource::findOne($id);

        if (is_null($model)) {
            throw new NotFoundHttpException(Yii::t("app", "Request not found"));
        }

        if ($model->requesterID != Yii::$app->user->id) {
            throw new ForbiddenHttpException(Yii::t("app", "You don't have permission to view this request"));
        }

        // Check semester
        if ($model->semesterID !== Semester::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a request from a previous semester!")
            );
        }

        $model->scenario = PlagiarismResource::SCENARIO_UPDATE;
        $model->load(Yii::$app->request->post(), '');

        if ($model->save()) {
            return $model;
        } elseif ($model->hasErrors()) {
            $this->response->statusCode = 422;
            return $model->errors;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Delete a plagiarism check
     * @param int $id
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Delete(
     *     path="/instructor/plagiarism/{id}",
     *     operationId="instructor::PlagiarismController::actionDelete",
     *     tags={"Instructor Plagiarism"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=204,
     *         description="plagiarism check deleted",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDelete($id)
    {
        $model = PlagiarismResource::findOne($id);

        if (is_null($model)) {
            throw new NotFoundHttpException(Yii::t("app", "Request not found"));
        }

        if ($model->requesterID != Yii::$app->user->id) {
            throw new ForbiddenHttpException(Yii::t("app", "You don't have permission to view this request"));
        }

        // Check semester
        if ($model->semesterID !== Semester::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a request from a previous semester!")
            );
        }

        try {
            $model->delete();
            $this->response->statusCode = 204;
        } catch (Throwable $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }


    /**
     * Send the given plagiarism check to the Moss service
     * @param int $id
     * @return PlagiarismResource
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ErrorException
     *
     * @OA\Post(
     *     path="/instructor/plagiarism/{id}/run-moss",
     *     operationId="instructor::PlagiarismController::actionRunMoss",
     *     tags={"Instructor Plagiarism"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the plagiarism check",
     *         @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful run, plagiarism check updated",
     *         @OA\JsonContent(ref="#/components/schemas/Instructor_PlagiarismResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionRunMoss($id)
    {
        if (Yii::$app->params['mossId'] === '') {
            throw new BadRequestHttpException(
                Yii::t('app', 'Moss is disabled. Contact the administrator for more information.')
            );
        }

        $plagiarism = $this->findOwnPlagiarism($id);

        // This may take some time.
        set_time_limit(1800);
        ini_set('default_socket_timeout', 900);

        (new MossPlagiarismFinder($id))->start();

        return $plagiarism;
    }

    /**
     * Find own plagiarism with the given ID, or finish the request
     * handling immediately with a 404 status code if not found.
     * @throws NotFoundHttpException if the plagiarism doesn’t exist, or belongs to another user.
     */
    private function findOwnPlagiarism(int $id): PlagiarismResource
    {
        $plagiarism = PlagiarismResource::findOne(['id' => $id, 'requesterID' => Yii::$app->user->id]);
        if ($plagiarism) {
            return $plagiarism;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The plagiarism check does not exist or belongs to another user.'));
        }
    }
}
