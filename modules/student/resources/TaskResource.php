<?php

namespace app\modules\student\resources;

use app\components\GitManager;
use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\models\Task;
use app\models\User;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * Resource class for module 'Task'
 */
class TaskResource extends Task
{
    public function fields(): array
    {
        return [
            'id',
            'groupID',
            'name',
            'category',
            'translatedCategory',
            'description',
            'softDeadline',
            'hardDeadline',
            'available',
            'creatorName',
            'semesterID',
            'gitInfo',
            'autoTest',
            'passwordProtected',
            'canvasUrl',
            'appType',
        ];
    }

    public function extraFields(): array
    {
        return [
            'submissions',
            'taskFiles'
        ];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'creatorName' => new OAProperty(['type' => 'string']),
                'gitInfo' => new OAProperty(['type' => 'object']),
                'submissions' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Student_SubmissionResource_Read'])
                    ]
                ),
                'taskFiles' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Student_TaskFileResource_Read'])
                    ]
                ),
            ]
        );
    }

    public function getTaskFiles(): ActiveQuery
    {
        return $this->hasMany(TaskFileResource::class, ['taskID' => 'id'])
            ->andOnCondition(['not', ['name' => 'Dockerfile']])
            ->andOnCondition(['category' => TaskFileResource::CATEGORY_ATTACHMENT]);
    }

    public function getSubmissions(): ActiveQuery
    {
        return $this->hasMany(SubmissionResource::class, ['taskID' => 'id']);
    }

    public function getCreatorName(): string
    {
        return $this->group->isExamGroup ? '' : parent::getCreatorName();
    }

    public function getGitInfo(): ?array
    {
        if (Yii::$app->params['versionControl']['enabled'] && $this->isVersionControlled) {
            /** @var User $user */
            $user = Yii::$app->user->identity;
            $path = GitManager::getWriteableUserRepositoryUrl($this->id, $user->userCode);
            // TODO: move usage information creation to the frontend. This function should only return the path.
            $usage = 'git clone ' . $path;

            return [
                'path' => $path,
                'usage' => $usage
            ];
        } else {
            return null;
        }
    }
}
