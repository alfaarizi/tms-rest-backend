<?php

namespace app\modules\student\resources;

use Yii;
use yii\helpers\FileHelper;

/**
 * Resource class for module 'StudentFile'
 */
class StudentFileResource extends \app\models\StudentFile
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'id',
            'name',
            'uploadTime',
            'isAccepted',
            'translatedIsAccepted',
            'grade',
            'notes',
            'isVersionControlled',
            'graderName',
            'errorMsg'
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [];
    }

    /**
     * @return string|null
     */
    public function getGraderName()
    {
        if ($this->task->group->isExamGroup) {
            return '';
        }
        return $this->grader->name ?? '';
    }
}
