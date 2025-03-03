<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;

class QuizWriterQuestionResource extends Model implements IOpenApiFieldTypes
{
    public $questionID;
    public $text;
    public $answers;

    /**
     * QuizWriterQuestionResource constructor.
     * @param number $id
     * @param string $text
     */
    public function __construct($id = null, $text = null, $config = [])
    {
        parent::__construct($config);
        $this->questionID = $id;
        $this->text = $text;
        $this->answers = [];
    }
    public function fields(): array
    {
        return [
            'questionID',
            'text',
            'answers'
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'questionID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'text' => new OAProperty(['type' => 'string']),
            'answers' => new OAProperty(
                [
                    'type' => 'array',
                    new OAItems(['ref' => '#/components/schemas/Student_QuizWriterAnswerResource_Read'])
                ],
            ),
        ];
    }
}
