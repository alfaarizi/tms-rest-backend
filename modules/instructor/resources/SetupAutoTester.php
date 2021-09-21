<?php

namespace app\modules\instructor\resources;

use app\models\Model;

class SetupAutoTester extends Model
{
    public function rules()
    {
        return [
            [['testOS'], 'required'],
            [['testOS'], 'string'],
            [['imageName', 'runInstructions'], 'string', 'max' => 255],
            [['compileInstructions'], 'string', 'max' => 1000],
            [['showFullErrorMsg'], 'boolean'],
            [['files'], 'file', 'skipOnEmpty' => true, 'maxFiles' => 20]
        ];
    }

    public $testOS;
    public $imageName;
    public $compileInstructions;
    public $runInstructions;
    public $showFullErrorMsg;
    public $files;
}
