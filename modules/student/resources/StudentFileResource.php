<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAProperty;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

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
            'uploadCount',
            'translatedIsAccepted',
            'grade',
            'notes',
            'isVersionControlled',
            'graderName',
            'errorMsg' => function($model) {
                return $model->safeErrorMsg;
            },
            'taskID',
            'verified',
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        return [
            'codeCheckerResult'
        ];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'graderName' => new OAProperty(['type' => 'string']),
                'codeCheckerResult' => new OAProperty(['ref' => '#/components/schemas/Student_CodeCheckerResultResource_Read']),
            ]
        );
    }

    /**
     * @return string
     */
    public function getGraderName(): string
    {
        if ($this->task->group->isExamGroup) {
            return '';
        }
        return $this->grader->name ?? '';
    }

    public function getCodeCheckerResult(): ActiveQuery
    {
        return $this->hasOne(CodeCheckerResultResource::class, ['id' => 'codeCheckerResultID']);
    }
}
