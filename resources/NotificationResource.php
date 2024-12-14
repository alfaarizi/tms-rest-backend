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
            'dismissible',
            'isGroupNotification',
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        return [];
    }

    public function getIsGroupNotification(): bool
    {
        return $this->scope == self::SCOPE_GROUP;
    }

    public function fieldTypes(): array
    {
        $types = parent::fieldTypes();

        $types['isGroupNotification'] = new OAProperty(['type' => 'boolean']);

        return $types;
    }
}
