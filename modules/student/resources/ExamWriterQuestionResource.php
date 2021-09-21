<?php

namespace app\modules\student\resources;

use app\models\Model;

class ExamWriterQuestionResource extends Model
{
    public $questionID;
    public $text;
    public $answers;

    /**
     * ExamWriterQuestionResource constructor.
     * @param number $id
     * @param string $text
     * @param ExamWriterQuestionResource $answers
     */
    public function __construct($id, $text)
    {
        $this->questionID = $id;
        $this->text = $text;
        $this->answers = [];
    }

    public function fields()
    {
        return ['questionID', 'text', 'answers'];
    }
}
