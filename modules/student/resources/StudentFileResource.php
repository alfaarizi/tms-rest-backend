<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAProperty;
use Yii;
use yii\helpers\ArrayHelper;
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

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'graderName' => new OAProperty(['type' => 'string'])
            ]
        );
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
