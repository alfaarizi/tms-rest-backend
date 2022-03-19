<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class ExamWriterAnswerResource extends Model implements IOpenApiFieldTypes
{
    public $id;
    public $text;

    /**
     * ExamWriterAnswerResource constructor.
     * @param $id
     * @param $text
     */
    public function __construct($id = null, $text = null, $config = [])
    {
        parent::__construct($config);
        $this->id = $id;
        $this->text = $text;
    }

    public function fields()
    {
        return [
            'id',
            'text'
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'text' => new OAProperty(['type' => 'string']),
        ];
    }
}
