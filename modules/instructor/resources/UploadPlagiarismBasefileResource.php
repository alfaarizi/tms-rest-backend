<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Course;
use app\models\Model;
use yii\web\UploadedFile;

class UploadPlagiarismBasefileResource extends Model implements IOpenApiFieldTypes
{
    public int $courseID;
    /** @var UploadedFile[] */
    public array $files;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['courseID', 'files'], 'required'],
            [['courseID'], 'integer'],
            [['courseID'], 'exist', 'targetClass' => Course::class, 'targetAttribute' => ['courseID' => 'id']],
            [['files'], 'file', 'skipOnEmpty' => false, 'maxFiles' => 20],
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'courseID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'files' => new OAProperty(['type' => 'array', new OAItems(['type' => 'string', 'format' => 'binary'])]),
        ];
    }
}
