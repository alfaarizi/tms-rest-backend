<?php

namespace app\modules\instructor\resources;

class PlagiarismBasefileResource extends \app\models\PlagiarismBasefile
{
    public function fields()
    {
        return [
            'id',
            'name',
            'lastUpdateTime',
        ];
    }

    public function extraFields()
    {
        return ['course'];
    }
}
