<?php

namespace app\controllers;

use Yii;
use yii\web\Request;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\ActiveController;

/**
 * This class is a base class for other REST API controllers.
 * ActiveControllers implement basic CRUD actions for ActiveRecord classes.
 *
 * @property Response $response
 * @property Request $request
 */
abstract class BaseActiveController extends ActiveController
{
    /**
     * @return array|array[]
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class
        ];

        $behaviors['contentNegotiator']['languages'] = array_keys(Yii::$app->params['supportedLocale']);

        return $behaviors;
    }
}
