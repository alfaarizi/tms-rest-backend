<?php

namespace app\modules\student\controllers;

use app\controllers\BaseRestController;
use yii\filters\AccessControl;

/**
 * Common logic in student rest controllers
 */
abstract class BaseStudentRestController extends BaseRestController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['student'],
                ],
            ]
        ];

        return $behaviors;
    }
}
