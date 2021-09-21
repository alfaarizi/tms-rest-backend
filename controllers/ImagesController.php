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
     * Fetches an exam image.
     * @param int $id The id of the questionset.
     * @param string $filename The filename of the image.
     * @param string|null $imageToken
     */
    public function actionViewExamImage($id, $filename, $imageToken = null)
    {
        $this->checkImageToken($imageToken);

        $image = new ExamImageResource($filename, $id);
        if (!file_exists($image->getFilePath())) {
            throw new NotFoundHttpException(Yii::t('app', 'File not found.'));
        }

        Yii::$app->response->sendFile($image->getFilePath());
    }

    /**
     * Validates imageToken
     * @param string|null $imageToken
     * @throws ForbiddenHttpException
     */
    private function checkImageToken($imageToken)
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
