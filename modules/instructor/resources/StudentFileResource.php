<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use Yii;
use app\models\StudentFile;
use app\resources\UserResource;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

class StudentFileResource extends StudentFile
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'id',
            'name',
            'isAccepted',
            'grade',
            'notes',
            'isVersionControlled',
            'translatedIsAccepted',
            'graderName',
            'errorMsg',
            'taskID',
            'groupID',
            'gitRepo',
            'uploaderID',
            'uploadTime',
            'delay',
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
            'task'
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
     * @return string|null
     */
    public function getGitRepo()
    {
        if (!Yii::$app->params['versionControl']['enabled']) {
            return null;
        }
        if (!$this->isVersionControlled) {
            return Yii::t('app', 'Not version controlled');
        } elseif ($this->uploadTime == null) {
            return Yii::t('app', 'No file uploaded');
        }
        $repopath = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' . $this->taskID . '/' . strtolower($this->uploader->neptun) . '/';
        $dirs = FileHelper::findDirectories($repopath, ['recursive' => false]);
        rsort($dirs);
        $path = Yii::$app->params['versionControl']['basePath'] . '/' . $this->taskID . '/' . strtolower($this->uploader->neptun) . '/' . basename($dirs[1]);
        return Yii::$app->request->hostInfo . $path;
    }
}
