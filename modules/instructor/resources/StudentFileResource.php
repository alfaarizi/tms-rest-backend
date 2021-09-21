<?php

namespace app\modules\instructor\resources;

use Yii;
use app\models\StudentFile;
use app\resources\UserResource;
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
            'uploadTime' => function() {
                $uploadTime = $this->uploadTime;
                if (is_null($uploadTime)) {
                    return Yii::t('app', '(No file uploaded)');
                }
                $softDeadline = $this->task->softDeadline;

                $content = $uploadTime;
                if (
                    !empty($softDeadline) &&
                    strtotime($uploadTime) > strtotime($softDeadline)
                ) {
                    $timeSwitchHourDelay = 0;

                    $softDeadlineInTime = strtotime($softDeadline);
                    $softDeadlineInDaylight = date('I', $softDeadlineInTime);

                    $uploadTimeInTime = strtotime($uploadTime);
                    $uploadTimeInDaylight = date('I', $uploadTimeInTime);

                    if ($softDeadlineInDaylight == 0 && $uploadTimeInDaylight == 1) { //$softDeadlineInDaylight in winter time && $uploadTimeInDaylight in summer time
                        $timeSwitchHourDelay -= 1;
                    } elseif ($softDeadlineInDaylight == 1 && $uploadTimeInDaylight == 0) { //$softDeadlineInDaylight in summer time && $uploadTimeInDaylight in winter time
                        $timeSwitchHourDelay += 1;
                    }

                    $delay = ceil(
                        (((float)($uploadTimeInTime - $softDeadlineInTime) / 3600) + $timeSwitchHourDelay) / 24
                    );

                    $content .= ' (' . Yii::t(
                            'app',
                            '+{days} days',
                            ['days' => $delay]
                        ) . ')';
                }

                return $content;
            },
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
