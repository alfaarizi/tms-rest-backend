<?php

namespace app\controllers;

use Yii;
use app\models\AccessToken;
use app\resources\ExamImageResource;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Image controller
 */
class ImagesController extends BaseRestController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Disable normal token based authenticator
        unset($behaviors['authenticator']);

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return ArrayHelper::merge(parent::verbs(), [
            'view-exam-image' => ['GET']
        ]);
    }

    /**
     * Fetches an exam image
     * @param int $id The id of the questionset.
     * @param string $filename The filename of the image.
     * @param string|null $imageToken
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/examination/image/{id}/{filename}",
     *     operationId="common::ImagesController::actionViewExamImage",
     *     tags={"Common Images"},
     *     @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="ID of the question set",
     *        @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(
     *        name="filename",
     *        in="path",
     *        required=true,
     *        description="Name of the image",
     *        @OA\Schema(type="string"),
     *     ),
     *     @OA\Parameter(
     *        name="imageToken",
     *        in="query",
     *        required=false,
     *        description="Image token",
     *        explode=true,
     *        @OA\Schema(type="string"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=404, ref="#/components/responses/404"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionViewExamImage(int $id, string $filename, ?string $imageToken = null): void
    {
        $this->checkImageToken($imageToken);

        $image = new ExamImageResource();
        $image->name = $filename;
        $image->questionSetID = $id;
        if (!file_exists($image->getFilePath())) {
            throw new NotFoundHttpException(Yii::t('app', 'File not found.'));
        }

        Yii::$app->response->sendFile($image->getFilePath());
    }

    /**
     * Validates imageToken
     * @throws ForbiddenHttpException
     */
    private function checkImageToken(?string $imageToken): void
    {
        if (is_null($imageToken)) {
            throw new ForbiddenHttpException(
                Yii::t("app", "You don't have permission to view this image")
            );
        }

        $imageToken = AccessToken::findOne(['imageToken' => $imageToken]);

        if (is_null($imageToken) || !$imageToken->checkValidation()) {
            throw new ForbiddenHttpException(
                Yii::t("app", "You don't have permission to view this image")
            );
        }
    }
}
