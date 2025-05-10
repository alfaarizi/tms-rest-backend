<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\TaskFile;
use app\models\Model;
use yii\web\UploadedFile;

/**
 * Class UploadTaskFileResource
 * @property integer $taskID
 * @property string $category
 * @property UploadedFile[] $files
 * @property bool $override
 */

class UploadTaskFileResource extends Model implements IOpenApiFieldTypes
{
    public int $taskID;
    public string $category = TaskFile::CATEGORY_ATTACHMENT;
    public array $files;
    public bool $override = false;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['taskID', 'category', 'files'], 'required'],
            [['taskID'], 'integer'],
            [['override'], 'boolean'],
            [['taskID'], 'checkIfTaskExists'],
            [['category'], 'in', 'range' => array_keys(TaskFile::categoryMap())],
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
            'category' => new OAProperty(['type' => 'string']),
            'files' => new OAProperty(['type' => 'array', new OAItems(['type' => 'string', 'format' => 'binary'])]),
            'override' => new OAProperty(['type' => 'bool']),
        ];
    }
}
