<?php

namespace app\resources;

use app\components\openapi\generators\OAProperty;
use app\models\Notification;

/**
 * Resource class for module 'Notification'
 */
class NotificationResource extends Notification
{
    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        return [
            'id',
            'message',
            'scope',
            'dismissable',
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
