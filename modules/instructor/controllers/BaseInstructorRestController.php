<?php

namespace app\modules\instructor\controllers;

use app\controllers\BaseRestController;
use yii\filters\AccessControl;

/**
 * Common logic in instructor rest controllers
 */
abstract class BaseInstructorRestController extends BaseRestController
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
                    'roles' => ['faculty', 'admin'],
                ],
            ]
        ];

        return $behaviors;
    }
}
