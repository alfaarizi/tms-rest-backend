<?php

namespace app\modules\instructor\resources;

use app\models\TaskIpRestriction;

class TaskIpRestrictionResource extends TaskIpRestriction
{
    public function fields(): array
    {
        return [
            'id',
            'taskID',
            'ipAddress',
            'ipMask',
        ];
    }
}