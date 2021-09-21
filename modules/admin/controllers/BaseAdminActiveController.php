<?php

namespace app\modules\admin\controllers;

use yii\filters\AccessControl;

/**
 * Common logic in admin active controllers
 */
abstract class BaseAdminActiveController extends \app\controllers\BaseActiveController
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
