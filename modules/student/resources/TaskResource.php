<?php

namespace app\modules\student\resources;

use app\components\GitManager;
use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\models\Task;
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
            'studentFiles',
            'instructorFiles'
        ];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'creatorName' => new OAProperty(['type' => 'string']),
                'gitInfo' => new OAProperty(['type' => 'object']),
                'studentFiles' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Student_StudentFileResource_Read'])
                    ]
                ),
                'instructorFiles' => new OAProperty(
                    [
                        'type' => 'array',
                        new OAItems(['ref' => '#/components/schemas/Student_InstructorFileResource_Read'])
                    ]
                ),
            ]
        );
    }

    public function getInstructorFiles(): ActiveQuery
    {
        return $this->hasMany(InstructorFileResource::class, ['taskID' => 'id'])
            ->andOnCondition(['not', ['name' => 'Dockerfile']])
            ->andOnCondition(['category' => InstructorFileResource::CATEGORY_ATTACHMENT]);
    }

    public function getStudentFiles(): ActiveQuery
    {
        return $this->hasMany(StudentFileResource::class, ['taskID' => 'id']);
    }

    public function getCreatorName(): string
    {
        return $this->group->isExamGroup ? '' : parent::getCreatorName();
    }

    public function getGitInfo(): ?array
    {
        if (Yii::$app->params['versionControl']['enabled'] && $this->isVersionControlled) {
            $path = GitManager::getWriteableUserRepositoryUrl($this->id, Yii::$app->user->identity->neptun);
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
