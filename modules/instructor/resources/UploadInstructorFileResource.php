<?php

namespace app\modules\instructor\resources;

use app\models\InstructorFile;
use app\models\Model;
use yii\web\UploadedFile;

/**
 * Class UploadInstructorFileResource
 * @property integer $taskID
 * @property string $category
 * @property UploadedFile[] $files
 */

class UploadInstructorFileResource extends Model
{
    public $taskID;
    public $category = InstructorFile::CATEGORY_ATTACHMENT;
    public $files;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['taskID', 'category', 'files'], 'required'],
            [['taskID'], 'integer'],
            [['taskID'], 'checkIfTaskExists'],
            [['category'], 'in', 'range' => array_keys(InstructorFile::categoryMap())],
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
