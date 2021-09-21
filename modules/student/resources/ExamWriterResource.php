<?php

namespace app\modules\student\resources;

use app\models\ExamTestInstance;
use app\models\Model;

class ExamWriterResource extends Model
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
    public function __construct($testName, $duration, $questions)
    {
        $this->testName = $testName;
        $this->duration = $duration;
        $this->questions = $questions;
    }
}
