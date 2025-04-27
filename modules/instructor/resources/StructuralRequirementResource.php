<?php

namespace app\modules\instructor\resources;


use app\models\StructuralRequirements;

class StructuralRequirementResource extends StructuralRequirements
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
