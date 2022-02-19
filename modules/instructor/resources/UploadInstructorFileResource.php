<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;
use yii\web\UploadedFile;

/**
 * Class UploadInstructorFileResource
 * @property integer taskID
 * @property UploadedFile[] $files
 */

class UploadInstructorFileResource extends Model implements IOpenApiFieldTypes
{
    public $taskID;
    public $files;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['taskID', 'files'], 'required'],
            [['taskID'], 'integer'],
            [['taskID'], 'checkIfTaskExists'],
            [['files'], 'file', 'skipOnEmpty' => false, 'maxFiles' => 20],
        ];
    }

    public function checkIfTaskExists(): void
    {
        $task = TaskResource::findOne($this->taskID);
        if (is_null($task)) {
            $this->addError('taskID', 'Invalid taskID');
        }
    }

    public function fieldTypes(): array
    {
        return [
            'taskID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'files' => new OAProperty(['type' => 'array', new OAItems(['type' => 'string', 'format' => 'binary'])]),
        ];
    }
}
