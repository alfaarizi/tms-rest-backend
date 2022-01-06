<?php

namespace app\modules\instructor\resources;

class TestCaseResource extends \app\models\TestCase
{
    public function fields()
    {
        return [
            'id',
            'arguments',
            'input',
            'output',
            'taskID'
        ];
    }

    public function extraFields()
    {
        return [];
    }
}
