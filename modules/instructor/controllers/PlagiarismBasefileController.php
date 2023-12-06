<?php

namespace app\modules\instructor\controllers;

use app\exceptions\AddFailedException;
use app\models\Group;
use app\models\Task;
use app\modules\instructor\resources\PlagiarismBasefileResource;
use app\modules\instructor\resources\UploadFailedResource;
use app\modules\instructor\resources\UploadPlagiarismBasefileResource;
use app\modules\instructor\resources\UploadPlagiarismBasefileResultResource;
use app\resources\IntIDListResource;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\StaleObjectException;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;

/**
 * @OA\PathItem(
 *   path="/instructor/plagiarism-basefile/{id}",
 *   @OA\Parameter(
 *      name="id",
 *      in="path",
 *      required=true,
 *      description="ID of the basefile",
 *      @OA\Schema(ref="#/components/schemas/int_id"),
 *   ),
 * ),
 */

/**
 * This class provides access to plagiarism base files for instructors
 */
class PlagiarismBasefileController extends BaseInstructorRestController
{
    protected function verbs()
    {
        return array_merge(parent::verbs(), [
            'index' => ['GET'],
            'by-tasks' => ['POST'],
            'download' => ['GET'],
            'create' => ['POST'],
            'delete' => ['DELETE']
        ]);
    }

