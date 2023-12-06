<?php

namespace app\controllers;

use Yii;
use yii\web\Request;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\Controller;

/**
 * This class is a base class for other REST API controllers
 *
 * @property Response $response
 * @property Request $request
 */
abstract class BaseRestController extends Controller
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

    public function actions()
    {
        return [
            'options' => [
                'class' => 'yii\rest\OptionsAction',
            ],
        ];
    }
}
