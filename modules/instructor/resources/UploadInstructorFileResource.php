<?php

namespace app\modules\instructor\resources;

use app\models\Model;
use yii\web\UploadedFile;

/**
 * Class UploadInstructorFileResource
 * @property integer taskID
 * @property UploadedFile[] $files
 */

class UploadInstructorFileResource extends Model
{
    public $taskID;
    public $files;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['taskID', 'files'], 'required'],
            [['taskID'], 'integer'],
            [['taskID'], 'checkIfTaskExists'],
            [['files'], 'file', 'skipOnEmpty' => false, 'maxFiles' => 20],
        ];
    }

    public function checkIfTaskExists()
    {
        $task = TaskResource::findOne($this->taskID);
        if (is_null($task)) {
            $this->addError('taskID', 'Invalid taskID');
        }
    }
}
