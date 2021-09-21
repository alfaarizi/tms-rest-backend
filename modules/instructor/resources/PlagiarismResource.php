<?php

namespace app\modules\instructor\resources;

class PlagiarismResource extends \app\models\Plagiarism
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'id',
            'semesterID',
            'name',
            'description',
            'response',
            'ignoreThreshold'
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
