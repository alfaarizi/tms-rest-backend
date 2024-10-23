<?php

namespace app\modules\instructor\resources;

use app\components\GitManager;
use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\resources\SemesterResource;
use yii\helpers\ArrayHelper;
use \yii\db\ActiveQuery;

/**
 * Resource class for module 'Task'
 */
class TaskResource extends \app\models\Task
{
    public function fields(): array
    {
        return [
            'id',
            'name',
            'category',
            'translatedCategory',
            'description',
            'softDeadline',
            'hardDeadline',
            'available',
            'autoTest',
            'isVersionControlled',
            'groupID',
            'semesterID',
            'creatorName',
            'testOS',
            'showFullErrorMsg',
            'imageName',
            'compileInstructions',
            'runInstructions',
            'port',
            'appType',
            'password',
            'passwordProtected',
            'canvasUrl',
            'codeCompassCompileInstructions',
            'codeCompassPackagesInstallInstructions',
            'staticCodeAnalysis',
            'staticCodeAnalyzerTool',
            'staticCodeAnalyzerInstructions',
            'codeCheckerCompileInstructions',
            'codeCheckerToggles',
            'codeCheckerSkipFile',
        ];
    }

    public function extraFields(): array
    {
        return [
            'submissions',
            'taskFiles',
            'group',
            'semester',
            'taskLevelGitRepo',
        ];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'submissions' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Instructor_SubmissionResource_Read'])
                    ]
                ),
                'taskFiles' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Instructor_TaskFileResource_Read'])
                    ]
                ),
                'group' => new OAProperty(['ref' => '#/components/schemas/Instructor_GroupResource_Read']),
                'semester' => new OAProperty(['ref' => '#/components/schemas/Common_SemesterResource_Read']),
                'taskLevelGitRepo' => new OAProperty(['type' => 'string']),
            ]
        );
    }

    public function getTaskLevelGitRepo(): ?string
    {
        if (\Yii::$app->params['versionControl']['enabled'] && $this->isVersionControlled) {
            return GitManager::getReadonlyTaskLevelRepositoryUrl($this->id);
        }
        return null;
    }

    public function getTaskFiles(): ActiveQuery
    {
        return TaskFileResource::find()->where(['taskID' => $this->id])->andOnCondition(
            [
                'not',
                ['name' => 'Dockerfile']
            ]
        );
    }

    public function getSubmissions(): ActiveQuery
    {
        return $this->hasMany(SubmissionResource::class, ['taskID' => 'id']);
    }

    public function getGroup(): ActiveQuery
    {
        return $this->hasOne(GroupResource::class, ['id' => 'groupID']);
    }

    public function getSemester(): ActiveQuery
    {
        return $this->hasOne(SemesterResource::class, ['id' => 'semesterID']);
    }
}
