<?php

namespace app\modules\instructor\resources;

class CreatePlagiarismResource extends \yii\base\Model
{
    public function rules()
    {
        return [
            [['name', 'selectedTasks', 'selectedStudents', 'ignoreThreshold'], 'required'],
            [['name', 'description'], 'string'],
            [['ignoreThreshold'], 'integer'],
            ['selectedTasks', 'each', 'rule' => ['integer']],
            ['selectedStudents', 'each', 'rule' => ['integer']],
        ];
    }

    public $name;
    public $description;
    public $selectedTasks;
    public $selectedStudents;
    public $ignoreThreshold;
}
