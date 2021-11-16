<?php

namespace app\resources;

/**
 * Resource class for user settings
 */
class UserSettingsResource extends \app\models\User
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
