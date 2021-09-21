<?php

namespace app\modules\admin\controllers;

use app\controllers\BaseRestController;
use yii\filters\AccessControl;

/**
 * Common logic in admin rest controllers
 */
abstract class BaseAdminRestController extends BaseRestController
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
                    'roles' => ['admin'],
                ],
            ]
        ];

        return $behaviors;
    }
}
