<?php

namespace app\modules\student\resources;

use Yii;
use yii\helpers\FileHelper;

/**
 * Resource class for module 'Task'
 */
class TaskResource extends \app\models\Task
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

    public function getInstructorFiles()
    {
        return $this->hasMany(InstructorFileResource::class, ['taskID' => 'id'])
            ->andOnCondition(['not', ['name' => 'Dockerfile']])
            ->andOnCondition(['category' => InstructorFileResource::CATEGORY_ATTACHMENT]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStudentFiles()
    {
        return $this->hasMany(StudentFileResource::class, ['taskID' => 'id']);
    }

    /**
     * @return string
     */
    public function getCreatorName() {
        return $this->group->isExamGroup ? '' : parent::getCreatorName();
    }

    /**
     * @return array|null
     */
    public function getGitInfo()
    {
        if (Yii::$app->params['versionControl']['enabled'] && $this->isVersionControlled) {
            // Search for random string id directory
            $path = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' . $this->id . '/' . strtolower(Yii::$app->user->identity->neptun) . '/';
            $dirs = FileHelper::findDirectories($path, ['recursive' => false]);
            rsort($dirs);
            $path =  Yii::$app->request->hostInfo . Yii::$app->params['versionControl']['basePath'] . '/' . $this->id . '/' . strtolower(Yii::$app->user->identity->neptun) . '/' . basename($dirs[0]);
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
