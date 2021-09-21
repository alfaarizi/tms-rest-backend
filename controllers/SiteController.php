<?php

namespace app\controllers;

use Yii;

/**
 * This class controls the common functionalities.
 */
class SiteController extends BaseRestController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        unset($behaviors['authenticator']);

        return $behaviors;
    }

    /**
     * Redirects to frontend application
     */
    public function actionIndex()
    {
        $this->redirect(Yii::$app->params['frontendUrl']);
    }

    /**
     * Generic error handler action
     * @return array|null
     */
    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;

        if (is_null($exception)) {
            return null;
        }

        return [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
        ];
    }
}
