<?php

namespace app\resources;

/**
 * Resource class for module 'User'
 */
class UserResource extends \app\models\User
{
    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        return [
            'id',
            'neptun',
            'name',
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [];
    }
}
