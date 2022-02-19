<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\ExamTestInstance;
use app\models\Model;

class ExamWriterResource extends Model implements IOpenApiFieldTypes
{
    public $testName;
    public $duration;
    public $questions;

    /**
     * ExamWriterResource constructor.
     * @param string $testName
     * @param int $duration
     * @param ExamWriterQuestionResource[] $questions
     */
    public function __construct($testName = null, $duration = null, $questions = null, $config = [])
    {
        parent::__construct($config);
        $this->testName = $testName;
        $this->duration = $duration;
        $this->questions = $questions;
    }


    public function fieldTypes(): array
    {
        return [
            'testName' => new OAProperty(['type' => 'string']),
            'duration' => new OAProperty(['type' => 'integer']),
            'questions' => new OAProperty(
                [
                    'type' => 'array',
                    new OAItems(['ref' => '#/components/schemas/Student_ExamWriterQuestionResource_Read'])
                ]
            )
        ];
    }
}
