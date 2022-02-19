<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\models\Task;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * Resource class for module 'Task'
 */
class TaskResource extends Task
{
    public function fields()
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
            'autoTest'
        ];
    }

    public function extraFields()
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

    public function getInstructorFiles()
    {
        return $this->hasMany(InstructorFileResource::class, ['taskID' => 'id'])->andOnCondition(
            [
                'not',
                ['name' => 'Dockerfile']
            ]
        );
    }

    /**
     * @return ActiveQuery
     */
    public function getStudentFiles()
    {
        return $this->hasMany(StudentFileResource::class, ['taskID' => 'id']);
    }

    /**
     * @return string
     */
    public function getCreatorName()
    {
        return $this->group->isExamGroup ? '' : parent::getCreatorName();
    }

    /**
     * @return array|null
     */
    public function getGitInfo()
    {
        if (Yii::$app->params['versionControl']['enabled'] && $this->isVersionControlled) {
            // Search for random string id directory
            $path = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' . $this->id . '/' . strtolower(
                    Yii::$app->user->identity->neptun
                ) . '/';
            $dirs = FileHelper::findDirectories($path, ['recursive' => false]);
            rsort($dirs);
            $path = Yii::$app->request->hostInfo . Yii::$app->params['versionControl']['basePath'] . '/' . $this->id . '/' . strtolower(
                    Yii::$app->user->identity->neptun
                ) . '/' . basename($dirs[0]);
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
