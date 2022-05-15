<?php

namespace app\modules\instructor\resources;

use app\models\CodeCompassInstance;

/**
 * Resource class for module 'CodeCompassInstances'
 */
class CodeCompassInstanceResource extends CodeCompassInstance
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'id',
            'studentFileId',
            'instanceStarterUserId',
            'port',
            'status',
            'errorLogs',
            'username',
            'password'
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
