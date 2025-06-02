<?php

namespace app\modules\instructor\resources;

use app\components\GitManager;
use app\models\IpAddress;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\generators\OAItems;
use app\modules\instructor\resources\CodeCheckerResultResource;
use Yii;
use app\models\Submission;
use app\resources\UserResource;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

class SubmissionResource extends Submission
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'id',
            'name',
            'status',
            'grade',
            'notes',
            'isVersionControlled',
            'translatedStatus',
            'graderName',
            'errorMsg',
            'taskID',
            'groupID',
            'gitRepo',
            'uploaderID',
            'uploadTime',
            'personalDeadline',
            'delay',
            'uploadCount',
            'verified',
            'codeCompassID',
            'ipAddresses'
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'uploader',
            'grader',
            'task',
            'execution',
            'codeCompass',
            'codeCheckerResult',
        ];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'graderName' => new OAProperty(['type' => 'string']),
                'delay' => new OAProperty(['type' => 'string']),
                'gitRepo' => new OAProperty(['type' => 'string', 'nullable' => 'true']),
                'uploader' => new OAProperty(['ref' => '#/components/schemas/Common_UserResource_Read']),
                'grader' => new OAProperty(['ref' => '#/components/schemas/Common_UserResource_Read']),
                'task' => new OAProperty(['ref' => '#/components/schemas/Instructor_TaskResource_Read']),
                'execution' => new OAProperty(['ref' => '#/components/schemas/Instructor_WebAppExecutionResource_Read']),
                'codeCompass' => new OAProperty(['ref' => '#/components/schemas/Instructor_CodeCompassInstanceResource_Read']),
                'codeCompassID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
                'codeCheckerResult' => new OAProperty(['ref' => '#/components/schemas/Instructor_CodeCheckerResultResource_Read']),
                'ipAddresses' => new OAProperty(['type' => 'array', new OAItems(['type' => 'string'])])
            ]
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUploader()
    {
        return $this->hasOne(UserResource::class, ['id' => 'uploaderID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGrader()
    {
        return $this->hasOne(UserResource::class, ['id' => 'graderID']);
    }

    /**
     * @return string|null
     */
    public function getGraderName()
    {
        return $this->grader->name ?? null;
    }

    /**
     * @return int
     */
    public function getGroupID()
    {
        return $this->task->groupID;
    }

    /**
     * @inheritdoc
     */
    public function getTask()
    {
        return $this->hasOne(TaskResource::class, ['id' => 'taskID']);
    }

    /**
     * @inheritDoc
     */
    public function getExecution()
    {
        return $this
            ->hasOne(WebAppExecutionResource::class, ['submissionID' => 'id'])
            ->onCondition(['instructorID' => Yii::$app->user->id]);
    }

    /**
     * @return string|null
     */
    public function getGitRepo()
    {
        if (!Yii::$app->params['versionControl']['enabled']) {
            return null;
        }
        if (!$this->isVersionControlled) {
            return Yii::t('app', 'Not version controlled');
        }

        return GitManager::getReadonlyUserRepositoryUrl($this->taskID, $this->uploader->userCode);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCodeCompass()
    {
        return $this->hasOne(CodeCompassInstanceResource::class, ['submissionID' => 'id']);
    }

    public function getCodeCompassID()
    {
        return $this->hasOne(CodeCompassInstanceResource::class, ['submissionID' => 'id'])
            ->one()
            ->id ?? null;
    }

    public function getCodeCheckerResult(): ActiveQuery
    {
        return $this->hasOne(CodeCheckerResultResource::class, ['id' => 'codeCheckerResultID']);
    }
}
