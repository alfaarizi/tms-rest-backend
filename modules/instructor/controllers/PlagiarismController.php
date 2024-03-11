<?php

namespace app\modules\instructor\controllers;

use app\components\plagiarism\AbstractPlagiarismFinder;
use app\exceptions\PlagiarismServiceException;
use app\models\AbstractPlagiarism;
use app\models\JPlagPlagiarism;
use app\models\MossPlagiarism;
use app\models\Semester;
use app\modules\instructor\resources\CreatePlagiarismResource;
use app\modules\instructor\resources\JPlagPlagiarismResource;
use app\modules\instructor\resources\MossPlagiarismResource;
use app\modules\instructor\resources\Resource;
use app\modules\instructor\resources\PlagiarismResource;
use Throwable;
use Yii;
use yii\base\ErrorException;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

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
    public function actionIndex(int $semesterID): ActiveDataProvider
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
    public function actionView(int $id): PlagiarismResource
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
        $type = $requestModel->type;

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
                'type' => $type,
            ]
        );

        if ($plagiarism !== null) {
            switch ($type) {
                case MossPlagiarism::ID:
                    $moss = $plagiarism->moss;
                    // Type juggling because `$requestModel->ignoreThreshold` can be string for some reason
                    if ($moss->ignoreThreshold != $requestModel->ignoreThreshold) {
                        $plagiarism = null;
                    }
                    break;
                case JPlagPlagiarism::ID:
                    $jplag = $plagiarism->jplag;
                    $dbFiles = explode("\n", $jplag->ignoreFiles);
                    $requestFiles = $requestModel->ignoreFiles;
                    sort($dbFiles);
                    sort($requestFiles);
                    if ($jplag->tune != $requestModel->tune || $dbFiles !== $requestFiles) {
                        $plagiarism = null;
                    }
                    break;
            }
        }

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
                    'type' => $type,
                    'generateTime' => date('Y-m-d H:i:s'),
                ]
            );

            $transaction = PlagiarismResource::getDb()->beginTransaction();
            $success = false;
            try {
                if (!$plagiarism->save()) {
                    $transaction->rollBack();
                } else {
                    $plagiarismType = $this->createPlagiarismType($plagiarism, $requestModel);
                    if (!$plagiarismType->save()) {
                        $transaction->rollBack();
                    } else {
                        $transaction->commit();
                        $success = true;
                    }
                }
            } catch (\Throwable $t) {
                $transaction->rollBack();
                throw $t;
            }

            if ($success) {
                $this->response->statusCode = 201;
                return $plagiarism;
            } elseif ($plagiarism->hasErrors()) {
                $this->response->statusCode = 422;
                return $plagiarism->errors;
            } elseif (isset($plagiarismType) && $plagiarismType->hasErrors()) {
                $this->response->statusCode = 422;
                return $plagiarismType->errors;
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
    public function actionUpdate(int $id)
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
    public function actionDelete(int $id): void
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
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'), 0, $e);
        }
    }


    /**
     * List available plagiarism check types. An empty list means that plagiarism detection is not configured, and should be hidden on the UI.
     * @return string[]
     *
     * @OA\Get(
     *     path="/instructor/plagiarism/services",
     *     operationId="instructor::PlagiarismController::actionServices",
     *     tags={"Instructor Plagiarism"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(type="string", enum=app\modules\instructor\resources\PlagiarismResource::POSSIBLE_TYPES)),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionServices(): array {
        return PlagiarismResource::getAvailableTypes();
    }


    /**
     * Send the given plagiarism check to the configured service
     * @throws HttpException
     * @throws ErrorException
     *
     * @OA\Post(
     *     path="/instructor/plagiarism/{id}/run",
     *     operationId="instructor::PlagiarismController::actionRun",
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
     *    @OA\Response(response=501, ref="#/components/responses/501"),
     *    @OA\Response(response=502, ref="#/components/responses/502"),
     * ),
     */
    public function actionRun(int $id): PlagiarismResource
    {
        $plagiarism = $this->findOwnPlagiarism($id);

        $finder = Yii::$container->get(AbstractPlagiarismFinder::class, [$plagiarism]);
        if (!$finder::isEnabled()) {
            throw new HttpException(
                501,
                Yii::t('app', 'The requested plagiarism type has been disabled since creating the request. Contact the administrator for more information.')
            );
        }

        // This may take some time.
        set_time_limit(1800);
        ini_set('default_socket_timeout', 900);

        try {
            $finder->start();
        } catch (PlagiarismServiceException $e) {
            throw new HttpException(502, $e->getMessage(), 0, $e);
        }

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

    private function createPlagiarismType(PlagiarismResource $plagiarism, CreatePlagiarismResource $data): AbstractPlagiarism
    {
        switch ($plagiarism->type) {
            case MossPlagiarism::ID:
                return new MossPlagiarismResource(
                    [
                        'plagiarismId' => $plagiarism->id,
                        'ignoreThreshold' => $data->ignoreThreshold,
                    ]
                );
            case JPlagPlagiarism::ID:
                return new JPlagPlagiarismResource(
                    [
                        'plagiarismId' => $plagiarism->id,
                        'tune' => $data->tune,
                        'ignoreFiles' => $data->ignoreFilesString,
                    ]
                );
            default:
                throw new ErrorException('Not existing plagiarism type.');
        }
    }
}
