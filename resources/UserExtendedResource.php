<?php

namespace app\resources;

use app\components\openapi\generators\OAProperty;
use yii\helpers\ArrayHelper;

/**
 * Resource class for user settings
 */
class UserExtendedResource extends \app\models\User
{
    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        return [
            'name',
            'neptun',
            'email',
            'customEmail',
            'locale',
            'isStudent',
            'isFaculty',
            'isAdmin',
            'customEmailConfirmed',
            'notificationTarget',
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        return [];
    }
}
