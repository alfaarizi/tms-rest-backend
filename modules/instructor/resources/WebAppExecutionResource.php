<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\models\WebAppExecution;
use app\resources\UserResource;
use yii\helpers\ArrayHelper;

class WebAppExecutionResource extends WebAppExecution
{
    public function fields()
    {
        return [
            'id',
            'studentFileID',
            'instructorID',
            'startedAt',
            'shutdownAt',
            'port',
            'containerName',
            'url'
        ];
    }

    public function extraFields()
    {
        return [
            'studentFile',
            'instructor'
        ];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'studentFile' => new OAProperty(['ref' => '#/components/schemas/Instructor_StudentFileResource_Read']),
                'instructor' => new OAProperty(['ref' => '#/components/schemas/Common_UserResource_Read']),
            ]
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStudentFile(): \yii\db\ActiveQuery
    {
        return $this->hasOne(StudentFileResource::class, ['id' => 'studentFileID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstructor(): \yii\db\ActiveQuery
    {
        return $this->hasOne(UserResource::class, ['id' => 'instructorID']);
    }
}
