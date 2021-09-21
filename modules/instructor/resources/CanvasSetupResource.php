<?php

namespace app\modules\instructor\resources;

use app\models\Model;

class CanvasSetupResource extends Model
{
    public $canvasCourse;
    public $canvasSection;

    public function rules()
    {
        return [
            [['canvasCourse', 'canvasSection'], 'integer'],
            [['canvasCourse', 'canvasSection'], 'required'],
        ];
    }
}