    /**
     * List base files connected to the current user’s courses
     *
     * @OA\Get(
     *     path="/instructor/plagiarism-basefile",
     *     operationId="instructor::PlagiarismBasefileController::actionIndex",
     *     tags={"Instructor Plagiarism"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_PlagiarismBasefileResource_Read")),
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionIndex(): ActiveDataProvider
    {
        $courses = array_map(
            static fn (Group $group): int => $group->courseID,
            Group::find()->instructorAccessibleGroups(Yii::$app->user->id)->all()
        );
        return new ActiveDataProvider([
            'query' => PlagiarismBasefileResource::find()->where(['courseID' => $courses]),
            'pagination' => false,
        ]);
    }

    /**
     * List base files uploaded for the given tasks’ courses.
     *
     * Implemented as a POST endpoint instead of GET to overcome
     * browsers’ URL length limitations.
     *
     * @OA\Post(
     *     path="/instructor/plagiarism-basefile/by-tasks",
     *     operationId="instructor::PlagiarismBasefileController::actionByTasks",
     *     tags={"Instructor Plagiarism"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="new plagiarism check",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Common_IntIDListResource_ScenarioDefault"),
     *         )
     *     ),
     *     @OA\Response(
     *        response=200,
     *        description="successful operation",
     *        @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Instructor_PlagiarismBasefileResource_Read")),
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=422, ref="#/components/responses/422"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionByTasks(): ActiveDataProvider
    {
        $resource = new IntIDListResource();
        $resource->load(Yii::$app->request->post(), '');

        if (!$resource->validate()) {
            $this->response->statusCode = 422;
            return $resource->errors;
        }

        return new ActiveDataProvider([
            'query' => PlagiarismBasefileResource::find()
                ->joinWith('groups g')
                ->innerJoin(['t' => Task::find()->where(['id' => $resource->ids])], 't.groupID = g.id'),
            'pagination' => false,
        ]);
    }

    /**
     * Download a base file
     * @throws NotFoundHttpException
     *
     *  @OA\Get(
     *     path="/instructor/plagiarism-basefile/{id}/download",
     *     operationId="instructor::PlagiarismBasefileController::actionDownload",
     *     tags={"Instructor Plagiarism"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the file",
     *        @OA\Schema(ref="#/components/schemas/int_id"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionDownload(int $id)
    {
        $file = PlagiarismBasefileResource::findOne($id);

        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'The basefile does not exist.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['courseID' => $file->courseID])) {
            throw new NotFoundHttpException(Yii::t('app', 'You must be an instructor of the course to perform this action!'));
        }

        Yii::$app->response->sendFile(
            $file->path,
            $file->name,
            // The default of guessing based on the file name
            // doesn’t work since the physical file names are
            // 1, 2, ...; FileHelper::getMimeType() guesses
            // based on the content
            [ 'mimeType' => FileHelper::getMimeType($file->path) ]
        );
    }

    /**
     * Upload new base files
     * @return array|UploadPlagiarismBasefileResultResource
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\Post(
     *    path="/instructor/plagiarism-basefile",
     *    operationId="instructor::PlagiarismBasefileController::actionCreate",
     *    tags={"Instructor Plagiarism"},
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *        description="files to upload and course ID",
     *        @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(ref="#/components/schemas/Instructor_UploadPlagiarismBasefileResource_ScenarioDefault"),
     *        )
     *    ),
     *    @OA\Response(
     *        response=207,
     *        description="multistatus result",
     *        @OA\JsonContent(ref="#/components/schemas/Instructor_UploadPlagiarismBasefileResultResource_Read")
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionCreate()
    {
        $upload = new UploadPlagiarismBasefileResource();
        $upload->load(Yii::$app->request->post(), '');
        $upload->files = UploadedFile::getInstancesByName('files');

        if (!$upload->validate()) {
            $this->response->statusCode = 422;
            return $upload->errors;
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['courseID' => $upload->courseID])) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be an instructor of the course to perform this action!'));
        }

        // Create folder for the basefiles (if not exists)
        $dirPath = Yii::getAlias("@appdata/uploadedfiles/basefiles/");
        if (!file_exists($dirPath) && !is_dir($dirPath)) {
            FileHelper::createDirectory($dirPath, 0755, true);
        }

        $response = new UploadPlagiarismBasefileResultResource();
        foreach ($upload->files as $file) {
            try {
                $baseFile = new PlagiarismBasefileResource([
                    'name' => $file->name ?? null,
                    'courseID' => $upload->courseID,
                    'uploaderID' => Yii::$app->user->id,
                    'lastUpdateTime' => date('Y/m/d H:i:s'),
                ]);

                if (!$baseFile->save()) {
                    if ($baseFile->hasErrors()) {
                        throw new AddFailedException($baseFile->name, $baseFile->errors);
                    } else {
                        throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
                    }
                }

                if ($file->saveAs($baseFile->path, !YII_ENV_TEST)) {
                    $response->uploaded[] = $baseFile;
                } else {
                    $baseFile->forceDelete = true;
                    $baseFile->delete();
                    // Log
                    Yii::error(
                        "Failed to save file to the disc ($baseFile->path), error code: $file->error",
                        __METHOD__
                    );
                    throw new AddFailedException($baseFile->name, ['path' => Yii::t('app', 'Failed to save file. Error logged.')]);
                }
            } catch (AddFailedException $e) {
                $failedResource = new UploadFailedResource();
                $failedResource->name = $e->getIdentifier();
                $failedResource->cause = $e->getCause();
                $response->failed[] = $failedResource;
            }
        }

        $this->response->statusCode = 207;
        return $response;
    }

    /**
     * Delete a base file
     * @param int $id
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws BadRequestHttpException
     *
     * @OA\Delete(
     *    path="/instructor/plagiarism-basefile/{id}",
     *    operationId="instructor::PlagiarismBasefileController::actionDelete",
     *    tags={"Instructor Plagiarism"},
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       description="ID of the file",
     *       @OA\Schema(ref="#/components/schemas/int_id"),
     *    ),
     *    @OA\Response(
     *        response=204,
     *        description="base file deleted",
     *    ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionDelete(int $id)
    {
        $file = PlagiarismBasefileResource::findOne($id);
        if (is_null($file)) {
            throw new NotFoundHttpException(Yii::t('app', 'The basefile does not exist.'));
        }

        // Authorization check
        if (!$file->deletable) {
            throw new ForbiddenHttpException(Yii::t('app', 'You must be a lecturer of the course to delete a basefile of another user!'));
        }

        try {
            if ($file->delete()) {
                $this->response->statusCode = 204;
            } else {
                throw new \Exception(Yii::t('app', 'A database error occurred'));
            }
        } catch (StaleObjectException $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'Failed to remove BaseFile') . ' StaleObjectException:' . $e->getMessage());
        } catch (\Throwable $e) {
            throw new ServerErrorHttpException(Yii::t('app', 'Failed to remove BaseFile'), 0, $e);
        }
    }
}
