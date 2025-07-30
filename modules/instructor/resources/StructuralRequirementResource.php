<?php

namespace app\modules\instructor\resources;


use app\models\StructuralRequirement;

class StructuralRequirementResource extends StructuralRequirement
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'id',
            'taskID',
            'regexExpression',
            'type',
            'errorMessage',
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
