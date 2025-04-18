<?php

namespace app\controllers;

use Yii;
use yii\web\Request;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;
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
            'class' => HttpBearerAuth::class,
            // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
            'except' => ['options'],
        ];

        $behaviors['contentNegotiator']['languages'] = array_keys(Yii::$app->params['supportedLocale']);

        if (isset(Yii::$app->params['cors']) && !empty(Yii::$app->params['cors'])) {
            $domains = Yii::$app->params['cors'];
            if (!is_array($domains)) {
                $domains = [$domains];
            }

            $behaviors['cors'] =
                [
                    'class' => \yii\filters\Cors::class,
                    'cors' => [
                        'Origin' => $domains,
                        'Access-Control-Request-Method' => ['*'],
                        'Access-Control-Request-Headers' => ['*'],
                        'Access-Control-Allow-Credentials' => true,
                        'Access-Control-Max-Age' => 86400,
                    ],
                ];
        }

        return $behaviors;
    }
}
