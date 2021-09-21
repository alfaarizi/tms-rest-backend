<?php

namespace app\modules\student\resources;

use app\models\Model;

class ExamWriterAnswerResource extends Model
{
    public $id;
    public $text;

    /**
     * ExamWriterAnswerResource constructor.
     * @param $id
     * @param $text
     */
    public function __construct($id, $text)
    {
        $this->id = $id;
        $this->text = $text;
    }

    public function fields()
    {
        return ['id', 'text'];
    }
}
